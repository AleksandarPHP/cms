<?php

namespace App\Http\Controllers;

use App\Http\Requests\Blog\AiImageRequest;
use App\Http\Requests\Blog\AiTextRequest;
use App\Services\BlogAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Helper;
use RuntimeException;

class BlogController extends Controller
{
    private $model, $route, $title;

    public function __construct(private BlogAiService $blogAi)
    {
        $this->model = 'App\Models\Blog';
        $this->route = 'blog';   
        $this->title = 'Blog';      
   
    }

    public function index()
    {
        return view('cms.'.$this->route.'.list', ['route' => $this->route, 'title' => __($this->title)]);
    }

    public function ajax(Request $request)
    {       

        $data = [];

        $columns = array( 
            0 =>'id',
            1 =>'title',
            2 =>'action'
        );

        $sortable = [0, 1];

        $sqlRec = $this->model::query();

        $search = $request['search']['value'];
        if(!empty($search) && !is_null($search) && is_string($search) && $search!="") {
            $sqlRec->where(function($q) use ($search) {
                $q->whereRaw("(title like ?)", ["%$search%"]);
            });
        }
        
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
                '1' => $row->title,
                '2' => '<a href="'.url('cms/'.$this->route.'/'.$row->id.'/edit').'" class="btn_action"><img src="'.asset('cmsfiles/images/btn_edit.jpg').'" alt="bt_edit">
                </a>     
                <a href="#" class="btn_action confirmation" data-id="'.$row->id.'">
                    <img src="'.asset('cmsfiles/images/btn_delete.jpg').'" alt="btn_delete">
                    <form id="delete-form'.$row->id.'" action="'.url('cms/'.$this->route.'/'.$row->id).'" method="POST" style="display: none;">'.csrf_field().'<input type="hidden" name="_method" value="delete" /></form>
                </a>
                <a href="#" class="btn_action status" id="status-'.$row->id.'" data-id="'.$row->id.'">   
                    <img src="'.asset('cmsfiles/images/btn_'.($row->is_active ? 'on' : 'off').'.jpg').'" alt="bt_on-of">
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
        return view('cms.'.$this->route.'.form', ['item' => new $this->model(), 'editing' => false, 'route' => $this->route, 'title' => __($this->title)]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'subtitle' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'mimes:jpeg,png,svg', 'image', 'max:5000', 'dimensions:min_width=171,min_height=171'],
            'image2' => ['nullable', 'mimes:jpeg,png,svg', 'image', 'max:5000', 'dimensions:min_width=1920'],
            'ai_image' => ['nullable', 'string', 'max:255'],
            'ai_image2' => ['nullable', 'string', 'max:255'],
        ]);    

        $image = $this->imageFromRequest($request, 'image', 'ai_image', null);
        $image2 = $this->imageFromRequest($request, 'image2', 'ai_image2', null);

        $item = $this->model::create([
            'title' => $request->title,
            'subtitle' => $request->subtitle,
            'description' => $request->description,
            'content' => $request->content,
            'image' => $image,
            'image2' => $image2,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
        ]);

        Cache::tags(['blog'])->flush();

        session()->flash('success', __('app.success'));

        return redirect('cms/'.$this->route);
    }
    
    public function edit($id)
    {
        return view('cms.'.$this->route.'.form', ['item' => $this->model::findOrFail($id), 'editing' => true, 'route' => $this->route, 'title' => __($this->title)]);
    }

    public function update(Request $request, $id)
    {
        $item = $this->model::findOrFail($id);
        $request->validate([
            'title' => ['required', 'string', 'max:191'],
            'subtitle' => ['required', 'string', 'max:191'],
            'description' => ['nullable', 'string'],
            'meta_title' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'image' => ['nullable', 'mimes:jpeg,png,svg', 'image', 'max:5000', 'dimensions:min_width=171,min_height=171'],
            'image2' => ['nullable', 'mimes:jpeg,png,svg', 'image', 'max:5000', 'dimensions:min_width=1920'],
            'ai_image' => ['nullable', 'string', 'max:255'],
            'ai_image2' => ['nullable', 'string', 'max:255'],
        ]);   
        $titleChanged = $item->title != $request->title;
        $image = $this->imageFromRequest($request, 'image', 'ai_image', $item->image, $titleChanged);
        $image2 = $this->imageFromRequest($request, 'image2', 'ai_image2', $item->image2, $titleChanged);

        $item->title = $request->title;
        $item->subtitle = $request->subtitle;
        $item->description = $request->description;
        $item->meta_title = $request->meta_title;
        $item->meta_description = $request->meta_description;
        $item->content = $request->content;
        $item->image = $image;
        $item->image2 = $image2;

        $item->save();

        Cache::tags(['blog'])->flush();

        session()->flash('success', __('app.success'));

        return redirect('cms/'.$this->route);
    }

    public function destroy($id)
    {
        $item = $this->model::findOrFail($id);
        $item->delete();

        Cache::tags(['blog'])->flush();

        session()->flash('success', __('app.success'));
        
        return redirect('cms/'.$this->route);
    }

    public function show($id, $slug)
    {
        $page = $this->model::query()
            ->where('id', $id)
            ->where('is_active', true)
            ->firstOrFail();

        $canonical = $page->slug ?: 'beitrag';

        if ((string) $slug !== $canonical) {
            return redirect()->route('blog.show', ['id' => $page->id, 'slug' => $canonical]);
        }

        $path = $page->image2 ?: $page->image;

        return view('blog-detail', [
            'item' => $page,
            'image' => $path ? Helper::image($path, 1200, 5000) : null,
        ]);
    }

    public function removeImage(Request $request, $id)
    {
        $checkArray = ['image', 'image2', 'image3'];

        $checkArray = implode(",", $checkArray);

        $request->validate([
            'image' => ['required', 'in:'.$checkArray],
        ]);

        $item = $this->model::findOrFail($id);

        if(Helper::deleteImage($item[$request->image])) {
            $item[$request->image] = null;
            $item->save();
        } else {
            return back()->withErrors(__('app.error'));
        }

        Cache::tags(['blog'])->flush();

        session()->flash('success', __('app.success'));
        return back();
    }
    
    public function status($id)
    {
        $item = $this->model::findOrFail($id);

        if(!is_null($item->is_active)) {
            $item->is_active = !$item->is_active;
            $item->save();
            $change = true;
            Cache::tags(['blog'])->flush();
        } else {
            $change = false;
        }

        return response()->json(array($item->is_active, $change));    
    }

    public function sortShow()
    {
        return view('cms.'.$this->route.'.sort', ['items' => $this->model::orderBy('order')->orderBy('id', 'DESC')->get(), 'route' => $this->route, 'title' => __($this->title)]);
    }

    public function sortSave(Request $request)
    {
        $sortarray = explode("|", $request->sortarray);
        $request->merge(['sortarray' => $sortarray]);

        $allowed = implode(",",$this->model::all()->pluck('id')->toArray());

        $request->validate([
            'sortarray' => ['required', 'array'],
            'sortarray.*' => ['required', 'integer', 'in:'.$allowed],
        ]);

        $br = 0;
        foreach ($sortarray as $id) {
            $this->model::where('id', $id)->update(['order' => $br++]);
        }

        session()->flash('success', __('app.success'));
        
        Cache::tags(['blog'])->flush();

        return redirect('cms/'.$this->route);
    }

    public function aiText(AiTextRequest $request): JsonResponse
    {
        try {
            $fields = $this->blogAi->refineText($request->validated());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['fields' => $fields]);
    }

    public function aiImage(AiImageRequest $request): JsonResponse
    {
        try {
            $image = $this->blogAi->generateImage($request->validated());
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($image);
    }

    private function imageFromRequest(Request $request, $fileKey, $aiKey, $current, $titleChanged = false)
    {
        if ($request->hasFile($fileKey)) {
            return Helper::saveImage($request->file($fileKey), $this->route, $request->title, $current);
        }

        $generated = $this->blogAi->usableGeneratedPath($request->input($aiKey));

        if ($generated !== null && $generated !== $current) {
            $placed = Helper::renameImage($generated, $this->route, $request->title) ?: $generated;

            if ($current && $current !== $placed) {
                Helper::deleteImage($current);
            }

            return $placed;
        }

        if ($titleChanged && $current) {
            return Helper::renameImage($current, $this->route, $request->title) ?: $current;
        }

        return $current;
    }
}
