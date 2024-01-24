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

    use TraitCompanies,
        TraitTax,
        TraitLines;

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

    private function getHeader(array $arrayParams): array
    {
        $arrayCBC      = explode(':', $arrayParams['DocumentNameSpaces']['cbc']);
        $strCBC        = $arrayCBC[count($arrayCBC) - 1]; // CommonBasicComponents
        $strCAC        = $arrayParams['cacName']; // CommonAggregateComponents
        $arrayDocument = [
            $strCBC => $this->getHeaderCommonBasicComponents($arrayParams['DocumentTagName'], $arrayParams['CBC']),
            $strCAC => [
                'AccountingCustomerParty' => $this->getAccountingCustomerParty($arrayParams['CAC']
                    ->AccountingCustomerParty->children('cac', true)->Party),
                'AccountingSupplierParty' => $this->getAccountingSupplierParty($arrayParams['CAC']
                    ->AccountingSupplierParty->children('cac', true)->Party),
                'TaxTotal'                => $this->getTaxTotal($arrayParams['CAC']->TaxTotal),
            ],
        ];
        // optional components =========================================================================================
        foreach ($this->arraySettings['CustomOrder']['Header_CAC'] as $key => $value) {
            if (isset($arrayParams['CAC']->$key)) {
                switch ($value) {
                    case 'Single':
                        $arrayDocument[$strCAC][$key] = $this->getElements($arrayParams['CAC']->$key);
                        break;
                    case 'Multiple':
                        $arrayDocument[$strCAC][$key] = $this->getMultipleElements($arrayParams['CAC']->$key);
                        break;
                    case 'MultipleStandard':
                        $arrayDocument[$strCAC][$key] = $this->getMultipleElementsStandard($arrayParams['CAC']->$key);
                        break;
                }
            }
        }
        if (isset($arrayParams['CAC']->Delivery)) {
            $strEl                                                             = $arrayParams['CAC']->Delivery;
            $strElement                                                        = $strEl->children('cac', true)
                ->DeliveryLocation->children('cac', true)->Address;
            $arrayDocument[$strCAC]['Delivery']['DeliveryLocation']['Address'] = [
                'StreetName' => $strElement->children('cbc', true)->StreetName->__toString(),
                'CityName'   => $strElement->children('cbc', true)->CityName->__toString(),
                'PostalZone' => $strElement->children('cbc', true)->PostalZone->__toString(),
                'Country'    => [
                    'IdentificationCode' => $strElement->children('cac', true)->Country->children('cbc', true)->IdentificationCode->__toString(),
                ],
            ];
            if (isset($strEl->children('cbc', true)->ActualDeliveryDate)) {
                $arrayDocument[$strCAC]['Delivery']['ActualDeliveryDate'] = $strEl
                        ->children('cbc', true)->ActualDeliveryDate->__toString();
            }
        }
        return $arrayDocument;
    }

    private function getHeaderCommonBasicComponents(string $strType, $objCommonBasicComponents): array
    {
        $arrayOutput = [];
        foreach ($this->arraySettings['CustomOrder']['Header_CBC'] as $value) {
            if (isset($objCommonBasicComponents->$value)) {
                $arrayOutput[$value] = $objCommonBasicComponents->$value->__toString();
            }
        }
        return $arrayOutput;
    }

    private function getMultipleElements(array|\SimpleXMLElement $arrayIn): array
    {
        $arrayToReturn = [];
        $intLineNo     = 0;
        foreach ($arrayIn as $child) {
            $intLineNo++;
            $intLineStr                 = ($intLineNo < 10 ? '0' : '') . $intLineNo;
            $arrayToReturn[$intLineStr] = $this->getPaymentMeans($child);
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
