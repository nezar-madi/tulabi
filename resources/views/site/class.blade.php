@extends('site.layout', [
    'ownerBar' => $course['status'] === 'draft' ? __('site.draft_bar') : __('site.owner_bar'),
    'ownerEditUrl' => route('teach.classes.edit', $course['id']),
])

@section('content')
    @include('site.partials.page-head', [
        'title' => $course['title'],
        'eyebrow' => $course['status_label'],
        'crumbs' => [
            ['label' => __('site.nav.classes'), 'url' => $site['url'].'#classes'],
            ['label' => $course['title']],
        ],
    ])

    <section class="rq-section">
        <div class="rq-wrap rq-detail">
            <div class="rq-block">
                @if ($course['image_url'])
                    <img class="tl-class-image" src="{{ $course['image_url'] }}" alt="{{ $course['title'] }}">
                @endif
                @include('site.partials.course-content', ['item' => $course, 'aboutTitle' => __('site.classes.about_class')])
            </div>

            <aside class="rq-panel rq-panel--sticky" id="request">
                <div class="rq-price {{ $course['is_free'] ? 'rq-price--free' : '' }}">{{ $course['price'] }}</div>
                <dl class="rq-facts">
                    <div><dt>{{ __('site.classes.instructor') }}</dt><dd><a class="rq-link" href="{{ $site['url'] }}">{{ $site['name'] }}</a></dd></div>
                    @if ($course['program'])
                        <div><dt>{{ __('site.classes.program') }}</dt><dd><a class="rq-link" href="{{ $course['program']['url'] }}">{{ $course['program']['name'] }}</a></dd></div>
                    @endif
                    <div><dt>{{ __('site.classes.starts') }}</dt><dd>{{ $course['starting_date'] }}</dd></div>
                    @if ($course['ending_date'])
                        <div><dt>{{ __('site.classes.ends') }}</dt><dd>{{ $course['ending_date'] }}</dd></div>
                    @endif
                    @if ($course['schedule'])
                        <div><dt>{{ __('site.classes.schedule') }}</dt><dd>{{ $course['schedule'] }}</dd></div>
                    @endif
                    @if ($course['time'])
                        <div><dt>{{ __('site.classes.time') }}</dt><dd>{{ $course['time'] }}</dd></div>
                    @endif
                    <div>
                        <dt>{{ __('site.classes.place') }}</dt>
                        <dd>
                            @if ($course['type'] === 'in_person')
                                @if ($course['location']){{ $course['location'] }}<br>@endif
                                @if ($course['map_url'])
                                    <a class="rq-link" href="{{ $course['map_url'] }}" target="_blank" rel="noopener noreferrer">{{ __('site.classes.map') }}</a>
                                @endif
                            @else
                                {{ $course['type_label'] }} · {{ __('site.classes.online_note') }}
                            @endif
                        </dd>
                    </div>
                    @if ($course['seats'])
                        <div><dt>{{ __('site.classes.seats') }}</dt><dd>{{ __('site.classes.seats_count', ['count' => $course['seats']]) }}</dd></div>
                    @endif
                </dl>

                @if (session('status'))
                    <p class="tl-alert tl-alert--success" role="status">{{ session('status') }}</p>
                @endif
                @if ($errors->has('enrollment'))
                    <p class="tl-alert tl-alert--error" role="alert">{{ $errors->first('enrollment') }}</p>
                @endif

                @if ($site['is_owner'])
                    <p class="rq-note">{{ __('site.classes.owner') }}</p>
                @elseif ($course['enrollment'] && in_array($course['enrollment']['status'], ['requested', 'accepted'], true))
                    <p class="rq-note">{{ __('site.classes.your_status', ['status' => $course['enrollment']['status_label']]) }}</p>
                    <a class="rq-btn rq-btn--ghost rq-btn--block" href="{{ $course['enrollment']['url'] }}">{{ __('site.classes.see_my_classes') }}</a>
                @elseif ($course['ended'])
                    <p class="rq-note">{{ __('site.classes.ended') }}</p>
                @elseif (! $course['is_open'])
                    <p class="rq-note">{{ __('site.classes.closed') }}</p>
                @elseif (auth('web')->check())
                    @if ($course['enrollment'])
                        <p class="rq-note">{{ __('site.classes.your_status', ['status' => $course['enrollment']['status_label']]) }}</p>
                    @endif
                    <form class="tl-request" method="POST" action="{{ $course['request_url'] }}">
                        @csrf
                        <label class="rq-field">
                            <span>{{ __('site.classes.request_note') }}</span>
                            <textarea name="note" rows="3" maxlength="500" placeholder="{{ __('site.classes.request_note_hint') }}">{{ old('note') }}</textarea>
                        </label>
                        @error('note')
                            <p class="tl-alert tl-alert--error">{{ $message }}</p>
                        @enderror
                        <button type="submit" class="rq-btn rq-btn--primary rq-btn--block">
                            {{ $course['enrollment'] ? __('site.classes.request_again') : __('site.classes.request') }}
                        </button>
                    </form>
                @else
                    {{-- Signing in comes back here (JoinClassController::create). --}}
                    <a class="rq-btn rq-btn--primary rq-btn--block" href="{{ $course['request_login_url'] }}">{{ __('site.classes.request') }}</a>
                    <p class="rq-note">{{ __('site.classes.login_to_request') }}</p>
                @endif

                @if ($course['whatsapp_url'] && ! $site['is_owner'])
                    <a class="rq-btn rq-btn--whatsapp rq-btn--block" href="{{ $course['whatsapp_url'] }}" target="_blank" rel="noopener">
                        @include('site.partials.icon', ['name' => 'whatsapp', 'size' => 20])
                        {{ __('site.classes.ask_whatsapp') }}
                    </a>
                @endif
            </aside>
        </div>
    </section>

    @if (count($related) > 0)
        <section class="rq-section rq-section--white">
            <div class="rq-wrap">
                <div class="rq-section-head">
                    <div><h2 class="rq-h2">{{ __('site.classes.more') }}</h2></div>
                </div>
                <div class="rq-grid">
                    @foreach ($related as $item)
                        @include('site.partials.class-card', ['course' => $item])
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
