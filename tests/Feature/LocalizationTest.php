<?php

use Tests\TestCase;

/**
 * Flat, sorted key paths for a language file so two locales can be compared
 * as sets regardless of declaration order.
 *
 * @param  array<string, mixed>  $translations
 * @return list<string>
 */
function languageKeyPaths(array $translations, string $prefix = ''): array
{
    $keys = [];

    foreach ($translations as $key => $value) {
        $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;

        if (is_array($value)) {
            $keys = [...$keys, ...languageKeyPaths($value, $path)];

            continue;
        }

        $keys[] = $path;
    }

    sort($keys);

    return $keys;
}

it('renders the interface in Malay when the locale cookie is set', function () {
    /** @var TestCase $this */
    $this->withCookie('locale', 'ms')
        ->get('/')
        ->assertStatus(200)
        ->assertSee('lang="ms"', false)
        ->assertSee('Kira Zakat Emas')
        ->assertSee('Tiada akaun. Tiada penjejakan.');
});

it('auto-detects Malay from the browser language on a first visit', function () {
    /** @var TestCase $this */
    $this->withHeaders(['Accept-Language' => 'ms-MY,ms;q=0.9,en;q=0.8'])
        ->get('/')
        ->assertSee('Kira Zakat Emas');
});

it('serves English to a browser that prefers English', function () {
    /** @var TestCase $this */
    $this->withHeaders(['Accept-Language' => 'en-GB,en;q=0.9'])
        ->get('/')
        ->assertSee('Calculate Zakat on Gold');
});

it('prefers a stored language choice over the browser language', function () {
    /** @var TestCase $this */
    $this->withHeaders(['Accept-Language' => 'ms,en;q=0.5'])
        ->withCookie('locale', 'en')
        ->get('/')
        ->assertSee('Calculate Zakat on Gold');
});

it('ignores a cookie for an unsupported locale', function () {
    /** @var TestCase $this */
    $this->withCookie('locale', 'fr')
        ->get('/')
        ->assertSee('lang="en"', false)
        ->assertSee('Calculate Zakat on Gold');
});

it('stores the selected locale in a cookie', function () {
    /** @var TestCase $this */
    $this->get('/language?locale=ms')
        ->assertStatus(302)
        ->assertCookie('locale', 'ms');
});

it('returns to the page the visitor came from', function () {
    /** @var TestCase $this */
    $this->get('/about');

    $this->get('/language?locale=ms')->assertRedirect('/about');
});

it('falls back to the calculator when there is no page to return to', function () {
    /** @var TestCase $this */
    $this->get('/language?locale=ms')
        ->assertRedirect(route('calculator.index'));
});

it('keeps the current locale when an unsupported one is requested', function () {
    /** @var TestCase $this */
    $this->withCookie('locale', 'ms')
        ->get('/language?locale=zz')
        ->assertStatus(302)
        ->assertCookie('locale', 'ms');
});

it('marks the current language as selected in both locales', function () {
    /** @var TestCase $this */
    $this->get('/')->assertSee('<option value="en" selected>English</option>', false);

    $this->withCookie('locale', 'ms')
        ->get('/')
        ->assertSee('<option value="ms" selected>Bahasa Melayu</option>', false)
        ->assertDontSee('<option value="en" selected>', false);
});

it('renders the AJAX result partial in Malay without re-formatting money', function () {
    /** @var TestCase $this */
    $this->withCookie('locale', 'ms')
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->post('/calculate', [
            'weight' => '120',
            'category' => 'kept',
            'value' => '350',
            'currency' => 'MYR',
        ])
        ->assertStatus(200)
        ->assertSee('Keputusan Pengiraan')
        ->assertSee('12,250.00')
        ->assertSee('306.25');
});

it('validates in the selected language', function () {
    /** @var TestCase $this */
    $this->withCookie('locale', 'ms');

    $this->post('/calculate', [
        'weight' => 'not-a-number',
        'category' => 'kept',
        'value' => '350',
        'currency' => 'MYR',
    ])->assertRedirect('/')->assertSessionHasErrors('weight');

    $this->get('/')
        ->assertSee('Masukkan berat sebagai nombor positif dengan satu pemisah perpuluhan.')
        ->assertDontSee('Enter weight as a positive number with one decimal separator.');
});

it('leaves the JSON API in the default locale', function () {
    /** @var TestCase $this */
    $this->withCookie('locale', 'ms')
        ->postJson('/api/v1/calculate', [
            'weight' => '',
            'category' => 'kept',
            'value' => '350',
            'currency' => 'MYR',
        ])
        ->assertStatus(422)
        ->assertJsonPath('errors.weight.0', 'Weight field cannot be empty.');
});

it('renders the theme switcher with icons and no text label', function () {
    /** @var TestCase $this */
    $content = $this->get('/')->getContent();

    expect($content)->toMatch('/data-theme-toggle[^>]*aria-label="Toggle color theme"/')
        ->and($content)->toMatch('/app-header__toggle-icon--sun/')
        ->and($content)->toMatch('/app-header__toggle-icon--moon/')
        ->and(trim(strip_tags(preg_match('/<button[^>]*data-theme-toggle[^>]*>(.*?)<\/button>/s', $content, $toggle) ? $toggle[1] : 'text')))
        ->toBe('');
});

it('keeps the Malay and English language files in sync', function () {
    /** @var TestCase $this */
    foreach (['app', 'nav', 'layout', 'meta', 'footer', 'calculator', 'about'] as $group) {
        expect(languageKeyPaths(require lang_path("en/{$group}.php")))
            ->toBe(languageKeyPaths(require lang_path("ms/{$group}.php")), "Language group [{$group}] drifted between en and ms.");
    }
});
