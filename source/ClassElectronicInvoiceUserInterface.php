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

class ClassElectronicInvoiceUserInterface
{
    use \danielgp\io_operations\InputOutputFiles;

    private array $arrayConfiguration;
    private \SebastianBergmann\Timer\Timer $classTimer;

    public function __construct()
    {
        $this->classTimer         = new \SebastianBergmann\Timer\Timer();
        $this->classTimer->start();
        $this->arrayConfiguration = $this->getArrayFromJsonFile(__DIR__
            . DIRECTORY_SEPARATOR . 'config', 'BasicConfiguration.json');
    }

    private function actionAnalyzeZIPfromANAFfromLocalFolder(string $strFilePath): array
    {
        $arrayFiles    = new \RecursiveDirectoryIterator($strFilePath, \FilesystemIterator::SKIP_DOTS);
        $classZip      = new \ZipArchive();
        $arrayInvoices = [];
        $intFileNo     = 0;
        foreach ($arrayFiles as $strFile) {
            if ($strFile->isFile() && ($strFile->getExtension() === 'zip')) {
                $arrayInvoices[$intFileNo] = $this->setStandardizedFeedbackArray([
                    'ResponseIndex' => $strFile->getBasename('.zip'),
                    'Type'          => 'Default',
                ]);
                $res                       = $classZip->open($strFile->getRealPath(), \ZipArchive::RDONLY);
                if ($res === true) {
                    $intFilesArchived = $classZip->numFiles;
                    for ($intArchivedFile = 0; $intArchivedFile < $intFilesArchived; $intArchivedFile++) {
                        $strArchivedFile = $classZip->getNameIndex($intArchivedFile);
                        $matches         = [];
                        preg_match('/^[0-9]{5,20}\.xml$/', $strArchivedFile, $matches, PREG_OFFSET_CAPTURE);
                        $matches2        = [];
                        preg_match('/^semnatura_[0-9]{5,20}\.xml$/', $strArchivedFile, $matches2, PREG_OFFSET_CAPTURE);
                        if ($matches !== []) {
                            $resInvoice        = $classZip->getStream($strArchivedFile);
                            $strInvoiceContent = stream_get_contents($resInvoice);
                            fclose($resInvoice);
                            $intInvoiceSize    = strlen($strInvoiceContent);
                            if ($intInvoiceSize > 1000) {
                                $arrayInvoices[$intFileNo] = $this->setStandardizedFeedbackArray([
                                    'ResponseIndex'       => $strFile->getBasename('.zip'),
                                    'Size'                => $intInvoiceSize,
                                    'Type'                => 'XmlFile_Large',
                                    'LoadingIndex'        => substr($matches[0][0], 0, -4),
                                    'strArchivedFileName' => $strArchivedFile,
                                    'strInvoiceContent'   => $strInvoiceContent,
                                ]);
                            } else {
                                $arrayInvoices[$intFileNo] = $this->setStandardizedFeedbackArray([
                                    'ResponseIndex'     => $strFile->getBasename('.zip'),
                                    'Size'              => $intInvoiceSize,
                                    'Type'              => 'XmlFile_Small',
                                    'strInvoiceContent' => $strInvoiceContent,
                                ]);
                            }
                        } elseif ($matches2 === []) {
                            echo vsprintf('<div>' . $this->arrayConfiguration['Feedback']['DifferentFile'] . '</div>', [
                                $strArchivedFile,
                                $strFile->getBasename(),
                            ]);
                        }
                    }
                } else {
                    // @codeCoverageIgnoreStart
                    throw new \RuntimeException(sprintf('Archive %s could not be opened!', $strFile->getRealPath()));
                    // @codeCoverageIgnoreEnd
                }
                $intFileNo++;
            }
        }
        return $arrayInvoices;
    }

    private function getButtonToActionSomething(array $arrayButtonFeatures): string
    {
        $arrayButtonStyle = [
            'font: bold 14pt Arial',
            'margin: 2px',
            'padding: 4px 10px',
        ];
        $arrayStylePieces = $arrayButtonStyle;
        if (array_key_exists('AdditionalStyle', $arrayButtonFeatures)) {
            $arrayStylePieces = array_merge($arrayButtonStyle, $arrayButtonFeatures['AdditionalStyle']);
        }
        return vsprintf('<a href="%s" class="btn btn-outline-primary" style="%s">%s</a>', [
            $arrayButtonFeatures['URL'],
            implode(';', $arrayStylePieces),
            $arrayButtonFeatures['Text'],
        ]);
    }

    public function setActionToDo(): void
    {
        echo '<main>';
        $arrayOptions = [
            'action' => FILTER_SANITIZE_SPECIAL_CHARS,
        ];
        $arrayInputs  = filter_input_array(INPUT_GET, $arrayOptions);
        if (array_key_exists('action', $arrayInputs)) {
            switch ($arrayInputs['action']) {
                case 'AnalyzeZIPfromANAFfromLocalFolder':
                    $arrayInvoices = $this->actionAnalyzeZIPfromANAFfromLocalFolder('P:/e-Factura/Downloaded/');
                    $this->setArrayToHtmlTable($arrayInvoices);
                    break;
            }
        }
        echo '</main>';
    }

