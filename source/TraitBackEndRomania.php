<?php

/*
 * Copyright (c) 2024 - 2025 Daniel Popiniuc.
 * All rights reserved. This program and the accompanying materials
 * are made available under the terms of the Eclipse Public License v1.0
 * which accompanies this distribution, and is available at
 * http://www.eclipse.org/legal/epl-v10.html
 *
 * Contributors:
 *    Daniel Popiniuc
 */

namespace danielgp\efactura;

/**
 * Configurable & Re-usable methods facilitating calls to known Romanian services for Electronic Invoice
 *
 * @author Daniel Popiniuc
 */
trait TraitBackEndRomania
{
    use \danielgp\io_operations\InputOutputCurl;
    use \danielgp\efactura\TraitBasic;

    private array $arraystandardMesageFilters    = [
        [
            'Code'   => 'E',
            'Detail' => 'raspuns cu erorile si semnatura MF, livrat in urma transmiterii unei facturi'
            . ', daca NU este validata de sistem',
            'Value'  => 'ERORI FACTURA',
        ],
        [
            'Code'   => 'P',
            'Detail' => 'factura primita in calitate de cunparator de la un emitent'
            . ' care a folosit sistemul national'
            . ' privind factura electronica RO e-factura',
            'Value'  => 'FACTURA PRIMITA',
        ],
        [
            'Code'   => 'T',
            'Detail' => 'raspuns cu factura originala si semnatura MF'
            . ', transmis in urma transmiterii unei facturi, daca este validata de sistem',
            'Value'  => 'FACTURA TRIMISA',
        ],
        [
            'Code'   => 'R',
            'Detail' => 'mesaj de la cumparator catre emitentul facturii'
            . ' (încărcate în sistem prin serviciul de upload ca RASP)',
            'Value'  => 'MESAJ CUMPARATOR PRIMIT / MESAJ CUMPARATOR TRANSMIS',
        ],
    ];
    // below variable has to be initialized by class consuming this Trait
    protected array $arraySolutionCustomSettings = [
        'ArrayElectronicInvoiceSecrets'      => [
            'Client_ID'     => '',
            'Client_Secret' => '',
            'Token'         => '',
        ],
        'ArrayFolders'                       => [
            'Generated XML'  => '',
            'Uploaded XML'   => '',
            'Downloaded ZIP' => '',
        ],
        'ArrayStrategyForUpload'             => [
            'B2B' => 'File base name end with|__b2b.xml',
            'B2C' => 'File base name end with|__b2c.xml',
            'B2G' => 'File base name end with|__b2g.xml',
        ],
        'IntegerNumberOfDaysToCheckMessages' => 0,
        'IntegerTaxIdentificationNumber'     => 0,
        'StringElectronicInvoiceEnvironment' => '',
        'StringElectronicInvoiceStandard'    => 'UBL',
        'StringOptionalUploadParameters'     => '', // could be one of the following: extern=DA | autofactura=DA | executare=DA
    ];

    private function authorizeConnectionToLegalAuthority(): void
    {
        $this->checkPrerequisite([
            'Empty Secrets Array' => $this->arraySolutionCustomSettings['ArrayElectronicInvoiceSecrets'],
        ]);
        $strRelevantUrl = $this->arraySettings['Infrastructure']['RO']['Servers']['Login']
            . $this->arraySettings['Infrastructure']['RO']['Calls']['Login']['Authorisation']
            . '?' . implode('&', [
                'client_id=' . $this->arraySolutionCustomSettings['ArrayElectronicInvoiceSecrets']['Client_ID'],
                'client_secret=' . $this->arraySolutionCustomSettings['ArrayElectronicInvoiceSecrets']['Client_Secret'],
                'response_type=' . 'code',
                'redirect_uri=' . $this->arraySettings['Infrastructure']['RO']['Servers']['Redirect']
                . $this->arraySettings['Infrastructure']['RO']['Calls']['Redirect'],
        ]);
        header('Location: ' . $strRelevantUrl);
    }

    private function buildHeaderAsArray(): array
    {
        return [
            'Authorization: Bearer ' . $this->arraySolutionCustomSettings['ArrayElectronicInvoiceSecrets']['Token'],
            'Content-Type: text/plain',
        ];
    }

