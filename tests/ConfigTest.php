<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\Config as ClientConfig;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{

    public function testWithEvidenceReturnsCopyWithOnlyTheEvidenceChanged(): void
    {
        $config = new ClientConfig(
            'https://demo.flexibee.eu',
            'demo',
            'user',
            'pass',
            'adresar',
            verifySSLCertificate: true,
            authSessionId: 'session',
            debugMode: true,
        );

        $companyConfig = $config->withEvidence('c');

        Assert::assertEquals(
            new ClientConfig(
                'https://demo.flexibee.eu',
                'demo',
                'user',
                'pass',
                'c',
                verifySSLCertificate: true,
                authSessionId: 'session',
                debugMode: true,
            ),
            $companyConfig,
        );
        Assert::assertSame('adresar', $config->getEvidence());
    }

}
