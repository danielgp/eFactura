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

trait traitVersions
{

    protected $arraySettings = [];

    private function establishCurrentVersion(array $arrayKnownVersions): array {
        $arrayVersionToReturn = [];
        foreach ($arrayKnownVersions as $value) {
            $dtValidityStart = new \DateTime($value['Validity']['Start']);
            $dtValidityEnd   = new \DateTime($value['Validity']['End']);
            $dtValidityNow   = new \DateTime();
            if (($dtValidityNow >= $dtValidityStart) && ($dtValidityNow <= $dtValidityEnd)) {
                $arrayVersionToReturn = [
                    'UBL'     => $value['UBL'],
                    'CIUS-RO' => $value['CIUS-RO'],
                ];
            }
        }
        return $arrayVersionToReturn;
    }

    private function getSettingsFromFileIntoMemory(): void {
        $strFileName = __DIR__ . DIRECTORY_SEPARATOR . 'eFactura.json';
        if (!file_exists($strFileName)) {
            throw new \RuntimeException(sprintf('File %s does not exists!', $strFileName));
        }
        $fileHandle = fopen($strFileName, 'r', 'read');
        if ($fileHandle === false) {
            throw new \RuntimeException(sprintf('Unable to open file %s for read purpose!', $strFileName));
        }
        $fileContent   = fread($fileHandle, ((int) filesize($strFileName)));
        fclose($fileHandle);
        $arrayToReturn = json_decode($fileContent, true);
        if (json_last_error() != JSON_ERROR_NONE) {
            throw new \RuntimeException(sprintf('Unable to interpret JSON from %s file...', $strFileName));
        }
        $this->arraySettings = $arrayToReturn;
    }
}
