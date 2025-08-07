<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomVariables\tests\System;

use Piwik\Archive\ArchivePurger;
use Piwik\Archive\Chunk;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Date;
use Piwik\Db;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Plugins\CustomVariables\tests\Fixtures\TwoVisitsWithCustomVariables;
use Piwik\Version;

/**
 * Tests use of custom variable segments.
 *
 * @group Plugins
 * @group CustomVariables
 * @group TwoVisitsWithCustomVariablesSegmentMatchVisitorTypeTest
 */
class TwoVisitsWithCustomVariablesSegmentMatchVisitorTypeTest extends SystemTestCase
{
    public static $fixture = null; // initialized below class definition

    protected function setUp(): void
    {
        // the time of day appears to influence how many archives are created...
        Date::$now = strtotime('2020-08-01 03:00:00');

        parent::setUp();
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
        // Segment matching some
        $segments = array('customVariableName1==VisitorType;customVariableValue1==LoggedIn',
                          'customVariableName1==VisitorType;customVariableValue1=@LoggedI');

        $apiToCall = array('Referrers.getKeywords', 'CustomVariables.getCustomVariables', 'VisitsSummary.get');

        $periods = array('day', 'week');

        // We run it twice just to check that running archiving twice for same input parameters doesn't create more records/overhead
        $result = array();
        for ($i = 1; $i <= 2; $i++) {
            foreach ($segments as $segment) {
                $result[] = array(
                    $apiToCall, array('idSite'       => 'all',
                                      'date'         => self::$fixture->dateTime,
                                      'periods'      => $periods,
                                      'setDateLastN' => true,
                                      'segment'      => $segment)
                );
            }
        }

        return $result;
    }

    /**
     * @depends testApi
     */
    public function testCheck()
    {
        if (version_compare(Version::VERSION, '5.4.0-b3', '<')) {
            self::markTestSkipped('archive numbers have changed');
        }

        // TODO: if we do this in archivewriter, we don't need this code
        $archivePurger = StaticContainer::get(ArchivePurger::class);
        $archivePurger->purgeInvalidatedArchivesFrom(Date::factory(self::$fixture->dateTime));
        $archivePurger->purgeInvalidatedArchivesFrom(Date::factory('2009-12-01'));

        // ----------------------------------------------
        // Implementation Checks
        // ----------------------------------------------
        // Verify that, when a segment is specified, only the requested report is processed
        // In this case, check that only the Custom Variables blobs have been processed

        $tests = array(
            // 1) CHECK 'day' archive stored in January
            // We expect 2 segments
            //   * (1 custom variable name + 2 ref metrics
            //      + 1 subtable chunk for the custom var values + 7 Referrers blob (2 of them subtables)
            //   )
            'archive_blob_2010_01' => 22,
            // This contains all 'last N' weeks & days,
            // (8 metrics
            //  + 6 referrer metrics
            //  + 3 done flag )
            //  * 2 segments
            // for each "Last N" date that has data (just one date)
            'archive_numeric_2010_01' => 34,

            // 2) CHECK 'week' archive stored in December (week starts the month before)
            // We expect 2 segments * (2 custom variable name + 2 ref metrics + 1 subtable chunk for the values of the name + 7 referrers blob (2 of them subtables))
            'archive_blob_2009_12' => 22,
            // 8 metrics,
            // 6 Referrer metrics,
            // 3 done flag (referrers, CustomVar, VisitsSummary), all for period = 2, day w/ visits is in new year, other days have no data
            // X * 2 segments
            'archive_numeric_2009_12' => (8 + 6 + 3) * 2,
        );

        foreach ($tests as $table => $expectedRows) {
            $sql = "SELECT count(*) FROM " . Common::prefixTable($table);
            $countBlobs = Db::get()->fetchOne($sql);

            $this->assertEquals($expectedRows, $countBlobs, "$table: %s");
        }
    }

    /**
     *  Check that it merges all subtables into one blob entry
     *
     * @depends      testApi
     */
    public function test_checkArchiveRecords_shouldMergeSubtablesIntoOneRow()
    {
        if (version_compare(Version::VERSION, '5.4.0-b3', '<')) {
            self::markTestSkipped('archive numbers have changed');
        }

        $chunk = new Chunk();

        $tests = array(
            'archive_blob_2010_01' => array(
                $chunk->getRecordNameForTableId('CustomVariables_valueByName', 0) => 6,
                $chunk->getRecordNameForTableId('Referrers_keywordBySearchEngine', 0) => 1,
                $chunk->getRecordNameForTableId('Referrers_searchEngineByKeyword', 0) => 1
            ),
            'archive_blob_2009_12' => array(
                $chunk->getRecordNameForTableId('CustomVariables_valueByName', 0) => 6,
                $chunk->getRecordNameForTableId('Referrers_keywordBySearchEngine', 0) => 1,
                $chunk->getRecordNameForTableId('Referrers_searchEngineByKeyword', 0) => 1,
            )
        );
        $numTests = 0;
        foreach ($tests as $table => $expectedSubtables) {
            foreach ($expectedSubtables as $name => $expectedNumSubtables) {
                $sql   = "SELECT `value` FROM " . Common::prefixTable($table) . " WHERE `name` ='$name'";
                $blobs = Db::get()->fetchAll($sql);

                foreach ($blobs as $blob) {
                    $numTests++;
                    $blob = $blob['value'];
                    $blob = gzuncompress($blob);
                    $blob = unserialize($blob);

                    $countSubtables = count($blob);

                    $this->assertEquals($expectedNumSubtables, $countSubtables, "$name in $table expected to contain $expectedNumSubtables subtables, got $countSubtables");
                }
            }
        }

        // 6 _subtables entries + 6 _subtables entries for the segment + 1 other for CustomVariables_valueByName_chunk_0_99
        $this->assertEquals(12, $numTests, "$numTests were executed but expected 12");
    }

    public static function getOutputPrefix()
    {
        return 'twoVisitsWithCustomVariables_segmentMatchVisitorType';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

TwoVisitsWithCustomVariablesSegmentMatchVisitorTypeTest::$fixture = new TwoVisitsWithCustomVariables();
TwoVisitsWithCustomVariablesSegmentMatchVisitorTypeTest::$fixture->doExtraQuoteTests = false;
