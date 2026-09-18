<?php

namespace App\Http\Requests;

use App\Domain\Zakat\GoldCategory;
use App\Domain\Zakat\ZakatInput;
use Brick\Math\BigDecimal;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Single owner of ALL calculator input validation and normalization.
 *
 * Accepts dot or comma decimal separators, normalizes once to a dot,
 * then validates numerically with positivity and upper-bound checks.
 * Currency is restricted to the supported ISO-4217 set (see below).
 */
final class CalculateZakatRequest extends FormRequest
{
    /**
     * Supported ISO-4217 currency codes.
     *
     * Primary market is Malaysia (MYR). The list is intentionally minimal and
     * extensible; add a code here to accept it across web and API surfaces.
     */
    public const SUPPORTED_CURRENCIES = ['MYR', 'USD', 'EUR', 'GBP', 'SGD', 'AED', 'INR', 'CAD', 'AUD'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'weight' => $this->normalizeDecimal($this->input('weight')),
            'value' => $this->normalizeDecimal($this->input('value')),
            'category' => $this->normalizeCategory($this->input('category')),
            'currency' => $this->normalizeCurrency($this->input('currency')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'weight' => ['required', 'string', 'regex:/^[+-]?(?:\d+(?:\.\d+)?|\.\d+)$/', 'numeric', 'gt:0', 'max:1000000'],
            'value' => ['required', 'string', 'regex:/^[+-]?(?:\d+(?:\.\d+)?|\.\d+)$/', 'numeric', 'gt:0', 'max:10000000'],
            'category' => ['required', 'in:kept,worn'],
            'currency' => ['required', 'in:'.implode(',', self::SUPPORTED_CURRENCIES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'weight.required' => __('calculator.errorWeightEmpty'),
            'weight.regex' => __('calculator.errorWeightFormat'),
            'weight.gt' => __('calculator.errorWeightPositive'),
            'weight.max' => __('calculator.errorWeightRange'),
            'value.required' => __('calculator.errorValueEmpty'),
            'value.regex' => __('calculator.errorValueFormat'),
            'value.gt' => __('calculator.errorValuePositive'),
            'value.max' => __('calculator.errorValueRange'),
            'category.required' => __('calculator.errorCategoryRequired'),
            'category.in' => __('calculator.errorCategoryInvalid'),
            'currency.required' => __('calculator.errorCurrencyRequired'),
            'currency.in' => __('calculator.errorCurrencyInvalid'),
        ];
    }

    private function normalizeDecimal(mixed $value): string
    {
        if (! is_scalar($value) || is_bool($value)) {
            return '';
        }

        $value = trim((string) $value);

        if ($value === '' || str_contains($value, '.') && str_contains($value, ',')) {
            return $value;
        }

        return str_replace(',', '.', $value);
    }

    private function normalizeCategory(mixed $value): string
    {
        if (! is_scalar($value) || is_bool($value)) {
            return '';
        }

        return strtolower(trim((string) $value));
    }

    private function normalizeCurrency(mixed $value): string
    {
        if (! is_scalar($value) || is_bool($value)) {
            return '';
        }

        return strtoupper(trim((string) $value));
    }

    public function toInput(): ZakatInput
    {
        return new ZakatInput(
            weightGrams: BigDecimal::of($this->input('weight')),
            category: GoldCategory::from($this->input('category')),
            valuePerGram: BigDecimal::of($this->input('value')),
            currencyCode: $this->input('currency'),
        );
    }
}
