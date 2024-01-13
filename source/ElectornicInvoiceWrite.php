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

class ElectornicInvoiceWrite
{

    use TraitVersions;

    protected $objXmlWriter;

    private function setCompanyElementsOrdered(array $arrayInput): void {
        $this->setElementComment($arrayInput['commentParentKey']);
        $this->objXmlWriter->startElement('cac:' . $arrayInput['tag']);
        $arrayCustomOrder = $this->arraySettings['CustomOrder']['Header']['CAC'][$arrayInput['commentParentKey']];
        foreach ($arrayCustomOrder as $value) {
            if (array_key_exists($value, $arrayInput['data'])) {
                $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $value]));
                if (is_array($arrayInput['data'][$value])) {
                    $this->objXmlWriter->startElement('cac:' . $value);
                    foreach ($arrayInput['data'][$value] as $key2 => $value2) {
                        $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $value, $key2]));
                        $this->objXmlWriter->writeElement('cbc:' . $key2, $value2);
                    }
                    $this->objXmlWriter->endElement();
                } else {
                    $this->objXmlWriter->writeElement('cbc:' . $value, $arrayInput['data'][$value]);
                }
            }
        }
        $this->objXmlWriter->endElement(); // $key
    }

    private function setDocumentTag(array $arrayDocumentData): void {
        $this->objXmlWriter->startElement($arrayDocumentData['DocumentTagName']);
        foreach ($arrayDocumentData['DocumentNameSpaces'] as $key => $value) {
            if ($key === '') {
                $strValue = sprintf($value, $arrayDocumentData['DocumentTagName']);
                $this->objXmlWriter->writeAttributeNS(NULL, 'xmlns', NULL, $strValue);
            } else {
                $this->objXmlWriter->writeAttributeNS('xmlns', $key, NULL, $value);
            }
        }
        if (array_key_exists('SchemaLocation', $arrayDocumentData)) {
            $this->objXmlWriter->writeAttribute('xsi:schemaLocation', $arrayDocumentData['SchemaLocation']);
        }
    }

    private function setHeaderCommonAggregateComponentsCompanies(array $arrayParameters): void {
        $key              = $arrayParameters['tag'];
        $this->setElementComment($key);
        $this->objXmlWriter->startElement('cac:' . $key);
        $this->objXmlWriter->startElement('cac:Party');
        $arrayCustomOrder = $this->arraySettings['CustomOrder']['Header']['CAC'][$key];
        foreach ($arrayCustomOrder as $value) {
            if (array_key_exists($value, $arrayParameters['data'])) {
                $this->setCompanyElementsOrdered([
                    'commentParentKey' => implode('_', [$key, $value]),
                    'data'             => $arrayParameters['data'][$value],
                    'tag'              => $value,
                ]);
            }
        }
        $this->objXmlWriter->endElement(); // Party
        $this->objXmlWriter->endElement(); // $key
    }

    private function setElementWithAttribute(array $arrayParameters): void {
        $this->setElementComment(implode('_', [$arrayParameters['commentParentKey'], $arrayParameters['tag']]));
        $this->objXmlWriter->startElement('cbc:' . $arrayParameters['tag']);
        $this->objXmlWriter->writeAttribute('currencyID', $arrayParameters['data']['currencyID']);
        $this->objXmlWriter->writeRaw($arrayParameters['data']['value']);
        $this->objXmlWriter->endElement(); // TaxAmount
    }

    private function setHeaderCommonAggregateComponentsTaxTotal(array $arrayParameters): void {
        $key = $arrayParameters['tag'];
        $this->setElementComment($key);
        $this->objXmlWriter->startElement('cac:' . $key);
        $this->setElementWithAttribute([
            'commentParentKey' => $key,
            'data'             => $arrayParameters['data']['TaxAmount'],
            'tag'              => 'TaxAmount',
        ]);
        foreach ($arrayParameters['data']['TaxSubtotal'] as $value) {
            $this->objXmlWriter->startElement('cac:TaxSubtotal');
            $this->setElementWithAttribute([
                'commentParentKey' => implode('_', [$key, 'TaxSubtotal']),
                'data'             => $value['TaxAmount'],
                'tag'              => 'TaxableAmount',
            ]);
            $this->setElementWithAttribute([
                'commentParentKey' => implode('_', [$key, 'TaxSubtotal']),
                'data'             => $value['TaxAmount'],
                'tag'              => 'TaxAmount',
            ]);
            $this->setTaxCategory([
                'commentParentKey' => implode('_', [$key, 'TaxSubtotal', 'TaxCategory']),
                'data'             => $value['TaxCategory'],
                'tag'              => 'TaxCategory',
            ]);
            $this->objXmlWriter->endElement(); // TaxSubtotal
        }
        $this->objXmlWriter->endElement(); // $key
    }

    private function setHeaderCommonBasicComponents(array $arrayElementWithData): void {
        $arrayCustomOrdered = $this->arraySettings['CustomOrder']['Header']['CBC'];
        foreach ($arrayCustomOrdered as $value) {
            $this->setElementComment($value);
            $this->objXmlWriter->writeElement('cbc:' . $value, $arrayElementWithData[$value]);
        }
    }

    private function setTaxCategory(array $arrayParameters): void {
        $key = $arrayParameters['tag'];
        $this->objXmlWriter->startElement('cac:' . $key);
        $this->setElementComment(implode('_', [$arrayParameters['commentParentKey'], 'ID']));
        $this->objXmlWriter->writeElement('cbc:ID', $arrayParameters['data']['ID']);
        $this->setElementComment(implode('_', [$arrayParameters['commentParentKey'], 'Percent']));
        $this->objXmlWriter->writeElement('cbc:Percent', $arrayParameters['data']['Percent']);
        $this->objXmlWriter->startElement('cac:TaxScheme');
        $this->objXmlWriter->writeElement('cbc:ID', $arrayParameters['data']['TaxScheme']['ID']);
        $this->objXmlWriter->endElement(); // $key
        $this->objXmlWriter->endElement(); // $key
    }

    public function writeElectronicInvoice(string $strFile, array $arrayData, bool $bolComments): void {
        $this->objXmlWriter = new \XMLWriter();
        $this->objXmlWriter->openURI($strFile);
        $this->objXmlWriter->setIndent(true);
        $this->objXmlWriter->setIndentString(str_repeat(' ', 4));
        $this->objXmlWriter->startDocument('1.0', 'UTF-8');
        // if no DocumentNameSpaces seen take Default ones from local configuration
        $this->getSettingsFromFileIntoMemory($bolComments);
        $arrayDefaults      = $this->getDefaultsIntoDataSet($arrayData);
        if ($arrayDefaults !== []) {
            $arrayData = array_merge($arrayData, $arrayDefaults['Root']);
            if (!array_key_exists('CustomizationID', $arrayData['Header']['CommonBasicComponents-2'])) {
                $arrayData['Header']['CommonBasicComponents-2']['CustomizationID'] = $arrayDefaults['CIUS-RO'];
                $arrayData['Header']['CommonBasicComponents-2']['UBLVersionID']    = $arrayDefaults['UBL'];
            }
        }
        $this->setDocumentTag($arrayData);
        $this->setHeaderCommonBasicComponents($arrayData['Header']['CommonBasicComponents-2'], $bolComments);
        $arrayAggegateComponents = $arrayData['Header']['CommonAggregateComponents-2'];
        foreach (['AccountingSupplierParty', 'AccountingCustomerParty'] as $strCompanyType) {
            $this->setHeaderCommonAggregateComponentsCompanies([
                'data'   => $arrayAggegateComponents[$strCompanyType]['Party'],
                'tag'    => $strCompanyType,
                'subTag' => 'Party',
            ]);
        }
        // multiple accounts can be specified within PaymentMeans
        if ($arrayData['DocumentTagName'] === 'Invoice') {
            foreach ($arrayAggegateComponents['PaymentMeans'] as $value) {
                $this->setHeaderCommonAggregateComponentsOrdered([
                    'data' => $value,
                    'tag'  => 'PaymentMeans',
                ]);
            }
        }
        $this->setHeaderCommonAggregateComponentsTaxTotal([
            'data' => $arrayAggegateComponents['TaxTotal'],
            'tag'  => 'TaxTotal',
        ]);
        $this->setHeaderCommonAggregateComponentsOthers([
            'data' => $arrayAggegateComponents['LegalMonetaryTotal'],
            'tag'  => 'LegalMonetaryTotal',
        ]);
        // multiple Lines
        foreach ($arrayData['Lines'] as $value) {
            $this->setHeaderCommonAggregateComponentsOthers([
                'data'           => $value,
                'tagForComments' => 'Lines',
                'tag'            => $arrayData['DocumentTagName'] . 'Line',
            ]);
        }
        $this->objXmlWriter->endElement(); // Invoice or CreditNote
        $this->objXmlWriter->flush();
    }

    private function setHeaderCommonAggregateComponentsOrdered(array $arrayParameters): void {
        $key              = $arrayParameters['tag'];
        $this->setElementComment($key);
        $this->objXmlWriter->startElement('cac:' . $key);
        $arrayCustomOrder = $this->arraySettings['CustomOrder']['Header']['CAC'][$key];
        foreach ($arrayCustomOrder as $value) {
            if (array_key_exists($value, $arrayParameters['data'])) {
                $this->setElementComment(implode('_', [$key, $value]));
                if (is_array($arrayParameters['data'][$value])) {
                    $this->objXmlWriter->startElement('cac:' . $value);
                    foreach ($arrayParameters['data'][$value] as $key2 => $value2) {
                        $this->setElementComment(implode('_', [$key, $value, $key2]));
                        $this->objXmlWriter->writeElement('cbc:' . $key2, $value2);
                    }
                    $this->objXmlWriter->endElement(); // $value
                } else {
                    $this->objXmlWriter->writeElement('cbc:' . $value, $arrayParameters['data'][$value]);
                }
            }
        }
        $this->objXmlWriter->endElement(); // $key
    }

    private function setHeaderCommonAggregateComponentsOthers(array $arrayParameters): void {
        if (array_key_exists('tagForComments', $arrayParameters)) {
            $key = $arrayParameters['tagForComments'];
        } else {
            $key = $arrayParameters['tag'];
        }
        $this->setElementComment($key);
        $this->objXmlWriter->startElement('cac:' . $arrayParameters['tag']);
        foreach ($arrayParameters['data'] as $key2 => $value2) {
            $this->setElementComment(implode('_', [$key, $key2]));
            if (is_array($value2)) {
                if (substr($key2, -6) === 'Amount') {
                    $this->objXmlWriter->startElement('cbc:' . $key2);
                    $this->objXmlWriter->writeAttribute('currencyID', $value2['currencyID']);
                    $this->objXmlWriter->writeRaw($value2['value']);
                } elseif (substr($key2, -8) === 'Quantity') {
                    $this->objXmlWriter->startElement('cbc:' . $key2);
                    $this->objXmlWriter->writeAttribute('unitCode', $value2['unitCode']);
                    $this->objXmlWriter->writeRaw($value2['value']);
                } else {
                    $this->objXmlWriter->startElement('cac:' . $key2);
                    foreach ($value2 as $key3 => $value3) {
                        $this->setElementComment(implode('_', [$key, $key2, $key3]));
                        if (substr($key3, -6) === 'Amount') {
                            $this->objXmlWriter->startElement('cbc:' . $key3);
                            $this->objXmlWriter->writeAttribute('currencyID', $value3['currencyID']);
                            $this->objXmlWriter->writeRaw($value3['value']);
                            $this->objXmlWriter->endElement(); // $key3
                        } elseif (substr($key3, -8) === 'Quantity') {
                            $this->objXmlWriter->startElement('cbc:' . $key3);
                            $this->objXmlWriter->writeAttribute('unitCode', $value3['unitCode']);
                            $this->objXmlWriter->writeRaw($value3['value']);
                            $this->objXmlWriter->endElement(); // $key3
                        } elseif (is_array($value3)) {
                            $this->objXmlWriter->startElement('cac:' . $key3);
                            foreach ($value3 as $key4 => $value4) {
                                $this->setElementComment(implode('_', [$key, $key2, $key3, $key4]));
                                if (is_array($value4)) {
                                    $this->objXmlWriter->startElement('cac:' . $key4);
                                    foreach ($value4 as $key5 => $value5) {
                                        $this->setElementComment(implode('_', [$key, $key2, $key3, $key4, $key5]));
                                        $this->objXmlWriter->writeElement('cbc:' . $key5, $value5);
                                    }
                                    $this->objXmlWriter->endElement(); // $key4
                                } else {
                                    $this->objXmlWriter->writeElement('cbc:' . $key4, $value4);
                                }
                            }
                            $this->objXmlWriter->endElement(); // $key3
                        } else {
                            $this->objXmlWriter->writeElement('cbc:' . $key3, $value3);
                        }
                    }
                }
                $this->objXmlWriter->endElement(); // $key2
            } else {
                $this->objXmlWriter->writeElement('cbc:' . $key2, $value2);
            }
        }
        $this->objXmlWriter->endElement(); // $key
    }
}
