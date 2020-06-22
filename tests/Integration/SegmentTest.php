<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomVariables\tests\Integration;

use Piwik\Common;
use Piwik\Db;
use Piwik\Segment;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group CustomVariables
 * @group SegmentTest
 * @group Plugins
 */
class SegmentTest extends IntegrationTestCase
{
    public function getCommonTestData()
    {
        return [
            // test multiple column segments
            ['customVariableName==abc;customVariableValue==def', [
                'where' => ' (log_visit.custom_var_k1 = ? OR log_visit.custom_var_k2 = ? OR log_visit.custom_var_k3 = ? OR log_visit.custom_var_k4 = ? OR log_visit.custom_var_k5 = ?) AND (log_visit.custom_var_v1 = ? OR log_visit.custom_var_v2 = ? OR log_visit.custom_var_v3 = ? OR log_visit.custom_var_v4 = ? OR log_visit.custom_var_v5 = ? )',
                'bind' => [
                    'abc', 'abc', 'abc', 'abc', 'abc',
                    'def', 'def', 'def', 'def', 'def',
                ],
            ]]
        ];
    }

    /**
     * @dataProvider getCommonTestData
     */
    public function testCommon($segment, $expected)
    {
        $select = 'log_visit.idvisit';
        $from = 'log_visit';

        $expected = array(
            'sql'  => '
                SELECT
                    log_visit.idvisit
                FROM
                    ' . Common::prefixTable('log_visit') . ' AS log_visit
                WHERE
                    ' . $expected['where'],
            'bind' => $expected['bind']
        );

        $segment = new Segment($segment, $idSites = array());
        $sql = $segment->getSelectQuery($select, $from, false);
        $this->assertQueryDoesNotFail($sql);

        $this->assertEquals($this->removeExtraWhiteSpaces($expected), $this->removeExtraWhiteSpaces($sql));

        // calling twice should give same results
        $sql = $segment->getSelectQuery($select, array($from));
        $this->assertEquals($this->removeExtraWhiteSpaces($expected), $this->removeExtraWhiteSpaces($sql));

        $this->assertEquals(32, strlen($segment->getHash()));
    }

    private function assertQueryDoesNotFail($query)
    {
        Db::fetchAll($query['sql'], $query['bind']);
        $this->assertTrue(true);
    }

    private function removeExtraWhiteSpaces($valueToFilter)
    {
        if (is_array($valueToFilter)) {
            foreach ($valueToFilter as $key => $value) {
                $valueToFilter[$key] = $this->removeExtraWhiteSpaces($value);
            }
            return $valueToFilter;
        } else {
            return preg_replace('/[\s]+/', ' ', $valueToFilter);
        }
    }
}