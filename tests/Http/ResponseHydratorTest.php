<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest\Http;

use EcomailFlexibee\Config;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Http\Response\FlexibeeResponse;
use EcomailFlexibee\Http\ResponseHydrator;
use EcomailFlexibee\Result\EvidenceResult;
use Iterator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class ResponseHydratorTest extends TestCase
{

    /**
     * @dataProvider getFailedResponses
     * @param array<mixed> $data
     */
    public function testFailedResponseThrowsRequestFail(array $data, string $expectedMessage): void
    {
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->expectExceptionCode(400);

        $this->createHydrator()->convertResponseToEvidenceResults($this->createResponse($data, statusCode: 400));
    }

    /**
     * @return \Iterator<string, array{array<mixed>, string}>
     */
    public function getFailedResponses(): Iterator
    {
        yield 'errors without message' => [[['errors' => [['message' => 'First'], 'ignored', ['message' => 'Second']]]], "\nFirst\nSecond"];
        yield 'errors with message' => [['message' => 'Explicit', ['errors' => []]], 'Explicit'];
        yield 'success false flag' => [['message' => 'Rejected', 'success' => 'false'], 'Rejected'];
        yield 'success false without message' => [['success' => 'false'], ''];
    }

    public function testFailedPaginatedResponseThrowsRequestFail(): void
    {
        $this->expectException(EcomailFlexibeeRequestFail::class);

        $this->createHydrator()->convertResponseToPaginatedEvidenceResults($this->createResponse(['success' => 'false', 'message' => 'x']));
    }

    public function testEvidenceItemsBecomeEvidenceResults(): void
    {
        $results = $this->createHydrator()->convertResponseToEvidenceResults(
            $this->createResponse(['adresar' => [['id' => '1'], 'not-an-item', ['id' => '2']]]),
        );

        Assert::assertEquals([0 => new EvidenceResult(['id' => '1']), 2 => new EvidenceResult(['id' => '2'])], $results);
    }

    public function testResponseWithoutEvidenceIsReturnedAsSingleResult(): void
    {
        $results = $this->createHydrator()->convertResponseToEvidenceResults($this->createResponse(['properties' => []]));

        Assert::assertEquals([new EvidenceResult(['properties' => []])], $results);
    }

    public function testEmptyResponseIsReplacedByStatistics(): void
    {
        $response = new FlexibeeResponse(201, 1.0, success: true, message: 'done', rowCount: 4, data: [], statistics: ['created' => '1']);

        $results = $this->createHydrator()->convertResponseToEvidenceResults($response);

        Assert::assertEquals([
            new EvidenceResult([
                'created' => '1',
                'message' => 'done',
                'row_count' => 4,
                'status_code' => 201,
                'version' => 1.0,
            ]),
        ], $results);
    }

    public function testPaginatedResultsCarryRowCount(): void
    {
        $response = new FlexibeeResponse(200, version: null, success: true, rowCount: 7, data: ['adresar' => [['id' => '1']]]);

        $results = $this->createHydrator()->convertResponseToPaginatedEvidenceResults($response);

        Assert::assertEquals(['data' => [new EvidenceResult(['id' => '1'])], 'row_count' => 7], $results);
    }

    public function testPaginatedResponseWithoutEvidenceIsReturnedAsSingleResult(): void
    {
        $response = new FlexibeeResponse(200, version: null, success: true, rowCount: 0, data: ['properties' => []]);

        $results = $this->createHydrator()->convertResponseToPaginatedEvidenceResults($response);

        Assert::assertEquals(['data' => [new EvidenceResult(['properties' => []])], 'row_count' => 0], $results);
    }

    public function testSingleEvidenceResultIsReturned(): void
    {
        $result = $this->createHydrator()->convertResponseToEvidenceResult(
            $this->createResponse(['adresar' => [['id' => '1']]]),
            throwException: true,
        );

        Assert::assertEquals(new EvidenceResult([['id' => '1']]), $result);
    }

    public function testMissingEvidenceThrowsWhenRequested(): void
    {
        $this->expectException(EcomailFlexibeeNoEvidenceResult::class);

        $this->createHydrator()->convertResponseToEvidenceResult(
            $this->createResponse(['adresar' => [['id' => '1']]], statusCode: 404),
            throwException: true,
        );
    }

    public function testMissingEvidenceReturnsRawDataWhenNotThrowing(): void
    {
        $result = $this->createHydrator()->convertResponseToEvidenceResult($this->createResponse(['other' => 'x']), throwException: false);

        Assert::assertEquals(new EvidenceResult(['other' => 'x']), $result);
    }

    public function testMissingEvidenceReturnsEmptyResultForEmptyData(): void
    {
        $result = $this->createHydrator()->convertResponseToEvidenceResult($this->createResponse([]), throwException: false);

        Assert::assertEquals(new EvidenceResult([]), $result);
    }

    public function testAttachmentsAreReadFromThePrilohaCollection(): void
    {
        $response = $this->createResponse(['priloha' => [['id' => '14', 'nazSoub' => 'a.jpg'], ['id' => '15', 'nazSoub' => 'b.jpg']]]);

        $results = $this->createHydrator()->convertResponseToAttachments($response);

        Assert::assertEquals(
            [new EvidenceResult(['id' => '14', 'nazSoub' => 'a.jpg']), new EvidenceResult(['id' => '15', 'nazSoub' => 'b.jpg'])],
            $results,
        );
    }

    public function testRecordWithoutAttachmentsHasNoAttachments(): void
    {
        Assert::assertSame([], $this->createHydrator()->convertResponseToAttachments($this->createResponse([])));
    }

    public function testFailedAttachmentsResponseThrowsRequestFail(): void
    {
        $this->expectException(EcomailFlexibeeRequestFail::class);

        $this->createHydrator()->convertResponseToAttachments($this->createResponse(['success' => 'false', 'message' => 'Denied']));
    }

    private function createHydrator(): ResponseHydrator
    {
        return new ResponseHydrator(
            new Config('https://demo.flexibee.eu', 'demo', 'user', 'pass', 'adresar', verifySSLCertificate: true, debugMode: false),
        );
    }

    /**
     * @param array<mixed> $data
     */
    private function createResponse(array $data, int $statusCode = 200): FlexibeeResponse
    {
        return new FlexibeeResponse($statusCode, version: null, success: true, rowCount: 0, data: $data);
    }

}
