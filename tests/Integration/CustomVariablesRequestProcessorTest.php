<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\CustomVariables\tests\Integration;

use Piwik\Plugins\CustomVariables\CustomVariables;
use Piwik\Plugins\CustomVariables\Tracker\CustomVariablesRequestProcessor;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;
use Piwik\Tracker\Request;

/**
 * @group Plugins
 * @group CustomVariables
 * @group CustomVariablesRequestProcessor
 */
class CustomVariablesRequestProcessorTest extends IntegrationTestCase
{
    public function test_truncateCustomVariable_shouldNotTruncateAnything_IfValueIsShortEnough()
    {
        $len = CustomVariables::getMaxLengthCustomVariables();
        $input = str_pad('test', $len - 2, 't');

        $result = CustomVariablesRequestProcessor::truncateCustomVariable($input);

        $this->assertSame($result, $input);
    }

    public function test_truncateCustomVariable_shouldActuallyTruncateTheValue()
    {
        $len = CustomVariables::getMaxLengthCustomVariables();
        $input = str_pad('test', $len + 2, 't');

        $this->assertGreaterThan(100, $len);

        $truncated = CustomVariablesRequestProcessor::truncateCustomVariable($input);

        $this->assertEquals(str_pad('test', $len, 't'), $truncated);
    }

    public function test_getCustomVariablesInVisitScope_ShouldReturnNoCustomVars_IfNoWerePassedInParams()
    {
        $this->assertEquals(array(), CustomVariablesRequestProcessor::getCustomVariablesInVisitScope($this->buildRequest(array('idsite' => '1'))));
    }

    public function test_getCustomVariablesInVisitScope_ShouldReturnNoCustomVars_IfPassedParamIsNotAnArray()
    {
        $this->assertCustomVariablesInVisitScope(array(), '{"mykey":"myval"}');
    }

    public function test_getCustomVariablesInVisitScope_ShouldReturnCustomVars_IfTheyAreValid()
    {
        $customVars = $this->buildCustomVars(array('mykey' => 'myval', 'test' => 'value'));
        $expected   = $this->buildExpectedCustomVars(array('mykey' => 'myval', 'test' => 'value'));

        $this->assertCustomVariablesInVisitScope($expected, $customVars);
    }

    public function test_getCustomVariablesInVisitScope_ShouldIgnoreIndexesLowerThan1()
    {
        $customVars = array(
            array('mykey', 'myval'),
            array('test', 'value'),
        );
        $expected   = $this->buildExpectedCustomVars(array('test' => 'value'));

        $this->assertCustomVariablesInVisitScope($expected, json_encode($customVars));
    }

    public function test_getCustomVariablesInVisitScope_ShouldTruncateValuesIfTheyAreTooLong()
    {
        $maxLen = CustomVariables::getMaxLengthCustomVariables();

        $customVars = $this->buildCustomVars(array(
            'mykey' => 'myval',
            'test'  => str_pad('test', $maxLen + 5, 't'),
        ));
        $expected = $this->buildExpectedCustomVars(array(
            'mykey' => 'myval',
            'test'  => str_pad('test', $maxLen, 't'),
        ));

        $this->assertCustomVariablesInVisitScope($expected, $customVars);
    }

    public function test_getCustomVariablesInVisitScope_ShouldIgnoreVarsThatDoNotHaveKeyAndValue()
    {
        $customVars = array(
            1 => array('mykey', 'myval'),
            2 => array('test'),
        );
        $expected = $this->buildExpectedCustomVars(array('mykey' => 'myval'));

        $this->assertCustomVariablesInVisitScope($expected, json_encode($customVars));
    }

    public function test_getCustomVariablesInVisitScope_ShouldSetDefaultValueToEmptyStringAndHandleOtherTypes()
    {
        $input = array(
            'myfloat'  => 5.55,
            'myint'    => 53,
            'mystring' => '',
        );
        $customVars = $this->buildCustomVars($input);
        $expected   = $this->buildExpectedCustomVars($input);

        $this->assertCustomVariablesInVisitScope($expected, $customVars);
    }

    public function test_getCustomVariablesInPageScope_ShouldReturnNoCustomVars_IfNoWerePassedInParams()
    {
        $this->assertEquals(array(), CustomVariablesRequestProcessor::getCustomVariablesInPageScope($this->buildRequest(array('idsite' => '1'))));
    }

    public function test_getCustomVariablesInPageScope_ShouldReturnNoCustomVars_IfPassedParamIsNotAnArray()
    {
        $this->assertCustomVariablesInPageScope(array(), '{"mykey":"myval"}');
    }

