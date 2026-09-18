<?php

use Tests\TestCase;

it('returns the documented JSON shape for a valid request', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/calculate', [
        'weight' => '120',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'uruf_grams',
            'weight_minus_uruf',
            'below_uruf',
            'payable_value',
            'zakat_due',
            'zakat_rate',
            'currency_code',
        ])
        ->assertJson([
            'below_uruf' => false,
            'zakat_due' => '306.25',
            'currency_code' => 'MYR',
        ]);
});

it('returns 422 with validation errors for an invalid request', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/calculate', [
        'weight' => '',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('weight');
});

it('rejects an unknown currency code', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/calculate', [
        'weight' => '120',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'XXX',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('currency');
});

it('rejects zero or negative weight as a validation error, not a server error', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/calculate', [
        'weight' => '0',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('weight');
});

it('returns display-rounded values for high-precision input', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/calculate', [
        'weight' => '120.555',
        'category' => 'kept',
        'value' => '350.125',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('weight_minus_uruf', '35.56')
        ->assertJsonPath('payable_value', '12448.69')
        ->assertJsonPath('zakat_due', '311.22');
});

it('returns the below-uruf breakdown with zero payable', function () {
    /** @var TestCase $this */
    $response = $this->postJson('/api/v1/calculate', [
        'weight' => '50',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('below_uruf', true)
        ->assertJsonPath('weight_minus_uruf', '-35.00')
        ->assertJsonPath('payable_value', '0.00')
        ->assertJsonPath('zakat_due', '0.00');
});
