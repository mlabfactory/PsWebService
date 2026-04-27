<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Domain;

use DolzeZampa\WS\Service\PS\PrestashopServiceInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;

interface ObjectInterface extends Arrayable, Jsonable {

    public function __get(string $name): mixed;

    public static function create(array $data, PrestashopServiceInterface $service): self;

    public function normalizeData(): void;

}