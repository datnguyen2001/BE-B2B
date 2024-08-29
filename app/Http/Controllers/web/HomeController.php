<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\BannerModel;
use App\Models\CategoryModel;
use App\Models\ShopModel;
use App\Models\TrademarkModel;
use Illuminate\Support\Facades\DB;

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
            $data = DB::table('category as c')
                ->leftJoin('products as p', 'c.id', '=', 'p.category_id')
                ->where('c.display', 1)
                ->select('c.id', 'c.name','c.name_en','c.slug','c.src', DB::raw('COUNT(p.id) as product_count'))
                ->groupBy('c.id', 'c.name','c.name_en','c.slug','c.src')
                ->paginate(14);

            return response()->json(['message' => 'Lấy dữ liệu thành công','data'=>$data, 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function getProductShop($id)
    {
        try {
            $shop = ShopModel::find($id);
            if (!$shop){
                return response()->json(['message' => 'Shop không tồn tại', 'status' => false]);
            }
            $data = DB::table('products as p')
                ->join(DB::raw("
                (SELECT product_id, quantity, price
                FROM products_attribute
                WHERE (product_id, quantity) IN (
                    SELECT product_id, MIN(quantity)
                    FROM products_attribute
                    GROUP BY product_id
                )) pa
            "), 'p.id', '=', 'pa.product_id')
                ->where('p.shop_id', $shop->id)
                ->select(
                    'p.id',
                    'p.name',
                    'p.name_en',
                    'p.slug',
                    'p.sku',
                    'p.category_id',
                    'p.unit',
                    'p.en_unit',
                    'p.quantity',
                    'p.display',
                    'p.status',
                    'p.src',
                    'pa.quantity as min_quantity',
                    'pa.price as price'
                )
                ->paginate(20);
            foreach ($data as $item) {
                $item->src = json_decode($item->src, true);
            }

            return response()->json(['message' => 'Lấy dữ liệu thành công', 'data' => $data, 'status' => true]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

}
