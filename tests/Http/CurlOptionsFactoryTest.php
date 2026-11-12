<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Http\CurlOptionsFactory;
use EcomailFlexibee\Http\CurlRequest;
use EcomailFlexibee\Http\Method;
use JsonException;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

use const CURLAUTH_BASIC;
use const CURLINFO_HEADER_OUT;
use const CURLOPT_CUSTOMREQUEST;
use const CURLOPT_HTTPAUTH;
use const CURLOPT_HTTPHEADER;
use const CURLOPT_POSTFIELDS;
use const CURLOPT_SSL_VERIFYHOST;
use const CURLOPT_SSL_VERIFYPEER;
use const CURLOPT_URL;
use const CURLOPT_USERPWD;

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

        $options = (new CurlOptionsFactory())->create(new CurlRequest('https://demo.flexibee.eu/c/demo/adresar.json', Method::GET, [], [], []), $config);

        Assert::assertTrue($options[CURLOPT_SSL_VERIFYPEER]);
        Assert::assertSame(2, $options[CURLOPT_SSL_VERIFYHOST]);
    }

    public function testWrapsJsonEncodingFailureIntoRequestFail(): void
    {
        $config = new Config('https://demo.flexibee.eu', 'demo', 'user', 'pass', 'adresar', verifySSLCertificate: true, debugMode: false);

        try {
            (new CurlOptionsFactory())->create(
                new CurlRequest('https://demo.flexibee.eu/c/demo/adresar.json', Method::PUT, ['nazev' => "\xB1\x31"], [], []),
                $config,
            );
            Assert::fail('EcomailFlexibeeRequestFail was not thrown.');
        } catch (EcomailFlexibeeRequestFail $exception) {
            Assert::assertInstanceOf(JsonException::class, $exception->getPrevious());
        }
    }

    public function testRejectsAnEmptyUrl(): void
    {
        $config = new Config('https://demo.flexibee.eu', 'demo', 'user', 'pass', 'adresar', verifySSLCertificate: true, debugMode: false);

        $this->expectException(EcomailFlexibeeRequestFail::class);

        (new CurlOptionsFactory())->create(new CurlRequest('', Method::GET, [], [], []), $config);
    }

    public function testUsesBasicAuthenticationWithoutSession(): void
    {
        $config = new Config('https://demo.flexibee.eu', 'demo', 'user', 'pass', 'adresar', verifySSLCertificate: false, debugMode: true);

        $options = (new CurlOptionsFactory())->create(
            new CurlRequest('https://demo.flexibee.eu/c/demo/adresar.json', Method::DELETE, [], [], []),
            $config,
        );

        Assert::assertSame('https://demo.flexibee.eu/c/demo/adresar.json', $options[CURLOPT_URL]);
        Assert::assertSame('DELETE', $options[CURLOPT_CUSTOMREQUEST]);
        Assert::assertSame(CURLAUTH_BASIC, $options[CURLOPT_HTTPAUTH]);
        Assert::assertSame('user:pass', $options[CURLOPT_USERPWD]);
        Assert::assertFalse($options[CURLOPT_SSL_VERIFYPEER]);
        Assert::assertSame(0, $options[CURLOPT_SSL_VERIFYHOST]);
        Assert::assertTrue($options[CURLINFO_HEADER_OUT]);
        Assert::assertArrayNotHasKey(CURLOPT_HTTPHEADER, $options);
        Assert::assertArrayNotHasKey(CURLOPT_POSTFIELDS, $options);
    }

    public function testSessionAuthenticationSendsSessionHeader(): void
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

        $options = (new CurlOptionsFactory())->create(
            new CurlRequest('https://demo.flexibee.eu', Method::GET, [], [], ['Accept: application/json']),
            $config,
        );

        Assert::assertSame(0, $options[CURLOPT_HTTPAUTH]);
        Assert::assertArrayNotHasKey(CURLOPT_USERPWD, $options);
        Assert::assertSame(['Accept: application/json', 'X-authSessionId: session-id'], $options[CURLOPT_HTTPHEADER]);
    }

    public function testEncodesPostFieldsAsWinstromJson(): void
    {
        $config = new Config('https://demo.flexibee.eu', 'demo', 'user', 'pass', 'adresar', verifySSLCertificate: true, debugMode: false);

        $options = (new CurlOptionsFactory())->create(
            new CurlRequest('https://demo.flexibee.eu', Method::PUT, ['adresar' => ['kod' => 'A']], [], []),
            $config,
        );

        Assert::assertSame('{"winstrom":{"adresar":{"kod":"A"}}}', $options[CURLOPT_POSTFIELDS]);
    }

    public function testQueryParametersReplacePostFields(): void
    {
        $config = new Config('https://demo.flexibee.eu', 'demo', 'user', 'pass', 'adresar', verifySSLCertificate: true, debugMode: false);

        $request = new CurlRequest('https://demo.flexibee.eu', Method::POST, ['a' => 'b'], ['username' => 'u', 'password' => 'p']);

        $options = (new CurlOptionsFactory())->create($request, $config);

        Assert::assertSame('username=u&password=p', $options[CURLOPT_POSTFIELDS]);
    }

    public function testSendsRawBodyAsIs(): void
    {
        $config = new Config('https://demo.flexibee.eu', 'demo', 'user', 'pass', 'cenik', verifySSLCertificate: true);
        $request = new CurlRequest('https://demo.flexibee.eu', Method::PUT, headers: ['Content-Type: text/plain'], body: 'raw bytes');

        $options = (new CurlOptionsFactory())->create($request, $config);

        Assert::assertSame('raw bytes', $options[CURLOPT_POSTFIELDS]);
        Assert::assertSame(['Content-Type: text/plain'], $options[CURLOPT_HTTPHEADER]);
    }

}
