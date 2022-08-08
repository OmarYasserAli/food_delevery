<?php
namespace App\Http\Controllers\Admin;
use App\Models\Currency;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Mail;

class LanguageController extends Controller
{
    public function translate($lang)
    {
        
        $full_data = include(base_path('resources/lang/' . $lang . '/messages.php'));
        $lang_data = [];
        ksort($full_data);
        foreach ($full_data as $key => $data) {
            array_push($lang_data, ['key' => $key, 'value' => $data]);
        }
        //dd($lang_data);
        return view('admin-views.business-settings.language.translate', compact('lang', 'lang_data'));
    }
    public function translate_submit(Request $request, $lang)
    {
        
        $full_data = include(base_path('resources/lang/' . $lang . '/messages.php'));
        $full_data[$request['key']] = $request['value'];
        $str = "<?php return " . var_export($full_data, true) . ";";
        file_put_contents(base_path('resources/lang/' . $lang . '/messages.php'), $str);
    }

    public function lang($local)
    {
        app()->setLocale($local);
       //dd(\App::getLocale() );
        // $direction = 'ltr';
        // $language = BusinessSetting::where('type', 'language')->first();
        // foreach (json_decode($language['value'], true) as $key => $data) {
        //     if ($data['code'] == $local) {
        //         $direction = isset($data['direction']) ? $data['direction'] : 'ltr';
        //     }
        // }
         session()->forget('language_settings');
        // Helpers::language_load();
        session()->put('local', $local);
        // Session::put('direction', $direction);
        return redirect()->back();
    }

}
