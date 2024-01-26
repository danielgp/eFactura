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

class ElectornicInvoiceRead
{

    use TraitBasic,
        TraitTax,
        TraitLines;

    private function getAccountingCustomerOrSupplierParty(array $arrayIn): array
    {
        $arrayOut = [];
        foreach ($this->arraySettings['CustomOrder'][$arrayIn['type']] as $strElement) {
            if (isset($arrayIn['data']->children('cac', true)->$strElement)) {
                if ($strElement === 'PartyTaxScheme') {
                    $intLineNo = 0;
                    foreach ($arrayIn['data']->children('cac', true)->PartyTaxScheme as $child) {
                        $intLineNo++;
                        $intLineStr                         = ($intLineNo < 10 ? '0' : '') . $intLineNo;
                        $arrayOut[$strElement][$intLineStr] = $this->getElements($child);
                    }
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
        $arrayCBC    = explode(':', $arrayDataIn['namespace_cbc']);
        foreach ($this->arraySettings['CustomOrder']['Header_CBC'] as $value) {
            if (isset($arrayDataIn['data']->$value)) {
                $arrayOutput[$value] = $arrayDataIn['data']->$value->__toString();
            }
        }
        return [$arrayCBC[(count($arrayCBC) - 1)] => $arrayOutput];
    }

    private function getHeader(array $arrayParams): array
    {
        $arrayDocument = $this->getElementsOrdered([
            'data'          => $arrayParams['CBC'],
            'namespace_cbc' => $arrayParams['DocumentNameSpaces']['cbc'],
        ]);
        $strCAC        = $arrayParams['cacName']; // CommonAggregateComponents
        $intLineNo     = 0;
        foreach ($arrayParams['CAC']->TaxTotal as $child) {
            $intLineNo++;
            $intLineStr                                      = ($intLineNo < 10 ? '0' : '') . $intLineNo;
            $arrayDocument[$strCAC]['TaxTotal'][$intLineStr] = $this->getTaxTotal($child);
        }
        // optional components =========================================================================================
        foreach ($this->arraySettings['CustomOrder']['Header_CAC'] as $key => $value) {
            if (isset($arrayParams['CAC']->$key)) {
                switch ($value) {
                    case 'Multiple':
                        $arrayDocument[$strCAC][$key]          = $this->getMultiplePaymentMeansElements($arrayParams['CAC']->$key);
                        break;
                    case 'MultipleStandard':
                        $arrayDocument[$strCAC][$key]          = $this->getMultipleElementsStandard($arrayParams['CAC']->$key);
                        break;
                    case 'Single':
                        $arrayDocument[$strCAC][$key]          = $this->getElements($arrayParams['CAC']->$key);
                        break;
                    case 'SingleCompany':
                        $arrayDocument[$strCAC][$key]['Party'] = $this->getAccountingCustomerOrSupplierParty([
                            'data' => $arrayParams['CAC']->$key->children('cac', true)->Party,
                            'type' => $key,
                        ]);
                        break;
                    case 'SingleCompanyWithoutParty':
                        $arrayDocument[$strCAC][$key]          = $this->getAccountingCustomerOrSupplierParty([
                            'data' => $arrayParams['CAC']->$key,
                            'type' => $key,
                        ]);
                        break;
                }
            }
        }
        return $arrayDocument;
    }

    private function getMultiplePaymentMeansElements(array|\SimpleXMLElement $arrayIn): array
    {
        $arrayToReturn = [];
        $intLineNo     = 0;
        foreach ($arrayIn as $child) {
            $intLineNo++;
            $intLineStr                 = ($intLineNo < 10 ? '0' : '') . $intLineNo;
            $arrayToReturn[$intLineStr] = $this->getElements($child);
        }
        return $arrayToReturn;
    }

    public function readElectronicInvoice(string $strFile): array
    {
        $this->getHierarchyTagOrder();
        $objFile                 = new \SimpleXMLElement($strFile, NULL, TRUE);
        $arrayDocument           = $this->getDocumentRoot($objFile);
        $arrayCAC                = explode(':', $arrayDocument['DocumentNameSpaces']['cac']);
        $strElementA             = $arrayCAC[count($arrayCAC) - 1]; // CommonAggregateComponents
        $arrayDocument['Header'] = $this->getHeader([
            'CAC'                => $objFile->children('cac', true),
            'cacName'            => $strElementA,
            'CBC'                => $objFile->children('cbc', true),
            'DocumentNameSpaces' => $arrayDocument['DocumentNameSpaces'],
            'DocumentTagName'    => $arrayDocument['DocumentTagName'],
        ]);
        $arrayDocument['Lines']  = $this->getDocumentLines($objFile, $arrayDocument['DocumentTagName']);
        return $arrayDocument;
    }
}
