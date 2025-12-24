<?php

declare(strict_types=1);

namespace Flux\VerifactuBundle\Factory;

use Flux\VerifactuBundle\Contract\RegistrationRecordInterface;
use Flux\VerifactuBundle\Dto\RegistrationRecordDto;
use Flux\VerifactuBundle\Transformer\RegistrationRecordTransformer;
use Flux\VerifactuBundle\Validator\ContractsValidator;
use josemmo\Verifactu\Models\Records\RegistrationRecord;

final readonly class RegistrationRecordFactory
{
    public function __construct(
        private RegistrationRecordTransformer $registrationRecordTransformer,
        private ContractsValidator $validator,
    ) {
    }

    public function makeValidatedRegistrationRecordDtoFromInterface(RegistrationRecordInterface $input): RegistrationRecordDto
    {
        $registrationRecordDto = $this->registrationRecordTransformer->transformInterfaceToModel($input);
        $this->validator->validate($registrationRecordDto);

        return $registrationRecordDto;
    }

    public function makeValidatedRegistrationRecordModelFromDto(RegistrationRecordDto $input): RegistrationRecord
    {
        return $this->registrationRecordTransformer->transformDtoToModel($input);
    }
}
