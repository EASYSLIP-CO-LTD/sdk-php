<?php

declare(strict_types=1);

namespace EasySlip\Exception;

use EasySlip\Model\Job;

/** Polling deadline expired; the server job continues independently. */
class JobWaitTimeoutError extends EasySlipError
{
    public function __construct(public readonly ?Job $lastJob)
    {
        parent::__construct('Timed out waiting for the job');
    }
}
