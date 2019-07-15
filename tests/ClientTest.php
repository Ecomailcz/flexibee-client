<?php declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\Client;
use EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestError;
use EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed;
use EcomailFlexibee\Http\Method;
use EcomailFlexibee\Result\EvidenceResult;
use Faker\Factory;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{

    /**
     * @var \EcomailFlexibee\Client
     */
    private $client;

    /**
     * @var \Faker\Generator
     */
    private $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Factory::create();
        $this->client = new Client(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, Config::EVIDENCE, false, null);
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
        $client = new Client(Config::HOST, Config::COMPANY, 'xxx', 'xxx', Config::EVIDENCE, false, $authToken['authSessionId']);
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
        Assert::assertTrue(count($data['changes']) > 0);
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

    public function testFailedBackup(): void
    {
        $this->expectException(EcomailFlexibeeNotAcceptableRequest::class);
        $this->client->backup();
    }

    /**
     * @dataProvider getEvidences
     * @param string $evidence
     * @param array<mixed> $evidenceData
     * @param array<mixed> $expectedDataAfterUpdate
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeMethodNotAllowed
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNotAcceptableRequest
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
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
        Assert::assertNotEmpty($client->getPdfById($addressBookId, ['report-name' => 'FAKTURA-BLUE-FAV', 'report-lang' => 'en']));
        $client->deleteById($addressBookId);
        Assert::assertCount(0, $client->findById($addressBookId)->getData());
        $this->expectException(EcomailFlexibeeNoEvidenceResult::class);
        $client->getById($addressBookId);
        $this->expectException(EcomailFlexibeeSaveFailed::class);
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
        $client = new Client(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, 'faktura-vydana', false, null);
        $result = $client->searchInEvidence('kod<>\'JAN\'', []);
        Assert::assertTrue(count($result) > 0);

        $result = $client->searchInEvidence('(datSplat<\'2018-12-04\'%20and%20zuctovano=false)', []);
        Assert::assertTrue(count($result) > 0);
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
            []
        );

        Assert::assertArrayHasKey(0, $results);
        /** @var \EcomailFlexibee\Result\EvidenceResult $data */
        $data = $results[0];
        Assert::assertArrayHasKey('properties', $data->getData());
    }

    public function testWithExampleFlexibeeData(): void
    {
        /** @var string $content */
        $content = file_get_contents(sprintf('%s/_Resources/smlouva.xml', __DIR__));
        /** @var \SimpleXMLElement $content */
        $content = simplexml_load_string($content);
        /** @var string $content */
        $content = json_encode((array) $content);
        $xmlData = json_decode($content, true);

        foreach ($xmlData as $evidenceName => $evidenceData) {
            if (in_array($evidenceName, ['@attributes'], true)) {
                continue;
            }

            $client = new Client(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, $evidenceName, false, null);

            if (array_key_exists('@attributes', $evidenceData)) {
                unset($evidenceData['@attributes']);
            }

            if (array_key_exists('id', $evidenceData)) {
                unset($evidenceData['id']);
            }

            try {
                if ($evidenceName === 'smlouva' && isset($evidenceData['polozkySmlouvy'])) {
                    unset($evidenceData['polozkySmlouvy']['@attributes']);
                    $evidenceData['polozkySmlouvy']['kod'] = uniqid();

                    foreach ($evidenceData['polozkySmlouvy']["smlouva-polozka"] as &$item) {
                        $item['kod'] = uniqid();

                    }
                }

                $idEvidence = (int) $client->save($evidenceData, null)->getData()[0]['id'];
                $client->deleteById($idEvidence);
            } catch (EcomailFlexibeeRequestError $exception) {
                if (mb_stripos($exception->getMessage(), 'již používá jiný záznam') !== false) {
                    continue;
                }

                throw $exception;
            }
        }
    }

    public function testRunBackendProcesses(): void
    {
        $client = new Client(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, 'pokladni-pohyb', false);
        $results = $client->callRequest(Method::get(Method::POST), 'automaticke-parovani', [], [], []);

        foreach ($results as $result) {
            $this->checkResponseStructure($result);
        }
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
