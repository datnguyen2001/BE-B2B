<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\BannerModel;
use Illuminate\Http\Request;
use App\Models\ProductFavoritesModel;
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

    public function categoryProduct()
    {
        try {
            $data = DB::table('category as c')
                ->leftJoin('products as p', 'c.id', '=', 'p.category_id')
                ->where('c.display', 1)
                ->select('c.id', 'c.name','c.name_en','c.slug','c.src', DB::raw('COUNT(p.id) as product_count'))
                ->groupBy('c.id', 'c.name','c.name_en','c.slug','c.src')
                ->get();

            return response()->json(['message' => 'Lấy dữ liệu thành công','data'=>$data, 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function getProductShop(Request $request,$id)
    {
        try {
            $shop = ShopModel::find($id);
            if (!$shop){
                return response()->json(['message' => 'Shop không tồn tại', 'status' => false]);
            }
            $user_id = $request->get('user_id');
            $favoriteProducts = ProductFavoritesModel::where('user_id',$user_id)->pluck('product_id')->toArray();
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
                ->leftJoin('product_discounts as pd', function($join) {
                    $join->on('p.id', '=', 'pd.product_id')
                        ->whereDate('pd.date_start', '<=', now())
                        ->whereDate('pd.date_end', '>=', now())
                        ->where('pd.number', '>', 0);;
                })
                ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
                ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
                ->where('p.shop_id', $shop->id)
                ->where('p.display', 1)
                ->where('p.status', 1)
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
                    'p.src',
                    'p.minimum_quantity as min_quantity',
                    'pa.price as original_price',
                    DB::raw('IFNULL(pd.discount, 0) as discount'),
                    DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price),0) as final_price'),
                    DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name')
                )
                ->paginate(20);
            foreach ($data as $item) {
                $item->src = json_decode($item->src, true);
                $item->is_favorite = in_array($item->id, $favoriteProducts) ? 1 : 0;
            }

            return response()->json(['message' => 'Lấy dữ liệu thành công', 'data' => $data, 'status' => true]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

}
