@php
    $steps = [
        1 => ['label' => __('app.newsletter_step_blogs'), 'url' => route('newsletter.blogs', $newsletter), 'enabled' => $newsletter->status !== 'published'],
        2 => ['label' => __('app.newsletter_step_content'), 'url' => route('newsletter.content', $newsletter), 'enabled' => $newsletter->blogs_count > 0 && $newsletter->status !== 'published'],
        3 => ['label' => __('app.newsletter_step_review'), 'url' => route('newsletter.review', $newsletter), 'enabled' => $newsletter->blogs_count > 0 && $newsletter->title && $newsletter->text],
        4 => ['label' => __('app.newsletter_step_publish'), 'url' => route('newsletter.publish', $newsletter), 'enabled' => $newsletter->blogs_count > 0 && $newsletter->title && $newsletter->text && $newsletter->status !== 'published'],
    ];
@endphp
<ol class="nl-steps">
    @foreach($steps as $number => $step)
    <li class="{{ (int) $current === $number ? 'is-current' : '' }}">
        @if($step['enabled'] && (int) $current !== $number)
        <a href="{{ $step['url'] }}">{{ $number }}. {{ $step['label'] }}</a>
        @else
        <span>{{ $number }}. {{ $step['label'] }}</span>
        @endif
    </li>
    @endforeach
</ol>
<style>
    .nl-steps { display: flex; gap: 8px; list-style: none; padding: 0 15px; margin: 0 0 16px; }
    .nl-steps li { flex: 1; text-align: center; background: #f2f2f2; }
    .nl-steps a, .nl-steps span { display: block; padding: 10px 8px; color: inherit; text-decoration: none; }
    .nl-steps .is-current { background: #222; color: #fff; }
</style>
