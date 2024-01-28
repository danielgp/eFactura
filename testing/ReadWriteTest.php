<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\danielgp\efactura\ElectronicInvoiceRead::class)]
#[CoversClass(\danielgp\efactura\ElectronicInvoiceWrite::class)]
final class ReadWriteTest extends TestCase
{

    const REMOTE_UBL_EXAMPLES_PATH = 'https://raw.githubusercontent.com/ConnectingEurope/eInvoicing-EN16931/'
        . 'master/ubl/examples/';
    const LOCAL_RESULT_FILE        = __DIR__ . '/resultWrite.xml';

    public function testReadRemoteXml()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example1.xml';
        $xmlContent = file_get_contents($url);
        $this->assertNotEmpty($xmlContent);
    }

    public function testGetRemoteInvoiceIntoArrayAsCreditNote1()
    {
        $url           = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-creditnote1.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => false,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample1()
    {
        $url           = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example1.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample2()
    {
        $url           = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example2.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample3()
    {
        $url           = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example3.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample4()
    {
        $url           = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example4.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample5()
    {
        $url           = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example5.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample6()
    {
        $url           = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example6.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample7()
    {
        $url           = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example7.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample8()
    {
        $url           = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example8.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample9()
    {
        $url           = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example9.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . basename($url);
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample9WithComments()
    {
        $url           = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example9.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($url);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . str_replace('.xml', '_withComments.xml', basename($url));
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => true,
            'SchemaLocation' => true,
        ]);
        $this->assertXmlFileEqualsXmlFile($url, $strTargetFile);
    }

    public function testGetLocalCreditNoteIntoArray()
    {
        $strFile       = __DIR__ . '/Romanian/creditNote_ex.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($strFile);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
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
        $strFile       = __DIR__ . '/Romanian/eInvoice_ex.xml';
        $classRead     = new \danielgp\efactura\ElectronicInvoiceRead();
        $arrayData     = $classRead->readElectronicInvoice($strFile);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
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
