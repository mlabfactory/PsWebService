<?php
declare(strict_types=1);

namespace PS\Webservice\Domain\Models;

use Mdf\JsonStorage\Domain\Model\JsonModelInterface;

class RequestStorage implements JsonModelInterface
{
    private array $data;

    public function __construct(array $request)
    {
        $this->data = $request;
    }
    public function getId(): string
    {
        return $this->data['id'] ?? '';
    }

    public function toArray(): array
    {
        return $this->data;
    }
}