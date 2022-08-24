<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\CustomVariables\tests\Commands;

use Piwik\Plugins\CustomVariables\Commands\SetNumberOfCustomVariables;
use Piwik\Plugins\CustomVariables\CustomVariables;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group CustomVariables
 * @group CustomVariablesTest
 * @group Plugins
 * @group Plugins
 */
class SetNumberOfCustomVariablesTest extends IntegrationTestCase
{
    public function testExecuteShouldThrowExceptionIfArgumentIsMissing()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Not enough arguments');

        $this->executeCommand(null);
    }

    public function testExecuteShouldThrowExceptionHasToBeANumber()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The number of available custom variables has to be a number');

        $this->executeCommand('a');
    }

    public function testExecuteShouldThrowExceptionMinimum2CustomVarsRequired()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('There has to be at least five custom variables');

        $this->executeCommand(4);
    }

    public function testExecuteShouldThrowExceptionIfUserCancelsConfirmation()
    {
        $result = $this->executeCommand(7, false);
        $this->assertStringEndsWith('Are you sure you want to perform these actions? (y/N)', $result);
    }

    public function testExecuteShouldDoNothingIfExpectedResultIsAlreadyTheCase()
    {
        $result = $this->executeCommand(5);

        self::assertStringContainsString('Your Matomo is already configured for 5 custom variables', $result);
    }

    public function testExecuteShouldAddMaxCustomVarsIfNumberIsHigherThanActual()
    {
        $this->assertEquals(5, CustomVariables::getNumUsableCustomVariables());

        $result = $this->executeCommand(6);

        self::assertStringContainsString('Configuring Matomo for 6 custom variables', $result);
        self::assertStringContainsString('1 new custom variables having the index(es) 6 will be ADDED', $result);
        self::assertStringContainsString('Starting to apply changes', $result);
        self::assertStringContainsString('Added a variable in scope "Page" having the index 6', $result);
        self::assertStringContainsString('Added a variable in scope "Visit" having the index 6', $result);
        self::assertStringContainsString('Added a variable in scope "Conversion" having the index 6', $result);
        self::assertStringContainsString('Your Matomo is now configured for 6 custom variables.', $result);

        $this->assertEquals(6, CustomVariables::getNumUsableCustomVariables());
    }

    public function testExecuteShouldRemoveMaxCustomVarsIfNumberIsLessThanActual()
    {
        $this->executeCommand(6, true);
        $this->assertEquals(6, CustomVariables::getNumUsableCustomVariables());

        $result = $this->executeCommand(5);

        self::assertStringContainsString('Configuring Matomo for 5 custom variables', $result);
        self::assertStringContainsString('1 existing custom variables having the index(es) 6 will be REMOVED.', $result);
        self::assertStringContainsString('Starting to apply changes', $result);
        self::assertStringContainsString('Removed a variable in scope "Page" having the index 6', $result);
        self::assertStringContainsString('Removed a variable in scope "Visit" having the index 6', $result);
        self::assertStringContainsString('Removed a variable in scope "Conversion" having the index 6', $result);
        self::assertStringContainsString('Your Matomo is now configured for 5 custom variables.', $result);

        $this->assertEquals(5, CustomVariables::getNumUsableCustomVariables());
    }

    public function testExecuteAddMultipleRemoveMultiple()
    {
        $this->assertEquals(5, CustomVariables::getNumUsableCustomVariables());

        $this->executeCommand(9);
        $this->assertEquals(9, CustomVariables::getNumUsableCustomVariables());

        $this->executeCommand(6);
        $this->assertEquals(6, CustomVariables::getNumUsableCustomVariables());
    }

    /**
     * @param int|null $maxCustomVars
     * @param bool  $confirm
     *
     * @return string
     */
    private function executeCommand($maxCustomVars, $confirm = true)
    {
        $setNumberCmd = new SetNumberOfCustomVariables();

        $application = new Application();
        $application->add($setNumberCmd);

        $commandTester = new CommandTester($setNumberCmd);
        $commandTester->setInputs([($confirm ? 'yes' : 'no') . '\n']);

        if (is_null($maxCustomVars)) {
            $params = [];
        } else {
            $params = ['maxCustomVars' => $maxCustomVars];
        }

        $params['command'] = $setNumberCmd->getName();
        $commandTester->execute($params);
        $result = $commandTester->getDisplay();

        return $result;
    }

    protected function getInputStream($input)
    {
        $stream = fopen('php://memory', 'r+', false);
        fputs($stream, $input);
        rewind($stream);

        return $stream;
    }
}
