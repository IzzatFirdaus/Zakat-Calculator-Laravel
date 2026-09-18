@extends('layouts.app')

@section('title', __('about.title'))

@section('content')
<div class="calculator-shell">
    <div class="methodology">
        <div class="methodology__intro">
            <h1 class="methodology__heading">{{ __('about.heading') }}</h1>
            <p class="methodology__description">{{ __('about.introDescription') }}</p>
        </div>

        <div class="methodology__card">
            <ol class="methodology__list">
                <li class="methodology__item">
                    <span class="methodology__number" aria-hidden="true">1</span>
                    <div>
                        <h2 class="methodology__item-title">{{ __('about.urufKept') }}</h2>
                        <p class="methodology__item-description">{{ __('about.urufKeptDescription') }}</p>
                    </div>
                </li>
                <li class="methodology__item">
                    <span class="methodology__number" aria-hidden="true">2</span>
                    <div>
                        <h2 class="methodology__item-title">{{ __('about.urufWorn') }}</h2>
                        <p class="methodology__item-description">{{ __('about.urufWornDescription') }}</p>
                    </div>
                </li>
                <li class="methodology__item">
                    <span class="methodology__number" aria-hidden="true">3</span>
                    <div>
                        <h2 class="methodology__item-title">{{ __('about.rate') }}</h2>
                        <p class="methodology__item-description">{{ __('about.rateDescription') }}</p>
                    </div>
                </li>
            </ol>

            <div class="methodology__formula">
                <h2 class="methodology__formula-heading">{{ __('about.formulaHeading') }}</h2>
                <p class="methodology__formula-expression">{{ __('about.formulaExpression') }}</p>
                <p class="methodology__formula-example">{{ __('about.formulaExample') }}</p>
            </div>

            <p class="methodology__disclaimer">
                <strong class="methodology__disclaimer-title">{{ __('about.disclaimerTitle') }}</strong>
                {{ __('about.disclaimer') }}
            </p>

            <a class="methodology__source" href="https://github.com/IzzatFirdaus/Zakat-Calculator" target="_blank" rel="noopener">{{ __('about.source') }}</a>
        </div>
    </div>
</div>
@endsection
