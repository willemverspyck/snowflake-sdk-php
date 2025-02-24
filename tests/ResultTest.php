<?php

declare(strict_types=1);

namespace Tests;

use DateTime;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;
use Spyck\Snowflake\Client;
use Spyck\Snowflake\Result;
use Spyck\Snowflake\Service;

final class ResultTest extends TestCase
{
    private Result $result;

    public function setUp(): void
    {
        $client = new Client();

        $service = new Service($client);

        $this->result = new Result($service);
        $this->result->setId('ID');
        $this->result->setExecuted(false);
    }

    public function testGetId(): void
    {
        self::assertSame('ID', $this->result->getId());
    }

    public function testGetTotal(): void
    {
        self::assertNull($this->result->getTotal());

        $this->result->setTotal(10);

        self::assertSame(10, $this->result->getTotal());
    }

    public function testGetPage(): void
    {
        self::assertNull($this->result->getPage());

        $this->result->setPage(1);

        self::assertSame(1, $this->result->getPage());
    }

    public function testGetPageTotal(): void
    {
        self::assertNull($this->result->getPageTotal());

        $this->result->setPageTotal(20);

        self::assertSame(20, $this->result->getPageTotal());
    }

    public function testGetFields(): void
    {
        self::assertNull($this->result->getFields());

        $this->result->setFields([
            [
                'name' => 'Field1',
            ],
        ]);

        self::assertSame([
            [
                'name' => 'Field1',
            ],
        ], $this->result->getFields());
    }

    public function testGetData(): void
    {
        self::assertNull($this->result->getData());

        $this->result->setFields([
            [
                'name' => 'FIELD1',
                'type' => 'text',
                'scale' => null,
            ],
            [
                'name' => 'FIELD2',
                'type' => 'boolean',
                'scale' => null,
            ],
        ]);

        $this->result->setData([
            [
                'value1',
                '1',
            ],
            [
                'value2',
                '0',
            ],
        ]);

        self::assertSame([
            [
                'FIELD1' => 'value1',
                'FIELD2' => true,
            ],
            [
                'FIELD1' => 'value2',
                'FIELD2' => false,
            ],
        ], $this->result->getData());
    }

    public function testGetDataRaw(): void
    {
        self::assertNull($this->result->getDataRaw());

        $this->result->setData([
            [
                'field1',
                'field2',
            ],
            [
                'field1',
                'field2',
            ],
        ]);

        self::assertSame([
            [
                'field1',
                'field2',
            ],
            [
                'field1',
                'field2',
            ],
        ], $this->result->getDataRaw());
    }

    public function testGetTimestamp(): void
    {
        self::assertNull($this->result->getTimestamp());

        $this->result->setTimestamp(1633082116654);

        self::assertInstanceOf(DateTimeInterface::class, $this->result->getTimestamp());

        $timestamp = DateTime::createFromFormat('Y-m-d\TH:i:s', '2021-10-01T09:55:16');
        
        self::assertSame($timestamp->format('Y-m-d\TH:i:s.uP'), $this->result->getTimestamp()->format('Y-m-d\TH:i:s.uP'));
    }

    public function testIsExecuted(): void
    {
        self::assertFalse($this->result->isExecuted());
    }
}
