<x-layouts.public title="Honest Auto Repair in Portland · TrueWrench">
    {{-- Hero --}}
    <section class="relative bg-brand-800 text-white rounded-2xl overflow-hidden shadow-xl">
        <div class="relative max-w-4xl mx-auto px-8 py-20 text-center">
            <h1 class="text-4xl md:text-5xl font-bold tracking-tight">
                Auto repair you can actually trust.
            </h1>
            <p class="mt-4 text-lg text-slate-300 max-w-2xl mx-auto">
                ASE-certified mechanics. Upfront price ranges. See real-time availability and book your slot in 60 seconds — no phone calls required.
            </p>
            <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                <a href="/book" class="bg-accent-500 hover:bg-accent-600 text-white px-8 py-3 rounded-lg font-bold text-lg shadow-lg transition">
                    Book an Appointment
                </a>
                <a href="/services" class="bg-white/10 hover:bg-white/20 backdrop-blur text-white px-8 py-3 rounded-lg font-semibold text-lg border border-white/20 transition">
                    View Services & Pricing
                </a>
            </div>
        </div>
    </section>

    {{-- Trust Signals --}}
    <section class="mt-16 grid grid-cols-1 md:grid-cols-3 gap-8 text-center">
        <div class="p-6 bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="w-12 h-12 mx-auto bg-brand-100 text-brand-700 rounded-full flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-brand-800">ASE Certified</h3>
            <p class="text-slate-600 mt-2">Every mechanic on our floor is ASE certified and undergoes continuous training.</p>
        </div>
        <div class="p-6 bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="w-12 h-12 mx-auto bg-brand-100 text-brand-700 rounded-full flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-brand-800">Real-Time Tracking</h3>
            <p class="text-slate-600 mt-2">Log into your portal to see exactly what stage your repair is in. No more calling to ask "is it done yet?"</p>
        </div>
        <div class="p-6 bg-white rounded-xl border border-slate-200 shadow-sm">
            <div class="w-12 h-12 mx-auto bg-brand-100 text-brand-700 rounded-full flex items-center justify-center mb-4">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3 class="text-xl font-bold text-brand-800">Upfront Pricing</h3>
            <p class="text-slate-600 mt-2">We show you typical price ranges before you book. The final invoice matches the work done, with zero hidden fees.</p>
        </div>
    </section>

    {{-- CTA --}}
    <section class="mt-20 bg-brand-700 rounded-2xl p-10 text-center text-white">
        <h2 class="text-3xl font-bold">Need a mechanic this week?</h2>
        <p class="mt-2 text-brand-200">Check our live availability and reserve your bay right now.</p>
        <a href="/book" class="mt-6 inline-block bg-accent-500 hover:bg-accent-600 text-white px-8 py-3 rounded-lg font-bold text-lg shadow-lg transition">
            Check Availability →
        </a>
    </section>
</x-layouts.public>
