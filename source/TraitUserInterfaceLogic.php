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

    public function actionAnalyzeZIPfromANAFfromLocalFolder(string $strFilePath): array
    {
        $arrayFiles    = new \RecursiveDirectoryIterator($strFilePath, \FilesystemIterator::SKIP_DOTS);
        $arrayInvoices = [];
        $intFileNo     = 0;
        foreach ($arrayFiles as $strFile) {
            if ($strFile->isFile()) {
                $arrayFileDetails = $this->handleResponseFile($strFile);
                if ($arrayFileDetails !== []) {
                    $arrayInvoices[$intFileNo] = $arrayFileDetails;
                    $intFileNo++;
                }
            }
        }
        return $arrayInvoices;
    }

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

    private function handleResponseFile(\SplFileInfo | string $strFile): array
    {
        $arrayToReturn = [];
        $strFileMime   = mime_content_type($strFile->getRealPath());
        switch ($strFileMime) {
            case 'application/json':
                $arrayError    = $this->getArrayFromJsonFile($strFile->getPath(), $strFile->getFilename());
                $arrayToReturn = $this->setStandardizedFeedbackArray([
                    'Error'          => $arrayError['eroare'] . ' ===> ' . $arrayError['titlu'],
                    'Response_Index' => $strFile->getFilename(),
                    'Response_Size'  => $strFile->getSize(),
                ]);
                break;
            case 'application/zip':
                $arrayToReturn = $this->setArchiveFromAnaf($strFile->getRealPath(), $strFile->getSize());
                break;
        }
        return $arrayToReturn;
    }

    private function handleArchiveContent(\ZipArchive $classZip, array $arrayArchiveParam): array
    {
        $arrayToReturn = [];
        for ($intArchivedFile = 0; $intArchivedFile < $arrayArchiveParam['No_of_Files']; $intArchivedFile++) {
            $strArchivedFile = $classZip->getNameIndex($intArchivedFile);
            $matches         = [];
            preg_match('/^[0-9]{5,20}\.xml$/', $strArchivedFile, $matches, PREG_OFFSET_CAPTURE);
            $matches2        = [];
            preg_match('/^semnatura_[0-9]{5,20}\.xml$/', $strArchivedFile, $matches2, PREG_OFFSET_CAPTURE);
            if ($matches !== []) {
                $resInvoice        = $classZip->getStream($strArchivedFile);
                $strInvoiceContent = stream_get_contents($resInvoice);
                fclose($resInvoice);
                $strFileStats      = $classZip->statIndex($intArchivedFile);
                $arrayToReturn     = $this->setStandardizedFeedbackArray([
                    'Response_Index'      => pathinfo($arrayArchiveParam['FileName'])['filename'],
                    'Size'                => $strFileStats['size'],
                    'FileDateTime'        => date('Y-m-d H:i:s', $strFileStats['mtime']),
                    'Matches'             => $matches,
                    'strArchivedFileName' => $strArchivedFile,
                    'strInvoiceContent'   => $strInvoiceContent,
                    'Response_Size'       => $arrayArchiveParam['Response_Size'],
                ]);
            } elseif ($matches2 === []) {
                echo vsprintf('<div>' . $this->arrayConfiguration['Feedback']['DifferentFile'] . '</div>', [
                    $strArchivedFile,
                    $strFile->getBasename(),
                ]);
            }
        }
        return $arrayToReturn;
    }

    private function setArchiveFromAnaf(string $strFile, int $intFileSize)
    {
        $arrayToReturn = [];
        $classZip      = new \ZipArchive();
        $res           = $classZip->open($strFile, \ZipArchive::RDONLY);
        if ($res) {
            $intFilesArchived = $classZip->numFiles;
            $arrayToReturn    = $this->handleArchiveContent($classZip, [
                'No_of_Files'   => $intFilesArchived,
                'FileName'      => $strFile,
                'Response_Size' => $intFileSize,
            ]);
        } else {
            // @codeCoverageIgnoreStart
            $arrayToReturn = $this->setStandardizedFeedbackArray([
                'Response_Index' => pathinfo($strFile)['filename'],
                'Error'          => $this->translation->find(null, 'i18n_Msg_InvalidZip')->getTranslation(),
                'Response_Size'  => 0,
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

    private function setDefaultsToInvoiceDetailsArray(array $arrayData): array
    {
        return [
            'Response_DateTime'    => '',
            'Response_Index'  => $arrayData['Response_Index'],
            'Response_Size'   => $arrayData['Response_Size'],
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
    }

    private function setErrorsFromExtendedMarkupLaguage(array $arrayData, string $strErrorTag): array
    {
        $arrayErrors = [];
        $parser      = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $arrayData['strInvoiceContent'], $arrayErrors);
        xml_parser_free($parser);
        return [
            'Loading_Index' => $arrayErrors[0]['attributes']['Index_incarcare'],
            'Size'          => $arrayData['Size'],
            'Response_DateTime' => $arrayData['FileDateTime'],
            'Supplier_CUI'  => 'RO' . $arrayErrors[0]['attributes']['Cif_emitent'],
            'Supplier_Name' => '??????????',
            'Error'         => sprintf($strErrorTag, $arrayErrors[1]['attributes']['errorMessage']),
        ];
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
        $arrayToReturn = $this->setDefaultsToInvoiceDetailsArray($arrayData);
        $strErrorTag   = '<div style="max-width:200px;font-size:0.8rem;">%s</div>';
        if (array_key_exists('Error', $arrayData)) {
            $arrayToReturn['Error'] = sprintf($strErrorTag, $arrayData['Error']);
            $arrayToReturn['Size']  = 0;
        } elseif ($arrayData['Size'] > 1000) {
            $strTimeZone   = $this->translation->find(null, 'i18n_TimeZone')->getTranslation();
            $strFormatter  = new \IntlDateFormatter(
                $_GET['language_COUNTRY'],
                \IntlDateFormatter::FULL,
                \IntlDateFormatter::FULL,
                $strTimeZone,
                \IntlDateFormatter::GREGORIAN,
                'r-MM__MMMM'
            );
            $arrayAttr     = $this->getDocumentDetails($arrayData);
            $arrayTemp     = [
                'Loading_Index'   => substr($arrayData['Matches'][0][0], 0, -4),
                'Size'            => $arrayData['Size'],
                'Document_No'     => $arrayAttr['ID'],
                'Issue_Date'      => $arrayAttr['IssueDate'],
                'Issue_YearMonth' => $strFormatter->format(new \DateTime($arrayAttr['IssueDate'])),
                'Response_DateTime'    => $arrayData['FileDateTime'],
                'Amount_wo_VAT'   => $arrayAttr['wo_VAT'],
                'Amount_TOTAL'    => $arrayAttr['TOTAL'],
                'Amount_VAT'      => round(($arrayAttr['TOTAL'] - $arrayAttr['wo_VAT']), 2),
                'Supplier_CUI'    => $this->setDataSupplierOrCustomer($arrayAttr['Supplier']),
                'Supplier_Name'   => $arrayAttr['Supplier']['PartyLegalEntity']['RegistrationName'],
                'Customer_CUI'    => $this->setDataSupplierOrCustomer($arrayAttr['Customer']),
                'Customer_Name'   => $arrayAttr['Customer']['PartyLegalEntity']['RegistrationName'],
                'No_of_Lines'     => $arrayAttr['No_of_Lines'],
                'Days_Between'         => $this->setDaysElapsed($arrayAttr['IssueDate'], $arrayData['FileDateTime']),
            ];
            $arrayToReturn = array_merge($arrayToReturn, $arrayTemp);
        } elseif ($arrayData['Size'] > 0) {
            $arrayTemp     = $this->setErrorsFromExtendedMarkupLaguage($arrayData, $strErrorTag);
            $arrayToReturn = array_merge($arrayToReturn, $arrayTemp);
        }
        return $arrayToReturn;
    }
}
