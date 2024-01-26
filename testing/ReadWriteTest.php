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
    const LOCAL_RESULT_FILE        = __DIR__ . '/resultWrite.xml';

    public function testReadRemoteXml()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example1.xml';
        $xmlContent = file_get_contents($url);
        $this->assertNotEmpty($xmlContent);
    }

    public function testGetRemoteInvoiceIntoArrayAsCreditNote1()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-creditnote1.xml';
        $classRead  = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData  = $classRead->readElectronicInvoice($url);
        $classWrite = new \danielgp\efactura\ElectornicInvoiceWrite();
        $classWrite->writeElectronicInvoice(self::LOCAL_RESULT_FILE, $arrayData, false, false);
        $this->assertXmlFileEqualsXmlFile($url, self::LOCAL_RESULT_FILE);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample1()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example1.xml';
        $classRead  = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData  = $classRead->readElectronicInvoice($url);
        $classWrite = new \danielgp\efactura\ElectornicInvoiceWrite();
        $classWrite->writeElectronicInvoice(self::LOCAL_RESULT_FILE, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, self::LOCAL_RESULT_FILE);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample2()
    {
        $this->markTestSkipped('Full logic not yest implemented... WIP');
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example2.xml';
        $classRead  = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData  = $classRead->readElectronicInvoice($url);
        $classWrite = new \danielgp\efactura\ElectornicInvoiceWrite();
        $classWrite->writeElectronicInvoice(self::LOCAL_RESULT_FILE, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, self::LOCAL_RESULT_FILE);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample3()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example3.xml';
        $classRead  = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData  = $classRead->readElectronicInvoice($url);
        $classWrite = new \danielgp\efactura\ElectornicInvoiceWrite();
        $classWrite->writeElectronicInvoice(self::LOCAL_RESULT_FILE, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, self::LOCAL_RESULT_FILE);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample4()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example4.xml';
        $classRead  = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData  = $classRead->readElectronicInvoice($url);
        $classWrite = new \danielgp\efactura\ElectornicInvoiceWrite();
        $classWrite->writeElectronicInvoice(self::LOCAL_RESULT_FILE, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, self::LOCAL_RESULT_FILE);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample6()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example6.xml';
        $classRead  = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData  = $classRead->readElectronicInvoice($url);
        $classWrite = new \danielgp\efactura\ElectornicInvoiceWrite();
        $classWrite->writeElectronicInvoice(self::LOCAL_RESULT_FILE, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, self::LOCAL_RESULT_FILE);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample7()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example7.xml';
        $classRead  = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData  = $classRead->readElectronicInvoice($url);
        $classWrite = new \danielgp\efactura\ElectornicInvoiceWrite();
        $classWrite->writeElectronicInvoice(self::LOCAL_RESULT_FILE, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, self::LOCAL_RESULT_FILE);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample8()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example8.xml';
        $classRead  = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData  = $classRead->readElectronicInvoice($url);
        $classWrite = new \danielgp\efactura\ElectornicInvoiceWrite();
        $classWrite->writeElectronicInvoice(self::LOCAL_RESULT_FILE, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, self::LOCAL_RESULT_FILE);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample9()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example9.xml';
        $classRead  = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData  = $classRead->readElectronicInvoice($url);
        $classWrite = new \danielgp\efactura\ElectornicInvoiceWrite();
        $classWrite->writeElectronicInvoice(self::LOCAL_RESULT_FILE, $arrayData, false, true);
        $this->assertXmlFileEqualsXmlFile($url, self::LOCAL_RESULT_FILE);
    }

    public function testGetRemoteInvoiceIntoArrayAsExample9WithOutComments()
    {
        $url        = self::REMOTE_UBL_EXAMPLES_PATH . 'ubl-tc434-example9.xml';
        $classRead  = new \danielgp\efactura\ElectornicInvoiceRead();
        $arrayData  = $classRead->readElectronicInvoice($url);
        $classWrite = new \danielgp\efactura\ElectornicInvoiceWrite();
        $classWrite->writeElectronicInvoice(self::LOCAL_RESULT_FILE, $arrayData, true, true);
        $this->assertXmlFileEqualsXmlFile($url, self::LOCAL_RESULT_FILE);
    }

    public function tearDown(): void
    {
        unlink(self::LOCAL_RESULT_FILE);
    }
}
