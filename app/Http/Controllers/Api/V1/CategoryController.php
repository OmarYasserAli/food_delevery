<?php

namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CategoryLogic;
use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Pagination\Paginator;


class CategoryController extends Controller
{
    public function get_categories()
    {
        try {
            $categories = Category::where(['position'=>0,'status'=>1])
            ->when(config('module.current_module_data'), function($query){
                $query->module(config('module.current_module_data')['id']);
            })
            ->orderBy('priority','desc')->get();
            return response()->json(Helpers::category_data_formatting($categories, true), 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }
    public function get_category_with_product(){
        try {
             $categories = Category::with("items")->paginate(2);
            $current_page =  $categories->currentPage();
            $lastPage = $categories->lastPage();
            $hasMorePages = $categories->hasMorePages();
              $categories=  $categories ->map(function ($query) {
                $query->setRelation('items', $query->items->take(5));

                return $query;
            });
            foreach ($categories as $category) {

                foreach ($category->items as $item){
                $item["category_ids"] = json_decode($item->category_ids);
                $item["variations"] = json_decode($item->variations);
                $item["add_ons"] = json_decode($item->add_ons);
                $item["attributes"] = json_decode($item->attributes);
                $item["choice_options"] = json_decode($item->choice_options);
                $item['store_discount'] = Helpers::get_store_discount($item->store) ? $item->store->discount->discount : 0;

                }
            }
            $categories = json_decode($categories);

            return response()->json([
                'status' => "success",
                "currentPage" => $current_page,
                'lastPage' => $lastPage,
                'hasMorePages' =>$hasMorePages,
                'totalCategories' =>count($categories),
                'category' => $categories,


            ], 200);

        }catch (\Exception $e) {
return $e;
            return response()->json([], 200);
        }

    }

    public function get_childes($id)
    {
        try {
            $categories = Category::where(['parent_id' => $id,'status'=>1])->orderBy('priority','desc')->get();
            return response()->json(Helpers::category_data_formatting($categories, true), 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }

    public function get_products($id, Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $zone_id= $request->header('zoneId');

        $type = $request->query('type', 'all');

        $data = CategoryLogic::products($id, $zone_id, $request['limit'], $request['offset'], $type);
        $data['products'] = Helpers::product_data_formatting($data['products'] , true, false, app()->getLocale());
        return response()->json($data, 200);
    }


    public function get_stores($id, Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'limit' => 'required',
            'offset' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $zone_id= $request->header('zoneId');

        $type = $request->query('type', 'all');

        $data = CategoryLogic::stores($id, $zone_id, $request['limit'], $request['offset'], $type);
        $data['stores'] = Helpers::store_data_formatting($data['stores'] , true);
        return response()->json($data, 200);
    }



    public function get_all_products($id,Request $request)
    {
        if (!$request->hasHeader('zoneId')) {
            $errors = [];
            array_push($errors, ['code' => 'zoneId', 'message' => translate('messages.zone_id_required')]);
            return response()->json([
                'errors' => $errors
            ], 403);
        }
        $zone_id= $request->header('zoneId');

        try {
            return response()->json(Helpers::product_data_formatting(CategoryLogic::all_products($id, $zone_id), true, false, app()->getLocale()), 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }
}
