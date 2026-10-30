<?php

declare(strict_types=1);

namespace EasySlip\Exception;

/** QuotaExceededError: see the API contract for retry and handling semantics. */
class QuotaExceededError extends PermissionDeniedError {}
