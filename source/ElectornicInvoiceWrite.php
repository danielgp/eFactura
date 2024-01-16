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

    private function loadSettingsAndManageDefaults(array $arrayData, bool $bolComments): array
    {
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
        return $arrayData;
    }

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
                if ($value === 'TaxSubtotal') {
                    foreach ($arrayInput['data'][$value] as $value2) { // multiple subt-totals
                        $this->setElementsOrdered([
                            'commentParentKey' => $key,
                            'data'             => $value2,
                            'tag'              => $value,
                        ]);
                    }
                } elseif (in_array($value, ['AdditionalCharge', 'AllowanceCharge', 'Item', 'Price'])) {
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
                            'commentParentKey' => $arrayInput['commentParentKey'],
                            'data'             => $arrayInput['data'][$value],
                            'tag'              => $value,
                        ]);
                    } elseif (is_array($arrayInput['data'][$value])) {
                        $this->setElementsOrdered([
                            'commentParentKey' => $key,
                            'data'             => $arrayInput['data'][$value],
                            'tag'              => $value,
                        ]);
                    } else {
                        $this->setSingleElementWithAttribute([
                            'commentParentKey' => $arrayInput['commentParentKey'],
                            'data'             => $arrayInput['data'][$value],
                            'tag'              => $value,
                        ]);
                    }
                }
            }
        }
        $this->setExtraElement($arrayInput, 'End');
        $this->objXmlWriter->endElement(); // $key
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

    private function setHeaderCommonBasicComponents(array $arrayElementWithData): void
    {
        $arrayCustomOrdered = $this->arraySettings['CustomOrder']['Header_CBC'];
        foreach ($arrayCustomOrdered as $value) {
            $this->setElementComment($value);
            $this->objXmlWriter->writeElement('cbc:' . $value, $arrayElementWithData[$value]);
        }
    }

    private function setPaymentMeans(array $arrayData, string $strDocumentTagName): void
    {
        // multiple accounts can be specified within PaymentMeans
        if ($strDocumentTagName === 'Invoice') {
            foreach ($arrayData as $value) {
                $this->setElementsOrdered([
                    'commentParentKey' => 'PaymentMeans',
                    'data'             => $value,
                    'tag'              => 'PaymentMeans',
                ]);
            }
        }
    }

    private function setSingleComment(array $arrayInput): void
    {
        if (array_key_exists('commentParentKey', $arrayInput)) {
            $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $arrayInput['tag']]));
            if (str_ends_with($arrayInput['tag'], 'Quantity')) {
                $this->setElementComment(implode('_', [$arrayInput['commentParentKey'], $arrayInput['tag']
                        . 'UnitOfMeasure']));
            }
        }
    }

    private function setSingleElementWithAttribute(array $arrayInput): void
    {
        $this->setSingleComment($arrayInput);
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

    public function writeElectronicInvoice(string $strFile, array $arrayDataIn, bool $bolComments): void
    {
        $this->objXmlWriter = new \XMLWriter();
        $this->objXmlWriter->openURI($strFile);
        $this->objXmlWriter->setIndent(true);
        $this->objXmlWriter->setIndentString(str_repeat(' ', 4));
        $this->objXmlWriter->startDocument('1.0', 'UTF-8');
        $arrayData = $this->loadSettingsAndManageDefaults($arrayDataIn, $bolComments);
        $this->setDocumentTag($arrayData);
        $this->setHeaderCommonBasicComponents($arrayData['Header']['CommonBasicComponents-2']);
        $arrayAggregates = $arrayData['Header']['CommonAggregateComponents-2'];
        foreach (['AccountingSupplierParty', 'AccountingCustomerParty'] as $strCompanyType) {
            $this->setElementsOrdered([
                'commentParentKey' => $strCompanyType,
                'data'             => $arrayAggregates[$strCompanyType]['Party'],
                'tag'              => $strCompanyType,
            ]);
        }
        $this->setPaymentMeans($arrayAggregates['PaymentMeans'], $arrayData['DocumentTagName']);
        foreach (['TaxTotal', 'LegalMonetaryTotal'] as $strTotal) {
            $this->setElementsOrdered([
                'commentParentKey' => $strTotal,
                'data'             => $arrayAggregates[$strTotal],
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
