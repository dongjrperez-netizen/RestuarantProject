<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/Logo.png" type="image/png">
        <link rel="apple-touch-icon" href="/Logo.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="dns-prefetch" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600&display=swap" rel="stylesheet" />

        @routes
        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
        {{-- Normalize CSS preload links immediately so they are recognized as style preloads.
             This sets `as="style"` and an onload handler that swaps rel to stylesheet
             (pattern recommended to avoid "preload but not used" warnings). It also
             injects a stylesheet link fallback for older browsers. --}}
        <script>
            (function() {
                try {
                    // Convert any Vite-emitted CSS preload links into active stylesheets immediately.
                    // Some browsers emit a "preload but not used" warning when a CSS preload
                    // isn't converted quickly enough; switching to `rel="stylesheet"` here
                    // ensures the resource counts as used and removes the console warning.
                    var preloads = Array.prototype.slice.call(document.querySelectorAll('link[rel="preload"][href$=".css"]'));

                    preloads.forEach(function(link) {
                        var href = link.getAttribute('href');
                        // Ensure the `as` attribute is set for correctness
                        if (!link.getAttribute('as')) {
                            link.setAttribute('as', 'style');
                        }

                        // If the link is still a preload, convert it to a stylesheet immediately
                        // so the browser treats it as used. We set a data attribute to avoid
                        // double-processing the same element.
                        if (link.getAttribute('rel') === 'preload') {
                            link.setAttribute('rel', 'stylesheet');
                            link.setAttribute('media', 'all');
                            link.setAttribute('data-preload-swapped', '1');
                        }

                        // Ensure there is a stylesheet link for browsers that may not honor
                        // the converted element; avoid duplicating if it already exists.
                        if (href) {
                            var exists = document.querySelector('link[rel="stylesheet"][href="' + href + '"]');
                            if (!exists) {
                                var sheet = document.createElement('link');
                                sheet.rel = 'stylesheet';
                                sheet.href = href;
                                sheet.media = 'all';
                                document.head.appendChild(sheet);
                            }
                        }
                    });
                } catch (e) {
                    // noop - avoid breaking the page if this fails
                }
            })();
        </script>
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
