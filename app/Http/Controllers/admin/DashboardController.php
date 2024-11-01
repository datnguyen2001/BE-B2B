<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\DistrictModel;
use App\Models\ProvinceModel;
use App\Models\ShopModel;
use App\Models\User;
use App\Models\WardsModel;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $titlePage = 'Trang chủ';
        $page_menu = 'dashboard';
        $page_sub = null;
        return view('admin.index', compact('titlePage','page_menu','page_sub'));
    }

    public function getUser(Request $request)
    {
        $titlePage = 'Danh sách người dùng';
        $page_menu = 'user';
        $page_sub = null;
        $query = User::orderBy('created_at', 'desc');
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $listData = $query->paginate(10);
        foreach ($listData as $item){
            $province = ProvinceModel::where('province_id', $item->province_id)->first();
            $district = DistrictModel::where('district_id', $item->district_id)->first();
            $ward = WardsModel::where('wards_id', $item->ward_id)->first();
            if ($province&&$district&&$ward){
                $item->full_address = $item->address_detail . ', ' . $ward->name . ', ' . $district->name . ', ' . $province->name;
            }else{
                $item->full_address = "Chưa cập nhật";
            }

        }

        return view('admin.user.index', compact('titlePage','page_menu','page_sub','listData'));
    }

    public function setDisplayUser($id, $status)
    {
        $data = User::find($id);
        $data->display = $status;
        $data->save();

        toastr()->success('Cập nhật trạng thái thành công');
        return back();
    }

    public function getShop(Request $request)
    {
        $titlePage = 'Danh sách shop';
        $page_menu = 'shop';
        $page_sub = null;
        $query = ShopModel::orderBy('created_at', 'desc');
        $search = $request->input('search');
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $listData = $query->paginate(10);
        foreach ($listData as $item){
            $province = ProvinceModel::where('province_id', $item->province_id)->first();
            $district = DistrictModel::where('district_id', $item->district_id)->first();
            $ward = WardsModel::where('wards_id', $item->ward_id)->first();
            $item->full_address = $item->address_detail . ', ' . $ward->name . ', ' . $district->name . ', ' . $province->name;
        }

        return view('admin.shop.index', compact('titlePage','page_menu','page_sub','listData'));
    }

    public function setDisplayShop($id, $status)
    {
        $data = ShopModel::find($id);
        $data->display = $status;
        $data->save();

        toastr()->success('Cập nhật trạng thái thành công');
        return back();
    }

    public function upload(Request $request)
    {
        if($request->hasFile('upload')) {
            $originName = $request->file('upload')->getClientOriginalName();
            $fileName = pathinfo($originName, PATHINFO_FILENAME);
            $extension = $request->file('upload')->getClientOriginalExtension();
            $fileName = $fileName.'_'.time().'.'.$extension;
            $request->file('upload')->move(public_path('userfiles'), $fileName);
            $CKEditorFuncNum = $request->input('CKEditorFuncNum');
            $url = asset('userfiles/'.$fileName);
            $msg = 'Image successfully uploaded';
            $response = "<script>window.parent.CKEDITOR.tools.callFunction($CKEditorFuncNum, '$url', '$msg')</script>";

            @header('Content-type: text/html; charset=utf-8');
            echo $response;
        }
    }
}
