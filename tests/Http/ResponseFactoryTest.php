<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Http;

use EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail;
use EcomailFlexibee\Exception\EcomailFlexibeeForbidden;
use EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization;
use EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed;
use EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\FlexibeeAttachmentResponse;
use EcomailFlexibee\Http\Response\FlexibeeBackupResponse;
use EcomailFlexibee\Http\Response\FlexibeePdfResponse;
use EcomailFlexibee\Http\ResponseFactory;
use Iterator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

use const CURLE_COULDNT_CONNECT;
use const CURLE_OK;

final class ResponseFactoryTest extends TestCase
{

    private const URL = 'https://demo.flexibee.eu/c/demo/adresar.json';

    public function testMissingContentThrowsRequestFail(): void
    {
        $this->expectException(EcomailFlexibeeRequestFail::class);

        ResponseFactory::createFromOutput(
            self::URL,
            Method::GET,
            responseContent: null,
            statusCode: 200,
            errorNumber: CURLE_OK,
            errorMessage: null,
            curlInfoDebug: null,
        );
    }

    public function testCurlErrorThrowsConnectionFail(): void
    {
        $this->expectException(EcomailFlexibeeConnectionFail::class);
        $this->expectExceptionMessage('cURL error (7): Connection refused');

        ResponseFactory::createFromOutput(self::URL, Method::GET, '', 0, CURLE_COULDNT_CONNECT, 'Connection refused', curlInfoDebug: null);
    }

    public function testPdfUrlReturnsPdfResponse(): void
    {
        $response = ResponseFactory::createFromOutput(
            'https://demo.flexibee.eu/c/demo/faktura-vydana/1.pdf',
            Method::GET,
            '%PDF',
            200,
            CURLE_OK,
            errorMessage: null,
            curlInfoDebug: null,
        );

        Assert::assertInstanceOf(FlexibeePdfResponse::class, $response);
        Assert::assertSame(['pdfContent' => '%PDF'], $response->getData());
    }

    public function testBackupUrlReturnsBackupResponse(): void
    {
        $response = ResponseFactory::createFromOutput(
            'https://demo.flexibee.eu/c/demo/backup',
            Method::GET,
            'dump',
            200,
            CURLE_OK,
            errorMessage: null,
            curlInfoDebug: null,
        );

        Assert::assertInstanceOf(FlexibeeBackupResponse::class, $response);
        Assert::assertSame(['backupContent' => 'dump'], $response->getData());
    }

    public function testParsesWinstromMetadata(): void
    {
        $content = '{"winstrom":{"@version":"1.5","@rowCount":"3","@globalVersion":"42","message":"ok","stats":{"created":"1"},'
            . '"success":"true","results":[{"id":"1"}]}}';

        $response = ResponseFactory::createFromOutput(self::URL, Method::GET, $content, 201, CURLE_OK, errorMessage: null, curlInfoDebug: null);

        Assert::assertSame(201, $response->getStatusCode());
        Assert::assertSame(1.5, $response->getVersion());
        Assert::assertTrue($response->isSuccess());
        Assert::assertSame('ok', $response->getMessage());
        Assert::assertSame(3, $response->getRowCount());
        Assert::assertSame(42, $response->getGlobalVersion());
        Assert::assertSame([['id' => '1']], $response->getData());
        Assert::assertSame(['created' => '1'], $response->getStatistics());
    }

    public function testUsesDefaultsWhenMetadataIsMissing(): void
    {
        $response = ResponseFactory::createFromOutput(self::URL, Method::GET, '{"adresar":[]}', 200, CURLE_OK, errorMessage: null, curlInfoDebug: null);

        Assert::assertNull($response->getVersion());
        Assert::assertTrue($response->isSuccess());
        Assert::assertSame('{"adresar":[]}', $response->getMessage());
        Assert::assertSame(0, $response->getRowCount());
        Assert::assertNull($response->getGlobalVersion());
        Assert::assertSame(['adresar' => []], $response->getData());
        Assert::assertSame([], $response->getStatistics());
    }

