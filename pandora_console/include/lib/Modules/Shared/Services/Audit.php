<?php

namespace PandoraFMS\Modules\Shared\Services;

class Audit
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function write(
        string $action,
        string $message = '',
        string $extra = ''
    ): void {
        $idUser ??= $this->config->get('id_user');
        $remoteAddr ??= $this->config->get('REMOTE_ADDR');
        \db_pandora_audit(
            $action,
            __('User '.$idUser.': '.$message),
            $idUser,
            $remoteAddr,
            $extra
        );
    }
}
