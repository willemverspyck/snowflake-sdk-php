<?php

declare(strict_types=1);

namespace Tests;

use DateTime;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Spyck\Snowflake\Exception\TranslateException;
use Spyck\Snowflake\Translate;

final class TranslateTest extends TestCase
{
    private Translate $translate;

    public function setUp(): void
    {
        $this->translate = new Translate();
    }

    public function testGetFields(): void
    {
        $fields = [
            [
                'name' => 'NAME',
                'type' => 'fixed',
                'scale' => 0,
            ],
        ];

        $this->translate->setFields($fields);

        self::assertSame($fields, $this->translate->getFields());
    }

    public function testSetFields(): void
    {
        self::expectException(TranslateException::class);

        $this->translate->setFields([
            [
                'scale' => 0,
            ]
        ]);
    }

    public function testGetArray(): void
    {
        $reflection = new ReflectionClass($this->translate);

        $method = $reflection->getMethod('getArray');

        self::assertSame([['id' => 12345, 'name' => 'Name 1'],['id' => 67890, 'name' => 'Name 2']], $method->invokeArgs($this->translate, ['[{"id":12345,"name":"Name 1"},{"id":67890,"name":"Name 2"}]']));
        self::assertNull($method->invokeArgs($this->translate, [null]));
    }

    public function testGetBoolean(): void
    {
        $reflection = new ReflectionClass($this->translate);

        $method = $reflection->getMethod('getBoolean');

        self::assertFalse($method->invokeArgs($this->translate, ['0']));
        self::assertTrue($method->invokeArgs($this->translate, ['1']));
        self::assertNull($method->invokeArgs($this->translate, [null]));
        self::assertNull($method->invokeArgs($this->translate, ['2']));
    }

    public function testGetDate(): void
    {
        $reflection = new ReflectionClass($this->translate);

        $method = $reflection->getMethod('getDate');

        self::assertSame('1970-01-01', $method->invokeArgs($this->translate, ['0'])->format('Y-m-d'));
        self::assertSame('1970-01-02', $method->invokeArgs($this->translate, ['1'])->format('Y-m-d'));
        self::assertSame('2019-04-14', $method->invokeArgs($this->translate, ['18000'])->format('Y-m-d'));

        self::assertNull($method->invokeArgs($this->translate, [null]));
    }

    public function testGetFixed(): void
    {
        $reflection = new ReflectionClass($this->translate);

        $method = $reflection->getMethod('getFixed');

        self::assertSame(12345, $method->invokeArgs($this->translate, ['12345', 0]));
        self::assertIsInt($method->invokeArgs($this->translate, ['12345', 0]));

        self::assertSame(12345, $method->invokeArgs($this->translate, ['12345.1234567890', 0]));
        self::assertIsInt($method->invokeArgs($this->translate, ['12345.1234567890', 0]));

        self::assertSame(12345.0, $method->invokeArgs($this->translate, ['12345', 6]));
        self::assertIsFloat($method->invokeArgs($this->translate, ['12345', 6]));

        self::assertSame(12345.123456789, $method->invokeArgs($this->translate, ['12345.1234567890', 6]));
        self::assertIsFloat($method->invokeArgs($this->translate, ['12345.1234567890', 6]));

        self::assertNull($method->invokeArgs($this->translate, [null, 0]));
    }

    public function testGetTime(): void
    {
        $reflection = new ReflectionClass($this->translate);

        $method = $reflection->getMethod('getTime');

        self::assertSame('2021-03-19T17:06:59.000000+00:00', $method->invokeArgs($this->translate, ['1616173619.000000000'])->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2021-03-19T17:06:59.123456+00:00', $method->invokeArgs($this->translate, ['1616173619.123456789'])->format('Y-m-d\TH:i:s.uP'));

        self::assertNull($method->invokeArgs($this->translate, ['1616173619000000000']));
        self::assertNull($method->invokeArgs($this->translate, [null]));
    }

    public function testGetTimeWithTimezone(): void
    {
        $reflection = new ReflectionClass($this->translate);

        $method = $reflection->getMethod('getTimeWithTimezone');

        self::assertSame('2021-03-19T17:06:59.000000+00:00', $method->invokeArgs($this->translate, ['1616173619.000000000 0'])->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2021-03-19T17:36:59.000000+00:30', $method->invokeArgs($this->translate, ['1616173619.000000000 30'])->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2021-03-19T18:06:59.000000+01:00', $method->invokeArgs($this->translate, ['1616173619.000000000 60'])->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2021-03-20T09:06:59.000000+16:00', $method->invokeArgs($this->translate, ['1616173619.000000000 960'])->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2021-03-20T09:36:59.000000+16:30', $method->invokeArgs($this->translate, ['1616173619.000000000 990'])->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2021-03-19T17:06:59.123456+00:00', $method->invokeArgs($this->translate, ['1616173619.123456789 0'])->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2021-03-19T17:36:59.123456+00:30', $method->invokeArgs($this->translate, ['1616173619.123456789 30'])->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2021-03-19T18:06:59.123456+01:00', $method->invokeArgs($this->translate, ['1616173619.123456789 60'])->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2021-03-20T09:06:59.123456+16:00', $method->invokeArgs($this->translate, ['1616173619.123456789 960'])->format('Y-m-d\TH:i:s.uP'));
        self::assertSame('2021-03-20T09:36:59.123456+16:30', $method->invokeArgs($this->translate, ['1616173619.123456789 990'])->format('Y-m-d\TH:i:s.uP'));

        self::assertNull($method->invokeArgs($this->translate, ['1616173619.000000000']));
        self::assertNull($method->invokeArgs($this->translate, ['1616173619.123456789']));
        self::assertNull($method->invokeArgs($this->translate, ['1616173619000000000']));
        self::assertNull($method->invokeArgs($this->translate, [null]));
    }
}
