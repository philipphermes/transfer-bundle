<?php

namespace PhilippHermes\TransferBundle\Service\Model;

interface TransferCleanerInterface
{
    /**
     * @return void
     */
    public function clean(): void;
}