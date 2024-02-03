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

    private function getTax(\SimpleXMLElement $child): array
    {
        $arrayOut  = [];
        $intLineNo = 0;
        foreach ($child as $child2) {
            if (!is_null($child2)) {
                $intLineNo++;
                $intLineStr            = $this->getLineStringFromNumber($intLineNo);
                $arrayOut[$intLineStr] = $this->getTaxTotal($child2);
            }
        }
        return $arrayOut;
    }

    private function getTaxSubTotal(\SimpleXMLElement $child)
    {
        $arrayOut = [];
        foreach ($this->arrayProcessing['TaxSubtotal'] as $strElementChild => $strTypeChild) {
            switch ($strTypeChild) {
                case 'Single':
                    if (isset($child->children('cbc', true)->$strElementChild)) {
                        $arrayOut[$strElementChild] = $this
                            ->getElementSingle($child->children('cbc', true)->$strElementChild);
                    }
                    break;
                case 'Multiple':
                    $arrayOut[$strElementChild] = $this->getTaxCategory($child->children('cac', true)
                        ->$strElementChild, $strElementChild);
                    break;
            }
        }
        return $arrayOut;
    }

    private function getTaxTotal(\SimpleXMLElement $child): array
    {
        $arrayOut = [];
        foreach ($this->arrayProcessing['TaxTotal'] as $strElement => $strType) {
            switch ($strType) {
                case 'Single':
                    if (isset($child->children('cbc', true)->$strElement)) {
                        $arrayOut[$strElement] = $this->getElementSingle($child->children('cbc', true)->$strElement);
                    }
                    break;
                case 'Multiple':
                    $intLineNo = 0;
                    foreach ($child->children('cac', true)->$strElement as $child3) {
                        $intLineNo++;
                        $intLineStr                         = $this->getLineStringFromNumber($intLineNo);
                        $arrayOut[$strElement][$intLineStr] = $this->getTaxSubTotal($child3);
                    }
                    break;
            }
        }
        return $arrayOut;
    }
}