    private function checkPrerequisite(array $arrayAssignedChecks): void
    {
        $arrayErrorMessages = [];
        foreach ($arrayAssignedChecks as $strLabelCheck => $elementChecked) {
            switch($strLabelCheck) {
                case 'Empty Environment':
                    if ($elementChecked == '') {
                        $arrayErrorMessages[] = 'Environment is NOT allowed to be empty'
                            . ', please ensure proper environment from your custom class...';
                    }
                    break;
                case 'Empty Secrets Array':
                    if ($elementChecked == []) {
                        $arrayErrorMessages[] = 'Secrets array is NOT allowed to be empty'
                            . ', please ensure proper building from your custom class...';
                    }
                    break;
                case 'Message Filter Value':
                    $arrayAllowedValues = array_column($this->arraystandardMesageFilters, 'Value');
                    if (($elementChecked !== '') && !in_array($elementChecked, $arrayAllowedValues)) {
                        $arrayErrorMessages[] = vsprintf('Message Filter provided has a value of %s that is not allowed'
                            . ' as can only be one of the following: %s'
                            . ', please ensure proper value is given from your custom class...', [
                            $elementChecked,
                            '"' . implode('" or "', array_keys($arrayAllowedValues)) . '"',
                        ]);
                    }
                    break;
                case 'Message Type Value':
                    $arrayAllowedValues = array_keys($this->arraySettings['Infrastructure']['RO']['Calls']['Content']['Message']);
                    if (!in_array($elementChecked, $arrayAllowedValues)) {
                        $arrayErrorMessages[] = vsprintf('Message Type provided has a value of %s that is not allowed'
                            . ' as can only be one of the following: %s'
                            . ', please ensure proper value is given from your custom class...', [
                            $elementChecked,
                            '"' . implode('" or "', array_keys($arrayAllowedValues)) . '"',
                        ]);
                    }
                    break;
                case 'Non-Standard Environment Value':
                    $arrayAllowedValues = ['prod', 'test'];
                    if (($elementChecked !== '') && !in_array($elementChecked, $arrayAllowedValues)) {
                        $arrayErrorMessages[] = vsprintf('Environment provided has a value of %s that is not allowed'
                            . ' as can only be one of the following: %s'
                            . ', please ensure proper value is given from your custom class...', [
                            $elementChecked,
                            '"' . implode('" or "', $arrayAllowedValues) . '"',
                        ]);
                    }
                    break;
                case 'Zero Value':
                    if ($elementChecked === 0) {
                        $arrayErrorMessages[] = 'Tax Identification Number is not allowed to be 0'
                            . ', please ensure you pass proper value from your custom class...';
                    }
                    break;
            }
        }
        if ($arrayErrorMessages != []) {
            error_log(implode(PHP_EOL, $arrayErrorMessages));
            throw new \RuntimeException(implode(PHP_EOL, $arrayErrorMessages));
        }
    }

    private function decideRelevantUniformResourceLocatorForUpload(string $strFileName): string
    {
        $strLabel        = $this->setLabelBasedOnRule($strFileName);
        error_log(sprintf('For given file %s following Label %s has been established!', $strFileName, $strLabel));
        $strUrlForUpload = $this->arraySettings['Infrastructure']['RO']['Calls']['Content']['Upload'][$strLabel];
        return vsprintf($strUrlForUpload, [
            $this->arraySolutionCustomSettings['StringElectronicInvoiceStandard'],
            $this->arraySolutionCustomSettings['IntegerTaxIdentificationNumber'],
            $this->arraySolutionCustomSettings['StringOptionalUploadParameters'],
        ]);
    }

    private function exposeFeedbackOnFileProcessed(int $intFileFoundNo, int $intFileProcessed): void
    {
        if ($intFileFoundNo === 0) {
            error_log('NO files were found to be processed.');
        } elseif ($intFileFoundNo == $intFileProcessed) {
            error_log(sprintf('All %u files found were processed successfully!', $intFileFoundNo));
        } else {
            error_log(vsprintf('Out of %u files found only %u were processed successfully, hence %u were missed.'
                    . ' Check your source folder where residual files are still there.', [
                $intFileFoundNo,
                $intFileProcessed,
                ($intFileFoundNo - $intFileProcessed),
            ]));
        }
    }

