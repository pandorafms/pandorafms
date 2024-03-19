<?php

namespace PandoraFMS\Modules\Shared\Services;

class Timestamp
{
    public function __construct(
        private Config $config
    ) {
    }

    public function getMysqlCurrentTimestamp(
        int $unixtime,
        ?string $format = 'Y-m-d H:i:s',
    ): string {
        if ($unixtime == 0) {
            $unixtime = time();
        }

        if (!is_numeric($unixtime)) {
            $unixtime = strtotime($unixtime);
        }

        return date($format, $unixtime);
    }

    public function getMysqlSystemUtimestamp(): int
    {
        $return = \mysql_get_system_time();
        return $return;
    }
}
