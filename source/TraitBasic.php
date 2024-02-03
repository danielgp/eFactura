<?php

/*
 * Copyright (c) 2024, Daniel Popiniuc and its licensors.
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Daniel Popiniuc
 */

namespace danielgp\efactura;

trait TraitBasic
{
    protected array $arraySettings   = [];
    protected array $arrayProcessing = [];

    private function getCommentsFromFileAsArray(): array
    {
        return $this->getJsonFromFile('config/ElectronicInvoiceComments.json');
    }

    private function getElements(\SimpleXMLElement | null $arrayIn): array
    {
        $arrayToReturn = [];
        if (!is_null($arrayIn)) {
            if (count($arrayIn->children('cbc', true)) !== 0) { // checking if we have cbc elements
                foreach ($arrayIn->children('cbc', true) as $key => $value) {
                    $arrayToReturn[$key] = $this->getElementSingle($value);
                }
            }
            if (count($arrayIn->children('cac', true)) !== 0) { // checking if we have cac elements
                foreach ($arrayIn->children('cac', true) as $key => $value) {
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
                    $arrayToReturn[$keyA] = $valueA->__toString();
                }
            }
        }
        return $arrayToReturn;
    }

    private function getJsonFromFile(string $strFileName): array
    {
        $strFileName = __DIR__ . DIRECTORY_SEPARATOR . $strFileName;
        if (!file_exists($strFileName)) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('File %s does not exists!', $strFileName));
            // @codeCoverageIgnoreEnd
        }
        $fileHandle = fopen($strFileName, 'r');
        if ($fileHandle === false) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Unable to open file %s for read purpose!', $strFileName));
            // @codeCoverageIgnoreEnd
        }
        $fileContent   = fread($fileHandle, ((int) filesize($strFileName)));
        fclose($fileHandle);
        $arrayToReturn = json_decode($fileContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Unable to interpret JSON from %s file...', $strFileName));
            // @codeCoverageIgnoreEnd
        }
        return $arrayToReturn;
    }

    private function getHierarchyTagOrder(): void
    {
        $this->arraySettings['CustomOrder'] = $this->getJsonFromFile('config/ElectronicInvoiceHierarchyTagOrder.json');
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
            foreach ($child->children('cbc', true) as $key2 => $value2) {
                if (count($value2->attributes()) === 0) {
                    $arrayToReturn[$intLineStr][$key2] = $value2->__toString();
                } else {
                    $arrayToReturn[$intLineStr][$key2]['value'] = $value2->__toString();
                    foreach ($value2->attributes() as $keyA => $valueA) {
                        $arrayToReturn[$intLineStr][$key2][$keyA] = $valueA->__toString();
                    }
                }
            }
            foreach ($child->children('cac', true) as $key2 => $value2) {
                $arrayToReturn[$intLineStr][$key2] = $this->getElements($value2);
            }
        }
        return $arrayToReturn;
    }

    private function getProcessingDetails(): void
    {
        $this->arrayProcessing = $this->getJsonFromFile('config/ElectronicInvoiceProcessingDetails.json');
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
}
