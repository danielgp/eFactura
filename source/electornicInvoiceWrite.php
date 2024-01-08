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

    protected $arrayUniformResourceLocator = [
        'DefaultEnvironmentName' => 'test',
        'Domain'                 => 'https://webserviceapl.anaf.ro/{environmentName}/FCTEL/rest/{featureName}',
        'FeatureNames'           => [
            'Upload'         => 'upload',
            'Message_Status' => 'stareMesaj',
            'Messages_List'  => 'listaMesajeFactura',
            'Download'       => 'descarcare',
        ],
        'Versions'               => [
            '1.0.7' => [
                'Validity'     => [
                    'Start' => '2021-11-11',
                    'End'   => '2022-12-28',
                ],
                'Last_Updates' => '2022-10-18',
                'UBL'          => '2.1',
                'CIUS-RO'      => '1.0.0',
            ],
            '1.0.8' => [
                'Validity'     => [
                    'Start' => '2022-12-29',
                    'End'   => '2099-12-31',
                ],
                'Last_Updates' => '2022-07-12',
                'UBL'          => '2.1',
                'CIUS-RO'      => '1.0.1',
            ]
        ]
    ];
    protected $objXmlWriter;

    private function establishCurrentVersion(array $arrayKnownVersions): array {
        $arrayVersionToReturn = [];
        $dtValidityNow        = new \DateTime();
        foreach ($arrayKnownVersions as $value) {
            $dtValidityStart = new \DateTime($value['Validity']['Start']);
            $dtValidityEnd   = new \DateTime($value['Validity']['End']);
            if (($dtValidityNow >= $dtValidityStart) && ($dtValidityNow <= $dtValidityEnd)) {
                $arrayVersionToReturn = [
                    'UBL'     => $value['UBL'],
                    'CIUS-RO' => $value['CIUS-RO'],
                ];
            }
        }
        return $arrayVersionToReturn;
    }

    private function setDocumentHeader(string $strDocumentTagName, array $arrayVersion): void {
        $this->objXmlWriter->startElement($strDocumentTagName);
        $strCommonPrefix = 'urn:oasis:names:specification:ubl:schema:xsd:';
        $this->objXmlWriter->writeAttributeNS(NULL, 'xmlns', NULL, $strCommonPrefix
            . $strDocumentTagName . '-2');
        $this->objXmlWriter->writeAttributeNS('xmlns', 'cac', NULL, $strCommonPrefix . 'CommonAggregateComponents-2');
        $this->objXmlWriter->writeAttributeNS('xmlns', 'cbc', NULL, $strCommonPrefix . 'CommonBasicComponents-2');
        $this->objXmlWriter->writeAttributeNS('xmlns', 'ext', NULL, $strCommonPrefix . 'CommonExtensionComponents-2');
        $this->objXmlWriter->writeAttributeNS('xmlns', 'qdt', NULL, $strCommonPrefix . 'QualifiedDataTypes-2');
        $this->objXmlWriter->writeAttributeNS('xmlns', 'udt', NULL, $strCommonPrefix . 'UnqualifiedDataTypes-2');
        $this->objXmlWriter->writeAttributeNS('xmlns', 'xs', NULL, 'http://www.w3.org/2001/XMLSchema');
        $this->objXmlWriter->writeAttributeNS('xmlns', 'xsi', NULL, 'http://www.w3.org/2001/XMLSchema-instance');
        $this->objXmlWriter->writeAttributeNS('xsi', 'schemaLocation', NULL, implode(' ', [
            $strCommonPrefix . $strDocumentTagName . '-2', implode('-', [
                'http://docs.oasis-open.org/ubl/os-UBL',
                $arrayVersion['UBL'] . '/xsd/maindoc/UBL',
                $strDocumentTagName,
                $arrayVersion['UBL'] . '.xsd',
            ])
        ]));
    }

    private function setDocumentHeaderVersions(array $arrayVersion): void {
        $this->objXmlWriter->writeElement('cbc:UBLVersionID', $arrayVersion['UBL']);
        $this->objXmlWriter->writeElement('cbc:CustomizationID', 'urn:cen.eu:en16931:2017'
            . '#compliant#urn:efactura.mfinante.ro:CIUS-RO:'
            . $arrayVersion['CIUS-RO']);
    }

    public function writeElectronicInvoice(string $strFile, array $arrayDocumentData): void {
        $this->objXmlWriter = new \XMLWriter();
        $this->objXmlWriter->openURI($strFile);
        $this->objXmlWriter->setIndent(true);
        $this->objXmlWriter->setIndentString(str_repeat(' ', 4));
        $this->objXmlWriter->startDocument('1.0', 'UTF-8');
        $arrayVersion       = $this->establishCurrentVersion($this->arrayUniformResourceLocator['Versions']);
        $this->setDocumentHeader($arrayDocumentData['DocumentTagName']);
        $this->setDocumentHeaderVersions($arrayVersion);
        // TODO: add logic for each section
        $this->objXmlWriter->endElement(); // Invoice or CreditNote
        $this->objXmlWriter->flush();
    }
}
