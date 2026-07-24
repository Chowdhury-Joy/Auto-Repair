<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'My Garage · TrueWrench' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full antialiased flex flex-col justify-between">
    <header class="bg-brand-700 text-white shadow-sm" x-data="{ mobileMenuOpen: false }">
        <nav class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="/portal" class="flex items-center gap-2 font-bold text-xl tracking-tight shrink-0">
                <svg class="w-7 h-7 text-accent-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
                TrueWrench
            </a>

            <!-- Mobile menu button -->
            <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="md:hidden inline-flex items-center justify-center p-2 rounded-md text-brand-200 hover:text-white hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" aria-controls="portal-mobile-menu" aria-expanded="false">
                <span class="sr-only">Open main menu</span>
                <svg class="block h-6 w-6" x-show="!mobileMenuOpen" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                <svg class="h-6 w-6" x-show="mobileMenuOpen" style="display: none;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center gap-6 text-sm">
                <a href="/portal" class="hover:text-accent-400">Dashboard</a>
                <a href="/portal/vehicles" class="hover:text-accent-400">Vehicles</a>
                <a href="/portal/appointments" class="hover:text-accent-400">Appointments</a>
                <a href="/portal/invoices" class="hover:text-accent-400">Invoices</a>
                <a href="/book" class="bg-accent-500 hover:bg-accent-600 text-white px-3 py-1.5 rounded font-semibold text-xs shadow">
                    Book Service
                </a>
                <form method="POST" action="/logout" class="inline">
                    @csrf
                    <button type="submit" class="hover:text-accent-400 text-slate-300">Sign out</button>
                </form>
            </div>
        </nav>

        <!-- Mobile Menu -->
        <div class="md:hidden" id="portal-mobile-menu" x-show="mobileMenuOpen" style="display: none;">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 shadow-inner bg-brand-800">
                <a href="/portal" class="hover:text-accent-400 block px-3 py-2 rounded-md text-base font-medium">Dashboard</a>
                <a href="/portal/vehicles" class="hover:text-accent-400 block px-3 py-2 rounded-md text-base font-medium">Vehicles</a>
                <a href="/portal/appointments" class="hover:text-accent-400 block px-3 py-2 rounded-md text-base font-medium">Appointments</a>
                <a href="/portal/invoices" class="hover:text-accent-400 block px-3 py-2 rounded-md text-base font-medium">Invoices</a>
                <a href="/book" class="bg-accent-500 hover:bg-accent-600 text-white block px-3 py-2 mt-4 rounded-md text-base font-semibold text-center mx-3 mb-3 shadow">
                    Book Service
                </a>
                <form method="POST" action="/logout" class="block w-full text-left">
                    @csrf
                    <button type="submit" class="hover:text-accent-400 text-slate-300 px-3 py-2 rounded-md text-base font-medium w-full text-left">Sign out</button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-6 py-10 flex-grow w-full">
        {{ $slot }}
    </main>

    <footer class="bg-brand-800 text-slate-300 mt-16">
        <div class="max-w-6xl mx-auto px-6 py-6 text-xs text-center">
            © {{ date('Y') }} TrueWrench Auto Repair · Customer Portal
        </div>
    </footer>

    {{-- Every portal page happens to be a Livewire full-page component today, so Livewire
         would auto-inject its asset bundle (and Alpine, which the mobile menu button above
         needs) regardless — this is here for consistency with the public layout and so this
         layout keeps working correctly if a future page under /portal is ever a plain Blade
         view instead. Livewire detects this tag and skips its own auto-injection, so there's
         no double-loading risk on the Livewire pages that already get it for free. --}}
    @livewireScripts
</body>
</html>
