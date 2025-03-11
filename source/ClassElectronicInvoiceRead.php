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
            if (isset($arrayIn['data']->children($this->arrayProcessing['mapping']['cac'], true)->$strElement)) {
                if ($strElement === 'PartyTaxScheme') {
                    $arrayOut[$strElement] = $this->getMultipleElementsByKey($arrayIn['data']
                            ->children($this->arrayProcessing['mapping']['cac'], true)->$strElement);
                } else {
                    $arrayOut[$strElement] = $this->getElements($arrayIn['data']
                            ->children($this->arrayProcessing['mapping']['cac'], true)->$strElement);
                }
            }
            if (isset($arrayIn['data']->children($this->arrayProcessing['mapping']['cbc'], true)->$strElement)) {
                if ($strElement === 'EndpointID') {
                    $arrayOut['EndpointID'] = [
                        'schemeID' => $arrayIn['data']
                            ->children($this->arrayProcessing['mapping']['cbc'], true)->EndpointID
                            ->attributes()->schemeID->__toString(),
                        'value'    => $arrayIn['data']
                            ->children($this->arrayProcessing['mapping']['cbc'], true)->EndpointID
                            ->__toString(),
                    ];
                } else {
                    $arrayOut[$strElement] = $this->getElements($arrayIn['data']
                            ->children($this->arrayProcessing['mapping']['cbc'], true)->$strElement);
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

    public function getDocumentRoot(object $objFile): array
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
        if ($arrayDocument['DocumentTagName'] === 'header') {
            foreach ($objFile->attributes() as $attributeName => $attributeValue) {
                $arrayDocument['header'][$attributeName] = $attributeValue->__toString();
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
                    'data' => $arrayParams['CAC']->$key
                        ->children($this->arrayProcessing['mapping']['cac'], true)->Party,
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

    public function readElectronicXmlHeader(string $strFile): \SimpleXMLElement
    {
        $this->getProcessingDetails();
        $this->getHierarchyTagOrder();
        $flags      = LIBXML_PARSEHUGE | LIBXML_BIGLINES | LIBXML_NOERROR;
        $bolIsLocal = is_file($strFile);
        return new \SimpleXMLElement($strFile, $flags, $bolIsLocal);
    }

    public function readElectronicInvoice(string $strFile): array
    {
        $objFile                 = $this->readElectronicXmlHeader($strFile);
        $arrayDocument           = $this->getDocumentRoot($objFile);
        $this->setArrayProcessing($arrayDocument['DocumentNameSpaces']);
        $strMap                  = $this->arrayProcessing['mapping'];
        $arrayBasics             = $this->getElementsOrdered([
            'data'          => $objFile->children($strMap['cbc'], true),
            'namespace_cbc' => $arrayDocument['DocumentNameSpaces'][$strMap['cbc']],
        ]);
        $arrayAggregates         = $this->getHeader([
            'CAC'  => $objFile->children($strMap['cac'], true),
            'data' => $objFile,
        ]);
        $arrayDocument['Header'] = [
            $this->getBasicOrAggregateKey($arrayDocument['DocumentNameSpaces'], $strMap['cbc']) => $arrayBasics,
            $this->getBasicOrAggregateKey($arrayDocument['DocumentNameSpaces'], $strMap['cac']) => $arrayAggregates,
        ];
        $arrayDocument['Lines']  = $this->getDocumentLines($objFile, $arrayDocument['DocumentTagName']);
        return $arrayDocument;
    }

    private function setArrayProcessing(array $arrayDocumentNameSpaces): void
    {
        $bolMappingsNotSet = [
            'cac' => true,
            'cbc' => true,
        ];
        foreach ($arrayDocumentNameSpaces as $key => $value) {
            if (str_ends_with($value, ':CommonAggregateComponents-2') && $bolMappingsNotSet['cac']) {
                $this->arrayProcessing['mapping']['cac'] = $key;
                $bolMappingsNotSet['cac']                = false;
            }
            if (str_ends_with($value, ':CommonBasicComponents-2') && $bolMappingsNotSet['cbc']) {
                $this->arrayProcessing['mapping']['cbc'] = $key;
                $bolMappingsNotSet['cbc']                = false;
            }
        }
    }
}
