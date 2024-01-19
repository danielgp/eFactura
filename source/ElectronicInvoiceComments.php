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

    use TraitVersions;

    public function getCommentsIntoArrayForTable()
    {
        $arrayCommentsRaw      = $this->getCommentsFromFileAsArray();
        $arrayCommentsForTable = [];
        $arrayMapping          = [
            'BG-' => 'Group',
            'BT-' => 'Field',
        ];
        foreach ($arrayCommentsRaw as $key => $value) {
            $strKeyPrefix = substr($key, 0, 3);
            $strIdType    = (in_array($strKeyPrefix, array_keys($arrayMapping)) ? $arrayMapping[$strKeyPrefix] : '?');
            $arrayTemp    = [
                'ID'                 => $key,
                'IT_Type'            => $strIdType,
                'Level'              => $value['Level'],
                'Cardinality'        => $value['Cardinality'],
                'OperationalTerm_EN' => $this->getKeyFromArrayOrAlternative('en_US', $value['OperationalTerm'], '-'),
                'OperationalTerm_RO' => $this->getKeyFromArrayOrAlternative('ro_RO', $value['OperationalTerm'], '-'),
                'Description_EN'     => $this->getKeyFromArrayOrAlternative('en_US', $value['Description'], '-'),
                'Description_RO'     => $this->getKeyFromArrayOrAlternative('ro_RO', $value['Description'], '-'),
                'UsageNote_EN'       => $this->getKeyFromArrayOrAlternative('en_US', $value['UsageNote'], $value, '-'),
                'UsageNote_RO'       => $this->getKeyFromArrayOrAlternative('ro_RO', $value['UsageNote'], $value, '-'),
                'RequirementID'      => $value['RequirementID'],
                'SemanticDataType'   => $this->getKeyFromArrayOrAlternative('SemanticDataType', $value, '-'),
            ];
            if (is_array($value['HierarchycalTagName'])) {
                foreach ($value['HierarchycalTagName'] as $value2) {
                    $arrayCommentsForTable[] = array_merge(['Tag' => $value2], $arrayTemp);
                }
            } else {
                $arrayCommentsForTable[] = array_merge(['Tag' => $value['HierarchycalTagName']], $arrayTemp);
            }
        }
        return $arrayCommentsForTable;
    }

    public function getCommentsIntoArrayForVerifications()
    {
        $arrayCommentsRaw      = $this->getCommentsFromFileAsArray();
        $arrayCommentsForTable = [];
        foreach ($arrayCommentsRaw as $key => $value) {
            if (array_key_exists('SemanticDataType', $value)) {
                $arrayCommentsForTable[$value['SemanticDataType']] = [
                    'ID' => $key,
                ];
            }
        }
        return $arrayCommentsForTable;
    }

    public function getKeyFromArrayOrAlternative(string $strKey, array $arrayIn, string $strAlternative): string
    {
        $strToReturn = $strAlternative;
        if (array_key_exists($strKey, $arrayIn)) {
            $strToReturn = $arrayIn[$strKey];
        }
        return $strToReturn;
    }
}
