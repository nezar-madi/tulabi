<footer class="rq-footer">
    <div class="rq-wrap rq-footer-main">
        <div>
            <h2>{{ $site['name'] }}</h2>
            <p>{{ $site['specialty'] }} · {{ $site['city'] }}</p>
        </div>
        <div>
            <h2>{{ __('site.nav.menu') }}</h2>
            <ul>
                <li><a href="{{ $site['url'] }}#classes">{{ __('site.nav.classes') }}</a></li>
                @if ($site['about'])
                    <li><a href="{{ $site['url'] }}#about">{{ __('site.nav.about') }}</a></li>
                @endif
                <li><a href="{{ $site['url'] }}#contact">{{ __('site.nav.contact') }}</a></li>
            </ul>
        </div>
        <div>
            <h2>{{ __('site.nav.contact') }}</h2>
            <ul>
                @if ($site['whatsapp_url'])
                    <li><a href="{{ $site['whatsapp_url'] }}" target="_blank" rel="noopener" dir="ltr">{{ $site['whatsapp_number'] }}</a></li>
                @endif
                <li>{{ $site['city'] }}</li>
                @foreach ($site['links'] as $link)
                    <li><a href="{{ $link['url'] }}" target="_blank" rel="noopener nofollow">{{ __('site.links.'.$link['kind']) }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="rq-footer-bar">
        <div class="rq-wrap">
            <span>© {{ now()->year }} {{ $site['name'] }}. {{ __('site.footer.rights') }}</span>
            <a class="rq-powered" href="{{ route('home') }}">
                <span>{{ __('site.footer.powered_by') }}</span>
                <span class="tl-powered-mark"><b>ط</b>طلابي</span>
            </a>
        </div>
    </div>
</footer>
