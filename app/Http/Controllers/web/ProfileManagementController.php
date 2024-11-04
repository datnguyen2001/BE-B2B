<?php

namespace App\Http\Controllers\web;

use App\Events\NotifyUser;
use App\Http\Controllers\Controller;
use App\Models\NotificationModel;
use App\Models\OrdersModel;
use App\Models\ShopModel;
use App\Models\User;
use Carbon\Carbon;
use Google\Client;
use Google\Service\AnalyticsData;
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
                ->leftJoin('delivery_address as da', function ($join) {
                    $join->on('u.id', '=', 'da.user_id')
                        ->where('da.display', '=', 1);
                })
                ->leftJoin('province as p', 'da.province_id', '=', 'p.province_id')
                ->leftJoin('district as d', 'da.district_id', '=', 'd.district_id')
                ->leftJoin('wards as w', 'da.ward_id', '=', 'w.wards_id')
                ->where('o.shop_id', $shop->id)
                ->select(
                    'o.user_id',
                    'u.name',
                    'u.phone',
                    'u.avatar',
                    DB::raw("IFNULL(CONCAT(da.address_detail, ', ', w.name, ', ', d.name, ', ', p.name), 'Địa chỉ chưa cập nhật') as full_address"),
                    DB::raw('SUM(oi.total_money) as total_spent'),
                    DB::raw('COUNT(DISTINCT o.id) as total_orders')
                )
                ->groupBy('o.user_id', 'u.name', 'u.phone', 'u.avatar', 'full_address');

            if ($keySearch) {
                $customersQuery->where(function ($query) use ($keySearch) {
                    $query->where('u.name', 'LIKE', '%' . $keySearch . '%')
                        ->orWhere('u.phone', 'LIKE', '%' . $keySearch . '%');
                });
            }

            $customers = $customersQuery
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
            ->select('o.id', 'o.order_code', 'o.status', 'o.created_at', 'o.total_payment', 's.name as shop_name')
            ->groupBy('o.id', 'o.order_code', 's.name', 'o.status', 'o.created_at', 'o.total_payment', 'shop_name');

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

            $shop = ShopModel::find($order->shop_id);
            $user = User::find($order->user_id);
            $notification = new NotificationModel();
            $notification->sender_id = $order->user_id;
            $notification->receiver_id = $shop->user_id;
            $notification->message = 'Đơn hàng ' . $order->order_code . ' đã bị hủy bởi ' . $user->name;
            $notification->is_read = 0;
            $notification->type = 'create-order';
            $notification->save();
            broadcast(new NotifyUser($notification->message, $notification->receiver_id, $user->avatar, $user->name, $notification->type))->toOthers();

            $shop = ShopModel::find($order->shop_id);
            $receiver = User::find($shop->user_id);
            $notification = new NotificationModel();
            $notification->sender_id = $shop->user_id;
            $notification->receiver_id = $order->user_id;
            $notification->message = 'Bạn vừa hủy đơn hàng ' . $order->order_code;
            $notification->is_read = 0;
            $notification->type = 'create-order';
            $notification->save();
            broadcast(new NotifyUser($notification->message, $notification->receiver_id, $receiver->avatar, $receiver->name, $notification->type))->toOthers();

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
                ->select('p.name', 'p.name_en', 'p.unit', 'p.en_unit', 'p.src', 'oi.quantity', 'oi.price', 'oi.total_money')
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

            return response()->json(['message' => 'Lấy dữ liệu thành công', 'data' => $orderDetails, 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function shopOrder(Request $request)
    {
        $user = JWTAuth::user();
        $shop = ShopModel::where('user_id', $user->id)->first();
        if (!$shop) {
            return response()->json(['message' => 'Shop không tồn tại', 'status' => false]);
        }

        $keySearch = $request->input('key_search');
        $status = $request->input('status');

        $ordersQuery = DB::table('orders as o')
            ->join('orders_item as oi', 'o.id', '=', 'oi.order_id')
            ->join('products as p', 'oi.product_id', '=', 'p.id')
            ->where('o.shop_id', $shop->id)
            ->select('o.id', 'o.order_code', 'o.status', 'o.created_at', 'o.total_payment')
            ->groupBy('o.id', 'o.order_code', 'o.status', 'o.created_at', 'o.total_payment');

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
            if ($order->status == 1) {
                $status = 'Chờ lấy hàng';
            }elseif ($order->status == 2){
                $status = 'Đang giao';
            }elseif ($order->status == 3){
                $status = 'Đã giao';
            }elseif ($order->status == 4){
                $status = 'Đã hủy';
            }else{
                $status = 'Hoàn đơn';
            }

            $shop = ShopModel::find($order->shop_id);
            $receiver = User::find($shop->user_id);
            $notification = new NotificationModel();
            $notification->sender_id = $shop->user_id;
            $notification->receiver_id = $order->user_id;
            $notification->message = 'Đơn hàng ' . $order->order_code . ' của bạn vừa thay đổi trạng thái thành ' . $status;
            $notification->is_read = 0;
            $notification->type = 'create-order';
            $notification->save();
            broadcast(new NotifyUser($notification->message, $notification->receiver_id, $receiver->avatar, $receiver->name, $notification->type))->toOthers();

            return response()->json(['message' => 'Cập nhật trạng thái đơn hàng thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function statistical()
    {
        try {
            $user = JWTAuth::user();
            $shop = ShopModel::where('user_id', $user->id)->first();

            // Thống kê số đơn hàng theo trạng thái
            $orders = DB::table('orders')
                ->select(
                    DB::raw('COUNT(CASE WHEN status = 0 THEN 1 END) as pending_orders'),
                    DB::raw('COUNT(CASE WHEN status = 1 THEN 1 END) as awaiting_orders'),
                    DB::raw('COUNT(CASE WHEN status = 3 THEN 1 END) as completed_orders'),
                    DB::raw('COUNT(CASE WHEN status = 4 THEN 1 END) as cancels_orders')
                )
                ->where('shop_id', $shop->id)
                ->first();

            // Thống kê số sản phẩm hết hàng
            $outOfStockProducts = DB::table('products')
                ->where('shop_id', $shop->id)
                ->where('quantity', '<=', 0)
                ->where('status', '=', 1)
                ->where('display', '=', 1)
                ->count();

            // Thống kê số sản phẩm bị từ chối
            $rejectedProducts = DB::table('products')
                ->where('shop_id', $shop->id)
                ->where('quantity', '<=', 0)
                ->where('status', '=', 0)
                ->where('display', '=', 1)
                ->count();

            // Thống kê số tin nhắn chưa đọc
            $unreadSenderCount = DB::table('messages')
                ->where('is_read', 0)
                ->where('receiver_id', $user->id)
                ->distinct('sender_id')
                ->count('sender_id');

            // Thống kê số đơn hàng theo ngày
            $dailyOrders = DB::table('orders')
                ->select(
                    DB::raw('DATE(created_at) as order_date'),
                    DB::raw('COUNT(*) as total_orders')
                )
                ->where('shop_id', $shop->id)
                ->whereDate('created_at', '>=', Carbon::now()->subDays(7))
                ->groupBy(DB::raw('DATE(created_at)'))
                ->orderBy('order_date', 'desc')
                ->get();

            $client = new Client();
            $client->setAuthConfig(storage_path('app/google/b2b-ga4.json'));
            $client->addScope(AnalyticsData::ANALYTICS_READONLY);

            $analytics = new AnalyticsData($client);
            $propertyId = 'properties/463728937';

            // Truy xuất dữ liệu
            $request = new AnalyticsData\RunReportRequest([
                'dateRanges' => [['startDate' => '30daysAgo', 'endDate' => 'today']],
                'dimensions' => [['name' => 'date'], ['name' => 'pagePath']],
                'metrics' => [['name' => 'screenPageViews']],
                'dimensionFilter' => [
                    'filter' => [
                        'fieldName' => 'pagePath',
                        'stringFilter' => [
                            'matchType' => 'EXACT',
                            'value' => '/'
                        ]
                    ]
                ]
            ]);
            $response = $analytics->properties->runReport($propertyId, $request);

            $ga4Data = [];
            foreach ($response->getRows() as $row) {
                $ga4Data[] = [
                    'date' => $row->getDimensionValues()[0]->getValue(),
                    'pagePath' => $row->getDimensionValues()[1]->getValue(),
                    'screenPageViews' => $row->getMetricValues()[0]->getValue()
                ];
            }

            return response()->json([
                'message' => 'Lấy danh sách thống kê thành công.',
                'data' => [
                    'orders' => $orders,
                    'daily_orders' => $dailyOrders,
                    'out_of_stock_products' => $outOfStockProducts,
                    'unreadSenderCount' => $unreadSenderCount,
                    'rejectedProducts' => $rejectedProducts,
                    'ga4' => $ga4Data
                ],
                'status' => true
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

}
