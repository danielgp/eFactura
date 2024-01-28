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
    use TraitBasic;
    use TraitTax;

    private function getDocumentLines(\SimpleXMLElement $arrayDataIn, string $strTag): array
    {
        $arrayLines = [];
        $intLineNo  = 0;
        foreach ($arrayDataIn->children('cac', true) as $strNodeName => $child) {
            if ($strNodeName === ($strTag . 'Line')) {
                $intLineNo++;
                $intLineStr              = $this->getLineStringFromNumber($intLineNo);
                $arrayLines[$intLineStr] = $this->getLine($child);
            }
        }
        return $arrayLines;
    }

    private function getLine(\SimpleXMLElement $child): array
    {
        $arrayOutput = [];
        foreach ($this->arrayProcessing['Lines@Read'] as $strElement => $strType) {
            switch ($strType) {
                case 'Item':
                    $arrayOutput[$strElement] = $this->getLineItem($child->children('cac', true)->Item);
                    break;
                case 'Multiple':
                    $intLineNo                = 0;
                    foreach ($child->children('cac', true)->$strElement as $value2) {
                        $intLineNo++;
                        $intLineStr                            = $this->getLineStringFromNumber($intLineNo);
                        $arrayOutput[$strElement][$intLineStr] = $this->getElements($value2);
                    }
                    break;
                case 'Single':
                    if (count($child->children('cbc', true)->$strElement) !== 0) {
                        $arrayOutput[$strElement] = $this->getElementSingle($child->children('cbc', true)->$strElement);
                    }
                    if (count($child->children('cac', true)->$strElement) !== 0) {
                        $arrayOutput[$strElement] = $this->getElements($child->children('cac', true)->$strElement);
                    }
                    break;
            }
        }
        return $arrayOutput;
    }

    private function getLineItem(\SimpleXMLElement $child3): array
    {
        $arrayOutput = [];
        foreach ($this->arrayProcessing['Lines_Item@Read'] as $key => $value) {
            switch ($value) {
                case 'Multiple':
                    $intLineNo = 0;
                    foreach ($child3->children('cac', true)->$key as $value2) {
                        $intLineNo++;
                        $intLineStr                     = $this->getLineStringFromNumber($intLineNo);
                        $arrayOutput[$key][$intLineStr] = $this->getElements($value2);
                    }
                    break;
                case 'Single':
                    if (count($child3->children('cac', true)->$key) !== 0) {
                        $arrayOutput[$key] = $this->getElements($child3->children('cac', true)->$key);
                    }
                    if (count($child3->children('cbc', true)->$key) !== 0) {
                        $arrayOutput[$key] = $child3->children('cbc', true)->$key->__toString();
                    }
                    break;
                case 'TaxCategory':
                    $arrayOutput[$key] = $this->getTaxCategory($child3->children('cac', true)->$key, $key);
                    break;
            }
        }
        return $arrayOutput;
    }
}
