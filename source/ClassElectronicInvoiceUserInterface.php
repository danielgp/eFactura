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

class ClassElectronicInvoiceUserInterface
{
    use \danielgp\efactura\TraitUserInterfaceLogic;
    use \danielgp\efactura\TraitBackEndRomania;

    private \SebastianBergmann\Timer\Timer $classTimer;

    public function __construct()
    {
        $this->classTimer = new \SebastianBergmann\Timer\Timer();
        $this->classTimer->start();
        $this->getConfiguration();
        $this->setLocalization();
    }

    private function getButtonForLocalisation(string $strLanguageCountry): string
    {
        $arrayMapFlags  = [
            'ro_RO' => 'ro',
            'it_IT' => 'it',
            'en_US' => 'us',
        ];
        $arrayFlagSizes = ['20px', '15px'];
        if ($strLanguageCountry === filter_input(INPUT_GET, 'language_COUNTRY', FILTER_VALIDATE_REGEXP, [
                'options' => [
                    'regexp' => '/^(en_US|it_IT|ro_RO)$/'
                ]
            ])) {
            $arrayFlagSizes = ['40px', '30px'];
        }
        return vsprintf('<a href="?language_COUNTRY=%s" style="float:left;margin-left:10px;">'
            . '<span class="fi fi-%s" style="%s">&nbsp;</span>'
            . '</a>', [
            $strLanguageCountry . (array_key_exists('action', $_GET) ? '&action='
            . filter_input(INPUT_GET, 'action', FILTER_FLAG_ENCODE_LOW | FILTER_FLAG_ENCODE_HIGH) : ''),
            $arrayMapFlags[$strLanguageCountry],
            vsprintf('width:%s;height:%s;', $arrayFlagSizes),
        ]);
    }

    private function getButtonToActionSomething(array $arrayButtonFeatures): string
    {
        $arrayButtonStyle = [
            'font:bold 14pt Arial',
            'margin:2px',
            'padding:4px 10px',
        ];
        $arrayStylePieces = $arrayButtonStyle;
        if (array_key_exists('AdditionalStyle', $arrayButtonFeatures)) {
            $arrayStylePieces = array_merge($arrayButtonStyle, $arrayButtonFeatures['AdditionalStyle']);
        }
        return vsprintf('<a href="%s" class="btn btn-outline-primary" style="%s">%s</a>', [
            $arrayButtonFeatures['URL']
            . (array_key_exists('language_COUNTRY', $_GET) ? '&language_COUNTRY='
            . filter_input(INPUT_GET, 'language_COUNTRY', FILTER_VALIDATE_REGEXP, [
                'options' => [
                    'regexp' => '/^(en_US|it_IT|ro_RO)$/'
                ]
            ]) : ''),
            implode(';', $arrayStylePieces),
            $arrayButtonFeatures['Text'],
        ]);
    }

    private function getHeaderColumnMapping(array $arrayColumns): array
    {
        $arrayMap = [];
        foreach ($arrayColumns as $strColumnName) {
            $arrayMap[$strColumnName] = $strColumnName;
            $strRelevant              = $this->translation->find(null, 'i18n_Clmn_' . $strColumnName);
            if (!is_null($strRelevant)) {
                $arrayMap[$strColumnName] = $strRelevant->getTranslation();
            }
        }
        return $arrayMap;
    }

