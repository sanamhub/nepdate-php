<?php

declare(strict_types=1);

namespace NepDate;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use JsonException;
use JsonSerializable;
use NepDate\Internal\Calendar;
use NepDate\Internal\CalendarTable;
use NepDate\Internal\Format;
use NepDate\Internal\Lenient;
use NepDate\Internal\Parse;
use NepDate\Internal\Pattern;
use Stringable;

/**
 * A date in the Bikram Sambat calendar, from 1901-01-01 to 2199-12-30 BS
 * (1844-04-11 to 2143-04-15 AD).
 *
 * Immutable. Comparisons use the BS serial day number, so dates order chronologically.
 */
final class NepaliDate implements Stringable, JsonSerializable
{
    private function __construct(
        private readonly int $year,
        private readonly int $month,
        private readonly int $day,
        private readonly int $serial,
    ) {
    }

    // --- Construction (ND01, ND02, ND04, ND16, ND17, NC01) ---

    public static function fromBs(int $year, int $month, int $day): self
    {
        Calendar::validate($year, $month, $day);

        return new self($year, $month, $day, Calendar::toSerial($year, $month, $day));
    }

    public static function fromGregorian(DateTimeInterface $date): self
    {
        $y = (int) $date->format('Y');
        $m = (int) $date->format('n');
        $d = (int) $date->format('j');

        if (!Calendar::isValidGregorian($y, $m, $d)) {
            throw new NepDateException(ErrorKind::InvalidGregorian, "{$y}-{$m}-{$d} is not a real Gregorian date");
        }

        $serial = Calendar::daysFromCivil($y, $m, $d) - CalendarTable::EPOCH_UNIX_DAYS;
        if ($serial < 0 || $serial > CalendarTable::MAX_SERIAL) {
            throw new NepDateException(ErrorKind::OutOfRange, "{$y}-{$m}-{$d} is outside the supported range");
        }

        return self::fromSerial($serial);
    }

    public static function min(): self
    {
        return self::fromBs(CalendarTable::MIN_YEAR, 1, 1);
    }

    public static function max(): self
    {
        $length = Calendar::monthLength(CalendarTable::MAX_YEAR, 12);

        return self::fromBs(CalendarTable::MAX_YEAR, 12, $length);
    }

    public static function today(DateTimeZone $zone): self
    {
        return self::fromGregorian(new DateTimeImmutable('now', $zone));
    }

    private static function fromSerial(int $serial): self
    {
        [$y, $m, $d] = Calendar::fromSerial($serial);

        return new self($y, $m, $d, $serial);
    }

    private static function fromParts(int $year, int $month, int $day, int $serial): self
    {
        return new self($year, $month, $day, $serial);
    }

    // --- Parsing (ND02, NP01, NP03, NC02, NC03) ---

    public static function parse(string $text): self
    {
        [$y, $m, $d] = Parse::strict($text);

        return self::fromBs($y, $m, $d);
    }

    public static function tryParse(string $text): ?self
    {
        try {
            return self::parse($text);
        } catch (NepDateException) {
            return null;
        }
    }

    public static function parseLenient(string $text): self
    {
        [$y, $m, $d] = Lenient::parse($text);

        return self::fromBs($y, $m, $d);
    }

    public static function tryParseLenient(string $text): ?self
    {
        try {
            return self::parseLenient($text);
        } catch (NepDateException) {
            return null;
        }
    }

    // --- Serialization (NC09, NC10) ---

    public static function fromYmd(int $n): self
    {
        $year = intdiv($n, 10000);
        $month = intdiv($n, 100) % 100;
        $day = $n % 100;

        return self::fromBs($year, $month, $day);
    }

    public function toYmd(): int
    {
        return $this->year * 10000 + $this->month * 100 + $this->day;
    }

