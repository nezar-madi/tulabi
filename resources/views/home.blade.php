<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>طلابي - منصة المدرّسين المستقلين في ليبيا</title>
<meta name="description" content="صفحة باسمك لفصولك، وطلاب يطلبون الانضمام من هواتفهم، ودفعات وإيصالات بالدينار الليبي في مكان واحد.">
<meta name="theme-color" content="#00613E">
<meta property="og:type" content="website">
<meta property="og:locale" content="ar_LY">
<meta property="og:site_name" content="طلابي">
<meta property="og:title" content="طلابي — صفحة لفصولك، وطلابك يسجّلون فيها">
<meta property="og:description" content="للمدرّسين المستقلين في ليبيا: صفحة لفصولك، وطلبات انضمام تقبلها بنفسك، ودفعات وإيصالات.">
<meta property="og:url" content="{{ url('/') }}">
<link rel="preload" href="/fonts/site/07086eb9-b3d0-44f1-b3c5-9929c9360f78.woff2" as="font" type="font/woff2" crossorigin>
@include('landing.styles')
<style>
/* tulabi: the text logo, and the instructor-page replica in the hero */
.brand { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-family: var(--display); font-weight: 700; font-size: 24px; color: var(--blue); }
.brand b { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 8px; background: var(--blue); color: #fff; font-size: 19px; }
.site-foot .brand { color: #fff; font-size: 20px; }
.site-foot .brand b { width: 26px; height: 26px; background: #fff; color: var(--blue-deep); font-size: 15px; }
.ip { border-radius: 10px; overflow: hidden; background: var(--white); border: 1px solid var(--line); box-shadow: var(--shadow-lift); }
.ip__head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 18px; border-bottom: 1px solid var(--line); }
.ip__who { display: flex; align-items: center; gap: 10px; font-family: var(--display); font-weight: 700; color: var(--blue); }
.ip__who i { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 50%; background: var(--tint); font-style: normal; }
.ip__btn { padding: 6px 12px; border-radius: 4px; background: var(--blue); color: #fff; font-family: var(--display); font-size: 12px; font-weight: 600; }
.ip__hero { display: grid; gap: 6px; padding: 22px 18px 18px; }
.ip__hero small { font-family: var(--display); font-size: 12px; color: var(--blue-accent); }
.ip__hero b { font-family: var(--display); font-size: 24px; color: var(--blue); line-height: 1.3; }
.ip__hero span { font-size: 13px; color: var(--muted); }
.ip__grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; padding: 0 18px 18px; }
.ip__card { display: grid; gap: 6px; padding: 14px; border-radius: 8px; background: var(--page); }
.ip__card em { justify-self: start; padding: 2px 10px; border-radius: 999px; background: var(--white); color: #15803D; font-style: normal; font-family: var(--display); font-size: 11px; }
.ip__card b { font-family: var(--display); font-size: 14px; color: var(--blue); }
.ip__card small { font-size: 12px; color: var(--muted); }
.ip__card strong { font-family: var(--display); font-size: 15px; color: var(--blue); }
@media (max-width: 560px) { .ip__grid { grid-template-columns: 1fr; } }
</style>
</head>
<body class="no-js">
<a class="skip" href="#main">تخطَّ إلى المحتوى</a>

@include('landing.icons')

<header class="site-head" id="top">
  <div class="wrap nav">
    <a class="brand" href="#top" aria-label="طلابي — الصفحة الرئيسية"><b>ط</b>طلابي</a>
    <nav class="nav__links" aria-label="أقسام الصفحة">
      <a href="#features">المزايا</a>
      <a href="#students">للطلاب</a>
      <a href="#start">كيف تبدأ</a>
      <a href="#faq">الأسئلة الشائعة</a>
    </nav>
    <div class="nav__actions">
      @if ($user)
        <a class="btn btn--ghost btn--sm" href="{{ $user->isInstructor() ? route('teach.dashboard') : route('student.classes.index') }}">حسابي</a>
      @else
        <a class="btn btn--ghost btn--sm" href="{{ route('login') }}">الدخول</a>
      @endif
      <a class="btn btn--primary btn--sm" href="{{ route('join') }}">انضم كمدرّس</a>
      <details class="nav__menu">
        <summary aria-label="القائمة"><svg><use href="#i-menu"/></svg></summary>
        <div class="nav__panel">
          <a href="#features">المزايا</a>
          <a href="#students">للطلاب</a>
          <a href="#start">كيف تبدأ</a>
          <a href="#faq">الأسئلة الشائعة</a>
        </div>
      </details>
    </div>
  </div>
</header>

<main id="main">

<!-- Hero -->
<section class="hero" aria-labelledby="hero-title">
  <div class="wrap hero__grid">
    <div class="hero__copy">
      <span class="badge"><i class="badge__dot" aria-hidden="true"></i><b>وصول مبكر</b><i class="badge__sep" aria-hidden="true"></i>متاح الآن لعدد محدود من المدرّسين</span>
      <h1 id="hero-title">صفحة لفصولك، وطلابك يسجّلون فيها</h1>
      <p class="hero__lead">اعرض فصولك على صفحة باسمك، واقبل الطلاب، وسجّل دفعاتهم وأصدر إيصالاتهم من مكان واحد.</p>
      <div class="hero__cta">
        <a class="btn btn--primary" href="{{ route('join') }}">انضم كمدرّس<svg><use href="#i-arrow"/></svg></a>
        <a class="btn btn--ghost" href="{{ route('register') }}">سجّل كطالب ←</a>
      </div>
      <div class="hero__facts">
        <span><svg><use href="#i-bolt"/></svg>صفحتك جاهزة في دقائق</span>
        <span><svg><use href="#i-devices"/></svg>يعمل من الحاسوب والجوال</span>
        <span><svg><use href="#i-cloud"/></svg>لا يحتاج إلى تثبيت</span>
      </div>
    </div>
    <div class="showcase" aria-hidden="true">
      <div class="shot">
        <div class="shot__bar"><i></i><i></i><i></i><span>tulabi.ly/ahmed-math</span></div>
        <div class="ip" style="border:0;border-radius:0;box-shadow:none">
          <div class="ip__head">
            <span class="ip__who"><i>أ</i>أحمد المبروك</span>
            <span class="ip__btn">تواصل</span>
          </div>
          <div class="ip__hero">
            <small>رياضيات · طرابلس</small>
            <b>أحمد المبروك</b>
            <span>مدرّس رياضيات للمرحلة الثانوية منذ عشر سنوات.</span>
          </div>
          <div class="ip__grid">
            <div class="ip__card"><em>التسجيل مفتوح</em><b>رياضيات الثالث الثانوي</b><small>السبت، الإثنين · 5:00 م - 7:00 م</small><strong>300 د.ل</strong></div>
            <div class="ip__card"><em>التسجيل مفتوح</em><b>مراجعة الامتحانات (عن بعد)</b><small>الأحد، الثلاثاء · 8:00 م - 9:30 م</small><strong>150 د.ل</strong></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Features -->
<section class="section" id="features" aria-labelledby="features-title">
  <div class="wrap">
    <div class="section__head reveal">
      <span class="eyebrow">ما يفعله طلابي كل يوم</span>
      <h2 id="features-title">كل ما تحتاجه لتدريس فصولك</h2>
      <p class="lead">صفحتك، وطلبات الانضمام، والدفعات — بلا دفاتر ولا رسائل متفرقة.</p>
    </div>

    <div class="timeline">
      <article class="step reveal">
        <span class="step__dot"><svg><use href="#i-world"/></svg></span>
        <div class="step__body">
          <div class="step__text">
            <span class="eyebrow">اعرض</span>
            <h3>صفحة باسمك</h3>
            <p>رابط واحد تشاركه مع طلابك: صورتك ونبذتك وتخصصك وفصولك المفتوحة وزر واتساب.</p>
            <ul class="ticks">
              <li>عنوان تختاره بنفسك: tulabi.ly/اسمك</li>
              <li>فصول حضورية بمكانها أو عن بعد برابط الحصة</li>
              <li>رابط الحصة لا يراه إلا الطلاب المقبولون</li>
            </ul>
          </div>
          <div class="stage-box stage-box--tint">
            <div class="cl-app">
              <div class="cl-head"><div><span class="cl-crumb">الرئيسية › الفصول</span><b class="cl-title">الفصول</b></div><span class="cl-new"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/></svg>فصل جديد</span></div>
              <div class="cl-rows">
                <div class="cl-row cl-row--head"><span>الفصل</span><span>المواعيد</span><span>السعر</span><span>الحالة</span></div>
                <div class="cl-row"><span class="cl-name"><i class="cl-ico" style="--c:#00613E">ر</i><span><b>رياضيات الثالث الثانوي</b><small>حضوري · طرابلس</small></span></span><span><small>السبت، الإثنين</small></span><span><small>300 د.ل</small></span><span><i class="st st--green">منشور</i></span></div>
                <div class="cl-row"><span class="cl-name"><i class="cl-ico" style="--c:#0E7C86">م</i><span><b>مراجعة الامتحانات</b><small>عن بعد</small></span></span><span><small>الأحد، الثلاثاء</small></span><span><small>150 د.ل</small></span><span><i class="st st--green">منشور</i></span></div>
                <div class="cl-row"><span class="cl-name"><i class="cl-ico" style="--c:#6D4AB5">ف</i><span><b>فيزياء الثاني الثانوي</b><small>حضوري · طرابلس</small></span></span><span><small>الأربعاء</small></span><span><small>250 د.ل</small></span><span><i class="st st--grey">مسودة</i></span></div>
              </div>
            </div>
          </div>
        </div>
      </article>

      <article class="step reveal">
        <span class="step__dot"><svg><use href="#i-user-plus"/></svg></span>
        <div class="step__body">
          <div class="step__text">
            <span class="eyebrow">اقبل</span>
            <h3>طلبات الانضمام</h3>
            <p>يطلب الطالب الانضمام من صفحة الفصل، ويصلك الطلب مع ملاحظته. تقبله أو ترفضه بنفسك.</p>
            <ul class="ticks">
              <li>زر واتساب جاهز للتواصل مع كل طالب</li>
              <li>تنبيه إذا امتلأت المقاعد، والقبول يبقى قرارك</li>
              <li>الطالب يرى حالة طلبه في «فصولي»</li>
            </ul>
          </div>
          <div class="stage-box stage-box--tint">
            <div class="cl-app">
              <div class="cl-head"><div><span class="cl-crumb">الرئيسية › طلبات الانضمام</span><b class="cl-title">طلبات الانضمام</b></div></div>
              <div class="cl-rows">
                <div class="cl-row cl-row--head"><span>الطالب</span><span>الفصل</span><span>الهاتف</span><span>الحالة</span></div>
                <div class="cl-row"><span class="cl-name"><i class="cl-ico" style="--c:#00613E">س</i><span><b>سارة أحمد</b><small>هل يناسب الفصل الثاني الثانوي؟</small></span></span><span><small>رياضيات الثالث</small></span><span><small dir="ltr">091-2345678</small></span><span><i class="st st--blue">جديد</i></span></div>
                <div class="cl-row"><span class="cl-name"><i class="cl-ico" style="--c:#0E7C86">م</i><span><b>محمد علي</b><small>—</small></span></span><span><small>مراجعة الامتحانات</small></span><span><small dir="ltr">092-1112233</small></span><span><i class="st st--green">مقبول</i></span></div>
              </div>
            </div>
          </div>
        </div>
      </article>

      <article class="step reveal">
        <span class="step__dot"><svg><use href="#i-receipt"/></svg></span>
        <div class="step__body">
          <div class="step__text">
            <span class="eyebrow">حصّل</span>
            <h3>الدفعات والإيصالات</h3>
            <p>سجّل ما دفعه كل طالب بالدينار الليبي، واعرف المتبقي عليه، وأرسل له إيصالًا برقم لا يتكرر.</p>
            <ul class="ticks">
              <li>إيصال PDF لكل دفعة يفتحه الطالب من حسابه</li>
              <li>الدفعة تُلغى بسبب ولا تُحذف</li>
              <li>المحصّل هذا الشهر والمتبقي على الطلاب في الرئيسية</li>
            </ul>
          </div>
          <div class="stage-box stage-box--tint">
            <div class="cl-app">
              <div class="cl-head"><div><span class="cl-crumb">الفصول › رياضيات الثالث الثانوي</span><b class="cl-title">دفعات سارة أحمد</b></div></div>
              <div class="cl-rows">
                <div class="cl-row cl-row--head"><span>الإيصال</span><span>التاريخ</span><span>المبلغ</span><span>الحالة</span></div>
                <div class="cl-row"><span class="cl-name"><i class="cl-ico" style="--c:#00613E"><svg width="16" height="16"><use href="#i-receipt"/></svg></i><span><b dir="ltr">TL-20261110-0001</b><small>نقدًا</small></span></span><span><small>10 نوفمبر</small></span><span><small>150 د.ل</small></span><span><i class="st st--green">مدفوعة</i></span></div>
                <div class="cl-row"><span class="cl-name"><i class="cl-ico" style="--c:#00613E"><svg width="16" height="16"><use href="#i-receipt"/></svg></i><span><b dir="ltr">TL-20261124-0003</b><small>تحويل</small></span></span><span><small>24 نوفمبر</small></span><span><small>100 د.ل</small></span><span><i class="st st--green">مدفوعة</i></span></div>
              </div>
            </div>
          </div>
        </div>
      </article>
    </div>
  </div>
</section>

<!-- For students -->
<section class="section section--tint" id="students" aria-labelledby="students-title">
  <div class="wrap enroll">
    <div class="reveal">
      <span class="eyebrow">للطلاب</span>
      <h2 id="students-title">طلابك يطلبون الانضمام من هواتفهم</h2>
      <p class="lead">من صفحة الفصل مباشرة، بلا مكالمات ولا نماذج ورقية ولا رسائل متفرقة.</p>
      <ol class="enroll__steps">
        <li><b>يفتح الطالب صفحتك</b><span>يرى فصولك المفتوحة بمواعيدها وأسعارها ومقاعدها.</span></li>
        <li><b>يطلب الانضمام</b><span>برقم هاتفه وكلمة مرور، مع ملاحظة لك إن أراد.</span></li>
        <li><b>يصلك الطلب</b><span>في «طلبات الانضمام» مع زر واتساب للتواصل معه.</span></li>
        <li><b>تقبله بضغطة</b><span>فيرى فصله ورابط الحصة ودفعاته وإيصالاته في حسابه.</span></li>
      </ol>
    </div>
    <div class="reveal" aria-hidden="true">
      <div class="phone">
        <div class="phone__screen">
          <div class="phone__bar"><small>أحمد المبروك</small><b>اطلب الانضمام</b></div>
          <div class="phone__body">
            <div class="phone__course"><span>رياضيات الثالث الثانوي</span><em>300 د.ل</em></div>
            <div class="phone__field">ملاحظة للمدرّس<i class="is-typed">هل يناسب الثاني الثانوي؟</i></div>
            <div class="phone__send">أرسل الطلب</div>
          </div>
        </div>
        <div class="phone__toast"><svg><use href="#i-inbox"/></svg>طلب انضمام جديد</div>
      </div>
    </div>
  </div>
</section>

<!-- Trust -->
<section class="section" aria-labelledby="trust-title">
  <div class="wrap">
    <div class="section__head reveal">
      <h2 id="trust-title">بياناتك وبيانات طلابك لكم</h2>
      <p class="lead">بُني طلابي على نظام رقمنة لمراكز التدريب، وحماية البيانات جزء من تصميمه منذ اليوم الأول.</p>
    </div>
    <div class="trust reveal">
      <div><span class="ico"><svg><use href="#i-lock"/></svg></span><b>اتصال مشفّر</b><span>كل ما يمر بين متصفحك وطلابي مشفّر، وكلمات المرور لا تُحفظ إلا مشفّرة.</span></div>
      <div><span class="ico"><svg><use href="#i-db"/></svg></span><b>كل مدرّس يرى طلابه فقط</b><span>لا يرى أحد فصولك وطلابك ودفعاتهم غيرك.</span></div>
      <div><span class="ico"><svg><use href="#i-shield"/></svg></span><b>مدرّسون معتمدون</b><span>كل مدرّس يُراجَع طلب انضمامه قبل أن تظهر صفحته.</span></div>
      <div><span class="ico"><svg><use href="#i-history"/></svg></span><b>نسخ احتياطية منتظمة</b><span>نسخ احتياطية مشفّرة بشكل دوري، حتى لا يضيع شيء.</span></div>
    </div>
  </div>
</section>

<!-- Steps -->
<section class="section section--tint" id="start" aria-labelledby="start-title">
  <div class="wrap">
    <div class="section__head reveal"><h2 id="start-title">ابدأ في 3 خطوات</h2></div>
    <ol class="steps reveal">
      <li><b>قدّم طلب الانضمام</b><span>اسمك ورقم هاتفك وتخصصك ومدينتك، في دقيقة.</span></li>
      <li><b>جهّز صفحتك</b><span>بعد الموافقة: اختر عنوان صفحتك، وأضف صورتك ونبذتك.</span></li>
      <li><b>انشر فصولك</b><span>شارك رابط صفحتك، واقبل طلبات طلابك وسجّل دفعاتهم.</span></li>
    </ol>
  </div>
</section>

<!-- FAQ -->
<section class="section" id="faq" aria-labelledby="faq-title">
  <div class="wrap">
    <div class="section__head reveal"><h2 id="faq-title">أسئلة شائعة</h2></div>
    <div class="faq reveal">
      <details><summary>هل أحتاج إلى تثبيت أي برنامج؟</summary><p>لا. طلابي يعمل من المتصفح على الحاسوب أو الجوال، لك ولطلابك.</p></details>
      <details><summary>كيف يدفع الطلاب؟</summary><p>يدفع الطالب لك مباشرة نقدًا أو تحويلًا، وتسجّل الدفعة في طلابي فيصله إيصالها. الدفع الإلكتروني يأتي لاحقًا.</p></details>
      <details><summary>هل يمكن أن أكون مدرّسًا وطالبًا معًا؟</summary><p>نعم. حساب واحد لرقم هاتفك، تدرّس فصولك وتسجّل في فصول غيرك.</p></details>
      <details><summary>نسيت كلمة المرور ولا بريد لي، ماذا أفعل؟</summary><p>يرسل لك مدرّسك رابطًا لتعيين كلمة مرور جديدة على واتساب، والإدارة تفعل ذلك للمدرّسين.</p></details>
      <details><summary>ماذا يحدث بعد إرسال طلب الانضمام كمدرّس؟</summary><p>تراجع الإدارة الطلب وتتواصل معك على واتساب، وبعد القبول تجهّز صفحتك وتنشر فصولك.</p></details>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section cta" aria-labelledby="cta-title">
  <div class="wrap cta__inner reveal">
    <h2 id="cta-title">هل أنت مستعد لتنظيم فصولك؟</h2>
    <p>انضم إلى المدرّسين الأوائل في طلابي، واعرض فصولك واقبل طلابك وسجّل دفعاتهم من مكان واحد.</p>
    <div class="cta__actions">
      <a class="btn btn--primary btn--pill" href="{{ route('join') }}">انضم كمدرّس<svg><use href="#i-arrow"/></svg></a>
      <a class="textlink" href="{{ route('register') }}">أنا طالب</a>
    </div>
  </div>
</section>

</main>

<footer class="site-foot">
  <div class="wrap">
    <a class="lockup" href="/" aria-label="طلابي — منصة المدرّسين المستقلين في ليبيا"><span class="brand"><b>ط</b>طلابي</span><i class="lockup__sep" aria-hidden="true"></i><span class="lockup__tag">منصة المدرّسين المستقلين في ليبيا</span></a>
    <nav aria-label="روابط"><a href="#features">المزايا</a><a href="#students">للطلاب</a><a href="#faq">الأسئلة الشائعة</a><a href="{{ route('join') }}">انضم كمدرّس</a><a href="{{ route('login') }}">الدخول</a></nav>
  </div>
</footer>

<script>
(() => {
  document.body.classList.remove('no-js');
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Header shadow after scrolling (rqmana's landing)
  const head = document.querySelector('.site-head');
  const onScroll = () => {
    const on = head.classList.contains('is-scrolled');
    if (!on && scrollY > 24) head.classList.add('is-scrolled');
    else if (on && scrollY < 4) head.classList.remove('is-scrolled');
  };
  addEventListener('scroll', onScroll, { passive: true }); onScroll();

  document.querySelectorAll('.nav__panel a').forEach((a) => a.addEventListener('click', () => a.closest('details').removeAttribute('open')));

  // Reveal sections
  const reveals = document.querySelectorAll('.reveal');
  if (reduced || !('IntersectionObserver' in window)) reveals.forEach((el) => el.classList.add('is-in'));
  else {
    const io = new IntersectionObserver((entries) => entries.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } }), { rootMargin: '0px 0px -8% 0px' });
    reveals.forEach((el) => io.observe(el));
  }
})();
</script>
</body>
</html>
