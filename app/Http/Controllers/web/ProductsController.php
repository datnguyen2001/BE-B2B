<?php

namespace App\Http\Controllers\web;

use App\Events\NotifyUser;
use App\Http\Controllers\Controller;
use App\Models\AskToBuyModel;
use App\Models\NotificationModel;
use App\Models\ProductFavoritesModel;
use App\Models\ProductReportModel;
use App\Models\ProductsModel;
use App\Models\ShopModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProductsController extends Controller
{
    public function dealHotToday(Request $request)
    {
        try {
            $user_id = $request->get('user_id');
            $favoriteProducts = ProductFavoritesModel::where('user_id',$user_id)->pluck('product_id')->toArray();
            $query = DB::table('products as p')
                ->join(DB::raw("
                (SELECT pa.product_id, pa.quantity, pa.price
                FROM products_attribute pa
                WHERE (pa.product_id, pa.quantity) IN (
                    SELECT pa2.product_id, MIN(pa2.quantity)
                    FROM products_attribute pa2
                    GROUP BY pa2.product_id
                )) pa
            "), 'p.id', '=', 'pa.product_id')
                ->leftJoin('product_discounts as pd', function ($join) {
                    $join->on('p.id', '=', 'pd.product_id')
                        ->whereDate('pd.date_start', '<=', now())
                        ->whereDate('pd.date_end', '>=', now())
                        ->where('pd.number', '>', 0)
                        ->where('pd.display', 1);
                })
                ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
                ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
                ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'p.id', '=', 'atb.product_id')
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
                    DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price), 0) as price'),
                    'pa.price as price_original',
                    DB::raw('IFNULL(pd.discount, 0) as discount'),
                    's.name as shop_name',
                    DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name'),
                    DB::raw('IFNULL(atb.ask_count, 0) as ask_count')
                )
                ->where('p.display', '=', 1)
                ->where('p.status', '=', 1)
                ->where('s.display', '=', 1)
                ->orderByDesc(DB::raw('IF(pd.discount IS NOT NULL, pd.discount, 0)'));

            $data = $query->paginate(20);

            foreach ($data as $item) {
                $item->src = json_decode($item->src, true);
                $item->is_favorite = in_array($item->id, $favoriteProducts) ? 1 : 0;
            }

            return response()->json(['message' => 'Lấy sản phẩm deal hot hôm nay thành công', 'data' => $data, 'status' => true]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function filterDealHotToday(Request $request)
    {
        try {
            $user_id = $request->get('user_id');
            $favoriteProducts = ProductFavoritesModel::where('user_id',$user_id)->pluck('product_id')->toArray();
            $categoryFilter = $request->input('category', []);
            $regionFilter = $request->input('region', []);
            if (is_string($categoryFilter)) {
                $categoryFilter = json_decode($categoryFilter, true);
            }
            if (is_string($regionFilter)) {
                $regionFilter = json_decode($regionFilter, true);
            }
            $minPrice = $request->input('min_price', null);
            $maxPrice = $request->input('max_price', null);

            $query = DB::table('products as p')
                ->join(DB::raw("
                (SELECT pa.product_id, pa.quantity, pa.price
                FROM products_attribute pa
                WHERE (pa.product_id, pa.quantity) IN (
                    SELECT pa2.product_id, MIN(pa2.quantity)
                    FROM products_attribute pa2
                    GROUP BY pa2.product_id
                )) pa
            "), 'p.id', '=', 'pa.product_id')
                ->leftJoin('product_discounts as pd', function ($join) {
                    $join->on('p.id', '=', 'pd.product_id')
                        ->whereDate('pd.date_start', '<=', now())
                        ->whereDate('pd.date_end', '>=', now())
                        ->where('pd.number', '>', 0)
                        ->where('pd.display', 1);
                })
                ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
                ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
                ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'p.id', '=', 'atb.product_id')
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
                    DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price), 0) as price'),
                    'pa.price as price_original',
                    DB::raw('IFNULL(pd.discount, 0) as discount'),
                    's.name as shop_name',
                    DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name'),
                    DB::raw('IFNULL(atb.ask_count, 0) as ask_count')
                )
                ->where('p.display', '=', 1)
                ->where('p.status', '=', 1)
                ->where('s.display', '=', 1)
                ->orderByDesc(DB::raw('IF(pd.discount IS NOT NULL, pd.discount, 0)'));
            if ($categoryFilter) {
                $query->whereIn('p.category_id', $categoryFilter);
            }

            if ($regionFilter) {
                $query->whereIn('s.scope', $regionFilter);
            }

            if ($minPrice) {
                $query->where(DB::raw('IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price)'), '>=', $minPrice);
            }

            if ($maxPrice) {
                $query->where(DB::raw('IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price)'), '<=', $maxPrice);
            }

            $data = $query->paginate(20);

            foreach ($data as $item) {
                $item->src = json_decode($item->src, true);
                $item->is_favorite = in_array($item->id, $favoriteProducts) ? 1 : 0;
            }

            return response()->json(['message' => 'Lấy sản phẩm deal hot hôm nay thành công', 'data' => $data, 'status' => true]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function filterProduct(Request $request)
    {
        try {
            $user_id = $request->get('user_id');
            $favoriteProducts = ProductFavoritesModel::where('user_id',$user_id)->pluck('product_id')->toArray();
            $categoryFilter = $request->input('category', []);
            $regionFilter = $request->input('region', []);
            if (is_string($categoryFilter)) {
                $categoryFilter = json_decode($categoryFilter, true);
            }
            if (is_string($regionFilter)) {
                $regionFilter = json_decode($regionFilter, true);
            }
            $minPrice = $request->input('min_price', null);
            $maxPrice = $request->input('max_price', null);

            $query = DB::table('products as p')
                ->join(DB::raw("
            (SELECT pa.product_id, pa.quantity, pa.price
            FROM products_attribute pa
            WHERE (pa.product_id, pa.quantity) IN (
                SELECT pa2.product_id, MIN(pa2.quantity)
                FROM products_attribute pa2
                GROUP BY pa2.product_id
            )) pa
        "), 'p.id', '=', 'pa.product_id')
                ->leftJoin('product_discounts as pd', function ($join) {
                    $join->on('p.id', '=', 'pd.product_id')
                        ->whereDate('pd.date_start', '<=', now())
                        ->whereDate('pd.date_end', '>=', now())
                        ->where('pd.number', '>', 0)
                        ->where('pd.display', 1);
                })
                ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
                ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
                ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'p.id', '=', 'atb.product_id')
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
                    DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price), 0) as price'),
                    'pa.price as price_original',
                    DB::raw('IFNULL(pd.discount, 0) as discount'),
                    's.name as shop_name',
                    DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name'),
                    DB::raw('IFNULL(atb.ask_count, 0) as ask_count'),
                    'p.created_at'
                )
                ->where('p.display', '=', 1)
                ->where('p.status', '=', 1)
                ->where('s.display', '=', 1)
                ->orderBy('p.created_at', 'desc');

            if ($categoryFilter) {
                $query->whereIn('p.category_id', $categoryFilter);
            }

            if ($regionFilter) {
                $query->whereIn('s.scope', $regionFilter);
            }

            if ($minPrice) {
                $query->where(DB::raw('IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price)'), '>=', $minPrice);
            }

            if ($maxPrice) {
                $query->where(DB::raw('IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price)'), '<=', $maxPrice);
            }

            $data = $query->paginate(20);

            foreach ($data as $item) {
                $item->src = json_decode($item->src, true);
                $item->is_favorite = in_array($item->id, $favoriteProducts) ? 1 : 0;
            }

            return response()->json(['message' => 'Lấy sản phẩm mới nhất thành công', 'data' => $data, 'status' => true]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function productForYou(Request $request)
    {
        try {
            $user_id = $request->get('user_id');
            $favoriteProducts = ProductFavoritesModel::where('user_id',$user_id)->pluck('product_id')->toArray();
            $query = DB::table('products as p')
                ->join(DB::raw("
                (SELECT pa.product_id, pa.quantity, pa.price
                FROM products_attribute pa
                WHERE (pa.product_id, pa.quantity) IN (
                    SELECT pa2.product_id, MIN(pa2.quantity)
                    FROM products_attribute pa2
                    GROUP BY pa2.product_id
                )) pa
            "), 'p.id', '=', 'pa.product_id')
                ->leftJoin('product_discounts as pd', function ($join) {
                    $join->on('p.id', '=', 'pd.product_id')
                        ->whereDate('pd.date_start', '<=', now())
                        ->whereDate('pd.date_end', '>=', now())
                        ->where('pd.number', '>', 0)
                        ->where('pd.display', 1);
                })
                ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
                ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
                ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'p.id', '=', 'atb.product_id')
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
                    DB::raw('IFNULL(pd.discount, 0) as discount'),
                    DB::raw('ROUND(pa.price - (pa.price * IFNULL(pd.discount, 0) / 100), 0) as price'),
                    'pa.price as price_original',
                    's.name as shop_name',
                    DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name'),
                    DB::raw('IFNULL(atb.ask_count, 0) as ask_count')
                )->where('p.display', '=', 1)
                ->where('s.display', '=', 1)
                ->where('p.status', '=', 1);
            $query->orderByRaw('RAND()');

            $data = $query->paginate(20);
            foreach ($data as $item) {
                $item->src = json_decode($item->src, true);
                $item->is_favorite = in_array($item->id, $favoriteProducts) ? 1 : 0;
            }

            return response()->json(['message' => 'Lấy sản phẩm dành cho bạn thành công', 'data' => $data, 'status' => true]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function productNew(Request $request)
    {
        try {
            $user_id = $request->get('user_id');
            $favoriteProducts = ProductFavoritesModel::where('user_id',$user_id)->pluck('product_id')->toArray();
            $query = DB::table('products as p')
                ->join(DB::raw("
                (SELECT pa.product_id, pa.quantity, pa.price
                FROM products_attribute pa
                WHERE (pa.product_id, pa.quantity) IN (
                    SELECT pa2.product_id, MIN(pa2.quantity)
                    FROM products_attribute pa2
                    GROUP BY pa2.product_id
                )) pa
            "), 'p.id', '=', 'pa.product_id')
                ->leftJoin('product_discounts as pd', function ($join) {
                    $join->on('p.id', '=', 'pd.product_id')
                        ->whereDate('pd.date_start', '<=', now())
                        ->whereDate('pd.date_end', '>=', now())
                        ->where('pd.number', '>', 0)
                        ->where('pd.display', 1);
                })
                ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
                ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
                ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'p.id', '=', 'atb.product_id')
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
                    DB::raw('IFNULL(pd.discount, 0) as discount'),
                    DB::raw('ROUND(pa.price - (pa.price * IFNULL(pd.discount, 0) / 100), 0) as price'),
                    'pa.price as price_original',
                    's.name as shop_name',
                    DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name'),
                    DB::raw('IFNULL(atb.ask_count, 0) as ask_count')
                )->where('p.display', '=', 1)
                ->where('s.display', '=', 1)
                ->where('p.status', '=', 1);
            $query->orderBy('p.created_at','desc');

            $data = $query->paginate(20);
            foreach ($data as $item) {
                $item->src = json_decode($item->src, true);
                $item->is_favorite = in_array($item->id, $favoriteProducts) ? 1 : 0;
            }

            return response()->json(['message' => 'Lấy sản phẩm dành cho bạn thành công', 'data' => $data, 'status' => true]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function searchProduct(Request $request)
    {
        try {
            $user_id = $request->get('user_id');
            $favoriteProducts = ProductFavoritesModel::where('user_id',$user_id)->pluck('product_id')->toArray();
            $searchTerm = $request->input('search', '');
            $query = DB::table('products as p')
                ->join(DB::raw("
                (SELECT pa.product_id, pa.quantity, pa.price
                FROM products_attribute pa
                WHERE (pa.product_id, pa.quantity) IN (
                    SELECT pa2.product_id, MIN(pa2.quantity)
                    FROM products_attribute pa2
                    GROUP BY pa2.product_id
                )) pa
            "), 'p.id', '=', 'pa.product_id')
                ->leftJoin('product_discounts as pd', function ($join) {
                    $join->on('p.id', '=', 'pd.product_id')
                        ->whereDate('pd.date_start', '<=', now())
                        ->whereDate('pd.date_end', '>=', now())
                        ->where('pd.number', '>', 0)
                        ->where('pd.display', 1);
                })
                ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
                ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
                ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'p.id', '=', 'atb.product_id')
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
                    DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price), 0) as price'),
                    'pa.price as price_original',
                    DB::raw('IFNULL(pd.discount, 0) as discount'),
                    's.name as shop_name',
                    DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name'),
                    DB::raw('IFNULL(atb.ask_count, 0) as ask_count')
                )->where('p.display', '=', 1)
                ->where('s.display', '=', 1)
                ->where('p.status', '=', 1);

            if ($searchTerm) {
                $query->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('p.name', 'LIKE', "%$searchTerm%")
                        ->orWhere('p.sku', 'LIKE', "%$searchTerm%");
                });
            }
            $data = $query->paginate(20);
            foreach ($data as $item) {
                $item->src = json_decode($item->src, true);
                $item->is_favorite = in_array($item->id, $favoriteProducts) ? 1 : 0;
            }

            return response()->json(['message' => 'Tìm kiếm sản phẩm thành công', 'data' => $data, 'status' => true]);

        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function detailProduct(Request $request,$slug)
    {
        try {
            $user_id = $request->get('user_id');
            $favoriteProducts = ProductFavoritesModel::where('user_id',$user_id)->pluck('product_id')->toArray();
            $product = DB::table('products')
                ->join('shop', 'products.shop_id', '=', 'shop.id')
                ->leftJoin('product_discounts', 'products.id', '=', 'product_discounts.product_id')
                ->leftJoin('province', 'shop.province_id', '=', 'province.province_id')
                ->leftJoin('district', 'shop.district_id', '=', 'district.district_id')
                ->leftJoin('wards', 'shop.ward_id', '=', 'wards.wards_id')
                ->leftJoin('category', 'products.category_id', '=', 'category.id')
                ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'products.id', '=', 'atb.product_id')
                ->where('products.slug', $slug)
                ->where('products.display', 1)
                ->where('products.status', 1)
                ->select(
                    'products.id',
                    'products.name',
                    'products.name_en',
                    'products.slug',
                    'products.sku',
                    'products.describe',
                    'products.category_id',
                    'category.name as category_name',
                    'products.unit',
                    'products.en_unit',
                    'products.contact_info',
                    'products.minimum_quantity',
                    'products.src',
                    'products.quantity',
                    'shop.id as shop_id',
                    'shop.name as shop_name',
                    'shop.phone as shop_phone',
                    'shop.created_at as shop_date',
                    'shop.avatar as shop_avatar',
                    'shop.content as shop_content',
                    DB::raw("CONCAT_WS(', ', shop.address_detail, wards.name, district.name, province.name) as shop_full_address"),
                    DB::raw('IFNULL(product_discounts.date_start, NULL) as discount_date_start'),
                    DB::raw('IFNULL(product_discounts.date_end, NULL) as discount_date_end'),
                    DB::raw('IFNULL(product_discounts.number, 0) as number_discount'),
                    DB::raw('IFNULL(product_discounts.discount, 0) as discount'),
                    DB::raw('(SELECT COUNT(*) FROM products WHERE shop_id = shop.id) as total_products_shop'),
                    DB::raw('(SELECT COUNT(*) FROM follow_shops WHERE shop_id = shop.id) as total_followers_shop'),
                    DB::raw('IFNULL(atb.ask_count, 0) as ask_count'),
                    DB::raw('(SELECT COUNT(*) FROM conversations WHERE (user1_id = shop.user_id OR user2_id = shop.user_id)) as contact_count')
                )
                ->first();
            if (!$product) {
                return response()->json(['message' => 'Không tìm thấy sản phẩm', 'status' => false]);
            }
            $attributes = DB::table('products_attribute')
                ->where('product_id', $product->id)
                ->select('quantity', 'price')
                ->get();

            $product->attributes = $attributes;
            $product->src = json_decode($product->src, true);

            //Sản phẩm đã xem
            $viewItemsJson = Cookie::has('viewItemProduct') ? Cookie::get('viewItemProduct') : '[]';
            $viewItemProduct = json_decode($viewItemsJson, true);
            if (!in_array($product->id, $viewItemProduct)) {
//                $viewItemProduct[] = $product->id;
                array_unshift($viewItemProduct, $product->id);
                $cartItemsJson = json_encode($viewItemProduct);
                Cookie::queue('viewItemProduct', $cartItemsJson, 60 * 24 * 150, '/', env('SESSION_DOMAIN'), true, true, false, 'None');
            }

            $products_viewed = DB::table('products as p')
                    ->join(DB::raw("
            (SELECT pa.product_id, pa.quantity, pa.price
            FROM products_attribute pa
            WHERE (pa.product_id, pa.quantity) IN (
                SELECT pa2.product_id, MIN(pa2.quantity)
                FROM products_attribute pa2
                GROUP BY pa2.product_id
            )) pa
        "), 'p.id', '=', 'pa.product_id')
                    ->leftJoin('product_discounts as pd', function ($join) {
                        $join->on('p.id', '=', 'pd.product_id')
                            ->whereDate('pd.date_start', '<=', now())
                            ->whereDate('pd.date_end', '>=', now())
                            ->where('pd.number', '>', 0)
                            ->where('pd.display', 1);
                    })
                    ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
                    ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
                ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'p.id', '=', 'atb.product_id')
                    ->select(
                        'p.id',
                        'p.name',
                        'p.name_en',
                        'p.slug',
                        'p.sku',
                        'p.unit',
                        'p.en_unit',
                        'p.quantity',
                        'p.src',
                        'p.minimum_quantity as min_quantity',
                        DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price), 0) as price'),
                        'pa.price as price_original',
                        DB::raw('IFNULL(pd.discount, 0) as discount'),
                        's.name as shop_name',
                        DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name'),
                        DB::raw('IFNULL(atb.ask_count, 0) as ask_count')
                    )
                    ->whereIn('p.id', $viewItemProduct)
                    ->where('p.display', '=', 1)
                    ->where('s.display', '=', 1)
                    ->where('p.status', '=', 1)
                    ->limit(12)
                    ->get();
            foreach ($products_viewed as $val){
                $val->src = json_decode($val->src, true);
                $val->is_favorite = in_array($val->id, $favoriteProducts) ? 1 : 0;
            }

            //Sản phẩm đề xuất từ của hàng
            $products_recommended = DB::table('products as p')
                ->join(DB::raw("
            (SELECT pa.product_id, pa.quantity, pa.price
            FROM products_attribute pa
            WHERE (pa.product_id, pa.quantity) IN (
                SELECT pa2.product_id, MIN(pa2.quantity)
                FROM products_attribute pa2
                GROUP BY pa2.product_id
            )) pa
        "), 'p.id', '=', 'pa.product_id')
                ->leftJoin('product_discounts as pd', function ($join) {
                    $join->on('p.id', '=', 'pd.product_id')
                        ->whereDate('pd.date_start', '<=', now())
                        ->whereDate('pd.date_end', '>=', now())
                        ->where('pd.number', '>', 0)
                        ->where('pd.display', 1);
                })
                ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
                ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
                ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'p.id', '=', 'atb.product_id')
                ->select(
                    'p.id',
                    'p.name',
                    'p.name_en',
                    'p.slug',
                    'p.sku',
                    'p.unit',
                    'p.en_unit',
                    'p.quantity',
                    'p.src',
                    'p.minimum_quantity as min_quantity',
                    DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price), 0) as price'),
                    'pa.price as price_original',
                    DB::raw('IFNULL(pd.discount, 0) as discount'),
                    's.name as shop_name',
                    DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name'),
                    DB::raw('IFNULL(atb.ask_count, 0) as ask_count')
                )
                ->where('s.id', $product->shop_id)
                ->where('p.display', '=', 1)
                ->where('s.display', '=', 1)
                ->where('p.status', '=', 1)
                ->inRandomOrder()
                ->limit(12)
                ->get();
            foreach ($products_recommended as $recommended){
                $recommended->src = json_decode($recommended->src, true);
                $recommended->is_favorite = in_array($recommended->id, $favoriteProducts) ? 1 : 0;
            }

            //Sản phẩm tương tự
            $products_similar = DB::table('products as p')
                ->join(DB::raw("
            (SELECT pa.product_id, pa.quantity, pa.price
            FROM products_attribute pa
            WHERE (pa.product_id, pa.quantity) IN (
                SELECT pa2.product_id, MIN(pa2.quantity)
                FROM products_attribute pa2
                GROUP BY pa2.product_id
            )) pa
        "), 'p.id', '=', 'pa.product_id')
                ->leftJoin('product_discounts as pd', function ($join) {
                    $join->on('p.id', '=', 'pd.product_id')
                        ->whereDate('pd.date_start', '<=', now())
                        ->whereDate('pd.date_end', '>=', now())
                        ->where('pd.number', '>', 0)
                        ->where('pd.display', 1);
                })
                ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
                ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
                ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'p.id', '=', 'atb.product_id')
                ->select(
                    'p.id',
                    'p.name',
                    'p.name_en',
                    'p.slug',
                    'p.sku',
                    'p.unit',
                    'p.en_unit',
                    'p.quantity',
                    'p.src',
                    'p.minimum_quantity as min_quantity',
                    DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price), 0) as price'),
                    'pa.price as price_original',
                    DB::raw('IFNULL(pd.discount, 0) as discount'),
                    's.name as shop_name',
                    DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name'),
                    DB::raw('IFNULL(atb.ask_count, 0) as ask_count')
                )
                ->where('p.category_id', $product->category_id)
                ->where('p.display', '=', 1)
                ->where('s.display', '=', 1)
                ->where('p.status', '=', 1)
                ->inRandomOrder()
                ->limit(12)
                ->get();
            foreach ($products_similar as $similar){
                $similar->src = json_decode($similar->src, true);
                $similar->is_favorite = in_array($similar->id, $favoriteProducts) ? 1 : 0;
            }

            $response = [
                'product' => $product,
                'products_viewed' => $products_viewed,
                'products_recommended' => $products_recommended,
                'products_similar' => $products_similar
            ];

            return response()->json(['message' => 'Lấy dữ liệu thành công', 'data' => $response, 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function getViewedProducts(Request $request)
    {
        try {
            $user_id = $request->get('user_id');
            $favoriteProducts = ProductFavoritesModel::where('user_id',$user_id)->pluck('product_id')->toArray();
            $viewItemsJson = Cookie::has('viewItemProduct') ? Cookie::get('viewItemProduct') : '[]';
            $viewItemProduct = json_decode($viewItemsJson, true);

            $products_viewed = DB::table('products as p')
                ->join(DB::raw("
            (SELECT pa.product_id, pa.quantity, pa.price
            FROM products_attribute pa
            WHERE (pa.product_id, pa.quantity) IN (
                SELECT pa2.product_id, MIN(pa2.quantity)
                FROM products_attribute pa2
                GROUP BY pa2.product_id
            )) pa
        "), 'p.id', '=', 'pa.product_id')
                ->leftJoin('product_discounts as pd', function ($join) {
                    $join->on('p.id', '=', 'pd.product_id')
                        ->whereDate('pd.date_start', '<=', now())
                        ->whereDate('pd.date_end', '>=', now())
                        ->where('pd.number', '>', 0)
                        ->where('pd.display', 1);
                })
                ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
                ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
                ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'p.id', '=', 'atb.product_id')
                ->select(
                    'p.id',
                    'p.name',
                    'p.name_en',
                    'p.slug',
                    'p.sku',
                    'p.unit',
                    'p.en_unit',
                    'p.quantity',
                    'p.src',
                    'p.minimum_quantity as min_quantity',
                    DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price), 0) as price'),
                    'pa.price as price_original',
                    DB::raw('IFNULL(pd.discount, 0) as discount'),
                    's.name as shop_name',
                    DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name'),
                    DB::raw('IFNULL(atb.ask_count, 0) as ask_count')
                )
                ->whereIn('p.id', $viewItemProduct)
                ->where('p.display', '=', 1)
                ->where('s.display', '=', 1)
                ->where('p.status', '=', 1)
                ->paginate(16);
            foreach ($products_viewed as $val){
                $val->src = json_decode($val->src, true);
                $val->is_favorite = in_array($val->id, $favoriteProducts) ? 1 : 0;
            }

            return response()->json(['message' => 'Lấy dữ liệu thành công', 'data' => $products_viewed , 'status' => true]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

    public function favoriteProduct(Request $request)
    {
        $productId = $request->get('product_id');
        $user = JWTAuth::user();

        $favorite = ProductFavoritesModel::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($favorite) {
            $favorite->delete();
            return response()->json(['message' => 'Bỏ yêu thích sản phẩm thành công', 'status' => true]);
        } else {
            ProductFavoritesModel::create([
                'user_id' => $user->id,
                'product_id' => $productId
            ]);
            return response()->json(['message' => 'Thêm sản phẩm yêu thích thành công', 'status' => true]);
        }
    }


    public function getFavoriteProducts()
    {
        $user = JWTAuth::user();
        $favoriteProducts =  ProductFavoritesModel::where('user_id', $user->id)->pluck('product_id')->toArray();

        $products_viewed = DB::table('products as p')
            ->join(DB::raw("
            (SELECT pa.product_id, pa.quantity, pa.price
            FROM products_attribute pa
            WHERE (pa.product_id, pa.quantity) IN (
                SELECT pa2.product_id, MIN(pa2.quantity)
                FROM products_attribute pa2
                GROUP BY pa2.product_id
            )) pa
        "), 'p.id', '=', 'pa.product_id')
            ->leftJoin('product_discounts as pd', function ($join) {
                $join->on('p.id', '=', 'pd.product_id')
                    ->whereDate('pd.date_start', '<=', now())
                    ->whereDate('pd.date_end', '>=', now())
                    ->where('pd.number', '>', 0)
                    ->where('pd.display', 1);
            })
            ->leftJoin('shop as s', 'p.shop_id', '=', 's.id')
            ->leftJoin('province as pr', 's.scope', '=', 'pr.province_id')
            ->leftJoin(DB::raw("(SELECT product_id, COUNT(*) as ask_count FROM ask_to_buy GROUP BY product_id) as atb"), 'p.id', '=', 'atb.product_id')
            ->select(
                'p.id',
                'p.name',
                'p.name_en',
                'p.slug',
                'p.sku',
                'p.unit',
                'p.en_unit',
                'p.quantity',
                'p.src',
                'p.minimum_quantity as min_quantity',
                DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price), 0) as price'),
                'pa.price as price_original',
                DB::raw('IFNULL(pd.discount, 0) as discount'),
                's.name as shop_name',
                DB::raw('IFNULL(pr.name, "Toàn quốc") as province_name'),
                DB::raw('IFNULL(atb.ask_count, 0) as ask_count')
            )
            ->whereIn('p.id', $favoriteProducts)
            ->where('p.display', '=', 1)
            ->where('s.display', '=', 1)
            ->where('p.status', '=', 1)
            ->paginate(16);
        foreach ($products_viewed as $val){
            $val->src = json_decode($val->src, true);
            $val->is_favorite = 1;
        }

        return response()->json(['message'=>'Lấy dữ liệu thành công','data' => $products_viewed, 'status' => true]);
    }

    public function saveAskBuy(Request $request)
    {
        try{
            $user = JWTAuth::user();
            $ask = new AskToBuyModel();
            $ask->user_id = $user->id;
            $ask->shop_id = $request->shop_id;
            $ask->product_id = $request->get('product_id');
            $ask->quantity = $request->get('quantity');
            $ask->content = $request->get('content');
            $ask->save();

            $shop = ShopModel::find($request->shop_id);
            $notification = new NotificationModel();
            $notification->sender_id = $user->id;
            $notification->receiver_id=$shop->user_id;
            $notification->message = 'Bạn có một yêu cầu mua hàng mới';
            $notification->is_read = 0;
            $notification->type = 'ask-buy';
            $notification->save();

            broadcast(new NotifyUser($notification->message, $notification->receiver_id,$user->avatar, $user->name,$notification->type,$notification->id))->toOthers();

            return response()->json(['message'=>'Hỏi mua hàng thành công', 'status' => true]);
        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(), 'status' => false]);
        }
    }

    public function productReport(Request $request)
    {
        try{
            $user = JWTAuth::user();
            $report = new ProductReportModel();
            $report->user_id = $user->id;
            $report->product_id = $request->get('product_id');
            $report->content = $request->get('content');
            $report ->save();

            $product = ProductsModel::find($report->product_id);
            $shop = ShopModel::find($product->shop_id);
            $notification = new NotificationModel();
            $notification->sender_id = $user->id;
            $notification->receiver_id=$shop->user_id;
            $notification->message = 'Bạn có một báo cáo về sản phẩm '.$product->name;
            $notification->is_read = 0;
            $notification->type = 'product-report';
            $notification->save();

            broadcast(new NotifyUser($notification->message, $notification->receiver_id,$user->avatar, $user->name,$notification->type,$notification->id))->toOthers();

            return response()->json(['message'=>'Báo cáo sản phẩm thành công', 'status' => true]);
        }catch (\Exception $e){
            return response()->json(['message'=>$e->getMessage(), 'status' => false]);
        }
    }

}
