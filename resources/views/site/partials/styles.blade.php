<style>
@font-face{font-family:'Alexandria';font-style:normal;font-weight:400 800;font-display:swap;src:url("/fonts/site/07086eb9-b3d0-44f1-b3c5-9929c9360f78.woff2") format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC}
@font-face{font-family:'Alexandria';font-style:normal;font-weight:400 800;font-display:swap;src:url("/fonts/site/49b3fe2e-ea96-40bb-9a18-408a85222df9.woff2") format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:400;font-display:swap;src:url("/fonts/site/537f491f-2e2b-4279-ad19-936241a91379.woff2") format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:400;font-display:swap;src:url("/fonts/site/6225cecc-dabb-4f8e-a8a5-2619fb4d2c94.woff2") format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:500;font-display:swap;src:url("/fonts/site/da4f9698-16ee-4590-9f13-660aa010f6b8.woff2") format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:500;font-display:swap;src:url("/fonts/site/7db635a2-9900-4dde-81f3-70b80fe0c44a.woff2") format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:700;font-display:swap;src:url("/fonts/site/09edd586-816a-4646-a7de-ee66e2217b2e.woff2") format('woff2');unicode-range:U+0600-06FF,U+0750-077F,U+0870-088E,U+0890-0891,U+0897-08E1,U+08E3-08FF,U+200C-200E,U+2010-2011,U+204F,U+2E41,U+FB50-FDFF,U+FE70-FE74,U+FE76-FEFC}
@font-face{font-family:'Tajawal';font-style:normal;font-weight:700;font-display:swap;src:url("/fonts/site/04648afb-caeb-4e63-a82e-b9161d41ce2c.woff2") format('woff2');unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD}

