<?php

declare(strict_types=1);

namespace NepDate\Bench;

use NepDate\Lang;
use NepDate\MonthOverflow;
use NepDate\NepaliDate;
use NepDate\NepaliDateRange;
use PhpBench\Attributes as Bench;

use function NepDate\Patro\info;

/**
 * The PHPBench front end of `shared/spec/BENCHMARK.md`: B1 to B8, one iteration per operation and
 * 64 operations per iteration, so the report's time per iteration is 64 × the time per operation.
 *
 *   vendor/bin/phpbench run bench/CoreBench.php --report=table
 *
 * `bench/run.php` is the protocol harness that divides by the input count and prints the table of
 * `docs/benchmarks.md`; this class is what a change to the library is regression-tested against.
 */
final class CoreBench
{
    /** @var list<NepaliDate> */
    private array $dates = [];

    /** @var list<string> */
    private array $texts = [];

    /** @var list<\DateTimeImmutable> */
    private array $gregorian = [];

    /** @var list<NepaliDate> */
    private array $patroDates = [];

    public function setUp(): void
    {
        $this->dates = Harness::dates();
        $this->texts = Harness::texts();
        $this->gregorian = Harness::gregorianDates();
        $this->patroDates = Harness::patroDates();
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1)]
    #[Bench\Iterations(5)]
    public function benchB1BsToAd(): void
    {
        foreach ($this->dates as $date) {
            $date->toDateTime()->format('Y-m-d');
        }
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1)]
    #[Bench\Iterations(5)]
    public function benchB2AdToBs(): void
    {
        foreach ($this->gregorian as $date) {
            NepaliDate::fromGregorian($date)->serial();
        }
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1)]
    #[Bench\Iterations(5)]
    public function benchB3AddDays(): void
    {
        foreach ($this->dates as $date) {
            $date->addDays(1000)->isoformat();
        }
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1)]
    #[Bench\Iterations(5)]
    public function benchB4AddMonths(): void
    {
        foreach ($this->dates as $date) {
            $date->addMonths(13, MonthOverflow::Clamp)->isoformat();
        }
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1)]
    #[Bench\Iterations(5)]
    public function benchB5LongNepali(): void
    {
        foreach ($this->dates as $date) {
            $date->toLongString(weekday: true, lang: Lang::Ne);
        }
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1)]
    #[Bench\Iterations(5)]
    public function benchB6Parse(): void
    {
        foreach ($this->texts as $text) {
            NepaliDate::parse($text)->serial();
        }
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1)]
    #[Bench\Iterations(5)]
    public function benchB7PatroInfo(): void
    {
        foreach ($this->patroDates as $date) {
            $entry = info($date);
            $entry->tithi()?->en;
            foreach ($entry->events() as $event) {
                $event->en;
            }
        }
    }

    #[Bench\BeforeMethods('setUp')]
    #[Bench\Revs(1)]
    #[Bench\Iterations(5)]
    public function benchB8PatroMonth(): void
    {
        foreach ($this->patroDates as $date) {
            foreach (NepaliDateRange::forMonth($date->year(), $date->month()) as $day) {
                info($day)->tithi()?->en;
            }
        }
    }
}
