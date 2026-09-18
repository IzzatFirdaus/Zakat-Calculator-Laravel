<?php

use Tests\TestCase;

it('reports the application environment via artisan about', function () {
    /** @var TestCase $this */
    $this->artisan('about')
        ->expectsOutputToContain('Environment')
        ->assertExitCode(0);
});
