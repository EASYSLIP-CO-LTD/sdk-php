<?php

declare(strict_types=1);

namespace EasySlip\Exception;

/** ServiceUnavailableError: see the API contract for retry and handling semantics. */
class ServiceUnavailableError extends ServerError {}
