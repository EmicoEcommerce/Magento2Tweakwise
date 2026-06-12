<?php

declare(strict_types=1);

namespace Tweakwise\Test\Unit\Etc\Adminhtml;

use DOMDocument;
use DOMXPath;
use Emico\CodeCept\Test\Unit;
use Tweakwise\Test\Support\UnitTester;

class SystemConfigTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @return void
     */
    public function testGeneralGroupContainsInternalTrafficIpAddressesField(): void
    {
        $document = new DOMDocument();
        $document->load(__DIR__ . '/../../../../src/etc/adminhtml/system.xml');

        $xPath = new DOMXPath($document);
        $field = $xPath->query('/config/system/section[@id="tweakwise"]/group[@id="general"]/field[@id="internal_ip_addresses"]')->item(0);

        $this->assertNotNull($field);
        $this->assertSame('textarea', $field?->attributes?->getNamedItem('type')?->nodeValue);
        $this->assertSame('120', $field?->attributes?->getNamedItem('sortOrder')?->nodeValue);
        $this->assertSame(
            'Comma-separated list of IP addresses. Requests from these IPs will be tagged with TWN-Source: Internal-Traffic in Tweakwise API calls, allowing you to filter them from Tweakwise Analytics reports.',
            trim((string) $xPath->query('./comment', $field)->item(0)?->textContent)
        );
    }
}
