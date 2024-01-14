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

    protected \XMLWriter $objXmlWriter;

    private function setCompanyElementsOrdered(array $arrayInput): void {
        $this->setElementComment($arrayInput['commentParentKey']);
        $this->objXmlWriter->startElement('cac:' . $arrayInput['tag']);
        $arrayCustomOrder = $this->arraySettings['CustomOrder'][$arrayInput['commentParentKey']];
        foreach ($arrayCustomOrder as $value) {
            if (array_key_exists($value, $arrayInput['data'])) {
                $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $value]));
                if (in_array($value, ['CreditNoteQuantity', 'InvoicedQuantity'])) {
                    $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $value
                        . 'UnitOfMeasure']));
                }
                if (str_ends_with($value, 'Amount')) {
                    $this->setElementWithAttribute([
                        'attrib' => 'currencyID',
                        'data'   => $arrayInput['data'][$value],
                        'tag'    => '',
                    ]);
                } elseif (str_ends_with($value,  'Quantity')) {
                    $this->setElementWithAttribute([
                        'attrib' => 'unitCode',
                        'data'   => $arrayInput['data'][$value],
                        'tag'    => '',
                    ]);
                } elseif (is_array($arrayInput['data'][$value])) {
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

    private function setElementWithAttribute(array $arrayInput): void {
        if (array_key_exists('commentParentKey', $arrayInput)) {
            $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $arrayInput['tag']]));
        }
        $this->objXmlWriter->startElement('cbc:' . $arrayInput['tag']);
        $this->objXmlWriter->writeAttribute($arrayInput['attrib'], $arrayInput['data'][$arrayInput['attrib']]);
        $this->objXmlWriter->writeRaw($arrayInput['data']['value']);
        $this->objXmlWriter->endElement();
    }

    private function setHeaderCommonAggregateComponentsCompanies(array $arrayParameters): void {
        $key              = $arrayParameters['tag'];
        $this->setElementComment($key);
        $this->objXmlWriter->startElement('cac:' . $key);
        $this->objXmlWriter->startElement('cac:Party');
        $arrayCustomOrder = $this->arraySettings['CustomOrder'][$key];
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

    private function setHeaderCommonAggregateComponentsTaxTotal(array $arrayParameters): void {
        $key = $arrayParameters['tag'];
        $this->setElementComment($key);
        $this->objXmlWriter->startElement('cac:' . $key);
        $this->setElementWithAttribute([
            'attrib'           => 'currencyID',
            'commentParentKey' => $key,
            'data'             => $arrayParameters['data']['TaxAmount'],
            'tag'              => 'TaxAmount',
        ]);
        foreach ($arrayParameters['data']['TaxSubtotal'] as $value) {
            $this->objXmlWriter->startElement('cac:TaxSubtotal');
            $this->setElementWithAttribute([
                'attrib'           => 'currencyID',
                'commentParentKey' => implode('_', [$key, 'TaxSubtotal']),
                'data'             => $value['TaxableAmount'],
                'tag'              => 'TaxableAmount',
            ]);
            $this->setElementWithAttribute([
                'attrib'           => 'currencyID',
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
        $arrayCustomOrdered = $this->arraySettings['CustomOrder']['Header_CBC'];
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
        $this->setHeaderCommonBasicComponents($arrayData['Header']['CommonBasicComponents-2']);
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
                $this->setCompanyElementsOrdered([
                    'commentParentKey' => 'PaymentMeans',
                    'data'             => $value,
                    'tag'              => 'PaymentMeans',
                ]);
            }
        }
        $this->setHeaderCommonAggregateComponentsTaxTotal([
            'data' => $arrayAggegateComponents['TaxTotal'],
            'tag'  => 'TaxTotal',
        ]);
        $this->setCompanyElementsOrdered([
            'commentParentKey' => 'LegalMonetaryTotal',
            'data'             => $arrayAggegateComponents['LegalMonetaryTotal'],
            'tag'              => 'LegalMonetaryTotal',
        ]);
        // multiple Lines
        foreach ($arrayData['Lines'] as $value) {
            $this->setCompanyElementsOrdered([
                'commentParentKey' => 'Lines',
                'data'             => $value,
                'tag'              => $arrayData['DocumentTagName'] . 'Line',
            ]);
        }
        $this->objXmlWriter->endElement(); // Invoice or CreditNote
        $this->objXmlWriter->flush();
    }
}
