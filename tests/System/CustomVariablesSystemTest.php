<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomVariables\tests\System;

use Piwik\Plugins\CustomVariables\tests\Fixtures\VisitWithManyCustomVariables;
use Piwik\Tests\Framework\TestCase\SystemTestCase;
use Piwik\Version;

/**
 * @group CustomVariables
 * @group CustomVariablesSystemTest
 * @group Plugins
 */
class CustomVariablesSystemTest extends SystemTestCase
{
    /**
     * @var VisitWithManyCustomVariables
     */
    public static $fixture = null; // initialized below class definition

    public static function getOutputPrefix()
    {
        return 'CustomVariablesSystemTest';
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
        $apiToCall = ['CustomVariables.getCustomVariables', 'Live.getLastVisitsDetails'];

        $xmlFieldsToRemove = [];

        if (version_compare(Version::VERSION, '4.12.0-rc1', '<')) {
            // those fields have been added to goal details
            $xmlFieldsToRemove = [
                'referrerType',
                'referrerName',
                'referrerKeyword',
            ];
        }

        return [
            [
                $apiToCall,
                [
                    'idSite'            => self::$fixture->idSite,
                    'date'              => self::$fixture->dateTime,
                    'periods'           => ['day'],
                    'xmlFieldsToRemove' => $xmlFieldsToRemove,
                ],
            ],
        ];
    }

    /**
     * Path where expected/processed output files are stored.
     */
    public static function getPathToTestDirectory()
    {
        return __DIR__;
    }
}

CustomVariablesSystemTest::$fixture = new VisitWithManyCustomVariables();