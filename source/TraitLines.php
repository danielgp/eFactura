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

trait TraitLines
{

    use TraitBasic,
        TraitTax;

    private function getDocumentLines($objFile, string $strTag): array
    {
        $arrayLines = [];
        $intLineNo  = 0;
        foreach ($objFile->children('cac', true) as $strNodeName => $child) {
            if ($strNodeName === ($strTag . 'Line')) {
                $intLineNo++;
                $intLineStr              = ($intLineNo < 10 ? '0' : '') . $intLineNo;
                $arrayLines[$intLineStr] = $this->getLine($strTag, $child);
            }
        }
        return $arrayLines;
    }

    private function getLine(string $strType, $child): array
    {
        $arrayOutput = [
            'ID'                  => $child->children('cbc', true)->ID->__toString(),
            'LineExtensionAmount' => $this->getTagWithCurrencyParameter($child->children('cbc', true)
                ->LineExtensionAmount),
        ];
        // optional components =========================================================================================
        if (isset($child->children('cac', true)->OrderLineReference)) {
            $arrayOutput['OrderLineReference']['LineID'] = $child->children('cac', true)->OrderLineReference
                    ->children('cbc', true)->LineID->__toString();
        }
        if (isset($child->children('cac', true)->DocumentReference)) {
            $arrayOutput['DocumentReference']['ID'] = $child->children('cac', true)->DocumentReference
                    ->children('cbc', true)->ID->__toString();
        }
        foreach (['AccountingCost', 'InvoicePeriod', 'Note'] as $strElement) {
            if (count($child->children('cbc', true)->$strElement) !== 0) {
                $arrayOutput[$strElement] = $child->children('cbc', true)->$strElement->__toString();
            }
            if (count($child->children('cac', true)->$strElement) !== 0) {
                $arrayOutput[$strElement] = $this->getElements($child->children('cac', true)->$strElement);
            }
        }
        switch ($strType) {
            case 'CreditNote':
                $arrayOutput['CreditedQuantity'] = $this->getTagWithUnitCodeParameter($child->children('cbc', true)
                    ->CreditedQuantity);
                break;
            case 'Invoice':
                $arrayOutput['InvoicedQuantity'] = $this->getTagWithUnitCodeParameter($child->children('cbc', true)
                    ->InvoicedQuantity);
                break;
        }
        if (isset($child->children('cac', true)->AllowanceCharge)) {
            $arrayOutput['AllowanceCharge'] = $this->getLineItemAllowanceCharge($child
                    ->children('cac', true)->AllowanceCharge);
        }
        $arrayOutput['Item']  = $this->getLineItem($child->children('cac', true)->Item);
        $arrayOutput['Price'] = $this->getElements($child->children('cac', true)->Price);
        return $arrayOutput;
    }

    private function getLineItem($child3): array
    {
        $arrayOutput = [];
        foreach ($this->arraySettings['CustomOrder']['Lines_Item'] as $value) {
            switch ($value) {
                case 'AdditionalItemProperty':
                    $intLineNo = 0;
                    foreach ($child3->children('cac', true)->$value as $value2) {
                        $intLineNo++;
                        $intLineStr                       = ($intLineNo < 10 ? '0' : '') . $intLineNo;
                        $arrayOutput[$value][$intLineStr] = [
                            'Name'  => $value2->children('cbc', true)->Name->__toString(),
                            'Value' => $value2->children('cbc', true)->Value->__toString(),
                        ];
                    }
                    break;
                case 'ClassifiedTaxCategory':
                    $arrayOutput[$value] = $this->getTaxCategory($child3->children('cac', true)->$value);
                    break;
                case 'CommodityClassification':
                // intentionally left open
                case 'StandardItemIdentification':
                    $intLineNo           = 0;
                    foreach ($child3->children('cac', true)->$value as $value2) {
                        $intLineNo++;
                        $intLineStr                       = ($intLineNo < 10 ? '0' : '') . $intLineNo;
                        $arrayOutput[$value][$intLineStr] = $this->getElements($value2);
                    }
                    break;
                case 'OriginCountry':
                // intentionally left open
                case 'SellersItemIdentification':
                    if (count($child3->children('cac', true)->$value) !== 0) {
                        $arrayOutput[$value] = $this->getElements($child3->children('cac', true)->$value);
                    }
                    break;
                default:
                    if (count($child3->children('cbc', true)->$value) !== 0) {
                        $arrayOutput[$value] = $child3->children('cbc', true)->$value->__toString();
                    }
                    break;
            }
        }
        return $arrayOutput;
    }

    private function getLineItemAllowanceCharge($child3): array
    {
        $arrayOutput = [];
        $intLineNo   = 0;
        foreach ($child3 as $child4) {
            $intLineNo++;
            $intLineStr               = ($intLineNo < 10 ? '0' : '') . $intLineNo;
            $arrayOutput[$intLineStr] = [
                'Amount'                    => $this->getTagWithCurrencyParameter($child4
                        ->children('cbc', true)->Amount),
                'AllowanceChargeReasonCode' => $child4->children('cbc', true)->AllowanceChargeReasonCode->__toString(),
                'ChargeIndicator'           => $child4->children('cbc', true)->ChargeIndicator->__toString(),
            ];
            if (isset($child4->children('cbc', true)->AllowanceChargeReason)) {
                $arrayOutput[$intLineStr]['AllowanceChargeReason'] = $child4
                        ->children('cbc', true)->AllowanceChargeReason->__toString();
            }
            if (isset($child4->children('cbc', true)->BaseAmount)) {
                $arrayOutput[$intLineStr]['BaseAmount'] = $this->getTagWithCurrencyParameter($child4
                        ->children('cbc', true)->BaseAmount);
            }
            if (isset($child4->children('cbc', true)->MultiplierFactorNumeric)) {
                $arrayOutput[$intLineStr]['MultiplierFactorNumeric'] = $child4
                        ->children('cbc', true)->MultiplierFactorNumeric->__toString();
            }
        }
        return $arrayOutput;
    }
}
