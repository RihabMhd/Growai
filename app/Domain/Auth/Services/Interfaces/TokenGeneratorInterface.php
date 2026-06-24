<?php

namespace Domain\Auth\Services\Interfaces;

interface TokenGeneratorInterface
{

    public function generate(): string;


    public function hash(string $plain): string;
}