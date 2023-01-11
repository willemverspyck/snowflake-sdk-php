<?php

declare(strict_types=1);

namespace Spyck\Snowflake;

use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Spyck\Snowflake\Exception\ParameterException;
use Spyck\Snowflake\Exception\ResultException;

final class Service
{
    private const CODE_SUCCESS = '090001';
    private const CODE_ASYNC = '333334';

    private ?string $warehouse = null;

    private ?string $database = null;

    private ?string $schema = null;

    private ?string $role = null;

    private bool $nullable = true;

    public function __construct(private readonly Client $client)
    {
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getWarehouse(): ?string
    {
        return $this->warehouse;
    }

    public function setWarehouse(string $warehouse): self
    {
        $this->warehouse = $warehouse;

        return $this;
    }

    public function getDatabase(): ?string
    {
        return $this->database;
    }

    public function setDatabase(string $database): self
    {
        $this->database = $database;

        return $this;
    }

    public function getSchema(): ?string
    {
        return $this->schema;
    }

    public function setSchema(string $schema): self
    {
        $this->schema = $schema;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function isNullable(): bool
    {
        return $this->nullable;
    }

    public function setNullable(bool $nullable): self
    {
        $this->nullable = $nullable;

        return $this;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ParameterException
     * @throws RedirectionExceptionInterface
     * @throws ResultException
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function postStatement(string $statement): string
    {
        $client = $this->getClient();

        $account = $client->getAccount();
        
        $variables = http_build_query([
            'async' => 'true',
            'nullable' => $this->isNullable() ? 'true' : 'false',
        ]);

        $url = sprintf('https://%s.snowflakecomputing.com/api/v2/statements?%s', $account, $variables);

        $data = [
            'statement' => $statement,
            'warehouse' => $this->getWarehouse(),
            'database' => $this->getDatabase(),
            'schema' => $this->getSchema(),
            'role' => $this->getRole(),
            'resultSetMetaData' => [
                'format' => 'jsonv2',
            ],
        ];

        $response = $client->getHttpClient()->request('POST', $url, [
            'headers' => $this->getHeaders(),
            'json' => $data,
        ]);

        $content = $this->toArray($response);

        $this->hasResult($content, [self::CODE_ASYNC]);

        return $content['statementHandle'];
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ParameterException
     * @throws RedirectionExceptionInterface
     * @throws ResultException
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getStatement(string $id, int $page): array
    {
        $client = $this->getClient();

        $account = $client->getAccount();

        $variables = http_build_query([
            'partition' => $page - 1,
        ]);

        $url = sprintf('https://%s.snowflakecomputing.com/api/v2/statements/%s?%s', $account, $id, $variables);

        $response = $client->getHttpClient()->request('GET', $url, [
            'headers' => $this->getHeaders(),
        ]);

        // Remove custom toArray when bug in PHP is fixed with support for multiple GZIP's (CRC-32 check and length)
        // $content = $response->toArray(true);

        return $this->toArray($response);
    }

    /**
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ParameterException
     * @throws RedirectionExceptionInterface
     * @throws ResultException
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function cancelStatement(string $id): void
    {
        $client = $this->getClient();

        $url = sprintf('https://%s.snowflakecomputing.com/api/v2/statements/%s/cancel', $client->getAccount(), $id);

        $response = $client->getHttpClient()->request('POST', $url, [
            'headers' => $this->getHeaders(),
        ]);

        $this->hasResult($response->toArray(false), [self::CODE_SUCCESS]);
    }

    /**
     * @return Result
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ParameterException
     * @throws RedirectionExceptionInterface
     * @throws ResultException
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function getResult(string $id): Result
    {
        $data = $this->getStatement($id, 1);

        $executed = $data['code'] === self::CODE_SUCCESS;

        $result = new Result($this);
        $result->setId($data['statementHandle']);
        $result->setExecuted($executed);

        if (false === $executed) {
            return $result;
        }

        foreach (['resultSetMetaData', 'data', 'createdOn'] as $field) {
            if (false === array_key_exists($field, $data)) {
                throw new ResultException(sprintf('Object "%s" not found', $field));
            }
        }

        foreach (['numRows', 'partitionInfo', 'rowType'] as $field) {
            if (false === array_key_exists($field, $data['resultSetMetaData'])) {
                throw new ResultException(sprintf('Object "%s" in "resultSetMetaData" not found', $field));
            }
        }

        $result->setTotal($data['resultSetMetaData']['numRows']);
        $result->setPage(1);
        $result->setPageTotal(count($data['resultSetMetaData']['partitionInfo']));
        $result->setFields($data['resultSetMetaData']['rowType']);
        $result->setData($data['data']);
        $result->setTimestamp($data['createdOn']);

        return $result;
    }

    /**
     * @throws ResultException
     */
    private function hasResult(array $data, array $codes): void
    {
        foreach (['code', 'message'] as $field) {
            if (false === array_key_exists($field, $data)) {
                throw new ResultException('Unacceptable result', 406);
            }
        }

        if (false === in_array($data['code'], $codes)) {
            throw new ResultException(sprintf('%s (%s)', $data['message'], $data['code']), 422);
        }

        foreach (['statementHandle', 'statementStatusUrl'] as $field) {
            if (false === array_key_exists($field, $data)) {
                throw new ResultException('Unprocessable result', 422);
            }
        }
    }

    /**
     * @throws ParameterException
     *
     * @todo Remove "Accept-Encoding" when bugfix GZIP is fixed
     */
    private function getHeaders(): array
    {
        return [
            sprintf('Authorization: Bearer %s', $this->getClient()->getToken()),
            'Accept-Encoding: gzip',
            'User-Agent: SnowflakeService/0.5',
            'X-Snowflake-Authorization-Token-Type: KEYPAIR_JWT',
        ];
    }

    /**
     * @throws JsonException
     *
     * @todo Remove this method when bugfix GZIP is fixed
     */
    private function toArray(ResponseInterface $response): array
    {
        if ('' === $content = $response->getContent(true)) {
            throw new JsonException('Response body is empty.');
        }

        $headers = $response->getHeaders();

        if ('gzip' === ($headers['content-encoding'][0] ?? null)) {
            $content = $this->gzdecode($content);
        }

        try {
            $content = json_decode($content, true, 512, JSON_BIGINT_AS_STRING | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new JsonException(sprintf('%s for "%s".', $exception->getMessage(), $response->getInfo('url')), $exception->getCode());
        }

        if (false === is_array($content)) {
            throw new JsonException(sprintf('JSON content was expected to decode to an array, "%s" returned for "%s".', get_debug_type($content), $response->getInfo('url')));
        }

        return $content;
    }

    private function gzdecode(string $data): string
    {
        $inflate = inflate_init(ZLIB_ENCODING_GZIP);

        $content = '';
        $offset = 0;

        do {
            $content .= inflate_add($inflate, substr($data, $offset));

            if (ZLIB_STREAM_END === inflate_get_status($inflate)) {
                $offset += inflate_get_read_len($inflate);
            }
        } while ($offset < strlen($data));

        return $content;
    }
}
