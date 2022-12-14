<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderDetail;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\CentralLogics\ProductLogic;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Scopes\StoreScope;
use PDF;
class POSController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category_id', 0);
        $module_id = $request->query('module_id', null);
        $store_id = $request->query('store_id', null);
        $categories =  Helpers::model_join_translation(Category::active()->module($module_id));
        $store = Store::active()->find($store_id);
        $keyword = $request->query('keyword', false);
        $key = explode(' ', $keyword);

        if ($request->session()->has('cart')) {
            $cart = $request->session()->get('cart', collect([]));
            if(!isset($cart['store_id']) || $cart['store_id'] != $store_id) {
                session()->forget('cart');
            }
        }

        $products = Helpers::model_join_translation(Item::withoutGlobalScope(StoreScope::class)->active()
        ->when($category, function($query)use($category){
            $query->whereHas('category',function($q)use($category){
                return $q->whereId($category)->orWhere('parent_id', $category);
            });
        })
        ->when($keyword, function($query)use($key){
            return $query->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->orWhere('name', 'like', "%{$value}%");
                }
            });
        })
        ->whereHas('store', function($query)use($store_id, $module_id){
            return $query->where(['id'=>$store_id, 'module_id'=>$module_id]);
        })
        ->latest(),10);
        return view('admin-views.pos.index', compact('categories', 'products','category', 'keyword', 'store', 'module_id'));
    }

    public function quick_view(Request $request)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->with('store')->findOrFail($request->product_id);

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos._quick-view-data', compact('product'))->render(),
        ]);
    }

    public function quick_view_card_item(Request $request)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->findOrFail($request->product_id);
        $item_key = $request->item_key;
        $cart_item = session()->get('cart')[$item_key];

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos._quick-view-cart-item', compact('product', 'cart_item', 'item_key'))->render(),
        ]);
    }

    public function variant_price(Request $request)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->with('store')->find($request->id);
        $str = '';
        $quantity = 0;
        $price = 0;
        $addon_price = 0;

        foreach (json_decode($product->choice_options) as $key => $choice) {
            if ($str != null) {
                $str .= '-' . str_replace(' ', '', $request[$choice->name]);
            } else {
                $str .= str_replace(' ', '', $request[$choice->name]);
            }
        }

        if($request['addon_id'])
        {
            foreach($request['addon_id'] as $id)
            {
                $addon_price+= $request['addon-price'.$id]*$request['addon-quantity'.$id];
            }
        }

        if ($str != null) {
            $count = count(json_decode($product->variations));
            for ($i = 0; $i < $count; $i++) {
                if (json_decode($product->variations)[$i]->type == $str) {
                    $price = json_decode($product->variations)[$i]->price - Helpers::product_discount_calculate($product, json_decode($product->variations)[$i]->price,$product->store);
                }
            }
        } else {
            $price = $product->price - Helpers::product_discount_calculate($product, $product->price,$product->store);
        }

        return array('price' => Helpers::format_currency(($price * $request->quantity)+$addon_price));
    }

    public function addToCart(Request $request)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->with('store')->find($request->id);

        $data = array();
        $data['id'] = $product->id;
        $str = '';
        $variations = [];
        $price = 0;
        $addon_price = 0;

        //Gets all the choice values of customer choice option and generate a string like Black-S-Cotton
        foreach (json_decode($product->choice_options) as $key => $choice) {
            $data[$choice->name] = $request[$choice->name];
            $variations[$choice->title] = $request[$choice->name];
            if ($str != null) {
                $str .= '-' . str_replace(' ', '', $request[$choice->name]);
            } else {
                $str .= str_replace(' ', '', $request[$choice->name]);
            }
        }
        $data['variations'] = $variations;
        $data['variant'] = $str;
        if ($request->session()->has('cart') && !isset($request->cart_item_key)) {
            if (count($request->session()->get('cart')) > 0) {
                foreach ($request->session()->get('cart') as $key => $cartItem) {
                    if (is_array($cartItem) && $cartItem['id'] == $request['id'] && $cartItem['variant'] == $str) {
                        return response()->json([
                            'data' => 1
                        ]);
                    }
                }

            }
        }
        //Check the string and decreases quantity for the stock
        if ($str != null) {
            $count = count(json_decode($product->variations));
            for ($i = 0; $i < $count; $i++) {
                if (json_decode($product->variations)[$i]->type == $str) {
                    $price = json_decode($product->variations)[$i]->price;
                    $data['variations'] = json_decode($product->variations, true)[$i];
                }
            }
        } else {
            $price = $product->price;
        }

        $data['quantity'] = $request['quantity'];
        $data['price'] = $price;
        $data['name'] = $product->name;
        $data['discount'] = Helpers::product_discount_calculate($product, $price,$product->store);
        $data['image'] = $product->image;
        $data['add_ons'] = [];
        $data['add_on_qtys'] = [];

        if($request['addon_id'])
        {
            foreach($request['addon_id'] as $id)
            {
                $addon_price+= $request['addon-price'.$id]*$request['addon-quantity'.$id];
                $data['add_on_qtys'][]=$request['addon-quantity'.$id];
            }
            $data['add_ons'] = $request['addon_id'];
        }

        $data['addon_price'] = $addon_price;

        if ($request->session()->has('cart')) {
            $cart = $request->session()->get('cart', collect([]));

            if(!isset($cart['store_id']) || $cart['store_id'] != $product->store_id) {
                return response()->json([
                    'data' => -1
                ]);
            }
            if(isset($request->cart_item_key))
            {
                $cart[$request->cart_item_key] = $data;
                $data = 2;
            }
            else
            {
                $cart->push($data);
            }

        } else {
            $cart = collect([$data]);
            $cart->put('store_id', $product->store_id);
            $request->session()->put('cart', $cart);
        }

        return response()->json([
            'data' => $data
        ]);
    }

    public function cart_items(Request $request)
    {
        $store = Store::find($request->store_id);
        return view('admin-views.pos._cart', compact('store'));
    }

    //removes from Cart
    public function removeFromCart(Request $request)
    {
        if ($request->session()->has('cart')) {
            $cart = $request->session()->get('cart', collect([]));
            $cart->forget($request->key);
            $request->session()->put('cart', $cart);
        }

        return response()->json([],200);
    }

    //updated the quantity for a cart item
    public function updateQuantity(Request $request)
    {
        $cart = $request->session()->get('cart', collect([]));
        $cart = $cart->map(function ($object, $key) use ($request) {
            if ($key == $request->key) {
                $object['quantity'] = $request->quantity;
            }
            return $object;
        });
        $request->session()->put('cart', $cart);
        return response()->json([],200);
    }

    //empty Cart
    public function emptyCart(Request $request)
    {
        session()->forget('cart');
        return response()->json([],200);
    }

    public function update_tax(Request $request)
    {
        $cart = $request->session()->get('cart', collect([]));
        $cart['tax'] = $request->tax;
        $request->session()->put('cart', $cart);
        return back();
    }

    public function update_discount(Request $request)
    {
        $cart = $request->session()->get('cart', collect([]));
        $cart['discount'] = $request->discount;
        $cart['discount_type'] = $request->type;
        $request->session()->put('cart', $cart);
        return back();
    }

    public function get_customers(Request $request){
        $key = explode(' ', $request['q']);
        $data = User::
        where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('f_name', 'like', "%{$value}%")
                ->orWhere('l_name', 'like', "%{$value}%")
                ->orWhere('phone', 'like', "%{$value}%");
            }
        })
        ->limit(8)
        ->get([DB::raw('id, CONCAT(f_name, " ", l_name, " (", phone ,")") as text')]);

        $data[]=(object)['id'=>false, 'text'=>translate('messages.walk_in_customer')];

        return response()->json($data);
    }

    public function place_order(Request $request)
    {
        
        if($request->session()->has('cart'))
        {
            if(count($request->session()->get('cart')) < 1)
            {
                Toastr::error(translate('messages.cart_empty_warning'));
                return back();
            }
        }
        else
        {
            Toastr::error(translate('messages.cart_empty_warning'));
            return back();
        }
        $user = User::find($request->user_id);
        $address = [
            'contact_person_name' => $request->contact_person_name ? $request->contact_person_name : ($user ? $user->f_name . ' ' . $user->f_name : ''),
            'contact_person_number' => $request->contact_person_number ? $request->contact_person_number : ($user ? $user->phone : ''),
            'address_type' => $request->address_type ? $request->address_type : 'Delivery',
            'address' => $request->address,
            'floor' => $request->floor,
            'road' => $request->road,
            'flat' => $request->flat,
            'house' => $request->house,
            'longitude' => (string)$request->longitude??'',
            'latitude' => (string)$request->latitude??'',
        ];

        $store = Store::find($request->store_id);
        $cart = $request->session()->get('cart');

        $total_addon_price = 0;
        $product_price = 0;
        $store_discount_amount = 0;

        $order_details = [];
        $product_data = [];
        $order = new Order();
        $order->id = 100000 + Order::all()->count() + 1;
        if (Order::find($order->id)) {
            $order->id = Order::latest()->first()->id + 1;
        }
        $order->payment_status = 'paid';
        $order->order_status = 'delivered';
        $order->order_type = 'pos';
        $order->payment_method = $request->type;
        $order->store_id = $store->id;
        $order->module_id = $store->module_id;
        $order->user_id = $request->user_id;
        $order->delivery_charge = 0;
        $order->original_delivery_charge = 0;
        $order->delivery_address = isset($request->address)?json_encode($address):null;
        $order->checked = 1;
        $order->created_at = now();
        $order->updated_at = now();
        foreach ($cart as $c) {
            if(is_array($c))
            {
                $product = Item::withoutGlobalScope(StoreScope::class)->find($c['id']);
                if ($product) {
                    if (count(json_decode($product['variations'], true)) > 0) {
                        $variant_data = Helpers::variation_price($product, json_encode([$c['variations']]));
                        $price = $variant_data['price'];
                        $stock = $variant_data['stock'];
                    } else {
                        $price = $product['price'];
                        $stock = $product->stock;
                    }

                    if(config('module.'.$product->module->module_type)['stock'])
                    {
                        if($c['quantity']>$stock)
                        {
                            Toastr::error(translate('messages.product_out_of_stock_warning',['item'=>$product->name]));
                            return back();
                        }

                        $product_data[]=[
                            'item'=>clone $product,
                            'quantity'=>$c['quantity'],
                            'variant'=>count($c['variations'])>0?$c['variations']['type']:null
                        ];
                    }

                    $price = $c['price'];
                    $product->tax = $store->tax;
                    $product = Helpers::product_data_formatting($product);
                    $addon_data = Helpers::calculate_addon_price(\App\Models\AddOn::whereIn('id',$c['add_ons'])->get(), $c['add_on_qtys']);
                    $or_d = [
                        'item_id' => $c['id'],
                        'item_campaign_id' => null,
                        'item_details' => json_encode($product),
                        'quantity' => $c['quantity'],
                        'price' => $price,
                        'tax_amount' => Helpers::tax_calculate($product, $price),
                        'discount_on_item' => Helpers::product_discount_calculate($product, $price, $store),
                        'discount_type' => 'discount_on_product',
                        'variant' => json_encode($c['variant']),
                        'variation' => json_encode([$c['variations']]),
                        'add_ons' => json_encode($addon_data['addons']),
                        'total_add_on_price' => $addon_data['total_add_on_price'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ];
                    $order_item = Item::find($c['id'])->order_count;
//                    dd($order_item);
                    $item = Item::find($c['id'])->update([
                       'order_count' => ++$order_item
                    ]);
                    $total_addon_price += $or_d['total_add_on_price'];
                    $product_price += $price*$or_d['quantity'];
                    $store_discount_amount += $or_d['discount_on_item']*$or_d['quantity'];
                    $order_details[] = $or_d;
                }
            }
        }


        if(isset($cart['discount']))
        {
            $store_discount_amount += $cart['discount_type']=='percent'&&$cart['discount']>0?((($product_price + $total_addon_price - $store_discount_amount) * $cart['discount'])/100):$cart['discount'];
        }

        $total_price = $product_price + $total_addon_price - $store_discount_amount;
        $tax = isset($cart['tax'])?$cart['tax']:$store->tax;
        $total_tax_amount= ($tax > 0)?(($total_price * $tax)/100):0;

        try {
            $order->store_discount_amount= $store_discount_amount;
            $order->total_tax_amount= $total_tax_amount;
            $order->order_amount = $total_price + $total_tax_amount + $order->delivery_charge;
            $order->adjusment = $request->amount - ($total_price + $total_tax_amount + $order->delivery_charge);
            $order->save();
            foreach ($order_details as $key => $item) {
                $order_details[$key]['order_id'] = $order->id;
            }
            OrderDetail::insert($order_details);
            if(count($product_data)>0)
            {
                foreach($product_data as $item)
                {
                    ProductLogic::update_stock($item['item'], $item['quantity'], $item['variant'])->save();
                }
            }
            session()->forget('cart');
            session(['last_order' => $order->id]);
            Toastr::success(translate('messages.order_placed_successfully'));
            return back();
        } catch (\Exception $e) {
            info($e);
        }
        Toastr::warning(translate('messages.failed_to_place_order'));
        return back();
    }

    public function order_list()
    {
        $orders = Order::with(['customer'])
        ->pos()
        ->latest()
        ->paginate(config('default_pagination'));

        return view('admin-views.pos.order.list', compact('orders'));
    }

    public function inv_print(){
        $customPaper = array(0,0,567.00,283.80);
        $mpdf = PDF::loadView('admin-views.pos.order.inv_print',[],[], [
            'format' => [180, 250]
     ]);
        $mpdf->showImageErrors = true;
        return $mpdf->stream('document.pdf');
    }
    public function generate_invoice_pdf($id)
    {
        $mpdf = new \Mpdf\Mpdf(array('mode' => 'utf-8', 'format' => array(216,356)));	
        $order = Order::with(['details', 'store' => function ($query) {
            return $query->withCount('orders');
        }, 'details.item' => function ($query) {

            return Helpers::model_join_translation($query->withoutGlobalScope(StoreScope::class));
        }, 'details.campaign' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }])->where('id', $id)->first();
        $customPaper = array(0,0,567.00,283.80);

        $data = [
            'order'=>$order,
        ];
        
        $h= 90;
        if($order->store->gst_status) $h+=10;
        if($order->store) $h+=40;
        $h+= $order->details->count()*9;
        $address = json_decode($order->delivery_address, true);
        if(!empty($address)) $h+=40;
        $v = view('admin-views.pos.order.inv_print',$data);
        //dd($v);
        $mpdf = PDF::loadView('admin-views.pos.order.inv_print',$data,[], [
            'format' => [80, $h]
     ]);
        $mpdf->showImageErrors = true;
        return $mpdf->stream('document.pdf');
        // return response()->json([
        //     'success' => 1,
        //     'view' => view('admin-views.pos.order.invoice', compact('order'))->render(),
        // ]);
    }

    public function search(Request $request){
        $key = explode(' ', $request['search']);
        $orders=Order::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%")
                    ->orWhere('order_status', 'like', "%{$value}%")
                    ->orWhere('transaction_reference', 'like', "%{$value}%");
            }
        })->pos()->limit(100)->get();
        $parcel_order =  false;
        return response()->json([
            'view'=>view('admin-views.pos.order.partials._table',compact('orders', 'parcel_order'))->render()
        ]);
    }

    public function order_details($id)
    {
        $order = Order::with(['details', 'store' => function ($query) {
            return $query->withCount('orders');
        }, 'customer' => function ($query) {
            return $query->withCount('orders');
        }, 'delivery_man' => function ($query) {
            return $query->withCount('orders');
        }, 'details.item' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }, 'details.campaign' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }])->where(['id' => $id])->first();
        if (isset($order)) {
            return view('admin-views.pos.order.order-view', compact('order'));
        } else {
            Toastr::info(translate('No more orders!'));
            return back();
        }
    }

    public function generate_invoice($id)
    {
        $order = Order::with(['details', 'store' => function ($query) {
            return $query->withCount('orders');
        }, 'details.item' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }, 'details.campaign' => function ($query) {
            return $query->withoutGlobalScope(StoreScope::class);
        }])->where('id', $id)->first();

        return response()->json([
            'success' => 1,
            'view' => view('admin-views.pos.order.invoice', compact('order'))->render(),
        ]);
    }

    public function customer_store(Request $request)
    {
        $request->validate([
            'f_name' => 'required',
            'l_name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'unique:users',
        ]);
        User::create([
            'f_name' => $request['f_name'],
            'l_name' => $request['l_name'],
            'email' => $request['email'],
            'phone' => $request['phone'],
            'password' => bcrypt('password')
        ]);
        try
        {
            if ( config('mail.status') ) {
                Mail::to($request->email)->send(new \App\Mail\CustomerRegistration($request->f_name.' '.$request->l_name));
            }
        }
        catch(\Exception $ex)
        {
            info($ex);
        }
        Toastr::success(translate('customer_added_successfully'));
        return back();
    }
}
