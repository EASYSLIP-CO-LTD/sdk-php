<?php

declare(strict_types=1);

namespace EasySlip\Exception;

/** SlipPendingError: see the API contract for retry and handling semantics. */
class SlipPendingError extends NotFoundError {}
