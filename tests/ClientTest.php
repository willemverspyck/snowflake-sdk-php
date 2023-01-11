<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Spyck\Snowflake\Client;
use Spyck\Snowflake\Exception\ParameterException;

final class ClientTest extends TestCase
{
    private Client $client;

    public function setUp(): void
    {
        $this->client = new Client();
    }

    public function testGetUserException(): void
    {
        self::expectException(ParameterException::class);

        $this->client->getUser();
    }

    public function testGetUser(): void
    {
        $this->client->setUser('USER');

        self::assertEquals('USER', $this->client->getUser());
    }

    public function testGetAccountException(): void
    {
        self::expectException(ParameterException::class);

        $this->client->getAccount();
    }

    public function testGetAccount(): void
    {
        $this->client->setAccount('ACCOUNT');

        self::assertEquals('ACCOUNT', $this->client->getAccount());
    }

    public function testGetPublicKeyException(): void
    {
        self::expectException(ParameterException::class);

        $this->client->getPublicKey();
    }

    public function testGetPublicKey(): void
    {
        $this->client->setPublicKey('PUBLIC_KEY');

        self::assertEquals('PUBLIC_KEY', $this->client->getPublicKey());
    }

    public function testGetPrivateKeyException(): void
    {
        self::expectException(ParameterException::class);

        $this->client->getPrivateKey();
    }

    public function testGetPrivateKey(): void
    {
        $this->client->setPrivateKey('PRIVATE_KEY');

        self::assertEquals('PRIVATE_KEY', $this->client->getPrivateKey());
    }
}
