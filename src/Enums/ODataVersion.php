<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Enums;

enum ODataVersion: string
{
    case V3 = '3.0';
    case V4 = '4.0';
}
