<div class="max-w-4xl mx-auto">
    <h1 class="text-3xl font-bold text-brand-700 mb-6">My Invoices</h1>

    <div class="space-y-4">
        @forelse($invoices as $inv)
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 flex justify-between items-start">
                <div>
                    <div class="text-xl font-semibold">Invoice #{{ $inv->number }}</div>
                    <div class="text-slate-600 mt-1">
                        {{ $inv->workOrder?->vehicle?->display_name }}
                    </div>
                    <div class="text-sm text-slate-500 mt-1">
                        Issued {{ $inv->issued_at?->format('M j, Y') ?? $inv->created_at->format('M j, Y') }}
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-2xl font-bold text-brand-700">${{ number_format($inv->total_cents / 100, 2) }}</div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        {{ $inv->status->color() === 'success' ? 'bg-green-100 text-green-800' : '' }}
                        {{ $inv->status->color() === 'warning' ? 'bg-amber-100 text-amber-800' : '' }}
                        {{ $inv->status->color() === 'gray' ? 'bg-slate-100 text-slate-600' : '' }}">
                        {{ $inv->status->label() }}
                    </span>
                    <div class="mt-3">
                        <a href="{{ $inv->publicUrl() }}" target="_blank"
                           class="text-sm text-brand-700 hover:underline">
                            View invoice →
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-12 text-center">
                <p class="text-slate-600">No invoices yet.</p>
            </div>
        @endforelse
    </div>
</div>
