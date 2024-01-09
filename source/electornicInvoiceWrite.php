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

class electornicInvoiceWrite
{

    use traitVersions;

    protected $objXmlWriter;

    private function setDocumentBasicElements(array $arrayElementWithData): void {
        foreach ($arrayElementWithData as $key => $value) {
            if (array_key_exists($key, $this->arraySettings['Defaults']['Comments']['CBC'])) {
                $this->objXmlWriter->writeComment($this->arraySettings['Defaults']['Comments']['CBC'][$key]);
            }
            $this->objXmlWriter->writeElement('cbc:' . $key, $value);
        }
    }

    private function setDocumentHeader(array $arrayDocumentData): void {
        $this->objXmlWriter->startElement($arrayDocumentData['DocumentTagName']);
        foreach ($arrayDocumentData['DocumentNameSpaces'] as $key => $value) {
            if ($key === '') {
                $strValue = sprintf($value, $arrayDocumentData['DocumentTagName']);
                $this->objXmlWriter->writeAttributeNS(NULL, 'xmlns', NULL, $strValue);
            } else {
                $this->objXmlWriter->writeAttributeNS('xmlns', $key, NULL, $value);
            }
        }
        if (array_key_exists('SchemaLocation', $arrayDocumentData)) {
            $this->objXmlWriter->writeAttribute('xsi:schemaLocation', $arrayDocumentData['SchemaLocation']);
        }
    }

    public function writeElectronicInvoice(string $strFile, array $arrayDocumentData): void {
        $this->objXmlWriter = new \XMLWriter();
        $this->objXmlWriter->openURI($strFile);
        $this->objXmlWriter->setIndent(true);
        $this->objXmlWriter->setIndentString(str_repeat(' ', 4));
        $this->objXmlWriter->startDocument('1.0', 'UTF-8');
        // if no DocumentNameSpaces seen take Default ones from local configuration
        if (!array_key_exists('DocumentNameSpaces', $arrayDocumentData)) {
            $this->getSettingsFromFileIntoMemory();
            $arrayDocumentData['DocumentNameSpaces'] = $this->arraySettings['Defaults']['DocumentNameSpaces'];
            $arrayDocumentData['SchemaLocation']     = vsprintf($this->arraySettings['Defaults']['SchemaLocation'], [
                $arrayDocumentData['DocumentTagName'],
                $arrayDocumentData['UBLVersionID'],
                $arrayDocumentData['DocumentTagName'],
                $arrayDocumentData['UBLVersionID'],
            ]);
        }
        $this->setDocumentHeader($arrayDocumentData);
        $this->setDocumentBasicElements($arrayDocumentData['Header']['CommonBasicComponents-2']);
        // TODO: add logic for each section
        $this->objXmlWriter->endElement(); // Invoice or CreditNote
        $this->objXmlWriter->flush();
    }
}
