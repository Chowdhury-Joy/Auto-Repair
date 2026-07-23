<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50 text-slate-800">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'My Garage · TrueWrench' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased flex flex-col justify-between">
    <header class="bg-brand-700 text-white shadow-sm">
        <nav class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="/portal" class="flex items-center gap-2 font-bold text-xl tracking-tight">
                <svg class="w-7 h-7 text-accent-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                </svg>
                TrueWrench
            </a>
            <div class="flex items-center gap-6 text-sm">
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
    </header>

    <main class="max-w-6xl mx-auto px-6 py-10 flex-grow w-full">
        {{ $slot }}
    </main>

    <footer class="bg-brand-800 text-slate-300 mt-16">
        <div class="max-w-6xl mx-auto px-6 py-6 text-xs text-center">
            © {{ date('Y') }} TrueWrench Auto Repair · Customer Portal
        </div>
    </footer>
</body>
</html>
