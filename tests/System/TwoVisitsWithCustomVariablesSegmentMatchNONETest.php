<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomVariables\tests\System;

use Piwik\Columns\Dimension;
use Piwik\Plugins\API\tests\System\AutoSuggestAPITest;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Plugins\CustomVariables\tests\Fixtures\TwoVisitsWithCustomVariables;
use Piwik\Version;

/**
 * testing a segment containing all supported fields
 *
 * @group Plugins
 * @group CustomVariables
 * @group TwoVisitsWithCustomVariablesSegmentMatchNONETest
 */
class TwoVisitsWithCustomVariablesSegmentMatchNONETest extends SystemTestCase
{
    public static $fixture = null; // initialized below class definition

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        if (!array_key_exists('segment', $params)) {
            $params['segment'] = $this->getSegmentToTest(); // this method can access the DB, so we get it here instead of the data provider
        }

        $this->runApiTests($api, $params);
    }

    public function getApiForTesting()
    {
        // we will test all segments from all plugins
        $apiToCall = array('VisitsSummary.get', 'CustomVariables.getCustomVariables');

        if (version_compare(Version::VERSION, '5.4.0-b3', '<')) {
            // archive numbers changed, so we ignore VisitsSummary
            $apiToCall = ['CustomVariables.getCustomVariables'];
        }

        return array(
            array($apiToCall, array('idSite'       => 'all',
                                    'date'         => self::$fixture->dateTime,
                                    'periods'      => array('day', 'week'),
                                    'setDateLastN' => true))
        );
    }

    public function getSegmentToTest()
    {
        $segments = AutoSuggestAPITest::getSegmentsMetadata();

        $minimumExpectedSegmentsCount = 55; // as of Piwik 1.12
        $this->assertGreaterThan($minimumExpectedSegmentsCount, count($segments));
        $segmentExpression = array();

        $temporalSegmentValues = self::getTemporalSegmentValues();

        $seenVisitorId = false;
        foreach ($segments as $segment) {
            // Date/time segments can't be compared against an arbitrary string: MySQL 8.0 rejects
            // e.g. "visitEndServerDate != 'campaign'" as an invalid DATE value (5.7 tolerated it).
            // Give them a valid, type-appropriate value (a date for DATE() segments, a number for
            // the HOUR()/MINUTE()/YEAR()/... integer extractions) so they stay covered.
            $value = $temporalSegmentValues[$segment] ?? 'campaign';
            if ($segment == 'visitorId') {
                $seenVisitorId = true;
                $value = '34c31e04394bdc63';
            }
            if ($segment == 'visitEcommerceStatus') {
                $value = 'none';
            }
            if ($segment == 'actionType') {
                $value = 'pageviews';
            }
            if ($segment == 'fingerprint') {
                $value = 'abcdef1234567890';        //Needs to be a valid 16-char hex string
            }
            $matchNone = $segment . '!=' . $value;

            // deviceType != campaign matches ALL visits, but we want to match None
            if ($segment == 'deviceType') {
                $matchNone = $segment . '==car%20browser';
            }

            if ($segment == 'deviceBrand') {
                $matchNone = $segment . '==Yarvik';
            }

            $segmentExpression[] = $matchNone;
        }

        $segment = implode(";", $segmentExpression);

        // just checking that this segment was tested (as it has the only visible to admin flag)
        $this->assertTrue($seenVisitorId);
        $this->assertGreaterThan(100, strlen($segment));

        return $segment;
    }

    public static function getOutputPrefix()
    {
        return 'twoVisitsWithCustomVariables_segmentMatchNONE';
    }

    /**
     * Returns a valid comparison value for every non-internal segment backed by a date/time typed
     * dimension, keyed by segment name.
     *
     * These segments map to SQL that expects either a date (e.g. DATE(...)) or an integer (the
     * HOUR()/MINUTE()/YEAR()/... extractions), so an arbitrary string like "campaign" produces an
     * invalid DATE value error on MySQL 8.0. The returned values are valid for the respective
     * comparison.
     */
    private static function getTemporalSegmentValues(): array
    {
        $temporalTypes = [
            Dimension::TYPE_DATE,
            Dimension::TYPE_DATETIME,
            Dimension::TYPE_TIME,
            Dimension::TYPE_TIMESTAMP,
        ];

        $values = [];
        foreach (Dimension::getAllDimensions() as $dimension) {
            if (!in_array($dimension->getType(), $temporalTypes, true)) {
                continue;
            }

            foreach ($dimension->getSegments() as $segment) {
                $isIntegerExtraction = (bool) preg_match(
                    '/^\s*(HOUR|MINUTE|SECOND|DAYOFWEEK|DAYOFMONTH|DAYOFYEAR|WEEKOFYEAR|WEEK|MONTH|QUARTER|YEAR)\s*\(/i',
                    $segment->getSqlSegment()
                );
                $values[$segment->getSegment()] = $isIntegerExtraction ? '99' : '2099-12-31';
            }
        }

        return $values;
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

TwoVisitsWithCustomVariablesSegmentMatchNONETest::$fixture = new TwoVisitsWithCustomVariables();
TwoVisitsWithCustomVariablesSegmentMatchNONETest::$fixture->doExtraQuoteTests = false;
