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

trait TraitTax
{

    use TraitBasic;

    private function getTaxCategory($child3): array
    {
        $arrayOutput = [
            'ID'        => $child3->children('cbc', true)->ID->__toString(),
            'TaxScheme' => [
                'ID' => $child3->children('cac', true)->TaxScheme->children('cbc', true)->ID->__toString(),
            ],
        ];
        // optional components =========================================================================================
        if (isset($child3->children('cbc', true)->Percent)) {
            $arrayOutput['Percent'] = $child3->children('cbc', true)->Percent->__toString();
        }
        if (isset($child3->children('cbc', true)->TaxExemptionReason)) {
            $arrayOutput['TaxExemptionReason'] = $child3->children('cbc', true)->TaxExemptionReason->__toString();
        }
        return $arrayOutput;
    }

    private function getTaxSubTotal($child3): array
    {
        return [
            'TaxAmount'     => $this->getElementSingle($child3->children('cbc', true)->TaxAmount),
            'TaxableAmount' => $this->getElementSingle($child3->children('cbc', true)->TaxableAmount),
            'TaxCategory'   => $this->getTaxCategory($child3->children('cac', true)->TaxCategory),
        ];
    }

    private function getTaxTotal($child2): array
    {
        $arrayOutput = [
            'TaxAmount' => $this->getElementSingle($child2->children('cbc', true)->TaxAmount)
        ];
        if (isset($child2->children('cac', true)->TaxSubtotal)) {
            $intLineNo = 0;
            foreach ($child2->children('cac', true)->TaxSubtotal as $child3) {
                $intLineNo++;
                $intLineStr                              = ($intLineNo < 10 ? '0' : '') . $intLineNo;
                $arrayOutput['TaxSubtotal'][$intLineStr] = $this->getTaxSubTotal($child3);
            }
        }
        return $arrayOutput;
    }
}
