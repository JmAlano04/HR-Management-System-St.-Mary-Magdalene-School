<?php

namespace App\Casts;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class EncryptedOrPlaintext implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $payload = base64_decode($value, true);
        $decoded = $payload === false ? null : json_decode($payload, true);

        if (is_array($decoded)
            && (array_key_exists('iv', $decoded)
                || array_key_exists('value', $decoded)
                || array_key_exists('mac', $decoded)
                || array_key_exists('tag', $decoded))) {
            try {
                return Crypt::decryptString($value);
            } catch (DecryptException $exception) {
                Log::warning('Unable to decrypt employee data; omitting the affected value.', [
                    'model'     => $model::class,
                    'model_id'  => $model->getKey(),
                    'attribute' => $key,
                    'error'     => $exception->getMessage(),
                ]);

                return null;
            }
        }

        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value === null ? null : Crypt::encryptString((string) $value);
    }
}
