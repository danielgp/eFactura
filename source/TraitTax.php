<?php

/*
 * Copyright (c) 2024, Daniel Popiniuc and its licensors.
 *
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Daniel Popiniuc
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

    private function getTaxCategory(\SimpleXMLElement $child3, string $strElementName): array
    {
        $arrayOut = [];
        foreach ($this->arrayProcessing[$strElementName] as $strElement => $strType) {
            switch ($strType) {
                case 'Elements':
                    if (isset($child3->children('cac', true)->$strElement)) {
                        $arrayOut[$strElement] = $this->getElements($child3->children('cac', true)->$strElement);
                    }
                    break;
                case 'Single':
                    if (isset($child3->children('cbc', true)->$strElement)) {
                        $arrayOut[$strElement] = $this->getElementSingle($child3->children('cbc', true)->$strElement);
                    }
                    break;
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
