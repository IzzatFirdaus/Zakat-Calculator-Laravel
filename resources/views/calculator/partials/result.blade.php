@use('App\Support\MoneyFormatter')

<svg class="watermark" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
    <path d="M16 2.5 19 13l10.5 3L19 19l-3 10.5L13 19 2.5 16 13 13l3-10.5Z"/>
    <path d="m16 8 1.7 6.3L24 16l-6.3 1.7L16 24l-1.7-6.3L8 16l6.3-1.7L16 8Z"/>
</svg>

<h2 class="result-panel__heading" id="result-heading" data-result-heading tabindex="-1">{{ __('calculator.resultHeading') }}</h2>

@if ($result === null)
    <div class="status-panel status-panel--empty" role="status">
        <svg class="status-panel__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect x="3" y="3" width="18" height="18" rx="2"/>
            <path d="M12 8v4M12 16h.01"/>
        </svg>
        <span>{{ __('calculator.resultEmpty') }}</span>
    </div>
@else
    @if ($result->belowUruf)
        <div class="status-panel" role="status">
            <svg class="status-panel__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                <path d="m9 12 2 2 4-4"/>
            </svg>
            <div>
                <div>{{ __('calculator.belowUruf') }}</div>
                <p class="status-panel__detail">{{ __('calculator.belowUrufDetail') }}</p>
            </div>
        </div>
    @else
        @php
            // Presentational geometry only: zakatable share of the entered weight.
            // The zakat formula itself lives in ZakatCalculator — do not extend this math.
            $total = $result->weightMinusUruf->plus($result->urufGrams);
            $meterWidth = $result->weightMinusUruf->multipliedBy(100)->dividedBy($total, 2, \Brick\Math\RoundingMode::HalfUp)->toFloat();
            $meterTick = $result->urufGrams->multipliedBy(100)->dividedBy($total, 2, \Brick\Math\RoundingMode::HalfUp)->toFloat();
        @endphp
        <p class="eyebrow">{{ __('calculator.resultEyebrow') }}</p>
        <p class="amount">{{ MoneyFormatter::format($result->zakatDue) }}<small class="amount__currency">{{ $result->currencyCode }}</small></p>
        <div class="meter" style="--meter-width:{{ $meterWidth }}%;--meter-tick:{{ $meterTick }}%"
             role="img"
             aria-label="{{ __('calculator.meterLabel', [
                 'weight' => MoneyFormatter::format($total),
                 'zakatable' => MoneyFormatter::format($result->weightMinusUruf),
                 'uruf' => MoneyFormatter::format($result->urufGrams),
             ]) }}">
            <div class="meter__labels">
                <span>{{ __('calculator.meterZakatableLabel') }} {{ MoneyFormatter::format($result->weightMinusUruf) }} g</span>
                <span>{{ __('calculator.meterTotalLabel') }} {{ MoneyFormatter::format($total) }} g</span>
            </div>
            <div class="meter__track"><div class="meter__fill"></div></div>
            <div class="meter__ticks"><span class="meter__tick">{{ __('calculator.meterUrufTick', ['uruf' => MoneyFormatter::format($result->urufGrams)]) }}</span></div>
        </div>
    @endif

    <dl class="result-list">
        <div class="result-list__row">
            <dt class="result-list__label">{{ __('calculator.urufApplied') }}</dt>
            <span class="result-list__leader" aria-hidden="true"></span>
            <dd class="result-list__value num">{{ MoneyFormatter::format($result->urufGrams) }} g</dd>
        </div>
        <div class="result-list__row">
            <dt class="result-list__label">{{ __('calculator.resultWeightMinusUruf') }}</dt>
            <span class="result-list__leader" aria-hidden="true"></span>
            <dd class="result-list__value num">{{ MoneyFormatter::format($result->weightMinusUruf) }} g</dd>
        </div>
        <div class="result-list__row">
            <dt class="result-list__label">{{ __('calculator.resultPayableValue') }}</dt>
            <span class="result-list__leader" aria-hidden="true"></span>
            <dd class="result-list__value num">{{ MoneyFormatter::format($result->payableValue) }} {{ $result->currencyCode }}</dd>
        </div>
        <div class="result-list__row result-list__row--total">
            <dt class="result-list__label">{{ __('calculator.resultTotalZakat') }}</dt>
            <span class="result-list__leader" aria-hidden="true"></span>
            <dd class="result-list__value num">{{ MoneyFormatter::format($result->zakatDue) }} {{ $result->currencyCode }}</dd>
        </div>
    </dl>

    <p class="method-note">{{ __('calculator.resultMethodNote') }} <a class="method-note__link" href="{{ route('calculator.about') }}">{{ __('calculator.resultAboutLink') }}</a></p>
@endif
