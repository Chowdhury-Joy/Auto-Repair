<x-layouts.public title="Services & Pricing · TrueWrench">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-4xl font-bold text-brand-800 text-center">Our Services</h1>
        <p class="text-center text-slate-600 mt-3 mb-12">Transparent pricing. No surprises. Here's what we do and what it typically costs.</p>

        <div class="space-y-4">
            @foreach($services as $svc)
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col md:flex-row md:items-center justify-between gap-4 hover:border-brand-300 transition">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-brand-800">{{ $svc->name }}</h3>
                        <p class="text-slate-600 mt-1">{{ $svc->description }}</p>
                        <p class="text-xs text-slate-400 mt-2">Estimated time: ~{{ $svc->duration_minutes }} minutes</p>
                    </div>
                    <div class="text-right md:min-w-[150px]">
                        <div class="text-sm text-slate-500">Typical Range</div>
                        <div class="text-2xl font-bold text-brand-700">{{ $svc->formattedPriceRange() }}</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-12 text-center">
            <p class="text-slate-600 mb-4">Ready to get your car fixed?</p>
            <a href="/book" class="bg-brand-700 hover:bg-brand-800 text-white px-8 py-3 rounded-lg font-bold shadow transition">
                Book an Appointment →
            </a>
        </div>
    </div>
</x-layouts.public>
