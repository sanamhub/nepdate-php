<?php

declare(strict_types=1);

namespace NepDate\Bench;

use NepDate\Internal\Calendar;
use NepDate\NepaliDate;

use function NepDate\Patro\coverage;
use function NepDate\Patro\publicHolidays;

/**
 * The shared inputs and helpers of `shared/spec/BENCHMARK.md`: the 64 dates of section 2, the
 * patro inputs of section 3 (B7, B8), and the timing loop of section 4.
 */
final class Harness
{
    /**
     * The 64 dates of BENCHMARK.md §2: date `i` is the serial `s0 + i * ((s1 - s0) / 63)`, where
     * `s0` is BS 2000-01-01 and `s1` is BS 2090-12-30.
     *
     * @return list<NepaliDate>
     */
    public static function dates(): array
    {
        $first = NepaliDate::fromBs(2000, 1, 1)->serial();
        $last = NepaliDate::fromBs(2090, 12, 30)->serial();

        return self::spread($first, $last);
    }

    /**
     * The 64 dates of BENCHMARK.md §3 moved into the patro coverage (PATRO.md §2).
     *
     * @return list<NepaliDate>
     */
    public static function patroDates(): array
    {
        $range = coverage();

        return self::spread($range->start()->serial(), $range->end()->serial());
    }

    /**
     * The text inputs of BENCHMARK.md §2: the 64 dates as `YYYY/MM/DD`.
     *
     * @return list<string>
     */
    public static function texts(): array
    {
        return array_map(
            static fn (NepaliDate $date): string => sprintf(
                '%04d/%02d/%02d',
                $date->year(),
                $date->month(),
                $date->day(),
            ),
            self::dates(),
        );
    }

    /**
     * The Gregorian dates BEHIND the 64 BS dates, for the AD to BS benchmark.
     *
     * @return list<\DateTimeImmutable>
     */
    public static function gregorianDates(): array
    {
        return array_map(static fn (NepaliDate $date): \DateTimeImmutable => $date->toDateTime(), self::dates());
    }

    /**
     * Times one iteration of `$operation` over every input and returns the mean nanoseconds per
     * operation and the standard deviation, after a warm-up pass.
     *
     * @param callable(mixed): mixed $operation one input per call
     * @param list<mixed> $inputs
     * @return array{mean: float, stddev: float, iterations: int, count: int}
     */
    public static function measure(callable $operation, array $inputs, int $iterations = 10): array
    {
        foreach ($inputs as $input) {
            $operation($input);
        }

        $samples = [];
        for ($run = 0; $run < $iterations; $run++) {
            $start = hrtime(true);
            foreach ($inputs as $input) {
                $operation($input);
            }
            $samples[] = (hrtime(true) - $start) / count($inputs);
        }

        $mean = array_sum($samples) / count($samples);
        $variance = 0.0;
        foreach ($samples as $sample) {
            $variance += ($sample - $mean) ** 2;
        }

        return [
            'mean' => $mean,
            'stddev' => sqrt($variance / count($samples)),
            'iterations' => $iterations,
            'count' => count($inputs),
        ];
    }

    /** The number of gazetted holidays; a run touches the whole table once. */
    public static function holidayCount(): int
    {
        return count(publicHolidays());
    }

    private static function fromSerial(int $serial): NepaliDate
    {
        [$year, $month, $day] = Calendar::fromSerial($serial);

        return NepaliDate::fromBs($year, $month, $day);
    }

    /**
     * @return list<NepaliDate>
     */
    private static function spread(int $first, int $last): array
    {
        $step = intdiv($last - $first, 63);
        $dates = [];
        for ($i = 0; $i < 64; $i++) {
            $dates[] = self::fromSerial($first + $i * $step);
        }

        return $dates;
    }
}
