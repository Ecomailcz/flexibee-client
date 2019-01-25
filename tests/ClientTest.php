<?php declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use function Docopt\dump;
use function Docopt\dump_scalar;
use EcomailFlexibee\Client;
use EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeRequestError;
use EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed;
use EcomailFlexibee\Http\Method;
use Faker\Factory;
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

    public function testInvalidAuthorization(): void
    {
        $client = new Client(Config::HOST, Config::COMPANY, 'xxx', 'xxx', Config::EVIDENCE, false, null);
        $this->expectException(EcomailFlexibeeInvalidAuthorization::class);
        $client->findById($this->faker->numberBetween());
    }

    public function testGetAuthToken(): void
    {
        $authToken = $this->client->getAuthAndRefreshToken();
        $this->assertArrayHasKey('refreshToken', $authToken);
        $this->assertArrayHasKey('authSessionId', $authToken);
        $this->assertArrayHasKey('csrfToken', $authToken);
        $client = new Client(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, Config::EVIDENCE,false, $authToken['authSessionId']);
        $evidenceData = [
            'nazev' => $this->faker->company,
        ];
        $id = $client->save($evidenceData, null);
        $code = $client->getById($id)['kod'];
        $evidenceItem = $client->getByCustomId(sprintf('code:%s', $code));
        $this->assertCount(1, $evidenceItem);
        $this->assertEquals($id, (int) $evidenceItem[0]['id']);
        $client->deleteByCustomId(sprintf('code:%s', $code));
        $this->assertCount(0, $client->findByCustomId(sprintf('code:%s', $code)));

    }

    public function testCRUDForCustomIds(): void
    {
        $evidenceData = [
            'nazev' => $this->faker->company,
        ];
        $id = $this->client->save($evidenceData, null);
        $code = $this->client->getById($id)['kod'];
        $evidenceItem = $this->client->getByCustomId(sprintf('code:%s', $code));
        $this->assertCount(1, $evidenceItem);
        $this->assertEquals($id, (int) $evidenceItem[0]['id']);
        $this->client->deleteByCustomId(sprintf('code:%s', $code));
        $this->assertCount(0, $this->client->findByCustomId(sprintf('code:%s', $code)));
    }

    /**
     * @dataProvider getEvidences
     * @param string $evidence
     * @param mixed[] $evidenceData
     * @param mixed[] $expectedDataAfterUpdate
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeAnotherError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeConnectionError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeRequestError
     * @throws \EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed
     */
    public function testCRUDOperations(string $evidence, array $evidenceData, array $expectedDataAfterUpdate): void
    {
        $client = new Client(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, $evidence, false, null);
        $addressBookId = $client->save($evidenceData, null);
        $client->save($expectedDataAfterUpdate, $addressBookId);
        $addressBookRefreshed = $client->getById($addressBookId);

        foreach ($expectedDataAfterUpdate as $key => $value) {
            $this->assertEquals($value, $addressBookRefreshed[$key]);
        }

        $this->assertNotEmpty($client->getPdfById($addressBookId));
        $this->assertNotEmpty($client->getPdfById($addressBookId, ['report-name' => 'FAKTURA-BLUE-FAV', 'report-lang' => 'en']));
        $client->deleteById($addressBookId);
        $this->assertCount(0, $client->findById($addressBookId));
        $this->expectException(EcomailFlexibeeNoEvidenceResult::class);
        $client->getById($addressBookId);
        $this->expectException(EcomailFlexibeeSaveFailed::class);
        $evidenceData = [];
        $this->client->save($evidenceData, null);
    }

    /**
     * @return mixed[][]
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
        $this->assertTrue(count($result) > 0);

        $firstResult = $this->client->chunkInEvidence(0, 1);
        $this->assertCount(1, $firstResult);

        $resultOther = $this->client->chunkInEvidence(1, 1);
        $this->assertCount(1, $resultOther);

        $this->assertNotEquals($firstResult[0]['id'], $resultOther[0]['id']);
    }

    public function testSearchInEvidence(): void
    {
        $client = new Client(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, 'faktura-vydana', false, null);
        $result = $client->searchInEvidence('kod<>\'JAN\'');
        $this->assertTrue(count($result) > 0);

        $result = $client->searchInEvidence('datSplat<\'2018-12-04\'%20and%20zuctovano=false');
        $this->assertTrue(count($result) > 0);
    }

    public function testMakePreparedUrl(): void
    {
        $this->markTestSkipped();
        $client = new Client(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, 'smlouva', false, null);
        $client->makeRequestPrepared(Method::get(Method::POST), 'generovani-faktur.json');
    }

    public function testMakeRawRequest(): void
    {
        $client = new Client(Config::HOST, Config::COMPANY, Config::USERNAME, Config::PASSWORD, 'faktura-vydana', false, null);
        $result = $client->makeRawRequest(Method::get(Method::GET), '/c/demo/faktura-vydana/1.json');
        $this->assertTrue(count($result) > 0);
    }

    public function testWithExampleFlexibeeData(): void
    {
        $xmlData = json_decode(json_encode((array) simplexml_load_string(file_get_contents(sprintf('%s/_Resources/smlouva.xml', __DIR__)))), true);
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
                $idEvidence = $client->save($evidenceData, null);
                $client->deleteById($idEvidence);
            } catch (EcomailFlexibeeRequestError $exception) {
                if (mb_stripos($exception->getMessage(), 'již používá jiný záznam')) {
                    continue;
                }

                throw $exception;
            }

            $this->assertTrue(true);

        }
    }

}