    public function setActionToDo(): void
    {
        echo '<main>';
        if (is_array($_GET) && array_key_exists('action', $_GET)) {
            $arrayOptions = [
                'action' => FILTER_SANITIZE_SPECIAL_CHARS,
            ];
            $arrayInputs  = filter_input_array(INPUT_GET, $arrayOptions, true);
            switch($arrayInputs['action']) {
                case 'AnalyzeZIPfromANAFfromLocalFolder':
                    echo '<div class="tabber" id="tabStandard">'
                    . '<div class="tabbertab" id="tab1" title="Filters">';
                    echo '</div><!-- tab1 -->';
                    echo '<div class="tabbertab" id="tab2" title="Lista">';
                    $strRelevantFolder = 'P:/e-Factura/ZIPs_from_ANAF/';
                    if (!file_exists($strRelevantFolder)) {
                        $strRelevantFolder = realpath('../../');
                    }
                    $arrayInvoices = $this->actionAnalyzeZIPfromANAFfromLocalFolder($strRelevantFolder);
                    if (count($arrayInvoices) === 0) {
                        echo sprintf('<p style="color:red;">'
                            . $this->translation->find(null, 'i18n_Msg_NoZip')->getTranslation()
                            . '</p>', $strRelevantFolder);
                    } else {
                        echo $this->setHtmlTable($arrayInvoices);
                    }
                    echo '</div><!-- tab2 -->'
                    . '</div><!-- tabStandard -->';
                    break;
                case 'checkAllMessages':
                    if (!array_key_exists('NumberOfDays', $_GET)) {
                        $_GET['NumberOfDays'] = 2;
                    }
                    $this->getElectronicInvoiceMessages('ListAll', [
                        'Days' => filter_input(INPUT_GET, 'NumberOfDays', FILTER_SANITIZE_NUMBER_INT),
                    ]);
                case 'checkSingleMessage':
                    if (!array_key_exists('NumberOfDays', $_GET)) {
                        $_GET['LoadingId'] = 0; // this is not real ID...
                    }
                    $this->getElectronicInvoiceMessages('Single', [
                        'LoadingId' => filter_input(INPUT_GET, 'LoadingId', FILTER_SANITIZE_NUMBER_INT),
                    ]);
                case 'uploadElectronicInvoicesFromFolderToRomanianAuthority':
                    $this->uploadElectronicInvoicesFromFolder();
                    break;
            }
        }
        echo '</main>';
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

    public function setHtmlTable(array $arrayData): string
    {
        $strReturn = '<table style="margin-left:auto;margin-right:auto;">';
        foreach ($arrayData as $intLineNo => $arrayContent) {
            ksort($arrayContent);
            if ($intLineNo === 0) {
                $strReturn .= $this->setHtmlTableHeader(array_keys($arrayContent))
                    . '<tbody>';
            }
            $strReturn .= $this->setHtmlTableLine(($intLineNo + 1), $arrayContent);
        }
        return ($strReturn . '</tbody>' . '</table>');
    }

    private function setHtmlTableHeader(array $arrayData): string
    {
        $arrayMap    = $this->getHeaderColumnMapping(array_values($arrayData));
        $strToReturn = '<th>#</th>';
        foreach ($arrayData as $key) {
            $strToReturn .= sprintf('<th>%s</th>', (array_key_exists($key, $arrayMap) ? $arrayMap[$key] : $key));
        }
        return '<thead><tr>' . $strToReturn . '</tr></thead>';
    }

    private function setHtmlTableLine(int $intLineNo, array $arrayLine): string
    {
        $arrayContent = [];
        foreach ($arrayLine as $strColumn => $strValue) {
            if (str_starts_with($strColumn, 'Amount_')) {
                $arrayContent[] = sprintf('<td style="text-align:right;">%s</td>', $this->setNumbers($strValue, 2, 2));
            } elseif (in_array($strColumn, ['No_of_Lines', 'Response_Size', 'Size'])) {
                $arrayContent[] = sprintf('<td style="text-align:right;">%s</td>', $this->setNumbers($strValue, 0, 0));
            } else {
                $arrayContent[] = sprintf('<td>%s</td>', $strValue);
            }
        }
        return '<tr' . ($arrayLine['Error'] === '' ? '' : ' style="color:red;"') . '>'
            . '<td>' . $intLineNo . '</td>'
            . implode('', $arrayContent)
            . '</tr>';
    }

    private function setNumbers(float $floatNumber, int $intMinDigits, int $intMaxDigits): string
    {
        $classFormat = new \NumberFormatter($_GET['language_COUNTRY'], \NumberFormatter::DECIMAL);
        $classFormat->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $intMinDigits);
        $classFormat->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $intMaxDigits);
        return $classFormat->format($floatNumber);
    }

    public function setUserInterface(): void
    {
        echo '<header class="border-bottom">'
        . $this->getButtonForLocalisation('en_US')
        . $this->getButtonForLocalisation('it_IT')
        . $this->getButtonForLocalisation('ro_RO')
        . $this->getButtonToActionSomething([
            'Text' => $this->translation->find(null, 'i18n_Btn_AnalyzeZIP')->getTranslation(),
            'URL'  => '?action=AnalyzeZIPfromANAFfromLocalFolder',
        ])
        . ' </header>';
    }
}
