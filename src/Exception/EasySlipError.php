<?php

declare(strict_types=1);

namespace EasySlip\Exception;

/** Root of every error this SDK throws. Catch it to handle any SDK failure in one place. */
class EasySlipError extends \RuntimeException {}
