<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Transformer;

use Flux\VerifactuBundle\Contract\AeatResponseInterface;
use Flux\VerifactuBundle\Dto\AeatResponseDto;
use josemmo\Verifactu\Models\Responses\AeatResponse;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class AeatResponseTransformer extends BaseTransformer
{
    public const FORMAT_JSON = 'json';

    public function __construct(
        private SerializerInterface $serializer,
    ) {
    }

    public function transformInterfaceToDto(AeatResponseInterface $input): AeatResponseDto
    {
        return new AeatResponseDto(
            csv: $input->getCsv(),
            submittedAt: $input->getSubmittedAt(),
            waitSecond: $input->getWaitSeconds(),
            status: $input->getStatus(),
            items: $input->getItems(),
        );
    }

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

    public function transformDtoToArray(AeatResponseDto $dto): array
    {
        return $this->serializer->normalize($dto, self::FORMAT_JSON);
    }

    public function transformArrayToDto(array $aeatResponseArray): AeatResponseDto
    {
        return $this->serializer->denormalize($aeatResponseArray, AeatResponseDto::class);
    }

    public function transformDtoToJson(AeatResponseDto $dto): string
    {
        return $this->serializer->serialize($dto, self::FORMAT_JSON);
    }

    public function transformJsonToDto(string $json): AeatResponseDto
    {
        return $this->serializer->deserialize($json, AeatResponseDto::class, self::FORMAT_JSON);
    }
}
