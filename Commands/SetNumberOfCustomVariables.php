<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\CustomVariables\Commands;

use Piwik\Plugin\ConsoleCommand;
use Piwik\Plugins\CustomVariables\Model;
use Piwik\Tracker\Cache;

/**
 */
class SetNumberOfCustomVariables extends ConsoleCommand
{
    protected function configure()
    {
        $this->setName('customvariables:set-max-custom-variables');
        $this->setDescription('Change the number of available custom variables');
        $this->setHelp("Example:
./console customvariables:set-max-custom-variables 10
=> 10 custom variables will be available in total
");
        $this->addRequiredArgument('maxCustomVars', 'Set the number of max available custom variables');
    }

    protected function doExecute(): int
    {
        $input = $this->getInput();
        $output = $this->getOutput();
        $numVarsToSet = $this->getNumVariablesToSet();
        $numChangesToPerform = $this->getNumberOfChangesToPerform($numVarsToSet);

        if (0 === $numChangesToPerform) {
            $this->writeSuccessMessage([
                'Your Matomo is already configured for ' . $numVarsToSet . ' custom variables.'
            ]);
            return 0;
        }

        $output->writeln('');
        $output->writeln(sprintf('Configuring Matomo for %d custom variables', $numVarsToSet));

        foreach (Model::getScopes() as $scope) {
            $this->printChanges($scope, $numVarsToSet);
        }

        if ($input->isInteractive() && !$this->confirmChange()) {
            return 0;
        }

        $output->writeln('');
        $output->writeln('Starting to apply changes');
        $output->writeln('');

        $this->initProgressBar($numChangesToPerform);

        foreach (Model::getScopes() as $scope) {
            $this->performChange($scope, $numVarsToSet);
        }

        Cache::clearCacheGeneral();
        $this->finishProgressBar();

        $this->writeSuccessMessage([
            'Your Matomo is now configured for ' . $numVarsToSet . ' custom variables.'
        ]);

        return 0;
    }

    private function performChange($scope, $numVarsToSet)
    {
        $model = new Model($scope);
        $numCurrentVars = $model->getCurrentNumCustomVars();
        $numDifference  = $this->getAbsoluteDifference($numCurrentVars, $numVarsToSet);

        if ($numVarsToSet > $numCurrentVars) {
            $this->addCustomVariables($model, $numDifference);
            return;
        }

        $this->removeCustomVariables($model, $numDifference);
    }

    private function getNumVariablesToSet(): int
    {
        $maxCustomVars = $this->getInput()->getArgument('maxCustomVars');

        if (!is_numeric($maxCustomVars)) {
            throw new \InvalidArgumentException('The number of available custom variables has to be a number');
        }

        $maxCustomVars = (int) $maxCustomVars;

        if ($maxCustomVars < 5) {
            throw new \InvalidArgumentException('There has to be at least five custom variables');
        }

        return $maxCustomVars;
    }

    private function confirmChange()
    {
        $this->getOutput()->writeln('');
        return $this->askForConfirmation(
            '<question>Are you sure you want to perform these actions? (y/N)</question>',
            false
        );
    }

    private function printChanges($scope, $numVarsToSet)
    {
        $output               = $this->getOutput();
        $model                = new Model($scope);
        $scopeName            = $model->getScopeName();
        $highestIndex         = $model->getHighestCustomVarIndex();
        $numCurrentCustomVars = $model->getCurrentNumCustomVars();
        $numVarsDifference    = $this->getAbsoluteDifference($numCurrentCustomVars, $numVarsToSet);

        $output->writeln('');
        $output->writeln(sprintf('Scope "%s"', $scopeName));

        if ($numVarsToSet > $numCurrentCustomVars) {
            $indexes = $highestIndex + 1;
            if (1 !== $numVarsDifference) {
                $indexes .= ' - ' . ($highestIndex + $numVarsDifference);
            }

            $output->writeln(
                sprintf('%s new custom variables having the index(es) %s will be ADDED', $numVarsDifference, $indexes)
            );
        } elseif ($numVarsToSet < $numCurrentCustomVars) {
            $indexes = $highestIndex - $numVarsDifference + 1;

            if (1 !== $numVarsDifference) {
                $indexes .= ' - ' . $highestIndex;
            }

            $output->writeln(
                sprintf("%s existing custom variables having the index(es) %s will be REMOVED.", $numVarsDifference, $indexes)
            );
            $output->writeln('<comment>This is an irreversible change</comment>');
        }
    }

    private function getAbsoluteDifference(int $currentNumber, int $numberToSet): int
    {
        return abs($numberToSet - $currentNumber);
    }

    private function removeCustomVariables(Model $model, $numberOfVarsToRemove)
    {
        for ($index = 0; $index < $numberOfVarsToRemove; $index++) {
            $indexRemoved = $model->removeCustomVariable();
            $this->advanceProgressBar();
            $this->getOutput()->writeln('  <info>Removed a variable in scope "' . $model->getScopeName() .  '" having the index ' . $indexRemoved . '</info>');
        }
    }

    private function addCustomVariables(Model $model, $numberOfVarsToAdd)
    {
        for ($index = 0; $index < $numberOfVarsToAdd; $index++) {
            $indexAdded = $model->addCustomVariable();
            $this->advanceProgressBar();
            $this->getOutput()->writeln('  <info>Added a variable in scope "' . $model->getScopeName() .  '" having the index ' . $indexAdded . '</info>');
        }
    }

    private function getNumberOfChangesToPerform(int $numVarsToSet): int
    {
        $numChangesToPerform = 0;

        foreach (Model::getScopes() as $scope) {
            $model = new Model($scope);
            $numCurrentCustomVars = $model->getCurrentNumCustomVars();
            $numChangesToPerform += $this->getAbsoluteDifference($numCurrentCustomVars, $numVarsToSet);
        }

        return $numChangesToPerform;
    }
}
