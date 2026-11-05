@extends('site.layout', [
    'ownerBar' => $ownerBar ?? null,
    'ownerEditUrl' => $ownerEditUrl ?? null,
])

@section('content')
    @include('site.partials.page-head', [
        'title' => $program['name'],
        'eyebrow' => __('site.programs.program'),
    ])

    <section class="rq-section">
        <div class="rq-wrap rq-detail">
            <div class="rq-block">
                @if ($program['poster_url'])
                    <div class="rq-detail-media"><img src="{{ $program['poster_url'] }}" alt="{{ $program['name'] }}"></div>
                @endif
                @include('site.partials.course-content', ['item' => $program, 'aboutTitle' => __('site.programs.about_program')])
            </div>

            <aside class="rq-panel rq-panel--sticky">
                <dl class="rq-facts">
                    <div><dt>{{ __('site.classes.instructor') }}</dt><dd><a class="rq-link" href="{{ $site['url'] }}">{{ $site['name'] }}</a></dd></div>
                    <div><dt>{{ __('site.nav.classes') }}</dt><dd>{{ trans_choice('site.programs.upcoming_runs', count($runs), ['count' => count($runs)]) }}</dd></div>
                </dl>
                @if (count($runs) > 0)
                    <a class="rq-btn rq-btn--primary rq-btn--block" href="#runs">{{ __('site.programs.runs_title') }}</a>
                @elseif ($site['whatsapp_url'])
                    <a class="rq-btn rq-btn--whatsapp rq-btn--block" href="{{ $site['whatsapp_url'] }}" target="_blank" rel="noopener">
                        @include('site.partials.icon', ['name' => 'whatsapp', 'size' => 20])
                        {{ __('site.classes.ask_whatsapp') }}
                    </a>
                @endif
            </aside>
        </div>
    </section>

    <section class="rq-section rq-section--white" id="runs">
        <div class="rq-wrap">
            <div class="rq-section-head">
                <div><h2 class="rq-h2">{{ __('site.programs.runs_title') }}</h2></div>
            </div>
            @if (count($runs) > 0)
                <div class="rq-grid">
                    @foreach ($runs as $course)
                        @include('site.partials.class-card', ['course' => $course])
                    @endforeach
                </div>
            @else
                <p class="rq-empty">{{ __('site.programs.no_runs') }}</p>
            @endif
        </div>
    </section>
@endsection
