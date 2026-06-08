<?php 
namespace App\Domain\Orders\Models;

final class WhatsAppTemplate
{
    public function __construct(
        private readonly array $translations 
    ) {}

    public function forLanguage(string $lang): ?string
    {
        return $this->translations[$lang] ?? null;
    }

    public function toArray(): array
    {
        return $this->translations;
    }
}