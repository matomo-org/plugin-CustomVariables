<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomVariables\tests\System;

use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Plugins\CustomVariables\tests\Fixtures\TwoVisitsWithCustomVariables;
use Piwik\Version;

/**
 * Tests use of custom variable segments.
 *
 * @group Plugins
 * @group CustomVariables
 * @group TwoVisitsWithCustomVariablesSegmentContainsTest
 */
class TwoVisitsWithCustomVariablesSegmentContainsTest extends SystemTestCase
{
    public static $fixture = null; // initialized below class definition

    public static function getOutputPrefix()
    {
        return 'twoVisitsWithCustomVariables';
    }

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }

    public function getApiForTesting()
    {
        $idSite   = self::$fixture->idSite;
        $dateTime = self::$fixture->dateTime;

        $return = [];
        $api    = ['VisitsSummary.get'];

        if (version_compare(Version::VERSION, '4.13.1-rc1', '>=')) {
            // goals for pages reports had been changed in 4.13.1, so we don't perform this tests before
            $api[] = 'Actions.getPageUrls';
            $api[] = 'Actions.getPageTitles';
        }

        $segmentsToTest = [
            // array( SegmentString , TestSuffix , Array of API to test)
            ["pageTitle=@*_)%", '_SegmentPageTitleContainsStrangeCharacters', ['VisitsSummary.get']],
            ["pageUrl=@user/profile", '_SegmentPageUrlContains', $api],
            ["pageTitle=@Profile pa", '_SegmentPageTitleContains', $api],
            ["pageUrl!@user/profile", '_SegmentPageUrlExcludes', $api],
            ["pageTitle!@Profile pa", '_SegmentPageTitleExcludes', $api],
        ];

        if (version_compare(Version::VERSION, '4.13.1-rc1', '>=')) {
            // goals for pages reports had been changed in 4.13.1, so we don't perform this tests before

            $segmentsToTest[] = ["pageTitle=@*_)%", '_SegmentPageTitleContainsStrangeCharacters', ['Actions.getPageTitles']];

            // starts with
            $segmentsToTest[] = ['pageUrl=^example.org/home', '_SegmentPageUrlStartsWith', ['Actions.getPageUrls']];
            $segmentsToTest[] = ['pageTitle=^Profile pa', '_SegmentPageTitleStartsWith', ['Actions.getPageTitles']];

            // ends with
            $segmentsToTest[] = ['pageUrl=$er/profile', '_SegmentPageUrlEndsWith', ['Actions.getPageUrls']];
            $segmentsToTest[] = ['pageTitle=$page', '_SegmentPageTitleEndsWith', ['Actions.getPageTitles']];
        }

        foreach ($segmentsToTest as $segment) {
            // Also test "Page URL / Page title CONTAINS string" feature
            $return[] = [
                $segment[2],
                [
                    'idSite'       => $idSite,
                    'date'         => $dateTime,
                    'periods'      => ['day'],
                    'setDateLastN' => false,
                    'segment'      => $segment[0],
                    'testSuffix'   => $segment[1],
                ],
            ];
        }
        return $return;
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

TwoVisitsWithCustomVariablesSegmentContainsTest::$fixture                    = new TwoVisitsWithCustomVariables();
TwoVisitsWithCustomVariablesSegmentContainsTest::$fixture->doExtraQuoteTests = false;
