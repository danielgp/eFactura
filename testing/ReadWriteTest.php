<?php

declare(strict_types=1);

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\danielgp\efactura\ClassElectronicInvoiceRead::class)]
#[CoversClass(\danielgp\efactura\ClassElectronicInvoiceWrite::class)]
final class ReadWriteTest extends TestCase
{
    const LOCAL_UBL_EXAMPLES_PATH = __DIR__ . '/UBL_examples/';

    public function testGetLocalInvoiceIntoArrayAsCreditNote1()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'eInvoicing-EN16931/ubl-tc434-creditnote1.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => false,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalInvoiceIntoArrayAsExample1()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'eInvoicing-EN16931/ubl-tc434-example1.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalInvoiceIntoArrayAsExample2()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'eInvoicing-EN16931/ubl-tc434-example2.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalInvoiceIntoArrayAsExample3()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'eInvoicing-EN16931/ubl-tc434-example3.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalInvoiceIntoArrayAsExample4()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'eInvoicing-EN16931/ubl-tc434-example4.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalInvoiceIntoArrayAsExample5()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'eInvoicing-EN16931/ubl-tc434-example5.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalInvoiceIntoArrayAsExample6()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'eInvoicing-EN16931/ubl-tc434-example6.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalInvoiceIntoArrayAsExample7()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'eInvoicing-EN16931/ubl-tc434-example7.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalInvoiceIntoArrayAsExample8()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'eInvoicing-EN16931/ubl-tc434-example8.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalInvoiceIntoArrayAsExample9()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'eInvoicing-EN16931/ubl-tc434-example9.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalInvoiceIntoArrayAsExample9WithComments()
    {
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'eInvoicing-EN16931/ubl-tc434-example9.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . str_replace('.xml', '_withComments.xml', basename($url));
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => true,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalCreditNoteIntoArray()
    {
        $strFile       = self::LOCAL_UBL_EXAMPLES_PATH . 'Romanian/creditNote_ex.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($strFile);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($strFile);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'Ident'          => 2,
            'SchemaLocation' => false,
        ]);
        $this->assertXmlFileEqualsXmlFile($strFile, $strTargetFile);
    }

    public function testGetLocalInvoiceIntoArray()
    {
        $strFile       = self::LOCAL_UBL_EXAMPLES_PATH . 'Romanian/eInvoice_ex.xml';
        $classRead     = new \danielgp\efactura\ClassElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($strFile);
        $classWrite    = new \danielgp\efactura\ClassElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($strFile);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'Ident'          => 2,
            'SchemaLocation' => false,
        ]);
        $this->assertXmlFileEqualsXmlFile($strFile, $strTargetFile);
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
