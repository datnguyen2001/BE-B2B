<?php

namespace App\Http\Controllers\admin;

use App\Events\NotifyUser;
use App\Http\Controllers\Controller;
use App\Models\DistrictModel;
use App\Models\NotificationModel;
use App\Models\OrdersItemModel;
use App\Models\OrdersModel;
use App\Models\ProductsModel;
use App\Models\ProvinceModel;
use App\Models\ShopModel;
use App\Models\User;
use App\Models\WardsModel;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function getDataOrder(Request $request, $status)
    {
        try {
            $titlePage = 'Quản lý đơn hàng';
            $page_menu = 'order';
            $page_sub = 'order';
            $listData = OrdersModel::query();
            if ($status !== 'all') {
                $listData = $listData->where('status', $status);
            }
            $key_search = $request->get('search');
            if (isset($key_search)) {
                $listData = $listData->where(function ($listData) use ($key_search) {
                    return $listData->where('name', 'like', '%' . $key_search . '%')->orWhere('phone', 'like', '%' . $key_search . '%')
                        ->orWhere('order_code', 'LIKE', '%' . $key_search . '%');
                });
            }
            $listData = $listData->orderBy('updated_at', 'desc')->paginate(10);
            foreach ($listData as $item) {
                $province = ProvinceModel::where('province_id', $item->province_id)->first();
                $district = DistrictModel::where('district_id', $item->district_id)->first();
                $ward = WardsModel::where('wards_id', $item->ward_id)->first();
                $item->status_name = $this->checkStatusOrder($item);
                $item->full_address = $item->address_detail . ', ' . $ward->name . ', ' . $district->name . ', ' . $province->name;
            }
            $order_all = OrdersModel::count();
            $order_pending = OrdersModel::where('status', 0)->count();
            $order_confirm = OrdersModel::where('status', 1)->count();
            $order_delivery = OrdersModel::where('status', 2)->count();
            $order_complete = OrdersModel::where('status', 3)->count();
            $order_cancel = OrdersModel::where('status', 4)->count();
            $return_refund = OrdersModel::where('status', 5)->count();
            return view('admin.order.index', compact('titlePage', 'page_menu', 'listData', 'page_sub', 'order_pending', 'order_confirm',
                'order_delivery', 'order_complete', 'order_cancel', 'status', 'order_all', 'return_refund'));
        } catch (\Exception $exception) {
            dd($exception);
        }
    }

    public function orderDetail($order_id)
    {
        try {
            $titlePage = 'Chi tiết đơn hàng';
            $page_menu = 'order';
            $page_sub = 'order';
            $listData = OrdersModel::find($order_id);
            if ($listData) {
                $order_item = OrdersItemModel::where('order_id', $order_id)->get();
                $listData->shop = ShopModel::find($listData->shop_id);
                foreach ($order_item as $item) {
                    $product = ProductsModel::find($item->product_id);
                    $item->product_name = $product->name;
                    $item->product_image = json_decode($product->src, true);

                }
                $province = ProvinceModel::where('province_id', $listData->province_id)->first();
                $district = DistrictModel::where('district_id', $listData->district_id)->first();
                $ward = WardsModel::where('wards_id', $listData->ward_id)->first();
                $listData['full_address'] = $listData->address_detail . ', ' . $ward->name . ', ' . $district->name . ', ' . $province->name;
                $listData['status_name'] = $this->checkStatusOrder($listData);
                $listData['order_item'] = $order_item;

                return view('admin.order.detail', compact('titlePage', 'page_menu', 'listData', 'page_sub', 'province', 'district', 'ward'));
            } else {
                toastr()->error('Đơn hàng không tồn tại');
                return back();
            }
        } catch (\Exception $exception) {
            return back()->withErrors(['error' => $exception->getMessage()]);
        }
    }

    /**
     * Xét trạng thái đơn hàng
     *
     */
    public function statusOrder($order_id, $status_id)
    {
        try {
            $order = OrdersModel::find($order_id);
            if ($order) {
                $order->status = $status_id;
                if ($status_id == 4 || $status_id == 5) {
                    $this->updateQuantityProductWhenCancel($order);
                }
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
                broadcast(new NotifyUser($notification->message, $notification->receiver_id, $receiver->avatar, $receiver->name, $notification->type,$notification->id))->toOthers();

                toastr()->success('Xét trạng thái đơn hàng thành công');
                return \redirect()->route('admin.order.index', [$status_id]);
            }
        } catch (\Exception $exception) {
            dd($exception);
        }
    }

    public function updateQuantityProductWhenCancel($order)
    {
        $order_item = OrdersItemModel::where('order_id', $order->id)->get();
        foreach ($order_item as $value) {
            $product = ProductsModel::find($value->product_id);
            if (isset($product)) {
                $product->quantity = $product->quantity + $value->quantity;
                $product->save();
            }
        }
        return true;
    }

    public function checkStatusOrder($item)
    {

        if ($item->status == 0) {
            $val_status = 'Chờ xác nhận';
        } elseif ($item->status == 1) {
            $val_status = 'Đã xác nhận';
        } elseif ($item->status == 2) {
            $val_status = 'Đang vận chuyển';
        } elseif ($item->status == 3) {
            $val_status = 'Đã hoàn thành';
        } elseif ($item->status == 4) {
            $val_status = 'Đã hủy';
        } else {
            $val_status = 'Trả hàng hoàn tiền';
        }

        return $val_status;
    }

}
