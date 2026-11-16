<?php

declare(strict_types=1);

namespace NepDate\Tests\Unit;

use DateTimeImmutable;
use DateTimeZone;
use NepDate\ErrorKind;
use NepDate\Internal\Calendar;
use NepDate\Internal\CalendarTable;
use NepDate\Lang;
use NepDate\MonthOverflow;
use NepDate\NepaliDate;
use NepDate\NepDateException;
use PHPUnit\Framework\TestCase;

/**
 * The shortcuts of ADR-0008 must give the same answers as the plain definitions: the month start
 * table, the conversion through the timestamp, the digit tables and the canonical parse.
 */
final class FastPathsTest extends TestCase
{
    public function testTheMonthStartTableHasEveryMonthAndEndsAtTheTotal(): void
    {
        self::assertCount(CalendarTable::MONTHS + 1, CalendarTable::MONTH_START);
        self::assertSame(CalendarTable::YEARS * 12, CalendarTable::MONTHS);
        self::assertSame(0, CalendarTable::MONTH_START[0]);
        self::assertSame(CalendarTable::TOTAL_DAYS, CalendarTable::MONTH_START[CalendarTable::MONTHS]);
        for ($index = 0; $index < CalendarTable::MONTHS; $index++) {
            $length = CalendarTable::MONTH_START[$index + 1] - CalendarTable::MONTH_START[$index];
            self::assertGreaterThanOrEqual(29, $length);
            self::assertLessThanOrEqual(32, $length);
        }
    }

    public function testEverySerialMapsToItsMonthAndBack(): void
    {
        $index = 0;
        for ($serial = 0; $serial <= CalendarTable::MAX_SERIAL; $serial++) {
            if ($serial === CalendarTable::MONTH_START[$index + 1]) {
                $index++;
            }
            self::assertSame($index, Calendar::monthIndexOf($serial));
            [$year, $month, $day] = Calendar::fromSerial($serial);
            self::assertSame(CalendarTable::MIN_YEAR + intdiv($index, 12), $year);
            self::assertSame($index % 12 + 1, $month);
            self::assertSame($serial - CalendarTable::MONTH_START[$index] + 1, $day);
            self::assertSame($serial, Calendar::serialOf($year, $month, $day));
        }
        self::assertSame(CalendarTable::MONTHS - 1, $index);
    }

    public function testEveryDayConvertsThroughTheGregorianDateAndBack(): void
    {
        $utc = new DateTimeZone('UTC');
        $epoch = new DateTimeImmutable('1844-04-11', $utc);
        $date = NepaliDate::min();
        for ($serial = 0; $serial <= CalendarTable::MAX_SERIAL; $serial += 7) {
            $gregorian = $epoch->modify("+{$serial} days");
            $converted = NepaliDate::fromGregorian($gregorian);
            self::assertSame($serial, $converted->serial());
            self::assertSame($gregorian->format('Y-m-d'), $converted->toDateTime()->format('Y-m-d'));
            self::assertTrue($converted->equals($date->addDays($serial)));
        }
    }

    public function testFromGregorianUsesTheWallClockOfTheValuesOwnZone(): void
    {
        $expected = NepaliDate::fromBs(2081, 4, 15);
        foreach (['UTC', 'Asia/Kathmandu', 'America/Los_Angeles', 'Pacific/Kiritimati', '+05:45', '-11:00'] as $zone) {
            foreach (['00:00:00', '00:00:01', '12:30:00', '23:59:59'] as $time) {
                $value = new DateTimeImmutable("2024-07-30 {$time}", new DateTimeZone($zone));
                self::assertTrue($expected->equals(NepaliDate::fromGregorian($value)), "{$zone} {$time}");
            }
        }
    }

    public function testFromGregorianFloorsInstantsBeforeTheUnixEpoch(): void
    {
        $utc = new DateTimeZone('UTC');
        foreach (['1969-12-31', '1950-06-15', '1900-01-01', '1844-04-11'] as $day) {
            $expected = NepaliDate::fromGregorian(new DateTimeImmutable("{$day} 00:00:00", $utc));
            foreach (['00:00:01', '12:00:00', '23:59:59'] as $time) {
                $value = new DateTimeImmutable("{$day} {$time}", $utc);
                self::assertTrue($expected->equals(NepaliDate::fromGregorian($value)), "{$day} {$time}");
            }
        }
        self::assertSame('1901-01-01', NepaliDate::fromGregorian(new DateTimeImmutable('1844-04-11', $utc))->isoformat());
    }

    public function testFromGregorianRejectsTheFirstDayOutsideOnBothSides(): void
    {
        $utc = new DateTimeZone('UTC');
        foreach (['1844-04-10 23:59:59', '2143-04-16 00:00:00', '0900-01-01', '9000-01-01'] as $text) {
            try {
                NepaliDate::fromGregorian(new DateTimeImmutable($text, $utc));
                self::fail("{$text} was accepted");
            } catch (NepDateException $error) {
                self::assertSame(ErrorKind::OutOfRange, $error->kind());
            }
        }
        self::assertSame('2199-12-30', NepaliDate::fromGregorian(new DateTimeImmutable('2143-04-15 23:59:59', $utc))->isoformat());

        try {
            NepaliDate::fromGregorian(new DateTimeImmutable('2143-04-16', $utc));
            self::fail('2143-04-16 was accepted');
        } catch (NepDateException $error) {
            self::assertStringContainsString('2143-4-16', $error->getMessage());
        }
    }

