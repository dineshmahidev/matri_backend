<?php

namespace App\Services;

use App\Models\SiteSetting;

class GpayConfigService
{
    public function isEnabled(): bool
    {
        $env = env('GPAY_ENABLED');
        if ($env !== null && $env !== '') {
            return filter_var($env, FILTER_VALIDATE_BOOLEAN);
        }

        return filter_var(
            SiteSetting::where('key', 'gpay_enabled')->value('value'),
            FILTER_VALIDATE_BOOLEAN
        );
    }

    public function merchantName(): ?string
    {
        $env = env('GPAY_MERCHANT_NAME');
        if (!empty($env)) {
            return $env;
        }

        return SiteSetting::where('key', 'gpay_merchant_name')->value('value') ?: null;
    }

    public function merchantId(): ?string
    {
        $env = env('GPAY_MERCHANT_ID');
        if (!empty($env)) {
            return $env;
        }

        return SiteSetting::where('key', 'gpay_merchant_id')->value('value') ?: null;
    }

    public function upiId(): ?string
    {
        $env = env('GPAY_UPI_ID');
        if (!empty($env)) {
            return $env;
        }

        return SiteSetting::where('key', 'gpay_upi_id')->value('value') ?: null;
    }

    public function source(): string
    {
        return env('GPAY_ENABLED') !== null && env('GPAY_ENABLED') !== '' ? 'env' : 'database';
    }

    public function isConfigured(): bool
    {
        return $this->isEnabled() && !empty($this->merchantName());
    }

    public function publicConfig(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'configured' => $this->isConfigured(),
            'merchant_name' => $this->merchantName() ?: 'Google Pay',
            'merchant_id' => $this->merchantId(),
            'upi_id' => $this->upiId(),
            'source' => $this->source(),
        ];
    }
}