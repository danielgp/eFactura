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

    private function establishCurrentVersion(array $arrayKnownVersions): array
    {
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

    private function getDefaultsIntoDataSet(array $arrayDocumentData, bool $bolSchemaLocation): array
    {
        $arrayVersions = $this->establishCurrentVersion($this->arraySettings['Versions']);
        $arrayOutput   = [];
        if (!array_key_exists('DocumentNameSpaces', $arrayDocumentData)) {
            $arrayOutput = [
                'Root'    => [
                    'DocumentNameSpaces' => $this->arraySettings['Defaults']['DocumentNameSpaces'],
                ],
                'UBL'     => $arrayVersions['UBL'],
                'CIUS-RO' => $arrayVersions['CIUS-RO'],
            ];
        }
        if ($bolSchemaLocation && !array_key_exists('SchemaLocation', $arrayDocumentData)) {
            $arrayOutput['Root']['SchemaLocation'] = vsprintf($this->arraySettings['Defaults']['SchemaLocation'], [
                $arrayDocumentData['DocumentTagName'],
                $arrayVersions['UBL'],
                $arrayDocumentData['DocumentTagName'],
                $arrayVersions['UBL'],
            ]);
        }
        return $arrayOutput;
    }

    private function getSettingsFromFileIntoMemory(bool $bolComments): void
    {
        $this->arraySettings             = $this->getJsonFromFile('json/ElectronicInvoiceSettings.json');
        $this->getHierarchyTagOrder();
        $this->arraySettings['Comments'] = [
            'CAC' => [],
            'CBC' => [],
        ];
        if ($bolComments) {
            $this->getCommentsFromFileIntoSetting();
        }
    }

    private function getCommentsFromFileIntoSetting(): void
    {
        $strGlue                = ' | ';
        $arrayFlattenedComments = [];
        $arrayComments          = $this->getCommentsFromFileAsArray();
        foreach ($arrayComments as $key => $value) {
            $strComment = implode($strGlue, [
                    $key,
                    $value['OperationalTerm']['ro_RO'],
                    $value['RequirementID'],
                ])
                . (array_key_exists('SemanticDataType', $value) ? $strGlue . $value['SemanticDataType'] : '');
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

    private function loadSettingsAndManageDefaults(array $arrayData, array $arrayFeatures): array
    {
        // if no DocumentNameSpaces seen take Default ones from local configuration
        $this->getSettingsFromFileIntoMemory($arrayFeatures['Comments']);
        $arrayDefaults = $this->getDefaultsIntoDataSet($arrayData, $arrayFeatures['SchemaLocation']);
        if ($arrayDefaults !== []) {
            $arrayData = array_merge($arrayData, $arrayDefaults['Root']);
            if (!array_key_exists('CustomizationID', $arrayData['Header']['CommonBasicComponents-2'])) {
                $arrayData['Header']['CommonBasicComponents-2']['CustomizationID'] = 'urn:cen.eu:en16931:2017'
                    . '#compliant#urn:efactura.mfinante.ro:CIUS-RO:' . $arrayDefaults['CIUS-RO'];
                $arrayData['Header']['CommonBasicComponents-2']['UBLVersionID']    = $arrayDefaults['UBL'];
            }
        }
        return $arrayData;
    }

    private function setCategorizedVerifications(array $arrayDataIn)
    {
        $strCategoryToReturn = '';
        $key                 = implode('_', [$arrayDataIn['commentParentKey'], $arrayDataIn['tag']]);
        $arrayVarious        = $this->arrayProcessingDetails['WritingCatgoryization'];
        if (array_key_exists($key, $arrayVarious['Key'])) {
            $strCategoryToReturn = $arrayVarious['Key'][$key];
        } elseif (array_key_exists($arrayDataIn['tag'], $arrayVarious['Tag'])) {
            $strCategoryToReturn = $arrayVarious['Tag'][$arrayDataIn['tag']];
        } elseif (in_array($arrayDataIn['commentParentKey'], $arrayVarious['CommentParrentKey'])) {
            $strCategoryToReturn = 'SingleElementWithAttribute';
        } elseif ($arrayDataIn['matches'] !== []) {
            $strCategoryToReturn = 'SingleElementWithAttribute';
        } elseif (is_array($arrayDataIn['data'])) {
            $strCategoryToReturn = 'ElementsOrdered';
        } else {
            $strCategoryToReturn = 'SingleElementWithAttribute';
        }
        return $strCategoryToReturn;
    }

    private function setManageComment(string $strCommentParentKey, array $arrayIn): string
    {
        if (str_starts_with($strCommentParentKey, 'AllowanceCharge')) {
            $arrayCommentPieces = explode('_', $strCommentParentKey);
            // carefully manage a child to decide on comment tag
            $strChargeIndicator = $arrayIn['ChargeIndicator'];
            if (in_array($strChargeIndicator, ['0', '1'])) {
                $strChargeIndicator = [
                    '0' => 'false',
                    '1' => 'true',
                    ][$arrayIn['ChargeIndicator']];
            }
            array_splice($arrayCommentPieces, 0, 1, 'AllowanceCharge~ChargeIndicator'
                . ucfirst($strChargeIndicator));
            $strCommentParentKey = implode('_', $arrayCommentPieces);
        }
        return $strCommentParentKey;
    }

    protected function setNumericValue(string $strTag, array $arrayDataIn): string|float
    {
        $sReturn      = $arrayDataIn['value'];
        $arrayRawTags = ['CreditedQuantity', 'EndpointID', 'InvoicedQuantity', 'ItemClassificationCode', 'PriceAmount'];
        if (is_numeric($arrayDataIn['value']) && !in_array($strTag, $arrayRawTags)) {
            $fmt = new \NumberFormatter('en_US', \NumberFormatter::DECIMAL);
            $fmt->setAttribute(\NumberFormatter::GROUPING_USED, 0);
            $fmt->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
            // if contains currencyID consider 2 decimals as minimum
            if (in_array('currencyID', array_keys($arrayDataIn))) {
                $fmt->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2);
            }
            $fmt->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);
            $sReturn = $fmt->format($arrayDataIn['value']);
        }
        return $sReturn;
    }
}
