<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\RequestSupplierModel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Stichoza\GoogleTranslate\GoogleTranslate;
use Tymon\JWTAuth\Facades\JWTAuth;

class RequestSupplierController extends Controller
{
    public function getRequestSupplier(){
        try{
            $data = RequestSupplierModel::where('request_supplier.display', 1)
                ->where('request_supplier.status', 1)
                ->leftJoin('province', 'request_supplier.scope', '=', 'province.province_id')
                ->select(
                    'request_supplier.*',
                    DB::raw("IF(request_supplier.scope = 0, 'Toàn quốc', IFNULL(province.name, 'Toàn quốc')) as scope_name")
                )
                ->orderby('request_supplier.created_at', 'desc')
                ->paginate(12);
            foreach ($data as $item) {
                $item->src = json_decode($item->src, true);
            }

            return response()->json(['message'=>'Lấy dữ liệu thành công','data'=>$data,'status'=>true]);

        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(),'status'=>false]);
        }
    }

    public function getRequestSupplierUser(){
        try{
            $user = JWTAuth::user();
            $data = RequestSupplierModel::where('user_id', $user->id)
                ->leftJoin('province', 'request_supplier.scope', '=', 'province.province_id')
                ->select(
                    'request_supplier.*',
                    DB::raw("IF(request_supplier.scope = 0, 'Toàn quốc', IFNULL(province.name, 'Toàn quốc')) as scope_name")
                )
                ->orderBy('request_supplier.created_at', 'desc')
                ->paginate(20);
            foreach ($data as $item) {
                $item->src = json_decode($item->src, true);
            }

            return response()->json(['message'=>'Lấy dữ liệu thành công','data'=>$data,'status'=>true]);

        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(),'status'=>false]);
        }
    }

    public function createRequestSupplier(Request $request)
    {
        try {
            $user = JWTAuth::user();
            $translator = new GoogleTranslate();
            $translator->setSource('vi');
            $translator->setTarget('en');
            $translatedName = $translator->translate($request->get('name'));
            $translatedContent = $translator->translate($request->get('content'));
            $data = new RequestSupplierModel();
            $data->name = $request->get('name');
            $data->name_en = $translatedName;
            $data->slug = Str::slug($request->get('name'));
            $data->content = $request->get('content');
            $data->content_en = $translatedContent;
            $data->phone = $request->get('phone');
            $data->quantity = $request->get('quantity');
            $srcArray = [];
            if ($request->hasFile('src')) {
                foreach ($request->file('src') as $file) {
                    $imagePath = Storage::url($file->store('requests', 'public'));
                    $srcArray[] = $imagePath;
                }
            }
            $data->src = json_encode($srcArray);
            $data->scope = $request->get('scope');
            $data->date_end = $request->get('date_end');
            $data->user_id = $user->id;
            $data->display = 1;
            $data->status = 0;
            $data->save();

            return response()->json(['message' => 'Tạo yêu cầu thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => true]);
        }
    }

    public function editRequestSupplierUser($id){
        try{
            $data = RequestSupplierModel::find($id);
            $data->src = json_decode($data->src, true);
            $user = User::find($data->user_id);
            $response = [
                'request' => $data,
                'user' => $user,
            ];

            return response()->json(['message'=>'Lấy dữ liệu thành công','data'=>$response,'status'=>true]);

        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(),'status'=>false]);
        }
    }

    public function updateRequestSupplier(Request $request, $id)
    {
        try {
            $translator = new GoogleTranslate();
            $translator->setSource('vi');
            $translator->setTarget('en');

            $data = RequestSupplierModel::find($id);
            if ($request->has('name')) {
                $data->name = $request->get('name');
                $data->name_en = $translator->translate($request->get('name'));
                $data->slug = Str::slug($request->get('name'));
            }
            if ($request->has('phone')) {
                $data->phone = $request->get('phone');
            }
            if ($request->has('content')) {
                $data->content = $request->get('content');
                $data->content_en = $translator->translate($request->get('content'));
            }
            if ($request->has('quantity')) {
                $data->quantity = $request->get('quantity');
            }
            if ($request->has('scope')) {
                $data->scope = $request->get('scope');
            }
            if ($request->has('date_end')) {
                $data->date_end = $request->get('date_end');
            }
            $existingSrc = json_decode($data->src, true) ?? [];
            $newSrcArray = [];
            if ($request->hasFile('src')) {
                foreach ($request->file('src') as $file) {
                    $imagePath = Storage::url($file->store('requests', 'public'));
                    $newSrcArray[] = $imagePath;
                }
                $finalSrcArray = array_merge($existingSrc, $newSrcArray);
                $data->src = json_encode($finalSrcArray);
            }
            $data->save();

            return response()->json(['message' => 'Cập nhật yêu cầu  thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function updateRequestDisplay(Request $request, $id)
    {
        try {
            $data = RequestSupplierModel::find($id);
            if (!$data) {
                return response()->json(['message' => 'Yêu cầu không tồn tại', 'status' => false]);
            }
            $data->display = $request->get('display');
            $data->save();

            return response()->json(['message' => 'Cập nhật trạng thái yêu cầu thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function deleteRequest($id)
    {
        try {
            $data = RequestSupplierModel::find($id);
            if (!$data) {
                return response()->json(['message' => 'Yêu cầu không tồn tại', 'status' => false]);
            }
            $imagesToDelete = json_decode($data->src, true) ?? [];
            foreach ($imagesToDelete as $image) {
                $filePath = str_replace('/storage', 'public', $image);
                Storage::delete($filePath);
            }
            $data->delete();

            return response()->json(['message' => 'Xóa sản phẩm thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

}
