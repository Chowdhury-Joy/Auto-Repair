<div class="max-w-4xl mx-auto" wire:poll.30s>
    <div class="mb-6">
        <a href="/portal/vehicles" class="text-sm text-brand-700 hover:underline">← Back to vehicles</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
        <h1 class="text-3xl font-bold text-brand-700">{{ $vehicle->display_name }}</h1>
        <div class="text-slate-600 mt-2">
            @if($vehicle->license_plate) Plate: {{ $vehicle->license_plate }} @endif
            @if($vehicle->current_mileage) · {{ number_format($vehicle->current_mileage) }} miles @endif
        </div>
    </div>

    @if($activeWorkOrder)
        <div class="bg-gradient-to-br from-brand-50 to-amber-50 rounded-lg shadow-sm border-2 border-brand-200 p-8 mb-6">
            <h2 class="text-2xl font-bold text-brand-700 mb-4">Current Repair Status</h2>

            <div class="flex items-center gap-4 mb-6">
                <div class="flex-1">
                    <div class="text-sm text-slate-600">Mechanic</div>
                    <div class="font-semibold">{{ $activeWorkOrder->mechanic?->name ?? 'Unassigned' }}</div>
                </div>
                <div class="flex-1">
                    <div class="text-sm text-slate-600">Opened</div>
                    <div class="font-semibold">{{ $activeWorkOrder->opened_at->format('M j, g:i A') }}</div>
                </div>
            </div>

            <div class="space-y-3">
                @php
                    $stages = [
                        \App\Enums\WorkOrderStatus::Open           => 'Checked In',
                        \App\Enums\WorkOrderStatus::AwaitingParts  => 'Awaiting Parts',
                        \App\Enums\WorkOrderStatus::InProgress     => 'In Progress',
                        \App\Enums\WorkOrderStatus::ReadyForPickup => 'Ready for Pickup',
                    ];
                    $currentStage = $activeWorkOrder->status;
                @endphp

                @foreach($stages as $stage => $label)
                    @php
                        $isComplete = array_search($currentStage, array_keys($stages)) >= array_search($stage, array_keys($stages));
                        $isCurrent = $currentStage === $stage;
                    @endphp
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center
                            {{ $isComplete ? 'bg-brand-700 text-white' : 'bg-slate-200 text-slate-400' }}">
                            @if($isComplete)
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <span class="text-sm font-bold">{{ $loop->iteration }}</span>
                            @endif
                        </div>
                        <div class="flex-1 {{ $isCurrent ? 'font-bold text-brand-700' : ($isComplete ? 'text-slate-700' : 'text-slate-400') }}">
                            {{ $label }}
                            @if($isCurrent)
                                <span class="ml-2 inline-block w-2 h-2 bg-accent-500 rounded-full animate-pulse"></span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @if($activeWorkOrder->status === \App\Enums\WorkOrderStatus::ReadyForPickup)
                <div class="mt-6 p-4 bg-green-100 border border-green-300 rounded-md">
                    <p class="font-semibold text-green-800">Your vehicle is ready for pickup!</p>
                    <p class="text-sm text-green-700 mt-1">Visit us during business hours to collect your vehicle and complete payment.</p>
                </div>
            @endif
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
        <h2 class="text-xl font-semibold mb-4">Service History</h2>

        @forelse($workOrders as $wo)
            <div class="border-b border-slate-200 last:border-0 py-4">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="font-semibold">{{ $wo->opened_at->format('M j, Y') }}</div>
                        <div class="text-sm text-slate-600">
                            {{ $wo->mechanic?->name ?? 'Unassigned' }}
                            @if($wo->invoice->first())
                                · Invoice #{{ $wo->invoice->first()?->number }}
                            @endif
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="font-semibold">${{ number_format($wo->total_cents / 100, 2) }}</div>
                        <span class="text-xs px-2 py-1 rounded-full
                            {{ $wo->status->isTerminal() ? 'bg-green-100 text-green-800' : 'bg-brand-100 text-brand-800' }}">
                            {{ $wo->status->label() }}
                        </span>
                    </div>
                </div>

                @if($wo->items->isNotEmpty())
                    <div class="mt-3 text-sm text-slate-600">
                        @foreach($wo->items as $item)
                            <div class="flex justify-between">
                                <span>{{ $item->description }}</span>
                                <span>${{ number_format($item->amount_cents / 100, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($wo->invoice->first())
                    <div class="mt-3">
                        <a href="{{ $wo->invoice->first()?->publicUrl() }}" target="_blank"
                           class="text-sm text-brand-700 hover:underline">
                            View invoice →
                        </a>
                    </div>
                @endif
            </div>
        @empty
            <p class="text-slate-600 text-center py-8">No service history yet.</p>
        @endforelse
    </div>
</div>
