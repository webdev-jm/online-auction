<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AuctionSettings
{
    private const CACHE_KEY = 'auction-settings';

    /**
     * @var array<string, mixed>|null
     */
    private ?array $values = null;

    /**
     * @return array<string, array{type: string, default: mixed}>
     */
    public function definitions(): array
    {
        return config('auction.settings');
    }

    /**
     * Every setting, saved values first and config defaults for the rest.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        $saved = Cache::rememberForever(self::CACHE_KEY, function (): array {
            return Setting::query()->pluck('value', 'key')->all();
        });

        $values = [];
        foreach ($this->definitions() as $key => $definition) {
            $values[$key] = array_key_exists($key, $saved)
                ? $this->cast($definition['type'], $saved[$key])
                : $definition['default'];
        }

        return $this->values = $values;
    }

    public function get(string $key): mixed
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * Save known settings and ignore anything else.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(array $values): void
    {
        foreach ($this->definitions() as $key => $definition) {
            if (! array_key_exists($key, $values)) {
                continue;
            }

            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $this->serialize($definition['type'], $values[$key])]
            );
        }

        $this->flush();
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
        $this->values = null;
    }

    public function biddingMode(): string
    {
        return $this->get('bidding_mode') === 'open' ? 'open' : 'sealed';
    }

    /**
     * Terms text with live values filled in, rendered from Markdown.
     */
    public function termsHtml(): string
    {
        $replacements = [
            '{payment_methods}' => $this->get('payment_methods'),
            '{payment_window_days}' => $this->get('payment_window_days'),
            '{pickup_location}' => $this->get('pickup_location'),
            '{max_bids_per_user}' => $this->get('max_bids_per_user'),
        ];

        $markdown = strtr((string) $this->get('terms_content'), array_map('strval', $replacements));

        return Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * @return list<string>
     */
    public function paymentMethods(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->get('payment_methods')))));
    }

    private function cast(string $type, ?string $value): mixed
    {
        return match ($type) {
            'bool' => (bool) $value,
            'int' => $value === null || $value === '' ? null : (int) $value,
            'decimal' => $value === null || $value === '' ? null : (float) $value,
            default => (string) $value,
        };
    }

    private function serialize(string $type, mixed $value): ?string
    {
        return match ($type) {
            'bool' => $value ? '1' : '0',
            'int', 'decimal' => $value === null || $value === '' ? null : (string) $value,
            default => (string) $value,
        };
    }
}
