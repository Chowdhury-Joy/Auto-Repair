<div class="max-w-3xl mx-auto">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-brand-700">Book an Appointment</h1>
        <p class="text-slate-600 mt-1">
            Pick your services, choose a real open time, and we'll have your bay and mechanic reserved.
        </p>
    </div>

    {{-- Progress bar --}}
    @if($step < 5)
        <div class="flex items-center gap-2 mb-8 text-sm">
            @foreach(['Services','Date & Time','Vehicle','Review'] as $i => $label)
                @php $n = $i + 1; @endphp
                <div class="flex items-center gap-2 {{ $step >= $n ? 'text-brand-700 font-semibold' : 'text-slate-400' }}">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs
                        {{ $step >= $n ? 'bg-brand-700 text-white' : 'bg-slate-200 text-slate-500' }}">
                        {{ $n }}
                    </span>
                    <span>{{ $label }}</span>
                </div>
                @if($i < 3)
                    <span class="flex-1 h-px {{ $step > $n ? 'bg-brand-700' : 'bg-slate-200' }}"></span>
                @endif
            @endforeach
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-800 rounded-md text-sm">
            {{ session('error') }}
            @if($emailConflict)
                <a href="{{ route('login') }}" class="font-semibold underline ml-1">Log in →</a>
            @endif
        </div>
    @endif

    {{-- STEP 1: Services --}}
    @if($step === 1)
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-semibold mb-4">What do you need done?</h2>
            <div class="space-y-3">
                @foreach($this->serviceMenu as $svc)
                    <label class="flex items-start gap-3 p-4 rounded-md border border-slate-200
                                  hover:border-brand-500 hover:bg-brand-50 cursor-pointer transition
                                  {{ in_array($svc->id, $selectedServiceIds) ? 'border-brand-500 bg-brand-50' : '' }}">
                        <input type="checkbox"
                               wire:model.live="selectedServiceIds"
                               value="{{ $svc->id }}"
                               class="mt-1 w-4 h-4 text-brand-600 rounded">
                        <div class="flex-1">
                            <div class="flex justify-between items-baseline">
                                <span class="font-semibold">{{ $svc->name }}</span>
                                <span class="text-sm text-slate-600">{{ $svc->formattedPriceRange() }}</span>
                            </div>
                            @if($svc->description)
                                <p class="text-sm text-slate-600 mt-1">{{ $svc->description }}</p>
                            @endif
                            <p class="text-xs text-slate-500 mt-1">~{{ $svc->duration_minutes }} min</p>
                        </div>
                    </label>
                @endforeach
            </div>

            @if(! empty($selectedServiceIds))
                <div class="mt-6 p-4 bg-slate-50 rounded-md text-sm">
                    <div class="flex justify-between">
                        <span class="text-slate-600">Estimated duration</span>
                        <span class="font-semibold">{{ $this->totalDuration }} min</span>
                    </div>
                    <div class="flex justify-between mt-1">
                        <span class="text-slate-600">Typical price range</span>
                        <span class="font-semibold">{{ $this->priceRange }}</span>
                    </div>
                </div>
            @endif

            <div class="mt-6 flex justify-end">
                <button wire:click="nextStep"
                        class="bg-brand-700 hover:bg-brand-800 text-white px-6 py-2 rounded-md font-semibold disabled:opacity-50"
                        @if(empty($selectedServiceIds)) disabled @endif>
                    Continue →
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 2: Date + Time --}}
    @if($step === 2)
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-semibold mb-4">Pick a date and time</h2>

            @if(empty($availableSlots))
                <p class="text-slate-600">No open slots in the next two weeks. Please call us at (503) 555-0100.</p>
            @else
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-slate-600 mb-2">Available dates</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach(array_keys($availableSlots) as $date)
                            @php $d = \Carbon\CarbonImmutable::parse($date); @endphp
                            <button wire:click="selectDate('{{ $date }}')"
                                class="px-3 py-2 rounded-md text-sm border
                                    {{ $selectedDate === $date
                                        ? 'bg-brand-700 text-white border-brand-700'
                                        : 'bg-white text-slate-700 border-slate-300 hover:border-brand-500' }}">
                                <div class="font-semibold">{{ $d->format('D') }}</div>
                                <div class="text-xs">{{ $d->format('M j') }}</div>
                            </button>
                        @endforeach
                    </div>
                </div>

                @if($selectedDate)
                    <div>
                        <h3 class="text-sm font-semibold text-slate-600 mb-2">
                            Open times on {{ \Carbon\CarbonImmutable::parse($selectedDate)->format('l, M j') }}
                        </h3>
                        <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                            @foreach($availableSlots[$selectedDate] ?? [] as $time)
                                @php
                                    $display = \Carbon\CarbonImmutable::parse($selectedDate.' '.$time)->format('g:i A');
                                @endphp
                                <button wire:click="selectTime('{{ $time }}')"
                                    class="px-2 py-2 rounded-md text-sm border
                                        {{ $selectedTime === $time
                                            ? 'bg-accent-500 text-white border-accent-500'
                                            : 'bg-white text-slate-700 border-slate-300 hover:border-accent-500' }}">
                                    {{ $display }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            <div class="mt-6 flex justify-between">
                <button wire:click="backStep" class="text-slate-600 hover:text-brand-700">← Back</button>
                <button wire:click="nextStep"
                        class="bg-brand-700 hover:bg-brand-800 text-white px-6 py-2 rounded-md font-semibold disabled:opacity-50"
                        @if($selectedDate === null || $selectedTime === null) disabled @endif>
                    Continue →
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 3: Vehicle + Contact --}}
    @if($step === 3)
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-semibold mb-4">Vehicle & contact info</h2>

            @if($this->myVehicles->isNotEmpty())
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-slate-600 mb-2">Which vehicle?</h3>
                    <div class="space-y-2">
                        @foreach($this->myVehicles as $v)
                            <label class="flex items-center gap-3 p-3 rounded-md border border-slate-200
                                          hover:border-brand-500 cursor-pointer
                                          {{ $useExistingVehicle && $existingVehicleId === $v->id ? 'border-brand-500 bg-brand-50' : '' }}">
                                <input type="radio" name="vehicle_choice"
                                       wire:model.live="useExistingVehicle"
                                       wire:click="$set('existingVehicleId', {{ $v->id }})"
                                       value="1"
                                       class="w-4 h-4 text-brand-600">
                                <span class="font-medium">{{ $v->year }} {{ $v->make }} {{ $v->model }}</span>
                                @if($v->license_plate)
                                    <span class="text-sm text-slate-500">· {{ $v->license_plate }}</span>
                                @endif
                            </label>
                        @endforeach
                        <label class="flex items-center gap-3 p-3 rounded-md border border-slate-200
                                      hover:border-brand-500 cursor-pointer
                                      {{ !$useExistingVehicle ? 'border-brand-500 bg-brand-50' : '' }}">
                            <input type="radio" name="vehicle_choice"
                                   wire:model.live="useExistingVehicle"
                                   value="0"
                                   class="w-4 h-4 text-brand-600">
                            <span class="font-medium">Add a different vehicle</span>
                        </label>
                    </div>
                </div>
            @endif

            @if(! $useExistingVehicle)
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Make</label>
                        <input type="text" wire:model="newVehicleMake" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Model</label>
                        <input type="text" wire:model="newVehicleModel" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Year</label>
                        <input type="text" wire:model="newVehicleYear" maxlength="4" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">License plate</label>
                        <input type="text" wire:model="newVehiclePlate" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-medium text-slate-700 mb-1">Current mileage (optional)</label>
                        <input type="number" wire:model="newVehicleMileage" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>
            @endif

            <h3 class="text-sm font-semibold text-slate-600 mb-2">How can we reach you?</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
                    <input type="text" wire:model="contactName" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" wire:model="contactEmail" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
                    <input type="tel" wire:model="contactPhone" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-slate-700 mb-1">Notes for the mechanic (optional)</label>
                    <textarea wire:model="customerNotes" rows="2" class="w-full border-slate-300 rounded-md shadow-sm focus:ring-brand-500 focus:border-brand-500"></textarea>
                </div>
            </div>

            <div class="mt-6 flex justify-between">
                <button wire:click="backStep" class="text-slate-600 hover:text-brand-700">← Back</button>
                <button wire:click="nextStep" class="bg-brand-700 hover:bg-brand-800 text-white px-6 py-2 rounded-md font-semibold">
                    Review booking →
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 4: Review --}}
    @if($step === 4)
        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <h2 class="text-xl font-semibold mb-4">Review your booking</h2>

            @php
                $when = \Carbon\CarbonImmutable::parse($selectedDate.' '.$selectedTime);
                $services = \App\Models\ServiceType::whereIn('id', $selectedServiceIds)->get();
            @endphp

            <dl class="divide-y divide-slate-200">
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm text-slate-600">When</dt>
                    <dd class="text-sm font-medium col-span-2">{{ $when->format('l, M j \a\t g:i A') }}</dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm text-slate-600">Services</dt>
                    <dd class="text-sm col-span-2">
                        @foreach($services as $s)
                            <div>{{ $s->name }} <span class="text-slate-500">(~{{ $s->duration_minutes }} min)</span></div>
                        @endforeach
                    </dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm text-slate-600">Vehicle</dt>
                    <dd class="text-sm col-span-2">
                        @if($useExistingVehicle && $existingVehicleId)
                            @php $v = \App\Models\Vehicle::find($existingVehicleId); @endphp
                            {{ $v?->year }} {{ $v?->make }} {{ $v?->model }}
                        @else
                            {{ $newVehicleYear }} {{ $newVehicleMake }} {{ $newVehicleModel }}
                            @if($newVehiclePlate) · {{ $newVehiclePlate }} @endif
                        @endif
                    </dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm text-slate-600">Contact</dt>
                    <dd class="text-sm col-span-2">
                        {{ $contactName }}<br>
                        <span class="text-slate-500">{{ $contactEmail }} · {{ $contactPhone }}</span>
                    </dd>
                </div>
                <div class="py-3 grid grid-cols-3 gap-4">
                    <dt class="text-sm text-slate-600">Estimated total</dt>
                    <dd class="text-sm font-semibold col-span-2">{{ $this->priceRange }}</dd>
                </div>
            </dl>

            <p class="mt-4 text-xs text-slate-500">
                A bay and mechanic will be assigned automatically. You'll receive a confirmation at {{ $contactEmail }}.
                Final price may vary based on actual work performed.
            </p>

            <div class="mt-6 flex justify-between">
                <button wire:click="backStep" class="text-slate-600 hover:text-brand-700">← Edit</button>
                <button wire:click="confirm"
                        wire:loading.attr="disabled"
                        class="bg-accent-500 hover:bg-accent-600 text-white px-6 py-3 rounded-md font-bold shadow">
                    <span wire:loading.remove wire:target="confirm">Confirm Booking</span>
                    <span wire:loading wire:target="confirm">Reserving your slot…</span>
                </button>
            </div>
        </div>
    @endif

    {{-- STEP 5: Confirmation --}}
    @if($step === 5 && $this->confirmedAppointment)
        @php $appt = $this->confirmedAppointment; @endphp
        <div class="bg-white rounded-lg shadow-sm border border-green-200 p-8 text-center">
            <div class="w-16 h-16 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-brand-700">You're booked!</h2>
            <p class="text-slate-600 mt-2">
                Your appointment is confirmed for
                <strong>{{ $appt->starts_at->format('l, M j \a\t g:i A') }}</strong>.
            </p>
            <p class="text-sm text-slate-500 mt-1">
                Bay {{ $appt->serviceBay?->name }} · Mechanic {{ $appt->mechanic?->name }}
            </p>

            <div class="mt-6 p-4 bg-slate-50 rounded-md text-sm text-left max-w-md mx-auto">
                <p class="font-semibold mb-2">What to bring:</p>
                <ul class="list-disc list-inside text-slate-600 space-y-1">
                    <li>Your vehicle keys</li>
                    <li>Registration and insurance</li>
                    <li>Any warning lights or symptoms to describe</li>
                </ul>
            </div>

            {{-- No confirmation email is actually sent yet — there's no Notification/Mailable
                 wired up for this event. Once one exists, restore copy like "A confirmation
                 has been sent to {{ $appt->customer?->user?->email }}." Until then, don't
                 promise something that doesn't happen. --}}
            <p class="text-sm text-slate-500 mt-6">
                You can review this appointment anytime from <strong>My Garage</strong> in your account.
            </p>

            <button wire:click="startOver" class="mt-6 text-sm text-slate-600 hover:text-brand-700">
                Book another appointment
            </button>
        </div>
    @endif
</div>
