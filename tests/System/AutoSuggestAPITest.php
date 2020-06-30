<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomVariables\tests\System;

use Piwik\API\Request;
use Piwik\ArchiveProcessor\Rules;
use Piwik\Cache as PiwikCache;
use Piwik\Common;
use Piwik\Date;
use Piwik\Option;
use Piwik\Plugins\API\API;
use Piwik\Plugins\CustomVariables\Model;
use Piwik\Tests\Fixtures\ManyVisitsWithGeoIPAndEcommerce;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Tracker\Cache;

/**
 * testing a the auto suggest API for all known segments
 *
 * @group CustomVariables
 * @group AutoSuggestAPITest
 * @group Plugins
 */
class AutoSuggestAPITest extends SystemTestCase
{
    public static $fixture = null; // initialized below class definition

    private static $hasArchivedData = false;

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        // Refresh cache for CustomVariables\Model
        Cache::clearCacheGeneral();

        $this->runApiTests($api, $params);
    }

    public function getApiForTesting()
    {
        $idSite = self::$fixture->idSite;
        $segments = self::getSegmentsMetadata();

        $apiForTesting = array();
        foreach ($segments as $segment) {
            $apiForTesting[] = $this->getApiForTestingForSegment($idSite, $segment);
        }

        $apiForTesting[] = array('Live.getLastVisitsDetails',
                                 array('idSite' => $idSite,
                                       'date' => '1998-07-12,today',
                                       'period' => 'range',
                                       'otherRequestParameters' => array('filter_limit' => 1000)));

        return $apiForTesting;
    }

    /**
     * @dataProvider getApiForTestingBrowserArchivingDisabled
     */
    public function testApiBrowserArchivingDisabled($api, $params)
    {
        if (!self::$hasArchivedData) {
            self::$hasArchivedData = true;
            // need to make sure data is archived before disabling the archiving
            Request::processRequest('API.get', array(
                'date' => '2018-01-10', 'period' => 'year', 'idSite' => $params['idSite'],
                'trigger' => 'archivephp'
            ));
        }

        // Refresh cache for CustomVariables\Model
        Cache::clearCacheGeneral();
        // disable browser archiving so the APIs are used
        Option::set(Rules::OPTION_BROWSER_TRIGGER_ARCHIVING, 0);

        $this->runApiTests($api, $params);

        Option::set(Rules::OPTION_BROWSER_TRIGGER_ARCHIVING, 1);
    }

    public function getApiForTestingBrowserArchivingDisabled()
    {
        $idSite = self::$fixture->idSite;
        $segments = self::getSegmentsMetadata();

        $apiForTesting = array();
        foreach ($segments as $segment) {
            $apiForTesting[] = $this->getApiForTestingForSegment($idSite, $segment);
        }

        return $apiForTesting;
    }

    /**
     * @param $idSite
     * @param $segment
     * @return array
     */
    protected function getApiForTestingForSegment($idSite, $segment)
    {
        return array('API.getSuggestedValuesForSegment',
                     array('idSite' => $idSite,
                           'testSuffix' => '_' . $segment,
                           'otherRequestParameters' => array('segmentName' => $segment)));
    }

    /**
     * @depends      testApi
     * @dataProvider getAnotherApiForTesting
     */
    public function testAnotherApi($api, $params)
    {
        // Get the top segment value
        $request = new Request(
            'method=API.getSuggestedValuesForSegment'
            . '&segmentName=' . $params['segmentToComplete']
            . '&idSite=' . $params['idSite']
            . '&format=json'
        );
        $response = json_decode($request->process(), true);
        $this->assertApiResponseHasNoError($response);
        $topSegmentValue = @$response[0];

        if (empty($topSegmentValue)) {
            $this->markTestIncomplete('No segment value available for ' . $params['segmentToComplete']);
        }

        // Now build the segment request
        $segmentValue = rawurlencode(html_entity_decode($topSegmentValue, ENT_COMPAT | ENT_HTML401, 'UTF-8'));
        $params['segment'] = $params['segmentToComplete'] . '==' . $segmentValue;
        unset($params['segmentToComplete']);
        $this->runApiTests($api, $params);
    }

    public function getAnotherApiForTesting()
    {
        $segments = self::getSegmentsMetadata();

        $apiForTesting = array();
        foreach ($segments as $segment) {
            $apiForTesting[] = array('VisitsSummary.get',
                                     array('idSite' => self::$fixture->idSite,
                                           'date' => date("Y-m-d", strtotime(self::$fixture->dateTime)) . ',today',
                                           'period' => 'range',
                                           'testSuffix' => '_' . $segment,
                                           'segmentToComplete' => $segment));
        }
        return $apiForTesting;
    }

    public static function getSegmentsMetadata()
    {
        // Refresh cache for CustomVariables\Model
        Cache::clearCacheGeneral();
        PiwikCache::getTransientCache()->flushAll();

        $segments = array();

        // add CustomVariables manually since the data provider may not have access to the DB
        for ($i = 1; $i != Model::DEFAULT_CUSTOM_VAR_COUNT + 1; ++$i) {
            $segments = array_merge($segments, self::getCustomVariableSegments($i));
        }
        $segments = array_merge($segments, self::getCustomVariableSegments());

        return $segments;
    }

    private static function getCustomVariableSegments($columnIndex = null)
    {
        $result = array(
            'customVariableName',
            'customVariableValue',
            'customVariablePageName',
            'customVariablePageValue',
        );

        if ($columnIndex !== null) {
            foreach ($result as &$name) {
                $name = $name . $columnIndex;
            }
        }

        return $result;
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

$date = mktime(0, 0, 0, 1, 1, 2018);

$lookBack = ceil((time() - $date) / 86400);

API::$_autoSuggestLookBack = $lookBack;

AutoSuggestAPITest::$fixture = new ManyVisitsWithGeoIPAndEcommerce();
AutoSuggestAPITest::$fixture->dateTime = Date::factory($date)->getDatetime();
