<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\OrdersModel;
use App\Models\ShopModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfileManagementController extends Controller
{
    public function getClient(Request $request)
    {
        try {
            $user = JWTAuth::user();
            $shop = ShopModel::where('user_id', $user->id)->first();
            $keySearch = $request->get('key_search');

            if (!$shop) {
                return response()->json(['message' => 'Cửa hàng không tồn tại.', 'status' => false]);
            }

            $customersQuery = DB::table('orders as o')
                ->join('orders_item as oi', 'o.id', '=', 'oi.order_id')
                ->join('users as u', 'o.user_id', '=', 'u.id')
                ->join('province as p', 'o.province_id', '=', 'p.province_id')
                ->join('district as d', 'o.district_id', '=', 'd.district_id')
                ->join('wards as w', 'o.ward_id', '=', 'w.wards_id')
                ->where('oi.shop_id', $shop->id)
                ->select(
                    'o.user_id',
                    'u.name',
                    'u.phone',
                    'u.avatar',
                    DB::raw("CONCAT(o.address_detail, ', ', w.name, ', ', d.name, ', ', p.name) as full_address"),
                    DB::raw('SUM(oi.total_money) as total_spent'),
                    DB::raw('COUNT(DISTINCT o.id) as total_orders')
                );
            if ($keySearch) {
                $customersQuery->where('u.name', 'LIKE', '%' . $keySearch . '%')
                    ->orWhere('u.phone', 'LIKE', '%' . $keySearch . '%');
            }
            $customers = $customersQuery->groupBy('o.user_id', 'u.name', 'u.phone', 'u.avatar', 'full_address')
                ->orderBy('total_spent', 'desc')
                ->paginate(16);

            if ($customers->isEmpty()) {
                return response()->json(['message' => 'Không có khách hàng nào đã mua hàng từ shop này.', 'status' => false]);
            }

            return response()->json(['message' => 'Lấy danh sách khách hàng thành công.', 'data' => $customers, 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function userOrder(Request $request)
    {
        $user = JWTAuth::user();

        $keySearch = $request->input('key_search');
        $status = $request->input('status');

        $ordersQuery = DB::table('orders as o')
            ->join('orders_item as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->join('shop as s', 'o.shop_id', '=', 's.id')
            ->where('o.user_id', $user->id)
            ->select('o.id', 'o.order_code', 'o.status', 'o.created_at', 'o.total_payment','s.name as shop_name')
            ->groupBy('o.id', 'o.order_code','s.name','o.status','o.created_at', 'o.total_payment','shop_name');

        if ($keySearch) {
            $ordersQuery->where(function ($query) use ($keySearch) {
                $query->where('o.order_code', 'LIKE', '%' . $keySearch . '%')
                    ->orWhere('p.name', 'LIKE', '%' . $keySearch . '%');
            });
        }

        if ($status) {
            $ordersQuery->where('status', $status);
        }
        $orders = $ordersQuery->orderBy('o.created_at', 'desc')->paginate(15);

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'Không có đơn hàng nào.', 'status' => false]);
        }

        $orderDetails = [];

        foreach ($orders as $order) {
            $orderItems = DB::table('orders_item as oi')
                ->join('products as p', 'oi.product_id', '=', 'p.id')
                ->where('oi.order_id', $order->id)
                ->select('p.name', 'p.src', 'p.unit', 'p.en_unit', 'oi.quantity', 'oi.price', 'oi.total_money')
                ->get();
            foreach ($orderItems as $items) {
                $items->src = json_decode($items->src, true);
            }

            $orderDetails[] = [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'shop_name' => $order->shop_name,
                'total_payment' => $order->total_payment,
                'status' => $order->status,
                'date' => $order->created_at,
                'items' => $orderItems
            ];
        }

        return response()->json(['message' => 'Lấy dữ liệu thành công', 'data' => $orderDetails, 'status' => true]);
    }

    public function userOrderCancel(Request $request)
    {
        try {
            $order_id = $request->get('order_id');
            $order = OrdersModel::find($order_id);
            if ($order->status != 0) {
                return response()->json(['message' => 'Bạn không có quyền hủy đơn khi ở trạng thái này', 'status' => true]);
            }
            $order->status = 4;
            $order->save();

            return response()->json(['message' => 'Hủy đơn hàng thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function detailUserOrder($id)
    {
        try {
            $order = DB::table('orders as o')
                ->join('province as p', 'o.province_id', '=', 'p.province_id')
                ->join('district as d', 'o.district_id', '=', 'd.district_id')
                ->join('wards as w', 'o.ward_id', '=', 'w.wards_id')
                ->join('shop as s', 'o.shop_id', '=', 's.id')
                ->join('order_total as ot', function ($join) use ($id) {
                    $join->on(DB::raw("FIND_IN_SET(o.id, ot.order_id)"), '>', DB::raw(0));
                })
                ->where('o.id', $id)
                ->select(
                    'o.id as order_id',
                    'o.order_code',
                    'o.name',
                    'o.phone',
                    DB::raw("CONCAT(o.address_detail, ', ', w.name, ', ', d.name, ', ', p.name) as full_address"),
                    'o.note',
                    'o.shipping_unit',
                    'ot.type_payment',
                    'o.commodity_money',
                    'o.shipping_fee',
                    'ot.exchange_points',
                    'o.total_payment',
                    'o.status',
                    'o.created_at',
                    's.name as shop_name'
                )
                ->first();
            if (!$order) {
                return response()->json(['message' => 'Đơn hàng không tồn tại.', 'status' => false]);
            }
            $orderItems = DB::table('orders_item as oi')
                ->join('products as p', 'oi.product_id', '=', 'p.id')
                ->where('oi.order_id', $order->order_id)
                ->select('p.name','p.name_en','p.unit','p.en_unit', 'p.src', 'oi.quantity', 'oi.price', 'oi.total_money')
                ->get();

            foreach ($orderItems as $item) {
                $item->src = json_decode($item->src, true);
            }
            $orderDetails = [
                'order_id' => $order->order_id,
                'order_code' => $order->order_code,
                'customer_name' => $order->name,
                'customer_phone' => $order->phone,
                'full_address' => $order->full_address,
                'note' => $order->note,
                'shipping_unit' => $order->shipping_unit,
                'type_payment' => $order->type_payment,
                'commodity_money' => $order->commodity_money,
                'shipping_fee' => $order->shipping_fee,
                'exchange_points' => $order->exchange_points,
                'total_payment' => $order->total_payment,
                'status' => $order->status,
                'created_at' => $order->created_at,
                'shop_name' => $order->shop_name,
                'items' => $orderItems
            ];

            return response()->json(['message' => 'Lấy dữ liệu thành công','data'=>$orderDetails, 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function shopOrder(Request $request)
    {
        $user = JWTAuth::user();
        $shop = ShopModel::where('user_id',$user->id)->first();
        if (!$shop){
            return response()->json(['message'=>'Shop không tồn tại','status'=>false]);
        }

        $keySearch = $request->input('key_search');
        $status = $request->input('status');

        $ordersQuery = DB::table('orders as o')
            ->join('orders_item as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->where('o.shop_id', $shop->id)
            ->select('o.id', 'o.order_code', 'o.status', 'o.created_at', 'o.total_payment')
            ->groupBy('o.id', 'o.order_code','o.status', 'o.created_at', 'o.total_payment');

        if ($keySearch) {
            $ordersQuery->where(function ($query) use ($keySearch) {
                $query->where('o.order_code', 'LIKE', '%' . $keySearch . '%')
                    ->orWhere('p.name', 'LIKE', '%' . $keySearch . '%');
            });
        }

        if ($status) {
            $ordersQuery->where('o.status', $status);
        }
        $orders = $ordersQuery->orderBy('o.created_at', 'desc')->paginate(10);

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'Không có đơn hàng nào.', 'status' => false]);
        }

        $orderDetails = [];

        foreach ($orders as $order) {
            $orderItems = DB::table('orders_item as oi')
                ->join('products as p', 'oi.product_id', '=', 'p.id')
                ->where('oi.order_id', $order->id)
                ->select('p.name', 'p.src', 'p.unit', 'p.en_unit', 'oi.quantity', 'oi.price', 'oi.total_money')
                ->get();
            foreach ($orderItems as $items) {
                $items->src = json_decode($items->src, true);
            }

            $orderDetails[] = [
                'order_id' => $order->id,
                'order_code' => $order->order_code,
                'total_payment' => $order->total_payment,
                'status' => $order->status,
                'date' => $order->created_at,
                'items' => $orderItems
            ];
        }

        return response()->json(['message' => 'Lấy dữ liệu thành công', 'data' => $orderDetails, 'status' => true]);
    }

    public function shopOrderStatus(Request $request)
    {
        try {
            $order_id = $request->get('order_id');
            $status = $request->get('status');
            $order = OrdersModel::find($order_id);
            $order->status = $status;
            $order->save();

            return response()->json(['message' => 'Cập nhật trạng thái đơn hàng thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

}
