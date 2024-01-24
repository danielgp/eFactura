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

    private function loadSettingsAndManageDefaults(array $arrayData, bool $bolComments, bool $bolSchemaLctn): array
    {
        // if no DocumentNameSpaces seen take Default ones from local configuration
        $this->getSettingsFromFileIntoMemory($bolComments);
        $arrayDefaults = $this->getDefaultsIntoDataSet($arrayData, $bolSchemaLctn);
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
                $key     = implode('_', [$arrayInput['commentParentKey'], $value]);
                $matches = [];
                preg_match('/^.*(Amount|Quantity)$/', $value, $matches, PREG_OFFSET_CAPTURE);
                if (in_array($value, ['AdditionalItemProperty', 'CommodityClassification', 'StandardItemIdentification', 'TaxSubtotal'])) {
                    $this->setMultipleElementsOrdered([
                        'commentParentKey' => $key,
                        'data'             => $arrayInput['data'][$value],
                        'tag'              => $value,
                    ]);
                } elseif (($matches !== []) || !is_array($arrayInput['data'][$value]) || in_array($arrayInput['commentParentKey'], ['AccountingCustomerParty_PartyIdentification', 'AccountingSupplierParty_PartyIdentification', 'Lines_Item_SellersItemIdentification', 'Lines_Item_StandardItemIdentification', 'Lines_Item_CommodityClassification'])) {
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
            if (array_key_exists($value, $arrayElementWithData)) {
                $this->setElementComment($value);
                $this->objXmlWriter->writeElement('cbc:' . $value, $arrayElementWithData[$value]);
            }
        }
    }

    private function setManageComment(string $strCommentParentKey, array $arrayIn): string
    {
        if (str_starts_with($strCommentParentKey, 'AllowanceCharge')) {
            $arrayCommentPieces  = explode('_', $strCommentParentKey);
            array_splice($arrayCommentPieces, 0, 1, 'AllowanceCharge~ChargeIndicator'
                . ucfirst($arrayIn['ChargeIndicator'])); // carefully manage a child to decide on comment tag
            $strCommentParentKey = implode('_', $arrayCommentPieces);
        }
        return $strCommentParentKey;
    }

    private function setMultipleElementsOrdered(array $arrayData): void
    {
        foreach ($arrayData['data'] as $value) {
            $strCommentParentKey = $this->setManageComment($arrayData['commentParentKey'], $value);
            $this->setElementsOrdered([
                'commentParentKey' => $strCommentParentKey,
                'data'             => $value,
                'tag'              => $arrayData['tag'],
            ]);
        }
    }

    private function setPrepareXml(string $strFile): void
    {
        $this->objXmlWriter = new \XMLWriter();
        $this->objXmlWriter->openURI($strFile);
        $this->objXmlWriter->setIndent(true);
        $this->objXmlWriter->setIndentString(str_repeat(' ', 4));
        $this->objXmlWriter->startDocument('1.0', 'UTF-8');
    }

    private function setProduceMiddleXml(array $arrayData): void
    {
        $arrayAggregates             = $arrayData['Header']['CommonAggregateComponents-2'];
        $arrayOptionalElementsHeader = [
            'InvoicePeriod'               => 'Single',
            'OrderReference'              => 'Single',
            'BillingReference'            => 'Single',
            'DespatchDocumentReference'   => 'Single',
            'ReceiptDocumentReference'    => 'Single',
            'OriginatorDocumentReference' => 'Single',
            'ContractDocumentReference'   => 'Single',
            'ProjectReference'            => 'Single',
            'AdditionalDocumentReference' => 'Multiple',
            'AccountingSupplierParty'     => 'SingleCompany',
            'AccountingCustomerParty'     => 'SingleCompany',
            'PayeeParty'                  => 'Single',
            'TaxRepresentativeParty'      => 'Single',
            'Delivery'                    => 'Single',
            'PaymentMeans'                => 'Multiple',
            'PaymentTerms'                => 'Single',
            'AllowanceCharge'             => 'Multiple',
            'TaxTotal'                    => 'Single',
            'LegalMonetaryTotal'          => 'Single',
        ];
        foreach ($arrayOptionalElementsHeader as $key => $strLogicType) {
            if (array_key_exists($key, $arrayAggregates)) {
                switch ($strLogicType) {
                    case 'Multiple':
                        $this->setMultipleElementsOrdered([
                            'commentParentKey' => $key,
                            'data'             => $arrayAggregates[$key],
                            'tag'              => $key,
                        ]);
                        break;
                    case 'Single':
                        $this->setElementsOrdered([
                            'commentParentKey' => $key,
                            'data'             => $arrayAggregates[$key],
                            'tag'              => $key,
                        ]);
                        break;
                    case 'SingleCompany':
                        $this->setElementsOrdered([
                            'commentParentKey' => $key,
                            'data'             => $arrayAggregates[$key]['Party'],
                            'tag'              => $key,
                        ]);
                        break;
                }
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
            $fmt = new \NumberFormatter('en_US', \NumberFormatter::DECIMAL);
            $fmt->setAttribute(\NumberFormatter::GROUPING_USED, 0);
            $fmt->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 0);
            $fmt->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);
            foreach ($arrayInput['data'] as $key => $value) {
                if ($key !== 'value') { // if is not value, must be an attribute
                    $this->objXmlWriter->writeAttribute($key, $value);
                }
                if ($key === 'currencyID') {
                    $fmt->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2);
                }
            }
            if ($arrayInput['tag'] === 'PriceAmount') {
                $this->objXmlWriter->writeRaw($arrayInput['data']['value']);
            } else {
                $this->objXmlWriter->writeRaw($fmt->format($arrayInput['data']['value']));
            }
            $this->objXmlWriter->endElement();
        } else {
            $this->objXmlWriter->writeElement('cbc:' . $arrayInput['tag'], $arrayInput['data']);
        }
    }

    public function writeElectronicInvoice(string $strFile, array $inData, bool $bolCmnts, bool $bolScLc = false): void
    {
        $arrayData = $this->loadSettingsAndManageDefaults($inData, $bolCmnts, $bolScLc);
        $this->setPrepareXml($strFile);
        $this->setDocumentTag($arrayData);
        $this->setHeaderCommonBasicComponents($arrayData['Header']['CommonBasicComponents-2']);
        $this->setProduceMiddleXml($arrayData);
        // multiple Lines
        $this->setMultipleElementsOrdered([
            'commentParentKey' => 'Lines',
            'data'             => $arrayData['Lines'],
            'tag'              => $arrayData['DocumentTagName'] . 'Line',
        ]);
        $this->objXmlWriter->endElement(); // Invoice or CreditNote
        $this->objXmlWriter->flush();
    }
}
