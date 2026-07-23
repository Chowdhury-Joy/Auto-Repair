<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->number }} · TrueWrench</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-slate-100 p-8">
    <div class="max-w-3xl mx-auto bg-white shadow-lg rounded-lg p-10">
        <div class="flex justify-between items-start mb-10">
            <div>
                <h1 class="text-3xl font-bold text-brand-700">TrueWrench</h1>
                <p class="text-slate-500 text-sm mt-1">1234 Industrial Way · Portland, OR 97201</p>
                <p class="text-slate-500 text-sm">(503) 555-0100</p>
            </div>
            <div class="text-right">
                <h2 class="text-2xl font-bold text-slate-800">INVOICE</h2>
                <p class="text-slate-600 mt-1">#{{ $invoice->number }}</p>
                <p class="text-sm text-slate-500 mt-2">Date: {{ $invoice->issued_at?->format('M j, Y') ?? $invoice->created_at->format('M j, Y') }}</p>
                <span class="mt-2 inline-block px-3 py-1 rounded-full text-xs font-semibold
                    {{ $invoice->status->color() === 'success' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $invoice->status->color() === 'warning' ? 'bg-amber-100 text-amber-800' : '' }}
                    {{ $invoice->status->color() === 'gray' ? 'bg-slate-100 text-slate-600' : '' }}">
                    {{ $invoice->status->label() }}
                </span>
            </div>
        </div>

        <div class="mb-8 p-4 bg-slate-50 rounded-md">
            <p class="text-sm text-slate-500">Billed To:</p>
            <p class="font-semibold">{{ $invoice->customer?->user?->name }}</p>
            <p class="text-sm text-slate-600">{{ $invoice->customer?->user?->email }}</p>
            <p class="text-sm text-slate-600 mt-2">
                Vehicle: {{ $invoice->workOrder?->vehicle?->display_name }}
            </p>
        </div>

        <table class="w-full text-left mb-8">
            <thead>
                <tr class="border-b-2 border-slate-200 text-slate-600 text-sm">
                    <th class="py-2">Description</th>
                    <th class="py-2 text-center">Qty</th>
                    <th class="py-2 text-right">Rate</th>
                    <th class="py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->workOrder?->items ?? [] as $item)
                    <tr class="border-b border-slate-100">
                        <td class="py-3">
                            {{ $item->description }}
                            <span class="text-xs text-slate-400 ml-2">({{ ucfirst($item->type) }})</span>
                        </td>
                        <td class="py-3 text-center">{{ $item->quantity }}</td>
                        <td class="py-3 text-right">${{ number_format($item->rate_cents / 100, 2) }}</td>
                        <td class="py-3 text-right font-medium">${{ number_format($item->amount_cents / 100, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="flex justify-end">
            <div class="w-64">
                <div class="flex justify-between py-2 border-t-2 border-brand-700">
                    <span class="text-lg font-bold text-brand-700">Total Due</span>
                    <span class="text-lg font-bold text-brand-700">${{ number_format($invoice->total_cents / 100, 2) }}</span>
                </div>
            </div>
        </div>

        <div class="mt-12 pt-6 border-t border-slate-200 text-center text-sm text-slate-500">
            <p>Thank you for choosing TrueWrench Auto Repair.</p>
            <p class="mt-1">Please make checks payable to TrueWrench Auto Repair, or pay in person at the shop.</p>
        </div>
    </div>
</body>
</html>