    /**
     * Work In Progress =================> UNIFNISHED !!!
     *
     * @param string $strType
     * @param array $arrayParameters
     * @return void
     */
    protected function getElectronicInvoiceMessages(string $strType, array $arrayParameters): void
    {
        $this->getServerDiscutionValidations([
            'Message Type Value', $strType,
            'Message Parameters' => $arrayParameters,
        ]);
        $this->getServerMessageParametersValidation($strType, $arrayParameters);
        $this->authorizeConnectionToLegalAuthority();
        $strContentBranch          = '';
        $arrayFinalParameterValues = [];
        $strFilter                 = '';
        switch($strType) {
            case 'ListAll':
                $strContentBranch            = 'ListAllMessages';
                $arrayFinalParameterValues[] = $arrayParameters['Days'];
                $arrayFinalParameterValues[] = $this->arraySolutionCustomSettings['IntegerTaxIdentificationNumber'];
                $arrayFinalParameterValues[] = $this->getMessageFilterCode($strFilter);
                break;
            case 'ListSinglePage':
                $strContentBranch            = 'ListMessagesPaged';
                $arrayFinalParameterValues[] = $arrayParameters['StartTime'];
                $arrayFinalParameterValues[] = $arrayParameters['EndTime'];
                $arrayFinalParameterValues[] = $arrayParameters['Page'];
                $arrayFinalParameterValues[] = $this->getMessageFilterCode($strFilter);
                break;
            case 'Single':
                $strContentBranch            = 'SingleMessageStatus';
                $arrayFinalParameterValues[] = $arrayParameters['LoadingId'];
                break;
        }
        $strRelevantUrl = $this->arraySettings['Infrastructure']['RO']['Servers']['Content']['OAuth2']
            . $this->arraySolutionCustomSettings['StringElectronicInvoiceEnvironment']
            . vsprintf($this->arraySettings['Infrastructure']['RO']['Calls']['Content'][$strContentBranch], $arrayFinalParameterValues);
        error_log(vsprintf('Relevant URL for Message reading of type %s is %s', [
            $strType,
            $strRelevantUrl,
        ]));
        // sent XML content using CURL and capture feedback
        $arrayFeedback  = $this->getContentFromUrlThroughCurl($strRelevantUrl, [
            'HttpHeader' => $this->buildHeaderAsArray(),
        ]);
        if ($arrayFeedback['errNo'] === 0) {
            if (json_validate($arrayFeedback['response'])) {
                $arrayFeedback['response'] = json_decode($arrayFeedback['response'], true, 512, \JSON_OBJECT_AS_ARRAY);
                $intMessageNo              = 0;
                error_log('Response = ' . json_encode($arrayFeedback['response']));
                if (array_key_exists('eroare', $arrayFeedback['response'])) {
                    error_log('Error response = ' . $arrayFeedback['response']['eroare']);
                } else {
                    foreach ($arrayFeedback['response']['mesaje'] as $arrayMessageDetails) {
                        $strTargetFile = $this->arraySolutionCustomSettings['ArrayFolders']['Downloaded ZIP']
                            . strval($arrayMessageDetails['id']) . '.zip';
                        if (!file_exists($strTargetFile)) {
                            $this->getElectronicInvoiceResponseFile($arrayMessageDetails['id'], $strTargetFile);
                        }
                        error_log('Current message is ' . json_encode($arrayMessageDetails));
                        $intMessageNo++;
                    }
                    error_log(sprintf('%u messages were processed!', strval($intMessageNo)));
                }
            } else {
                error_log('Response is expected to be a JSON but is not...');
                error_log(json_encode($arrayFeedback));
            }
        } else {
            error_log($arrayFeedback['errNo'] . ' => ' . $arrayFeedback['errMsg']);
        }
    }

