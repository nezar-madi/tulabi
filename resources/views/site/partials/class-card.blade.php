<article class="rq-card">
    <a class="rq-card-media" href="{{ $course['url'] }}" tabindex="-1" aria-hidden="true">
        <span class="rq-card-placeholder">@include('site.partials.icon', ['name' => $course['type'] === 'online' ? 'video' : 'layers', 'size' => 44])</span>
        <span class="rq-chip rq-chip--status {{ $course['is_open'] ? 'rq-chip--active' : '' }}">{{ $course['status_label'] }}</span>
    </a>
    <div class="rq-card-body">
        <p class="rq-card-kicker">{{ $course['type_label'] }}@if ($course['location']) · {{ $course['location'] }}@endif</p>
        <h3 class="rq-h3 rq-card-title"><a href="{{ $course['url'] }}">{{ $course['title'] }}</a></h3>
        <ul class="rq-meta">
            <li>@include('site.partials.icon', ['name' => 'calendar', 'size' => 16])<span>{{ __('site.classes.starts') }} {{ $course['starting_date'] }}</span></li>
            @if ($course['schedule'])
                <li>@include('site.partials.icon', ['name' => 'repeat', 'size' => 16])<span>{{ $course['schedule'] }}</span></li>
            @endif
            @if ($course['time'])
                <li>@include('site.partials.icon', ['name' => 'clock', 'size' => 16])<span>{{ $course['time'] }}</span></li>
            @endif
        </ul>
        <div class="rq-card-foot">
            <div>
                <div class="rq-price {{ $course['is_free'] ? 'rq-price--free' : '' }}">{{ $course['price'] }}</div>
                @if ($course['seats'])
                    <div class="rq-seats">{{ __('site.classes.seats_count', ['count' => $course['seats']]) }}</div>
                @endif
            </div>
            <div class="rq-card-actions">
                <a class="rq-btn rq-btn--primary rq-btn--sm" href="{{ $course['url'] }}">{{ __('site.classes.view') }}</a>
            </div>
        </div>
    </div>
</article>
