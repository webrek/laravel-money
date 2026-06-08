<?php

namespace Webrek\Money;

enum RoundingMode
{
    /** Round away from zero. */
    case UP;

    /** Round toward zero (truncate). */
    case DOWN;

    /** Round toward positive infinity. */
    case CEILING;

    /** Round toward negative infinity. */
    case FLOOR;

    /** Round to nearest; ties go away from zero. */
    case HALF_UP;

    /** Round to nearest; ties go toward zero. */
    case HALF_DOWN;

    /** Round to nearest; ties go to the even neighbour (banker's rounding). */
    case HALF_EVEN;
}
