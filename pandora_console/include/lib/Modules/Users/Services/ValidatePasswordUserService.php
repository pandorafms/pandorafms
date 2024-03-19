<?php

namespace PandoraFMS\Modules\Users\Services;

use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Users\Entities\User;

final class ValidatePasswordUserService
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function __invoke(User $user, ?User $oldUser): void
    {
        // Excluyes palabras.
        $excludePassword = $this->checkExcludePassword($user->getPassword());
        if ($excludePassword === true) {
            throw new BadRequestException(
                __('The password provided is not valid. Please set another one.')
            );
        }

        // Si es una actualizacion, revisas el o los antiguos paswords, que no se pueda repetir.
        if ($oldUser !== null) {
            $newPass = password_hash($user->getPassword(), PASSWORD_BCRYPT);
            if ((bool) $this->config->get('enable_pass_history') === true) {
                $oldPasswords = $this->getOldPasswords($user->getIdUser());
                foreach ($oldPasswords as $oldPass) {
                    if ($oldPass['password'] === $newPass) {
                        throw new BadRequestException(
                            __(
                                'Password must be different from the %s previous changes',
                                $this->config->get('compare_pass')
                            )
                        );
                    }
                }
            } else {
                if ($oldUser->getPassword() === $newPass) {
                    throw new BadRequestException(__('Password must be different'));
                }
            }
        }

        // Tamaño del Password.
        if ((strlen($user->getPassword())) < $this->config->get('pass_size')) {
            throw new BadRequestException(__('Password too short'));
        }

        // Numeros includos.
        if ($this->config->get('pass_needs_numbers')
            && preg_match('/([[:alpha:]])*(\d)+(\w)*/', $user->getPassword()) == 0
        ) {
            throw new BadRequestException(__('Password must contain numbers'));
        }

        // Simbolos incluidos.
        if ($this->config->get('pass_needs_symbols')
            && preg_match('/(\w)*(\W)+(\w)*/', $user->getPassword()) == 0
        ) {
            throw new BadRequestException(__('Password must contain symbols'));
        }
    }

    private function checkExcludePassword(string $newPassword): bool
    {
        if ((bool) $this->config->get('enable_pass_policy') === true
            && empty($this->config->get('exclusion_word_list')) === false
        ) {
            $wordList = explode(',', $this->config->get('exclusion_word_list'));
            if (is_array($wordList) === true) {
                foreach ($wordList as $word) {
                    if ($newPassword === trim(io_safe_output($word))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function getOldPasswords(string $idUser): array
    {
        // TODO: create new service for this.
        $sql = sprintf(
            'SELECT `password`
            FROM tpassword_history
            WHERE id_user="%s"
            ORDER BY date_begin
            DESC LIMIT %d',
            $idUser,
            $this->config->get('compare_pass')
        );

        $oldPasswords = db_get_all_rows_sql($sql);
        if ((bool) $oldPasswords === false) {
            $oldPasswords = [];
        }

        return $oldPasswords;
    }
}
