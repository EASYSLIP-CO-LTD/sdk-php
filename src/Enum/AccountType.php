<?php

declare(strict_types=1);

namespace EasySlip\Enum;

/** Fixed request values. Response values remain open strings. */
enum AccountType: string
{
    case NATURAL = 'NATURAL';
    case JURISTIC = 'JURISTIC';
}
