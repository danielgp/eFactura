<?php

/**
 *
 * The MIT License (MIT)
 *
 * Copyright (c) 2024 Daniel Popiniuc
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 */

namespace danielgp\efactura;

trait TraitBasic
{

    protected array $arraySettings = [];

    private function getCommentsFromFileAsArray(): array
    {
        return $this->getJsonFromFile('json/ElectronicInvoiceComments.json');
    }

    private function getElements(\SimpleXMLElement|null $arrayIn): array
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

    private function getElementSingle(\SimpleXMLElement|null $value)
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
            throw new \RuntimeException(sprintf('File %s does not exists!', $strFileName));
        }
        $fileHandle = fopen($strFileName, 'r');
        if ($fileHandle === false) {
            throw new \RuntimeException(sprintf('Unable to open file %s for read purpose!', $strFileName));
        }
        $fileContent   = fread($fileHandle, ((int) filesize($strFileName)));
        fclose($fileHandle);
        $arrayToReturn = json_decode($fileContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \RuntimeException(sprintf('Unable to interpret JSON from %s file...', $strFileName));
        }
        return $arrayToReturn;
    }

    private function getHierarchyTagOrder(): void
    {
        $this->arraySettings['CustomOrder'] = $this->getJsonFromFile('json/ElectronicInvoiceHierarchyTagOrder.json');
    }

    private function getLineStringFromNumber(int $intLineNo): string
    {
        return ($intLineNo < 10 ? '0' : '') . $intLineNo;
    }

    private function getMultipleElementsStandard(array|\SimpleXMLElement $arrayIn): array
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
}
