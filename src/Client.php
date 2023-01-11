<?php

declare(strict_types=1);

namespace Spyck\Snowflake;

use Jose\Component\Core\AlgorithmManager;
use Jose\Component\KeyManagement\JWKFactory;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSBuilder;
use Jose\Component\Signature\Serializer\CompactSerializer;
use Spyck\Snowflake\Exception\ParameterException;
use Spyck\Snowflake\Retry\ClientRetry;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class Client
{
    private ?string $account = null;

    private ?string $user = null;

    private ?string $publicKey = null;

    private ?string $privateKey = null;

    private ?string $token = null;

    public function __construct(private ?HttpClientInterface $httpClient = null)
    {
        if (null === $this->httpClient) {
            $this->httpClient = new RetryableHttpClient(HttpClient::create(), new ClientRetry());
        }
    }

    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    /**
     * @throws ParameterException
     */
    public function getAccount(): string
    {
        if (null === $this->account) {
            throw new ParameterException('Account not set');
        }

        return $this->account;
    }

    public function setAccount(string $account): self
    {
        $this->account = $account;

        return $this;
    }

    /**
     * @throws ParameterException
     */
    public function getUser(): string
    {
        if (null === $this->user) {
            throw new ParameterException('User not set');
        }

        return $this->user;
    }

    public function setUser(string $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @throws ParameterException
     */
    public function getPublicKey(): string
    {
        if (null === $this->publicKey) {
            throw new ParameterException('Public key not set');
        }

        return $this->publicKey;
    }

    /**
     * @throws ParameterException
     */
    public function setPublicKey(string $publicKey): self
    {
        if (file_exists($publicKey)) {
            throw new ParameterException(sprintf('Public key "%s" not found', $publicKey));
        }

        $this->publicKey = $publicKey;

        return $this;
    }

    /**
     * @throws ParameterException
     */
    public function getPrivateKey(): string
    {
        if (null === $this->privateKey) {
            throw new ParameterException('Private key not set');
        }

        return $this->privateKey;
    }

    public function setPrivateKey(string $privateKey): self
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * @throws ParameterException
     */
    public function getToken(): string
    {
        if (null === $this->token) {
            throw new ParameterException('Token not set');
        }

        return $this->token;
    }

    /**
     * @throws ParameterException
     */
    public function setToken(int $expires = 3600): void
    {
        $account = strtoupper($this->getAccount());
        $user = strtoupper($this->getUser());
        $time = time();

        $payload = [
            'iss' => sprintf('%s.%s.%s', $account, $user, $this->getPublicKey()),
            'sub' => sprintf('%s.%s', $account, $user),
            'iat' => $time,
            'exp' => $time + ($expires * 30),
        ];

        $algorithmManager = new AlgorithmManager([
            new RS256(),
        ]);

        $jwsBuilder = new JWSBuilder($algorithmManager);

        $signature = JWKFactory::createFromKeyFile($this->getPrivateKey());

        $jws = $jwsBuilder
            ->create()
            ->withPayload(json_encode($payload))
            ->addSignature($signature, ['alg' => 'RS256'])
            ->build();

        $serializer = new CompactSerializer();

        $this->token = $serializer->serialize($jws);
    }

    public function getService(): Service
    {
        return new Service($this);
    }
}
