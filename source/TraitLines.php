<?php

/*
 * Copyright (c) 2024, Daniel Popiniuc and its licensors.
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v20.html
 *
 * Contributors:
 *    Daniel Popiniuc
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
                    $arrayOutput[$strElement] = $this->getLineItem($child->children('cac', true)->$strElement);
                    break;
                case 'Multiple':
                    if (count($child->children('cac', true)->$strElement) !== 0) {
                        $arrayOutput[$strElement] = $this->getMultipleElementsByKey($child
                                ->children('cac', true)->$strElement);
                    }
                    break;
                case 'Single':
                    if (count($child->children('cbc', true)->$strElement) !== 0) {
                        $arrayOutput[$strElement] = $this->getElementSingle($child->children('cbc', true)->$strElement);
                    } elseif (count($child->children('cac', true)->$strElement) !== 0) {
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
                    $arrayOutput[$key] = $this->getMultipleElementsByKey($child3->children('cac', true)->$key);
                    break;
                case 'Single':
                    if (count($child3->children('cbc', true)->$key) !== 0) {
                        $arrayOutput[$key] = $child3->children('cbc', true)->$key->__toString();
                    } elseif (count($child3->children('cac', true)->$key) !== 0) {
                        $arrayOutput[$key] = $this->getElements($child3->children('cac', true)->$key);
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
