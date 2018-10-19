<?php declare(strict_types = 1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\Client;
use EcomailFlexibee\Exception\EcomailFlexibeeInvalidAuthorization;
use EcomailFlexibee\Exception\EcomailFlexibeeNoEvidenceResult;
use EcomailFlexibee\Exception\EcomailFlexibeeSaveFailed;
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
        $this->assertCount(2, $authToken);
        $this->assertArrayHasKey('refreshToken', $authToken);
        $this->assertArrayHasKey('authSessionId', $authToken);
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

}