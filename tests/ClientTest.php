<?php

declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\Client;
use EcomailFlexibee\Config as ClientConfig;
use EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Http\Method;
use Faker\Factory;
use Faker\Generator;
use Iterator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

use function count;
use function mb_substr;
use function uniqid;

/**
 * @group integration
 */
final class ClientTest extends TestCase
{

    private Client $client;

    private Generator $faker;

    public function testGetCompanies(): void
    {
        /** @var array<mixed> $companies */
        $companies = $this->client->getCompanies()->getData();
        Assert::assertArrayHasKey('companies', $companies);
        Assert::assertNotEmpty($companies['companies']);
    }

    public function testGetCompany(): void
    {
        Assert::assertNotEmpty($this->client->getCompany());
    }

    public function testGetLoginFormUrl(): void
    {
        Assert::assertNotEmpty($this->client->getLoginFormUrl([]));
        $queryWithParameters = $this->client->getLoginFormUrl(['otp' => 'test', 'returnUrl' => $this->faker->url]);
        Assert::assertStringContainsString('otp', $queryWithParameters);
        Assert::assertStringContainsString('returnUrl', $queryWithParameters);
    }

    public function testInvalidAuthorization(): void
    {
        $client = new Client(new ClientConfig(Config::HOST, Config::COMPANY, 'xxx', 'xxx', Config::EVIDENCE, verifySSLCertificate: false));
        $this->expectException(EcomailFlexibeeInvalidAuthorization::class);
        $client->findById($this->faker->numberBetween());
    }

    public function testGetAuthTokenAndMakeSuccessCallWithSessionAuthId(): void
    {
        $authToken = $this->client->getAuthAndRefreshToken()->getData();
        Assert::assertArrayHasKey('refreshToken', $authToken);
        Assert::assertArrayHasKey('authSessionId', $authToken);
        Assert::assertArrayHasKey('csrfToken', $authToken);
        $client = new Client(new ClientConfig(
            Config::HOST,
            Config::COMPANY,
            'xxx',
            'xxx',
            Config::EVIDENCE,
            verifySSLCertificate: false,
            authSessionId: $this->getString($authToken, 'authSessionId'),
        ));
        Assert::assertNotEmpty($client->allInEvidence());
    }

    public function testCount(): void
    {
        Assert::assertTrue($this->client->countInEvidence() > 0);
    }

    public function testInvalidGetAuthToken(): void
    {
        $client = new Client(new ClientConfig(Config::HOST, Config::COMPANY, 'xxx', 'xxx', Config::EVIDENCE, verifySSLCertificate: false));
        $flexibeeResponse = $client->getAuthAndRefreshToken();
        Assert::assertFalse($flexibeeResponse->isSuccess());
        $data = $flexibeeResponse->getData();
        Assert::assertArrayHasKey('errors', $data);
        Assert::assertIsArray($data['errors']);
        Assert::assertArrayHasKey('reason', $data['errors']);
    }

    public function testGetChanges(): void
    {
        if (!$this->client->isAllowedChangesApi()) {
            return;
        }

        $response = $this->client->getAllApiChanges(fromVersion: null);
        Assert::assertTrue($response->isSuccess());
        $data = $response->getData();
        Assert::assertArrayHasKey('changes', $data);
        $response = $this->client->getChangesApiForEvidence('faktura-vydana');
        Assert::assertTrue($response->isSuccess());
    }

    public function testCrudForCustomIds(): void
    {
        $evidenceData = [
            'nazev' => $this->faker->firstName,
        ];
        $id = $this->getInt($this->getFirstItem($this->client->save($evidenceData, id: null)->getData()), 'id');
        $code = $this->getString($this->getFirstItem($this->client->getById($id)->getData()), 'kod');
        $evidenceItem = $this->client->getByCode($code);
        Assert::assertCount(1, $evidenceItem->getData());
        Assert::assertSame($id, $this->getInt($this->getFirstItem($evidenceItem->getData()), 'id'));
        $evidenceItemFull = $this->client->getByCode($code, ['detail' => 'full']);
        Assert::assertNotCount(
            count($this->getFirstItem($evidenceItem->getData())),
            $this->getFirstItem($evidenceItemFull->getData()),
        );
        $this->client->deleteByCode($code);
        Assert::assertCount(0, $this->client->findByCode($code)->getData());
    }

    public function testEvidenceGetOnlyCustomFields(): void
    {
        $evidenceData = [
            'nazev' => $this->faker->firstName,
        ];
        $id = $this->getInt($this->getFirstItem($this->client->save($evidenceData, id: null)->getData()), 'id');
        $evidenceData = $this->client->getById($id, ['detail' => 'custom:id,email,kontakty(primarni,email)'])->getData()[0];
        Assert::assertEquals([
            'email' => '',
            'id' => $id,
            'kontakty' => [],
        ],
            $evidenceData,
        );
    }

    /**
     * @dataProvider getEvidences
     * @param array<mixed> $evidenceData
     * @param array<mixed> $expectedDataAfterUpdate
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeForbidden
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestFail
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed
     */
    public function testCrudOperations(string $evidence, array $evidenceData, array $expectedDataAfterUpdate): void
    {
        $client = $this->createClient($evidence);
        $addressBookId = $this->getInt($this->getFirstItem($client->save($evidenceData, id: null)->getData()), 'id');
        $client->save($expectedDataAfterUpdate, $addressBookId);
        $addressBookRefreshed = $this->getFirstItem($client->getById($addressBookId)->getData());

        foreach ($expectedDataAfterUpdate as $key => $value) {
            Assert::assertEquals($value, $addressBookRefreshed[$key]);
        }

        Assert::assertNotEmpty($client->getPdfById($addressBookId, []));
        Assert::assertNotEmpty(
            $client->getPdfById($addressBookId, ['report-name' => 'FAKTURA-BLUE-FAV', 'report-lang' => 'en']),
        );
        $client->deleteById($addressBookId);
        Assert::assertCount(0, $client->findById($addressBookId)->getData());
        $this->expectException(EcomailFlexibeeNoEvidenceResult::class);
        $client->getById($addressBookId);
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $evidenceData = [];
        $this->client->save($evidenceData, id: null);
    }

