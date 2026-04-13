<?php

namespace Aliziodev\PayId\DTO;

final class ItemData
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $price,
        public readonly int $quantity,
        public readonly ?string $category = null,
        public readonly ?string $merchantName = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function make(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            price: $data['price'],
            quantity: $data['quantity'],
            category: $data['category'] ?? null,
            merchantName: $data['merchant_name'] ?? null,
        );
    }

    /**
     * Total harga item (price * quantity).
     */
    public function total(): int
    {
        return $this->price * $this->quantity;
    }
}