    protected function getElectronicInvoiceResponseFile(int $intResponseId, string $strTargetFile): void
    {
        error_log(sprintf('Will download the response ZIP file having ID = %s', $intResponseId));
        $strRelevantUrl = vsprintf($this->arraySettings['Infrastructure']['RO']['Servers']['Content']['OAuth2']
            . $this->arraySolutionCustomSettings['StringElectronicInvoiceEnvironment']
            . $this->arraySettings['Infrastructure']['RO']['Calls']['Content']['Download'], [
            $intResponseId,
        ]);
        error_log(vsprintf('Relevant URL for Downloading Response ZIP file havign ID = %s is %s', [
            strval($intResponseId),
            $strRelevantUrl,
        ]));
        // sent XML content using CURL and capture feedback
        $datafile       = $this->getContentFromUrlThroughCurl($strRelevantUrl, [
            'HttpHeader' => $this->buildHeaderAsArray(),
        ]);
        $ffileHandler   = fopen($strTargetFile, 'w');
        fwrite($ffileHandler, $datafile['response']);
        fclose($ffileHandler);
        if (file_exists($strTargetFile)) {
            error_log(vsprintf('Response ZIP file having ID = %s was sucessfully saved to %s', [
                strval($intResponseId),
                $strTargetFile,
            ]));
        }
    }

    private function getMessageFilterCode(string $strFilter): string
    {
        $strReturn = '';
        if ($strFilter != '') {
            $strReturn = '&filtru=' . array_column($this->arraystandardMesageFilters, 'Code', 'Value')[$strFilter];
        }
        return $strReturn;
    }

    /**
     * implementing strong validations to avoid issues later in the logic
     *
     * @param array $arrayAdditionalValidations
     */
    private function getServerDiscutionValidations(array $arrayAdditionalValidations = []): void
    {
        $arrayUniversalValidations = [
            'Zero Value'                     => $this->arraySolutionCustomSettings['IntegerTaxIdentificationNumber'],
            'Empty Environment'              => $this->arraySolutionCustomSettings['StringElectronicInvoiceEnvironment'],
            'Non-Standard Environment Value' => $this->arraySolutionCustomSettings['StringElectronicInvoiceEnvironment'],
        ];
        $arrayValidations          = $arrayUniversalValidations;
        if ($arrayAdditionalValidations != []) {
            $arrayValidations = array_merge($arrayValidations, $arrayAdditionalValidations);
        }
        $this->checkPrerequisite($arrayValidations);
    }

