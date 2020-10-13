<?php declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\Client;
use EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestFail;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Result\EvidenceResult;
use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use function count;
use function file_exists;
use function file_get_contents;
use function mb_substr;
use function uniqid;
use function unlink;

final class ClientTest extends TestCase
{

    private Client $client;
    private Generator $faker;

    public function setUp(): void
    {
        parent::setUp();

        $this->faker = Factory::create();
        $this->client = new Client(
            Config::HOST,
            Config::COMPANY,
            Config::USERNAME,
            Config::PASSWORD,
            Config::EVIDENCE,
            false,
            null,
        );
    }

    public function testGetCompanies(): void
    {
        /** @var array<mixed> $companies */
        $companies = $this->client->getCompanies()->getData();
        Assert::assertArrayHasKey('companies', $companies);
        Assert::assertTrue(count($companies) > 0);
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
        $client = new Client(Config::HOST, Config::COMPANY, 'xxx', 'xxx', Config::EVIDENCE, false, null);
        $this->expectException(EcomailFlexibeeInvalidAuthorization::class);
        $client->findById($this->faker->numberBetween());
    }

    public function testGetAuthTokenAndMakeSuccessCallWithSessionAuthId(): void
    {
        $authToken = $this->client->getAuthAndRefreshToken()->getData();
        Assert::assertArrayHasKey('refreshToken', $authToken);
        Assert::assertArrayHasKey('authSessionId', $authToken);
        Assert::assertArrayHasKey('csrfToken', $authToken);
        $client = new Client(
            Config::HOST,
            Config::COMPANY,
            'xxx',
            'xxx',
            Config::EVIDENCE,
            false,
            $authToken['authSessionId'],
        );
        Assert::assertNotEmpty($client->allInEvidence());
    }

    public function testCount(): void
    {
        Assert::assertTrue($this->client->countInEvidence() > 0);
    }

    public function testInvalidGetAuthToken(): void
    {
        $client = new Client(Config::HOST, Config::COMPANY, 'xxx', 'xxx', Config::EVIDENCE, false, null);
        $flexibeeResponse = $client->getAuthAndRefreshToken();
        Assert::assertFalse($flexibeeResponse->isSuccess());
        $data = $flexibeeResponse->getData();
        Assert::assertArrayHasKey('errors', $data);
        Assert::assertArrayHasKey('reason', $data['errors']);
    }

    public function testGetChanges(): void
    {
        if (!$this->client->isAllowedChangesApi()) {
            return;
        }

        $response = $this->client->getAllApiChanges(null);
        Assert::assertTrue($response->isSuccess());
        $data = $response->getData();
        Assert::assertArrayHasKey('changes', $data);
        $response = $this->client->getChangesApiForEvidence('faktura-vydana');
        Assert::assertTrue($response->isSuccess());
    }

    public function testCRUDForCustomIds(): void
    {
        $evidenceData = [
            'nazev' => $this->faker->firstName,
        ];
        $id = (int) $this->client->save($evidenceData, null)->getData()[0]['id'];
        $code = $this->client->getById($id)->getData()[0]['kod'];
        $evidenceItem = $this->client->getByCode($code);
        Assert::assertCount(1, $evidenceItem->getData());
        Assert::assertEquals($id, (int) $evidenceItem->getData()[0]['id']);
        $evidenceItemFull = $this->client->getByCode($code, ['detail' => 'full']);
        Assert::assertNotEquals(count($evidenceItem->getData()[0]), count($evidenceItemFull->getData()[0]));
        $this->client->deleteByCode($code);
        Assert::assertCount(0, $this->client->findByCode($code)->getData());
    }

