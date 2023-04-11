<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\CustomVariables\Commands;

use Piwik\Common;
use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\CustomVariables\CustomVariables;
use Piwik\Plugins\CustomVariables\Model;

/**
 */
class Info extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('customvariables:info');
        $this->setDescription('Get info about configured custom variables');
    }

    protected function doExecute(): int
    {
        $output = $this->getOutput();
        $maxVars = CustomVariables::getNumUsableCustomVariables();

        if ($this->hasEverywhereSameAmountOfVariables()) {
            $this->writeSuccessMessage([
                'Your Matomo is configured for ' . $maxVars . ' custom variables.'
            ]);
            return self::SUCCESS;
        }

        $output->writeln('<error>There is a problem with your custom variables configuration:</error>');
        $output->writeln('<error>Some database tables miss custom variables columns.</error>');
        $output->writeln('');
        $output->writeln('Your Matomo seems to be configured for ' . $maxVars . ' custom variables.');
        $output->writeln('Executing "<comment>./console customvariables:set-max-custom-variables ' . $maxVars . '</comment>" might fix this issue.');
        $output->writeln('If not check the following tables whether they have the same columns starting with <comment>custom_var_</comment>: ');
        foreach (Model::getScopes() as $scope) {
            $output->writeln(Common::prefixTable($scope));
        }

        return self::SUCCESS;
    }

    private function hasEverywhereSameAmountOfVariables()
    {
        $indexesBefore = null;

        foreach (Model::getScopes() as $scope) {
            $model   = new Model($scope);
            $indexes = $model->getCustomVarIndexes();

            if (is_null($indexesBefore)) {
                $indexesBefore = $indexes;
            } elseif ($indexes != $indexesBefore) {
                return false;
            }
        }

        return true;
    }
}
