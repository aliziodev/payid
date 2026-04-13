<?php

namespace Aliziodev\PayId\Factories;

use Aliziodev\PayId\Contracts\DriverInterface;
use Aliziodev\PayId\Exceptions\DriverNotFoundException;
use Aliziodev\PayId\Exceptions\DriverResolutionException;
use Illuminate\Contracts\Container\Container;
use Throwable;

class DriverFactory
{
    /** @var array<string, callable(array<string, mixed>): DriverInterface> */
    protected array $resolvers = [];

    public function __construct(
        protected readonly Container $container,
    ) {}

    /**
     * Daftarkan custom resolver untuk driver tertentu.
     *
     * @param  callable(array<string, mixed>): DriverInterface  $resolver
     */
    public function extend(string $driver, callable $resolver): void
    {
        $this->resolvers[$driver] = $resolver;
    }

    /**
     * Buat instance driver dari konfigurasi.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws DriverNotFoundException
     * @throws DriverResolutionException
     */
    public function make(string $name, array $config): DriverInterface
    {
        $driverType = $config['driver'] ?? $name;

        if (isset($this->resolvers[$driverType])) {
            try {
                return ($this->resolvers[$driverType])($config);
            } catch (Throwable $e) {
                throw new DriverResolutionException($name, $e);
            }
        }

        throw new DriverNotFoundException($name);
    }

    /**
     * Periksa apakah resolver untuk driver tertentu sudah terdaftar.
     */
    public function hasResolver(string $driver): bool
    {
        return isset($this->resolvers[$driver]);
    }
}
