<div class="max-w-6xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-brand-700">My Garage</h1>
        <p class="text-slate-600 mt-1">Welcome back, {{ Auth::user()->name }}.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <div class="text-sm text-slate-600">Vehicles</div>
            <div class="text-3xl font-bold text-brand-700 mt-2">{{ $vehicles->count() }}</div>
            <a href="/portal/vehicles" class="text-sm text-brand-700 hover:underline mt-2 inline-block">Manage →</a>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <div class="text-sm text-slate-600">Upcoming Appointments</div>
            <div class="text-3xl font-bold text-brand-700 mt-2">{{ $upcomingAppointments->count() }}</div>
            <a href="/portal/appointments" class="text-sm text-brand-700 hover:underline mt-2 inline-block">View all →</a>
        </div>
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <div class="text-sm text-slate-600">In-Shop Repairs</div>
            <div class="text-3xl font-bold text-accent-500 mt-2">{{ $inProgressWorkOrders->count() }}</div>
            <a href="/portal/vehicles" class="text-sm text-brand-700 hover:underline mt-2 inline-block">Track status →</a>
        </div>
    </div>

    @if($inProgressWorkOrders->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Live Repair Status</h2>
            <div class="space-y-4">
                @foreach($inProgressWorkOrders as $wo)
                    <div class="flex items-start gap-4 p-4 bg-slate-50 rounded-md">
                        <div class="flex-1">
                            <div class="font-semibold">{{ $wo->vehicle?->year }} {{ $wo->vehicle?->make }} {{ $wo->vehicle?->model }}</div>
                            <div class="text-sm text-slate-600 mt-1">
                                Mechanic: {{ $wo->mechanic?->name ?? 'Unassigned' }}
                            </div>
                            <div class="mt-3">
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold
                                    {{ $wo->status === \App\Enums\WorkOrderStatus::ReadyForPickup ? 'bg-green-100 text-green-800' : 'bg-brand-100 text-brand-800' }}">
                                    {{ $wo->status->customerLabel() }}
                                </span>
                            </div>
                        </div>
                        <a href="/portal/vehicles/{{ $wo->vehicle_id }}" class="text-sm text-brand-700 hover:underline">
                            View details →
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($upcomingAppointments->isNotEmpty())
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-semibold mb-4">Upcoming Appointments</h2>
            <div class="space-y-3">
                @foreach($upcomingAppointments as $appt)
                    <div class="flex items-center gap-4 p-3 border border-slate-200 rounded-md">
                        <div class="text-center min-w-[60px]">
                            <div class="text-xs text-slate-500">{{ $appt->starts_at->format('D') }}</div>
                            <div class="text-2xl font-bold text-brand-700">{{ $appt->starts_at->format('j') }}</div>
                            <div class="text-xs text-slate-500">{{ $appt->starts_at->format('M') }}</div>
                        </div>
                        <div class="flex-1">
                            <div class="font-medium">{{ $appt->starts_at->format('g:i A') }}</div>
                            <div class="text-sm text-slate-600">
                                {{ $appt->vehicle?->year }} {{ $appt->vehicle?->make }} {{ $appt->vehicle?->model }}
                            </div>
                            <div class="text-xs text-slate-500 mt-1">
                                {{ $appt->serviceTypes->pluck('name')->join(', ') }}
                            </div>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-semibold bg-blue-100 text-blue-800">
                            {{ $appt->status->label() }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
