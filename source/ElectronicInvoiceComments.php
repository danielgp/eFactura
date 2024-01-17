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

class ElectronicInvoiceComments
{

    use TraitVersions;

    public function getCommentsIntoArrayForTable()
    {
        $arrayCommentsRaw      = $this->getCommentsFromFileAsArray();
        $arrayCommentsForTable = [];
        foreach ($arrayCommentsRaw as $key => $value) {
            $arrayCommentsForTable[] = [
                'ID'               => $key,
                'Tag'              => $value['HierarchycalTagName'],
                'Level'            => $value['Level'],
                'Cardinality'      => $value['Cardinality'],
                'OperationalTerm'  => $value['OperationalTerm'],
                'Description_RO'   => $value['Description']['ro_RO'],
                'Description_EN'   => (array_key_exists('en_US', $value['Description']) ? $value['Description']['en_US'] : '-'),
                'UsageNote'        => (array_key_exists('UsageNote', $value) ? $value['UsageNote'] : '-'),
                'RequirementID'    => $value['RequirementID'],
                'SemanticDataType' => (array_key_exists('SemanticDataType', $value) ? $value['SemanticDataType'] : '-'),
            ];
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
}
