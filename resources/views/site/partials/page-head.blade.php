<section class="rq-page-head">
    <div class="rq-wrap">
        @if (! empty($eyebrow))
            <p class="rq-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="rq-h1">{{ $title }}</h1>
        @if (! empty($lead))
            <p class="rq-lead">{{ $lead }}</p>
        @endif
    </div>
</section>