    private function setArrayToHtmlTable(array $arrayData)
    {
        foreach ($arrayData as $intLineNo => $arrayContent) {
            ksort($arrayContent);
            if ($intLineNo === 0) {
                echo '<table style="margin-left:auto;margin-right:auto;">'
                . '<thead>'
                . '<tr>'
                . '<th>' . implode('</th><th>', array_keys($arrayContent)) . '</th>'
                . '</tr>'
                . '</thead>';
                echo '<tbody>';
            }
            echo '<tr' . ($arrayContent['Error'] === '' ? '' : ' style="color:red;"') . '>'
            . '<td>' . implode('</td><td>', array_values($arrayContent)) . '</td>'
            . '</tr>';
        }
        echo '</tbody>'
        . '</table>';
    }

    public function setHtmlFooter(): void
    {
        $strHtmlContent = implode('', $this->getFileEntireContent(implode(DIRECTORY_SEPARATOR, [
                __DIR__,
                'HTML',
                'footer.html',
        ])));
        echo vsprintf($strHtmlContent, [
            (new \SebastianBergmann\Timer\ResourceUsageFormatter())->resourceUsage($this->classTimer->stop()),
            date('Y'),
            $this->arrayConfiguration['Application']['Developer'],
        ]);
    }

    public function setHtmlHeader(): void
    {
        $strHtmlContent = implode('', $this->getFileEntireContent(implode(DIRECTORY_SEPARATOR, [
                __DIR__,
                'HTML',
                'header.html',
        ])));
        echo vsprintf($strHtmlContent, [
            $this->arrayConfiguration['Application']['Name'],
            $this->arrayConfiguration['Application']['Developer'],
        ]);
    }

    private function setStandardizedFeedbackArray(array $arrayData): array
    {
        $arrayToReturn = [
            'ResponseIndex' => $arrayData['ResponseIndex'],
            'LoadingIndex'  => '',
            'Size'          => '',
            'Document_No'   => '',
            'Issue_Date'    => '',
            'Amount_wo_VAT' => '',
            'Amount_TOTAL'  => '',
            'Amount_VAT'    => '',
            'Supplier_CUI'  => '',
            'Supplier_Name' => '',
            'Customer_CUI'  => '',
            'Customer_Name' => '',
            'Error'         => '',
        ];
        switch ($arrayData['Type']) {
            case 'XmlFile_Large':
                file_put_contents($arrayData['strArchivedFileName'], $arrayData['strInvoiceContent']);
                $appR                           = new \danielgp\efactura\ClassElectronicInvoiceRead();
                $arrayElectronicInv             = $appR->readElectronicInvoice($arrayData['strArchivedFileName']);
                $arrayBasic                     = $arrayElectronicInv['Header']['CommonBasicComponents-2'];
                $arrayAggregate                 = $arrayElectronicInv['Header']['CommonAggregateComponents-2'];
                $floatAmounts                   = [
                    'wo_VAT' => (float) $arrayAggregate['LegalMonetaryTotal']['TaxExclusiveAmount']['value'],
                    'TOTAL'  => (float) $arrayAggregate['LegalMonetaryTotal']['TaxInclusiveAmount']['value'],
                ];
                $arrayParties                   = [
                    'Customer' => $arrayAggregate['AccountingCustomerParty']['Party'],
                    'Supplier' => $arrayAggregate['AccountingSupplierParty']['Party'],
                ];
                $arrayToReturn['LoadingIndex']  = $arrayData['LoadingIndex'];
                $arrayToReturn['Size']          = $arrayData['Size'];
                $arrayToReturn['Document_No']   = $arrayBasic['ID'];
                $arrayToReturn['Issue_Date']    = $arrayBasic['IssueDate'];
                $arrayToReturn['Amount_wo_VAT'] = $floatAmounts['wo_VAT'];
                $arrayToReturn['Amount_TOTAL']  = $floatAmounts['TOTAL'];
                $arrayToReturn['Amount_VAT']    = ($floatAmounts['TOTAL'] - $floatAmounts['wo_VAT']);
                $arrayToReturn['Supplier_CUI']  = $arrayParties['Supplier']['PartyTaxScheme']['01']['CompanyID'];
                $arrayToReturn['Supplier_Name'] = $arrayParties['Supplier']['PartyLegalEntity']['RegistrationName'];
                $arrayToReturn['Customer_CUI']  = $arrayParties['Customer']['PartyTaxScheme']['01']['CompanyID'];
                $arrayToReturn['Customer_Name'] = $arrayParties['Customer']['PartyLegalEntity']['RegistrationName'];
                unlink($arrayData['strArchivedFileName']);
                break;
            case 'XmlFile_Small':
                $objErrors                      = new \SimpleXMLElement($arrayData['strInvoiceContent']);
                $arrayToReturn['LoadingIndex']  = $objErrors->attributes()->Index_incarcare->__toString();
                $arrayToReturn['Size']          = $arrayData['Size'];
                $arrayToReturn['Supplier_CUI']  = $objErrors->attributes()->Cif_emitent->__toString();
                $arrayToReturn['Supplier_Name'] = '??????????';
                $arrayToReturn['Error']         = $objErrors->Error->attributes()->errorMessage->__toString();
                break;
        }
        return $arrayToReturn;
    }

    public function setUserInterface(): void
    {
        echo '<header class="border-bottom">'
        . $this->getButtonToActionSomething([
            'Text' => 'Analyze ZIP archives from ANAF from a local folder',
            'URL'  => '?action=AnalyzeZIPfromANAFfromLocalFolder',
        ])
        . '</header>';
    }
}
