<?php

declare(strict_types=1);

namespace NepDate;

/**
 * A fiscal quarter: Q1 Shrawan-Ashoj through Q4 Baishakh-Ashad of the following year.
 */
enum Quarter: int
{
    case Q1 = 1;
    case Q2 = 2;
    case Q3 = 3;
    case Q4 = 4;
}
