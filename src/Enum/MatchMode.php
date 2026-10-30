<?php

declare(strict_types=1);

namespace EasySlip\Enum;

/** Fixed request values. Response values remain open strings. */
enum MatchMode: string
{
    case NAME = 'NAME';
    case NUMBER = 'NUMBER';
    case NAME_NUMBER = 'NAME_NUMBER';
}
