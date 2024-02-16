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

trait TraitUserInterfaceLogic
{
    use \danielgp\io_operations\InputOutputFiles;

    protected array $arrayConfiguration;
    protected $translation;

    protected function getConfiguration()
    {
        $this->arrayConfiguration = $this->getArrayFromJsonFile(__DIR__
            . DIRECTORY_SEPARATOR . 'config', 'BasicConfiguration.json');
    }

    /**
     * Archived document is read as content in memory since 2024-02-16
     * (prior to this date a temporary local file was saved, processed and finally removed when done with it)
     *
     * @param array $arrayData
     * @return array
     */
    private function getDocumentDetails(array $arrayData): array
    {
        $appR               = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayElectronicInv = $appR->readElectronicInvoice($arrayData['strInvoiceContent']);
        $arrayBasic         = $arrayElectronicInv['Header']['CommonBasicComponents-2'];
        $arrayAggregate     = $arrayElectronicInv['Header']['CommonAggregateComponents-2'];
        $arrayStandardized  = [
            'Customer'    => $arrayAggregate['AccountingCustomerParty']['Party'],
            'ID'          => $arrayBasic['ID'],
            'IssueDate'   => $arrayBasic['IssueDate'],
            'No_of_Lines' => count($arrayElectronicInv['Lines']),
            'Supplier'    => $arrayAggregate['AccountingSupplierParty']['Party'],
            'TOTAL'       => (float) $arrayAggregate['LegalMonetaryTotal']['TaxInclusiveAmount']['value'],
            'wo_VAT'      => (float) $arrayAggregate['LegalMonetaryTotal']['TaxExclusiveAmount']['value'],
        ];
        return $arrayStandardized;
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
            $arrayToReturn = $this->setStandardizedFeedbackArray([
                'Response_Index'   => pathinfo($strFile)['filename'],
                'NotOpeningReason' => $this->translation->find(null, 'i18n_Msg_InvalidZip')->getTranslation(),
            ]);
            // @codeCoverageIgnoreEnd
        }
        return $arrayToReturn;
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

    private function setDaysElapsed(string $strFirstDate, string $strLaterDate): string
    {
        $origin   = new \DateTimeImmutable($strFirstDate);
        $target   = new \DateTimeImmutable($strLaterDate);
        $interval = $origin->diff($target);
        return $interval->format('%R%a');
    }

    private function setLocalization(): void
    {
        if (!array_key_exists('language_COUNTRY', $_GET)) {
            $_GET['language_COUNTRY'] = 'ro_RO';
        }
        $loader            = new \Gettext\Loader\PoLoader();
        $this->translation = $loader->loadFile(__DIR__ . '/locale/' . $_GET['language_COUNTRY']
            . '/LC_MESSAGES/eFactura.po');
    }

    private function setStandardizedFeedbackArray(array $arrayData): array
    {
        $arrayToReturn = [
            'Response_Date'   => '',
            'Response_Index'  => $arrayData['Response_Index'],
            'Loading_Index'   => '',
            'Size'            => '',
            'Document_No'     => '',
            'Issue_Date'      => '',
            'Issue_YearMonth' => '',
            'Amount_wo_VAT'   => '',
            'Amount_TOTAL'    => '',
            'Amount_VAT'      => '',
            'Supplier_CUI'    => '',
            'Supplier_Name'   => '',
            'Customer_CUI'    => '',
            'Customer_Name'   => '',
            'No_of_Lines'     => '',
            'Error'           => '',
            'Days_Between'    => '',
        ];
        if ($arrayData['Size'] > 1000) {
            $arrayAttr                        = $this->getDocumentDetails($arrayData);
            $arrayToReturn['Loading_Index']   = substr($arrayData['Matches'][0][0], 0, -4);
            $arrayToReturn['Size']            = $arrayData['Size'];
            $arrayToReturn['Document_No']     = $arrayAttr['ID'];
            $arrayToReturn['Issue_Date']      = $arrayAttr['IssueDate'];
            $arrayToReturn['Issue_YearMonth'] = (new \IntlDateFormatter(
                    $_GET['language_COUNTRY'],
                    \IntlDateFormatter::FULL,
                    \IntlDateFormatter::FULL,
                    $this->translation->find(null, 'i18n_TimeZone')->getTranslation(),
                    \IntlDateFormatter::GREGORIAN,
                    'r-MM__MMMM'
                ))->format(new \DateTime($arrayAttr['IssueDate']));
            $arrayToReturn['Response_Date']   = $arrayData['FileDate'];
            $arrayToReturn['Amount_wo_VAT']   = $arrayAttr['wo_VAT'];
            $arrayToReturn['Amount_TOTAL']    = $arrayAttr['TOTAL'];
            $arrayToReturn['Amount_VAT']      = round(($arrayAttr['TOTAL'] - $arrayAttr['wo_VAT']), 2);
            $arrayToReturn['Supplier_CUI']    = $this->setDataSupplierOrCustomer($arrayAttr['Supplier']);
            $arrayToReturn['Supplier_Name']   = $arrayAttr['Supplier']['PartyLegalEntity']['RegistrationName'];
            $arrayToReturn['Customer_CUI']    = $this->setDataSupplierOrCustomer($arrayAttr['Customer']);
            $arrayToReturn['Customer_Name']   = $arrayAttr['Customer']['PartyLegalEntity']['RegistrationName'];
            $arrayToReturn['No_of_Lines']     = $arrayAttr['No_of_Lines'];
            $arrayToReturn['Days_Between']    = $this->setDaysElapsed($arrayAttr['IssueDate'], $arrayData['FileDate']);
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
}
