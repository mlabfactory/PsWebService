<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Domain\Object;

use DolzeZampa\WS\Domain\ObjectInterface;
use DolzeZampa\WS\Service\PS\PrestashopServiceInterface;
use InvalidArgumentException;

final class WebserviceConfig implements ObjectInterface {

    private readonly string $apikey;
    private readonly string $base_uri;

    private string $api;

    private array $header = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ];

    private const ALLOWED_CONFIGS = [
        'apikey',
        'base_uri',
        'timeout',
        'headers',
        'proxy',
        'verify',
        'debug',
        'http_errors',
        'cookies',
        'allow_redirects',
        ];

    public function __construct(string $apiKey, string $domain, array $headers = [])
    {
        $this->apikey = $apiKey;
        $this->base_uri = $domain;
        $this->api = 'https://' . $apiKey . '@' . $domain . '/api';
        $this->header = array_merge($this->header, $headers);
    }

    public function __get(string $name): mixed 
    {
        if(!isset($this->name)) {
            throw new InvalidArgumentException("No argument found with " . $name);
        }
        return $this->name;
    }

    public function api(string $api): string
    {
        return $this->api . $api;
    }

    public function toArray(): array
    {
        return [
            'base_uri' => $this->base_uri,
            'headers' => $this->header
        ];
    }

    public function toJson($options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public static function create(array $data, PrestashopServiceInterface $service): self
    {
        return new self(
            $data['apikey'],
            $data['base_uri']
        );
    }

    public function normalizeData(): void
    {
        //
    }
}