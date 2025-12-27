<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Transformer;

use Flux\VerifactuBundle\Dto\AeatResponseDto;
use josemmo\Verifactu\Models\Responses\AeatResponse;

final readonly class AeatResponseTransformer extends BaseTransformer
{
    public function transformModelToDto(AeatResponse $model): AeatResponseDto
    {
        return new AeatResponseDto(
            csv: $model->csv,
            submittedAt: $model->submittedAt,
            waitSecond: $model->waitSeconds,
            status: $model->status,
            items: $model->items,
        );
    }
}
