<?php

use Tests\TestCase;

it('returns the calculator page', function () {
    /** @var TestCase $this */
    $response = $this->get('/');

    $response->assertStatus(200);
});
