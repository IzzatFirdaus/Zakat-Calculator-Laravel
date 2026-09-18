<?php

arch('domain layer stays pure PHP')
    ->expect('App\Domain\Zakat')
    ->toOnlyUse(['Brick\Math']);
