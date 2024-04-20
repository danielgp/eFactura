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
        foreach ($arrayDataIn->children($this->arrayProcessing['mapping']['cac'], true) as $strNodeName => $child) {
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
                    $arrayOutput[$strElement] = $this->getLineItem($child->children($this->arrayProcessing['mapping']['cac'], true)->$strElement);
                    break;
                case 'Multiple':
                    if (isset($child->children($this->arrayProcessing['mapping']['cac'], true)->$strElement)) {
                        $arrayOutput[$strElement] = $this->getMultipleElementsByKey($child
                                ->children($this->arrayProcessing['mapping']['cac'], true)->$strElement);
                    }
                    break;
                case 'Single':
                    if (isset($child->children($this->arrayProcessing['mapping']['cbc'], true)->$strElement)) {
                        $arrayOutput[$strElement] = $this->getElementSingle($child->children($this->arrayProcessing['mapping']['cbc'], true)->$strElement);
                    } elseif (isset($child->children($this->arrayProcessing['mapping']['cac'], true)->$strElement)) {
                        $arrayOutput[$strElement] = $this->getElements($child->children($this->arrayProcessing['mapping']['cac'], true)->$strElement);
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
                    if (isset($child3->children($this->arrayProcessing['mapping']['cac'], true)->$key)) {
                        $arrayOutput[$key] = $this->getMultipleElementsByKey($child3->children($this->arrayProcessing['mapping']['cac'], true)->$key);
                    }
                    break;
                case 'Single':
                    if (isset($child3->children($this->arrayProcessing['mapping']['cbc'], true)->$key)) {
                        $arrayOutput[$key] = $child3->children($this->arrayProcessing['mapping']['cbc'], true)->$key->__toString();
                    } elseif (isset($child3->children($this->arrayProcessing['mapping']['cac'], true)->$key)) {
                        $arrayOutput[$key] = $this->getElements($child3->children($this->arrayProcessing['mapping']['cac'], true)->$key);
                    }
                    break;
                case 'TaxCategory':
                    if (isset($child3->children($this->arrayProcessing['mapping']['cac'], true)->$key)) {
                        $arrayOutput[$key] = $this->getTaxCategory($child3->children($this->arrayProcessing['mapping']['cac'], true)->$key, $key);
                    }
                    break;
            }
        }
        return $arrayOutput;
    }
}
