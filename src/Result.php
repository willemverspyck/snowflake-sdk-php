<?php

declare(strict_types=1);

namespace Spyck\Snowflake;

use DateTime;
use DateTimeInterface;
use Exception;
use Spyck\Snowflake\Exception\ResultException;
use Spyck\Snowflake\Exception\TranslateException;

final class Result
{
    private string $id;

    private ?int $total = null;

    private ?int $page = null;

    private ?int $pageTotal = null;

    private ?array $fields = null;

    private ?array $data = null;

    private ?DateTimeInterface $timestamp = null;

    private bool $executed;

    public function __construct(private readonly Service $service)
    {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getTotal(): ?int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getPageTotal(): ?int
    {
        return $this->pageTotal;
    }

    public function setPageTotal(int $pageTotal): self
    {
        $this->pageTotal = $pageTotal;

        return $this;
    }

    public function getFields(): ?array
    {
        return $this->fields;
    }

    public function setFields(array $fields): self
    {
        $this->fields = $fields;

        return $this;
    }

    /**
     * @throws TranslateException
     */
    public function getData(): ?array
    {
        $fields = $this->getFields();

        if (null === $this->data || null === $fields) {
            return null;
        }

        $translate = new Translate();
        $translate->setFields($fields);

        return array_map([$translate, 'getData'], $this->data);
    }

    public function getDataRaw(): ?array
    {
        return $this->data;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getTimestamp(): ?DateTimeInterface
    {
        return $this->timestamp;
    }

    /**
     * @throws Exception
     */
    public function setTimestamp(int $timestamp): self
    {
        $this->timestamp = new DateTime();
        $this->timestamp->setTimestamp((int) ($timestamp / 1000));

        return $this;
    }

    public function isExecuted(): bool
    {
        return $this->executed;
    }

    public function setExecuted(bool $executed): self
    {
        $this->executed = $executed;

        return $this;
    }

    /**
     * @throws ResultException
     */
    public function getPaginationFirst(): bool
    {
        return $this->getPagination(1);
    }

    public function getPaginationPrevious(): bool
    {
        return $this->getPagination($this->getPage() - 1);
    }

    public function getPaginationNext(): bool
    {
        return $this->getPagination($this->getPage() + 1);
    }

    public function getPaginationLast(): bool
    {
        return $this->getPagination($this->getPageTotal());
    }

    public function getPagination(int $page): bool
    {
        if (false === $this->isExecuted()) {
            return false;
        }

        if ($page < 0 || $page > $this->getPageTotal()) {
            return false;
        }

        $data = $this->service->getStatement($this->getId(), $page);

        if (false === array_key_exists('data', $data)) {
            throw new ResultException('Object "data" not found');
        }

        $this->setData($data['data']);
        $this->setPage($page);

        return true;
    }
}
