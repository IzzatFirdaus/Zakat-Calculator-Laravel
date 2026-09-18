@extends('layouts.app')

@section('title', __('calculator.title'))

@section('content')
<div class="calculator-shell">
    <div class="calculator-intro">
        <h1 class="calculator-intro__title">{{ __('calculator.title') }}</h1>
        <p class="calculator-intro__description">{{ __('calculator.introDescription') }}</p>
    </div>

    <div class="calculator-grid">
        <div>
            @if ($errors->any())
                <div id="validation-summary" class="error-summary" role="alert" aria-live="assertive" tabindex="-1" data-validation-summary>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <div>
                        <strong>{{ __('calculator.errorSummary') }}</strong>
                        <p class="error-summary__detail">{{ __('calculator.errorSummaryDetail') }}</p>
                        <ul class="error-summary__list">
                            @error('weight')
                                <li><a href="#weight">{{ __('calculator.weightLabel') }}</a></li>
                            @enderror
                            @error('category')
                                <li><a href="#category">{{ __('calculator.categoryLabel') }}</a></li>
                            @enderror
                            @error('value')
                                <li><a href="#value">{{ __('calculator.valueLabel') }}</a></li>
                            @enderror
                            @error('currency')
                                <li><a href="#currency">{{ __('calculator.currencyLabel') }}</a></li>
                            @enderror
                        </ul>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('calculator.calculate') }}" data-calculator-form class="calculator-form">
                <div class="calculator-form__header">
                    <p class="calculator-form__section">{{ __('calculator.sectionDetails') }}</p>
                    <h2 class="calculator-form__heading">{{ __('calculator.detailsHeading') }}</h2>
                </div>

                @csrf

                <div class="field" data-field="weight">
                    <label for="weight" class="field__label">{{ __('calculator.weightLabel') }}</label>
                    <div class="control">
                        <input
                            type="text"
                            id="weight"
                            name="weight"
                            value="{{ old('weight') }}"
                            placeholder="{{ __('calculator.weightPlaceholder') }}"
                            class="input @error('weight') input--error @enderror"
                            inputmode="decimal"
                            autocomplete="off"
                            aria-describedby="weight-help @error('weight') weight-error @enderror"
                            @if ($errors->has('weight')) aria-invalid="true" @endif
                        >
                        <span class="control__unit" aria-hidden="true">{{ __('calculator.unitGramShort') }}</span>
                    </div>
                    <p id="weight-help" class="field__help">{{ __('calculator.weightHelp') }}</p>
                    @error('weight')
                        <p id="weight-error" class="field__error" role="alert" data-field-error>{{ $message }}</p>
                    @enderror
                </div>

                <fieldset id="category" class="category-fieldset" data-field="category" @if ($errors->has('category')) aria-invalid="true" aria-describedby="category-error" @endif>
                    <legend class="category-fieldset__legend" id="category-legend">{{ __('calculator.categoryLabel') }}</legend>
                    <div class="category-grid" role="radiogroup" aria-labelledby="category-legend">
                        <label class="category-option">
                            <input type="radio" name="category" value="kept" {{ old('category', 'kept') === 'kept' ? 'checked' : '' }}>
                            <span class="category-option__indicator" aria-hidden="true"></span>
                            <span class="category-option__content">
                                <span class="category-option__title-row">
                                    <span class="category-option__title">{{ __('calculator.categoryKept') }}</span>
                                    <span class="category-option__badge num">{{ __('calculator.categoryKeptUruf') }}</span>
                                </span>
                                <span class="category-option__description">{{ __('calculator.keptDescription') }}</span>
                            </span>
                        </label>
                        <label class="category-option">
                            <input type="radio" name="category" value="worn" {{ old('category') === 'worn' ? 'checked' : '' }}>
                            <span class="category-option__indicator" aria-hidden="true"></span>
                            <span class="category-option__content">
                                <span class="category-option__title-row">
                                    <span class="category-option__title">{{ __('calculator.categoryWorn') }}</span>
                                    <span class="category-option__badge num">{{ __('calculator.categoryWornUruf') }}</span>
                                </span>
                                <span class="category-option__description">{{ __('calculator.wornDescription') }}</span>
                            </span>
                        </label>
                    </div>
                    @error('category')
                        <p id="category-error" class="field__error" role="alert" data-field-error>{{ $message }}</p>
                    @enderror
                </fieldset>

                <div class="currency-grid">
                    <div class="field field--compact" data-field="value">
                        <label for="value" class="field__label">{{ __('calculator.valueLabel') }}</label>
                        <div class="control control--prefixed">
                            <span class="control__prefix" aria-hidden="true" data-currency-prefix>{{ old('currency', 'MYR') }}</span>
                            <input
                                type="text"
                                id="value"
                                name="value"
                                value="{{ old('value') }}"
                                placeholder="{{ __('calculator.valuePlaceholder') }}"
                                class="input @error('value') input--error @enderror"
                                inputmode="decimal"
                                autocomplete="off"
                                aria-describedby="value-help @error('value') value-error @enderror"
                                @if ($errors->has('value')) aria-invalid="true" @endif
                            >
                        </div>
                        <p id="value-help" class="field__help">{{ __('calculator.valueHelp') }}</p>
                        @error('value')
                            <p id="value-error" class="field__error" role="alert" data-field-error>{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="field field--compact" data-field="currency">
                        <label for="currency" class="field__label">{{ __('calculator.currencyLabel') }}</label>
                        <select
                            id="currency"
                            name="currency"
                            class="select @error('currency') input--error @enderror"
                            aria-describedby="currency-help @error('currency') currency-error @enderror"
                            @if ($errors->has('currency')) aria-invalid="true" @endif
                        >
                            @foreach ($currencies as $code)
                                <option value="{{ $code }}" {{ old('currency', 'MYR') === $code ? 'selected' : '' }}>{{ $code }}</option>
                            @endforeach
                        </select>
                        <p id="currency-help" class="field__help">{{ __('calculator.currencyHelp') }}</p>
                        @error('currency')
                            <p id="currency-error" class="field__error" role="alert" data-field-error>{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" data-submit-text="{{ __('calculator.submit') }}" data-submitting-text="{{ __('calculator.submitting') }}" class="button-primary">
                        {{ __('calculator.submit') }}
                    </button>
                    <a href="{{ route('calculator.index') }}" class="button-secondary" data-calculator-reset>{{ __('calculator.reset') }}</a>
                </div>
            </form>
        </div>

        <aside id="calculation-result" class="calculator-result" data-calculator-result aria-live="polite" aria-busy="false" tabindex="-1">
            @include('calculator.partials.result', ['result' => $result ?? null])
        </aside>
    </div>

    <template data-result-loading-template>
        <div class="result-state result-state--loading" role="status">
            <svg class="result-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <path d="M12 3a9 9 0 1 0 9 9"/>
            </svg>
            <p class="result-state__text">{{ __('calculator.resultLoading') }}</p>
        </div>
    </template>

    <template data-result-network-error-template>
        <div class="result-state result-state--error" role="alert">
            <svg class="result-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p class="result-state__text">{{ __('calculator.errorNetwork') }}</p>
        </div>
    </template>

    <template data-result-unexpected-error-template>
        <div class="result-state result-state--error" role="alert">
            <svg class="result-state__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p class="result-state__text">{{ __('calculator.errorUnexpected') }}</p>
        </div>
    </template>

    <template data-validation-summary-template>
        <div id="validation-summary" class="error-summary" role="alert" aria-live="assertive" tabindex="-1" data-validation-summary>
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <div>
                <strong>{{ __('calculator.errorSummary') }}</strong>
                <p class="error-summary__detail">{{ __('calculator.errorSummaryDetail') }}</p>
                <ul class="error-summary__list" data-validation-summary-list></ul>
            </div>
        </div>
    </template>
</div>
@endsection
