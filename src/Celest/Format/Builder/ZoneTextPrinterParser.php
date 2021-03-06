<?php

namespace Celest\Format\Builder;

use Celest\Format\DateTimeParseContext;
use Celest\Format\DateTimePrintContext;
use Celest\Format\DateTimeTextProvider;
use Celest\Format\TextStyle;
use Celest\Instant;
use Celest\Locale;
use Celest\Temporal\ChronoField;
use Celest\Temporal\TemporalQueries;
use Celest\Zone\ZoneRulesProvider;
use Celest\ZoneId;
use Celest\ZoneOffset;


/**
 * Prints or parses a zone ID.
 */
final class ZoneTextPrinterParser extends ZoneIdPrinterParser
{

    /** The text style to output.
     * @var TextStyle
     */
    private $textStyle;

    /** The preferred zoneid map
     * @var ZoneId[]
     */
    private $preferredZones;

    /**
     * ZoneTextPrinterParser constructor.
     * @param TextStyle $textStyle
     * @param ZoneId[] $preferredZones
     */
    public function __construct(TextStyle $textStyle, $preferredZones)
    {
        parent::__construct(TemporalQueries::zone(), "ZoneText(" . $textStyle . ")");
        $this->textStyle = $textStyle;
        if ($preferredZones !== null && count($preferredZones) !== 0) {
            $this->preferredZones = [];
            foreach ($preferredZones as $id) {
                $this->preferredZones[] = $id->getId();
            }
        }
    }

    private static $STD = 0;
    private static $DST = 1;
    private static $GENERIC = 2;

    private function getDisplayName($id, $type, Locale $locale)
    {
        if ($this->textStyle == TextStyle::NARROW()) {
            return null;
        }

        $names = DateTimeTextProvider::getZoneNames($id, $locale);

        switch ($type) {
            case self::$STD:
                return $names[$this->textStyle->zoneNameStyleIndex() . 'g'];
            case self::$DST:
                return $names[$this->textStyle->zoneNameStyleIndex() . 'd'];
        }
        return $names[$this->textStyle->zoneNameStyleIndex() . 's'];
    }

    public function format(DateTimePrintContext $context, &$buf)
    {
        /** @var ZoneID $zone */
        $zone = $context->getValue(TemporalQueries::zoneId());
        if ($zone === null) {
            return false;
        }

        $zname = $zone->getId();
        if (!($zone instanceof ZoneOffset)) {
            $dt = $context->getTemporal();
            $name = $this->getDisplayName($zname,
                $dt->isSupported(ChronoField::INSTANT_SECONDS())
                    ? ($zone->getRules()->isDaylightSavings(Instant::from($dt)) ? self::$DST : self::$STD)
                    : self::$GENERIC,
                $context->getLocale());
            if ($name !== null) {
                $zname = $name;
            }
        }
        $buf .= $zname;
        return true;
    }

    // cache per instance for now
//private final Map<Locale, Entry < Integer, SoftReference < PrefixTree >>> cachedTree = new HashMap <> ();
//private final Map<Locale, Entry < Integer, SoftReference < PrefixTree >>> cachedTreeCI = new HashMap <> ();

    protected function getTree(DateTimeParseContext $context)
    {
        if ($this->textStyle == TextStyle::NARROW()) {
            return parent::getTree($context);
        }

        $locale = $context->getLocale();
        $isCaseSensitive = $context->isCaseSensitive();
        $regionIds = ZoneRulesProvider::getAvailableZoneIds();
        $regionIdsSize = $regionIds->size();

        $cached =
            $isCaseSensitive ? self::$cachedTree : self::$cachedTreeCI;

        $entry = null;
        $tree = null;
        $zoneStrings = null;
        if (($entry = $cached->get($locale)) === null ||
            ($entry->getKey() !== $regionIdsSize ||
                ($tree = $entry->getValue()->get()) === null)
        ) {
            $tree = PrefixTree::newTree($context);
            $zoneStrings = TimeZoneNameUtility::getZoneStrings($locale);
            foreach ($zoneStrings as $names) {
                $zid = $names[0];
                if (!$regionIds->contains($zid)) {
                    continue;
                }
                $tree->add($zid, $zid);    // don't convert zid -> metazone
                $zid = ZoneName::toZid($zid, $locale);
                $i = $this->textStyle == TextStyle::FULL() ? 1 : 2;
                for (; $i < count($names); $i += 2) {
                    $tree->add($names[$i], $zid);
                }
            }
            // if we have a set of preferred zones, need a copy and
            // add the preferred zones again to overwrite
            if (self::$preferredZones !== null) {
                foreach ($zoneStrings as $names) {
                    $zid = $names[0];
                    if (!self::$preferredZones->contains($zid) || !$regionIds->contains($zid)) {
                        continue;
                    }
                    $i = $this->textStyle == TextStyle::FULL() ? 1 : 2;
                    for (; $i < count($names); $i += 2) {
                        $tree->add($names[$i], $zid);
                    }
                }
            }
            $cached->put($locale, new SimpleImmutableEntry($regionIdsSize, $tree));
        }
        return $tree;
    }
}
