<?php

namespace App\Http\Controllers\web;

use App\Events\NotifyUser;
use App\Http\Controllers\Controller;
use App\Models\NotificationModel;
use App\Models\QuotesModel;
use App\Models\RequestSupplierModel;
use Illuminate\Http\Request;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Tymon\JWTAuth\Facades\JWTAuth;

class QuotesController extends Controller
{
    public function getQuotes(){
        try{
            $user = JWTAuth::user();
            $data = QuotesModel::join('request_supplier', 'quotes.request_supplier_id', '=', 'request_supplier.id')
                ->where('request_supplier.user_id', $user->id)
                ->where('request_supplier.display', 1)
                ->where('request_supplier.status', 1)
                ->orderBy('quotes.created_at', 'desc')
                ->paginate(20, [
                    'quotes.*',
                    'request_supplier.name as request_name',
                    'request_supplier.src as request_src',
                    'request_supplier.quantity as request_quantity'
                ]);
            foreach ($data as $item) {
                $item->request_src = json_decode($item->request_src, true);
            }

            return response()->json(['message'=>'Lấy dữ liệu thành công','data'=>$data,'status'=>true]);

        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(),'status'=>false]);
        }
    }

    public function createQuotes(Request $request)
    {
        try {
            $translator = new GoogleTranslate();
            $translator->setSource('vi');
            $translator->setTarget('en');
            $user = JWTAuth::user();
            $translatedContent = $translator->translate($request->get('content'));
            $data = new QuotesModel();
            $data->request_supplier_id = $request->get('request_supplier_id');
            $data->user_id = $user->id;
            $data->name = $request->get('name');
            $data->content = $request->get('content');
            $data->content_en = $translatedContent;
            $data->phone = $request->get('phone');
            $data->price = $request->get('price');
            $data->address = $request->get('address');
            $data->save();

            $product = RequestSupplierModel::find($data->request_supplier_id);
            $notification = new NotificationModel();
            $notification->sender_id = $user->id;
            $notification->receiver_id=$product->user_id;
            $notification->message = 'Bạn có một đơn báo giá từ ' . $user->name;
            $notification->is_read = 0;
            $notification->type = 'create-quotes';
            $notification->save();

            broadcast(new NotifyUser($notification->message, $notification->receiver_id,$user->avatar, $user->name,$notification->type))->toOthers();

            return response()->json(['message' => 'Tạo báo giá thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => true]);
        }
    }

    public function detailQuotes($id){
        try{
            $data = QuotesModel::join('request_supplier', 'quotes.request_supplier_id', '=', 'request_supplier.id')
                ->where('quotes.id', $id)
                ->select('quotes.*', 'request_supplier.name as request_name','request_supplier.quantity as request_quantity','request_supplier.src as request_src')
                ->first();

            return response()->json(['message'=>'Lấy dữ liệu thành công','data'=>$data,'status'=>true]);
        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(),'status'=>false]);
        }
    }

    public function getQuotesUser(){
        try{
            $user = JWTAuth::user();
            $data = QuotesModel::join('request_supplier', 'quotes.request_supplier_id', '=', 'request_supplier.id')
                ->where('quotes.user_id', $user->id)
                ->where('request_supplier.display', 1)
                ->where('request_supplier.status', 1)
                ->orderBy('quotes.created_at', 'desc')
                ->paginate(20, [
                    'quotes.*',
                    'request_supplier.name as request_name',
                    'request_supplier.src as request_src',
                    'request_supplier.quantity as request_quantity'
                ]);
            foreach ($data as $item) {
                $item->request_src = json_decode($item->request_src, true);
            }

            return response()->json(['message'=>'Lấy dữ liệu thành công','data'=>$data,'status'=>true]);

        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(),'status'=>false]);
        }
    }

}
