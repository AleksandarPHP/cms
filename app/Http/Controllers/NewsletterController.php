<?php

namespace App\Http\Controllers;

use App\Http\Requests\Newsletter\AiCopyRequest;
use App\Http\Requests\Newsletter\ContentRequest;
use App\Http\Requests\Newsletter\PublishRequest;
use App\Http\Requests\Newsletter\SelectBlogsRequest;
use App\Models\Blog;
use App\Models\Newsletter;
use App\Services\NewsletterAiService;
use App\Services\NewsletterService;
use App\Services\NewsletterTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class NewsletterController extends Controller
{
    public function __construct(
        private NewsletterService $newsletters,
        private NewsletterTemplateService $template,
        private NewsletterAiService $ai,
    ) {}

    public function index(): View
    {
        return view('cms.newsletter.list', [
            'route' => 'newsletter',
            'title' => __('app.newsletter'),
        ]);
    }

    public function ajax(Request $request): string
    {
        $columns = [
            0 => 'id',
            1 => 'title',
            2 => 'status',
        ];

        $query = Newsletter::query();
        $search = $request['search']['value'] ?? null;

        if (is_string($search) && $search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('title', 'like', '%'.$search.'%')
                    ->orWhere('issue_number', 'like', '%'.$search.'%');
            });
        }

        $total = $query->count();

        foreach ((array) ($request['order'] ?? []) as $order) {
            $column = (int) ($order['column'] ?? -1);
            $direction = $order['dir'] ?? '';

            if (isset($columns[$column]) && in_array($direction, ['asc', 'desc'], true)) {
                $query->orderBy($columns[$column], $direction);
            }
        }

        $length = (int) $request['length'] > 0 ? (int) $request['length'] : 10;
        $rows = $query->skip((int) $request['start'])->take($length)->get();
        $data = [];

        foreach ($rows as $row) {
            $label = $row->title ?: ('Nr. '.$row->issue_number);
            $data[] = [
                '0' => $row->id,
                '1' => e($label),
                '2' => e(__('app.newsletter_status_'.$row->status)),
                '3' => '<a href="'.e($row->wizardUrl()).'" class="btn_action"><img src="'.asset('cmsfiles/images/btn_edit.jpg').'" alt="edit"></a>
                    <a href="#" class="btn_action confirmation" data-id="'.$row->id.'">
                        <img src="'.asset('cmsfiles/images/btn_delete.jpg').'" alt="delete">
                        <form id="delete-form'.$row->id.'" action="'.route('newsletter.destroy', $row).'" method="POST" style="display: none;">'.csrf_field().'<input type="hidden" name="_method" value="delete"></form>
                    </a>',
            ];
        }

        return json_encode([
            'draw' => (int) $request['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $data,
        ]);
    }

    public function create(Request $request): RedirectResponse
    {
        $newsletter = $this->newsletters->start($request->user());

        return redirect()->route('newsletter.blogs', $newsletter);
    }

    public function blogs(Newsletter $newsletter): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfPublished($newsletter)) {
            return $redirect;
        }

        $selected = $newsletter->blogs()->pluck('blogs.id')->map(fn ($id) => (int) $id)->all();
        $blogs = Blog::query()
            ->where('is_active', true)
            ->when($selected !== [], fn ($query) => $query->orWhereIn('id', $selected))
            ->orderBy('order')
            ->orderByDesc('id')
            ->get();

        return view('cms.newsletter.blogs', [
            'newsletter' => $newsletter->loadCount('blogs'),
            'blogs' => $blogs,
            'selected' => $selected,
            'route' => 'newsletter',
            'title' => __('app.newsletter'),
        ]);
    }

    public function storeBlogs(SelectBlogsRequest $request, Newsletter $newsletter): RedirectResponse
    {
        $this->newsletters->saveBlogs($newsletter, $request->validated('blog_ids'));

        return redirect()->route('newsletter.content', $newsletter);
    }

    public function content(Newsletter $newsletter): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfPublished($newsletter)) {
            return $redirect;
        }

        if (! $newsletter->blogs()->exists()) {
            return redirect()
                ->route('newsletter.blogs', $newsletter)
                ->withErrors(['blog_ids' => __('app.newsletter_need_blogs')]);
        }

        return view('cms.newsletter.content', [
            'newsletter' => $newsletter->loadCount('blogs'),
            'route' => 'newsletter',
            'title' => __('app.newsletter'),
        ]);
    }

    public function storeContent(ContentRequest $request, Newsletter $newsletter): RedirectResponse
    {
        $this->newsletters->saveContent(
            $newsletter,
            $request->validated('title'),
            $request->validated('text'),
        );

        return redirect()->route('newsletter.review', $newsletter);
    }

    public function ai(AiCopyRequest $request, Newsletter $newsletter): JsonResponse
    {
        if ($newsletter->isPublished()) {
            return response()->json(['message' => __('app.newsletter_locked')], 422);
        }

        try {
            $copy = $this->ai->generate($newsletter, (string) $request->validated('instruction'));
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($copy);
    }

    public function review(Newsletter $newsletter): View|RedirectResponse
    {
        if (! $newsletter->blogs()->exists()) {
            return redirect()
                ->route('newsletter.blogs', $newsletter)
                ->withErrors(['blog_ids' => __('app.newsletter_need_blogs')]);
        }

        if (trim((string) $newsletter->title) === '' || trim((string) $newsletter->text) === '') {
            return redirect()
                ->route('newsletter.content', $newsletter)
                ->withErrors(['title' => __('app.newsletter_need_content')]);
        }

        return view('cms.newsletter.review', [
            'newsletter' => $newsletter->loadCount('blogs'),
            'route' => 'newsletter',
            'title' => __('app.newsletter'),
        ]);
    }

    public function preview(Newsletter $newsletter): View
    {
        return view('newsletter.email', $this->template->present(
            $newsletter,
            route('newsletter.preview', $newsletter),
        ));
    }

    public function publishForm(Newsletter $newsletter): View|RedirectResponse
    {
        if ($redirect = $this->redirectIfPublished($newsletter)) {
            return $redirect;
        }

        if ($redirect = $this->redirectUnlessReady($newsletter)) {
            return $redirect;
        }

        return view('cms.newsletter.publish', [
            'newsletter' => $newsletter->loadCount('blogs'),
            'route' => 'newsletter',
            'title' => __('app.newsletter'),
            'scheduledLocal' => $newsletter->scheduled_at
                ? $newsletter->scheduled_at->timezone(Newsletter::TIMEZONE)->format('Y-m-d\TH:i')
                : '',
        ]);
    }

    public function publish(PublishRequest $request, Newsletter $newsletter): RedirectResponse
    {
        if ($request->validated('mode') === 'now') {
            $this->newsletters->publishNow($newsletter);
            session()->flash('success', __('app.newsletter_published'));

            return redirect()->route('newsletter.review', $newsletter);
        }

        $this->newsletters->schedule($newsletter, $request->validated('scheduled_at'));
        session()->flash('success', __('app.newsletter_scheduled'));

        return redirect()->route('newsletter.review', $newsletter);
    }

    public function publicShow(Newsletter $newsletter): View
    {
        abort_unless($newsletter->isPublished(), 404);

        return view('newsletter.email', $this->template->present(
            $newsletter,
            route('newsletter.public', $newsletter),
        ));
    }

    public function destroy(Newsletter $newsletter): RedirectResponse
    {
        $newsletter->delete();
        session()->flash('success', __('app.success'));

        return redirect()->route('newsletter.index');
    }

    private function redirectIfPublished(Newsletter $newsletter): ?RedirectResponse
    {
        if (! $newsletter->isPublished()) {
            return null;
        }

        return redirect()->route('newsletter.review', $newsletter);
    }

    private function redirectUnlessReady(Newsletter $newsletter): ?RedirectResponse
    {
        if (! $newsletter->blogs()->exists()) {
            return redirect()
                ->route('newsletter.blogs', $newsletter)
                ->withErrors(['blog_ids' => __('app.newsletter_need_blogs')]);
        }

        if (trim((string) $newsletter->title) === '' || trim((string) $newsletter->text) === '') {
            return redirect()
                ->route('newsletter.content', $newsletter)
                ->withErrors(['title' => __('app.newsletter_need_content')]);
        }

        return null;
    }
}
