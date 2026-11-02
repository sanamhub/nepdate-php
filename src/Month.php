<?php

declare(strict_types=1);

namespace NepDate;

/**
 * A Bikram Sambat month, 1 = Baishakh through 12 = Chaitra.
 */
enum Month: int
{
    case Baishakh = 1;
    case Jestha = 2;
    case Ashad = 3;
    case Shrawan = 4;
    case Bhadra = 5;
    case Ashoj = 6;
    case Kartik = 7;
    case Mangsir = 8;
    case Poush = 9;
    case Magh = 10;
    case Falgun = 11;
    case Chaitra = 12;

    /** The full month name in {@see $lang}, for example "Shrawan" or "साउन". */
    public function label(Lang $lang = Lang::En): string
    {
        return Internal\Names::MONTH[$lang === Lang::Ne ? 'ne' : 'en'][$this->value];
    }

    /** The short month name in {@see $lang}; Nepali has no abbreviation (FORMATTING.md §1). */
    public function shortLabel(Lang $lang = Lang::En): string
    {
        if ($lang === Lang::Ne) {
            return Internal\Names::MONTH['ne'][$this->value];
        }

        return Internal\Names::MONTH_SHORT_EN[$this->value];
    }
}
