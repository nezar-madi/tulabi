<!DOCTYPE html>
<html lang="ar" dir="rtl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'طلابي') }}</title>

        <meta name="theme-color" content="#0D4AA8">

        {{-- Apply the stored theme before Vue boots to avoid a flash. --}}
        <script>
            (function () {
                try {
                    var stored = localStorage.getItem('vuetify-theme');
                    var theme = stored === 'dark' ? 'dark' : 'light';

                    if (stored !== null && stored !== 'light' && stored !== 'dark') {
                        localStorage.removeItem('vuetify-theme');
                    }

                    document.documentElement.dataset.theme = theme;
                    document.documentElement.style.colorScheme = theme;
                    document.documentElement.style.backgroundColor = theme === 'dark' ? '#121212' : '#F3F7FC';
                } catch (e) {}
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=alexandria:400,500,600,700,800|tajawal:300,400,500,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body>
        @inertia
    </body>
</html>