    /**
     * Reads the JSON form written by {@see jsonSerialize()}: a quoted `YYYY-MM-DD` string
     * (NC10, D-11). Anything else, including a JSON `null`, is an invalid date.
     */
    public static function fromJson(string $json): self
    {
        try {
            $decoded = json_decode($json, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new NepDateException(ErrorKind::InvalidCharacter, 'the text is not a JSON string');
        }

        if (!is_string($decoded)) {
            throw new NepDateException(ErrorKind::InvalidCharacter, 'the JSON value is not a string');
        }

        return self::parse($decoded);
    }

    public function jsonSerialize(): string
    {
        return Format::iso($this->year, $this->month, $this->day, Lang::En);
    }

    // --- Batch conversion (NC04-NC08) ---

    /**
     * @param iterable<DateTimeInterface> $dates
     * @return list<self>
     */
    public static function fromGregorianBatch(iterable $dates): array
    {
        $result = [];
        foreach ($dates as $date) {
            $result[] = self::fromGregorian($date);
        }

        return $result;
    }

    /**
     * @param iterable<string> $texts
     * @return list<DateTimeImmutable>
     */
    public static function parseBatch(iterable $texts): array
    {
        $result = [];
        foreach ($texts as $text) {
            $result[] = self::parse($text)->toDateTime();
        }

        return $result;
    }

    /**
     * @param iterable<self> $dates
     * @return list<DateTimeImmutable>
     */
    public static function toGregorianBatch(iterable $dates): array
    {
        $result = [];
        foreach ($dates as $date) {
            $result[] = $date->toDateTime();
        }

        return $result;
    }

    // --- Accessors (ND06-ND13) ---

    public function year(): int
    {
        return $this->year;
    }

    public function month(): int
    {
        return $this->month;
    }

    public function day(): int
    {
        return $this->day;
    }

    public function monthEnum(): Month
    {
        return Month::from($this->month);
    }

    public function weekday(): Weekday
    {
        return Weekday::from(($this->serial + CalendarTable::EPOCH_WEEKDAY) % 7);
    }

    public function dayOfYear(): int
    {
        return $this->serial - CalendarTable::YEAR_START[$this->year - CalendarTable::MIN_YEAR] + 1;
    }

    public function monthLength(): int
    {
        return Calendar::monthLength($this->year, $this->month);
    }

    public function yearLength(): int
    {
        $index = $this->year - CalendarTable::MIN_YEAR;

        return CalendarTable::YEAR_START[$index + 1] - CalendarTable::YEAR_START[$index];
    }

    public function serial(): int
    {
        return $this->serial;
    }

    public function toDateTime(): DateTimeImmutable
    {
        [$y, $m, $d] = Calendar::civilFromDays($this->serial + CalendarTable::EPOCH_UNIX_DAYS);

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $y, $m, $d));
    }

    public function lastDayOfMonth(): self
    {
        $length = $this->monthLength();

        return self::fromParts($this->year, $this->month, $length, $this->serial - $this->day + $length);
    }

    /** @return array{0: int, 1: int, 2: int} */
    public function toArray(): array
    {
        return [$this->year, $this->month, $this->day];
    }

    // --- Clock-relative (ND21-ND23) ---

    public function isToday(DateTimeZone $zone): bool
    {
        return $this->equals(self::today($zone));
    }

    public function isYesterday(DateTimeZone $zone): bool
    {
        return $this->equals(self::today($zone)->addDays(-1));
    }

    public function isTomorrow(DateTimeZone $zone): bool
    {
        return $this->equals(self::today($zone)->addDays(1));
    }

    // --- Comparison (ND24, ND26, ND29-ND34) ---

    public function compareTo(self $other): int
    {
        return $this->serial <=> $other->serial;
    }

    public function equals(self $other): bool
    {
        return $this->serial === $other->serial;
    }

    // --- Arithmetic (NA01-NA05) ---

    public function addDays(int $days): self
    {
        $serial = $this->serial + $days;
        if ($serial < 0 || $serial > CalendarTable::MAX_SERIAL) {
            throw new NepDateException(ErrorKind::OutOfRange, "day offset {$days} is outside the range");
        }

        return self::fromSerial($serial);
    }

    public function addMonths(int $months, MonthOverflow $overflow = MonthOverflow::Clamp): self
    {
        $total = ($this->year - CalendarTable::MIN_YEAR) * 12 + ($this->month - 1) + $months;
        $totalMonths = CalendarTable::YEARS * 12;
        if ($total < 0 || $total >= $totalMonths) {
            throw new NepDateException(ErrorKind::OutOfRange, "month offset {$months} is outside the range");
        }

        $year = CalendarTable::MIN_YEAR + intdiv($total, 12);
        $month = $total % 12 + 1;
        $length = Calendar::monthLength($year, $month);

        if ($this->day <= $length) {
            return self::fromBs($year, $month, $this->day);
        }

        if ($overflow === MonthOverflow::Spill) {
            if ($month === 12) {
                $year++;
                $month = 1;
            } else {
                $month++;
            }
            if ($year > CalendarTable::MAX_YEAR) {
                throw new NepDateException(ErrorKind::OutOfRange, "month offset {$months} spills past the range");
            }
            $day = $this->day - $length;

            return self::fromBs($year, $month, $day);
        }

        return self::fromBs($year, $month, $length);
    }

    public function addYears(int $years, MonthOverflow $overflow = MonthOverflow::Clamp): self
    {
        return $this->addMonths($years * 12, $overflow);
    }

    public function daysUntil(self $other): int
    {
        return $other->serial - $this->serial;
    }

    public function diff(self $other): DateDiff
    {
        if ($this->compareTo($other) <= 0) {
            $a = $this;
            $b = $other;
            $negative = false;
        } else {
            $a = $other;
            $b = $this;
            $negative = true;
        }

        $months = ($b->year - $a->year) * 12 + ($b->month - $a->month);
        $anchor = $a->addMonths($months);
        if ($anchor->compareTo($b) > 0) {
            $months--;
            $anchor = $a->addMonths($months);
        }

        return new DateDiff(
            negative: $negative,
            years: intdiv($months, 12),
            months: $months % 12,
            days: $b->serial - $anchor->serial,
            totalDays: $this->daysUntil($other),
        );
    }

    // --- Fiscal year (NY01-NY10, dispatched from FiscalYear) ---

    public function fiscalYear(): FiscalYear
    {
        return FiscalYear::containing($this);
    }

    public function quarter(): Quarter
    {
        return match (true) {
            $this->month >= 4 && $this->month <= 6 => Quarter::Q1,
            $this->month >= 7 && $this->month <= 9 => Quarter::Q2,
            $this->month >= 10 && $this->month <= 12 => Quarter::Q3,
            default => Quarter::Q4,
        };
    }

    // --- Formatting (NF01-NF06) ---

    public function __toString(): string
    {
        return Format::default($this->year, $this->month, $this->day, Lang::En);
    }

    public function isoformat(): string
    {
        return Format::iso($this->year, $this->month, $this->day, Lang::En);
    }

    public function toShortString(
        DateOrder $order = DateOrder::Ymd,
        Separator $separator = Separator::Slash,
        bool $pad = true,
        Lang $lang = Lang::En,
    ): string {
        return Format::short($this->year, $this->month, $this->day, $order, $separator, $pad, $lang);
    }

    public function toLongString(
        bool $weekday = false,
        bool $year = true,
        bool $pad = true,
        Lang $lang = Lang::En,
    ): string {
        return Format::long(
            $this->year,
            $this->month,
            $this->day,
            $weekday ? ($this->serial + CalendarTable::EPOCH_WEEKDAY) % 7 : null,
            $year,
            $pad,
            $lang,
        );
    }

    public function format(string $pattern, Lang $lang = Lang::En): string
    {
        return Pattern::format($pattern, $this->year, $this->month, $this->day, $lang);
    }
}
