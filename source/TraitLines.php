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

    use TraitTax;

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
        if (isset($child->children('cbc', true)->Note)) {
            $arrayOutput['Note'] = $child->children('cbc', true)->Note->__toString();
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
        $arrayOutput['Price'] = $this->getLinePrice($child->children('cac', true)->Price);
        return $arrayOutput;
    }

    private function getLineItem($child3): array
    {
        $arrayOutput = [
            'Name'                  => $child3->children('cbc', true)->Name->__toString(),
            'ClassifiedTaxCategory' => $this->getTaxCategory($child3->children('cac', true)->ClassifiedTaxCategory),
        ];
        // optional components =========================================================================================
        if (isset($child3->children('cbc', true)->Description)) {
            $arrayOutput['Description'] = $child3->children('cbc', true)->Description->__toString();
        }
        if (isset($child3->children('cac', true)->AdditionalItemProperty)) {
            $intLineNo = 0;
            foreach ($child3->children('cac', true)->AdditionalItemProperty as $value) {
                $intLineNo++;
                $intLineStr                                         = ($intLineNo < 10 ? '0' : '') . $intLineNo;
                $arrayOutput['AdditionalItemProperty'][$intLineStr] = [
                    'Name'  => $value->children('cbc', true)->Name->__toString(),
                    'Value' => $value->children('cbc', true)->Value->__toString(),
                ];
            }
        }
        $arrayAggregate = $this->getLineItemAggregate($child3);
        return array_merge($arrayOutput, $arrayAggregate);
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
                $arrayOutput[$intLineStr]['AllowanceChargeReason'] = $this->getTagWithCurrencyParameter($child4
                        ->children('cbc', true)->AllowanceChargeReason);
            }
            if (isset($child4->children('cbc', true)->BaseAmount)) {
                $arrayOutput[$intLineStr]['BaseAmount'] = $this->getTagWithCurrencyParameter($child4
                        ->children('cbc', true)->BaseAmount);
            }
        }
        return $arrayOutput;
    }

    private function getLineItemAggregate($child3): array
    {
        $arrayOutput = [];
        foreach ($child3->children('cac', true) as $strName => $value) {
            switch ($strName) {
                case 'CommodityClassification':
                    $child4                                          = $value->children('cbc', true)
                        ->ItemClassificationCode;
                    $arrayOutput[$strName]['ItemClassificationCode'] = [
                        'listID' => $child4->attributes()->listID->__toString(),
                        'value'  => $child4->__toString(),
                    ];
                    break;
                case 'SellersItemIdentification':
                    $arrayOutput[$strName]['ID']                     = $value->children('cbc', true)->ID->__toString();
                    break;
            }
        }
        return $arrayOutput;
    }

    private function getLinePrice($child2): array
    {
        $arrayOutput = [
            'PriceAmount' => $this->getTagWithCurrencyParameterAsString($child2->children('cbc', true)->PriceAmount),
        ];
        // optional components =========================================================================================
        if (isset($child2->children('cbc', true)->BaseQuantity)) {
            $arrayOutput['BaseQuantity'] = $this->getTagWithUnitCodeParameter($child2->children('cbc', true)
                ->BaseQuantity);
        }
        return $arrayOutput;
    }
}