    /**
     * @return \Iterator<int, array{string, array<string, string>, array<string, string>}>
     */
    public function getEvidences(): Iterator
    {
        $faker = Factory::create();
        $code = mb_substr($faker->uuid, 0, 20);
        $name = $faker->userName;

        yield [
            'adresar',
            [
                'kod' => $code,
                'nazev' => $name,
            ],
            [
                'nazev' => 'Adresar edited',
            ],
        ];

        yield [
            'faktura-vydana',
            [
                'cisDosle' => '1234',
                'eetTypK' => 'eetTyp.ne',
                'kod' => $code,
                'typDokl' => 'code:FAKTURA',
            ],
            [
                'cisDosle' => '1234',
            ],
        ];
    }

    public function testAllAndChunkInEvidence(): void
    {
        $result = $this->client->allInEvidence();
        Assert::assertTrue(count($result) > 0);

        $firstResult = $this->client->chunkInEvidence(0, 1);
        Assert::assertCount(1, $firstResult);

        $resultOther = $this->client->chunkInEvidence(1, 1);
        Assert::assertCount(1, $resultOther);
        Assert::assertNotEquals($firstResult[0]->getData()['id'], $resultOther[0]->getData()['id']);
    }

    public function testSearchInEvidence(): void
    {
        $client = $this->createClient('faktura-vydana');
        $result = $client->searchInEvidence('(kod neq \'JAN\')', []);
        Assert::assertTrue(count($result) > 0);
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $client->searchInEvidence('kod neq \'JAN\'', []);
    }

    public function testSearchInEvidencePaginated(): void
    {
        $client = $this->createClient('faktura-vydana');
        $result = $client->searchInEvidencePaginated('(kod neq \'JAN\')', []);
        Assert::assertIsInt($result['row_count']);
        Assert::assertTrue($result['row_count'] > 0);
        Assert::assertIsArray($result['data']);
        Assert::assertNotEmpty($result['data']);
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $client->searchInEvidencePaginated('kod neq \'JAN\'', []);
    }

    public function testFindLastInEvidence(): void
    {
        $client = $this->createClient('faktura-vydana');
        $result = $client->findLastInEvidence(fullDetail: true);
        Assert::assertIsArray($result->getData()['faktura-vydana']);
        Assert::assertCount(1, $result->getData()['faktura-vydana']);
    }

    public function testSearchInEvidenceWithInvalidUrl(): void
    {
        $client = $this->createClient('faktura-vydana');
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $client->searchInEvidence(' bla.json', []);
    }

    public function testDryRunRequest(): void
    {
        $response = $this->client->save(['kod' => uniqid(), 'nazev' => 'SDSDXXXXX'], id: null, dryRun: true);
        $content = $this->getFirstItem($response->getData())['content'] ?? null;
        Assert::assertIsArray($content);
        Assert::assertArrayHasKey(Config::EVIDENCE, $content);
        Assert::assertIsArray($content[Config::EVIDENCE]);
        Assert::assertLessThan(0, $this->getInt($content[Config::EVIDENCE], 'id'));
    }

    public function testMakeCustomRequest(): void
    {
        $results = $this->client->callRequest(
            Method::GET,
            'properties',
            [],
            [],
            [],
        );

        Assert::assertArrayHasKey(0, $results);
        /** @var \EcomailFlexibee\Result\EvidenceResult $data */
        $data = $results[0];
        Assert::assertArrayHasKey('properties', $data->getData());
    }

    public function testUnknownCompany(): void
    {
        $client = new Client(new ClientConfig(Config::HOST, 'xxx', Config::USERNAME, Config::PASSWORD, 'faktura-vydana', verifySSLCertificate: false));
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $client->save([], id: null);
    }

    public function testCreateSameEvidenceRecord(): void
    {
        $code = uniqid();
        $data = [
            'kod' => $code,
            'nazev' => $code,
        ];
        $this->client->save($data, id: null);
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $this->expectExceptionCode(400);
        $this->client->save($data, id: null);
    }

    public function testGetPropertiesForEvidence(): void
    {
        $responseData = $this->client->getPropertiesForEvidence()->getData();
        Assert::assertArrayHasKey('properties', $responseData);
        Assert::assertIsArray($responseData['properties']);
        Assert::assertArrayHasKey('property', $responseData['properties']);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
        $this->client = $this->createClient(Config::EVIDENCE);
    }

    private function createClient(string $evidence): Client
    {
        return new Client(new ClientConfig(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, $evidence, verifySSLCertificate: false));
    }

    /**
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function getFirstItem(array $data): array
    {
        $item = $data[0] ?? null;
        Assert::assertIsArray($item);

        return $item;
    }

    /**
     * @param array<mixed> $data
     */
    private function getString(array $data, string $key): string
    {
        $value = $data[$key] ?? null;
        Assert::assertIsString($value);

        return $value;
    }

    /**
     * @param array<mixed> $data
     */
    private function getInt(array $data, string $key): int
    {
        $value = $data[$key] ?? null;
        Assert::assertIsNumeric($value);

        return (int) $value;
    }

}
