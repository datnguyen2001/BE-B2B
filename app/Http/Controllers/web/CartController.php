<?php

namespace App\Http\Controllers\web;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAddressModel;
use App\Models\OrdersItemModel;
use App\Models\OrdersModel;
use App\Models\OrderTotalModel;
use App\Models\ProductsModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class CartController extends Controller
{
    public function getCart()
    {
        $cartItemsJson = Cookie::get('cartItems', '[]');
        $cartItems = json_decode($cartItemsJson, true);

        if (empty($cartItems)) {
            return response()->json(['message' => 'Giỏ hàng trống', 'data' => [], 'status' => false]);
        }

        $cartDetails = [];
        $groupedItems = [];
        foreach ($cartItems as $item) {
            $shopId = $item['shop_id'];
            if (!isset($groupedItems[$shopId])) {
                $groupedItems[$shopId] = [];
            }
            $groupedItems[$shopId][] = $item;
        }

        foreach ($groupedItems as $shopId => $items) {
            $shop = DB::table('shop')->where('id', $shopId)->first();

            if ($shop) {
                $shopDetails = [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'products' => []
                ];

                foreach ($items as $item) {
                    $product = DB::table('products as p')
                        ->leftJoin('product_discounts as pd', function ($join) use ($item) {
                            $join->on('p.id', '=', 'pd.product_id')
                                ->whereDate('pd.date_start', '<=', now())
                                ->whereDate('pd.date_end', '>=', now())
                                ->where('pd.number', '>=', $item['quantity']);
                        })
                        ->leftJoin('products_attribute as pa', function ($join) use ($item) {
                            $join->on('p.id', '=', 'pa.product_id')
                                ->where('pa.quantity', '<=', $item['quantity'])
                                ->where(function($query) use ($item) {
                                    $query->where('pa.quantity', '=', $item['quantity'])
                                        ->orWhere(function($subquery) use ($item) {
                                            $subquery->where('pa.quantity', '<=', $item['quantity'])
                                                ->whereRaw('pa.quantity = (SELECT MAX(quantity) FROM products_attribute WHERE quantity <= ? AND product_id = pa.product_id)', [$item['quantity']]);
                                        });
                                });
                        })
                        ->where('p.id', $item['product_id'])
                        ->where('p.shop_id', $shopId)
                        ->select(
                            'p.id',
                            'p.name',
                            'p.name_en',
                            'p.unit',
                            'p.en_unit',
                            'p.src',
                            'p.quantity as inventory_quantity',
                            'pa.price as attribute_price',
                            'pd.discount',
                            'pd.number',
                            DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price),0) as final_price')
                        )
                        ->first();

                    if ($product) {
                        $maxDiscountedQuantity = $product->number;
                        $discountedQuantity = min($item['quantity'], $maxDiscountedQuantity);
                        $fullPriceQuantity = max(0, $item['quantity'] - $discountedQuantity);

                        if ($discountedQuantity > 0) {
                            $shopDetails['products'][] = [
                                'product_id' => $product->id,
                                'name' => $product->name,
                                'name_en' => $product->name_en,
                                'unit' => $product->unit,
                                'unit_en' => $product->en_unit,
                                'quantity' => $discountedQuantity,
                                'inventory_quantity' => $product->inventory_quantity,
                                'discount' => $product->discount,
                                'price' => $product->final_price,
                                'src' => json_decode($product->src, true),
                            ];
                        }

                        if ($fullPriceQuantity > 0) {
                            $shopDetails['products'][] = [
                                'product_id' => $product->id,
                                'name' => $product->name,
                                'name_en' => $product->name_en,
                                'unit' => $product->unit,
                                'unit_en' => $product->en_unit,
                                'quantity' => $fullPriceQuantity,
                                'inventory_quantity' => $product->inventory_quantity,
                                'discount' => 0,
                                'price' => $product->attribute_price,
                                'src' => json_decode($product->src, true),
                            ];
                        }
                    }
                }

                $cartDetails[] = $shopDetails;
            }
        }

        return response()->json(['cart' => $cartDetails, 'status' => true]);
    }

    public function addToCart(Request $request)
    {
        $shopId = $request->get('shop_id');
        $productId = $request->get('product_id');
        $quantity = $request->get('quantity');

        $product = DB::table('products')
            ->join('products_attribute', 'products.id', '=', 'products_attribute.product_id')
            ->where('products.id', $productId)
            ->where('products.shop_id', $shopId)
            ->select('products.quantity')
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm', 'status' => false]);
        }

        $cartItemsJson = Cookie::has('cartItems') ? Cookie::get('cartItems') : '[]';
        $cartItems = json_decode($cartItemsJson, true);
        $found = false;

        $currentCartQuantity = 0;
        foreach ($cartItems as $item) {
            if ($item['product_id'] == $productId && $item['shop_id'] == $shopId) {
                $currentCartQuantity = $item['quantity'];
                break;
            }
        }

        if ($currentCartQuantity + $quantity > $product->quantity) {
            return response()->json(['message' => 'Số lượng sản phẩm vượt quá số lượng tồn kho', 'status' => false]);
        }

        foreach ($cartItems as &$item) {
            if ($item['product_id'] == $productId && $item['shop_id'] == $shopId) {
                $item['quantity'] += $quantity;
                $found = true;
                break;
            }
        }
        if (!$found) {
            $cartItems[] = [
                'product_id' => $productId,
                'shop_id' => $shopId,
                'quantity' => $quantity,
            ];
        }
        $cartItemsJson = json_encode($cartItems);
        Cookie::queue('cartItems', $cartItemsJson, 60 * 24 * 30, '/', env('SESSION_DOMAIN'), true, true, false, 'None');

        return response()->json(['message' => 'Sản phẩm đã được thêm vào giỏ hàng', 'status' => true, 'cart' => $cartItems]);
    }

    public function removeProductFromCart(Request $request)
    {
        $productId = $request->get('product_id');
        $shopId = $request->get('shop_id');
        $quantityToRemove = $request->get('quantity');

        $cartItemsJson = Cookie::has('cartItems') ? Cookie::get('cartItems') : '[]';
        $cartItems = json_decode($cartItemsJson, true);

        $updatedCartItems = [];
        $found = false;

        foreach ($cartItems as $item) {
            if ($item['product_id'] == $productId && $item['shop_id'] == $shopId) {
                $remainingQuantity = $item['quantity'];

                if ($quantityToRemove <= $remainingQuantity) {
                    $remainingQuantity -= $quantityToRemove;
                    $quantityToRemove = 0;
                    if ($remainingQuantity > 0) {
                        $updatedCartItems[] = [
                            'product_id' => $productId,
                            'shop_id' => $shopId,
                            'quantity' => $remainingQuantity,
                        ];
                    }
                } else {
                    $quantityToRemove -= $remainingQuantity;
                    $found = true;
                    continue;
                }
            } else {
                $updatedCartItems[] = $item;
            }
        }

        $cartItemsJson = json_encode($updatedCartItems);
        Cookie::queue('cartItems', $cartItemsJson, 60 * 24 * 30, '/', env('SESSION_DOMAIN'), true, true, false, 'None');

        return response()->json(['message' => 'Sản phẩm đã được xóa khỏi giỏ hàng', 'status' => true, 'cart' => $updatedCartItems]);
    }

    public function removeShopFromCart(Request $request)
    {
        $shopId = $request->get('shop_id');

        $cartItemsJson = Cookie::has('cartItems') ? Cookie::get('cartItems') : '[]';
        $cartItems = json_decode($cartItemsJson, true);
        $cartItems = array_filter($cartItems, function ($item) use ($shopId) {
            return $item['shop_id'] != $shopId;
        });
        $cartItemsJson = json_encode(array_values($cartItems));
        Cookie::queue('cartItems', $cartItemsJson, 60 * 24 * 30, '/', env('SESSION_DOMAIN'), true, true, false, 'None');

        return response()->json(['message' => 'Tất cả sản phẩm của cửa hàng đã được xóa khỏi giỏ hàng', 'status' => true, 'cart' => $cartItems]);
    }

    public function updateCartQuantity(Request $request)
    {
        $shopId = $request->get('shop_id');
        $productId = $request->get('product_id');
        $newQuantity = $request->get('quantity');

        $cartItemsJson = Cookie::get('cartItems', '[]');
        $cartItems = json_decode($cartItemsJson, true);

        $itemFound = false;
        $product = DB::table('products')
            ->join('products_attribute', 'products.id', '=', 'products_attribute.product_id')
            ->where('products.id', $productId)
            ->where('products.shop_id', $shopId)
            ->select('products.quantity')
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Không tìm thấy sản phẩm', 'status' => false]);
        }

        foreach ($cartItems as &$item) {
            if ($item['product_id'] == $productId && $item['shop_id'] == $shopId) {
                if ($newQuantity > 0) {
                    if ($newQuantity > $product->quantity) {
                        return response()->json(['message' => 'Số lượng sản phẩm vượt quá số lượng tồn kho', 'status' => false]);
                    }
                    $item['quantity'] = $newQuantity;
                } else {
                    $cartItems = array_filter($cartItems, function ($cartItem) use ($productId, $shopId) {
                        return !($cartItem['product_id'] == $productId && $cartItem['shop_id'] == $shopId);
                    });
                }
                $itemFound = true;
                break;
            }
        }

        if (!$itemFound) {
            return response()->json(['message' => 'Sản phẩm không tồn tại trong giỏ hàng', 'status' => false]);
        }

        $cartItemsJson = json_encode(array_values($cartItems));
        Cookie::queue('cartItems', $cartItemsJson, 60 * 24 * 30, '/', env('SESSION_DOMAIN'), true, true, false, 'None');

        return response()->json(['message' => 'Số lượng sản phẩm trong giỏ hàng đã được cập nhật', 'status' => true, 'cart' => $cartItems]);
    }


    public function checkout(Request $request)
    {
        $selectedItems = $request->get('items', []);
        $cartItemsJson = Cookie::get('cartItems', '[]');
        $cartItems = json_decode($cartItemsJson, true);

        if (empty($cartItems)) {
            return response()->json(['message' => 'Giỏ hàng trống', 'data' => [], 'status' => false]);
        }

        $checkoutDetails = [];

        foreach ($selectedItems as $selectedItem) {
            $shopId = $selectedItem['shop_id'];
            $shop = DB::table('shop')->where('id', $shopId)->first();
            if ($shop) {
                $shopDetails = [
                    'shop_id' => $shop->id,
                    'shop_name' => $shop->name,
                    'products' => []
                ];

                foreach ($selectedItem['products'] as $productItem) {
                    $productId = $productItem['product_id'];
                    $quantity = $productItem['quantity'];

                    $product = DB::table('products as p')
                        ->leftJoin('product_discounts as pd', function ($join) use ($quantity) {
                            $join->on('p.id', '=', 'pd.product_id')
                                ->whereDate('pd.date_start', '<=', now())
                                ->whereDate('pd.date_end', '>=', now())
                                ->where('pd.number', '>=', $quantity);
                        })
                        ->leftJoin('products_attribute as pa', function ($join) use ($quantity) {
                            $join->on('p.id', '=', 'pa.product_id')
                                ->where('pa.quantity', '<=', $quantity)
                                ->where(function($query) use ($quantity) {
                                    $query->where('pa.quantity', '=', $quantity)
                                        ->orWhere(function($subquery) use ($quantity) {
                                            $subquery->where('pa.quantity', '<=', $quantity)
                                                ->whereRaw('pa.quantity = (SELECT MAX(quantity) FROM products_attribute WHERE quantity <= ? AND product_id = pa.product_id)', [$quantity]);
                                        });
                                });
                        })
                        ->where('p.id', $productId)
                        ->where('p.shop_id', $shopId)
                        ->select(
                            'p.id',
                            'p.name',
                            'p.name_en',
                            'p.unit',
                            'p.en_unit',
                            'p.src',
                            'p.quantity as inventory_quantity',
                            'pa.price as attribute_price',
                            'pd.discount',
                            DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price),0) as final_price')
                        )
                        ->first();

                    if ($product) {
                        $shopDetails['products'][] = [
                            'product_id' => $product->id,
                            'name' => $product->name,
                            'name_en' => $product->name_en,
                            'unit' => $product->unit,
                            'unit_en' => $product->en_unit,
                            'quantity' => $quantity,
                            'inventory_quantity' => $product->inventory_quantity,
                            'original_price' => $product->attribute_price ?? 0,
                            'discount' => $product->discount,
                            'price' => $product->final_price,
                            'src' => json_decode($product->src, true),
                        ];
                    }
                }
                $checkoutDetails[] = $shopDetails;
            }
        }

        return response()->json(['message' => 'Lấy dữ liệu thành công', 'data' => $checkoutDetails, 'status' => true]);
    }

    public function buyNow(Request $request)
    {
        $productId = $request->get('product_id');
        $quantity = $request->get('quantity');

        $product = DB::table('products as p')
            ->leftJoin('product_discounts as pd', function ($join) use ($quantity) {
                $join->on('p.id', '=', 'pd.product_id')
                    ->whereDate('pd.date_start', '<=', now())
                    ->whereDate('pd.date_end', '>=', now())
                    ->where('pd.number', '>=', $quantity);
            })
            ->leftJoin('products_attribute as pa', function ($join) use ($quantity) {
                $join->on('p.id', '=', 'pa.product_id')
                    ->where('pa.quantity', '<=', $quantity)
                    ->where(function($query) use ($quantity) {
                        $query->where('pa.quantity', '=', $quantity)
                            ->orWhere(function($subquery) use ($quantity) {
                                $subquery->where('pa.quantity', '<=', $quantity)
                                    ->whereRaw('pa.quantity = (SELECT MAX(quantity) FROM products_attribute WHERE quantity <= ? AND product_id = pa.product_id)', [$quantity]);
                            });
                    });
            })
            ->leftJoin('shops as s', 'p.shop_id', '=', 's.id')
            ->where('p.id', $productId)
            ->select(
                'p.id',
                'p.name',
                'p.name_en',
                'p.unit',
                'p.en_unit',
                'p.shop_id',
                's.name as shop_name',
                'p.src',
                'p.quantity as inventory_quantity',
                'pa.price as attribute_price',
                'pd.discount',
                DB::raw('ROUND(IF(pd.discount IS NOT NULL, pa.price - (pa.price * pd.discount / 100), pa.price),0) as final_price')
            )
            ->first();
        $product->quantity = $quantity;
        $product->src = json_decode($product->src, true);
        $shopDetails = [
            'shop_id' => $product->shop_id,
            'shop_name' => $product->shop_name,
            'products' => [
                'product_id' => $product->id,
                'name' => $product->name,
                'name_en' => $product->name_en,
                'unit' => $product->unit,
                'unit_en' => $product->en_unit,
                'quantity' => $quantity,
                'inventory_quantity' => $product->inventory_quantity,
                'original_price' => $product->attribute_price ?? 0,
                'discount' => $product->discount,
                'price' => $product->final_price,
                'src' => json_decode($product->src, true),
            ]
        ];

        return response()->json(['message' => 'Lấy dữ liệu thành công', 'data' => $shopDetails, 'status' => true]);
    }

    public function pay(Request $request)
    {
        try{
            $user = JWTAuth::user();
            $today = date('Ymd');
            $orderCount = DB::table('orders')->whereDate('created_at', now()->format('Y-m-d'))->count();
            $orderCode = $today . ($orderCount + 1);
            $deliver_address = DeliveryAddressModel::find($request->get('deliver_address'));
            $orderIds = [];
            $totalProductCost = 0;
            $totalShip = 0;
            $totalPayment = 0;

            // Kiểm tra tồn kho cho tất cả các sản phẩm trước khi tạo đơn hàng
            foreach ($request->get('shop_items') as $shopItem) {
                foreach ($shopItem['products'] as $product) {
                    $productId = $product['product_id'];
                    $quantity = $product['quantity'];

                    $data_product = ProductsModel::find($productId);
                    if ($data_product->quantity < $quantity) {
                        return response()->json([
                            'message' => "Sản phẩm '{$data_product->name}' có số lượng tồn kho không đủ",
                            'status' => false
                        ]);
                    }
                }
            }

            $cartItemsJson = Cookie::get('cartItems', '[]');
            $cartItems = json_decode($cartItemsJson, true);
            foreach ($request->get('shop_items') as $shopItem) {
                $shopId = $shopItem['shop_id'];

                $order = new OrdersModel();
                $order->order_code = $orderCode;
                $order->shop_id = $shopId;
                $order->user_id = $user->id;
                $order->name = $deliver_address->name;
                $order->phone = $deliver_address->phone;
                $order->province_id = $deliver_address->province_id;
                $order->district_id = $deliver_address->district_id;
                $order->ward_id = $deliver_address->ward_id;
                $order->address_detail = $deliver_address->address_detail;
                $order->note = $shopItem['note'];
                $order->shipping_unit = $shopItem['shipping_unit'];
                $order->commodity_money = 0;
                $order->shipping_fee = $shopItem['shipping_fee'];
                $order->total_payment = 0;
                $order->status = 0;
                $order->save();

                $orderTotalMoney = 0;

                foreach ($shopItem['products'] as $product) {
                    $productId = $product['product_id'];
                    $quantity = $product['quantity'];
                    $price = $product['price'];
                    $totalMoney = $price * $quantity;
                    $orderTotalMoney += $totalMoney;

                    $orderItem = new OrdersItemModel();
                    $orderItem->order_id = $order->id;
                    $orderItem->product_id = $productId;
                    $orderItem->quantity = $quantity;
                    $orderItem->price = $price;
                    $orderItem->total_money = $totalMoney;
                    $orderItem->save();

                    $data_product = ProductsModel::find($productId);
                    $data_product->quantity -= $quantity;
                    $data_product->save();

                    $productDiscount = DB::table('product_discounts')
                        ->where('product_id', $productId)
                        ->where('number', '>=', $quantity)
                        ->whereDate('date_start', '<=', now())
                        ->whereDate('date_end', '>=', now())
                        ->orderBy('number', 'desc')
                        ->first();

                    if ($productDiscount) {
                        $remainingQuantity = $productDiscount->number - $quantity;
                        DB::table('product_discounts')
                            ->where('id', $productDiscount->id)
                            ->update(['number' => $remainingQuantity]);
                    }
                    // Xóa sản phẩm đã mua khỏi giỏ hàng
                    if (isset($cartItems)) {
                        foreach ($cartItems as $key => $item) {
                            if (is_array($item) && isset($item['product_id']) && $item['product_id'] == $productId) {
                                unset($cartItems[$key]);
                            }
                        }
                    }
                }
                $order->commodity_money = $orderTotalMoney;
                $order->total_payment = $orderTotalMoney + $order->shipping_fee;
                $order->save();

                $orderIds[] = $order->id;
                $totalProductCost += $orderTotalMoney;
                $totalShip += $order->shipping_fee;
                $totalPayment += $order->total_payment;
            }
            $order_total = new OrderTotalModel();
            $order_total->order_id = implode(',', $orderIds);
            $order_total->type_payment = $request->get('type_payment');
            $order_total->total_shipping_fee = $totalShip;
            $order_total->total_product = $totalProductCost;
            $order_total->exchange_points = $request->get('exchange_points');
            $order_total->total_payment = $totalPayment;
            $order_total->save();

            Cookie::queue('cartItems', json_encode($cartItems), 60 * 24 * 7, '/', env('SESSION_DOMAIN'), true, true, false, 'None');

            return response()->json(['message' => 'Tạo đơn hàng thành công', 'status' => true]);
        }catch(\Exception $e){
            return response()->json(['message' => $e->getMessage(), 'status' => false]);
        }
    }

}
