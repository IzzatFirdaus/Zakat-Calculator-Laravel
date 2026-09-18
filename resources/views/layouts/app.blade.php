<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('meta.description') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', __('app.name'))</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    <script>
        (function () {
            var saved = localStorage.getItem('theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            var isDark = saved === 'dark' || (!saved && prefersDark);
            var theme = isDark ? 'dark' : 'light';
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.dataset.theme = theme;
            var toggle = document.querySelector('[data-theme-toggle]');
            if (toggle) {
                toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            }
        })();
    </script>
</head>
<body>
    <a href="#main-content" class="skip-link">{{ __('layout.skipToContent') }}</a>
    <header class="app-header">
        <a href="{{ route('calculator.index') }}" class="app-header__brand" aria-label="{{ __('app.name') }}">
            <svg class="app-header__mark" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M16 2.5 19 13l10.5 3L19 19l-3 10.5L13 19 2.5 16 13 13l3-10.5Z"/>
                <path d="m16 8 1.7 6.3L24 16l-6.3 1.7L16 24l-1.7-6.3L8 16l6.3-1.7L16 8Z"/>
            </svg>
            <span>{{ __('app.name') }}</span>
        </a>
        <nav class="app-header__nav" aria-label="{{ __('layout.primaryNav') }}">
            <a href="{{ route('calculator.index') }}" class="app-header__link @if (request()->routeIs('calculator.index')) app-header__link--active @endif" @if (request()->routeIs('calculator.index')) aria-current="page" @endif>{{ __('nav.calculator') }}</a>
            <a href="{{ route('calculator.about') }}" class="app-header__link @if (request()->routeIs('calculator.about')) app-header__link--active @endif" @if (request()->routeIs('calculator.about')) aria-current="page" @endif>{{ __('nav.about') }}</a>
            <form action="{{ route('language.update') }}" method="GET" class="app-header__language" data-language-form>
                <label class="sr-only" for="language-select">{{ __('layout.languageLabel') }}</label>
                <select id="language-select" name="locale" class="app-header__select" data-language-select>
                    <option value="en" @selected(app()->getLocale() === 'en')>English</option>
                    <option value="ms" @selected(app()->getLocale() === 'ms')>Bahasa Melayu</option>
                </select>
                <button type="submit" class="app-header__toggle" data-language-submit>{{ __('layout.languageSubmit') }}</button>
            </form>
            <button
                type="button"
                data-theme-toggle
                class="app-header__toggle"
                aria-pressed="false"
                aria-label="{{ __('layout.themeToggle') }}"
                hidden
            >
                <svg class="app-header__toggle-icon app-header__toggle-icon--sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41"/>
                </svg>
                <svg class="app-header__toggle-icon app-header__toggle-icon--moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                </svg>
            </button>
        </nav>
    </header>

    <main id="main-content" class="app-main" tabindex="-1">
        @yield('content')
    </main>

    <footer class="app-footer">
        <p>{{ __('footer.privacy') }}</p>
    </footer>
</body>
</html>
