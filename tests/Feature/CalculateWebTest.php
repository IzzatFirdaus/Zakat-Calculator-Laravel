<?php

use Tests\TestCase;

it('renders the calculator form with the empty state', function () {
    /** @var TestCase $this */
    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('Calculate Zakat on Gold')
        ->assertSee('Enter your gold weight, category, and current value to estimate your Zakat obligation.')
        ->assertSee('Calculator')
        ->assertSee('About')
        ->assertSee('Weight of Gold (grams)')
        ->assertSee('Select Gold Category')
        ->assertSee('Current Gold Value per Gram')
        ->assertSee('Currency')
        ->assertSee('Calculate')
        ->assertSee('Enter your details to see your Zakat estimate.')
        ->assertSee('No accounts. No tracking. Your inputs stay on this device.')
        ->assertSee('aria-label="Primary"', false)
        ->assertSee('<link rel="icon" type="image/svg+xml"', false);
});

it('resolves translation keys in rendered views', function () {
    /** @var TestCase $this */
    $content = $this->get('/')->getContent();
    $about = $this->get('/about')->getContent();

    expect($content)->not->toContain('calculator.title')
        ->and($content)->not->toContain('calculator.introDescription')
        ->and($content)->not->toContain('nav.calculator')
        ->and($content)->not->toContain('nav.about')
        ->and($about)->not->toContain('about.heading')
        ->and($about)->not->toContain('about.formulaHeading')
        ->and($about)->not->toContain('about.formulaExpression')
        ->and($about)->not->toContain('about.disclaimerTitle')
        ->and($about)->not->toContain('nav.about')
        ->and($about)->not->toContain('footer.privacy');
});

it('renders the about / methodology page', function () {
    /** @var TestCase $this */
    $response = $this->get('/about');

    $response->assertStatus(200)
        ->assertSee('About and Methodology')
        ->assertSee('How the calculation works')
        ->assertSee('2.5% Zakat rate')
        ->assertSee('Formula')
        ->assertSee('Zakat = (gold weight − uruf exemption) × price per gram × 2.5%')
        ->assertSee('Example: 120 g of kept gold at 350.00 per gram leaves 35 g above the 85 g uruf, so the payable value is 12,250.00 and the Zakat due is 306.25.')
        ->assertSee('Disclaimer')
        ->assertSee('This tool provides an estimate only. Confirm calculations with a trusted scholar and use accurate gold prices for your jurisdiction and school of thought.')
        ->assertSee('https://github.com/IzzatFirdaus/Zakat-Calculator', false)
        ->assertSee('Version 1.0 — Migrated from Android Zakat Gold Calculator')
        ->assertSee('<ol class="methodology__list"', false);
});

it('has a currency select instead of free text input', function () {
    /** @var TestCase $this */
    $response = $this->get('/');

    $content = $response->getContent();
    $response->assertStatus(200);
    expect($content)->toContain('<select')
        ->and($content)->toContain('name="currency"')
        ->and($content)->not->toContain('name="currency" type="text"');
});

it('renders nav links with the configured route URLs', function () {
    /** @var TestCase $this */
    $response = $this->get('/');

    $content = $response->getContent();
    $response->assertStatus(200);
    expect($content)->toContain('href="'.route('calculator.index').'"')
        ->and($content)->toContain('href="'.route('calculator.about').'"');
});

it('computes a result for a valid POST to /calculate', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '120',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(200)
        ->assertSee('35.00')
        ->assertSee('12,250.00')
        ->assertSee('306.25');
});

it('returns the result partial for AJAX requests', function () {
    /** @var TestCase $this */
    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->post('/calculate', [
            'weight' => '120',
            'category' => 'kept',
            'value' => '350',
            'currency' => 'MYR',
        ]);

    $response->assertStatus(200)
        ->assertSee('306.25')
        ->assertSee('12,250.00')
        ->assertSee('35.00');
});

it('rejects invalid input with field errors and no 500', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(302)
        ->assertSessionHasErrors();
});

it('rejects zero weight with a validation error, not a 500', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '0',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(302)
        ->assertSessionHasErrors('weight');
});

it('rejects an unknown currency with a validation error', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '120',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'XXX',
    ]);

    $response->assertStatus(302)
        ->assertSessionHasErrors('currency');
});

it('rejects malformed decimal input with a field error', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '1,2.3',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertRedirect('/')
        ->assertSessionHasErrors('weight');
});

it('accepts a leading plus sign on decimal inputs', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '+120',
        'category' => 'kept',
        'value' => '+350.5',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(200)
        ->assertSee('306.69');
});

it('rejects negative weight with a field error', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '-1',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertRedirect('/')
        ->assertSessionHasErrors('weight');
});

it('rejects an invalid category with a field error', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '120',
        'category' => 'unknown',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertRedirect('/')
        ->assertSessionHasErrors('category');
});

