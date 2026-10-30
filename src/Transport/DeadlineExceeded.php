<?php

declare(strict_types=1);

namespace EasySlip\Transport;

use EasySlip\Exception\RequestTimeoutError;

/** @internal The request consumed the enclosing job wait budget. */
final class DeadlineExceeded extends RequestTimeoutError {}