    private function getServerMessageParametersValidation(string $strType, array $arrayParameters): void
    {
        $arrayAllowedParameters = match ($strType) {
            'ListAll'        => [
                'Mandatory' => ['Days'],
                'Optional'  => ['Filter'],
            ],
            'ListSinglePage' => [
                'Mandatory' => ['StartTime', 'EndTime', 'Page'],
                'Optional'  => ['Filter'],
            ],
            'Single'         => [
                'Mandatory' => ['LoadingId'],
            ],
        };
        $arrayAllowedCombined   = $arrayAllowedParameters['Mandatory'];
        if (array_key_exists('Optional', $arrayAllowedParameters)) {
            $arrayAllowedCombined = array_merge($arrayAllowedParameters['Mandatory'], $arrayAllowedParameters['Optional']);
        }
        $arrayGivenKeys = array_keys($arrayParameters);
        $arrayErrors    = [];
        if (array_diff($arrayAllowedParameters['Mandatory'], $arrayGivenKeys) != []) {
            $arrayErrors[] = vsprintf('Provided parameters %s does contain all mandatory ones: %s...', [
                json_encode($arrayGivenKeys),
                json_encode($arrayAllowedParameters['Mandatory']),
            ]);
        } elseif (($arrayGivenKeys != $arrayAllowedParameters['Mandatory']) && (array_diff($arrayAllowedCombined, $arrayGivenKeys) != [])) {
            $arrayErrors[] = vsprintf('Provided parameters %s does contain all mandatory & optional ones: %s...', [
                json_encode($arrayGivenKeys),
                json_encode($arrayAllowedCombined),
            ]);
        } else {
            // here we have to validate actual value passed as parameters
            foreach ($arrayParameters as $strParameterKey => $strParameterValue) {
                switch($strParameterKey) {
                    case 'Days':
                    case 'LoadingId':
                    case 'Page':
                        $arrayRangeAllowedPieces = explode('-', match ($strParameterKey) {
                            'Days'      => '1-60',
                            'LoadingId' => '1-' . strval(9 * pow(10, 10)),
                            'Page'      => '0-' . strval(9 * pow(10, 6)),
                        });
                        $regs                    = null;
                        preg_match('/[0-9]{1,20}/', $strParameterValue, $regs, PREG_UNMATCHED_AS_NULL);
                        if (is_array($regs) && ($regs !== []) && ($regs[0] != $strParameterValue)) {
                            $arrayErrors[] = vsprintf('Parameter "%s" is expected to be of integer type'
                                . ' but something else is given "%s"...', [
                                $strParameterKey,
                                json_encode($strParameterValue),
                            ]);
                        } elseif (($strParameterValue < $arrayRangeAllowedPieces[0]) || ($strParameterValue > $arrayRangeAllowedPieces[1])) {
                            $arrayErrors[] = vsprintf('Parameter "%s" is an integer value'
                                . ' and within range between 1 and 60 but %s is given...', [
                                $strParameterKey,
                                $strParameterValue,
                            ]);
                        }
                        break;
                    case 'Filter':
                        $arrayAllowedValues = array_column($this->arraystandardMesageFilters, 'Value');
                        if (($strParameterValue !== '') && !in_array($strParameterValue, $arrayAllowedValues)) {
                            $arrayErrors[] = vsprintf('Message Filter provided has a value of %s'
                                . ' that is not allowed'
                                . ' as can only be one of the following: %s'
                                . ', please ensure proper value is given from your custom class...', [
                                $strParameterValue,
                                '"' . implode('" or "', array_keys($arrayAllowedValues)) . '"',
                            ]);
                        }
                        break;
                    case 'StartTime':
                    case 'EndTime':
                        $arrayRangeAllowedPieces          = explode('|', strval((\DateTime::createFromFormat('Y-m-d G:i:s', '2021-01-01 00:00:00', new \DateTimeZone('UTC')))->format('U') * 1000)
                            . '|' . strval((new \DateTime('now', new \DateTimeZone('UTC')))->format('U') * 1000));
                        $arrayRangeAllowedPiecesForHumans = explode('|', (\DateTime::createFromFormat('U', intval(($arrayRangeAllowedPieces[0] / 1000)), new \DateTimeZone('UTC')))->format('Y-m-d H:i:s')
                            . '|' . (\DateTime::createFromFormat('U', intval(($arrayRangeAllowedPieces[1] / 1000)), new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'));
                        $arrayErrors                      = [];
                        if (is_integer($strParameterValue) && (($strParameterValue < $arrayRangeAllowedPieces[0]) || ($strParameterValue > $arrayRangeAllowedPieces[1]))) {
                            $arrayErrors[] = vsprintf('Parameter "%s" is given as an integer value %s (which translate in %s) '
                                . ' but that is NOT within allowed range, which is between %s and %s...', [
                                $strParameterKey,
                                $strParameterValue,
                                (\DateTime::createFromFormat('U', intval(($strParameterValue / 1000)), new \DateTimeZone('UTC')))->format('Y-m-d H:i:s'),
                                $arrayRangeAllowedPiecesForHumans[0],
                                $arrayRangeAllowedPiecesForHumans[1],
                            ]);
                        } elseif (is_string($strParameterValue) && (strlen($strParameterValue) == 19)) {
                            $regs = null;
                            preg_match('/(1|2)[0-9]{3}\-((01|03|05|07|08|10|12)\-(0{1}[1-9]{1}|[1-2]{1}[0-9]{1}|3[0-1]{1})|(04|06|09|11)\-(0{1}[1-9]{1}|[1-2]{1}[0-9]{1}|30)|02\-[0-1-2]{1}[0-9]{1})\s(0[0-9]{1}|1[0-9]{1}|2[0-3]{1})\:[0-5]{1}[0-9]{1}\:[0-5]{1}[0-9]{1}/m', $strParameterValue, $regs, PREG_UNMATCHED_AS_NULL);
                            if (is_array($regs) && ($regs !== []) && ($regs[0] == $strParameterValue)) {
                                $intValueToCompare = (\DateTime::createFromFormat('Y-m-d G:i:s', $strParameterValue, new \DateTimeZone('UTC')))->format('U') * 1000;
                                if (($intValueToCompare < $arrayRangeAllowedPieces[0]) || ($intValueToCompare > $arrayRangeAllowedPieces[1])) {
                                    $arrayErrors[] = vsprintf('Parameter "%s" is given as a TimeStamp value of %s '
                                        . ' but that is NOT within allowed range, which is between %s and %s...', [
                                        $strParameterKey,
                                        $strParameterValue,
                                        $arrayRangeAllowedPiecesForHumans[0],
                                        $arrayRangeAllowedPiecesForHumans[1],
                                    ]);
                                }
                            }
                        }
                        break;
                }
            }
        }
        if ($arrayErrors != []) {
            error_log(implode(PHP_EOL, $arrayErrors));
            throw new \RuntimeException(implode(PHP_EOL, $arrayErrors));
        }
    }

    private function setLabelBasedOnRule(string $strFileName): string
    {
        $arrayKnownLabels = ['B2B', 'B2C', 'B2G'];
        $arrayKnownRules  = [
            'File base name end with',
            'File base name starts with',
        ];
        $strLabel         = '';
        foreach ($arrayKnownLabels as $strCurrentLabel) {
            $arrayPieces = explode('|', $this->arraySolutionCustomSettings['ArrayStrategyForUpload'][$strCurrentLabel]);
            switch($arrayPieces[0]) {
                case 'File base name end with':
                    if (str_ends_with($strFileName, $arrayPieces[1])) {
                        $strLabel = $strCurrentLabel;
                    }
                    break;
                case 'File base name starts with':
                    if (str_starts_with($strFileName, $arrayPieces[1])) {
                        $strLabel = $strCurrentLabel;
                    }
                    break;
                default:
                    throw \RuntimeException('Unknown/missconfigured rule given'
                            . ', please ensure only proper rules ('
                            . 'that is one of these values "' . implode('", "', $arrayKnownRules) . '"'
                            . ') are configured in your custom class...');
                    break;
            }
        }
        return $strLabel;
    }

    protected function uploadElectronicInvoicesFromFolder(): void
    {
        $this->getServerDiscutionValidations();
        $arrayHttpHeader  = $this->buildHeaderAsArray();
        $intFileFoundNo   = 0;
        $intFileProcessed = 0;
        $arrayFiles       = new \RecursiveDirectoryIterator($this->arraySolutionCustomSettings['ArrayFolders']['Generated XML'], \FilesystemIterator::SKIP_DOTS);
        foreach ($arrayFiles as $strCrtFile) {
            if ($strCrtFile->isFile()) { // only Files are relevant for processing
                if ($intFileFoundNo === 0) { // authorization is only needed for 1st file
                    $this->authorizeConnectionToLegalAuthority();
                }
                $strRawUrl             = $this->decideRelevantUniformResourceLocatorForUpload($strCrtFile->getRealPath());
                $strRelevantUrl        = $this->arraySettings['Infrastructure']['RO']['Servers']['Content']['OAuth2']
                    . $this->arraySolutionCustomSettings['StringElectronicInvoiceEnvironment']
                    . $strRawUrl;
                error_log(vsprintf('I will be uploading content of %s file to %s url', [
                    $strCrtFile->getRealPath(),
                    $strRelevantUrl,
                ]));
                $xmlCurrentFileContent = $this->getFileJsonContent($strCrtFile->getPath(), $strCrtFile->getRealPath());
                // sent XML content using CURL and capture feedback
                $arrayFeedback         = $this->getContentFromUrlThroughCurl($strRelevantUrl, [
                    'HttpHeader' => $arrayHttpHeader,
                    'PostFields' => $xmlCurrentFileContent,
                ]);
                error_log(json_encode($arrayFeedback));
                if ($arrayFeedback['errNo'] === 0) {
                    $arrayResponse = json_decode(json_encode(simplexml_load_string($arrayFeedback['response'])), true, 512, \JSON_OBJECT_AS_ARRAY);
                    error_log('Complete content of response is: ' . json_encode($arrayResponse));
                    error_log(vsprintf('File %s has been sucessfully loaded with %u index', [
                        $strCrtFile->getRealPath(),
                        $arrayResponse['@attributes']['index_incarcare'],
                    ]));
                    // if response has no error move the file to processed folder
                    rename($strCrtFile->getRealPath(), $this->arraySolutionCustomSettings['ArrayFolders']['Uploaded XML']
                        . $strCrtFile->getBasename());
                    $intFileProcessed++;
                }
                $intFileFoundNo++;
            }
        }
        $this->exposeFeedbackOnFileProcessed($intFileFoundNo, $intFileProcessed);
    }
}
