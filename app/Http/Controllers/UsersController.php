<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Auth;

class UsersController extends Controller
{

    private $model, $route, $title, $allowedLangs;

    public function __construct()
    {
        $this->allowedLangs = ['de', 'fr', 'it'];
        $this->model = 'App\Models\User';
        $this->route = 'admins';
        $this->title = 'CMS Admins';
    }

    public function index()
    {
        return view('cms.'.$this->route.'.list', ['route' => $this->route, 'title' => $this->title]);
    }

    public function ajax(Request $request)
    {       

        $data = [];

        $columns = array( 
            0 =>'id',
            1 =>'username',
            2 =>'action'
        );

        $sortable = [0, 1];

        $sqlRec = $this->model::query();

        $search = $request['search']['value'];
        if(!empty($search) && !is_null($search) && is_string($search) && $search!="") {
            $sqlRec->where(function($q) use ($search) {
                $q->whereRaw("(name like ? or email like ? or username like ?)", ["%$search%", "%$search%", "%$search%"]);
            });
        }

        $sqlRec->where('id', '!=', '1');
        
        $totalRecords = $sqlRec->count();

        $order = $request['order'];
        if(!empty($order) && !is_null($order) && is_array($order))
            foreach($order as $key => $value) {
                if(in_array($value['column'], $sortable) && ($value['dir'] == "asc" || $value['dir'] == "desc"))
                    $sqlRec->orderBy($columns[$value['column']], $value['dir']);
            }
        
        $length = (intval($request['length']) > 0) ? intval($request['length']) : 10;
        $start = intval($request['start']);
        $rows = $sqlRec->take($length)->skip($start)->get();
        
        foreach($rows as $row) {
            $data[] = [
                '0' => $row->id,
                '1' => $row->username,
                '2' => '<a href="'.url('cms/'.$this->route.'/'.$row->id.'/edit').'" class="btn_action"><img src="'.asset('cmsfiles/images/btn_edit.jpg').'" alt="bt_edit">
                        </a>     
                        <a href="#" class="btn_action confirmation" data-id="'.$row->id.'">
                            <img src="'.asset('cmsfiles/images/btn_delete.jpg').'" alt="btn_delete">
                            <form id="delete-form'.$row->id.'" action="'.url('cms/'.$this->route.'/'.$row->id).'" method="POST" style="display: none;">'.csrf_field().'<input type="hidden" name="_method" value="delete" /></form>
                        </a>
                        <a href="#" class="btn_action status" id="status-'.$row->id.'" data-id="'.$row->id.'">   
                            <img class="img-'.$row->id.'" src="'.asset('cmsfiles/images/btn_'.($row->is_active ? 'on' : 'off').'.jpg').'" alt="bt_on-of">
                        </a>',
            ];
        }
        
        $json_data = array(
                    "draw"            => intval($request['draw']),  
                    "recordsTotal"    => intval($totalRecords),  
                    "recordsFiltered" => intval($totalRecords), 
                    "data"            => $data   
                    );
            
        return json_encode($json_data);
    }

    public function create()
    {
        return view('cms.'.$this->route.'.form', ['item' => new $this->model(), 'editing' => false, 'route' => $this->route, 'title' => $this->title]);
    }

    public function store(Request $request)
    {
        $allLangs = implode(",", $this->allowedLangs);

        $validateArray = [
            'name' => ['required', 'string', 'max:191'],
            'username' => ['required', 'string', 'max:191', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cmsLang' => ['required', 'string', 'in:'.$allLangs],
            'is_active' => ['required', 'string', 'in:0,1'],
            'notification' => ['required', 'string', 'in:0,1'],  
        ];

        foreach(config('modules') as $mods){
            $validateArray[$mods[0]] = ['nullable', 'string', 'in:1'];
        }

        $request->validate($validateArray);

        $item = new $this->model;

        $item->name = $request->name;
        if(!is_null($request->password)) $item->password = Hash::make($request->password);
        $item->username = $request->username;
        $item->email = $request->email;
        $item->is_active = $request->is_active;
        $item->notification = $request->notification;
        $item->language = $request->cmsLang;
        foreach(config('modules') as $mods){
            $item[$mods[0]] = $request[$mods[0]] ? 1 : 0;
        }

        $item->save();

        session()->flash('success', __('app.success'));

        return redirect('cms/'.$this->route);
    }

    public function edit($id)
    {
        return view('cms.'.$this->route.'.form', ['item' => $this->model::findOrFail($id), 'editing' => true, 'route' => $this->route, 'title' => $this->title]);

    }
    
    public function update(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);
        if($item->id == 1)
            return redirect('cms/'.$this->route);

        $allLangs = implode(",", $this->allowedLangs);

        $validateArray = [
            'name' => ['required', 'string', 'max:191'],
            'username' => ['required', 'string', 'max:191', 'unique:users,username,'.$item->id],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email,'.$item->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'cmsLang' => ['required', 'string', 'in:'.$allLangs],
            'is_active' => ['required', 'string', 'in:0,1'],
            'notification' => ['required', 'string', 'in:0,1'],  
        ];

        foreach(config('modules') as $mods){
            $validateArray[$mods[0]] = ['nullable', 'string', 'in:1'];
        }

        $request->validate($validateArray);
        
        $item->name = $request->name;
        if(!is_null($request->password)) $item->password = Hash::make($request->password);
        $item->username = $request->username;
        $item->email = $request->email;
        $item->is_active = $request->is_active;
        $item->notification = $request->notification;
        $item->language = $request->cmsLang;
        foreach(config('modules') as $mods){
            $item[$mods[0]] = $request[$mods[0]] ? 1 : 0;
        }
        $item->save();

        session()->flash('success',  __('app.success'));

        return redirect('cms/'.$this->route);
    }

    public function destroy($id)
    {
        $item = $this->model::findOrFail($id);

        if($item->id == 1)
            return redirect('cms/'.$this->route);
            
        $item->delete();

        session()->flash('success',  __('app.success'));
        
        return redirect('cms/'.$this->route);
    }

    public function userlang($lang)
    {
        if(in_array($lang, $this->allowedLangs)) {
            Auth::user()->language = $lang;
            Auth::user()->save();
            return redirect()->back();
        } else {
            return redirect()->back();
        }
    }

    public function status($id)
    {
        $item = $this->model::findOrFail($id);

        if($item->id!=1 && !is_null($item->is_active)) {
            $item->is_active = !$item->is_active;
            $item->save();
            $change = true;
        } else {
            $change = false;
        }

        return response()->json(array($item->is_active ?? 0, $change));    
    }
}