:root{
  --rq-ink:#333A36;
  --rq-muted:#5A6860;
  --rq-faint:#78857D;
  --rq-primary:#00613E;
  --rq-primary-dark:#003A23;
  --rq-accent:#008154;
  --rq-sky:#00A86E;
  --rq-light:#7DD0A5;
  --rq-plate:#D8EDE1;
  --rq-tint:#EDF6F0;
  --rq-page:#F2F8F5;
  --rq-primary-rgb:0, 97, 62;
  --rq-dark-rgb:0, 58, 35;
  --rq-surface:#FFFFFF;
  --rq-line:rgba(var(--rq-dark-rgb),.12);
  --rq-line-strong:rgba(var(--rq-dark-rgb),.22);
  --rq-success:#1E7F4F;
  --rq-success-bg:#E5F4EC;
  --rq-gutter:clamp(16px,4vw,48px);
  --rq-heading:'Alexandria',sans-serif;
  --rq-body:'Tajawal',sans-serif;
}
*,*::before,*::after{box-sizing:border-box}
html{scroll-behavior:smooth;-webkit-text-size-adjust:100%}
body{margin:0;background:var(--rq-page);color:var(--rq-ink);font-family:var(--rq-body);font-size:17px;line-height:1.7;-webkit-font-smoothing:antialiased}
img{max-width:100%;display:block}
a{color:inherit}
::selection{background:var(--rq-light);color:var(--rq-ink)}
:focus-visible{outline:2px solid var(--rq-sky);outline-offset:3px;border-radius:4px}
.rq-skip{position:absolute;inset-inline-start:-9999px;top:8px;background:var(--rq-primary);color:#FFFFFF;padding:8px 14px;border-radius:4px;z-index:50}
.rq-skip:focus{inset-inline-start:8px}
.rq-wrap{max-width:1280px;margin:0 auto;padding-inline:var(--rq-gutter)}

/* Header */
.rq-header{background:var(--rq-surface);border-bottom:1px solid var(--rq-line);position:sticky;top:0;z-index:20}
.rq-nav{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:76px}
.rq-brand{display:flex;align-items:center;gap:12px;text-decoration:none;color:var(--rq-primary);min-width:0}
.rq-brand img{height:44px;width:auto;max-width:160px;object-fit:contain}
.rq-brand span{font-family:var(--rq-heading);font-weight:700;font-size:18px;line-height:1.3;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.rq-links{display:none;align-items:center;gap:clamp(16px,2.4vw,32px);font-family:var(--rq-heading);font-size:15px}
.rq-links a{color:var(--rq-muted);text-decoration:none;padding:6px 0;border-bottom:2px solid transparent}
.rq-links a:hover{color:var(--rq-primary)}
.rq-links a[aria-current="page"]{color:var(--rq-primary);border-bottom-color:var(--rq-sky)}
.rq-nav-actions{display:flex;align-items:center;gap:10px;flex-shrink:0}
.rq-menu{position:relative}
.rq-menu summary{list-style:none;cursor:pointer;display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:6px;border:1px solid var(--rq-line-strong);color:var(--rq-primary)}
.rq-menu summary::-webkit-details-marker{display:none}
.rq-menu-panel{position:absolute;inset-inline-end:0;top:52px;background:var(--rq-surface);border:1px solid var(--rq-line);border-radius:8px;box-shadow:0 20px 40px -24px rgba(var(--rq-dark-rgb),.45);padding:8px;min-width:220px;display:flex;flex-direction:column}
.rq-menu-panel a{padding:12px 14px;border-radius:6px;text-decoration:none;font-family:var(--rq-heading);font-size:15px;color:var(--rq-ink)}
.rq-menu-panel a:hover{background:var(--rq-tint);color:var(--rq-primary)}
@media (min-width:900px){.rq-links{display:flex}.rq-menu{display:none}}

/* Buttons */
.rq-btn{display:inline-flex;align-items:center;justify-content:center;gap:10px;min-height:48px;padding:12px 22px;border-radius:4px;font-family:var(--rq-heading);font-weight:600;font-size:16px;line-height:1.2;text-decoration:none;border:1px solid transparent;cursor:pointer;transition:background .15s,color .15s,border-color .15s}
.rq-btn--primary{background:var(--rq-primary);color:#FFFFFF}
.rq-btn--primary:hover{background:var(--rq-primary-dark);color:#FFFFFF}
.rq-btn--ghost{background:transparent;color:var(--rq-primary);border-color:var(--rq-line-strong)}
.rq-btn--ghost:hover{border-color:var(--rq-primary);color:var(--rq-primary)}
.rq-btn--whatsapp{background:#E8F7EE;color:#11643A;border-color:rgba(17, 100, 58,.2)}
.rq-btn--whatsapp:hover{background:#D4F0DF;color:#0B4D2C}
.rq-btn--sm{min-height:40px;padding:8px 16px;font-size:15px}
.rq-btn--block{width:100%}
.rq-link{color:var(--rq-primary);text-decoration:none;font-family:var(--rq-heading);font-size:16px;padding:8px 2px;border-bottom:1px solid rgba(var(--rq-dark-rgb),.3)}
.rq-link:hover{color:var(--rq-accent);border-bottom-color:var(--rq-accent)}

/* Type */
.rq-eyebrow{display:flex;align-items:center;gap:12px;font-family:var(--rq-heading);font-size:14px;color:var(--rq-accent);letter-spacing:.5px;margin:0}
.rq-eyebrow::before{content:"";width:28px;height:2px;background:var(--rq-sky);flex-shrink:0}
.rq-eyebrow--plain::before{display:none}
.rq-h1{margin:0;font-family:var(--rq-heading);font-weight:700;font-size:clamp(30px,4vw,52px);line-height:1.3;letter-spacing:-.3px;color:var(--rq-primary);text-wrap:balance}
.rq-h2{margin:0;font-family:var(--rq-heading);font-weight:700;font-size:clamp(28px,3.4vw,46px);line-height:1.3;color:var(--rq-primary);text-wrap:balance}
.rq-h3{margin:0;font-family:var(--rq-heading);font-weight:600;font-size:clamp(18px,1.6vw,21px);line-height:1.45;color:var(--rq-primary)}
.rq-lead{margin:0;font-size:clamp(17px,1.4vw,20px);line-height:1.8;color:var(--rq-muted);text-wrap:pretty;max-width:680px}
.rq-muted{color:var(--rq-muted)}

/* Sections */
.rq-section{padding-block:clamp(56px,8vw,112px)}
.rq-section--white{background:var(--rq-surface);border-top:1px solid var(--rq-line)}
.rq-section--tint{background:var(--rq-page);border-top:1px solid var(--rq-line)}
.rq-section-head{display:flex;flex-wrap:wrap;align-items:end;justify-content:space-between;gap:20px 32px;margin-bottom:clamp(28px,4vw,48px)}
.rq-section-head > div{display:flex;flex-direction:column;gap:16px;max-width:720px}

/* Hero */
.rq-hero{background:var(--rq-surface);overflow:hidden}
.rq-hero-grid{display:grid;grid-template-columns:minmax(0,1fr);gap:clamp(32px,4vw,56px);align-items:center;padding-block:clamp(40px,7vw,96px)}
@media (min-width:960px){.rq-hero-grid{grid-template-columns:minmax(0,1.05fr) minmax(0,1fr)}}
.rq-hero-copy{display:flex;flex-direction:column;gap:24px}
.rq-hero-actions{display:flex;flex-wrap:wrap;align-items:center;gap:14px}
.rq-hero-facts{display:flex;flex-wrap:wrap;align-items:center;gap:8px 14px;padding-top:20px;border-top:1px solid var(--rq-line);font-size:15px;color:var(--rq-muted)}
.rq-hero-facts strong{font-family:var(--rq-heading);color:var(--rq-primary);font-weight:700}
.rq-dot{color:var(--rq-light)}
.rq-hero-visual{position:relative;min-width:0}
.rq-plate{position:absolute;top:6%;inset-inline-end:-3%;bottom:-6%;inset-inline-start:6%;border-radius:6px;background:var(--rq-plate)}
.rq-hero-frame{position:relative;border-radius:6px;overflow:hidden;background:var(--rq-surface);border:1px solid var(--rq-line);box-shadow:0 30px 60px -36px rgba(var(--rq-dark-rgb),.5)}
.rq-hero-frame img{width:100%;aspect-ratio:16/11;object-fit:cover}
.rq-next{position:relative;background:var(--rq-surface);border:1px solid var(--rq-line);border-radius:6px;box-shadow:0 30px 60px -36px rgba(var(--rq-dark-rgb),.5);padding:8px 20px}
.rq-next-title{font-family:var(--rq-heading);font-size:14px;color:var(--rq-accent);padding:14px 0 6px;margin:0}
.rq-next ol{list-style:none;margin:0;padding:0}
.rq-next li{border-top:1px solid var(--rq-line)}
.rq-next li:first-child{border-top:0}
.rq-next a{display:grid;grid-template-columns:64px minmax(0,1fr);gap:14px;align-items:center;padding:14px 0;text-decoration:none}
.rq-next-date{display:flex;flex-direction:column;align-items:center;justify-content:center;height:64px;border-radius:6px;background:var(--rq-tint);color:var(--rq-primary);font-family:var(--rq-heading);line-height:1.2;text-align:center}
.rq-next-date b{font-size:22px}
.rq-next-date span{font-size:12px}
.rq-next-name{font-family:var(--rq-heading);font-weight:600;color:var(--rq-primary);font-size:16px;line-height:1.45}
.rq-next-meta{font-size:14px;color:var(--rq-muted)}
.rq-hero-placeholder{position:relative;display:flex;align-items:center;justify-content:center;aspect-ratio:16/11;border-radius:6px;background:var(--rq-surface);border:1px solid var(--rq-line)}
.rq-hero-placeholder img{max-width:55%;max-height:55%;object-fit:contain}

/* Grids and cards */
.rq-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(min(100%,300px),1fr));gap:clamp(18px,2.2vw,28px)}
.rq-card{display:flex;flex-direction:column;min-width:0;background:var(--rq-surface);border:1px solid var(--rq-line);border-radius:8px;overflow:hidden;transition:border-color .15s,box-shadow .15s}
.rq-card:hover{border-color:var(--rq-line-strong);box-shadow:0 24px 44px -32px rgba(var(--rq-dark-rgb),.55)}
.rq-section--white .rq-card{background:var(--rq-page);border-color:transparent}
.rq-section--white .rq-card:hover{border-color:var(--rq-line)}
.rq-card-media{position:relative;aspect-ratio:16/10;background:var(--rq-tint);overflow:hidden}
.rq-card-media img{width:100%;height:100%;object-fit:cover}
.rq-card-placeholder{display:flex;align-items:center;justify-content:center;width:100%;height:100%;color:rgba(var(--rq-primary-rgb),.35);background:linear-gradient(135deg,var(--rq-tint) 0%,var(--rq-plate) 100%)}
.rq-card-body{display:flex;flex-direction:column;gap:10px;padding:20px 22px 22px;flex:1}
.rq-card-kicker{font-size:14px;color:var(--rq-faint);margin:0}
.rq-card-title a{text-decoration:none}
.rq-card-title a:hover{color:var(--rq-accent)}
.rq-card-text{margin:0;font-size:16px;line-height:1.7;color:var(--rq-muted);text-wrap:pretty}
.rq-card-foot{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin-top:auto;padding-top:14px;border-top:1px solid var(--rq-line)}
.rq-card-actions{display:flex;flex-wrap:wrap;gap:8px}
.rq-chip{display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:500;line-height:1;padding:7px 12px;border-radius:999px;border:1px solid var(--rq-line-strong);color:var(--rq-ink);background:var(--rq-surface)}
.rq-chip--status{position:absolute;top:12px;inset-inline-start:12px;border-color:transparent;background:rgba(255, 255, 255,.94);color:var(--rq-primary);font-family:var(--rq-heading)}
.rq-chip--active{color:var(--rq-success)}
.rq-chip--featured{position:absolute;top:12px;inset-inline-start:12px;border-color:transparent;background:var(--rq-primary);color:#FFFFFF;font-family:var(--rq-heading)}
.rq-meta{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px;font-size:15px;color:var(--rq-muted)}
.rq-meta li{display:flex;align-items:flex-start;gap:8px}
.rq-meta svg{flex-shrink:0;margin-top:4px;color:var(--rq-accent)}
.rq-price{font-family:var(--rq-heading);font-weight:700;color:var(--rq-primary);font-size:18px}
.rq-price--free{color:var(--rq-success)}
.rq-seats{font-size:14px;color:var(--rq-muted)}
.rq-empty{padding:40px 24px;text-align:center;background:var(--rq-surface);border:1px dashed var(--rq-line-strong);border-radius:8px;color:var(--rq-muted)}

/* Page header (inner pages) */
.rq-page-head{background:var(--rq-surface);border-bottom:1px solid var(--rq-line);padding-block:clamp(36px,5vw,64px)}
.rq-page-head .rq-wrap{display:flex;flex-direction:column;gap:16px}
.rq-crumbs{display:flex;flex-wrap:wrap;gap:6px;font-size:14px;color:var(--rq-faint);margin:0;padding:0;list-style:none}
.rq-crumbs a{color:var(--rq-muted);text-decoration:none}
.rq-crumbs a:hover{color:var(--rq-primary)}
.rq-crumbs li+li::before{content:"/";margin-inline-end:6px;color:var(--rq-line-strong)}

/* Filters */
.rq-filters{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,190px),1fr));gap:16px 14px;align-items:end;padding:20px 22px;background:var(--rq-tint);border:1px solid var(--rq-line);border-radius:12px;margin-bottom:32px}
.rq-field{display:flex;flex-direction:column;gap:8px;min-width:0}
.rq-field label{font-family:var(--rq-heading);font-size:12px;font-weight:500;color:var(--rq-ink)}
/* Same look as the app's own fields (formPlainControl.css): 42px, 4px corners, a quiet hairline, and a border-colour change on focus with no ring. */
.rq-field select{width:100%;min-height:42px;padding:0 14px;padding-inline-end:40px;appearance:none;-webkit-appearance:none;border:1px solid var(--rq-line-strong);border-radius:4px;background-color:var(--rq-surface);background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='18' height='18' viewBox='0 0 24 24' fill='none' stroke='%2378838C' stroke-width='2.2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;color:var(--rq-ink);font-family:var(--rq-body);font-size:14px;cursor:pointer;transition:border-color .15s}
[dir=rtl] .rq-field select{background-position:left 14px center}
.rq-field select:hover{border-color:var(--rq-faint)}
.rq-field select:focus-visible{outline:0;border-color:var(--rq-primary);box-shadow:none}
.rq-filter-actions{display:flex;gap:14px;align-items:center;flex-wrap:wrap;min-height:42px}
.rq-filter-actions .rq-btn{min-height:42px;padding-block:8px}

/* Detail pages */
.rq-detail{display:grid;grid-template-columns:minmax(0,1fr);gap:clamp(28px,4vw,56px);align-items:start}
@media (min-width:980px){.rq-detail{grid-template-columns:minmax(0,1fr) 380px}}
.rq-detail-media{border-radius:8px;overflow:hidden;border:1px solid var(--rq-line);background:var(--rq-tint)}
.rq-detail-media img{width:100%;max-height:520px;object-fit:cover}
.rq-prose{font-size:17px;line-height:1.85;color:var(--rq-ink);overflow-wrap:anywhere}
.rq-prose h1,.rq-prose h2,.rq-prose h3{font-family:var(--rq-heading);color:var(--rq-primary);line-height:1.4}
.rq-prose a{color:var(--rq-accent)}
.rq-prose ul,.rq-prose ol{padding-inline-start:1.4em}
.rq-panel{background:var(--rq-surface);border:1px solid var(--rq-line);border-radius:8px;padding:24px;display:flex;flex-direction:column;gap:18px}
@media (min-width:980px){.rq-panel--sticky{position:sticky;top:100px}}
.rq-facts{margin:0;display:flex;flex-direction:column}
.rq-facts div{display:grid;grid-template-columns:110px minmax(0,1fr);gap:12px;padding:12px 0;border-top:1px solid var(--rq-line)}
.rq-facts div:first-child{border-top:0;padding-top:0}
.rq-facts dt{font-size:14px;color:var(--rq-faint)}
.rq-facts dd{margin:0;font-weight:500;color:var(--rq-ink)}
.rq-tags{display:flex;flex-wrap:wrap;gap:8px;margin:0;padding:0;list-style:none}
.rq-note{margin:0;font-size:14px;color:var(--rq-muted)}
.rq-block{display:flex;flex-direction:column;gap:16px}

/* About and contact */
.rq-about{display:grid;grid-template-columns:minmax(0,1fr);gap:clamp(28px,5vw,80px);align-items:start}
@media (min-width:960px){.rq-about{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}}
.rq-about-text{margin:0;font-size:clamp(17px,1.4vw,20px);line-height:1.9;color:var(--rq-ink);white-space:pre-line}
.rq-contact{list-style:none;margin:0;padding:0;display:flex;flex-direction:column}
.rq-contact li{display:grid;grid-template-columns:44px minmax(0,1fr);gap:14px;align-items:center;padding:16px 0;border-bottom:1px solid var(--rq-line)}
.rq-contact li:first-child{padding-top:0}
.rq-contact-icon{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:10px;background:var(--rq-primary);color:#FFFFFF}
.rq-contact small{display:block;font-size:13px;color:var(--rq-faint)}
.rq-contact a,.rq-contact span{color:var(--rq-ink);text-decoration:none;font-weight:500;overflow-wrap:anywhere}
.rq-contact .rq-contact-icon{color:#FFFFFF}
.rq-contact-line{display:block}
.rq-contact .rq-contact-number{display:flex;flex-wrap:wrap;align-items:baseline;gap:4px 10px;padding:2px 0}
.rq-contact-number em{font-style:normal;font-size:13px;font-weight:400;color:var(--rq-faint)}
.rq-footer-label{font-size:13px;color:rgba(255, 255, 255,.7);margin-inline-start:6px}
.rq-contact a:hover{color:var(--rq-primary)}

/* CTA band */
.rq-band{background:var(--rq-plate);color:var(--rq-primary)}
.rq-band .rq-wrap{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:20px 32px;padding-block:clamp(40px,6vw,72px)}

/* Footer */
.rq-footer{background:var(--rq-primary);color:#FFFFFF}
.rq-footer-main{display:grid;grid-template-columns:minmax(0,1fr);gap:28px;padding-block:40px}
@media (min-width:800px){.rq-footer-main{grid-template-columns:minmax(0,1.3fr) minmax(0,1fr) minmax(0,1fr)}}
.rq-footer h2{margin:0 0 10px;font-family:var(--rq-heading);font-size:18px;font-weight:700}
.rq-footer p{margin:0;color:rgba(255, 255, 255,.82);font-size:15px}
.rq-footer ul{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:6px;font-size:15px}
.rq-footer a{color:#FFFFFF;text-decoration:none}
.rq-footer a:hover{color:rgba(255, 255, 255,.75)}
.rq-footer-bar{border-top:1px solid rgba(255, 255, 255,.18)}
.rq-footer-bar .rq-wrap{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px 20px;padding-block:16px;font-size:14px;color:rgba(255, 255, 255,.82)}
.rq-powered{display:inline-flex;align-items:center;gap:10px}
.rq-powered img{height:22px;width:auto}

/* Pagination */
.rq-pagination{display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin-top:32px}
.rq-pagination a,.rq-pagination span{display:inline-flex;align-items:center;justify-content:center;min-width:44px;min-height:44px;padding:0 12px;border-radius:4px;border:1px solid var(--rq-line-strong);text-decoration:none;font-family:var(--rq-heading);font-size:15px;color:var(--rq-primary);background:var(--rq-surface)}
.rq-pagination span[aria-current]{background:var(--rq-primary);border-color:var(--rq-primary);color:#FFFFFF}
.rq-pagination span.is-disabled{color:var(--rq-faint);border-color:var(--rq-line)}

.rq-floating-whatsapp{position:fixed;inset-inline-start:16px;bottom:16px;z-index:30;display:flex;align-items:center;justify-content:center;width:56px;height:56px;border-radius:50%;background:#25D366;color:#FFFFFF;box-shadow:0 14px 28px -12px rgba(0, 0, 0,.45)}
.rq-floating-whatsapp:hover{background:#1EBE5B;color:#FFFFFF}
/* tulabi additions (same tokens): the owner's banner, the instructor photo, the request form, messages */
.tl-owner-bar{display:flex;flex-wrap:wrap;justify-content:center;gap:12px;padding:10px 16px;background:#FBF1E0;color:#A45F00;font-size:15px}
.tl-owner-bar a{color:inherit;font-weight:700}
.rq-brand img.tl-brand-photo{width:44px;height:44px;max-width:none;border-radius:50%;object-fit:cover}
.tl-about-photo{width:160px;height:160px;border-radius:50%;object-fit:cover;border:1px solid var(--rq-line)}
.tl-placeholder-mark{display:flex;align-items:center;justify-content:center;width:120px;height:120px;border-radius:50%;background:var(--rq-plate);color:var(--rq-primary);font-family:var(--rq-heading);font-size:56px;font-weight:700}
.tl-powered-mark{display:inline-flex;align-items:center;gap:6px;font-family:var(--rq-heading);font-weight:700;color:#fff}
.tl-powered-mark b{display:inline-flex;align-items:center;justify-content:center;width:22px;height:22px;border-radius:6px;background:#fff;color:var(--rq-primary-dark);font-size:13px}
.tl-request{display:flex;flex-direction:column;gap:12px}
.rq-field textarea{width:100%;min-height:84px;padding:10px 14px;border:1px solid var(--rq-line-strong);border-radius:4px;background:var(--rq-surface);color:var(--rq-ink);font-family:var(--rq-body);font-size:14px;resize:vertical;transition:border-color .15s}
.rq-field textarea:focus-visible{outline:0;border-color:var(--rq-primary)}
.rq-field > span{font-family:var(--rq-heading);font-size:12px;font-weight:500;color:var(--rq-ink)}
.tl-alert{margin:0;padding:10px 14px;border-radius:6px;font-size:15px}
.tl-alert--success{background:var(--rq-success-bg);color:var(--rq-success)}
.tl-alert--error{background:#FCEBEA;color:#B42318}
@media (prefers-reduced-motion:reduce){html{scroll-behavior:auto}*{transition:none!important}}
</style>
