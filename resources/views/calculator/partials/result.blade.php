@use('App\Support\MoneyFormatter')

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
        <p class="result-amount">
            {{ MoneyFormatter::format($result->zakatDue) }}
            <span class="result-amount__currency">{{ $result->currencyCode }}</span>
        </p>
        <p class="result-sublabel">{{ __('calculator.resultStatus') }}</p>
    @endif

    <dl class="result-list">
        <div class="result-list__row">
            <dt class="result-list__label">{{ __('calculator.urufApplied') }}</dt>
            <dd class="result-list__value">{{ MoneyFormatter::format($result->urufGrams) }} g</dd>
        </div>
        <div class="result-list__row">
            <dt class="result-list__label">{{ __('calculator.resultWeightMinusUruf') }}</dt>
            <dd class="result-list__value">{{ MoneyFormatter::format($result->weightMinusUruf) }} g</dd>
        </div>
        <div class="result-list__row">
            <dt class="result-list__label">{{ __('calculator.resultPayableValue') }}</dt>
            <dd class="result-list__value">{{ MoneyFormatter::format($result->payableValue) }} {{ $result->currencyCode }}</dd>
        </div>
        <div class="result-list__row">
            <dt class="result-list__label">{{ __('calculator.resultTotalZakat') }}</dt>
            <dd class="result-list__value result-list__value--total">{{ MoneyFormatter::format($result->zakatDue) }} {{ $result->currencyCode }}</dd>
        </div>
    </dl>

    <p class="method-note">{{ __('calculator.resultMethodNote') }}</p>
@endif
