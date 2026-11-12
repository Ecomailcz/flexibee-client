<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Http\CurlOptionsFactory;
use EcomailFlexibee\Http\Method;
use JsonException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;

final class CurlOptionsFactoryTest extends TestCase
{

    public function testKeepsTlsVerificationForSessionAuthentication(): void
    {
        $config = new Config(
            'https://demo.flexibee.eu',
            'demo',
            'user',
            'pass',
            'adresar',
            verifySSLCertificate: true,
            authSessionId: 'session-id',
            debugMode: false,
        );

        $options = (new CurlOptionsFactory())->create('https://demo.flexibee.eu/c/demo/adresar.json', Method::GET, [], [], [], $config);

        Assert::assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        Assert::assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
    }

    public function testWrapsJsonEncodingFailureIntoRequestFail(): void
    {
        $config = new Config('https://demo.flexibee.eu', 'demo', 'user', 'pass', 'adresar', verifySSLCertificate: true, debugMode: false);

        try {
            (new CurlOptionsFactory())->create('https://demo.flexibee.eu/c/demo/adresar.json', Method::PUT, ['nazev' => "\xB1\x31"], [], [], $config);
            Assert::fail('EcomailFlexibeeRequestFail was not thrown.');
        } catch (EcomailFlexibeeRequestFail $exception) {
            Assert::assertInstanceOf(JsonException::class, $exception->getPrevious());
        }
    }

    public function testRejectsAnEmptyUrl(): void
    {
        $config = new Config('https://demo.flexibee.eu', 'demo', 'user', 'pass', 'adresar', verifySSLCertificate: true, debugMode: false);

        $this->expectException(EcomailFlexibeeRequestFail::class);

        (new CurlOptionsFactory())->create('', Method::GET, [], [], [], $config);
    }

}
