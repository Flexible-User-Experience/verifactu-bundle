<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Contract;

use josemmo\Verifactu\Models\Responses\ResponseItem;
use josemmo\Verifactu\Models\Responses\ResponseStatus;

interface AeatResponseInterface extends ValidatableInterface
{
    public function getCsv(): ?string;

    public function getSubmittedAt(): ?\DateTimeInterface;

    public function getWaitSeconds(): int;

    public function getStatus(): ResponseStatus;

    /**
     * @return ResponseItem[]
     */
    public function getItems(): array;
}
