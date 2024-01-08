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

trait traitLines
{

    use traitTax;

    private function getLine(string $strType, $child): array {
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
        $arrayOutput['Item']  = $this->getLineItem($child->children('cac', true)->Item);
        $arrayOutput['Price'] = $this->getLinePrice($child->children('cac', true)->Price);
        return $arrayOutput;
    }

    private function getLineItem($child3): array {
        $arrayOutput = [
            'Name' => $child3->children('cbc', true)->Name->__toString(),
        ];
        // optional components =========================================================================================
        if (isset($child3->children('cbc', true)->Description)) {
            $arrayOutput['Description'] = $child3->children('cac', true)->Description->__toString();
        }
        // Sub-sections ================================================================================================
        if (isset($child3->children('cac', true)->AllowanceCharge)) {
            $intLineNo = 0;
            foreach ($child3->children('cac', true)->AllowanceCharge as $child4) {
                $intLineNo++;
                $intLineStr                                  = ($intLineNo < 10 ? '0' : '') . $intLineNo;
                $arrayOutput['AllowanceCharge'][$intLineStr] = $this->getLineItemAllowanceCharge();
            }
        }
        if (isset($child3->children('cac', true)->CommodityClassification)) {
            $child4                                                           = $child3->children('cac', true)
                ->CommodityClassification->children('cbc', true)->ItemClassificationCode;
            $arrayOutput['CommodityClassification']['ItemClassificationCode'] = [
                'listID' => $child4->attributes()->listID->__toString(),
                'value'  => $child4->__toString(),
            ];
        }
        if (isset($child3->children('cac', true)->SellersItemIdentification)) {
            $arrayOutput['SellersItemIdentification']['ID'] = $child3
                    ->children('cac', true)->SellersItemIdentification->children('cbc', true)->ID->__toString();
        }
        $arrayOutput['ClassifiedTaxCategory'] = $this->getTaxCategory($child3
                ->children('cac', true)->ClassifiedTaxCategory);
        return $arrayOutput;
    }

    private function getLineItemAllowanceCharge($child2): array {
        $arrayOutput = [
            'Amount'                    => $this->getTagWithCurrencyParameter($child2->children('cbc', true)->Amount),
            'AllowanceChargeReason'     => $child2->children('cbc', true)->AllowanceChargeReason->__toString(),
            'AllowanceChargeReasonCode' => $child2->children('cbc', true)->AllowanceChargeReasonCode->__toString(),
            'ChargeIndicator'           => $child2->children('cbc', true)->ChargeIndicator->__toString(),
        ];
        // optional components =========================================================================================
        if (isset($child2->children('cbc', true)->BaseAmount)) {
            $arrayOutput['BaseAmount'] = $this->getTagWithCurrencyParameter($child2->children('cbc', true)
                ->BaseAmount);
        }
        return $arrayOutput;
    }

    private function getLinePrice($child2): array {
        $arrayOutput = [
            'PriceAmount' => $this->getTagWithCurrencyParameter($child2->children('cbc', true)->PriceAmount),
        ];
        // optional components =========================================================================================
        if (isset($child2->children('cbc', true)->BaseQuantity)) {
            $arrayOutput['BaseQuantity'] = $this->getTagWithUnitCodeParameter($child2->children('cbc', true)
                ->BaseQuantity);
        }
        return $arrayOutput;
    }
}
