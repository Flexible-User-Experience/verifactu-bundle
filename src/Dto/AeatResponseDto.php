<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Dto;

use Flux\VerifactuBundle\Contract\AeatResponseInterface;
use josemmo\Verifactu\Models\Responses\ResponseItem;
use josemmo\Verifactu\Models\Responses\ResponseStatus;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AeatResponseDto implements AeatResponseInterface
{
    public function __construct(
        public ?string $csv,
        public ?\DateTimeInterface $submittedAt,
        #[Assert\NotBlank]
        #[Assert\Positive]
        public int $waitSecond,
        #[Assert\NotBlank]
        public ResponseStatus $status,
        #[Assert\Valid]
        public array $items = [],
    ) {
    }

    public function getCsv(): ?string
    {
        return $this->csv;
    }

    public function getSubmittedAt(): ?\DateTimeInterface
    {
        return $this->submittedAt;
    }

    public function getWaitSeconds(): int
    {
        return $this->waitSecond;
    }

    public function getStatus(): ResponseStatus
    {
        return $this->status;
    }

    /**
     * @return ResponseItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
