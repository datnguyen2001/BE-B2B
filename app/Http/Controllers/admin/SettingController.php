<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\SettingModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index()
    {
        $titlePage = 'Cài đặt chung';
        $page_menu = 'setting';
        $page_sub = null;
        $data = SettingModel::first();

        return view('admin.setting.index',compact('titlePage','page_menu','page_sub','data'));
    }

    public function save(Request $request){
        $setting = SettingModel::first();
        if ($setting){
            if ($request->hasFile('file')){
                $file = $request->file('file');
                $imagePath = Storage::url($file->store('banner', 'public'));
                if (isset($setting->logo) && Storage::exists(str_replace('/storage', 'public', $setting->logo))) {
                    Storage::delete(str_replace('/storage', 'public', $setting->logo));
                }
                $setting->logo = $imagePath;
            }

            $setting->hotline = $request->get('hotline');
            $setting->customer_support_email = $request->get('customer_support_email');
            $setting->technical_support_email = $request->get('technical_support_email');
            $setting->email = $request->get('email');
            $setting->facebook = $request->get('facebook');
            $setting->twitter = $request->get('twitter');
            $setting->zalo = $request->get('zalo');
            $setting->save();
        }else{
            $imagePath = null;
            if ($request->hasFile('file')){
                $file = $request->file('file');
                $imagePath = Storage::url($file->store('banner', 'public'));
            }
            $setting = new SettingModel([
                'hotline'=>$request->get('hotline'),
                'customer_support_email'=>$request->get('customer_support_email'),
                'technical_support_email'=>$request->get('technical_support_email'),
                'facebook'=>$request->get('facebook'),
                'email'=>$request->get('email'),
                'twitter'=>$request->get('twitter'),
                'logo'=>$imagePath,
                'zalo'=>$request->get('zalo'),
            ]);
            $setting->save();
        }

        return redirect()->back()->with(['success'=>"Lưu thông tin thành công"]);
    }
}
