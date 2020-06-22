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

/**
 * @group Plugins
 * @group CustomVariables
 * @group TwoVisitsWithCustomVariablesSegmentMatchALLNoGoalDataTest
 */
class TwoVisitsWithCustomVariablesSegmentMatchALLNoGoalDataTest extends SystemTestCase
{
    public static $fixture = null; // initialized below class definition

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }

    public function getApiForTesting()
    {
        $apiToCall = array('VisitsSummary.get', 'CustomVariables.getCustomVariables');

        // Segment matching ALL
        // + adding DOES NOT CONTAIN segment always matched, to test this particular operator
        $resolution = self::$fixture->resolutionWidthToUse . 'x' . self::$fixture->resolutionHeightToUse;
        $segment = 'resolution==' . $resolution . ';customVariableName1!@randomvalue does not exist';

        return array(
            array($apiToCall, array('idSite'       => 'all',
                                    'date'         => self::$fixture->dateTime,
                                    'periods'      => array('day', 'week'),
                                    'setDateLastN' => true,
                                    'segment'      => $segment))
        );
    }

    public static function getOutputPrefix()
    {
        return 'twoVisitsWithCustomVariables_segmentMatchALL_noGoalData';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

TwoVisitsWithCustomVariablesSegmentMatchALLNoGoalDataTest::$fixture = new TwoVisitsWithCustomVariables();
TwoVisitsWithCustomVariablesSegmentMatchALLNoGoalDataTest::$fixture->doExtraQuoteTests = false;