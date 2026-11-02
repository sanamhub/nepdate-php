<?php

declare(strict_types=1);

namespace NepDate;

/**
 * A weekday with Sunday = 0, the spec's numbering (not PHP `date('N')`'s Monday = 1).
 */
enum Weekday: int
{
    case Sunday = 0;
    case Monday = 1;
    case Tuesday = 2;
    case Wednesday = 3;
    case Thursday = 4;
    case Friday = 5;
    case Saturday = 6;

    /** The full weekday name in {@see $lang}, for example "Tuesday" or "मङ्गलवार". */
    public function label(Lang $lang = Lang::En): string
    {
        return Internal\Names::WEEKDAY[$lang === Lang::Ne ? 'ne' : 'en'][$this->value];
    }

    /** The short weekday name in {@see $lang}, for example "Tue" or "मङ्गल". */
    public function shortLabel(Lang $lang = Lang::En): string
    {
        return Internal\Names::WEEKDAY_SHORT[$lang === Lang::Ne ? 'ne' : 'en'][$this->value];
    }
}
