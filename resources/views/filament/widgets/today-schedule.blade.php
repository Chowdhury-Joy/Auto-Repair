<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Today's Schedule — {{ $today->format('l, M j') }}
        </x-slot>

        {{-- table-fixed + explicit widths: without it, a long bay name (e.g. "Bay 3
             (Diagnostics)") blows past the "w-32" hint on an auto-layout table and
             runs straight into the Schedule column with no visible gap. --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm table-fixed">
                <thead>
                    <tr class="text-left text-slate-500 border-b border-slate-200">
                        <th class="py-2 pr-4 w-40">Bay</th>
                        <th class="py-2 pr-4">Schedule</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bays as $bay)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="py-3 pr-4 font-semibold text-brand-700">{{ $bay->name }}</td>
                            <td class="py-3 pr-4">
                                @php $list = $appts->get($bay->id, collect()); @endphp
                                @if($list->isEmpty())
                                    <span class="text-slate-400 italic">No appointments</span>
                                @else
                                    <div class="space-y-2">
                                        @foreach($list as $a)
                                            @php
                                                $color = match($a->status) {
                                                    \App\Enums\AppointmentStatus::Scheduled   => 'bg-blue-100 text-blue-800 border-blue-200',
                                                    \App\Enums\AppointmentStatus::CheckedIn   => 'bg-amber-100 text-amber-800 border-amber-200',
                                                    \App\Enums\AppointmentStatus::InProgress  => 'bg-indigo-100 text-indigo-800 border-indigo-200',
                                                    \App\Enums\AppointmentStatus::Completed   => 'bg-green-100 text-green-800 border-green-200',
                                                    \App\Enums\AppointmentStatus::NoShow      => 'bg-red-100 text-red-800 border-red-200',
                                                    \App\Enums\AppointmentStatus::Cancelled   => 'bg-slate-100 text-slate-500 border-slate-200 line-through',
                                                };
                                            @endphp
                                            <div class="p-2.5 rounded-lg border text-xs flex items-center justify-between {{ $color }}">
                                                <div>
                                                    <span class="font-bold">{{ $a->starts_at->format('g:i A') }} – {{ $a->ends_at->format('g:i A') }}</span>
                                                    <span class="ml-2 font-semibold text-slate-900">{{ $a->customer?->user?->name }}</span>
                                                    <span class="text-slate-600">({{ $a->vehicle?->display_name }})</span>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <span class="text-xs px-2 py-0.5 rounded bg-white/70 font-medium border border-black/5">
                                                        {{ $a->mechanic?->name ?? 'Unassigned' }}
                                                    </span>
                                                    <span class="text-xs uppercase tracking-wider font-bold opacity-75">
                                                        {{ $a->status->label() }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="py-4 text-slate-400 italic">No bays configured</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
