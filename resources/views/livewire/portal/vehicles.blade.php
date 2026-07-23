<div class="max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-brand-700">My Vehicles</h1>
        <button wire:click="$toggle('showAddForm')"
                class="bg-brand-700 hover:bg-brand-800 text-white px-4 py-2 rounded-md font-semibold">
            + Add Vehicle
        </button>
    </div>

    @if(session()->has('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-800 rounded-md text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if($showAddForm)
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4">Add a Vehicle</h2>
            <form wire:submit="addVehicle">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Make</label>
                        <input type="text" wire:model="make" class="w-full border-slate-300 rounded-md">
                        @error('make') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Model</label>
                        <input type="text" wire:model="model" class="w-full border-slate-300 rounded-md">
                        @error('model') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Year</label>
                        <input type="text" wire:model="year" maxlength="4" class="w-full border-slate-300 rounded-md">
                        @error('year') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">License Plate</label>
                        <input type="text" wire:model="plate" class="w-full border-slate-300 rounded-md">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Current Mileage</label>
                        <input type="number" wire:model="mileage" class="w-full border-slate-300 rounded-md">
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <button type="submit" class="bg-brand-700 hover:bg-brand-800 text-white px-4 py-2 rounded-md">Save</button>
                    <button type="button" wire:click="$set('showAddForm', false)" class="text-slate-600">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="space-y-4">
        @forelse($vehicles as $v)
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 flex justify-between items-start">
                <div>
                    <div class="text-xl font-semibold">{{ $v->year }} {{ $v->make }} {{ $v->model }}</div>
                    <div class="text-sm text-slate-600 mt-1">
                        @if($v->license_plate) Plate: {{ $v->license_plate }} @endif
                        @if($v->current_mileage) · {{ number_format($v->current_mileage) }} miles @endif
                    </div>
                    <a href="/portal/vehicles/{{ $v->id }}" class="text-sm text-brand-700 hover:underline mt-2 inline-block">
                        View service history →
                    </a>
                </div>
                <button wire:click="deleteVehicle({{ $v->id }})"
                        wire:confirm="Are you sure you want to remove this vehicle?"
                        class="text-red-600 hover:text-red-800 text-sm">
                    Remove
                </button>
            </div>
        @empty
            <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-12 text-center">
                <p class="text-slate-600">No vehicles yet. Add your first vehicle to get started.</p>
            </div>
        @endforelse
    </div>
</div>
