<?php declare(strict_types=1);

namespace EcomailFlexibeeTest;

use EcomailFlexibee\RecurringContractClient;
use Mockery;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

final class RecurringContractClientTest extends TestCase
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
            'smlouvy',
            false,
            null,
        );
    }

    public function testBackup(): void
    {
        /** @var \EcomailFlexibee\RecurringContractClient|\Mockery\MockInterface $bankClientMock */
        $bankClientMock = Mockery::mock(RecurringContractClient::class, [$this->config])->makePartial();
        $bankClientMock->shouldReceive('generateInvoices')->andReturnTrue();
        Assert::assertTrue($bankClientMock->generateInvoices());
    }
}