    public function test_getCustomVariablesInPageScope_ShouldReturnCustomVars_IfTheyAreValid()
    {
        $customVars = $this->buildCustomVars(array('mykey' => 'myval', 'test' => 'value'));
        $expected   = $this->buildExpectedCustomVars(array('mykey' => 'myval', 'test' => 'value'));

        $this->assertCustomVariablesInPageScope($expected, $customVars);
    }

    public function test_getCustomVariablesInPageScope_ShouldIgnoreIndexesLowerThan1()
    {
        $customVars = array(
            array('mykey', 'myval'),
            array('test', 'value'),
        );
        $expected   = $this->buildExpectedCustomVars(array('test' => 'value'));

        $this->assertCustomVariablesInPageScope($expected, json_encode($customVars));
    }

    public function test_getCustomVariablesInPageScope_ShouldTruncateValuesIfTheyAreTooLong()
    {
        $maxLen = CustomVariables::getMaxLengthCustomVariables();

        $customVars = $this->buildCustomVars(array(
            'mykey' => 'myval',
            'test'  => str_pad('test', $maxLen + 5, 't'),
        ));
        $expected = $this->buildExpectedCustomVars(array(
            'mykey' => 'myval',
            'test'  => str_pad('test', $maxLen, 't'),
        ));

        $this->assertCustomVariablesInPageScope($expected, $customVars);
    }

    public function test_getCustomVariablesInPageScope_ShouldIgnoreVarsThatDoNotHaveKeyAndValue()
    {
        $customVars = array(
            1 => array('mykey', 'myval'),
            2 => array('test'),
        );
        $expected = $this->buildExpectedCustomVars(array('mykey' => 'myval'));

        $this->assertCustomVariablesInPageScope($expected, json_encode($customVars));
    }

    public function test_getCustomVariablesInPageScope_ShouldSetDefaultValueToEmptyStringAndHandleOtherTypes()
    {
        $input = array(
            'myfloat'  => 5.55,
            'myint'    => 53,
            'mystring' => '',
        );
        $customVars = $this->buildCustomVars($input);
        $expected   = $this->buildExpectedCustomVars($input);

        $this->assertCustomVariablesInPageScope($expected, $customVars);
    }

    public function test_getCustomVariables_nonStringInput()
    {
        $input = array('mykey' => array('myarraykey' => 'myvalue'), 'myotherkey' => 2);
        $customVars = $this->buildCustomVars($input);
        // Int value should come through; array value is invalid so should be discarded
        $expected = array('custom_var_k2' => 'myotherkey', 'custom_var_v2' => 2);

        $this->assertCustomVariablesInPageScope($expected, $customVars);
    }

    public function test_internalBuildExpectedCustomVars()
    {
        $this->assertEquals(array(), $this->buildExpectedCustomVars(array()));

        $this->assertEquals(
            array('custom_var_k1' => 'key', 'custom_var_v1' => 'val'),
            $this->buildExpectedCustomVars(array('key' => 'val'))
        );

        $this->assertEquals(array(
            'custom_var_k1' => 'key', 'custom_var_v1' => 'val',
            'custom_var_k2' => 'key2', 'custom_var_v2' => 'val2',
        ), $this->buildExpectedCustomVars(array('key' => 'val', 'key2' => 'val2')));
    }

    public function test_internalBuildCustomVars()
    {
        $this->assertEquals('[]', $this->buildCustomVars(array()));

        $this->assertEquals(
            '{"1":["key","val"]}',
            $this->buildCustomVars(array('key' => 'val'))
        );

        $this->assertEquals(
            '{"1":["key","val"],"2":["key2","val2"]}',
            $this->buildCustomVars(array('key' => 'val', 'key2' => 'val2'))
        );
    }

    private function buildRequest($params)
    {
        return new Request($params);
    }

    private function buildCustomVars($customVars)
    {
        $vars  = array();
        $index = 1;

        foreach ($customVars as $key => $value) {
            $vars[$index] = array($key, $value);
            $index++;
        }

        return json_encode($vars);
    }

    private function buildExpectedCustomVars($customVars)
    {
        $vars  = array();
        $index = 1;

        foreach ($customVars as $key => $value) {
            $vars['custom_var_k' . $index] = $key;
            $vars['custom_var_v' . $index] = $value;
            $index++;
        }

        return $vars;
    }

    private function assertCustomVariablesInVisitScope($expectedCvars, $cvarsJsonEncoded)
    {
        $request = $this->buildRequest(array('_cvar' => $cvarsJsonEncoded));
        $this->assertEquals($expectedCvars, CustomVariablesRequestProcessor::getCustomVariablesInVisitScope($request));
    }

    private function assertCustomVariablesInPageScope($expectedCvars, $cvarsJsonEncoded)
    {
        $request = $this->buildRequest(array('cvar' => $cvarsJsonEncoded));
        $this->assertEquals($expectedCvars, CustomVariablesRequestProcessor::getCustomVariablesInPageScope($request));
    }
}
