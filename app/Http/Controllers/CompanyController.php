<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cache;

class CompanyController extends Controller
{
    private $model, $route, $title;

    public function __construct()
    {
        $this->model = 'App\Models\Company';
        $this->route = 'company';
        $this->title = 'app.company';
    }
    
    public function edit($id)
    {
        return view('cms.'.$this->route.'.form', ['item' => $this->model::findOrFail($id), 'editing' => true, 'route' => $this->route, 'title' => __($this->title)]);
    }

    public function update(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);    

        $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'street' => ['nullable', 'string', 'max:191'],
            'plz' => ['nullable', 'string', 'max:191'],
            'ort' => ['nullable', 'string', 'max:191'],
            'telefon' => ['nullable', 'string', 'max:191'],
            'email' => ['required', 'email', 'max:191'],
            'facebook' => ['nullable', 'string', 'max:191'],
            'instagram' => ['nullable', 'string', 'max:191'],
        ]);

        $item->name = $request->name;
        $item->description = $request->description;
        $item->street = $request->street;
        $item->plz = $request->plz;
        $item->ort = $request->ort;
        $item->telefon = $request->telefon;
        $item->email = $request->email;
        $item->facebook = $request->facebook;
        $item->instagram = $request->instagram;

        $item->save();

        Cache::forget('company');

        session()->flash('success',  __('app.success'));

        return redirect('cms/'.$this->route.'/'.$item->id.'/edit');
    }
}
