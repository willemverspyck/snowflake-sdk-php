<?php

declare(strict_types=1);

namespace Spyck\Snowflake;

use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Spyck\Snowflake\Exception\TranslateException;

final class Translate
{
    private array $fields = [];

    /**
     * @throws TranslateException
     */
    public function getFields(): array
    {
        if (count($this->fields) === 0) {
            throw new TranslateException('Fields not set');
        }
        
        return $this->fields;
    }
    
    /**
     * @throws TranslateException
     */
    public function setFields(array $fields): void
    {
        foreach ($fields as $field) {
            foreach (['name', 'type', 'scale'] as $name) {
                if (false === array_key_exists($name, $field)) {
                    throw new TranslateException('Fields not found');
                }
            }
        }

        $this->fields = $fields;
    }

    /**
     * @throws TranslateException
     */
    public function getData(array $data): array
    {
        $content = [];

        foreach ($this->getFields() as $index => $field) {
            $fieldName = $field['name'];

            switch ($field['type']) {
                case 'array':
                    $content[$fieldName] = $this->getArray($data[$index]);

                    break;
                case 'binary':
                case 'text':
                    $content[$fieldName] = $data[$index];

                    break;
                case 'boolean':
                    $content[$fieldName] = $this->getBoolean($data[$index]);

                    break;
                case 'date':
                    $content[$fieldName] = $this->getDate($data[$index]);

                    break;
                case 'fixed':
                    $content[$fieldName] = $this->getFixed($data[$index], $field['scale']);

                    break;
                case 'time':
                case 'timestamp_ltz':
                case 'timestamp_ntz':
                    $content[$fieldName] = $this->getTime($data[$index]);

                    break;
                case 'timestamp_tz':
                    $content[$fieldName] = $this->getTimeWithTimezone($data[$index]);

                    break;
                default:
                    throw new TranslateException(sprintf('Type "%s" not found', $field['type']));
            }
        }

        return $content;
    }

    private function getArray(?string $value): ?array
    {
        if (null === $value) {
            return null;
        }

        return json_decode($value, true);
    }

    private function getBoolean(?string $value): ?bool
    {
        if ('0' === $value) {
            return false;
        }

        if ('1' === $value) {
            return true;
        }

        return null;
    }

    private function getDate(?string $value): ?DateTimeInterface
    {
        if (null === $value) {
            return null;
        }

        $date = new DateTime('1970-01-01 00:00:00');
        $date->modify(sprintf('+%d days', $value));

        return $date;
    }

    private function getFixed(?string $value, int $scale): float|int|null
    {
        if (null === $value) {
            return null;
        }

        if (0 === $scale) {
            return (int) $value;
        }

        return (float) $value;
    }

    /**
     * @throws Exception
     */
    private function getTime(?string $value): ?DateTimeInterface
    {
        if (null === $value) {
            return null;
        }

        if (1 === preg_match('/^(\d+\.\d{6})\d{3}/is', $value, $matches)) {
            $timezone = new DateTimeZone('+0000');

            $date = new DateTime(sprintf('@%f', $matches[1]));
            $date->setTimezone($timezone);

            return $date;
        }

        return null;
    }

    /**
     * @throws Exception
     */
    private function getTimeWithTimezone(?string $value): ?DateTimeInterface
    {
        if (null === $value) {
            return null;
        }

        if (1 === preg_match('/^(\d+\.\d{6})\d{3}\s(\d{1,4})/is', $value, $matches)) {
            $timezone = new DateTimeZone(sprintf('+%02d:%02d', floor($matches[2] / 60), $matches[2] % 60));

            $date = new DateTime(sprintf('@%f', $matches[1]));
            $date->setTimezone($timezone);

            return $date;
        }

        return null;
    }
}
