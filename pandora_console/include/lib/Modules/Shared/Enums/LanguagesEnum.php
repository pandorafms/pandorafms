<?php

namespace PandoraFMS\Modules\Shared\Enums;

use PandoraFMS\Modules\Shared\Traits\EnumTrait;

enum LanguagesEnum: string
{
    use EnumTrait;

case CATALONIAN = 'ca';
case ENGLISH = 'en_GB';
case SPANISH = 'es';
case FRENCH = 'fr';
case JAPANESE = 'ja';
case RUSSIAN = 'ru';
case CHINESE = 'zh_CN';

    }