    public function testEvidenceGetOnlyCustomFields(): void
    {
        $evidenceData = [
            'nazev' => $this->faker->firstName,
        ];
        $id = (int) $this->client->save($evidenceData, null)->getData()[0]['id'];
        $evidenceData = $this->client->getById($id, ['detail' => 'custom:id,email,kontakty(primarni,email)'])->getData()[0];
        Assert::assertEquals([
            'id' => $id,
            'email' => '',
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
    public function testCRUDOperations(string $evidence, array $evidenceData, array $expectedDataAfterUpdate): void
    {
        $client = new Client(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, $evidence, false, null);
        $addressBookId = (int) $client->save($evidenceData, null)->getData()[0]['id'];
        $client->save($expectedDataAfterUpdate, $addressBookId);
        $addressBookRefreshed = $client->getById($addressBookId);

        foreach ($expectedDataAfterUpdate as $key => $value) {
            Assert::assertEquals($value, $addressBookRefreshed->getData()[0][$key]);
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
        $this->client->save($evidenceData, null);
    }

    /**
     * @return array<array<mixed>>
     */
    public function getEvidences(): array
    {
        $faker = Factory::create();
        $code = mb_substr($faker->uuid, 0, 20);
        $name = $faker->userName;

        return [
            [
                'adresar',
                [
                    'kod' => $code,
                    'nazev' => $name,
                ],
                [
                    'nazev' => 'Adresar edited',
                ],
            ],
            [
                'faktura-vydana',
                [
                    'kod' => $code,
                    'typDokl' => 'code:FAKTURA',
                    'cisDosle' => '1234',
                    'eetTypK' => 'eetTyp.ne',
                ],
                [
                    'cisDosle' => '1234',
                ],
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
        $client = new Client(
            Config::HOST,
            Config::COMPANY,
            Config::USERNAME,
            Config::PASSWORD,
            'faktura-vydana',
            false,
            null,
        );
        $result = $client->searchInEvidence('(kod neq \'JAN\')', []);
        Assert::assertTrue(count($result) > 0);
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $client->searchInEvidence('kod neq \'JAN\'', []);
    }

    public function testSearchInEvidenceWithInvalidUrl(): void
    {
        $client = new Client(
            Config::HOST,
            Config::COMPANY,
            Config::USERNAME,
            Config::PASSWORD,
            'faktura-vydana',
            false,
            null,
        );
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $client->searchInEvidence(' bla.json', []);
    }

    public function testDryRunRequest(): void
    {
        $response = $this->client->save(['kod' => uniqid(), 'nazev' => 'SDSDXXXXX'], null, true);
        $firstItem = $response->getData()[0];
        Assert::assertArrayHasKey('content', $firstItem);
        Assert::assertArrayHasKey(Config::EVIDENCE, $firstItem['content']);
        Assert::assertTrue((int) $firstItem['content'][Config::EVIDENCE]['id'] < 0);
    }

    public function testMakeCustomRequest(): void
    {
        $results = $this->client->callRequest(
            Method::get(Method::GET),
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

    public function testRunBackendProcesses(): void
    {
        $client = new Client(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, 'pokladni-pohyb', false);
        $results = $client->callRequest(Method::get(Method::POST), 'automaticke-parovani', [], [], []);

        foreach ($results as $result) {
            $this->checkResponseStructure($result);
        }
    }

    public function testUnknownCompany(): void
    {
        $client = new Client(Config::HOST, 'xxx', Config::USERNAME, Config::PASSWORD, 'faktura-vydana', false);
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $client->save([], null);

    }

    public function testCreateSameEvidenceRecord(): void
    {
        $code = uniqid();
        $data = [
            'nazev' => $code,
            'kod' => $code,
        ];
        $this->client->save($data, null);
        $this->expectException(EcomailFlexibeeRequestFail::class);
        $this->expectExceptionCode(400);
        $this->client->save($data, null);
    }

    public function testGetPropertiesForEvidence(): void
    {
        $responseData = $this->client->getPropertiesForEvidence()->getData();
        Assert::assertArrayHasKey('properties', $responseData);
        Assert::assertArrayHasKey('property', $responseData['properties']);
    }

    public function testLogRequest(): void
    {
        $logPath = 'logs/log.txt';
        @unlink($logPath);
        $client = new Client(
            Config::HOST,
            Config::COMPANY,
            Config::USERNAME,
            Config::PASSWORD,
            'faktura-vydana',
            false,
            null,
            $logPath,
        );
        $client->allInEvidence();

        Assert::assertTrue(file_exists($logPath));
        Assert::assertNotEmpty(file_get_contents($logPath));
        unlink($logPath);
    }

    private function checkResponseStructure(EvidenceResult $result): void
    {
        $requiredKeys = [
            'created',
            'updated',
            'deleted',
            'skipped',
            'failed',
            'status_code',
            'message',
            'version',
            'row_count',
        ];

        foreach ($requiredKeys as $requiredKey) {
            Assert::assertArrayHasKey($requiredKey, $result->getData());
        }
    }

}
