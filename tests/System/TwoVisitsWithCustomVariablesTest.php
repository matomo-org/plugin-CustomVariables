<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */
namespace Piwik\Plugins\CustomVariables\tests\System;

use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Tests\Fixtures\TwoVisitsWithCustomVariables;
use Piwik\Version;

/**
 * Tests w/ two visits & custom variables.
 *
 * @group TwoVisitsWithCustomVariablesTest
 * @group CustomVariables
 * @group Plugins
 */
class TwoVisitsWithCustomVariablesTest extends SystemTestCase
{
    /**
     * @var TwoVisitsWithCustomVariables
     */
    public static $fixture = null; // initialized below class definition

    public function getApiForTesting()
    {
        $idSite = self::$fixture->idSite;
        $dateTime = self::$fixture->dateTime;

        $apiToCall = array('VisitsSummary.get', 'CustomVariables.getCustomVariables');

        $return = array(
            array($apiToCall, array('idSite'       => 'all',
                                    'date'         => $dateTime,
                                    'periods'      => array('day', 'week'),
                                    'setDateLastN' => true)),

            // test w/ custom variable segments
            array('VisitsSummary.get', array(
                'idSite' => self::$fixture->idSite,
                'date' => self::$fixture->dateTime,
                'periods' => array('day'),
                'testSuffix' => '_segmentCustomVarName',
                'segment' => 'customVariablePageName=@SET WITH',
            )),

            array('VisitsSummary.get', array(
                'idSite' => self::$fixture->idSite,
                'date' => self::$fixture->dateTime,
                'periods' => array('day'),
                'testSuffix' => '_segmentCustomVarValue',
                'segment' => 'customVariableValue=@LoggedIn',
            )),

            array('VisitsSummary.get', array(
                'idSite' => self::$fixture->idSite,
                'date' => self::$fixture->dateTime,
                'periods' => array('day'),
                'testSuffix' => '_segmentAll',
                'segment' => 'customVariableName=@Othercustom,customVariablePageValue=@abcdefghi',
            )),
        );

        if (version_compare(Version::VERSION, '4.13.3', '<=')) {
            // test getProcessedReport w/ custom variables subtable
            $return[] = array('API.getProcessedReport', array(
                'idSite'        => $idSite,
                'date'          => $dateTime,
                'periods'       => 'day',
                'apiModule'     => 'CustomVariables',
                'apiAction'     => 'getCustomVariablesValuesFromNameId',
                'supertableApi' => 'CustomVariables.getCustomVariables',
                'testSuffix'    => '__subtable')
            );

        }

        return $return;
    }

    /**
     * @dataProvider getApiForTesting
     */
    public function testApi($api, $params)
    {
        $this->runApiTests($api, $params);
    }

    public static function getOutputPrefix()
    {
        return 'twoVisitsWithCustomVariables';
    }

    public static function getPathToTestDirectory()
    {
        return dirname(__FILE__);
    }
}

TwoVisitsWithCustomVariablesTest::$fixture = new TwoVisitsWithCustomVariables();