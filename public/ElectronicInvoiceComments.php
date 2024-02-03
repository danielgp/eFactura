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
 * to use, copy, modify, merge, publish, distribute, sub-license, and/or sell
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

class ElectronicInvoiceComments
{
    use TraitBasic;

    public function getCommentsIntoArrayForTable()
    {
        $arrayCommentsRaw   = $this->getCommentsFromFileAsArray();
        $arrayCommentsTable = [];
        foreach ($arrayCommentsRaw as $key => $value) {
            $arrayTemp = [
                'ID'                 => $key,
                'ID_Type'            => $this->getTypeOfIdentifier(explode('-', $key)[0]),
                'Level_No'           => strlen($value['Level']),
                'Level'              => $value['Level'],
                'Cardinality'        => $value['Cardinality'],
                'OperationalTerm_EN' => $this->getKeyFromArrayOrAlternative('en_US', $value['OperationalTerm'], '-'),
                'OperationalTerm_RO' => $this->getKeyFromArrayOrAlternative('ro_RO', $value['OperationalTerm'], '-'),
                'Description_EN'     => $this->getKeyFromArrayOrAlternative('en_US', $value['Description'], '-'),
                'Description_RO'     => $this->getKeyFromArrayOrAlternative('ro_RO', $value['Description'], '-'),
                'UsageNote_EN'       => '-',
                'UsageNote_RO'       => '-',
                'RequirementID'      => $value['RequirementID'],
                'SemanticDataType'   => $this->getKeyFromArrayOrAlternative('SemanticDataType', $value, '-'),
            ];
            if (array_key_exists('UsageNote', $value)) {
                $arrayTemp['UsageNote_EN'] = $this->getKeyFromArrayOrAlternative('en_US', $value['UsageNote'], '-');
                $arrayTemp['UsageNote_RO'] = $this->getKeyFromArrayOrAlternative('ro_RO', $value['UsageNote'], '-');
            }
            $arrayCommentsTable[] = $this->getOneOrMultipleTags($value['HierarchycalTagName'], $arrayTemp);
        }
        return $arrayCommentsTable;
    }

    public function getCommentsIntoArrayForVerifications(): array
    {
        $arrayCommentsRaw   = $this->getCommentsFromFileAsArray();
        $arrayCommentsTable = [];
        foreach ($arrayCommentsRaw as $key => $value) {
            if (array_key_exists('SemanticDataType', $value)) {
                $arrayCommentsTable[$value['SemanticDataType']] = [
                    'ID' => $key,
                ];
            }
        }
        return $arrayCommentsTable;
    }

    public function getKeyFromArrayOrAlternative(string $strKey, array $arrayIn, string $strAlternative): string
    {
        $strToReturn = $strAlternative;
        if (array_key_exists($strKey, $arrayIn)) {
            $strToReturn = $arrayIn[$strKey];
        }
        return $strToReturn;
    }

    private function getOneOrMultipleTags(string|array $inElement, array $arrayIn): array
    {
        $arrayToReturn = [];
        if (is_array($inElement)) {
            foreach ($inElement as $value2) {
                $arrayToReturn = array_merge(['Tag' => $value2], $arrayIn);
            }
        } else {
            $arrayToReturn = array_merge(['Tag' => $inElement], $arrayIn);
        }
        return $arrayToReturn;
    }

    private function getTypeOfIdentifier(string $strKeyPrefix): string
    {
        $arrayMapping = [
            'BG'    => 'Group',
            'BT'    => 'Field',
            'UBL21' => 'Spec',
        ];
        $strIdType    = 'unknown';
        if (in_array($strKeyPrefix, array_keys($arrayMapping))) {
            $strIdType = $arrayMapping[$strKeyPrefix];
        }
        return $strIdType;
    }
}
