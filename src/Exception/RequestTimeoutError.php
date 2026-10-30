<?php

declare(strict_types=1);

namespace EasySlip\Exception;

/** RequestTimeoutError: see the API contract for retry and handling semantics. */
class RequestTimeoutError extends NetworkError {}
