<?php

declare(strict_types=1);

namespace EasySlip\Exception;

use EasySlip\Model\BatchSubmission;

/** Earlier batches may have spent quota; retry only the failed remainder. */
class BatchSubmitError extends EasySlipError
{
    /** @param list<BatchSubmission> $submitted */
    public function __construct(public readonly array $submitted, public readonly int $failedChunk, public readonly EasySlipError $cause)
    {
        parent::__construct('Batch submission stopped at chunk ' . $failedChunk, 0, $cause);
    }
}
