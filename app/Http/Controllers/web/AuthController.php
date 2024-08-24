<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Mail\VerificationCodes;
use App\Models\FollowShopsModel;
use App\Models\User;
use App\Models\UserVerificationModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function sendCode(Request $request)
    {
        $email = User::where('email', $request->get('email'))->first();
        if ($email) {
            return response()->json(['message' => 'Email này đã được đăng ký', 'status' => false]);
        }
        $verification_code = rand(100000, 999999);
        $veriCode = UserVerificationModel::where('email',$request->get('email'))->first();
        if (isset($veriCode)){
            $veriCode->verification_code = $verification_code;
            $veriCode->save();
        }else{
            $user = new UserVerificationModel();
            $user->email = $request->get('email');
            $user->verification_code = $verification_code;
            $user->save();
        }

        Mail::to($request->get('email'))->send(new VerificationCodes($verification_code));

        return response()->json(['message' => 'Gửi mã xác nhận thành công', 'status' => true]);
    }

    public function verifyCode(Request $request)
    {
        $verification = UserVerificationModel::where('email', $request->get('email'))
            ->where('verification_code', $request->get('verification_code'))
            ->first();

        if (!$verification) {
            return response()->json(['message' => 'Mã xác thực không hợp lệ', 'status' => false]);
        }

        return response()->json(['message' => 'Mã xác thực hợp lệ', 'status' => true]);
    }

    public function register(Request $request)
    {
        $user = User::create([
            'name' => $request->get('name'),
            'phone' => $request->get('phone'),
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
        ]);

        return response()->json(['message' => 'Đăng ký thành công', 'status' => true]);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['message' => 'Unauthorized','status' => false]);
        }
        $user = JWTAuth::user();
        if ($user->display == 0){
            return response()->json(['message' => 'Tài khoản của bạn đã bị khóa', 'status' => true]);
        }
        $user->token = $token;
        $user->save();

        return response()->json(['message' => 'Đăng nhập thành công', 'data' => $user, 'status' => true]);
    }

    public function updateProfile(Request $request)
    {
        $user = JWTAuth::user();
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $user->avatar = Storage::url($file->store('user', 'public'));
        }
        if ($request->has('name')) {
            $user->name = $request->get('name');
        }
        if ($request->has('phone')) {
            $user->phone = $request->get('phone');
        }
        if ($request->has('email')) {
            $email = User::where('email',$request->get('email'))->first();
            if ($email){
                return response()->json(['message' => 'Email đã tồn tại', 'status' => false]);
            }
            $user->email = $request->get('email');
        }
        if ($request->has('province_id')) {
            $user->province_id = $request->get('province_id');
        }
        if ($request->has('district_id')) {
            $user->district_id = $request->get('district_id');
        }
        if ($request->has('ward_id')) {
            $user->ward_id = $request->get('ward_id');
        }
        if ($request->has('address_detail')) {
            $user->address_detail = $request->get('address_detail');
        }

        $user->save();

        return response()->json(['message' => 'Thông tin tài khoản đã được cập nhật thành công', 'status' => true]);
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->bearerToken();
            if (!$token) {
                return response()->json(['message' => 'Token không hợp lệ', 'status' => false]);
            }
            $user = User::where('token', $token)->first();
            JWTAuth::setToken($token);
            JWTAuth::invalidate($token);
            $user->token = null;
            $user->save();

            return response()->json(['message' => 'Đăng xuất thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function followShop(Request $request)
    {
        try {
            $user = JWTAuth::user();
            $followShop = new FollowShopsModel();
            $followShop->user_id = $user->id;
            $followShop->shop_id = $request->get('shop_id');
            $followShop->save();

            return response()->json(['message' => 'Theo dõi shop thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function unfollowShop(Request $request)
    {
        try {
            $user = JWTAuth::user();
            $followShop = FollowShopsModel::where('user_id',$user->id)->where('shop_id',$request->get('shop_id'))->first();
            if (!$followShop){
                return response()->json(['message' => 'Bỏ theo dõi thất bại', 'status' => true]);
            }
            $followShop->delete();

            return response()->json(['message' => 'Bỏ theo dõi shop thành công', 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function  getFollowShop(Request $request)
    {
        try {
            $user = JWTAuth::user();
            $followShop = FollowShopsModel::where('user_id',$user->id)->pluck('shop_id');
            $shop = DB::table('shop as s')
                ->select(
                    's.id',
                    's.name',
                    's.avatar',
                    DB::raw("CONCAT(s.address_detail, ', ', w.name, ', ', d.name, ', ', p.name) as full_address")
                )
                ->leftJoin('province as p', 's.province_id', '=', 'p.province_id')
                ->leftJoin('district as d', 's.district_id', '=', 'd.district_id')
                ->leftJoin('wards as w', 's.ward_id', '=', 'w.wards_id')
                ->whereIn('s.id', $followShop)
                ->where('s.display', 1)
                ->paginate(15);

            return response()->json(['message' => 'Lấy dữ liệu thành công', 'data'=>$shop, 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

}
