<?php

namespace App\Http\Controllers;

use App\Services\BrandService;
use Illuminate\Http\Request;
use Helper;
use Cache;

class SettingsController extends Controller
{
    private $model, $route, $title;

    public function __construct()
    {
        $this->model = 'App\Models\Settings';
        $this->route = 'settings';
        $this->title = 'app.settings';
    }
    
    public function edit($id, BrandService $brand)
    {
        return view('cms.'.$this->route.'.form', [
            'item' => $this->model::findOrFail($id),
            'editing' => true,
            'route' => $this->route,
            'title' => __($this->title),
            'brandColors' => $brand->colors(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);    

        $request->validate([
            'webtitle' => ['required', 'string', 'max:191'],
            'webdescription' => ['required', 'string', 'max:500'],
            'webkeys' => ['required', 'string', 'max:500'],
            'logo' => ['nullable', 'mimes:jpeg,png,svg', 'image', 'max:5000','dimensions:min_width=150,min_height=50'],
            'brand_font_primary' => ['nullable', 'string', 'max:80'],
            'brand_font_secondary' => ['nullable', 'string', 'max:80'],
            'favicon' => ['nullable', 'mimes:jpeg,png,svg', 'image', 'max:5000','dimensions:min_width=50,min_height=50'],
            'map' => ['nullable', 'mimes:jpeg,png,svg', 'image', 'max:5000','dimensions:min_width=960,min_height=680'],

        ]);
        
        $logo = $item->logo;
        if($request->hasFile('logo')) $logo = Helper::saveImage($request->logo, $this->route, 'logo', $logo);

        $favicon = $item->favicon;
        if($request->hasFile('favicon')) $favicon = Helper::saveImage($request->favicon, $this->route, 'favicon', $favicon);

        $map = $item->map;
        if($request->hasFile('map')) $map = Helper::saveImage($request->map, $this->route, 'map', $map);

        $item->webtitle = $request->webtitle;
        $item->webdescription = $request->webdescription;
        $item->webkeys = $request->webkeys;
        $item->logo = $logo;
        $item->brand_font_primary = trim((string) $request->input('brand_font_primary', '')) ?: null;
        $item->brand_font_secondary = trim((string) $request->input('brand_font_secondary', '')) ?: null;
        $item->favicon = $favicon;
        $item->map = $map;

        $item->save();

        Cache::forget('settings');

        session()->flash('success',  __('app.success'));

        return redirect('cms/'.$this->route.'/'.$item->id.'/edit');
    }

}
