<?php

declare(strict_types=1);

namespace SimpleSquid\SaloonOData\Enums;

enum LogicalOperator: string
{
    case And = 'and';
    case Or = 'or';
    case Not = 'not';
}