    public function testExplicitSuccessFlagOverridesStatusCode(): void
    {
        $response = ResponseFactory::createFromOutput(self::URL, Method::GET, '{"success":false}', 200, CURLE_OK, errorMessage: null, curlInfoDebug: null);

        Assert::assertFalse($response->isSuccess());
    }

    public function testNonJsonContentProducesEmptyData(): void
    {
        $response = ResponseFactory::createFromOutput(self::URL, Method::GET, 'not json', 302, CURLE_OK, errorMessage: null, curlInfoDebug: null);

        Assert::assertFalse($response->isSuccess());
        Assert::assertSame([], $response->getData());
        Assert::assertSame('not json', $response->getMessage());
    }

    /**
     * @dataProvider getStatusCodeExceptions
     * @param class-string<\Throwable> $exceptionClass
     */
    public function testStatusCodeThrowsMatchingException(int $statusCode, ?string $curlInfoDebug, string $exceptionClass, string $message): void
    {
        $this->expectException($exceptionClass);
        $this->expectExceptionMessage($message);

        ResponseFactory::createFromOutput(self::URL, Method::GET, '{}', $statusCode, CURLE_OK, errorMessage: null, curlInfoDebug: $curlInfoDebug);
    }

    /**
     * @return \Iterator<string, array{int, string|null, class-string<\Throwable>, string}>
     */
    public function getStatusCodeExceptions(): Iterator
    {
        yield 'unauthorized' => [401, null, EcomailFlexibeeInvalidAuthorization::class, 'přihlásit'];
        yield 'forbidden' => [403, null, EcomailFlexibeeForbidden::class, 'nemá oprávnění'];
        yield 'forbidden with debug info' => [403, '"GET /c/demo"', EcomailFlexibeeForbidden::class, 'GET /c/demo'];
        yield 'method not allowed' => [405, null, EcomailFlexibeeMethodNotAllowed::class, ''];
        yield 'not acceptable' => [406, null, EcomailFlexibeeNotAcceptableRequest::class, 'ISDOC'];
    }

    public function testBadRequestThrowsFirstErrorWithStatusCode(): void
    {
        $content = '{"winstrom":{"results":[{"id":"1"},{"errors":[{"code":"E1","for":"kod","path":"adresar","message":"Duplicate","extra":[]},'
            . '{"message":"Second"}]}]}}';

        $this->expectException(EcomailFlexibeeRequestFail::class);
        $this->expectExceptionMessage("code: E1\nfor attribute: kod\npath: adresar\nmessage: Duplicate");
        $this->expectExceptionCode(400);

        ResponseFactory::createFromOutput(self::URL, Method::PUT, $content, 400, CURLE_OK, errorMessage: null, curlInfoDebug: null);
    }

    public function testServerErrorWithoutErrorDetailsThrowsResponseMessage(): void
    {
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $this->expectExceptionMessage('Internal failure');

        ResponseFactory::createFromOutput(
            self::URL,
            Method::GET,
            '{"message":"Internal failure","results":[{"errors":["invalid"]}]}',
            500,
            CURLE_OK,
            errorMessage: null,
            curlInfoDebug: null,
        );
    }

    public function testAttachmentContentUrlReturnsAttachmentResponse(): void
    {
        $response = ResponseFactory::createFromOutput(
            'https://demo.flexibee.eu/c/demo/cenik/164/prilohy/15/content',
            Method::GET,
            "\x89PNG",
            200,
            CURLE_OK,
            errorMessage: null,
            curlInfoDebug: null,
        );

        Assert::assertInstanceOf(FlexibeeAttachmentResponse::class, $response);
        Assert::assertSame(['attachmentContent' => "\x89PNG"], $response->getData());
    }

    public function testMissingAttachmentContentIsParsedAsJson(): void
    {
        $response = ResponseFactory::createFromOutput(
            'https://demo.flexibee.eu/c/demo/cenik/164/prilohy/99/content',
            Method::GET,
            '{"winstrom":{"success":"false","message":"Not found"}}',
            404,
            CURLE_OK,
            errorMessage: null,
            curlInfoDebug: null,
        );

        Assert::assertNotInstanceOf(FlexibeeAttachmentResponse::class, $response);
        Assert::assertFalse($response->isSuccess());
    }

}
