<?php

declare(strict_types=1);

namespace FlexibleUx\VerifactuBundle\Contract;

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

    /**
     * Records AEAT stored, which are the ones that entered the invoice chain. Both "Correcto" and
     * "AceptadoConErrores" count: the latter is registered in spite of the reported errors.
     *
     * @return ResponseItem[]
     */
    public function getRegisteredItems(): array;

    /**
     * Records AEAT refused ("Incorrecto"). They never entered the chain, so their hash must not be
     * persisted and the next record has to keep chaining to the last registered one.
     *
     * @return ResponseItem[]
     */
    public function getRejectedItems(): array;

    /**
     * Whether every record of the submission was registered. Reading the envelope status is not
     * enough: "ParcialmenteCorrecto" says some record failed but not which one, and a response
     * carrying no record at all is never an acceptance.
     */
    public function isAccepted(): bool;

    /**
     * Description of the first refused record, or null when nothing was refused.
     */
    public function getErrorDescription(): ?string;
}
