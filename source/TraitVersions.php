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

trait TraitVersions
{

    use TraitBasic;

    private function establishCurrentVersion(array $arrayKnownVersions): array {
        $arrayVersionToReturn = [];
        foreach ($arrayKnownVersions as $value) {
            $dtValidityStart = new \DateTime($value['Validity']['Start']);
            $dtValidityEnd   = new \DateTime($value['Validity']['End']);
            $dtValidityNow   = new \DateTime();
            if (($dtValidityNow >= $dtValidityStart) && ($dtValidityNow <= $dtValidityEnd)) {
                $arrayVersionToReturn = [
                    'UBL'     => $value['UBL'],
                    'CIUS-RO' => $value['CIUS-RO'],
                ];
            }
        }
        return $arrayVersionToReturn;
    }

    private function getDefaultsIntoDataSet(array $arrayDocumentData): array {
        $arrayOutput = [];
        if (!array_key_exists('DocumentNameSpaces', $arrayDocumentData)) {
            $arrayVersions = $this->establishCurrentVersion($this->arraySettings['Versions']);
            $arrayOutput   = [
                'Root'    => [
                    'DocumentNameSpaces' => $this->arraySettings['Defaults']['DocumentNameSpaces'],
                    'SchemaLocation'     => vsprintf($this->arraySettings['Defaults']['SchemaLocation'], [
                        $arrayDocumentData['DocumentTagName'],
                        $arrayVersions['UBL'],
                        $arrayDocumentData['DocumentTagName'],
                        $arrayVersions['UBL'],
                    ]),
                ],
                'UBL'     => $arrayVersions['UBL'],
                'CIUS-RO' => $arrayVersions['CIUS-RO'],
            ];
        }
        return $arrayOutput;
    }

    private function getSettingsFromFileIntoMemory(bool $bolComments): void {
        $this->arraySettings             = $this->getJsonFromFile('ElectronicInvoiceSettings.json');
        $this->arraySettings['Comments'] = [
            'CAC' => [],
            'CBC' => [],
        ];
        if ($bolComments) {
            $this->getCommentsFromFileIntoSetting();
        }
    }

    private function getCommentsFromFileIntoSetting(): void {
        $strGlue                = ' | ';
        $arrayFlattenedComments = [];
        $arrayComments          = $this->getJsonFromFile('ElectronicInvoiceComments.json');
        foreach ($arrayComments as $key => $value) {
            $strComment = implode($strGlue, [
                    $key,
                    $value['OperationalTerm'],
                    $value['RequirementID'],
                ]) . (array_key_exists('SemanticDataType', $value) ? $strGlue . $value['SemanticDataType'] : '');
            if (is_array($value['HierarchycalTagName'])) {
                foreach ($value['HierarchycalTagName'] as $value2) {
                    $arrayFlattenedComments[$value2] = $strComment;
                }
            } else {
                $arrayFlattenedComments[$value['HierarchycalTagName']] = $strComment;
            }
        }
        $this->arraySettings['Comments'] = $arrayFlattenedComments;
    }

    private function setElementComment(string $strKey): void {
        if (array_key_exists($strKey, $this->arraySettings['Comments'])) {
            $elementComment = $this->arraySettings['Comments'][$strKey];
            if (is_array($elementComment)) {
                foreach ($elementComment as $value) {
                    $this->objXmlWriter->writeComment($value);
                }
            } else {
                $this->objXmlWriter->writeComment($elementComment);
            }
        }
    }
}
