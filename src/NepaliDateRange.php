<?php

declare(strict_types=1);

namespace NepDate;

use ArrayIterator;
use DateTimeZone;
use IteratorAggregate;
use Stringable;
use Traversable;

/**
 * An inclusive, non-empty span of consecutive Bikram Sambat dates (ALGORITHM.md §10, API.md NR group).
 *
 * @implements IteratorAggregate<int, NepaliDate>
 */
final class NepaliDateRange implements IteratorAggregate, Stringable
{
    private function __construct(
        private readonly NepaliDate $start,
        private readonly NepaliDate $end,
    ) {
    }

    // --- Construction ---

    public static function of(NepaliDate $start, NepaliDate $end): self
    {
        if ($start->compareTo($end) > 0) {
            throw new NepDateException(ErrorKind::OutOfRange, 'a range must not start after it ends');
        }

        return new self($start, $end);
    }

    public static function singleDay(NepaliDate $day): self
    {
        return new self($day, $day);
    }

    public static function fromDayCount(NepaliDate $start, int $days): self
    {
        if ($days < 1) {
            throw new NepDateException(ErrorKind::OutOfRange, 'a range must span at least one day');
        }

        return new self($start, $start->addDays($days - 1));
    }

    public static function forMonth(int $year, int $month): self
    {
        $start = NepaliDate::fromBs($year, $month, 1);

        return new self($start, $start->lastDayOfMonth());
    }

    public static function forYear(int $year): self
    {
        $start = NepaliDate::fromBs($year, 1, 1);
        $end = NepaliDate::fromBs($year, 12, \NepDate\Internal\Calendar::monthLength($year, 12));

        return new self($start, $end);
    }

    public static function currentMonth(DateTimeZone $zone): self
    {
        $today = NepaliDate::today($zone);

        return self::forMonth($today->year(), $today->month());
    }

    public static function currentFiscalYear(DateTimeZone $zone): self
    {
        return FiscalYear::containing(NepaliDate::today($zone))->range();
    }

    public static function currentCalendarYear(DateTimeZone $zone): self
    {
        return self::forYear(NepaliDate::today($zone)->year());
    }

    // --- Accessors ---

    public function start(): NepaliDate
    {
        return $this->start;
    }

    public function end(): NepaliDate
    {
        return $this->end;
    }

    public function count(): int
    {
        return $this->start->daysUntil($this->end) + 1;
    }

    public function equals(self $other): bool
    {
        return $this->start->equals($other->start) && $this->end->equals($other->end);
    }

    // --- Membership and relations ---

    public function contains(NepaliDate $date): bool
    {
        return $this->start->compareTo($date) <= 0 && $this->end->compareTo($date) >= 0;
    }

    public function containsRange(self $other): bool
    {
        return $this->contains($other->start) && $this->contains($other->end);
    }

    public function overlaps(self $other): bool
    {
        return $this->start->compareTo($other->end) <= 0 && $other->start->compareTo($this->end) <= 0;
    }

    public function isAdjacentTo(self $other): bool
    {
        $afterThis = $this->end->compareTo($other->start) < 0
            && $this->end->serial() + 1 <= \NepDate\Internal\CalendarTable::MAX_SERIAL
            && $this->end->addDays(1)->equals($other->start);
        $afterOther = $other->end->compareTo($this->start) < 0
            && $other->end->serial() + 1 <= \NepDate\Internal\CalendarTable::MAX_SERIAL
            && $other->end->addDays(1)->equals($this->start);

        return $afterThis || $afterOther;
    }

    // --- Set operations ---

    public function intersect(self $other): ?self
    {
        $start = $this->start->compareTo($other->start) >= 0 ? $this->start : $other->start;
        $end = $this->end->compareTo($other->end) <= 0 ? $this->end : $other->end;
        if ($start->compareTo($end) > 0) {
            return null;
        }

        return new self($start, $end);
    }

