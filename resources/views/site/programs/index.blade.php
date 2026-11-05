@extends('site.layout')

@section('content')
    @include('site.partials.page-head', [
        'title' => __('site.programs.title'),
        'lead' => __('site.programs.intro'),
    ])

    <section class="rq-section">
        <div class="rq-wrap">
            @if (count($programs) > 0)
                <div class="rq-grid">
                    @foreach ($programs as $program)
                        @include('site.partials.program-card', ['program' => $program])
                    @endforeach
                </div>
            @else
                <p class="rq-empty">{{ __('site.programs.empty') }}</p>
            @endif
        </div>
    </section>
@endsection
