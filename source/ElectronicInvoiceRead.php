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

class ElectronicInvoiceRead
{
    use TraitBasic;
    use TraitTax;
    use TraitLines;

    private function getAccountingCustomerOrSupplierParty(array $arrayIn): array
    {
        $arrayOut = [];
        foreach ($this->arraySettings['CustomOrder'][$arrayIn['type']] as $strElement) {
            if (isset($arrayIn['data']->children('cac', true)->$strElement)) {
                if ($strElement === 'PartyTaxScheme') {
                    $arrayOut[$strElement] = $this->getMultipleElementsByKey($arrayIn['data']
                            ->children('cac', true)->$strElement);
                } else {
                    $arrayOut[$strElement] = $this->getElements($arrayIn['data']->children('cac', true)->$strElement);
                }
            }
            if (isset($arrayIn['data']->children('cbc', true)->$strElement)) {
                if ($strElement === 'EndpointID') {
                    $arrayOut['EndpointID'] = [
                        'schemeID' => $arrayIn['data']->children('cbc', true)->EndpointID
                            ->attributes()->schemeID->__toString(),
                        'value'    => $arrayIn['data']->children('cbc', true)->EndpointID->__toString(),
                    ];
                } else {
                    $arrayOut[$strElement] = $this->getElements($arrayIn['data']->children('cbc', true)->$strElement);
                }
            }
        }
        return $arrayOut;
    }

    private function getDocumentRoot(object $objFile): array
    {
        $arrayDocument = [
            'DocumentTagName'    => $objFile->getName(),
            'DocumentNameSpaces' => $objFile->getDocNamespaces(true),
        ];
        if (array_key_exists('xsi', $arrayDocument['DocumentNameSpaces'])) {
            if (isset($objFile->attributes('xsi', true)->schemaLocation)) {
                $arrayDocument['SchemaLocation'] = $objFile->attributes('xsi', true)->schemaLocation;
            }
        }
        return $arrayDocument;
    }

    private function getElementsOrdered(array $arrayDataIn): array
    {
        $arrayOutput = [];
        foreach ($this->arraySettings['CustomOrder']['Header_CBC'] as $value) {
            if (isset($arrayDataIn['data']->$value)) {
                $arrayOutput[$value] = $arrayDataIn['data']->$value->__toString();
            }
        }
        return $arrayOutput;
    }

    private function getHeader(array $arrayParams): array
    {
        $arrayDocument = [
            'TaxTotal' => $this->getTax($arrayParams['CAC']->TaxTotal),
        ];
        foreach ($this->arraySettings['CustomOrder']['Header_CAC'] as $key => $value) {
            if (isset($arrayParams['CAC']->$key)) {
                $arrayDocument[$key] = $this->getHeaderComponents($arrayParams, $key, $value);
            }
        }
        return $arrayDocument;
    }

    private function getHeaderComponents(array $arrayParams, string $key, string $value): array | string
    {
        $arrayDocument = [];
        if ($value === 'SingleCompany') {
            $arrayDocument = [
                'Party' => $this->getAccountingCustomerOrSupplierParty([
                    'data' => $arrayParams['CAC']->$key->children('cac', true)->Party,
                    'type' => $key,
                ])
            ];
        } else {
            $arrayMapping  = [
                'Multiple'                  => [
                    'getMultipleElementsByKey'
                    , $arrayParams['data']->children('cac', true)->$key
                ],
                'MultipleStandard'          => ['getMultipleElementsStandard', $arrayParams['CAC']->$key],
                'Single'                    => ['getElements', $arrayParams['CAC']->$key],
                'SingleCompanyWithoutParty' => [
                    'getAccountingCustomerOrSupplierParty'
                    , [
                        'data' => $arrayParams['CAC']->$key,
                        'type' => $key,
                    ]
                ],
            ];
            $arrayDocument = $this->getRightMethod($arrayMapping[$value][0], $arrayMapping[$value][1]);
        }
        return $arrayDocument;
    }

    public function readElectronicInvoice(string $strFile): array
    {
        $this->getProcessingDetails();
        $this->getHierarchyTagOrder();
        $objFile                        = new \SimpleXMLElement($strFile, null, true);
        $arrayDocument                  = $this->getDocumentRoot($objFile);
        $arrayCBC                       = explode(':', $arrayDocument['DocumentNameSpaces']['cbc']);
        $arrayCommonBasicComponents     = $this->getElementsOrdered([
            'data'          => $objFile->children('cbc', true),
            'namespace_cbc' => $arrayDocument['DocumentNameSpaces']['cbc'],
        ]);
        $arrayCAC                       = explode(':', $arrayDocument['DocumentNameSpaces']['cac']);
        // CommonAggregateComponents
        $arrayCommonAggregateComponents = $this->getHeader([
            'CAC'  => $objFile->children('cac', true),
            'data' => $objFile,
        ]);
        $arrayDocument['Header']        = [
            $arrayCBC[count($arrayCBC) - 1] => $arrayCommonBasicComponents,
            $arrayCAC[count($arrayCAC) - 1] => $arrayCommonAggregateComponents,
        ];
        $arrayDocument['Lines']         = $this->getDocumentLines($objFile, $arrayDocument['DocumentTagName']);
        return $arrayDocument;
    }
}
