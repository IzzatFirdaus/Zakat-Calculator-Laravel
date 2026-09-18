<?php

namespace App\Http\Controllers;

use App\Domain\Zakat\StandardUrufRule;
use App\Domain\Zakat\ZakatCalculator;
use App\Http\Requests\CalculateZakatRequest;
use Brick\Math\RoundingMode;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class CalculatorController extends Controller
{
    public function index(): View
    {
        return view('calculator.index', [
            'currencies' => CalculateZakatRequest::SUPPORTED_CURRENCIES,
        ]);
    }

    public function calculate(CalculateZakatRequest $request): View|Response
    {
        $result = ZakatCalculator::calculate($request->toInput(), new StandardUrufRule);

        if ($request->ajax()) {
            return response()->view('calculator.partials.result', [
                'result' => $result,
            ]);
        }

        return view('calculator.index', [
            'result' => $result,
            'currencies' => CalculateZakatRequest::SUPPORTED_CURRENCIES,
        ]);
    }

    public function apiCalculate(CalculateZakatRequest $request): JsonResponse
    {
        $result = ZakatCalculator::calculate($request->toInput(), new StandardUrufRule);

        return response()->json([
            'uruf_grams' => $result->urufGrams->toScale(ZakatCalculator::SCALE, RoundingMode::HalfUp)->toString(),
            'weight_minus_uruf' => $result->weightMinusUruf->toScale(ZakatCalculator::SCALE, RoundingMode::HalfUp)->toString(),
            'below_uruf' => $result->belowUruf,
            'payable_value' => $result->payableValue->toScale(ZakatCalculator::SCALE, RoundingMode::HalfUp)->toString(),
            'zakat_due' => $result->zakatDue->toScale(ZakatCalculator::SCALE, RoundingMode::HalfUp)->toString(),
            'zakat_rate' => $result->zakatRate->toString(),
            'currency_code' => $result->currencyCode,
        ]);
    }

    public function about(): View
    {
        return view('calculator.about');
    }
}
