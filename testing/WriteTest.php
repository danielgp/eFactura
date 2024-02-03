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
declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\danielgp\efactura\ClassElectronicInvoiceWrite::class)]
final class WriteTest extends TestCase
{
    const LOCAL_UBL_EXAMPLES_PATH = __DIR__ . '/UBL_examples/';

    public function testGetLocalJsonInvoiceIntoXml()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'Romanian/Invoice.json';
        $fileHandle    = fopen($url, 'r');
        $jsonData      = fread($fileHandle, ((int) filesize($url)));
        fclose($fileHandle);
        $arrayData     = json_decode($jsonData, true);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . pathinfo($url)['filename'] . '.xml';
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => false,
        ]);
        $this->assertXmlFileEqualsXmlFile(self::LOCAL_UBL_EXAMPLES_PATH
            . 'Romanian/eInvoice_ex.xml', $strTargetFile);
    }

    public function testGetLocalJsonInvoiceIntoXmlWithComments()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'Romanian/Invoice.json';
        $fileHandle    = fopen($url, 'r');
        $jsonData      = fread($fileHandle, ((int) filesize($url)));
        fclose($fileHandle);
        $arrayData     = json_decode($jsonData, true);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . pathinfo($url)['filename'] . '_with_Comments.xml';
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => true,
            'SchemaLocation' => false,
        ]);
        $this->assertXmlFileEqualsXmlFile(self::LOCAL_UBL_EXAMPLES_PATH
            . 'Romanian/eInvoice_ex.xml', $strTargetFile);
    }

    public function testGetLocalJsonInvoiceIntoXmlWithSchemaLocation()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'Romanian/Invoice.json';
        $fileHandle    = fopen($url, 'r');
        $jsonData      = fread($fileHandle, ((int) filesize($url)));
        fclose($fileHandle);
        $arrayData     = json_decode($jsonData, true);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . pathinfo($url)['filename'] . '_with_SchemaLocation.xml';
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileNotEqualsXmlFile(self::LOCAL_UBL_EXAMPLES_PATH
            . 'Romanian/eInvoice_ex.xml', $strTargetFile);
    }

    public function tearDown(): void
    {
        $arrayFiles = new RecursiveDirectoryIterator(__DIR__, FilesystemIterator::SKIP_DOTS);
        foreach ($arrayFiles as $strFile) {
            if ($strFile->isFile() && ($strFile->getExtension() === 'xml')) {
                unlink($strFile->getRealPath());
            }
        }
    }
}
