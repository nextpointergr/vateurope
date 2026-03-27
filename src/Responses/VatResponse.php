<?php

namespace NextPointer\VatEurope\Responses;

class VatResponse
{
    protected array $data;
    protected bool $success;
    protected ?string $reason;

    public function __construct(bool $success, array $data = [], ?string $reason = null)
    {
        $this->success = $success;
        $this->data = $data;
        $this->reason = $reason;
    }

    public function success(): bool { return $this->success; }
    public function data(): array { return $this->data; }
    public function reason(): ?string { return $this->reason; }

    public function getName(): ?string { return $this->data['name'] ?? null; }
    public function getAddress(): ?string { return $this->data['street'] ?? null; }
    public function getAddressNumber(): ?string { return  null;}
    public function getCity(): ?string { return $this->data['city'] ?? null; }
    public function getPostalCode(): ?string { return $this->data['zip'] ?? null; }
    public function getFullAddress(): ?string { return $this->data['full_address'] ?? null; }
}
