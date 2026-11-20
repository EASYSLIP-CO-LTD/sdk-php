<?php

declare(strict_types=1);

namespace EasySlip\Model;

/** Typed API data; has() distinguishes omitted fields from explicit null. */
final readonly class TrueWalletReceiver extends Model
{
    public ?string $name;
    public ?string $phone;

    /** @param array<string, mixed> $data */
    public function __construct(array $data, ?ResponseMeta $meta = null)
    {
        parent::__construct($data, $meta);
        $this->name = !isset($data['name']) ? null : $this->string($data, 'name');
        $this->phone = !isset($data['phone']) ? null : $this->string($data, 'phone');
    }
}
