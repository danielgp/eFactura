<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(\danielgp\efactura\ElectronicInvoiceWrite::class)]
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
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . pathinfo($url, PATHINFO_FILENAME) . '.xml';
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => false,
        ]);
        $this->assertXmlFileEqualsXmlFile(self::LOCAL_UBL_EXAMPLES_PATH
            . 'Romanian/eInvoice_ex.xml', $strTargetFile);
    }

    public function testGetLocalJsonInvoiceIntoXmlWithDefaults()
    {
        // given file does not contain namespaces, as these elements are Optional and taken from default if not provided
        $url           = self::LOCAL_UBL_EXAMPLES_PATH . 'Romanian/Invoice_Data.json';
        $fileHandle    = fopen($url, 'r');
        $jsonData      = fread($fileHandle, ((int) filesize($url)));
        fclose($fileHandle);
        $arrayData     = json_decode($jsonData, true);
        $classWrite    = new \danielgp\efactura\ElectronicInvoiceWrite();
        $strTargetFile = __DIR__ . '/' . pathinfo($url, PATHINFO_FILENAME) . '_raw.xml';
        $classWrite->writeElectronicInvoice($strTargetFile, $arrayData, [
            'Comments'       => false,
            'SchemaLocation' => false,
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
