<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\danielgp\efactura\ElectornicInvoiceRead::class)]
#[CoversClass(\danielgp\efactura\ElectornicInvoiceWrite::class)]
final class ReadWriteTest extends TestCase
{

    const REMOTE_UBL_EXAMPLES_PATH = 'https://raw.githubusercontent.com/ConnectingEurope/eInvoicing-EN16931/'
        . 'master/ubl/examples/';

    public function testReadRemoteXml()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example1.xml';
        $xmlContent = file_get_contents($url);
        $this->assertNotEmpty($xmlContent);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample1()
    {
        $url            = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example1.xml';
        $classRead      = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData      = $classRead->readElectronicInvoice($url);
        $classWrite     = new \danielgp\efactura\ElectornicInvoiceWrite();
        $strFileToWrite = __DIR__ . '/restulWrite.xml';
        $classWrite->writeElectronicInvoice($strFileToWrite, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, $strFileToWrite);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample4()
    {
        $url            = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example4.xml';
        $classRead      = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData      = $classRead->readElectronicInvoice($url);
        $classWrite     = new \danielgp\efactura\ElectornicInvoiceWrite();
        $strFileToWrite = __DIR__ . '/restulWrite.xml';
        $classWrite->writeElectronicInvoice($strFileToWrite, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, $strFileToWrite);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample8()
    {
        $url            = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example8.xml';
        $classRead      = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData      = $classRead->readElectronicInvoice($url);
        $classWrite     = new \danielgp\efactura\ElectornicInvoiceWrite();
        $strFileToWrite = __DIR__ . '/restulWrite.xml';
        $classWrite->writeElectronicInvoice($strFileToWrite, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, $strFileToWrite);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample9()
    {
        $url            = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example9.xml';
        $classRead      = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData      = $classRead->readElectronicInvoice($url);
        $classWrite     = new \danielgp\efactura\ElectornicInvoiceWrite();
        $strFileToWrite = __DIR__ . '/restulWrite.xml';
        $classWrite->writeElectronicInvoice($strFileToWrite, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, $strFileToWrite);
    }

    public function tearDown(): void
    {
        $strFileToWrite = __DIR__ . '/restulWrite.xml';
        unlink($strFileToWrite);
    }
}
