<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Tests\Fixtures;

use SimpleSquid\SaloonOData\Attributes\ODataVersion;
use SimpleSquid\SaloonOData\Enums\ODataVersion as VersionEnum;

#[ODataVersion(VersionEnum::V3)]
class V3Connector extends TestConnector {}
