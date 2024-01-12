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

    use traitVersions;

    protected $objXmlWriter;

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

    private function setElementComment(string $strKey, string $strSection, bool $includeComments): void {
        if ($includeComments && array_key_exists($strKey, $this->arraySettings['Comments'][$strSection])) {
            switch ($strSection) {
                case 'CAC':
                    $elementComment = $this->arraySettings['Comments'][$strSection][$strKey];
                    if (is_array($elementComment)) {
                        foreach ($elementComment as $value) {
                            $this->objXmlWriter->writeComment($value);
                        }
                    } else {
                        $this->objXmlWriter->writeComment($elementComment);
                    }
                    break;
                case 'CBC':
                    $this->objXmlWriter->writeComment($this->arraySettings['Comments'][$strSection][$strKey]);
                    break;
            }
        }
    }

    private function setCompanyElementsOrdered(array $arrayParameters): void {
        $this->setElementComment($arrayParameters['commentParentKey'], 'CAC', $arrayParameters['comments']);
        $this->objXmlWriter->startElement('cac:' . $arrayParameters['tag']);
        $arrayCustomOrder = $this->arraySettings['CustomOrder']['Header']['CAC'][$arrayParameters['commentParentKey']];
        foreach ($arrayCustomOrder as $value) {
            if (array_key_exists($value, $arrayParameters['data'])) {
                $this->setElementComment(implode('_', [
                    $arrayParameters['commentParentKey'], $value]), 'CAC', $arrayParameters['comments']);
                if (is_array($arrayParameters['data'][$value])) {
                    $this->objXmlWriter->startElement('cac:' . $value);
                    foreach ($arrayParameters['data'][$value] as $key2 => $value2) {
                        $this->setElementComment(implode('_', [
                            $arrayParameters['commentParentKey'], $value, $key2]), 'CAC', $arrayParameters['comments']);
                        $this->objXmlWriter->writeElement('cbc:' . $key2, $value2);
                    }
                    $this->objXmlWriter->endElement();
                } else {
                    $this->objXmlWriter->writeElement('cbc:' . $value, $arrayParameters['data'][$value]);
                }
            }
        }
        $this->objXmlWriter->endElement(); // $key
    }

    private function setHeaderCommonAggregateComponentsCompanies(array $arrayParameters): void {
        $key              = $arrayParameters['tag'];
        $this->setElementComment($key, 'CAC', $arrayParameters['comments']);
        $this->objXmlWriter->startElement('cac:' . $key);
        $this->objXmlWriter->startElement('cac:Party');
        $arrayCustomOrder = $this->arraySettings['CustomOrder']['Header']['CAC'][$key];
        foreach ($arrayCustomOrder as $value) {
            if (array_key_exists($value, $arrayParameters['data'])) {
                $this->setCompanyElementsOrdered([
                    'comments'         => $arrayParameters['comments'],
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
        $this->setElementComment($key, 'CAC', $arrayParameters['comments']);
        $this->objXmlWriter->startElement('cac:' . $key);
        $this->setElementComment(implode('_', [$key, 'TaxAmount']), 'CAC', $arrayParameters['comments']);
        $this->objXmlWriter->startElement('cbc:TaxAmount');
        $this->objXmlWriter->writeAttribute('currencyID', $arrayParameters['data']['TaxAmount']['currencyID']);
        $this->objXmlWriter->writeRaw($arrayParameters['data']['TaxAmount']['value']);
        $this->objXmlWriter->endElement(); // TaxAmount
        foreach ($arrayParameters['data']['TaxSubtotal'] as $value) {
            $this->objXmlWriter->startElement('cac:TaxSubtotal');
            $this->setElementComment(implode('_', [$key, 'TaxSubtotal', 'TaxableAmount']), 'CAC', $arrayParameters['comments']);
            $this->objXmlWriter->startElement('cbc:TaxableAmount');
            $this->objXmlWriter->writeAttribute('currencyID', $value['TaxableAmount']['currencyID']);
            $this->objXmlWriter->writeRaw($value['TaxableAmount']['value']);
            $this->objXmlWriter->endElement(); // TaxableAmount
            $this->setElementComment(implode('_', [$key, 'TaxSubtotal', 'TaxAmount']), 'CAC', $arrayParameters['comments']);
            $this->objXmlWriter->startElement('cbc:TaxAmount');
            $this->objXmlWriter->writeAttribute('currencyID', $value['TaxAmount']['currencyID']);
            $this->objXmlWriter->writeRaw($value['TaxAmount']['value']);
            $this->objXmlWriter->endElement(); // TaxAmount
            $this->setTaxCategory([
                'comments'         => $arrayParameters['comments'],
                'commentParentKey' => implode('_', [$key, 'TaxSubtotal', 'TaxCategory']),
                'data'             => $value['TaxCategory'],
                'tag'              => 'TaxCategory',
            ]);
            $this->objXmlWriter->endElement(); // TaxSubtotal
        }
        $this->objXmlWriter->endElement(); // $key
    }

    private function setHeaderCommonBasicComponents(array $arrayElementWithData, bool $includeComments): void {
        $arrayCustomOrdered = $this->arraySettings['CustomOrder']['Header']['CBC'];
        foreach ($arrayCustomOrdered as $value) {
            $this->setElementComment($value, 'CBC', $includeComments);
            $this->objXmlWriter->writeElement('cbc:' . $value, $arrayElementWithData[$value]);
        }
    }

    private function setTaxCategory(array $arrayParameters): void {
        $key = $arrayParameters['tag'];
        $this->objXmlWriter->startElement('cac:' . $key);
        $this->setElementComment(implode('_', [
            $arrayParameters['commentParentKey'], 'ID']), 'CAC', $arrayParameters['comments']);
        $this->objXmlWriter->writeElement('cbc:ID', $arrayParameters['data']['ID']);
        $this->setElementComment(implode('_', [
            $arrayParameters['commentParentKey'], 'Percent']), 'CAC', $arrayParameters['comments']);
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
        $this->getSettingsFromFileIntoMemory();
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
        $this->setHeaderCommonAggregateComponentsCompanies([
            'data'     => $arrayAggegateComponents['AccountingSupplierParty']['Party'],
            'tag'      => 'AccountingSupplierParty',
            'subTag'   => 'Party',
            'comments' => $bolComments,
        ]);
        $this->setHeaderCommonAggregateComponentsCompanies([
            'data'     => $arrayAggegateComponents['AccountingCustomerParty']['Party'],
            'tag'      => 'AccountingCustomerParty',
            'subTag'   => 'Party',
            'comments' => $bolComments,
        ]);
        // multiple accounts can be specified within PaymentMeans
        foreach ($arrayAggegateComponents['PaymentMeans'] as $value) {
            $this->setHeaderCommonAggregateComponentsOrdered([
                'data'     => $value,
                'tag'      => 'PaymentMeans',
                'comments' => $bolComments,
            ]);
        }
        $this->setHeaderCommonAggregateComponentsTaxTotal([
            'data'     => $arrayAggegateComponents['TaxTotal'],
            'tag'      => 'TaxTotal',
            'comments' => $bolComments,
        ]);
        $this->setHeaderCommonAggregateComponentsOthers([
            'data'     => $arrayAggegateComponents['LegalMonetaryTotal'],
            'tag'      => 'LegalMonetaryTotal',
            'comments' => $bolComments,
        ]);
        // multiple Lines
        /* foreach ($arrayData['Lines'] as $key => $value) {
          $this->setHeaderCommonAggregateComponentsOthers([
          'data'           => $value,
          'tagForComments' => 'Lines',
          'tag'            => $arrayData['DocumentTagName'] . 'Line',
          'comments'       => $bolComments,
          ]);
          } */
        $this->objXmlWriter->endElement(); // Invoice or CreditNote
        $this->objXmlWriter->flush();
    }

    private function setHeaderCommonAggregateComponentsOrdered(array $arrayParameters): void {
        $key              = $arrayParameters['tag'];
        $this->setElementComment($key, 'CAC', $arrayParameters['comments']);
        $this->objXmlWriter->startElement('cac:' . $key);
        $arrayCustomOrder = $this->arraySettings['CustomOrder']['Header']['CAC'][$key];
        foreach ($arrayCustomOrder as $value) {
            if (array_key_exists($value, $arrayParameters['data'])) {
                $this->setElementComment(implode('_', [$key, $value]), 'CAC', $arrayParameters['comments']);
                if (is_array($arrayParameters['data'][$value])) {
                    $this->objXmlWriter->startElement('cac:' . $value);
                    foreach ($arrayParameters['data'][$value] as $key2 => $value2) {
                        $this->setElementComment(implode('_', [$key, $value, $key2]), 'CAC', $arrayParameters['comments']);
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
        $this->setElementComment($key, 'CAC', $arrayParameters['comments']);
        $this->objXmlWriter->startElement('cac:' . $arrayParameters['tag']);
        foreach ($arrayParameters['data'] as $key2 => $value2) {
            $this->setElementComment(implode('_', [$key, $key2]), 'CAC', $arrayParameters['comments']);
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
                        $this->setElementComment(implode('_', [
                            $key, $key2, $key3]), 'CAC', $arrayParameters['comments']);
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
                                $this->setElementComment(implode('_', [
                                    $key, $key2, $key3, $key4]), 'CAC', $arrayParameters['comments']);
                                if (is_array($value4)) {
                                    $this->objXmlWriter->startElement('cac:' . $key4);
                                    foreach ($value4 as $key5 => $value5) {
                                        $this->setElementComment(implode('_', [
                                            $key, $key2, $key3, $key4, $key5]), 'CAC', $arrayParameters['comments']);
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
