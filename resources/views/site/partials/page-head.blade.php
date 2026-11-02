<section class="rq-page-head">
    <div class="rq-wrap">
        <ol class="rq-crumbs" aria-label="breadcrumb">
            <li><a href="{{ $site['url'] }}">{{ $site['name'] }}</a></li>
            @foreach ($crumbs ?? [] as $crumb)
                <li>
                    @if (! empty($crumb['url']))
                        <a href="{{ $crumb['url'] }}">{{ $crumb['label'] }}</a>
                    @else
                        <span aria-current="page">{{ $crumb['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
        @if (! empty($eyebrow))
            <p class="rq-eyebrow">{{ $eyebrow }}</p>
        @endif
        <h1 class="rq-h1">{{ $title }}</h1>
        @if (! empty($lead))
            <p class="rq-lead">{{ $lead }}</p>
        @endif
    </div>
</section>
