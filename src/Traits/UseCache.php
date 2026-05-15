<?php
declare(strict_types=1);

namespace PS\Webservice\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

trait UseCache
{

    private array $tags = [];

    protected function getFromCache(string $key): mixed
    {
        $key = sha1($key);
        if(Cache::has($key)) {
            return Cache::get($key);
        }

        return null;
    }

    protected function tags(string|array $tags): self
    {
        if (is_string($tags)) {
            $tags = [$tags];
        }

        $this->tags = array_merge($this->tags, $tags);
        return $this;
    }

    protected function setToCache(string $key, mixed $value, ?int $ttl = 1440): void
    {
        $key = sha1($key);
        if($ttl === null) {
            Cache::forever($key, $value);
        } else {
            $expiresAt = Carbon::now()->addMinutes($ttl);
            Cache::put($key, $value, $expiresAt);
        }
    }

    protected function removeFromCache(string $key): void
    {
        $key = sha1($key);
        Cache::forget($key);
    }

    protected function existsInCache(string $key): bool
    {
        $key = sha1($key);
        return Cache::has($key);
    }
}