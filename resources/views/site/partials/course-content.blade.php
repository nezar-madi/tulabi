{{-- A class's or a program's content: the video, the description and the three bullet lists.
     Everything is plain text escaped by Blade; the video is rebuilt by App\Support\VideoEmbed. --}}
@if (! empty($item['video_embed_url']))
    <div class="tl-video">
        <iframe
            src="{{ $item['video_embed_url'] }}"
            title="{{ $item['title'] ?? $item['name'] ?? '' }}"
            loading="lazy"
            allow="encrypted-media; picture-in-picture; fullscreen"
            allowfullscreen
            referrerpolicy="strict-origin-when-cross-origin"
        ></iframe>
    </div>
@endif

<h2 class="rq-h3">{{ $aboutTitle }}</h2>
<p class="tl-plain">{{ $item['description'] }}</p>

@foreach (['learn', 'requirements', 'audience'] as $list)
    @if (! empty($item[$list]))
        <h3 class="rq-h3 tl-list-title">{{ __('site.content.'.$list) }}</h3>
        <ul class="tl-list {{ $list === 'learn' ? 'tl-list--learn' : '' }}">
            @foreach ($item[$list] as $point)
                <li>{{ $point }}</li>
            @endforeach
        </ul>
    @endif
@endforeach