it('rejects over-bound weight with a validation error', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '1000001',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertRedirect('/')
        ->assertSessionHasErrors('weight');
});

it('renders no-JS validation errors with old input and a focusable summary', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => 'not-a-number',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertRedirect('/');
    $response->assertSessionHas('_old_input.weight', 'not-a-number');

    /** @var TestCase $this */
    $getResponse = $this->get('/');

    $getResponse
        ->assertStatus(200)
        ->assertSee('id="validation-summary"', false)
        ->assertSee('tabindex="-1"', false)
        ->assertSee('Enter weight as a positive number with one decimal separator.', false)
        ->assertSee('value="not-a-number"', false)
        ->assertSee('aria-invalid="true"', false)
        ->assertSee('data-field-error', false)
        ->assertSee('href="#weight"', false);
});

it('returns structured field errors for AJAX validation failures', function () {
    /** @var TestCase $this */
    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'application/json',
    ])->post('/calculate', [
        'weight' => '',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors('weight')
        ->assertJsonPath('message', __('calculator.errorWeightEmpty'))
        ->assertJsonPath('errors.weight.0', __('calculator.errorWeightEmpty'));
});

it('treats dot and comma decimal separators identically', function () {
    /** @var TestCase $this */
    $responseDot = $this->post('/calculate', [
        'weight' => '1,5',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    /** @var TestCase $this */
    $responseComma = $this->post('/calculate', [
        'weight' => '1.5',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    expect($responseDot->getStatusCode())->toBe($responseComma->getStatusCode());
});

it('renders without requiring Vite assets', function () {
    /** @var TestCase $this */
    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('Calculate Zakat on Gold')
        ->assertSee('Enter your details to see your Zakat estimate.');
});

it('shows below-uruf status for weight under the exemption', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '50',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(200)
        ->assertSee('Below uruf: Zakat not due')
        ->assertSee('Your gold weight is below the uruf exemption. Zakat is not due at this time.')
        ->assertSee('Calculation Result');
});

it('renders identical result markup for full-page and AJAX responses', function (array $payload) {
    /** @var TestCase $this */
    $fullResponse = $this->post('/calculate', $payload);
    $ajaxResponse = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->post('/calculate', $payload);

    preg_match('/<aside id="calculation-result"[^>]*>(.*?)<\/aside>/s', $fullResponse->getContent(), $matches);

    expect($matches)->toHaveCount(2)
        ->and(trim($matches[1]))->toBe(trim($ajaxResponse->getContent()));
})->with([
    'above uruf' => [['weight' => '120', 'category' => 'kept', 'value' => '350', 'currency' => 'MYR']],
    'below uruf' => [['weight' => '50', 'category' => 'kept', 'value' => '350', 'currency' => 'MYR']],
]);

it('renders the empty state with the client state templates', function () {
    /** @var TestCase $this */
    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('Calculation Result')
        ->assertSee('Enter your details to see your Zakat estimate.')
        ->assertSee('status-panel--empty', false)
        ->assertSee('Calculating your estimate...')
        ->assertSee('We could not reach the calculator. Check your connection and try again.')
        ->assertSee('The calculator could not complete this request. Try again.')
        ->assertSee('Please correct the highlighted fields.')
        ->assertSee('data-result-loading-template', false)
        ->assertSee('data-result-network-error-template', false)
        ->assertSee('data-result-unexpected-error-template', false)
        ->assertSee('data-validation-summary-template', false);
});

it('shows the below-uruf breakdown with zero payable and no negative money', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '50',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $content = $response->getContent();

    $response->assertStatus(200)
        ->assertSee('Below uruf: Zakat not due')
        ->assertSee('Total Zakat')
        ->assertSee('85.00 g')
        ->assertSee('-35.00 g')
        ->assertSee('0.00 MYR');

    expect($content)->not->toContain('-35.00 MYR')
        ->and($content)->not->toContain('-0.00');
});

it('renders high-precision inputs with rounded display values', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '120.555',
        'category' => 'kept',
        'value' => '350.125',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(200)
        ->assertSee('35.56 g')
        ->assertSee('12,448.69 MYR')
        ->assertSee('311.22');
});

it('never formats money in the browser script', function () {
    $script = (string) file_get_contents(resource_path('js/calculator.ts'));

    expect($script)->not->toContain('toFixed')
        ->and($script)->not->toContain('Intl.NumberFormat')
        ->and($script)->not->toContain('parseFloat')
        ->and($script)->not->toContain('parseInt');
});

it('formats upper-bound results with thousands separators', function () {
    /** @var TestCase $this */
    $response = $this->post('/calculate', [
        'weight' => '1000000',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ]);

    $response->assertStatus(200)
        ->assertSee('999,915.00 g')
        ->assertSee('349,970,250.00 MYR')
        ->assertSee('8,749,256.25');
});
