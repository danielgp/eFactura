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

    private function setDocumentTag(array $arrayDocumentData): void
    {
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

    private function setElementsOrdered(array $arrayInput): void
    {
        $this->setElementComment($arrayInput['commentParentKey']);
        $this->objXmlWriter->startElement('cac:' . $arrayInput['tag']);
        $this->setExtraElement($arrayInput, 'Start');
        $arrayCustomOrder = $this->arraySettings['CustomOrder'][$arrayInput['commentParentKey']];
        foreach ($arrayCustomOrder as $value) { // get the values in expected order
            if (array_key_exists($value, $arrayInput['data'])) { // because certain value are optional
                $key = implode('_', [$arrayInput['commentParentKey'], $value]);
                $this->setMultipleComments($arrayInput, $value);
                if ($value === 'TaxSubtotal') {
                    foreach ($arrayInput['data'][$value] as $value2) { // multiple subt-totals
                        $this->setElementsOrdered([
                            'commentParentKey' => $key,
                            'data'             => $value2,
                            'tag'              => $value,
                        ]);
                    }
                } elseif (in_array($value, ['Item', 'Price'])) { // single Item, single Price
                    $this->setElementsOrdered([
                        'commentParentKey' => $key,
                        'data'             => $arrayInput['data'][$value],
                        'tag'              => $value,
                    ]);
                } else {
                    $matches = []; // scan for special values
                    preg_match('/^(EndpointID|.*(Amount|Quantity))$/', $value, $matches, PREG_OFFSET_CAPTURE);
                    if ($matches !== []) {
                        $this->setSingleElementWithAttribute([
                            'data' => $arrayInput['data'][$value],
                            'tag'  => $value,
                        ]);
                    } elseif (is_array($arrayInput['data'][$value])) {
                        $this->objXmlWriter->startElement('cac:' . $value);
                        $arrayCustomOrder2 = $this->arraySettings['CustomOrder'][$key];
                        foreach ($arrayCustomOrder2 as $valueOrd2) {
                            if (array_key_exists($valueOrd2, $arrayInput['data'][$value])) { // 4 optional values
                                if (is_array($arrayInput['data'][$value][$valueOrd2])) {
                                    $this->objXmlWriter->startElement('cac:' . $valueOrd2);
                                    foreach ($arrayInput['data'][$value][$valueOrd2] as $key2 => $value2) {
                                        $this->setSingleElementWithAttribute([
                                            'commentParentKey' => implode('_', [$key, $valueOrd2, $valueOrd2]),
                                            'data'             => $value2,
                                            'tag'              => $key2,
                                        ]);
                                    }
                                    $this->objXmlWriter->endElement();
                                } else {
                                    $this->setSingleElementWithAttribute([
                                        'commentParentKey' => implode('_', [$key, $valueOrd2]),
                                        'data'             => $arrayInput['data'][$value][$valueOrd2],
                                        'tag'              => $valueOrd2,
                                    ]);
                                }
                            }
                        }
                        $this->objXmlWriter->endElement();
                    } else {
                        $this->objXmlWriter->writeElement('cbc:' . $value, $arrayInput['data'][$value]);
                    }
                }
            }
        }
        $this->setExtraElement($arrayInput, 'End');
        $this->objXmlWriter->endElement(); // $key
    }

    private function setHeaderCommonBasicComponents(array $arrayElementWithData): void
    {
        $arrayCustomOrdered = $this->arraySettings['CustomOrder']['Header_CBC'];
        foreach ($arrayCustomOrdered as $value) {
            $this->setElementComment($value);
            $this->objXmlWriter->writeElement('cbc:' . $value, $arrayElementWithData[$value]);
        }
    }

    private function setMultipleComments(array $arrayInput, string $strTag): void
    {
        $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $strTag]));
        if (in_array($strTag, ['CreditNoteQuantity', 'InvoicedQuantity'])) {
            $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $strTag
                . 'UnitOfMeasure']));
        }
    }

    private function setExtraElement(array $arrayInput, string $strType): void
    {
        if (in_array($arrayInput['tag'], ['AccountingCustomerParty', 'AccountingSupplierParty'])) {
            switch ($strType) {
                case 'End':
                    $this->objXmlWriter->endElement();
                    break;
                case 'Start':
                    $this->objXmlWriter->startElement('cac:Party');
                    break;
            }
        }
    }

    private function setSingleElementWithAttribute(array $arrayInput): void
    {
        if (array_key_exists('commentParentKey', $arrayInput)) {
            $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $arrayInput['tag']]));
        }
        if (is_array($arrayInput['data']) && array_key_exists('value', $arrayInput['data'])) {
            $this->objXmlWriter->startElement('cbc:' . $arrayInput['tag']);
            foreach ($arrayInput['data'] as $key => $value) {
                if ($key != 'value') { // if is not value, must be an attribute
                    $this->objXmlWriter->writeAttribute($key, $value);
                }
            }
            $this->objXmlWriter->writeRaw($arrayInput['data']['value']);
            $this->objXmlWriter->endElement();
        } else {
            $this->objXmlWriter->writeElement('cbc:' . $arrayInput['tag'], $arrayInput['data']);
        }
    }

    public function writeElectronicInvoice(string $strFile, array $arrayData, bool $bolComments): void
    {
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
            $this->setElementsOrdered([
                'commentParentKey' => $strCompanyType,
                'data'             => $arrayAggegateComponents[$strCompanyType]['Party'],
                'tag'              => $strCompanyType,
            ]);
        }
        // multiple accounts can be specified within PaymentMeans
        if ($arrayData['DocumentTagName'] === 'Invoice') {
            foreach ($arrayAggegateComponents['PaymentMeans'] as $value) {
                $this->setElementsOrdered([
                    'commentParentKey' => 'PaymentMeans',
                    'data'             => $value,
                    'tag'              => 'PaymentMeans',
                ]);
            }
        }
        foreach (['TaxTotal', 'LegalMonetaryTotal'] as $strTotal) {
            $this->setElementsOrdered([
                'commentParentKey' => $strTotal,
                'data'             => $arrayAggegateComponents[$strTotal],
                'tag'              => $strTotal,
            ]);
        }
        // multiple Lines
        foreach ($arrayData['Lines'] as $value) {
            $this->setElementsOrdered([
                'commentParentKey' => 'Lines',
                'data'             => $value,
                'tag'              => $arrayData['DocumentTagName'] . 'Line',
            ]);
        }
        $this->objXmlWriter->endElement(); // Invoice or CreditNote
        $this->objXmlWriter->flush();
    }
}
