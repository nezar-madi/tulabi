<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $meta['title'] }}</title>
    <meta name="description" content="{{ $meta['description'] }}">
    <link rel="canonical" href="{{ $meta['canonical'] }}">
    <meta name="theme-color" content="#0D4AA8">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="طلابي">
    <meta property="og:locale" content="ar_LY">
    <meta property="og:title" content="{{ $meta['title'] }}">
    <meta property="og:description" content="{{ $meta['description'] }}">
    <meta property="og:url" content="{{ $meta['canonical'] }}">
    @if (! empty($site['photo_url']))
        <meta property="og:image" content="{{ url($site['photo_url']) }}">
    @endif
    <meta name="twitter:card" content="summary">

    <link rel="preload" href="/fonts/site/07086eb9-b3d0-44f1-b3c5-9929c9360f78.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/fonts/site/537f491f-2e2b-4279-ad19-936241a91379.woff2" as="font" type="font/woff2" crossorigin>
    @include('site.partials.styles')
</head>
<body>
    <a class="rq-skip" href="#main">تخطَّ إلى المحتوى</a>

    @if ($site['is_owner'])
        <div class="tl-owner-bar">
            <span>{{ $ownerBar ?? __('site.owner_bar') }}</span>
            <a href="{{ $ownerEditUrl ?? route('teach.profile.edit') }}">{{ __('site.nav.edit_page') }}</a>
        </div>
    @endif

    @include('site.partials.header')

    <main id="main">
        @yield('content')
    </main>

    @include('site.partials.footer')

    @if (! empty($site['whatsapp_url']))
        <a class="rq-floating-whatsapp" href="{{ $site['whatsapp_url'] }}" target="_blank" rel="noopener" aria-label="{{ __('site.classes.ask_whatsapp') }}">
            @include('site.partials.icon', ['name' => 'whatsapp', 'size' => 28])
        </a>
    @endif
</body>
</html>
