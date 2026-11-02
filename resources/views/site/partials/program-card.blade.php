<article class="rq-card">
    <a class="rq-card-media" href="{{ $program['url'] }}" tabindex="-1" aria-hidden="true">
        @if ($program['poster_url'])
            <img src="{{ $program['poster_url'] }}" alt="" loading="lazy">
        @else
            <span class="rq-card-placeholder">@include('site.partials.icon', ['name' => 'layers', 'size' => 44])</span>
        @endif
        @if ($program['is_featured'])
            <span class="rq-chip rq-chip--featured">{{ __('site.programs.featured') }}</span>
        @endif
    </a>
    <div class="rq-card-body">
        <h3 class="rq-h3 rq-card-title"><a href="{{ $program['url'] }}">{{ $program['name'] }}</a></h3>
        @if ($program['summary'])
            <p class="rq-card-text">{{ $program['summary'] }}</p>
        @endif
        <div class="rq-card-foot">
            <span class="rq-note">{{ trans_choice('site.programs.upcoming_runs', $program['upcoming_count'], ['count' => $program['upcoming_count']]) }}</span>
            <a class="rq-link" href="{{ $program['url'] }}">{{ __('site.programs.view') }}</a>
        </div>
    </div>
</article>
