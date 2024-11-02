<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAddressModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeliveryAddressController extends Controller
{
    public function getDeliveryAddress()
    {
        try {
            $user = JWTAuth::user();
            $data = DeliveryAddressModel::where('delivery_address.user_id', $user->id)
                ->select(
                    'delivery_address.id',
                    'delivery_address.name',
                    'delivery_address.phone',
                    DB::raw("CONCAT(delivery_address.address_detail, ', ', wards.name, ', ', district.name, ', ', province.name) as full_address"),
                    'delivery_address.display'
                )
                ->leftJoin('province', 'delivery_address.province_id', '=', 'province.province_id')
                ->leftJoin('district', 'delivery_address.district_id', '=', 'district.district_id')
                ->leftJoin('wards', 'delivery_address.ward_id', '=', 'wards.wards_id')
                ->get();

            return response()->json(['message' => 'Lấy dữ liệu thành công','data'=>$data, 'status' => true]);
        }catch (\Exception $e){
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
}

    public function createDeliveryAddress(Request $request)
    {
        try {
            $user = JWTAuth::user();
            $existingDefaultAddress = DeliveryAddressModel::where('user_id', $user->id)
                ->where('display', 1)
                ->exists();
            $data = new DeliveryAddressModel();
            $data->user_id = $user->id;
            $data->name = $request->get('name');
            $data->phone = $request->get('phone');
            $data->province_id = $request->get('province_id');
            $data->district_id = $request->get('district_id');
            $data->ward_id = $request->get('ward_id');
            $data->address_detail = $request->get('address_detail');
            $data->display = $existingDefaultAddress ? 0 : 1;
            $data->save();

            return response()->json(['message' => 'Tạo địa chỉ thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function detailDeliveryAddress($id){
        try{
            $data = DeliveryAddressModel::find($id);

            return response()->json(['message'=>'Lấy dữ liệu thành công','data'=>$data,'status'=>true]);
        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(),'status'=>false]);
        }
    }

    public function updateDeliveryAddress(Request $request,$id){
        try{
            $data = DeliveryAddressModel::find($id);
            if ($request->has('name')) {
                $data->name = $request->get('name');
            }
            if ($request->has('phone')) {
                $data->phone = $request->get('phone');
            }
            if ($request->has('province_id')) {
                $data->province_id = $request->get('province_id');
            }
            if ($request->has('district_id')) {
                $data->district_id = $request->get('district_id');
            }
            if ($request->has('ward_id')) {
                $data->ward_id = $request->get('ward_id');
            }
            if ($request->has('address_detail')) {
                $data->address_detail = $request->get('address_detail');
            }
            $data->save();

            return response()->json(['message'=>'Cập nhật địa chỉ thành công','data'=>$data,'status'=>true]);
        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(),'status'=>false]);
        }
    }

    public function deleteDeliveryAddress($id){
        try{
            $data = DeliveryAddressModel::find($id);
            if (!$data){
                return response()->json(['message'=>'Địa chỉ không tồn tại','status'=>true]);
            }
            $data->delete();

            return response()->json(['message'=>'Xóa địa chỉ thành công','status'=>true]);
        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(),'status'=>false]);
        }
    }

    public function selectDefaultAddress($id){
        try{
            $user = JWTAuth::user();
            DeliveryAddressModel::where('user_id', $user->id)
                ->update(['display' => 0]);
            $data = DeliveryAddressModel::find($id);
            $data->display = 1;
            $data->save();

            return response()->json(['message'=>'Chọn địa chỉ mặc định thành công','status'=>true]);
        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(),'status'=>false]);
        }
    }

}
