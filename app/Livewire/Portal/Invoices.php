<?php

namespace App\Livewire\Portal;

use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Invoices extends Component
{
    public function render()
    {
        $customer = Auth::user()->customer;
        $invoices = Invoice::query()
            ->where('customer_id', $customer?->id)
            ->with('workOrder.vehicle')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('livewire.portal.invoices', [
            'invoices' => $invoices,
        ])->layout('components.layouts.portal', ['title' => 'My Invoices · TrueWrench']);
    }
}
