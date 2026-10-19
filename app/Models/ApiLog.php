<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

#[Fillable(['provider', 'direction', 'endpoint', 'invoice', 'http_status', 'success', 'message', 'duration_ms', 'request', 'response', 'ip'])]
class ApiLog extends Model
{
    use MassPrunable;

    public const UPDATED_AT = null;

    public const PROVIDERS = ['digiflazz' => 'Digiflazz', 'duitku' => 'Duitku'];

    /** Field rahasia yang disamarkan sebelum disimpan */
    protected const SECRET_KEYS = ['sign', 'signature', 'key', 'apikey', 'api_key', 'merchantkey', 'password'];

    /** Respons lebih besar dari ini (mis. price list) diringkas agar database tidak membengkak */
    protected const MAX_PAYLOAD_BYTES = 20_000;

    protected function casts(): array
    {
        return [
            'success' => 'boolean',
            'request' => 'array',
            'response' => 'array',
        ];
    }

    /**
     * Catat satu komunikasi API. Tidak pernah melempar exception:
     * kegagalan mencatat log tidak boleh menggagalkan transaksi.
     */
    public static function record(array $attributes): void
    {
        try {
            $attributes['request'] = static::sanitize($attributes['request'] ?? null);
            $attributes['response'] = static::sanitize($attributes['response'] ?? null);
            $attributes['message'] = isset($attributes['message']) ? mb_strimwidth((string) $attributes['message'], 0, 250, '…') : null;

            static::query()->create($attributes);
        } catch (Throwable $e) {
            Log::warning('Gagal menyimpan log API', ['error' => $e->getMessage()]);
        }
    }

    protected static function sanitize(mixed $payload): mixed
    {
        if (! is_array($payload)) {
            return $payload === null ? null : ['raw' => mb_strimwidth((string) $payload, 0, 2000, '…')];
        }

        array_walk_recursive($payload, function (&$value, $key) {
            if (is_string($key) && in_array(strtolower($key), self::SECRET_KEYS, true) && filled($value)) {
                $value = '***';
            }
        });

        if (strlen((string) json_encode($payload)) > self::MAX_PAYLOAD_BYTES) {
            return ['_diringkas' => true, 'jumlah_item' => count($payload), 'contoh' => array_slice($payload, 0, 2, true)];
        }

        return $payload;
    }

    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['provider'] ?? null, fn ($q, $provider) => $q->where('provider', $provider))
            ->when(($filters['result'] ?? null) === 'failed', fn ($q) => $q->where('success', false))
            ->when(($filters['result'] ?? null) === 'success', fn ($q) => $q->where('success', true))
            ->when($filters['q'] ?? null, fn ($q, $search) => $q->where(fn ($q) => $q
                ->where('invoice', 'like', "%{$search}%")
                ->orWhere('endpoint', 'like', "%{$search}%")
                ->orWhere('message', 'like', "%{$search}%")));
    }

    /** Log lebih dari 30 hari dihapus otomatis oleh `model:prune` */
    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subDays(30));
    }
}
