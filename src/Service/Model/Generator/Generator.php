<?php

declare(strict_types=1);

namespace PhilippHermes\TransferBundle\Service\Model\Generator;

use PhilippHermes\TransferBundle\Service\Model\Cleaner\TransferCleanerInterface;
use PhilippHermes\TransferBundle\Service\Model\Generator\GeneratorSteps\GeneratorStepInterface;
use PhilippHermes\TransferBundle\Service\Model\Generator\Helper\GeneratorHelperInterface;
use PhilippHermes\TransferBundle\Service\Model\Parser\TransferParserInterface;
use PhilippHermes\TransferBundle\Transfer\GeneratorConfigTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferCollectionTransfer;
use PhilippHermes\TransferBundle\Transfer\TransferTransfer;

class Generator implements GeneratorInterface
{
    /**
     * @var array<string>
     */
    protected array $transferTypes = [];

    /**
     * @param TransferParserInterface $transferParser
     * @param TransferCleanerInterface $transferCleaner
     * @param GeneratorHelperInterface $generatorHelper
     * @param array<GeneratorStepInterface> $generatorSteps
     */
    public function __construct(
        protected TransferParserInterface $transferParser,
        protected TransferCleanerInterface $transferCleaner,
        protected GeneratorHelperInterface $generatorHelper,
        protected array $generatorSteps,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function generate(GeneratorConfigTransfer $generatorConfigTransfer): TransferCollectionTransfer
    {
        $this->transferCleaner->clean($generatorConfigTransfer);

        $transferCollectionTransfer = $this->transferParser->parse($generatorConfigTransfer);

        foreach ($transferCollectionTransfer->getTransfers() as $transferTransfer) {
            $this->transferTypes[] = $transferTransfer->getName();
        }

        foreach ($transferCollectionTransfer->getTransfers() as $transferTransfer) {
            $this->generateTransfer($transferTransfer, $generatorConfigTransfer);
        }

        return $transferCollectionTransfer;
    }

    /**
     * @param TransferTransfer $transferTransfer
     * @param GeneratorConfigTransfer $generatorConfigTransfer
     *
     * @return void
     */
    protected function generateTransfer(TransferTransfer $transferTransfer, GeneratorConfigTransfer $generatorConfigTransfer): void
    {
        $transferTransfer = $this->generatorHelper->addRoleProperty($transferTransfer);

        foreach ($transferTransfer->getProperties() as $property) {
            $property->setIsBasic($this->generatorHelper->isBasicType($property->getType()));
            $property->setIsArray($this->generatorHelper->isArrayType($property->getType()));
            $property->setRealType($this->generatorHelper->getPropertyType($property->getType(), $this->transferTypes));
            $property->setAnnotationType($this->generatorHelper->getPropertyAnnotationType($property->getType(), $property->getRealType()));

            if ($property->getIsSensitive()) {
                $transferTransfer->addSensitiveProperty($property);
            }
        }

        $code = '';

        foreach ($this->generatorSteps as $generatorStep) {
            $code = $generatorStep->generate($generatorConfigTransfer, $transferTransfer, $code);
        }

        $filePath = sprintf(
            '%s/%sTransfer.php',
            $generatorConfigTransfer->getOutputDirectory(),
            $transferTransfer->getName()
        );

        if (!is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0777, true);
        }

        file_put_contents($filePath, $code);
    }
}