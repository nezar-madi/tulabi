<section class="rq-section rq-section--white" id="contact">
    <div class="rq-wrap rq-about">
        <div class="rq-block">
            <p class="rq-eyebrow rq-eyebrow--plain">{{ __('site.home.contact_eyebrow') }}</p>
            <h2 class="rq-h2">{{ __('site.home.contact_title') }}</h2>
            <p class="rq-lead">{{ __('site.home.contact_intro') }}</p>
            @if ($site['whatsapp_url'])
                <div class="rq-hero-actions">
                    <a class="rq-btn rq-btn--whatsapp" href="{{ $site['whatsapp_url'] }}" target="_blank" rel="noopener">
                        @include('site.partials.icon', ['name' => 'whatsapp', 'size' => 20])
                        {{ __('site.classes.ask_whatsapp') }}
                    </a>
                </div>
            @endif
        </div>

        <ul class="rq-contact">
            @if ($site['whatsapp_url'])
                <li>
                    <span class="rq-contact-icon">@include('site.partials.icon', ['name' => 'whatsapp', 'size' => 20])</span>
                    <div>
                        <small>واتساب</small>
                        <span class="rq-contact-number"><a href="{{ $site['whatsapp_url'] }}" target="_blank" rel="noopener" dir="ltr">{{ $site['whatsapp_number'] }}</a></span>
                    </div>
                </li>
            @endif
            <li>
                <span class="rq-contact-icon">@include('site.partials.icon', ['name' => 'pin', 'size' => 20])</span>
                <div><small>{{ __('site.contact.city') }}</small><span class="rq-contact-line">{{ $site['city'] }}</span></div>
            </li>
            @if (count($site['links']) > 0)
                <li>
                    <span class="rq-contact-icon">@include('site.partials.icon', ['name' => 'users', 'size' => 20])</span>
                    <div>
                        <small>{{ __('site.contact.follow') }}</small>
                        @foreach ($site['links'] as $link)
                            @if (! $loop->first)
                                <span class="rq-dot">•</span>
                            @endif
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener nofollow">{{ __('site.links.'.$link['kind']) }}</a>
                        @endforeach
                    </div>
                </li>
            @endif
        </ul>
    </div>
</section>
