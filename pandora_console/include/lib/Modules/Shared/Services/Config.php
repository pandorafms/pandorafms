<?php

namespace PandoraFMS\Modules\Shared\Services;

final class Config
{
    public function get(string $key, mixed $default = null): mixed
    {
        global $config;
        return ($config[$key] ?? $default);
    }

    public function set(string $key, mixed $value): bool
    {
        global $config;
        // TODO: change.
        $res = \update_config_token($key, $value);
        if ($res === true) {
            $config[$key] = $value;
        }

        return $res;
    }
}
