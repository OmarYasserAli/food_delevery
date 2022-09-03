<?php

namespace App\Http\Controllers\Vendor;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class CategoryController extends Controller
{
    function index()
    {

        $categories=  Helpers::model_join_translation(Category::where(['position'=>0])->module(Helpers::get_store_data()->module_id)->latest(), 1);
        return view('vendor-views.category.index',compact('categories'));
    }

    public function get_all(Request $request){
        if (LaravelLocalization::getCurrentLocale() == 'en') {

            $data = Category::where('name', 'like', '%' . $request->q . '%')->module(Helpers::get_store_data()->module_id)->limit(8)->get([DB::raw('id, CONCAT(name, " (", if(position = 0, "' . translate('messages.main') . '", "' . translate('messages.sub') . '"),")") as text')]);
        }
        elseif (LaravelLocalization::getCurrentLocale() == 'ar'){
            $data = Category::join('translations', 'translations.translationable_id', '=', 'categories.id')
                ->where(function ($q) use($request)  {

                    $q->where('translations.translationable_type','App\Models\Category')->where('translations.value', 'like', "%{$request->q}%");
                })
                ->select(
                    'categories.id as id',
                    'translations.value as text'
                )->module(Helpers::get_store_data()->module_id)
                ->limit(8)
                ->get([DB::raw('id, CONCAT(name, " (", if(position = 0, "' . translate('messages.main') . '", "' . translate('messages.sub') . '"),")") as text')]);

        }

        if(isset($request->all))
        {
            $data[]=(object)['id'=>'all', 'text'=>'All'];
        }
        $res =response()->json($data);

        return $res;
        // return response()->json($data);
    }

    function sub_index()
    {
        $categories=Category::with(['parent'])
        ->whereHas('parent',function($query){
            $query->module(Helpers::get_store_data()->module_id);
        })
        ->where(['position'=>1])->latest()->paginate(config('default_pagination'));
        return view('vendor-views.category.sub-index',compact('categories'));
    }

    public function search(Request $request){
        $key = explode(' ', $request['search']);
        $categories=Category::where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->orWhere('name', 'like', "%{$value}%");
            }
        })->limit(50)->get();
        return response()->json([
            'view'=>view('vendor-views.category.partials._table',compact('categories'))->render(),
            'count'=>$categories->count()
        ]);
    }
}
