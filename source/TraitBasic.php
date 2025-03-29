<?php

/*
 * Copyright (c) 2024, Daniel Popiniuc and its licensors.
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v20.html
 *
 * Contributors:
 *    Daniel Popiniuc
 */

namespace danielgp\efactura;

trait TraitBasic
{
    use \danielgp\io_operations\InputOutputFiles;

    protected array $arraySettings   = [];
    protected array $arrayProcessing = [];

    private function getCommentsFromFileAsArray(): array
    {
        return $this->getArrayFromJsonFile(__DIR__
                . DIRECTORY_SEPARATOR . 'config', 'ElectronicInvoiceComments.json');
    }

    private function getElements(\SimpleXMLElement | null $arrayIn): array
    {
        $arrayToReturn = [];
        if (!is_null($arrayIn)) {
            if (count($arrayIn->children($this->arrayProcessing['mapping']['cbc'], true)) !== 0) { // checking if we have cbc elements
                foreach ($arrayIn->children($this->arrayProcessing['mapping']['cbc'], true) as $key => $value) {
                    $arrayToReturn[$key] = $this->getElementSingle($value);
                }
            }
            if (count($arrayIn->children($this->arrayProcessing['mapping']['cac'], true)) !== 0) { // checking if we have cac elements
                foreach ($arrayIn->children($this->arrayProcessing['mapping']['cac'], true) as $key => $value) {
                    $arrayToReturn[$key] = $this->getElements($value);
                }
            }
        }
        return $arrayToReturn;
    }

    private function getElementSingle(\SimpleXMLElement | null $value)
    {
        $arrayToReturn = [];
        if (!is_null($value)) {
            if (count($value->attributes()) === 0) {
                $arrayToReturn = $value->__toString();
            } else {
                $arrayToReturn['value'] = $value->__toString();
                foreach ($value->attributes() as $keyA => $valueA) {
                    if (str_ends_with($valueA, ':CommonAggregateComponents-2')) {
                        // nothing
                    } else {
                        $arrayToReturn[$keyA] = $valueA->__toString();
                    }
                }
            }
        }
        return $arrayToReturn;
    }

    private function getHierarchyTagOrder(): void
    {
        $this->arraySettings['CustomOrder'] = $this->getArrayFromJsonFile(__DIR__
            . DIRECTORY_SEPARATOR . 'config', 'ElectronicInvoiceHierarchyTagOrder.json');
    }

    private function getLineStringFromNumber(int $intLineNo): string
    {
        return ($intLineNo < 10 ? '0' : '') . $intLineNo;
    }

    private function getMultipleElementsByKey(\SimpleXMLElement $arrayData): array
    {
        $arrayOutput = [];
        $intLineNo   = 0;
        foreach ($arrayData as $value2) {
            $intLineNo++;
            $intLineStr               = $this->getLineStringFromNumber($intLineNo);
            $arrayOutput[$intLineStr] = $this->getElements($value2);
        }
        return $arrayOutput;
    }

    private function getMultipleElementsStandard(array | \SimpleXMLElement $arrayIn): array
    {
        $arrayToReturn = [];
        $intLineNo     = 0;
        foreach ($arrayIn as $child) {
            $intLineNo++;
            $intLineStr = $this->getLineStringFromNumber($intLineNo);
            foreach ($child->children($this->arrayProcessing['mapping']['cbc'], true) as $key2 => $value2) {
                if (count($value2->attributes()) === 0) {
                    $arrayToReturn[$intLineStr][$key2] = $value2->__toString();
                } else {
                    $arrayToReturn[$intLineStr][$key2]['value'] = $value2->__toString();
                    foreach ($value2->attributes() as $keyA => $valueA) {
                        if (str_ends_with($valueA, ':CommonAggregateComponents-2')) {
                            // nothing
                        } else {
                            $arrayToReturn[$intLineStr][$key2][$keyA] = $valueA->__toString();
                        }
                    }
                }
            }
            foreach ($child->children($this->arrayProcessing['mapping']['cac'], true) as $key2 => $value2) {
                $arrayToReturn[$intLineStr][$key2] = $this->getElements($value2);
            }
        }
        return $arrayToReturn;
    }

    private function getProcessingDetails(): void
    {
        $this->arrayProcessing = $this->getArrayFromJsonFile(__DIR__
            . DIRECTORY_SEPARATOR . 'config', 'ElectronicInvoiceProcessingDetails.json');
    }

    public function getRightMethod(string $existingFunction, $givenParameters = null): array | string
    {
        try {
            if (is_array($givenParameters)) {
                return call_user_func_array([$this, $existingFunction], [$givenParameters]);
            } else {
                return call_user_func([$this, $existingFunction], $givenParameters);
            }
            // @codeCoverageIgnoreStart
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            return false;
        }
        // @codeCoverageIgnoreEnd
    }

    protected function loadSettingsFromFile(): void
    {
        $this->arraySettings = $this->getArrayFromJsonFile(__DIR__
            . DIRECTORY_SEPARATOR . 'config', 'ElectronicInvoiceSettings.json');
    }
}