    public function union(self $other): self
    {
        $start = $this->start->compareTo($other->start) <= 0 ? $this->start : $other->start;
        $end = $this->end->compareTo($other->end) >= 0 ? $this->end : $other->end;

        return new self($start, $end);
    }

    /** @return list<self> */
    public function except(self $other): array
    {
        $overlap = $this->intersect($other);
        if ($overlap === null) {
            return [$this];
        }

        $result = [];
        if ($this->start->compareTo($overlap->start) < 0) {
            $result[] = new self($this->start, $overlap->start->addDays(-1));
        }
        if ($this->end->compareTo($overlap->end) > 0) {
            $result[] = new self($overlap->end->addDays(1), $this->end);
        }

        return $result;
    }

    // --- Splitting and iteration ---

    /** @return list<self> */
    public function splitByMonth(): array
    {
        $result = [];
        $cursor = $this->start;
        while ($cursor->compareTo($this->end) <= 0) {
            $monthEnd = $cursor->lastDayOfMonth();
            $segmentEnd = $monthEnd->compareTo($this->end) <= 0 ? $monthEnd : $this->end;
            $result[] = new self($cursor, $segmentEnd);
            if ($segmentEnd->equals($this->end)) {
                break;
            }
            $cursor = $segmentEnd->addDays(1);
        }

        return $result;
    }

    /** @return list<self> */
    public function splitByFiscalQuarter(): array
    {
        $result = [];
        $cursor = $this->start;
        while ($cursor->compareTo($this->end) <= 0) {
            $fiscalYear = FiscalYear::containing($cursor);
            $quarterEnd = $fiscalYear->quarterRange($cursor->quarter())->end;
            $segmentEnd = $quarterEnd->compareTo($this->end) <= 0 ? $quarterEnd : $this->end;
            $result[] = new self($cursor, $segmentEnd);
            if ($segmentEnd->equals($this->end)) {
                break;
            }
            $cursor = $segmentEnd->addDays(1);
        }

        return $result;
    }

    /** @return list<NepaliDate> */
    public function datesWithInterval(int $step = 1): array
    {
        if ($step < 1) {
            throw new NepDateException(ErrorKind::OutOfRange, 'the step must be at least 1');
        }
        $result = [];
        $cursor = $this->start;
        while (true) {
            $result[] = $cursor;
            $remaining = $cursor->daysUntil($this->end);
            if ($remaining < $step) {
                break;
            }
            $cursor = $cursor->addDays($step);
        }

        return $result;
    }

    /** @return list<NepaliDate> every day except Saturday, and except Sunday when `$excludeSunday` */
    public function workingDays(bool $excludeSunday = false): array
    {
        return array_values(array_filter(
            $this->datesWithInterval(),
            static function (NepaliDate $date) use ($excludeSunday): bool {
                $weekday = $date->weekday();

                return $weekday !== Weekday::Saturday && !($excludeSunday && $weekday === Weekday::Sunday);
            },
        ));
    }

    /** @return list<NepaliDate> every Saturday, and Sunday when `$includeSunday` */
    public function weekendDays(bool $includeSunday = true): array
    {
        return array_values(array_filter(
            $this->datesWithInterval(),
            static function (NepaliDate $date) use ($includeSunday): bool {
                $weekday = $date->weekday();

                return $weekday === Weekday::Saturday || ($includeSunday && $weekday === Weekday::Sunday);
            },
        ));
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->datesWithInterval());
    }

    // --- Formatting ---

    public function __toString(): string
    {
        return $this->start . ' - ' . $this->end;
    }

    public function toStringWith(
        DateOrder $order = DateOrder::Ymd,
        Separator $separator = Separator::Slash,
        Lang $lang = Lang::En,
    ): string {
        return $this->start->toShortString($order, $separator, true, $lang)
            . ' - '
            . $this->end->toShortString($order, $separator, true, $lang);
    }
}
