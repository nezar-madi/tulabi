@php
    $home = $site['url'];
    $user = auth('web')->user();
    $navItems = [
        ['url' => $home, 'label' => __('site.nav.home'), 'active' => request()->routeIs('instructors.show')],
        ['url' => $home.'#classes', 'label' => __('site.nav.classes'), 'active' => request()->routeIs('classes.show')],
        ['url' => $home.'#about', 'label' => __('site.nav.about'), 'active' => false, 'show' => (bool) $site['about']],
        ['url' => $home.'#contact', 'label' => __('site.nav.contact'), 'active' => false],
    ];
    $navItems = array_filter($navItems, fn ($item) => $item['show'] ?? true);
    $account = $user
        ? ['url' => route('student.classes.index'), 'label' => __('site.nav.my_classes')]
        : ['url' => route('login'), 'label' => __('site.nav.login')];
@endphp
<header class="rq-header">
    <div class="rq-wrap rq-nav">
        <a class="rq-brand" href="{{ $home }}">
            @if ($site['photo_url'])
                <img class="tl-brand-photo" src="{{ $site['photo_url'] }}" alt="{{ $site['name'] }}">
            @endif
            <span>{{ $site['name'] }}</span>
        </a>

        <nav class="rq-links" aria-label="{{ __('site.nav.menu') }}">
            @foreach ($navItems as $item)
                <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endforeach
            <a href="{{ $account['url'] }}">{{ $account['label'] }}</a>
        </nav>

        <div class="rq-nav-actions">
            @if ($site['whatsapp_url'])
                <a class="rq-btn rq-btn--primary rq-btn--sm" href="{{ $site['whatsapp_url'] }}" target="_blank" rel="noopener">{{ __('site.nav.contact') }}</a>
            @endif

            <details class="rq-menu">
                <summary aria-label="{{ __('site.nav.menu') }}">@include('site.partials.icon', ['name' => 'menu', 'size' => 22])</summary>
                <div class="rq-menu-panel">
                    @foreach ($navItems as $item)
                        <a href="{{ $item['url'] }}" @if ($item['active']) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @endforeach
                    <a href="{{ $account['url'] }}">{{ $account['label'] }}</a>
                </div>
            </details>
        </div>
    </div>
</header>
