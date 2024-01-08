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

class electornicInvoiceRead
{

    use traitHeader,
        traitLines;

    public function readElectronicInvoice($strFile) {
        $objFile                 = new \SimpleXMLElement($strFile, NULL, TRUE);
        $arrayDocument           = [
            'DocumentTagName'    => $objFile->getName(),
            'DocumentNameSpaces' => $objFile->getDocNamespaces(true),
        ];
        $arrayCAC                = explode(':', $arrayDocument['DocumentNameSpaces']['cac']);
        $strElementA             = $arrayCAC[count($arrayCAC) - 1]; // CommonAggregateComponents
        $arrayDocument['Header'] = $this->getHeader([
            'CAC'                => $objFile->children('cac', true),
            'cacName'            => $strElementA,
            'CBC'                => $objFile->children('cbc', true),
            'DocumentNameSpaces' => $arrayDocument['DocumentNameSpaces'],
            'DocumentTagName'    => $arrayDocument['DocumentTagName'],
        ]);
        $intLineNo               = 0;
        foreach ($objFile->children('cac', true) as $child) {
            $strCurrentTag = $child->getName();
            switch ($strCurrentTag) {
                case 'CreditNoteLine':
                case 'InvoiceLine':
                    $intLineNo++;
                    $intLineStr                          = ($intLineNo < 10 ? '0' : '') . $intLineNo;
                    $arrayDocument['Lines'][$intLineStr] = $this->getLine($arrayDocument['DocumentTagName'], $child);
                    break;
            }
            unset($strCurrentTag);
        }
        return $arrayDocument;
    }
}
