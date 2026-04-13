<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Tests\Fixtures;

use SimpleSquid\SaloonOData\Attributes\UsesODataVersion;
use SimpleSquid\SaloonOData\Enums\ODataVersion;

#[UsesODataVersion(ODataVersion::V3)]
class V3Request extends TestRequest {}
