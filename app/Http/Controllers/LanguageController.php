<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

final class LanguageController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $locale = $request->query('locale');

        if (is_string($locale) && in_array($locale, SetLocale::SUPPORTED, true)) {
            App::setLocale($locale);
        }

        return redirect()
            ->back(fallback: route('calculator.index'))
            ->withCookie(cookie()->forever('locale', App::getLocale()));
    }
}
