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

    public function actionAnalyzeZIPfromANAFfromLocalFolder(string $strFilePath): array
    {
        $arrayFiles    = new \RecursiveDirectoryIterator($strFilePath, \FilesystemIterator::SKIP_DOTS);
        $arrayInvoices = [];
        $intFileNo     = 0;
        foreach ($arrayFiles as $strFile) {
            if ($strFile->isFile() && ($strFile->getExtension() === 'zip')) {
                $arrayInvoices[$intFileNo] = $this->setArchiveFromAnaf($strFile->getRealPath());
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

    private function setArchiveFromAnaf(string $strFile)
    {
        $classZip      = new \ZipArchive();
        $arrayToReturn = [];
        $res           = $classZip->open($strFile, \ZipArchive::RDONLY);
        if ($res === true) {
            $intFilesArchived = $classZip->numFiles;
            for ($intArchivedFile = 0; $intArchivedFile < $intFilesArchived; $intArchivedFile++) {
                $strArchivedFile = $classZip->getNameIndex($intArchivedFile);
                $strFileStats    = $classZip->statIndex($intArchivedFile);
                $matches         = [];
                preg_match('/^[0-9]{5,20}\.xml$/', $strArchivedFile, $matches, PREG_OFFSET_CAPTURE);
                $matches2        = [];
                preg_match('/^semnatura_[0-9]{5,20}\.xml$/', $strArchivedFile, $matches2, PREG_OFFSET_CAPTURE);
                if ($matches !== []) {
                    $resInvoice        = $classZip->getStream($strArchivedFile);
                    $strInvoiceContent = stream_get_contents($resInvoice);
                    fclose($resInvoice);
                    $arrayToReturn     = $this->setStandardizedFeedbackArray([
                        'Response_Index'      => pathinfo($strFile)['filename'],
                        'Size'                => $strFileStats['size'],
                        'FileDate'            => date('Y-m-d H:i:s', $strFileStats['mtime']),
                        'Matches'             => $matches,
                        'strArchivedFileName' => $strArchivedFile,
                        'strInvoiceContent'   => $strInvoiceContent,
                    ]);
                } elseif ($matches2 === []) {
                    echo vsprintf('<div>' . $this->arrayConfiguration['Feedback']['DifferentFile'] . '</div>', [
                        $strArchivedFile,
                        $strFile->getBasename(),
                    ]);
                }
            }
        } else {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(sprintf('Archive %s could not be opened!', $strFile));
            // @codeCoverageIgnoreEnd
        }
        return $arrayToReturn;
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

    private function setArrayToHtmlTableHeader(array $arrayData): string
    {
        $arrayMap    = [
            'Amount_TOTAL'   => 'TOTAL',
            'Amount_VAT'     => 'TVA',
            'Amount_wo_VAT'  => 'Valoare',
            'Customer_CUI'   => 'CUI client',
            'Customer_Name'  => 'Nume client',
            'Document_No'    => 'Identificator',
            'Error'          => 'Eroare',
            'Issue_Date'     => 'Data emiterii',
            'Loading_Index'  => 'Index încărcare',
            'No_Lines'       => 'Nr. linii',
            'Response_Date'  => 'Data răspuns',
            'Response_Index' => 'Index răspuns',
            'Supplier_CUI'   => 'CUI emitent',
            'Supplier_Name'  => 'Nume emitent',
        ];
        $strToReturn = '<th>#</th>';
        foreach ($arrayData as $key) {
            $strToReturn .= sprintf('<th>%s</th>', (array_key_exists($key, $arrayMap) ? $arrayMap[$key] : $key));
        }
        return '<thead><tr>' . $strToReturn . '</tr></thead>';
    }

    public function setArrayToHtmlTable(array $arrayData)
    {
        foreach ($arrayData as $intLineNo => $arrayContent) {
            ksort($arrayContent);
            if ($intLineNo === 0) {
                echo '<table style="margin-left:auto;margin-right:auto;">'
                . $this->setArrayToHtmlTableHeader(array_keys($arrayContent))
                . '<tbody>';
            }
            echo '<tr' . ($arrayContent['Error'] === '' ? '' : ' style="color:red;"') . '>'
            . '<td>' . $intLineNo . '</td>'
            . '<td>' . implode('</td><td>', array_values($arrayContent)) . '</td>'
            . '</tr>';
        }
        echo '</tbody>'
        . '</table>';
    }

    private function setDataSupplierOrCustomer(array $arrayData)
    {
        $strCustomerCui = '';
        if (isset($arrayData['PartyTaxScheme']['01']['CompanyID'])) {
            $strCustomerCui = $arrayData['PartyTaxScheme']['01']['CompanyID'];
        } else {
            $strCustomerCui = $arrayData['PartyLegalEntity']['CompanyID'];
        }
        if (is_numeric($strCustomerCui)) {
            $strCustomerCui = 'RO' . $strCustomerCui;
        }
        return $strCustomerCui;
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
            'Response_Date'  => '',
            'Response_Index' => $arrayData['Response_Index'],
            'Loading_Index'  => '',
            'Size'           => '',
            'Document_No'    => '',
            'Issue_Date'     => '',
            'Amount_wo_VAT'  => '',
            'Amount_TOTAL'   => '',
            'Amount_VAT'     => '',
            'Supplier_CUI'   => '',
            'Supplier_Name'  => '',
            'Customer_CUI'   => '',
            'Customer_Name'  => '',
            'No_Lines'       => '',
            'Error'          => '',
        ];
        if ($arrayData['Size'] > 1000) {
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
            $arrayToReturn['Loading_Index'] = substr($arrayData['Matches'][0][0], 0, -4);
            $arrayToReturn['Size']          = $arrayData['Size'];
            $arrayToReturn['Document_No']   = $arrayBasic['ID'];
            $arrayToReturn['Issue_Date']    = $arrayBasic['IssueDate'];
            $arrayToReturn['Response_Date'] = $arrayData['FileDate'];
            $arrayToReturn['Amount_wo_VAT'] = $floatAmounts['wo_VAT'];
            $arrayToReturn['Amount_TOTAL']  = $floatAmounts['TOTAL'];
            $arrayToReturn['Amount_VAT']    = round(($floatAmounts['TOTAL'] - $floatAmounts['wo_VAT']), 2);
            $arrayToReturn['Supplier_CUI']  = $this->setDataSupplierOrCustomer($arrayParties['Supplier']);
            $arrayToReturn['Supplier_Name'] = $arrayParties['Supplier']['PartyLegalEntity']['RegistrationName'];
            $arrayToReturn['Customer_CUI']  = $this->setDataSupplierOrCustomer($arrayParties['Customer']);
            $arrayToReturn['Customer_Name'] = $arrayParties['Customer']['PartyLegalEntity']['RegistrationName'];
            $arrayToReturn['No_Lines']      = count($arrayElectronicInv['Lines']);
            unlink($arrayData['strArchivedFileName']);
        } elseif ($arrayData['Size'] > 0) {
            $objErrors                      = new \SimpleXMLElement($arrayData['strInvoiceContent']);
            $arrayToReturn['Loading_Index'] = $objErrors->attributes()->Index_incarcare->__toString();
            $arrayToReturn['Size']          = $arrayData['Size'];
            $arrayToReturn['Response_Date'] = $arrayData['FileDate'];
            $arrayToReturn['Supplier_CUI']  = 'RO' . $objErrors->attributes()->Cif_emitent->__toString();
            $arrayToReturn['Supplier_Name'] = '??????????';
            $arrayToReturn['Error']         = '<div style="max-width:200px;font-size:0.8rem;">'
                . $objErrors->Error->attributes()->errorMessage->__toString() . '</div>';
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
