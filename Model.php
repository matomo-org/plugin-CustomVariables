<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomVariables;

use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\DataAccess\TableMetadata;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Log\LoggerInterface;

class Model
{
    const DEFAULT_CUSTOM_VAR_COUNT = 5;

    const SCOPE_PAGE = 'page';
    const SCOPE_VISIT = 'visit';
    const SCOPE_CONVERSION = 'conversion';

    private $scope = null;
    private $table = null;
    private $tableUnprefixed = null;

    public function __construct($scope)
    {
        if (empty($scope) || !in_array($scope, $this->getScopes())) {
            throw new \Exception('Invalid custom variable scope');
        }

        $this->scope = $scope;
        $this->tableUnprefixed = $this->getTableNameFromScope($scope);
        $this->table = Common::prefixTable($this->tableUnprefixed);
    }

    public function getUnprefixedTableName()
    {
        return $this->tableUnprefixed;
    }

    private function getTableNameFromScope($scope)
    {
        // actually we should have a class for each scope but don't want to overengineer it for now
        switch ($scope) {
            case self::SCOPE_PAGE:
                return 'log_link_visit_action';
            case self::SCOPE_VISIT:
                return 'log_visit';
            case self::SCOPE_CONVERSION:
                return 'log_conversion';
        }
    }

    public function getScope()
    {
        return $this->scope;
    }

    public function getScopeName()
    {
        return ucfirst($this->scope);
    }

    public function getScopeDescription()
    {
        switch ($this->scope) {
            case Model::SCOPE_PAGE:
                return Piwik::translate('CustomVariables_ScopePage');
            case Model::SCOPE_VISIT:
                return Piwik::translate('CustomVariables_ScopeVisit');
            case Model::SCOPE_CONVERSION:
                return Piwik::translate('CustomVariables_ScopeConversion');
        }

        return ucfirst($this->scope);
    }

    /**
     * @see getHighestCustomVarIndex()
     * @return int
     */
    public function getCurrentNumCustomVars()
    {
        $indexes = $this->getCustomVarIndexes();

        return count($indexes);
    }

    /**
     * result of getHighestCustomVarIndex() can be different to getCurrentNumCustomVars() in case there are some missing
     * custom variable indexes. For instance in case of manual changes on the DB
     *
     * custom_var_v1
     * custom_var_v2
     * custom_var_v4
     *
     * getHighestCustomVarIndex() -> returns 4
     * getCurrentNumCustomVars() -> returns 3
     *
     * @return int
     */
    public function getHighestCustomVarIndex()
    {
        $indexes = $this->getCustomVarIndexes();

        if (empty($indexes)) {
            return 0;
        }

        return max($indexes);
    }

    public function getCustomVarIndexes()
    {
        $columns = $this->getCustomVarColumnNames();

        if (empty($columns)) {
            return array();
        }

        $indexes = array_map(function ($column) {
            return Model::getCustomVariableIndexFromFieldName($column);
        }, $columns);

        return array_values(array_unique($indexes));
    }

    private function getCustomVarColumnNames()
    {
        $tableMetadataAccess = new TableMetadata();
        $columns = $tableMetadataAccess->getColumns($this->table);

        $customVarColumns = array_filter($columns, function ($column) {
            return false !== strpos($column, 'custom_var_');
        });

        return $customVarColumns;
    }

    public function removeCustomVariable()
    {
        $index = $this->getHighestCustomVarIndex();

        if ($index < 1) {
            return null;
        }

        Db::exec(sprintf('ALTER TABLE %s ', $this->table)
               . sprintf('DROP COLUMN custom_var_k%d,', $index)
               . sprintf('DROP COLUMN custom_var_v%d;', $index));

        return $index;
    }

    public function addCustomVariable()
    {
        $index  = $this->getHighestCustomVarIndex() + 1;
        $maxLen = CustomVariables::getMaxLengthCustomVariables();

        Db::exec(sprintf('ALTER TABLE %s ', $this->table)
               . sprintf('ADD COLUMN custom_var_k%d VARCHAR(%d) DEFAULT NULL,', $index, $maxLen)
               . sprintf('ADD COLUMN custom_var_v%d VARCHAR(%d) DEFAULT NULL;', $index, $maxLen));

        return $index;
    }

    public static function getCustomVariableIndexFromFieldName($fieldName)
    {
        $onlyNumber = str_replace(array('custom_var_k', 'custom_var_v'), '', $fieldName);

        if (is_numeric($onlyNumber)) {
            return (int) $onlyNumber;
        }
    }

    public static function getScopes()
    {
        return array(self::SCOPE_PAGE, self::SCOPE_VISIT, self::SCOPE_CONVERSION);
    }

    public static function install()
    {
        foreach (self::getScopes() as $scope) {
            $model = new Model($scope);

            try {
                $maxCustomVars   = self::DEFAULT_CUSTOM_VAR_COUNT;
                $customVarsToAdd = $maxCustomVars - $model->getCurrentNumCustomVars();

                for ($index = 0; $index < $customVarsToAdd; $index++) {
                    $model->addCustomVariable();
                }
            } catch (\Exception $e) {
                StaticContainer::get(LoggerInterface::class)->error('Failed to add custom variable: {exception}', [
                    'exception' => $e,
                    'ignoreInScreenWriter' => true,
                ]);
            }
        }
    }

    public static function uninstall()
    {
        foreach (self::getScopes() as $scope) {
            $model = new Model($scope);

            while ($model->getHighestCustomVarIndex()) {
                $model->removeCustomVariable();
            }
        }
    }

}

