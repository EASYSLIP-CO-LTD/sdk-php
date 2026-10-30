<?php

declare(strict_types=1);

namespace EasySlip\Exception;

use EasySlip\Model\BankSlip;
use EasySlip\Model\TrueWalletSlip;

/** v1 duplicate response with the original typed slip when supplied. */
class DuplicateSlipError extends InvalidRequestError
{
    public BankSlip|TrueWalletSlip|null $slip = null;
}
