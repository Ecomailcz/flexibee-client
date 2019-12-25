<?php declare(strict_types=1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\BankClient;
use Mockery;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class CompanyClientTest extends TestCase
{
    /**
     * @var \EcomailFlexibee\Config
     */
    private $config;

    public function setUp(): void
    {
        parent::setUp();
        $this->config = new \EcomailFlexibee\Config(
            Config::HOST,
            Config::COMPANY,
            Config::USERNAME,
            Config::PASSWORD,
            'c',
            false,
            null,
        );
    }

    public function testBackup(): void
    {
        /** @var \EcomailFlexibee\CompanyClient|\Mockery\MockInterface $bankClientMock */
        $bankClientMock = Mockery::mock(BankClient::class, [$this->config])->makePartial();
        $bankClientMock->shouldReceive('backup')->andReturnTrue();
        Assert::assertTrue($bankClientMock->backup());
    }

    public function testAutomaticPairing(): void
    {
        /** @var \EcomailFlexibee\CompanyClient|\Mockery\MockInterface $bankClientMock */
        $bankClientMock = Mockery::mock(BankClient::class, [$this->config])->makePartial();
        $bankClientMock->shouldReceive('restore')->andReturnTrue();
        Assert::assertTrue($bankClientMock->restore('demo', ''));
    }
}
