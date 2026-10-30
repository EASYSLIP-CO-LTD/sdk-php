<?php

declare(strict_types=1);

namespace EasySlip\Input;

/** Distinguishes an omitted field from an explicit null. */
enum UnsetValue
{
    case Omitted;
}
