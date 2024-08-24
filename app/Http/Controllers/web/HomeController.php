<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\BannerModel;
use App\Models\CategoryModel;
use App\Models\TrademarkModel;

class HomeController extends Controller
{
    public function home()
    {
        return view('web.home.index');
    }

    public function banner()
    {
        try {
            $data = BannerModel::where('display',1)->orderBy('ordinal_number','asc')->get();

            return response()->json(['message' => 'Lấy dữ liệu thành công','data'=>$data, 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function trademark()
    {
        try {
            $data = TrademarkModel::where('display',1)->orderBy('created_at','desc')->get();

            return response()->json(['message' => 'Lấy dữ liệu thành công','data'=>$data, 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function category()
    {
        try {
            $data = CategoryModel::where('display',1)->take(14)->get();

            return response()->json(['message' => 'Lấy dữ liệu thành công','data'=>$data, 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

}
