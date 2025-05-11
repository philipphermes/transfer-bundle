<?php

declare(strict_types = 1);

namespace PhilippHermes\TransferBundle\Transfer;

use ArrayObject;

class TransferCollectionTransfer
{
    /**
     * @var ArrayObject<array-key, TransferTransfer>
     */
    protected ArrayObject $transfers;

    /**
     * @var array<string>
     */
    protected array $errors = [];

    /**
     * @return ArrayObject<array-key, TransferTransfer>
     */
    public function getTransfers(): ArrayObject
    {
        if (!isset($this->transfers)) {
            return new ArrayObject();
        }

        return $this->transfers;
    }

    /**
     * @param ArrayObject<array-key, TransferTransfer> $transfers
     *
     * @return self
     */
    public function setTransfers(ArrayObject $transfers): self
    {
        $this->transfers = $transfers;

        return $this;
    }

    /**
     * @param TransferTransfer $transfer
     *
     * @return self
     */
    public function addTransfer(TransferTransfer $transfer): self
    {
        if (!isset($this->transfers)) {
            $this->transfers = new ArrayObject();
        }

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
     * @return self
     */
    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * @param string $error
     *
     * @return self
     */
    public function addError(string $error): self
    {
        $this->errors[] = $error;

        return $this;
    }
}