<?php

declare(strict_types=1);

namespace Nizaamomer\LaravelFastpay\Data;

final readonly class CartItem
{
    public function __construct(
        public string $name,
        public int $qty,
        public float $unitPrice,
    ) {}

    public function subTotal(): float
    {
        return $this->qty * $this->unitPrice;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'qty' => $this->qty,
            'unit_price' => $this->unitPrice,
            'sub_total' => $this->subTotal(),
        ];
    }

    /**
     * Accepts either a CartItem or a plain array like
     * ['name' => 'Scarf', 'qty' => 1, 'unit_price' => 5000].
     *
     * @param  array<int, self|array<string, mixed>>  $items
     * @return array<int, self>
     */
    public static function collection(array $items): array
    {
        return array_map(
            fn (self|array $item): self => $item instanceof self ? $item : new self(
                name: (string) $item['name'],
                qty: (int) $item['qty'],
                unitPrice: (float) $item['unit_price'],
            ),
            $items,
        );
    }
}
