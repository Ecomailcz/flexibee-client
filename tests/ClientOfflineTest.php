<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\Client;
use EcomailFlexibee\Config as ClientConfig;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed;
use EcomailFlexibee\Http\CurlRequest;
use EcomailFlexibee\Http\HttpClient;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Http\Response\FlexibeeAttachmentResponse;
use EcomailFlexibee\Http\Response\FlexibeeResponse;
use EcomailFlexibee\Result\EvidenceResult;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class ClientOfflineTest extends TestCase
{

    use MockeryPHPUnitIntegration;

    public function testGetCompanyReturnsTheConfiguredCompany(): void
    {
        $company = ['dbNazev' => 'DEMO', 'nazev' => 'Demo s.r.o.'];
        $client = $this->createClient($this->respondWith(['companies' => ['company' => $company]]));

        Assert::assertSame($company, $client->getCompany());
    }

    public function testGetCompanyThrowsWhenTheCompanyIsMissing(): void
    {
        $client = $this->createClient($this->respondWith(['companies' => ['company' => ['dbNazev' => 'other']]]));

        $this->expectException(EcomailFlexibeeNoEvidenceResult::class);

        $client->getCompany();
    }

    public function testGetAuthAndRefreshTokenPostsCredentials(): void
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with(
                Mockery::type('string'),
                Method::POST,
                [],
                ['password' => 'pass', 'username' => 'user'],
                ['Content-Type: application/x-www-form-urlencoded'],
                Mockery::type(ClientConfig::class),
            )
            ->andReturn($this->createResponse(['authSessionId' => 'abc']));

        Assert::assertSame(['authSessionId' => 'abc'], $this->createClient($httpClient)->getAuthAndRefreshToken()->getData());
    }

    public function testGetByIdAndGetByCodeReturnTheEvidence(): void
    {
        $client = $this->createClient($this->respondWith(['adresar' => [['id' => '1', 'kod' => 'A']]]));

        Assert::assertSame([['id' => '1', 'kod' => 'A']], $client->getById(1)->getData());
        Assert::assertSame([['id' => '1', 'kod' => 'A']], $client->getByCode('A')->getData());
    }

    public function testFindByCodesReturnsAllFoundRecords(): void
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with(Mockery::pattern("/\\(kod%20in%20\\('A','B'\\)\\)\\.json/"), Method::GET, [], [], [], Mockery::type(ClientConfig::class))
            ->andReturn($this->createResponse(['adresar' => [['kod' => 'A'], ['kod' => 'B']]]));

        $results = $this->createClient($httpClient)->findByCodes(['A', 'B']);

        Assert::assertEquals([new EvidenceResult(['kod' => 'A']), new EvidenceResult(['kod' => 'B'])], $results);
    }

    public function testFindByCodesWithoutCodesSendsNoRequest(): void
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldNotReceive('request');

        Assert::assertSame([], $this->createClient($httpClient)->findByCodes([]));
    }

    public function testFindByIdAndFindByCodeReturnEmptyResultWhenMissing(): void
    {
        $client = $this->createClient($this->respondWith([], statusCode: 404));

        Assert::assertSame([], $client->findById(1)->getData());
        Assert::assertSame([], $client->findByCode('A')->getData());
    }

    public function testSaveSendsEvidenceDataAndReturnsTheResult(): void
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with(
                Mockery::pattern('/dry-run=true/'),
                Method::PUT,
                ['adresar' => ['kod' => 'A', 'id' => 5]],
                [],
                [],
                Mockery::type(ClientConfig::class),
            )
            ->andReturn($this->createResponse([['id' => '5']]));

        $response = $this->createClient($httpClient)->save(['kod' => 'A'], id: 5, dryRun: true);

        Assert::assertTrue($response->isSuccess());
        Assert::assertSame(1, $response->getRowCount());
        Assert::assertSame([['id' => '5']], $response->getData());
    }

    public function testSaveThrowsWhenNothingWasCreatedOrUpdated(): void
    {
        $response = new FlexibeeResponse(
            200,
            version: null,
            success: true,
            message: 'Nothing saved',
            rowCount: 0,
            data: [],
            statistics: ['created' => '0', 'updated' => '0'],
        );
        $client = $this->createClient($this->respondWithResponse($response));

        $this->expectException(EcomailFlexibeeSaveFailed::class);
        $this->expectExceptionMessage('(200) Nothing saved');

        $client->save(['kod' => 'A'], id: null);
    }

    public function testSaveThrowsWhenTheResultIsNotSuccessful(): void
    {
        $client = $this->createClient($this->respondWith(['message' => 'Invalid data', 'success' => 'no']));

        $this->expectException(EcomailFlexibeeSaveFailed::class);
        $this->expectExceptionMessage('Invalid data');

        $client->save(['kod' => 'A'], id: null);
    }

    public function testGetUserRelationsReturnsTheRelations(): void
    {
        $client = $this->createClient($this->respondWith(['adresar' => [['uzivatelske-vazby' => [['id' => '9']]]]]));

        Assert::assertSame([['id' => '9']], $client->getUserRelations(1)->getData());
    }

    public function testGetUserRelationsReturnsEmptyResultWithoutRelations(): void
    {
        $client = $this->createClient($this->respondWith(['adresar' => [['id' => '1']]]));

        Assert::assertSame([], $client->getUserRelations(1)->getData());
    }

    public function testAddUserRelationLinksTheObjectByCode(): void
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with(Mockery::type('string'), Method::GET, [], [], [], Mockery::type(ClientConfig::class))
            ->andReturn($this->createResponse(['adresar' => [['kod' => 'B']]]));
        $httpClient->shouldReceive('request')
            ->once()
            ->with(
                Mockery::type('string'),
                Method::PUT,
                Mockery::on(static fn (array $postFields): bool => $postFields === [
                    'adresar' => [
                        'id' => 1,
                        'uzivatelske-vazby' => [
                            'uzivatelska-vazba' => [
                                'cena' => 10.5,
                                'evidenceType' => 'adresar',
                                'object' => 'code:B',
                                'popis' => 'note',
                                'vazbaTyp' => 3,
                            ],
                        ],
                    ],
                ]),
                [],
                [],
                Mockery::type(ClientConfig::class),
            )
            ->andReturn($this->createResponse([['id' => '1']]));

        $this->createClient($httpClient)->addUserRelation(1, 2, 10.5, 3, 'note');
    }

    public function testCountInEvidenceReturnsTheRowCount(): void
    {
        $response = new FlexibeeResponse(200, version: null, success: true, rowCount: 12, data: []);

        Assert::assertSame(12, $this->createClient($this->respondWithResponse($response))->countInEvidence());
    }

    public function testGetAttachmentsReturnsAttachmentsOfTheRecord(): void
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with('https://demo.flexibee.eu/c/demo/adresar/7/prilohy.json', Method::GET, [], [], [], Mockery::type(ClientConfig::class))
            ->andReturn($this->createResponse(['priloha' => [['id' => '14']]]));

        Assert::assertEquals([new EvidenceResult(['id' => '14'])], $this->createClient($httpClient)->getAttachments(7));
    }

    public function testGetAttachmentContentReturnsTheBinaryContent(): void
    {
        $client = $this->createClient($this->respondWithResponse(new FlexibeeAttachmentResponse("\x89PNG")));

        Assert::assertSame("\x89PNG", $client->getAttachmentContent(7, 14));
    }

    public function testGetAttachmentContentThrowsWhenTheAttachmentIsMissing(): void
    {
        $client = $this->createClient($this->respondWith([], statusCode: 404));

        $this->expectException(EcomailFlexibeeNoEvidenceResult::class);

        $client->getAttachmentContent(7, 99);
    }

    public function testAddAttachmentUploadsRawContentAndReturnsTheNewId(): void
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldReceive('send')
            ->once()
            ->with(
                Mockery::on(static fn (CurlRequest $request): bool => $request->url === 'https://demo.flexibee.eu/c/demo/adresar/7/prilohy/new/invoice.txt'
                    && $request->httpMethod === Method::PUT
                    && $request->headers === ['Content-Type: text/plain', 'Accept: application/json']
                    && $request->body === 'content'
                    && $request->postFields === []),
                Mockery::type(ClientConfig::class),
            )
            ->andReturn($this->createResponse([['id' => '5837', 'ref' => '/c/demo/adresar/7/prilohy/5837.json']], statusCode: 201));

        Assert::assertSame(5_837, $this->createClient($httpClient)->addAttachment(7, 'invoice.txt', 'content', 'text/plain'));
    }

    public function testAddAttachmentThrowsWhenTheResponseHasNoId(): void
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldReceive('send')->andReturn($this->createResponse([]));

        $this->expectException(EcomailFlexibeeRequestFail::class);

        $this->createClient($httpClient)->addAttachment(7, 'invoice.txt', 'content', 'text/plain');
    }

    public function testDeleteAttachmentSendsDeleteToTheAttachmentDetail(): void
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldReceive('request')
            ->once()
            ->with('https://demo.flexibee.eu/c/demo/adresar/7/prilohy/14.json', Method::DELETE, [], [], [], Mockery::type(ClientConfig::class))
            ->andReturn($this->createResponse([]));

        $this->createClient($httpClient)->deleteAttachment(7, 14);
    }

    private function createClient(HttpClient $httpClient): Client
    {
        return new Client(new ClientConfig('https://demo.flexibee.eu', 'demo', 'user', 'pass', 'adresar', verifySSLCertificate: true), $httpClient);
    }

    /**
     * @param array<mixed> $data
     */
    private function respondWith(array $data, int $statusCode = 200): HttpClient&MockInterface
    {
        return $this->respondWithResponse($this->createResponse($data, $statusCode));
    }

    private function respondWithResponse(FlexibeeResponse $response): HttpClient&MockInterface
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldReceive('request')->andReturn($response);

        return $httpClient;
    }

    /**
     * @param array<mixed> $data
     */
    private function createResponse(array $data, int $statusCode = 200): FlexibeeResponse
    {
        return new FlexibeeResponse($statusCode, version: null, success: true, rowCount: 0, data: $data);
    }

}
