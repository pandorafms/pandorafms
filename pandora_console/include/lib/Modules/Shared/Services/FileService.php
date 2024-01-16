<?php

namespace PandoraFMS\Modules\Shared\Services;

use InvalidArgumentException;
use PandoraFMS\Modules\Shared\Enums\HttpCodesEnum;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Exceptions\ForbiddenActionException;
use Psr\Http\Message\UploadedFileInterface;

class FileService
{
    public function __construct(
        private Config $config,
    ) {
    }

    public function moveUploadedFile(
        UploadedFileInterface $uploadedFile,
        ?string $filename = null,
        ?string $subdirectory = ''
    ) {
        $directory = $this->config->get('attachment_directory');
        $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);

        if ($filename === null) {
            $basename = bin2hex(random_bytes(8));
            $filename = sprintf('%s.%0.8s', $basename, $extension);
        }

        $path = $directory.DIRECTORY_SEPARATOR;
        if (empty($subdirectory) === false) {
            $path .= $subdirectory.DIRECTORY_SEPARATOR;
        }

        $path .= $filename;

        try {
            $uploadedFile->moveTo($path);
        } catch (\Throwable $th) {
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                $th->getCode() ? $th->getCode() : HttpCodesEnum::FORBIDDEN
            );
        }

        return $filename;
    }

    public function removeFile(string $filename)
    {
        $directory = $this->config->get('attachment_directory');
        if (unlink($directory.DIRECTORY_SEPARATOR.$filename) === false) {
            throw new ForbiddenActionException(
                __('Error remove file'),
                HttpCodesEnum::FORBIDDEN
            );
        }
    }

    public function validationFile(
        UploadedFileInterface $file,
        string $regexInvalidExtension = null,
        int $maxSize = null
    ): void {
        if (empty($regexInvalidExtension) === true) {
            $regexInvalidExtension = '/^(bat|exe|cmd|sh|php|php1|php2|php3|php4|php5|pl|cgi|386|dll|com|torrent|js|app|jar|iso|
                pif|vb|vbscript|wsf|asp|cer|csr|jsp|drv|sys|ade|adp|bas|chm|cpl|crt|csh|fxp|hlp|hta|inf|ins|isp|jse|htaccess|
                htpasswd|ksh|lnk|mdb|mde|mdt|mdw|msc|msi|msp|mst|ops|pcd|prg|reg|scr|sct|shb|shs|url|vbe|vbs|wsc|wsf|wsh)$/i';
        }

        $extension = pathinfo($file->getClientFilename(), PATHINFO_EXTENSION);

        if (preg_match($regexInvalidExtension, $extension)) {
            throw new BadRequestException(__('Error upload file, the file extension is not allowed'));
        }

        if (empty($maxSize) === true) {
            $maxSize = $this->config->get('max_file_size');
        }

        if ($file->getSize() > $this->calculateSizeBytes($maxSize)) {
            throw new BadRequestException(__('Error upload file, the file size exceeds the established limit'));
        }
    }

    private function calculateSizeBytes(int $maxSize)
    {
        $max = ini_get('upload_max_filesize');
        if (empty($maxSize) === false && $maxSize < $max) {
            $max = $maxSize;
        }

        return ($maxSize * 1000000);
    }
}
