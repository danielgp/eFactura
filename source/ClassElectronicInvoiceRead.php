<?php

/*
 * Copyright (c) 2024, Daniel Popiniuc and its licensors.
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v20.html
 *
 * Contributors:
 *    Daniel Popiniuc
 */

namespace danielgp\efactura;

class ClassElectronicInvoiceRead
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

    private function getBasicOrAggregateKey(array $arrayDocNmSp, string $strBasOrAggr): string
    {
        $arrayPieces = explode(':', $arrayDocNmSp[$strBasOrAggr]);
        return $arrayPieces[count($arrayPieces) - 1];
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

    private function getHeaderComponents(array $arrayParams, string $key, string $value): array|string
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
        $flags                   = LIBXML_PARSEHUGE | LIBXML_BIGLINES | LIBXML_NOERROR;
        $bolIsLocal              = is_file($strFile);
        $objFile                 = new \SimpleXMLElement($strFile, $flags, $bolIsLocal);
        $arrayDocument           = $this->getDocumentRoot($objFile);
        $arrayBasics             = $this->getElementsOrdered([
            'data'          => $objFile->children('cbc', true),
            'namespace_cbc' => $arrayDocument['DocumentNameSpaces']['cbc'],
        ]);
        $arrayAggregates         = $this->getHeader([
            'CAC'  => $objFile->children('cac', true),
            'data' => $objFile,
        ]);
        $arrayDocument['Header'] = [
            $this->getBasicOrAggregateKey($arrayDocument['DocumentNameSpaces'], 'cbc') => $arrayBasics,
            $this->getBasicOrAggregateKey($arrayDocument['DocumentNameSpaces'], 'cac') => $arrayAggregates,
        ];
        $arrayDocument['Lines']  = $this->getDocumentLines($objFile, $arrayDocument['DocumentTagName']);
        return $arrayDocument;
    }
}
