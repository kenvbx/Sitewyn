<?php

namespace Sitewyn\Core\Base\Support;

class DateFilter
{
    /**
     * Accept only strict Y-m-d dates so arbitrary query input never reaches
     * whereDate(); anything else counts as no filter.
     */
    public static function parse(?string $value): ?string
    {
        $value = trim($value ?? '');

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        return checkdate($month, $day, $year) ? $value : null;
    }
}
