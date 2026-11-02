<style>
@font-face{font-family:'Alexandria';font-style:normal;font-weight:400 800;font-display:swap;src:url("/fonts/site/07086eb9-b3d0-44f1-b3c5-9929c9360f78.woff2") format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC}
@font-face{font-family:'Alexandria';font-style:normal;font-weight:400 800;font-display:swap;src:url("/fonts/site/49b3fe2e-ea96-40bb-9a18-408a85222df9.woff2") format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:400;font-display:swap;src:url("/fonts/site/537f491f-2e2b-4279-ad19-936241a91379.woff2") format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:400;font-display:swap;src:url("/fonts/site/6225cecc-dabb-4f8e-a8a5-2619fb4d2c94.woff2") format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:500;font-display:swap;src:url("/fonts/site/da4f9698-16ee-4590-9f13-660aa010f6b8.woff2") format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:500;font-display:swap;src:url("/fonts/site/7db635a2-9900-4dde-81f3-70b80fe0c44a.woff2") format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:700;font-display:swap;src:url("/fonts/site/09edd586-816a-4646-a7de-ee66e2217b2e.woff2") format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:700;font-display:swap;src:url("/fonts/site/04648afb-caeb-4e63-a82e-b9161d41ce2c.woff2") format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
</style>
<style>
/* Tulabi home page (copied from rqmana's landing) — v3 design language (Design System Brief): flat colour, Alexandria + Tajawal,
   4px buttons, 6–8px frames, 999px chips. Plain HTML; no framework. */
:root {
  --blue: #0D4AA8; --blue-deep: #08286E; --blue-accent: #006DC5; --blue-bright: #0192F1; --blue-light: #7CC4F8;
  --tint: #DCE8FA; --tint-soft: #EEF4FC; --page: #F3F7FC; --chrome: #F4F7FB;
  --ink: #33393F; --muted: #5B6570; --subtle: #78838C; --white: #FFFFFF;
  --line: rgba(8,40,110,.12); --line-strong: rgba(8,40,110,.22);
  --display: 'Alexandria', 'Tajawal', sans-serif; --body: 'Tajawal', sans-serif;
  --wrap: 1200px; --gutter: clamp(20px, 4vw, 48px);
  --shadow: 0 24px 48px -28px rgba(8,40,110,.45); --shadow-lift: 0 40px 80px -36px rgba(8,40,110,.5);
}
* { box-sizing: border-box; }
html { scroll-behavior: smooth; scroll-padding-top: 84px; }
@media (prefers-reduced-motion: reduce) { html { scroll-behavior: auto; } *, *::before, *::after { transition: none !important; animation: none !important; } }
body { margin: 0; background: var(--white); color: var(--ink); font-family: var(--body); font-size: 17px; line-height: 1.75; -webkit-font-smoothing: antialiased; }
img { display: block; max-width: 100%; height: auto; }
a { color: inherit; }
::selection { background: var(--blue-light); color: var(--ink); }
:focus-visible { outline: 3px solid var(--blue-bright); outline-offset: 3px; border-radius: 4px; }
.wrap { max-width: var(--wrap); margin: 0 auto; padding-inline: var(--gutter); }
.sr-only { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
.skip { position: absolute; top: -60px; right: 16px; z-index: 50; padding: 10px 16px; border-radius: 4px; background: var(--blue); color: #fff; font-family: var(--display); text-decoration: none; }
.skip:focus { top: 12px; }

/* Type */
h1, h2, h3, h4 { margin: 0; font-family: var(--display); color: var(--blue); text-wrap: balance; }
h1, .h1 { margin: 0; font-family: var(--display); color: var(--blue); font-weight: 700; font-size: clamp(36px, 4.6vw, 60px); line-height: 1.25; text-wrap: balance; }
h2 { font-weight: 700; font-size: clamp(28px, 3.4vw, 44px); line-height: 1.35; }
h3 { font-weight: 700; font-size: clamp(22px, 2.2vw, 30px); line-height: 1.4; }
h4 { font-weight: 600; font-size: 17px; line-height: 1.5; }
p { margin: 0; text-wrap: pretty; }
.lead { font-size: clamp(17px, 1.5vw, 20px); line-height: 1.8; color: var(--muted); max-width: 640px; }
.eyebrow { font-family: var(--display); font-weight: 600; font-size: 13px; letter-spacing: .3px; color: var(--blue-accent); }
.muted { color: var(--muted); }

/* Buttons */
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-height: 48px; padding: 12px 24px; border-radius: 4px; border: 1px solid transparent;
  font-family: var(--display); font-weight: 600; font-size: 16px; text-decoration: none; cursor: pointer; transition: background .2s, color .2s, border-color .2s; }
.btn svg { width: 18px; height: 18px; flex: none; }
.btn--primary { background: var(--blue); color: var(--white); }
.btn--primary:hover { background: var(--blue-deep); }
.btn--ghost { color: var(--blue); border-color: rgba(13,74,168,.35); font-weight: 500; }
.btn--ghost:hover { background: var(--white); }
.btn--sm { min-height: 40px; padding: 8px 16px; font-size: 14px; }
.btn--pill { border-radius: 999px; padding-inline: 30px; min-height: 56px; font-size: 18px; box-shadow: 0 18px 36px -18px rgba(13,74,168,.7); }
.btn--light { background: var(--white); color: var(--blue-deep); }
.btn--light:hover { background: var(--tint); }
.btn--outline-light { color: var(--white); border-color: rgba(255,255,255,.4); font-weight: 500; }
.btn--outline-light:hover { background: rgba(255,255,255,.08); }

/* Header */
.site-head { position: sticky; top: 0; z-index: 20; background: rgba(220,232,250,.92); backdrop-filter: saturate(1.4) blur(10px); transition: box-shadow .2s, background .2s; }
.site-head.is-scrolled { background: rgba(255,255,255,.94); box-shadow: 0 1px 0 var(--line); }
.nav { display: flex; align-items: center; justify-content: space-between; gap: 24px; height: 72px; }
/* The header starts as wide as the hero, then narrows to the page width once you scroll */
.site-head .wrap.nav { max-width: 1440px; transition: max-width .45s cubic-bezier(.2,.7,.2,1), height .3s; }
.site-head.is-scrolled .wrap.nav { max-width: var(--wrap); }
.nav__logo img { height: 34px; width: auto; }
.nav__links { display: flex; align-items: center; gap: clamp(14px, 2.4vw, 30px); font-family: var(--display); font-size: 15px; }
.nav__links a { text-decoration: none; color: var(--ink); }
.nav__links a:hover { color: var(--blue); }
.nav__actions { display: flex; align-items: center; gap: 10px; }
.nav__menu { display: none; }
@media (max-width: 900px) {
  .nav__links { display: none; }
  .nav__menu { display: block; position: relative; }
  .nav__menu summary { list-style: none; display: grid; place-items: center; width: 42px; height: 42px; border-radius: 4px; border: 1px solid var(--line-strong); background: var(--white); color: var(--blue); cursor: pointer; }
  .nav__menu summary::-webkit-details-marker { display: none; }
  .nav__menu summary svg { width: 22px; height: 22px; }
  .nav__menu[open] .nav__panel { display: flex; }
  .nav__panel { display: none; position: absolute; left: 0; top: 52px; min-width: 220px; flex-direction: column; padding: 8px; border-radius: 8px; background: var(--white); border: 1px solid var(--line); box-shadow: var(--shadow); }
  .nav__panel a { padding: 10px 12px; border-radius: 4px; text-decoration: none; font-family: var(--display); font-size: 15px; }
  .nav__panel a:hover { background: var(--tint-soft); color: var(--blue); }
}
@media (max-width: 420px) { .nav__actions .btn { padding-inline: 12px; font-size: 13px; } }

/* Hero */
.hero { background: var(--tint); overflow: hidden; }
.hero__grid { max-width: 1440px; display: grid; grid-template-columns: minmax(0, .8fr) minmax(0, 1.5fr); gap: clamp(36px, 5vw, 64px); align-items: center; padding-block: clamp(36px, 5vw, 64px) clamp(64px, 8vw, 104px); }
@media (max-width: 1100px) { .hero__grid { grid-template-columns: 1fr; } }
.hero__copy { display: flex; flex-direction: column; align-items: flex-start; gap: 22px; }
.badge { display: inline-flex; align-items: center; gap: 10px; padding: 7px 16px 7px 14px; border-radius: 999px; background: rgba(255,255,255,.72);
  border: 1px solid rgba(13,74,168,.16); box-shadow: 0 6px 18px -12px rgba(8,40,110,.35); font-family: var(--display); font-size: 13px; color: var(--muted); }
.badge b { font-weight: 600; color: var(--blue-deep); }
.badge__dot { position: relative; flex: none; width: 8px; height: 8px; border-radius: 50%; background: #16A34A; }
.badge__dot::after { content: ''; position: absolute; inset: -4px; border-radius: 50%; border: 2px solid rgba(22,163,74,.35); animation: badge-pulse 2.4s ease-out infinite; }
@keyframes badge-pulse { from { transform: scale(.6); opacity: 1; } to { transform: scale(1.5); opacity: 0; } }
.badge__sep { width: 1px; height: 14px; background: rgba(13,74,168,.2); }
.hero__lead { font-size: clamp(17px, 1.5vw, 20px); line-height: 1.8; color: var(--ink); max-width: 540px; }
.hero__cta { display: flex; flex-wrap: wrap; gap: 14px; }
.hero__facts { display: flex; flex-wrap: wrap; gap: 10px 24px; margin-top: 4px; font-family: var(--display); font-size: 14px; color: var(--blue); }
.hero__facts span { display: inline-flex; align-items: center; gap: 8px; }
.hero__facts svg { width: 20px; height: 20px; }
.film { position: relative; aspect-ratio: 1120 / 700; min-width: 0; }
.film iframe { position: absolute; inset: -14px; width: calc(100% + 28px); height: calc(100% + 28px); border: 0; }
.film__poster { position: absolute; inset: 0; border-radius: 10px; overflow: hidden; background: var(--white); border: 1px solid var(--line); box-shadow: var(--shadow-lift); }
.film__poster img { width: 100%; height: 100% !important; object-fit: cover; object-position: top right; }
.film.is-live .film__poster { display: none; }
.film__caption { margin-top: 14px; text-align: center; font-size: 14px; color: var(--muted); }

/* Hero showcase: three films as chapters. The copy, the film and the chapter bar change together. */
.showcase { min-width: 0; }
.screen { position: relative; aspect-ratio: 1120 / 700; min-width: 0; }
.screen__poster, .screen iframe { position: absolute; border: 0; }
.screen__poster { inset: 0; border-radius: 10px; overflow: hidden; background: var(--white); border: 1px solid var(--line); box-shadow: var(--shadow-lift); transition: opacity .6s; }
.screen__poster img { width: 100%; height: 100% !important; object-fit: cover; object-position: top right; }
.screen.is-live .screen__poster { opacity: 0; }
.screen iframe { inset: -14px; width: calc(100% + 28px); height: calc(100% + 28px); opacity: 0; transform: scale(.965) translateY(10px); filter: blur(8px);
  transition: opacity .75s cubic-bezier(.2,.7,.2,1), transform .9s cubic-bezier(.2,.7,.2,1), filter .75s; pointer-events: none; }
.screen iframe.is-front { opacity: 1; transform: none; filter: none; }
.screen iframe.is-leaving { opacity: 0; transform: scale(1.025) translateY(-6px); filter: blur(6px); }
/* a soft light sweep across the frame while the films swap */
.screen::after { content: ''; position: absolute; inset: 0; border-radius: 10px; pointer-events: none; opacity: 0;
  background: linear-gradient(105deg, transparent 30%, rgba(255,255,255,.55) 48%, transparent 66%) no-repeat; background-size: 220% 100%; background-position: 120% 0; }
.screen.is-swapping::after { animation: sweep .9s ease-out; }
@keyframes sweep { 0% { opacity: 0; background-position: 120% 0; } 30% { opacity: 1; } 100% { opacity: 0; background-position: -60% 0; } }

.chapters { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(12px, 2vw, 28px); margin-bottom: 26px; padding: 0 6px; }
.chapter { all: unset; box-sizing: border-box; display: grid; grid-template-columns: auto 1fr; grid-template-rows: auto 1fr auto; column-gap: 10px; row-gap: 3px; cursor: pointer; text-align: right; }
.chapter:focus-visible { outline: 3px solid var(--blue-bright); outline-offset: 6px; border-radius: 4px; }
.chapter__track { grid-column: 1 / -1; grid-row: 3; position: relative; height: 3px; margin-top: 12px; border-radius: 3px; background: rgba(13,74,168,.14); overflow: hidden; }
.chapter__track i { position: absolute; inset: 0 0 0 auto; width: 0; border-radius: inherit; background: var(--blue); }
.chapter:hover .chapter__track { background: rgba(13,74,168,.24); }
.chapter__num { grid-row: 1 / 3; align-self: start; font-family: var(--display); font-weight: 600; font-size: 12px; line-height: 22px; color: rgba(13,74,168,.4); font-feature-settings: 'tnum'; transition: color .3s; }
.chapter__title { font-family: var(--display); font-weight: 600; font-size: 15px; line-height: 22px; color: var(--muted); transition: color .3s; }
.chapter__desc { font-size: 13px; line-height: 1.5; color: var(--subtle); transition: color .3s; }
.chapter.is-active .chapter__num { color: var(--blue-accent); }
.chapter.is-active .chapter__title { color: var(--blue-deep); }
.chapter.is-active .chapter__desc { color: var(--muted); }
.chapter.is-done .chapter__track i { width: 100%; background: rgba(13,74,168,.35); }
@media (max-width: 560px) { .chapter__desc { display: none; } .chapter__title { font-size: 13px; } .chapters { gap: 10px; } }

/* The hero copy: three versions stacked in one grid cell, so swapping never moves the layout */
.story { display: grid; }
.story > div { grid-area: 1 / 1; display: flex; flex-direction: column; gap: 18px; align-self: start; }
.story > div:not(.is-on) { visibility: hidden; }
.story .word { display: inline-block; opacity: 0; transform: translateY(14px); filter: blur(6px); transition: opacity .55s, transform .65s cubic-bezier(.2,.7,.2,1), filter .55s; transition-delay: calc(var(--i) * 45ms); }
.story .is-on .word { opacity: 1; transform: none; filter: none; }
.story .story__lead { opacity: 0; transform: translateY(10px); transition: opacity .6s .25s, transform .7s .25s cubic-bezier(.2,.7,.2,1); }
.story .is-on .story__lead { opacity: 1; transform: none; }
.story .is-out { visibility: visible !important; }
.story .is-out .word, .story .is-out .story__lead { opacity: 0; transform: translateY(-10px); filter: blur(4px); transition-duration: .3s; transition-delay: 0s; }

/* Generic sections */
.section { padding-block: clamp(72px, 9vw, 120px); }
.section--tint { background: var(--page); }
.section__head { display: flex; flex-direction: column; align-items: center; gap: 14px; text-align: center; margin-bottom: clamp(44px, 6vw, 80px); }
.section__head .lead { margin-inline: auto; }

/* Problem */

/* Timeline */
.timeline { position: relative; display: flex; flex-direction: column; gap: clamp(80px, 10vw, 136px); }
.timeline::before { content: ''; position: absolute; top: 20px; bottom: 20px; right: 19px; width: 2px; background: var(--tint); }
.step { position: relative; display: grid; grid-template-columns: 40px minmax(0, 1fr); gap: clamp(16px, 3vw, 40px); }
.step__dot { position: relative; display: grid; place-items: center; width: 40px; height: 40px; border-radius: 50%; background: var(--blue); color: var(--white); box-shadow: 0 0 0 6px var(--white); }
.step__dot svg { width: 20px; height: 20px; }
.step__body { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.25fr); gap: clamp(32px, 5vw, 64px); align-items: center; min-width: 0; }
.step--wide .step__body { grid-template-columns: 1fr; }
@media (max-width: 900px) { .step__body { grid-template-columns: 1fr; } }
.step__text { display: flex; flex-direction: column; gap: 10px; max-width: 560px; }
.step__text p { color: var(--muted); }
.ticks { margin: 12px 0 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 10px; font-size: 16px; color: var(--ink); }
.ticks li { position: relative; padding-right: 28px; line-height: 1.6; }
.ticks li::before { content: ''; position: absolute; right: 0; top: .35em; width: 18px; height: 18px; border-radius: 50%; background: var(--tint) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%230D4AA8' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 12l4 4 8-8'/%3E%3C/svg%3E") center / 12px no-repeat; }
.shot { min-width: 0; border-radius: 6px; overflow: hidden; background: var(--white); border: 1px solid var(--line); box-shadow: var(--shadow); }
.shot__bar { display: flex; align-items: center; gap: 7px; height: 30px; padding: 0 12px; background: var(--chrome); border-bottom: 1px solid rgba(8,40,110,.1); direction: ltr; }
.shot__bar i { width: 8px; height: 8px; border-radius: 50%; background: #CFD9E2; }
.shot__bar span { margin-left: 12px; font-family: var(--display); font-size: 11px; color: var(--subtle); }
.stage-box { position: relative; min-width: 0; border-radius: 6px; background: var(--page); padding: clamp(18px, 3vw, 36px); }
.stage-box--tint { background: var(--tint); }
.cards3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(20px, 2.4vw, 32px); }
@media (max-width: 900px) { .cards3 { grid-template-columns: 1fr; } }
.mini { display: flex; flex-direction: column; gap: 14px; min-width: 0; }
.mini__img { position: relative; border-radius: 8px; background: linear-gradient(160deg, var(--tint) 0%, var(--tint-soft) 100%); padding: 18px 18px 0; overflow: hidden; }
.mini__img div { aspect-ratio: 16 / 10; border-radius: 6px 6px 0 0; overflow: hidden; background: var(--white); box-shadow: 0 18px 34px -22px rgba(8,40,110,.5), 0 0 0 1px rgba(8,40,110,.08);
  -webkit-mask-image: linear-gradient(to bottom, #000 72%, transparent); mask-image: linear-gradient(to bottom, #000 72%, transparent); }
.mini__img img { width: 100%; height: 100% !important; object-fit: cover; object-position: top right; transform-origin: top right; transition: transform .6s cubic-bezier(.2,.7,.2,1); }
.mini:hover .mini__img img { transform: scale(1.04); }
.mini p { font-size: 15px; color: var(--muted); }
.float-shot { position: absolute; left: clamp(-12px, -1vw, 0px); bottom: clamp(14px, 2vw, 24px); width: 50%; }
.callouts { position: absolute; inset: 0; pointer-events: none; }
.callout { position: absolute; padding: 8px 13px; border-radius: 4px; background: var(--blue-deep); color: var(--white); font-family: var(--display); font-weight: 600; font-size: 13px; white-space: nowrap; box-shadow: 0 10px 20px -10px rgba(8,40,110,.6); }
@media (max-width: 760px) { .callouts { display: none; } }
.chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 6px; }
.chip { padding: 5px 12px; border-radius: 999px; border: 1px solid var(--line-strong); font-size: 14px; font-weight: 500; color: var(--ink); background: var(--white); }

/* Phones: "كل ما تحتاجه لإدارة مركزك" keeps only its heading and intro line, and a few notes are left out. */
@media (max-width: 760px) {
  .phone-hide { display: none !important; }
  #features .wrap > :not(.section__head) { display: none; }
  /* The hero keeps only its copy; the chapter bar and the animated screen are left out. */
  .showcase { display: none; }
}

/* Screenshot peek: hovering a card floats the full screen above it; clicking opens it in a dialog */
.mini__img { cursor: zoom-in; }
.mini__zoom { position: absolute; z-index: 2; top: 28px; left: 28px; display: grid; place-items: center; width: 34px; height: 34px; border-radius: 50%; background: rgba(255,255,255,.92);
  color: var(--blue); box-shadow: 0 6px 16px -8px rgba(8,40,110,.5); opacity: 0; transform: scale(.8); transition: opacity .25s, transform .25s; }
.mini__zoom svg { width: 17px; height: 17px; }
.mini:hover .mini__zoom, .mini__img:focus-visible .mini__zoom { opacity: 1; transform: none; }
.peek { position: fixed; z-index: 40; top: 0; left: 0; pointer-events: none; visibility: hidden; transform-origin: 0 0; will-change: transform, clip-path; }
.peek.is-on { visibility: visible; }
.peek img { display: block; width: 100%; height: 100% !important; border-radius: 12px; background: var(--white);
  box-shadow: 0 50px 100px -30px rgba(8,40,110,.6), 0 0 0 1px rgba(8,40,110,.1); }
.peek__glare { position: absolute; inset: 0; border-radius: 12px; pointer-events: none; opacity: 0;
  background: linear-gradient(115deg, transparent 35%, rgba(255,255,255,.65) 50%, transparent 65%) no-repeat; background-size: 250% 100%; }
.peek.is-on .peek__glare { animation: glare 1.1s .15s ease-out; }
@keyframes glare { 0% { opacity: 0; background-position: 130% 0; } 25% { opacity: 1; } 100% { opacity: 0; background-position: -40% 0; } }
.peek__hint { position: absolute; left: 50%; bottom: 14px; translate: -50% 0; display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px;
  background: rgba(8,40,110,.86); color: var(--white); font-family: var(--display); font-size: 12px; opacity: 0; transition: opacity .3s .35s; backdrop-filter: blur(6px); }
.peek.is-on .peek__hint { opacity: 1; }
@media (hover: none), (max-width: 900px) { .peek { display: none; } .mini__zoom { opacity: 1; transform: none; } }
.lightbox { width: min(1280px, calc(100vw - 32px)); max-width: none; max-height: calc(100vh - 32px); padding: 0; border: 0; border-radius: 14px; background: var(--white); box-shadow: 0 50px 120px -30px rgba(2,22,63,.6); overflow: hidden; }
.lightbox::backdrop { background: rgba(8,24,60,.55); backdrop-filter: blur(4px); }
.lightbox[open] { animation: lb-in .3s ease; }
.lightbox::backdrop { animation: lb-in .3s ease; }
@keyframes lb-in { from { opacity: 0; } to { opacity: 1; } }
.lightbox__body img { transform-origin: 0 0; }
.lightbox__bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; border-bottom: 1px solid var(--line); font-family: var(--display); font-weight: 600; font-size: 15px; color: var(--blue-deep); }
.lightbox__close { display: grid; place-items: center; width: 36px; height: 36px; border: 0; border-radius: 50%; background: var(--tint-soft); color: var(--blue); cursor: pointer; }
.lightbox__close:hover { background: var(--tint); }
.lightbox__close svg { width: 18px; height: 18px; }
.lightbox__body { max-height: calc(100vh - 100px); overflow: auto; padding: 14px; background: var(--page); }
.lightbox__body img { width: 100%; border-radius: 8px; border: 1px solid var(--line); background: var(--white); }

/* A wide, sharp screenshot: shown close to its real size, edges fade instead of a hard cut */
.shot-wide { padding: clamp(18px, 3vw, 40px) clamp(16px, 5vw, 72px) 0; }
.shot-wide .shot { border-radius: 8px 8px 0 0; border-bottom: 0; }
.shot__crop { -webkit-mask-image: linear-gradient(to right, transparent 0, #000 9%), linear-gradient(to top, transparent 0, #000 16%); mask-image: linear-gradient(to right, transparent 0, #000 9%), linear-gradient(to top, transparent 0, #000 16%);
  -webkit-mask-composite: source-in; mask-composite: intersect; }
.ticks--row { display: grid; grid-template-columns: repeat(auto-fit, minmax(min(100%, 230px), 1fr)); gap: 12px 28px; margin-top: 0; }

/* Classes: a live, crisp replica of the app's list (real fields, neutral demo data) with working status filters,
   plus the room-availability check. Text is real text, so it stays sharp at any size. */
.cl-stage { position: relative; display: grid; grid-template-columns: minmax(0, 1fr) 236px; gap: 22px; align-items: end; padding: clamp(18px, 2.6vw, 30px) clamp(16px, 3vw, 40px); overflow: hidden; }
.cl-app { position: relative; min-width: 0; border-radius: 10px; background: var(--white); border: 1px solid var(--line); box-shadow: var(--shadow-lift); overflow: hidden; }
.cl-head { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; padding: 14px 18px 10px; }
.cl-crumb { display: block; font-size: 11px; color: var(--subtle); }
.cl-title { display: block; font-family: var(--display); font-weight: 700; font-size: 18px; color: var(--blue); line-height: 1.3; }
.cl-new { display: inline-flex; align-items: center; gap: 5px; padding: 6px 11px; border-radius: 4px; background: var(--blue); color: #fff; font-family: var(--display); font-weight: 600; font-size: 12px; }
.cl-new svg { width: 14px; height: 14px; }
.cl-filters { display: flex; flex-wrap: wrap; gap: 6px; padding: 0 18px 12px; border-bottom: 1px solid var(--line); }
.cl-chip { all: unset; display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; border: 1px solid var(--line-strong); background: var(--white); font-family: var(--display); font-size: 12px; color: var(--ink); cursor: pointer; transition: background .2s, color .2s, border-color .2s; }
.cl-chip i { font-style: normal; font-weight: 600; font-size: 11px; color: var(--subtle); }
.cl-chip:hover { border-color: var(--blue); }
.cl-chip:focus-visible { outline: 3px solid var(--blue-bright); outline-offset: 2px; }
.cl-chip.is-on { background: var(--blue); border-color: var(--blue); color: #fff; }
.cl-chip.is-on i { color: rgba(255,255,255,.75); }
.dot { width: 7px; height: 7px; border-radius: 50%; }
.dot--blue { background: #0192F1; } .dot--green { background: #16A34A; } .dot--grey { background: #94A3B8; } .dot--red { background: #DC2626; }
.cl-chip.is-on .dot { box-shadow: 0 0 0 2px rgba(255,255,255,.8); }
.cl-row { display: grid; grid-template-columns: minmax(0, 2.2fr) minmax(0, 1.2fr) minmax(0, 1.5fr) 64px; gap: 12px; align-items: center; padding: 8px 18px; border-bottom: 1px solid rgba(8,40,110,.07); font-size: 13px; }
.cl-row--head { padding-block: 7px; background: var(--chrome); font-family: var(--display); font-weight: 600; font-size: 11px; color: var(--muted); }
.cl-row b { display: block; font-family: var(--display); font-weight: 600; font-size: 12.5px; color: var(--ink); line-height: 1.45; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cl-row small { display: block; font-size: 11px; color: var(--subtle); line-height: 1.5; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cl-name { display: flex; align-items: center; gap: 12px; min-width: 0; }
.cl-name > span { min-width: 0; }
.cl-ico { flex: none; display: grid; place-items: center; width: 30px; height: 30px; border-radius: 8px; background: color-mix(in srgb, var(--c) 12%, #fff); color: var(--c); font-style: normal; font-family: var(--display); font-weight: 700; font-size: 11px; }
.st { display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 999px; font-style: normal; font-family: var(--display); font-weight: 600; font-size: 10.5px; }
.st::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.st--blue { background: #E0F0FF; color: #0369A1; } .st--green { background: #DCFCE7; color: #15803D; } .st--grey { background: #F1F5F9; color: #64748B; } .st--red { background: #FEE2E2; color: #B91C1C; }
.cl-rows .cl-row { transition: opacity .35s, transform .45s cubic-bezier(.2,.7,.2,1), max-height .45s cubic-bezier(.2,.7,.2,1), padding .45s, border-color .3s; max-height: 140px; }
.cl-rows .cl-row.is-hidden { opacity: 0; transform: translateX(-14px); max-height: 0; padding-block: 0; border-color: transparent; }
.cl-rows .cl-row:last-child { border-bottom: 0; }
.reveal:not(.is-in) .cl-rows .cl-row { opacity: 0; transform: translateY(10px); }
.reveal.is-in .cl-rows .cl-row { transition-delay: calc(var(--n, 0) * 70ms); }
.cl-room { position: relative; margin-bottom: 18px; padding: 10px 12px; border-radius: 10px; background: var(--white);
  border: 1px solid var(--line); box-shadow: 0 30px 60px -24px rgba(8,40,110,.55); }
.cl-room__head { display: flex; align-items: center; gap: 8px; padding-bottom: 8px; margin-bottom: 8px; border-bottom: 1px solid var(--line); }
.cl-room__ico { display: grid; place-items: center; width: 28px; height: 28px; border-radius: 9px; background: var(--tint); color: var(--blue); }
.cl-room__ico svg { width: 18px; height: 18px; }
.cl-room b { display: block; font-family: var(--display); font-weight: 600; font-size: 12.5px; color: var(--ink); }
.cl-room small { display: block; font-size: 11px; color: var(--subtle); }
.cl-room ul { display: grid; grid-template-columns: 1fr; gap: 6px; margin: 0; padding: 0; list-style: none; }
.cl-room li { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 7px 9px; border-radius: 6px; background: var(--chrome); }
.cl-room li.is-pick { background: #F0FDF4; box-shadow: inset 0 0 0 1px rgba(22,163,74,.25); }
.av { display: inline-flex; align-items: center; gap: 4px; font-style: normal; font-family: var(--display); font-weight: 600; font-size: 11px; }
.av svg { width: 13px; height: 13px; }
.av--yes { color: #15803D; } .av--no { color: #B45309; }
@media (max-width: 980px) { .cl-stage { grid-template-columns: 1fr; } .cl-room { margin-bottom: 0; } }
@media (max-width: 760px) {
  .cl-row { grid-template-columns: minmax(0, 1fr) 70px; }
  .cl-row > span:nth-child(2), .cl-row > span:nth-child(3) { display: none; }
  .cl-stage { grid-template-columns: 1fr; }
  .cl-room { margin-bottom: 0; }
  .cl-name b { white-space: normal; }
}

/* Roles: a live permissions matrix. Role chips fill the checkboxes the way that role would be set up. */
.pm-count { display: inline-flex; align-items: baseline; gap: 5px; padding: 5px 11px; border-radius: 999px; background: var(--tint-soft); font-family: var(--display); font-size: 12px; color: var(--muted); }
.pm-count b { font-size: 15px; color: var(--blue); font-feature-settings: 'tnum'; }
.pm-grid { padding: 4px 0 6px; }
.pm-row { display: grid; grid-template-columns: minmax(0, 1.6fr) repeat(5, minmax(0, .7fr)); align-items: center; padding: 6px 18px; border-bottom: 1px solid rgba(8,40,110,.06); }
.pm-row:last-child { border-bottom: 0; }
.pm-row > span:not(.pm-mod) { justify-self: center; }
.pm-row--head { padding-block: 7px; background: var(--chrome); font-family: var(--display); font-weight: 600; font-size: 11px; color: var(--muted); }
.pm-mod { font-family: var(--display); font-weight: 600; font-size: 12.5px; color: var(--ink); }
.pm-row--head .pm-mod { font-size: 11px; color: var(--muted); }
.pm-box { display: grid; place-items: center; width: 20px; height: 20px; border-radius: 5px; border: 1.5px solid rgba(8,40,110,.22); background: var(--white); color: #fff;
  transition: background .25s, border-color .25s, transform .3s cubic-bezier(.3,1.6,.4,1); }
.pm-box svg { width: 12px; height: 12px; opacity: 0; transform: scale(.4); transition: opacity .2s, transform .3s cubic-bezier(.3,1.6,.4,1); }
.pm-box.is-on { background: var(--blue); border-color: var(--blue); transform: scale(1.06); }
.pm-box.is-on svg { opacity: 1; transform: none; }
.pm-log li { align-items: flex-start; justify-content: flex-start; gap: 9px; background: transparent; padding: 7px 4px; border-bottom: 1px solid rgba(8,40,110,.07); border-radius: 0; }
.pm-log li:last-child { border-bottom: 0; }
.pm-log small { white-space: normal; }
.pm-when { color: var(--blue-accent) !important; font-size: 10.5px !important; margin-top: 2px; }
.pm-who { flex: none; display: grid; place-items: center; width: 26px; height: 26px; border-radius: 50%; background: var(--tint); color: var(--blue); font-family: var(--display); font-weight: 700; font-size: 12px; }
@media (max-width: 560px) { .pm-row { padding-inline: 12px; grid-template-columns: minmax(0, 1.4fr) repeat(5, minmax(0, .6fr)); } .pm-mod { font-size: 11.5px; } .pm-row--head span { font-size: 10px; } .pm-box { width: 18px; height: 18px; } }

/* Feature spotlights (assistant, website) */
.spot { max-width: 1440px; display: grid; grid-template-columns: minmax(0, .75fr) minmax(0, 1.6fr); gap: clamp(36px, 5vw, 72px); align-items: center; }
.spot--flip { grid-template-columns: minmax(0, 1.6fr) minmax(0, .75fr); }
/* After .spot--flip, so the flipped section also stacks on phones and tablets. */
@media (max-width: 1100px) { .spot, .spot--flip { grid-template-columns: 1fr; } .spot > .reveal { order: 0 !important; } }
.spot__text { display: flex; flex-direction: column; gap: 14px; }
.spot--dark { color: rgba(255,255,255,.86); }
.section--navy { background: var(--blue-deep); }
.section--navy h2, .section--navy h3, .section--navy h4 { color: var(--white); }
.section--navy .eyebrow { color: var(--blue-light); }
.section--navy .lead { color: rgba(255,255,255,.78); }
.feature-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 18px 24px; margin: 12px 0 4px; padding: 0; list-style: none; }
@media (max-width: 560px) { .feature-list { grid-template-columns: 1fr; } }
.feature-list li { display: flex; gap: 12px; align-items: flex-start; }
.feature-list .ico { flex: none; display: grid; place-items: center; width: 40px; height: 40px; border-radius: 10px; background: var(--tint); color: var(--blue); }
.section--navy .feature-list .ico { background: rgba(124,196,248,.14); color: var(--blue-light); }
.feature-list .ico svg { width: 20px; height: 20px; }
.feature-list b { display: block; font-family: var(--display); font-weight: 600; font-size: 15px; color: var(--blue); }
.section--navy .feature-list b { color: var(--white); }
.feature-list small { display: block; font-size: 14px; line-height: 1.6; color: var(--muted); }
.section--navy .feature-list small { color: rgba(255,255,255,.7); }
.note { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 6px; background: rgba(255,255,255,.06); border: 1px solid rgba(124,196,248,.25); font-size: 15px; }
.note svg { flex: none; width: 20px; height: 20px; color: var(--blue-light); margin-top: 3px; }
.note--light { background: var(--white); border-color: var(--line); color: var(--ink); }
.note--light svg { color: var(--blue-accent); }

/* Modules */
.modules { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 1px; margin: 0; padding: 0; list-style: none; background: var(--line); border: 1px solid var(--line); border-radius: 8px; overflow: hidden; }
@media (max-width: 1000px) { .modules { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 560px) { .modules { grid-template-columns: 1fr; } }
.modules li { display: flex; flex-direction: column; gap: 8px; padding: 22px 22px 24px; background: var(--white); }
.modules .ico { display: grid; place-items: center; width: 42px; height: 42px; border-radius: 10px; background: var(--tint-soft); color: var(--blue-accent); }
.modules .ico svg { width: 22px; height: 22px; }
.modules b { font-family: var(--display); font-weight: 600; font-size: 16px; color: var(--blue); }
.modules span { font-size: 15px; line-height: 1.65; color: var(--muted); }

/* Trust */
.trust { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: clamp(20px, 3vw, 40px); }
@media (max-width: 1000px) { .trust { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 560px) { .trust { grid-template-columns: 1fr; } }
.trust div { display: flex; flex-direction: column; gap: 10px; }
.trust .ico { display: grid; place-items: center; width: 52px; height: 52px; border-radius: 12px; background: var(--blue); color: var(--white); }
.trust .ico svg { width: 24px; height: 24px; }
.trust b { font-family: var(--display); font-weight: 600; font-size: 17px; color: var(--blue); }
.trust span { font-size: 15px; color: var(--muted); }

/* Steps */
.steps { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: clamp(24px, 3vw, 48px); margin: 0; padding: 0; list-style: none; counter-reset: s; }
@media (max-width: 820px) { .steps { grid-template-columns: 1fr; } }
.steps li { display: flex; flex-direction: column; align-items: center; gap: 12px; text-align: center; }
.steps li::before { counter-increment: s; content: counter(s); display: grid; place-items: center; width: 52px; height: 52px; border-radius: 50%; background: var(--tint); color: var(--blue); font-family: var(--display); font-weight: 700; font-size: 22px; }
.steps b { font-family: var(--display); font-weight: 600; font-size: clamp(19px, 1.7vw, 22px); color: var(--blue); }
.steps span { max-width: 320px; font-size: 16px; color: var(--muted); }

/* FAQ */
.faq { max-width: 820px; margin: 0 auto; display: flex; flex-direction: column; border-top: 1px solid var(--line); }
.faq details { border-bottom: 1px solid var(--line); }
.faq summary { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 20px 4px; cursor: pointer; list-style: none; font-family: var(--display); font-weight: 600; font-size: 17px; color: var(--blue); }
.faq summary::-webkit-details-marker { display: none; }
.faq summary::after { content: ''; flex: none; width: 22px; height: 22px; border-radius: 50%; background: var(--tint) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%230D4AA8' stroke-width='2.6' stroke-linecap='round'%3E%3Cpath d='M12 6v12M6 12h12'/%3E%3C/svg%3E") center / 12px no-repeat; transition: transform .2s; }
.faq details[open] summary::after { transform: rotate(45deg); }
.faq p { padding: 0 4px 22px; color: var(--muted); font-size: 16px; }

/* CTA + footer */
.cta { background: var(--tint); }
.cta__inner { max-width: 860px; margin: 0 auto; display: flex; flex-direction: column; align-items: center; gap: 20px; text-align: center; }
.cta h2 { font-size: clamp(32px, 4.2vw, 56px); line-height: 1.3; }
.cta p { font-size: clamp(17px, 1.5vw, 21px); color: var(--ink); max-width: 620px; }
.cta__actions { display: flex; flex-wrap: wrap; justify-content: center; align-items: center; gap: 20px; margin-top: 8px; }
.textlink { padding: 10px 4px; border-bottom: 1px solid rgba(8,40,110,.3); color: var(--blue); text-decoration: none; font-family: var(--display); }
.textlink:hover { color: var(--blue-accent); }
.site-foot { background: var(--blue); color: var(--white); }
.site-foot .wrap { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px 24px; padding-block: 22px; font-size: 15px; }
.site-foot img { height: 24px; width: auto; }
.site-foot nav { display: flex; flex-wrap: wrap; gap: 0 20px; font-family: var(--display); font-size: 14px; }
@media (max-width: 700px) { .site-foot nav a { padding-block: 10px; } }
.site-foot a { color: var(--white); text-decoration: none; }
.site-foot a:hover { color: var(--tint); }
.lockup { display: inline-flex; align-items: center; gap: 14px; }
.lockup__sep { width: 1px; height: 22px; background: currentColor; opacity: .35; }
.lockup__tag { font-family: var(--display); font-size: 14px; font-weight: 500; letter-spacing: .2px; }
.site-foot .lockup:hover { color: var(--white); }

/* Online enrolment */
.enroll { display: grid; grid-template-columns: minmax(0, 1fr) minmax(280px, 380px); gap: clamp(36px, 6vw, 88px); align-items: center; }
@media (max-width: 900px) { .enroll { grid-template-columns: 1fr; } }
.enroll__steps { display: grid; gap: 16px; margin: 20px 0 0; padding: 0; list-style: none; counter-reset: e; }
.enroll__steps li { position: relative; display: grid; grid-template-columns: 44px 1fr; gap: 4px 16px; align-items: start; padding: 16px 18px; border: 1px solid var(--line); border-radius: 8px; background: var(--white); }
.enroll__steps li::before { counter-increment: e; content: counter(e); grid-row: span 2; display: grid; place-items: center; width: 44px; height: 44px; border-radius: 999px; background: var(--tint); color: var(--blue); font-family: var(--display); font-weight: 700; font-size: 18px; }
.enroll__steps b { font-family: var(--display); color: var(--blue-deep); font-size: 17px; }
.enroll__steps span { color: var(--muted); font-size: 15px; line-height: 1.7; }
.phone { position: relative; width: min(100%, 340px); margin-inline: auto; padding: 14px; border-radius: 36px; background: var(--blue-deep); box-shadow: var(--shadow-lift); }
.phone__screen { border-radius: 24px; overflow: hidden; background: var(--white); }
.phone__bar { padding: 18px 18px 14px; background: var(--blue); color: var(--white); }
.phone__bar small { display: block; font-size: 12px; opacity: .8; }
.phone__bar b { font-family: var(--display); font-size: 17px; }
.phone__body { display: grid; gap: 10px; padding: 16px 18px 20px; }
.phone__course { display: flex; justify-content: space-between; align-items: center; gap: 8px; padding: 10px 12px; border-radius: 8px; background: var(--tint-soft); font-size: 13px; color: var(--blue-deep); }
.phone__course em { font-style: normal; padding: 2px 10px; border-radius: 999px; background: var(--white); color: var(--blue-accent); font-size: 12px; white-space: nowrap; }
.phone__field { display: grid; gap: 4px; font-size: 12px; color: var(--muted); }
.phone__field i { display: block; height: 34px; border: 1px solid var(--line-strong); border-radius: 6px; background: var(--white); }
.phone__field i.is-typed { display: flex; align-items: center; padding-inline: 10px; font-style: normal; font-size: 13px; color: var(--ink); }
.phone__send { margin-top: 4px; padding: 10px; border-radius: 999px; background: var(--blue); color: var(--white); text-align: center; font-family: var(--display); font-size: 14px; }
.phone__toast { position: absolute; inset-inline-start: -28px; bottom: 56px; display: flex; align-items: center; gap: 10px; padding: 10px 14px; border-radius: 10px; background: var(--white); border: 1px solid var(--line); box-shadow: var(--shadow); font-family: var(--display); font-size: 13px; color: var(--blue-deep); }
.phone__toast svg { width: 18px; height: 18px; color: var(--blue-bright); }
@media (max-width: 480px) { .phone__toast { inset-inline-start: 8px; } }

/* Text beside the films: smaller so it breathes next to the wider film */
@media (min-width: 1101px) {
  .hero__copy { gap: 18px; }
  .hero__copy h1, .hero__copy .h1 { font-size: clamp(32px, 3.2vw, 46px); line-height: 1.3; }
  .hero__lead { font-size: 16px; line-height: 1.8; }
  .hero__cta .btn { min-height: 44px; padding: 10px 18px; font-size: 15px; }
  .hero__facts { flex-direction: column; gap: 8px; font-size: 13px; }
  .spot__text { gap: 12px; }
  .spot__text h2 { font-size: clamp(24px, 2.3vw, 32px); line-height: 1.4; }
  .spot__text .lead { font-size: 16px; line-height: 1.75; }
  .spot .feature-list { grid-template-columns: 1fr; gap: 14px; }
  .spot .feature-list .ico { width: 34px; height: 34px; border-radius: 8px; }
  .spot .feature-list .ico svg { width: 18px; height: 18px; }
  .spot .feature-list b { font-size: 14px; }
  .spot .feature-list small { font-size: 13px; }
  .spot .note { font-size: 14px; }
}

/* Reveal on scroll */
.reveal { opacity: 0; transform: translateY(16px); transition: opacity .6s ease, transform .6s ease; }
.reveal.is-in { opacity: 1; transform: none; }
.no-js .reveal { opacity: 1; transform: none; }
</style>
