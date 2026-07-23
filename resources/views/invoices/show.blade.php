<x-layouts.public title="Invoice #{{ $invoice->number }} · TrueWrench Auto Repair">
    <div class="max-w-3xl mx-auto bg-white rounded-xl shadow-sm border border-slate-200 p-8">
        <div class="flex justify-between items-start border-b border-slate-200 pb-6">
            <div>
                <h1 class="text-2xl font-bold text-brand-700">INVOICE</h1>
                <p class="text-slate-600 font-mono text-sm">#{{ $invoice->number }}</p>
                <div class="mt-2">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        {{ $invoice->status->color() === 'success' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                        {{ $invoice->status->label() }}
                    </span>
                </div>
            </div>
            <div class="text-right text-sm text-slate-600">
                <p class="font-bold text-slate-900">TrueWrench Auto Repair</p>
                <p>1234 Industrial Way</p>
                <p>Portland, OR 97201</p>
                <p>(503) 555-0100</p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-6 my-6 text-sm">
            <div>
                <h3 class="font-semibold text-slate-500 uppercase text-xs tracking-wider">Billed To</h3>
                <p class="font-bold text-slate-900 mt-1">{{ $invoice->customer?->user?->name }}</p>
                <p class="text-slate-600">{{ $invoice->customer?->user?->email }}</p>
                <p class="text-slate-600">{{ $invoice->customer?->user?->phone }}</p>
            </div>
            <div class="text-right">
                <h3 class="font-semibold text-slate-500 uppercase text-xs tracking-wider">Vehicle Details</h3>
                <p class="font-bold text-slate-900 mt-1">{{ $invoice->workOrder?->vehicle?->display_name }}</p>
                @if($invoice->workOrder?->vehicle?->license_plate)
                    <p class="text-slate-600">Plate: {{ $invoice->workOrder?->vehicle?->license_plate }}</p>
                @endif
                <p class="text-slate-600 mt-2">Issued: {{ $invoice->issued_at?->format('M j, Y') ?? $invoice->created_at->format('M j, Y') }}</p>
            </div>
        </div>

        <table class="w-full text-left text-sm my-6">
            <thead>
                <tr class="border-b border-slate-200 text-slate-500 uppercase text-xs">
                    <th class="py-2">Description</th>
                    <th class="py-2 text-center">Type</th>
                    <th class="py-2 text-center">Qty</th>
                    <th class="py-2 text-right">Rate</th>
                    <th class="py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->workOrder?->items ?? [] as $item)
                    <tr class="border-b border-slate-100">
                        <td class="py-3 font-medium">{{ $item->description }}</td>
                        <td class="py-3 text-center capitalize text-slate-500">{{ $item->type }}</td>
                        <td class="py-3 text-center">{{ $item->quantity }}</td>
                        <td class="py-3 text-right">${{ number_format($item->rate_cents / 100, 2) }}</td>
                        <td class="py-3 text-right font-semibold">${{ number_format($item->amount_cents / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-4 text-center text-slate-400 italic">No line items</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="border-t border-slate-200 pt-4 flex justify-end">
            <div class="w-64 text-right space-y-2">
                <div class="flex justify-between text-base font-bold text-slate-900 pt-2 border-t border-slate-200">
                    <span>Total Amount:</span>
                    <span class="text-brand-700">${{ number_format($invoice->total_cents / 100, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</x-layouts.public>
