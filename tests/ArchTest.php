<?php

declare(strict_types=1);

arch('strict types are declared')
    ->expect('SimpleSquid\\SaloonOData')
    ->toUseStrictTypes();

arch('no Laravel framework imports leak in')
    ->expect('SimpleSquid\\SaloonOData')
    ->not->toUse(['Illuminate\\Foundation', 'Laravel']);

arch('the plugin trait stays a trait')
    ->expect('SimpleSquid\\SaloonOData\\Concerns\\HasODataQuery')
    ->toBeTrait();

arch('attributes are final readonly')
    ->expect('SimpleSquid\\SaloonOData\\Attributes')
    ->toBeFinal()
    ->toBeReadonly();

arch('enums are pure')
    ->expect('SimpleSquid\\SaloonOData\\Enums')
    ->toBeEnums();
