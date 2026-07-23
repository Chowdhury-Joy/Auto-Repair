<x-layouts.public title="TrueWrench Auto Repair · Honest & Transparent Auto Service">
    <div class="space-y-16">
        {{-- Hero Section --}}
        <div class="relative bg-brand-700 text-white rounded-2xl p-8 md:p-12 overflow-hidden shadow-xl">
            <div class="relative z-10 max-w-2xl">
                <span class="inline-block bg-accent-500/20 text-accent-400 text-xs font-semibold px-3 py-1 rounded-full uppercase tracking-wider mb-4 border border-accent-400/30">
                    ASE-Certified Technicians
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight leading-tight">
                    Honest Auto Repair, Guaranteed Availability.
                </h1>
                <p class="mt-4 text-lg text-slate-200">
                    Book online in 60 seconds with real-time bay and mechanic slot availability. Upfront estimates, no hidden fees.
                </p>
                <div class="mt-8 flex flex-wrap gap-4">
                    <a href="/book" class="bg-accent-500 hover:bg-accent-600 text-white font-bold px-6 py-3 rounded-lg shadow-lg transition transform hover:-translate-y-0.5">
                        Book Your Appointment →
                    </a>
                    <a href="#services" class="bg-white/10 hover:bg-white/20 text-white font-semibold px-6 py-3 rounded-lg border border-white/20 transition">
                        Explore Services
                    </a>
                </div>
            </div>
        </div>

        {{-- Features Grid --}}
        <div class="grid md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <div class="w-12 h-12 bg-brand-100 rounded-lg flex items-center justify-center text-brand-600 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Real-Time Scheduling</h3>
                <p class="text-slate-600 mt-2 text-sm">
                    No waiting for callbacks. Pick an open bay slot live from our availability engine.
                </p>
            </div>

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <div class="w-12 h-12 bg-brand-100 rounded-lg flex items-center justify-center text-brand-600 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Transparent Pricing</h3>
                <p class="text-slate-600 mt-2 text-sm">
                    Clear price ranges for standard services before you even step foot in the shop.
                </p>
            </div>

            <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                <div class="w-12 h-12 bg-brand-100 rounded-lg flex items-center justify-center text-brand-600 mb-4">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Expert Mechanics</h3>
                <p class="text-slate-600 mt-2 text-sm">
                    Specialized master mechanics for diagnostics, brakes, engine work, and transmissions.
                </p>
            </div>
        </div>

        {{-- Service Menu Preview --}}
        <div id="services" class="space-y-6">
            <div class="flex justify-between items-end">
                <div>
                    <h2 class="text-2xl font-bold text-slate-900">Popular Services</h2>
                    <p class="text-slate-600 text-sm mt-1">Book any combination of services online.</p>
                </div>
                <a href="/book" class="text-brand-700 font-semibold text-sm hover:underline">View All & Book →</a>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach(\App\Models\ServiceType::active()->menuOrdered()->get() as $svc)
                    <div class="bg-white p-5 rounded-lg border border-slate-200 flex flex-col justify-between">
                        <div>
                            <div class="flex justify-between items-start">
                                <h3 class="font-bold text-slate-900">{{ $svc->name }}</h3>
                                <span class="text-xs bg-slate-100 text-slate-700 px-2 py-1 rounded font-medium">~{{ $svc->duration_minutes }} min</span>
                            </div>
                            <p class="text-xs text-slate-600 mt-2">{{ $svc->description }}</p>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex justify-between items-center text-sm">
                            <span class="font-semibold text-brand-700">{{ $svc->formattedPriceRange() }}</span>
                            <a href="/book" class="text-accent-600 hover:text-accent-700 font-semibold text-xs">Book Service</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layouts.public>
