<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Service;

use DolzeZampa\WS\Domain\Object\WebserviceConfig;
use DolzeZampa\WS\Service\HttpServiceInterface;
use GuzzleHttp\Client;
use Psr\Http\Client\ClientExceptionInterface;
use Slim\Http\Interfaces\ResponseInterface;
use DolzeZampa\WS\Domain\ObjectInterface;

class HttpService implements HttpServiceInterface {

    private WebserviceConfig $config;
    private string $api;
    private \GuzzleHttp\Psr7\Response $response;
    private int $httpCode;

    public function __construct(WebserviceConfig $config) {
        $this->config = $config;
    }

    public function setUrl(string $url): void {
        $this->api = $this->config->api($url);
    }

    /**
     * Invokes an HTTP request with the specified method and optional data.
     *
     * @param string $method The HTTP method to use for the request (e.g., 'GET', 'POST', 'PUT', 'DELETE').
     * @param array $data Optional. An associative array of data to be sent with the request. Default is an empty array.
     *
     * @return self
     */
    public function invoke(string $method, array|ObjectInterface $data = []): self {
        if($data instanceof ObjectInterface) {
            $data = $data->toArray();
        }

        try {
            $config = $this->config;
            $stream = new Client($config->toArray());
            $response = $stream->request($method, $this->api, );
            $this->response = $response;
            $this->httpCode = $response->getStatusCode();
            return $this;
        } catch (ClientExceptionInterface $e) {
            throw new \RuntimeException("HTTP request failed: " . $e->getMessage());
        }
    }

    /**
     * Get the HTTP response.
     *
     * @return ResponseInterface The HTTP response object.
     */
    public function response(): ResponseInterface {
        return $this->response;
    }

    /**
     * Get the response body as a string.
     *
     * @return string The response body.
     */
    public function getBody(): string {
        return $this->response->getBody()->getContents();
    }

    public function getHttpCode(): int {
        return $this->httpCode;
    }

    public function failed(): bool {
        return $this->httpCode >= 400;
    }

    /**
     * Registers a callback function to be executed when an error occurs.
     *
     * @param callable $callback The callback function to execute on error.
     *                           The callback will receive error details as parameters.
     * @return void
     */
    public function onError(callable $callback): void {
        if ($this->httpCode >= 400) {
            $callback($this->response);
        }
    }

    public function toArray(): array {
        return json_decode($this->getBody(), true);
    }
 }