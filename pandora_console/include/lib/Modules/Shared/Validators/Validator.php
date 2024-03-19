<?php

namespace PandoraFMS\Modules\Shared\Validators;

use PandoraFMS\Modules\Shared\Enums\LanguagesEnum;

class Validator
{
    public const INTEGER = 'Integer';
    public const BOOLEAN = 'Bool';
    public const STRING = 'String';
    public const ARRAY = 'Array';
    public const GREATERTHAN = 'GreaterThan';
    public const GREATEREQUALTHAN = 'GreaterEqualThan';
    public const DATETIME = 'DateTime';
    public const DATE = 'Date';
    public const TIME = 'Time';
    public const TIMEZONE = 'TimeZone';
    public const LANGUAGE = 'Language';
    public const MAIL = 'Mail';

    public function __construct()
    {
    }

    public function validate(array $args): array
    {
        $failed = [];
        foreach ($args as $field => $info) {
            $type = $info['type'];
            $value = $info['value'];

            if (is_array($type) === true) {
                foreach ($type as $subType) {
                    $result = $this->buildError($subType, $field, $value);
                    if ($result) {
                        $failed[$field] = $result;
                    }
                }
            } else {
                $result = $this->buildError($type, $field, $value);
                if ($result) {
                    $failed[$field] = $result;
                }
            }
        }

        return $failed;
    }

    private function buildError(string $type, string $field, $value): ?array
    {
        if ($this->{'is'.$type}($value) !== true) {
            return [
                'type'    => 'integer',
                'message' => 'Field '.$field.' The value '.$value.' is not a valid '.$type.'.',
            ];
        }

        return null;
    }

    public function isInteger($arg): bool
    {
        return is_numeric($arg);
    }

    public function isGreaterThan($arg): bool
    {
        return $arg > 0;
    }

    public function isGreaterEqualThan($arg): bool
    {
        return $arg >= 0;
    }

    public function isBool($arg): bool
    {
        return is_bool($arg);
    }

    public function isString($arg): bool
    {
        return is_string($arg) || is_numeric($arg);
    }

    public function isArray($arg): bool
    {
        return is_array($arg);
    }

    public function isDate($date)
    {
        return $this->isDateTime($date, 'Y-m-d');
    }

    public function isTime($date)
    {
        return $this->isDateTime($date, 'H:i:s');
    }

    public function isDateTime($date, $format = 'Y-m-d H:i:s')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public function isTimeZone(string $timeZone): string
    {
        return array_search($timeZone, timezone_identifiers_list());
    }

    protected function isLanguage(string $language): bool
    {
        $result = LanguagesEnum::get(strtoupper($language));

        return empty($result) === true ? false : true;
    }

    protected function isMail(string $mail): bool
    {
        return filter_var($mail, FILTER_VALIDATE_EMAIL);
    }
}
