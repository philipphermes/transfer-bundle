<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Transfer;

use ArrayObject;

class TransferCollection
{
    /**
     * @var ArrayObject<array-key, Transfer>
     */
    protected ArrayObject $transfers;

    /**
     * @var array<string>
     */
    protected array $errors = [];

    /**
     * @return ArrayObject<array-key, Transfer>
     */
    public function getTransfers(): ArrayObject
    {
        return $this->transfers;
    }

    /**
     * @param ArrayObject<array-key, Transfer> $transfers
     *
     * @return $this
     */
    public function setTransfers(ArrayObject $transfers): self
    {
        $this->transfers = $transfers;

        return $this;
    }

    /**
     * @param Transfer $transfer
     *
     * @return $this
     */
    public function addTransfer(Transfer $transfer): self
    {
        $this->transfers->append($transfer);

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param array<string> $errors
     *
     * @return $this
     */
    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @param string $error
     *
     * @return $this
     */
    public function addError(string $error): self
    {
        $this->errors[] = $error;

        return $this;
    }
}