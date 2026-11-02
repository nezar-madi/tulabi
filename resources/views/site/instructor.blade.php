@extends('site.layout')

@section('content')
    <section class="rq-hero">
        <div class="rq-wrap rq-hero-grid">
            <div class="rq-hero-copy">
                <p class="rq-eyebrow">{{ $site['specialty'] }} · {{ $site['city'] }}</p>
                <h1 class="rq-h1">{{ $site['name'] }}</h1>
                @if ($lead)
                    <p class="rq-lead">{{ $lead }}</p>
                @endif
                <div class="rq-hero-actions">
                    <a class="rq-btn rq-btn--primary" href="#classes">
                        {{ __('site.home.browse_classes') }}
                        @include('site.partials.icon', ['name' => 'arrow', 'size' => 18])
                    </a>
                    @if ($site['whatsapp_url'])
                        <a class="rq-link" href="{{ $site['whatsapp_url'] }}" target="_blank" rel="noopener">{{ __('site.home.contact') }} ←</a>
                    @endif
                </div>
                <div class="rq-hero-facts">
                    <span><strong>{{ count($courses) }}</strong> {{ __('site.home.stats_classes') }}</span>
                    <span class="rq-dot">•</span>
                    <span>{{ $site['city'] }}</span>
                </div>
            </div>

            <div class="rq-hero-visual">
                <div class="rq-plate" aria-hidden="true"></div>
                @if (count($courses) > 0)
                    <div class="rq-next">
                        <p class="rq-next-title">{{ __('site.home.next_title') }}</p>
                        <ol>
                            @foreach (array_slice($courses, 0, 3) as $course)
                                <li>
                                    <a href="{{ $course['url'] }}">
                                        <span class="rq-next-date"><b>{{ $course['starts_on']->day }}</b><span>{{ \App\Support\ClassText::monthName($course['starts_on']) }}</span></span>
                                        <span>
                                            <span class="rq-next-name">{{ $course['title'] }}</span><br>
                                            <span class="rq-next-meta">{{ collect([$course['schedule'], $course['time']])->filter()->implode(' · ') ?: $course['type_label'] }}</span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @elseif ($site['photo_url'])
                    <div class="rq-hero-frame">
                        <img src="{{ $site['photo_url'] }}" alt="{{ $site['name'] }}">
                    </div>
                @else
                    <div class="rq-hero-placeholder">
                        <span class="tl-placeholder-mark">{{ mb_substr($site['name'], 0, 1) }}</span>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="rq-section rq-section--white" id="classes">
        <div class="rq-wrap">
            <div class="rq-section-head">
                <div>
                    <p class="rq-eyebrow rq-eyebrow--plain">{{ __('site.home.classes_eyebrow') }}</p>
                    <h2 class="rq-h2">{{ __('site.home.classes_title') }}</h2>
                    <p class="rq-lead">{{ __('site.home.classes_intro') }}</p>
                </div>
            </div>
            @if (count($courses) > 0)
                <div class="rq-grid">
                    @foreach ($courses as $course)
                        @include('site.partials.class-card', ['course' => $course])
                    @endforeach
                </div>
            @else
                <p class="rq-empty">{{ __('site.classes.empty') }}</p>
            @endif
        </div>
    </section>

    @if ($site['about'])
        <section class="rq-section rq-section--tint" id="about">
            <div class="rq-wrap rq-about">
                <div class="rq-block">
                    <p class="rq-eyebrow rq-eyebrow--plain">{{ __('site.home.about_eyebrow') }}</p>
                    <h2 class="rq-h2">{{ __('site.home.about_title') }}</h2>
                    @if ($site['photo_url'])
                        <img class="tl-about-photo" src="{{ $site['photo_url'] }}" alt="{{ $site['name'] }}">
                    @endif
                </div>
                <p class="rq-about-text">{{ $site['about'] }}</p>
            </div>
        </section>
    @endif

    @include('site.partials.contact')
@endsection
