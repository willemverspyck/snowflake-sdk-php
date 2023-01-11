<?php

namespace Spyck\Snowflake\Retry;

use Symfony\Component\HttpClient\Exception\InvalidArgumentException;
use Symfony\Component\HttpClient\Response\AsyncContext;
use Symfony\Component\HttpClient\Retry\RetryStrategyInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class ClientRetry implements RetryStrategyInterface
{
    private const DELAY = 1000;
    private const MULTIPLIER = 2;
    private const STATUS_CODES = [
        429, // Too many requests
        504, // Gateway Timeout
    ];

    /**
     * @inheritDoc
     */
    public function shouldRetry(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $transportException): ?bool
    {
        $statusCode = $context->getStatusCode();

        if (in_array($statusCode, self::STATUS_CODES, true)) {
            return true;
        }
        
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getDelay(AsyncContext $context, ?string $responseContent, ?TransportExceptionInterface $transportException): int
    {
        return self::DELAY * self::MULTIPLIER ** $context->getInfo('retry_count');
    }
}
