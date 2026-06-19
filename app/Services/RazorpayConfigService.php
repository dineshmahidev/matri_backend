<?php

namespace App\Services;

use App\Models\SiteSetting;
use Razorpay\Api\Api;

class RazorpayConfigService
{
    public function keyId(): ?string
    {
        $env = env('RAZORPAY_KEY_ID');
        if (!empty($env)) {
            return $env;
        }

        return SiteSetting::where('key', 'razorpay_key_id')->value('value') ?: null;
    }

    public function keySecret(): ?string
    {
        $env = env('RAZORPAY_KEY_SECRET');
        if (!empty($env)) {
            return $env;
        }

        return SiteSetting::where('key', 'razorpay_key_secret')->value('value') ?: null;
    }

    public function source(): string
    {
        return !empty(env('RAZORPAY_KEY_ID')) ? 'env' : 'database';
    }

    public function isConfigured(): bool
    {
        return !empty($this->keyId()) && !empty($this->keySecret());
    }

    public function publicConfig(): array
    {
        return [
            'key_id' => $this->keyId(),
            'source' => $this->source(),
            'configured' => $this->isConfigured(),
        ];
    }

    public function api(): Api
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Razorpay is not configured. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in .env or admin settings.');
        }

        return new Api($this->keyId(), $this->keySecret());
    }
}