    public function testToDateTimeIsMidnightInTheDefaultZoneAndFollowsAChangeOfIt(): void
    {
        $before = date_default_timezone_get();
        try {
            foreach (['Asia/Kathmandu', 'UTC', 'America/New_York', 'Asia/Kathmandu'] as $zone) {
                date_default_timezone_set($zone);
                $value = NepaliDate::fromBs(2081, 4, 15)->toDateTime();
                self::assertSame('2024-07-30 00:00:00', $value->format('Y-m-d H:i:s'));
                self::assertSame($zone, $value->getTimezone()->getName());
                self::assertEquals(new DateTimeImmutable('2024-07-30'), $value);
            }
        } finally {
            date_default_timezone_set($before);
        }
    }

    public function testEveryDayPrintsThePaddedNumbersInBothScripts(): void
    {
        $digits = [
            '0' => '०', '1' => '१', '2' => '२', '3' => '३', '4' => '४',
            '5' => '५', '6' => '६', '7' => '७', '8' => '८', '9' => '९',
        ];
        $nepali = static fn (string $text): string => strtr($text, $digits);
        for ($serial = 0; $serial <= CalendarTable::MAX_SERIAL; $serial += 3) {
            $date = NepaliDate::min()->addDays($serial);
            $expected = sprintf('%04d-%02d-%02d', $date->year(), $date->month(), $date->day());
            self::assertSame($expected, $date->isoformat());
            self::assertSame(str_replace('-', '/', $expected), (string) $date);
            self::assertSame($nepali(str_replace('-', '/', $expected)), $date->toShortString(lang: Lang::Ne));
            self::assertSame(
                $nepali("{$date->year()}/{$date->month()}/{$date->day()}"),
                $date->toShortString(pad: false, lang: Lang::Ne),
            );
            self::assertSame("{$date->year()}/{$date->month()}/{$date->day()}", $date->toShortString(pad: false));
            self::assertSame(
                $nepali(sprintf('%02d %02d', $date->year() % 100, $date->day())),
                $date->format('yy dd', Lang::Ne),
            );
            self::assertSame(sprintf('%02d %d', $date->year() % 100, $date->month()), $date->format('yy M'));
            self::assertTrue($date->equals(NepaliDate::parse($expected)));
        }
    }

    public function testTheCanonicalParseAgreesWithTheScannerOnEveryShape(): void
    {
        $expected = NepaliDate::fromBs(2081, 4, 15);
        foreach (['/', '-', '.', '_', '\\', ' ', '|'] as $separator) {
            self::assertTrue($expected->equals(NepaliDate::parse("2081{$separator}04{$separator}15")));
        }
        // Ten bytes that are not the canonical shape take the scanner and still parse or fail as before.
        self::assertTrue($expected->equals(NepaliDate::parse('2081/4//15')));
        self::assertTrue($expected->equals(NepaliDate::parse(' 2081 4 15')));
        self::assertTrue(NepaliDate::fromBs(2081, 4, 1)->equals(NepaliDate::parse('2081/04/1 ')));
        self::assertSame(ErrorKind::InvalidCharacter, self::parseKind('2081/04/1x'));
        self::assertSame(ErrorKind::InvalidCharacter, self::parseKind('2081x04/15'));
        self::assertSame(ErrorKind::InvalidCharacter, self::parseKind('2081/04x15'));
        self::assertSame(ErrorKind::InvalidCharacter, self::parseKind('208a/04/15'));
        self::assertSame(ErrorKind::InvalidCharacter, self::parseKind('2081/0a/15'));
        self::assertSame(ErrorKind::NumberTooLong, self::parseKind('20811/4/15'));
        self::assertSame(ErrorKind::WrongGroupCount, self::parseKind('2081/4/1/5'));
        self::assertSame(ErrorKind::InvalidMonth, self::parseKind('2081/13/15'));
        self::assertSame(ErrorKind::InvalidDay, self::parseKind('2081/04/33'));
        self::assertSame(ErrorKind::OutOfRange, self::parseKind('1900/01/01'));
        self::assertNull(NepaliDate::tryParse('2081/04/33'));
    }

    public function testSpillCarriesTheExtraDaysAndStopsAtTheEndOfTheCalendar(): void
    {
        $date = NepaliDate::fromBs(2081, 4, 32);
        self::assertSame('2081-07-02', $date->addMonths(2, MonthOverflow::Spill)->isoformat());
        self::assertSame(
            NepaliDate::fromBs(2081, 7, 2)->serial(),
            $date->addMonths(2, MonthOverflow::Spill)->serial(),
        );
        self::assertSame('2081-06-30', $date->addMonths(2)->isoformat());

        $yearEnd = NepaliDate::fromBs(2080, 11, 30);
        $spilled = $yearEnd->addMonths(1, MonthOverflow::Spill);
        self::assertTrue($spilled->equals(NepaliDate::fromBs($spilled->year(), $spilled->month(), $spilled->day())));

        $last = NepaliDate::fromBs(2199, 11, 30);
        self::assertSame('2199-12-30', $last->addMonths(1)->isoformat());
        self::assertSame('2199-12-30', $last->addMonths(1, MonthOverflow::Spill)->isoformat());

        // The last month has 30 days, so day 31 has nowhere to spill to.
        $longMonth = NepaliDate::fromBs(2199, 1, 31);
        self::assertSame('2199-12-30', $longMonth->addMonths(11)->isoformat());
        try {
            $longMonth->addMonths(11, MonthOverflow::Spill);
            self::fail('the spill past the calendar was accepted');
        } catch (NepDateException $error) {
            self::assertSame(ErrorKind::OutOfRange, $error->kind());
        }
    }

    private static function parseKind(string $text): ErrorKind
    {
        try {
            NepaliDate::parse($text);
        } catch (NepDateException $error) {
            return $error->kind();
        }

        self::fail("{$text} was accepted");
    }
}
