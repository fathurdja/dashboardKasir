@php
    $columns = $this->getColumns();
    $pollingInterval = $this->getPollingInterval();

    $heading = $this->getHeading();
    $description = $this->getDescription();
    $hasHeading = filled($heading);
    $hasDescription = filled($description);
@endphp

<x-filament-widgets::widget
    :attributes="
        (new \Illuminate\View\ComponentAttributeBag)
            ->merge([
                'wire:poll.' . $pollingInterval => $pollingInterval ? true : null,
            ], escape: false)
            ->class([
                'fi-wi-stats-overview',
            ])
    "
>
    {{-- Filter Header --}}
    <div class="flex items-center justify-between gap-4 mb-4">
        <div>
            @if ($hasHeading)
                <h3 class="fi-wi-stats-overview-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $heading }}
                </h3>
            @endif
            @if ($hasDescription)
                <p class="fi-wi-stats-overview-description text-sm text-gray-500 dark:text-gray-400">
                    {{ $description }}
                </p>
            @endif
        </div>

        <select
            wire:model.live="filter"
            style="
                display:block;
                padding:0.4rem 2rem 0.4rem 0.75rem;
                font-size:0.8rem;
                font-weight:600;
                color:#313647;
                background-color:rgba(255,248,212,0.5);
                border:2px solid rgba(67,86,99,0.15);
                border-radius:10px;
                outline:none;
                transition:all 0.2s ease;
                cursor:pointer;
                appearance:auto;
            "
            onfocus="this.style.borderColor='#435663'; this.style.boxShadow='0 0 0 3px rgba(163,176,135,0.2)';"
            onblur="this.style.borderColor='rgba(67,86,99,0.15)'; this.style.boxShadow='none';"
        >
            @foreach ($this->getFilterOptions() as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    {{ $this->content }}
</x-filament-widgets::widget>
