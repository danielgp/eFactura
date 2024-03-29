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

    private function getCommentsIntoArrayForVerifications(): array
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

    private function getKeyFromArrayOrAlternative(string $strKey, array $arrayIn, string $strAlternative): string
    {
        $strToReturn = $strAlternative;
        if (array_key_exists($strKey, $arrayIn)) {
            $strToReturn = $arrayIn[$strKey];
        }
        return $strToReturn;
    }

    private function getOneOrMultipleTags(string | array $inElement, array $arrayIn): array
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
