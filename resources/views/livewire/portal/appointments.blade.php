<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-brand-700 mb-6">My Appointments</h1>

    @if(session()->has('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-md text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="space-y-4">
        @forelse($appointments as $appt)
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="text-xl font-semibold">{{ $appt->starts_at->format('l, M j \a\t g:i A') }}</div>
                        <div class="text-slate-600 mt-1">
                            {{ $appt->vehicle?->year }} {{ $appt->vehicle?->make }} {{ $appt->vehicle?->model }}
                        </div>
                        <div class="text-sm text-slate-500 mt-1">
                            {{ $appt->serviceTypes->pluck('name')->join(', ') }}
                        </div>
                        <div class="text-xs text-slate-400 mt-2">
                            Bay {{ $appt->serviceBay?->name ?? 'TBD' }} · {{ $appt->mechanic?->name ?? 'Unassigned' }}
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="px-3 py-1 rounded-full text-xs font-semibold
                            {{ $appt->status->color() === 'info' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $appt->status->color() === 'success' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $appt->status->color() === 'gray' ? 'bg-slate-100 text-slate-600' : '' }}
                            {{ $appt->status->color() === 'danger' ? 'bg-red-100 text-red-800' : '' }}">
                            {{ $appt->status->label() }}
                        </span>

                        @if($appt->status === \App\Enums\AppointmentStatus::Scheduled && $appt->starts_at->isFuture())
                            <div class="mt-3">
                                <button wire:click="cancel({{ $appt->id }})"
                                        wire:confirm="Cancel this appointment?"
                                        class="text-sm text-red-600 hover:text-red-800">
                                    Cancel
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-12 text-center">
                <p class="text-slate-600">No appointments yet.</p>
                <a href="/book" class="text-brand-700 hover:underline mt-2 inline-block">Book your first appointment →</a>
            </div>
        @endforelse
    </div>
</div>
