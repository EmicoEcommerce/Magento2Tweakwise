<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Etc;

use DOMDocument;
use Emico\CodeCept\Test\Unit;
use Magento\Framework\Config\Dom\UrnResolver;
use ReflectionClass;
use Tweakwise\Magento2Tweakwise\Model\Config;
use Tweakwise\Magento2Tweakwise\Model\Config\Backend\Timeout;

class OutageConfigXmlTest extends Unit
{
    public function testTimeoutFieldsAreValidAndUseThePositiveIntegerValidator(): void
    {
        $path = dirname((string) (new ReflectionClass(Config::class))->getFileName(), 2) . '/etc/adminhtml/system.xml';
        $document = new DOMDocument();
        $this->assertTrue($document->load($path));
        $schema = (new UrnResolver())->getRealPath('urn:magento:module:Magento_Config:etc/system_file.xsd');
        $this->assertTrue($document->schemaValidate($schema));

        $xml = simplexml_load_file($path);
        $this->assertNotFalse($xml);
        foreach (['timeout', 'connect_timeout'] as $field) {
            $nodes = $xml->xpath('/config/system/section[@id="tweakwise"]/group[@id="general"]/field[@id="' . $field . '"]');
            $this->assertCount(1, $nodes);
            $this->assertSame(Timeout::class, (string) $nodes[0]->backend_model);
        }
    }

    public function testTimeoutDefaultsPreserveTheRequestDeadlineAndBoundConnectionTime(): void
    {
        $path = dirname((string) (new ReflectionClass(Config::class))->getFileName(), 2) . '/etc/config.xml';
        $xml = simplexml_load_file($path);
        $this->assertNotFalse($xml);

        $this->assertSame('5', (string) $xml->default->tweakwise->general->timeout);
        $this->assertSame('1', (string) $xml->default->tweakwise->general->connect_timeout);
    }
}
