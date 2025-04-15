<?php

namespace PhilippHermes\TransferBundle\Service\Model;

use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;

interface XmlSchemaParserInterface
{
    /**
     * @return TransferCollectionTransfer
     */
    public function parse(): TransferCollectionTransfer;
}