<?php

namespace Celest\Temporal\Misc;

use Celest\Chrono\IsoChronology;
use Celest\Format\ResolverStyle;
use Celest\Helper\Math;
use Celest\LocalDate;
use Celest\Locale;
use Celest\Temporal\ChronoField;
use Celest\Temporal\ChronoUnit;
use Celest\Temporal\IsoFields;
use Celest\Temporal\Temporal;
use Celest\Temporal\TemporalAccessor;
use Celest\Temporal\TemporalField;
use Celest\Temporal\UnsupportedTemporalTypeException;
use Celest\Temporal\ValueRange;

class DayOfQuarter implements TemporalField
{
    public function getBaseUnit()
    {
        return ChronoUnit::DAYS();
    }

    public function getRangeUnit()
    {
        return IsoFields::QUARTER_YEARS();
    }

    public function range()
    {
        return ValueRange::ofVariable(1, 90, 92);
    }

    public function isSupportedBy(TemporalAccessor $temporal)
    {
        return $temporal->isSupported(ChronoField::DAY_OF_YEAR()) && $temporal->isSupported(ChronoField::MONTH_OF_YEAR()) &&
        $temporal->isSupported(ChronoField::YEAR()) && isIso($temporal);
    }

    public
    function rangeRefinedBy(TemporalAccessor $temporal)
    {
        if ($this->isSupportedBy($temporal) == false) {
            throw new UnsupportedTemporalTypeException("Unsupported field: DayOfQuarter");
        }
        $qoy = $temporal->getLong(IsoFields::QUARTER_OF_YEAR());
        if ($qoy == 1) {
            $year = $temporal->getLong(ChronoField::YEAR());
            return (IsoChronology::INSTANCE()->isLeapYear($year) ? ValueRange::of(1, 91) : ValueRange::of(1, 90));
        } else if ($qoy == 2) {
            return ValueRange::of(1, 91);
        } else if ($qoy == 3 || $qoy == 4) {
            return ValueRange::of(1, 92);
        } // else value not from 1 to 4, so drop through
        return $this->range();
    }

    public function getFrom(TemporalAccessor $temporal)
    {
        if ($this->isSupportedBy($temporal) == false) {
            throw new UnsupportedTemporalTypeException("Unsupported field: DayOfQuarter");
        }

        $doy = $temporal->get(ChronoField::DAY_OF_YEAR());
        $moy = $temporal->get(ChronoField::MONTH_OF_YEAR());
        $year = $temporal->getLong(ChronoField::YEAR());
        return $doy - self::QUARTER_DAYS[Math::div(($moy - 1), 3) + (IsoChronology::INSTANCE()->isLeapYear($year) ? 4 : 0)];
    }

    public function adjustInto(Temporal $temporal, $newValue)
    {
        // calls getFrom() to check if supported
        $curValue = $this->getFrom($temporal);
        $this->range()->checkValidValue($newValue, $this);  // leniently check from 1 to 92 TODO: check
        return $temporal->with(ChronoField::DAY_OF_YEAR(), $temporal->getLong(ChronoField::DAY_OF_YEAR()) + ($newValue - $curValue));
    }

    public function resolve(array $fieldValues, TemporalAccessor $partialTemporal, ResolverStyle $resolverStyle)
    {
        $yearLong = @$fieldValues[ChronoField::YEAR()->__toString()];
        $qoyLong = @$fieldValues[IsoFields::QUARTER_OF_YEAR()->__toString()];
        if ($yearLong == null || $qoyLong == null) {
            return null;
        }

        $y = ChronoField::YEAR()->checkValidIntValue($yearLong);  // always validate
        $doq = $fieldValues[IsoFields::DAY_OF_QUARTER()->__toString()];
        IsoFields::ensureIso($partialTemporal);
        if ($resolverStyle == ResolverStyle::LENIENT()) {
            $date = LocalDate::ofNumerical($y, 1, 1)->plusMonths(Math::multiplyExact(Math::subtractExact($qoyLong, 1), 3));
            $doq = Math::subtractExact($doq, 1);
        } else {
            $qoy = IsoFields::QUARTER_OF_YEAR()->range()->checkValidIntValue($qoyLong, IsoFields::QUARTER_OF_YEAR());  // validated
            $date = LocalDate::ofNumerical($y, (($qoy - 1) * 3) + 1, 1);
            if ($doq < 1 || $doq > 90) {
                if ($resolverStyle == ResolverStyle::STRICT()) {
                    $this->rangeRefinedBy($date)->checkValidValue($doq, $this);  // only allow exact range
                } else {  // SMART
                    $this->range()->checkValidValue($doq, $this);  // allow 1-92 rolling into next quarter
                }
            }
            $doq--;
        }
        unset($fieldValues[$this->__toString()]);
        unset($fieldValues[ChronoField::YEAR()->__toString()]);
        unset($fieldValues[IsoFields::QUARTER_OF_YEAR()->__toString()]);
        return $date->plusDays($doq);
    }

    public function __toString()
    {
        return "DayOfQuarter";
    }

    const QUARTER_DAYS = [0, 90, 181, 273, 0, 91, 182, 274];


    public function getDisplayName(Locale $locale)
    {
        return $this->__toString();
    }

    public function isDateBased()
    {
        return true;
    }

    public function isTimeBased()
    {
        return false;
    }
}