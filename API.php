<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Plugins\CustomVariables;

use Piwik\API\Request;
use Piwik\Archive;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Metrics;
use Piwik\Piwik;

/**
 * Exposes reporting API endpoints for Custom Variables names, values, and slot usage.
 *
 * @method static \Piwik\Plugins\CustomVariables\API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * @param int $idSite
     * @param string $period
     * @param Date $date
     * @param string $segment
     * @param bool $expanded
     * @param int $idSubtable
     *
     * @return DataTable|DataTable\Map
     */
    protected function getDataTable($idSite, $period, $date, $segment, $expanded, $flat, $idSubtable)
    {
        $dataTable = Archive::createDataTableFromArchive(Archiver::CUSTOM_VARIABLE_RECORD_NAME, $idSite, $period, $date, $segment, $expanded, $flat, $idSubtable);
        $dataTable->queueFilter('ColumnDelete', 'nb_uniq_visitors');

        if ($flat) {
            $dataTable->filterSubtables('Sort', array(Metrics::INDEX_NB_ACTIONS, 'desc', $naturalSort = false, $expanded));
            $dataTable->queueFilterSubtables('ColumnDelete', 'nb_uniq_visitors');
        }

        return $dataTable;
    }

    /**
     * Returns custom variable names for a site.
     *
     * @param int|string|int[] $idSite Website ID(s) to query.
     *                         - Single site ID (e.g. 1)
     *                         - Multiple site IDs (e.g. [1, 4, 5])
     *                         - Comma-separated list ("1,4,5") or "all"
     * @param 'day'|'week'|'month'|'year'|'range' $period The period to process, processes data for the period
     *                                                    containing the specified date.
     * @param string $date The date or date range to process.
     *                     'YYYY-MM-DD', magic keywords (today, yesterday, lastWeek, lastMonth, lastYear),
     *                     or date range (ie, 'YYYY-MM-DD,YYYY-MM-DD', lastX, previousX).
     * @param string|false|null $segment Custom segment to filter the report.
     *                                   Example: "referrerName==example.com"
     *                                   Supports AND (;) and OR (,) operators.
     * @param bool $expanded Whether to return subtables as nested data.
     * @param bool $_leavePiwikCoreVariables Whether to keep Matomo reserved custom variable rows.
     * @param bool $flat Whether to flatten subtables into a single table.
     * @return DataTable|DataTable\Map Custom variable names report for the requested site selection and period.
     */
    public function getCustomVariables($idSite, $period, $date, $segment = false, $expanded = false, $_leavePiwikCoreVariables = false, $flat = false)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $dataTable = $this->getDataTable($idSite, $period, $date, $segment, $expanded, $flat, $idSubtable = null);

        if (
            $dataTable instanceof DataTable
            && !$_leavePiwikCoreVariables
        ) {
            $mapping = self::getReservedCustomVariableKeys();
            foreach ($mapping as $name) {
                $row = $dataTable->getRowFromLabel($name);
                if ($row) {
                    $dataTable->deleteRow($dataTable->getRowIdFromLabel($name));
                }
            }
        }


        if ($flat) {
            $dataTable->filterSubtables('Piwik\Plugins\CustomVariables\DataTable\Filter\CustomVariablesValuesFromNameId');
        } else {
            $dataTable->filter('AddSegmentByLabel', array('customVariableName'));
        }

        return $dataTable;
    }

    /**
     * @ignore
     * @return array
     */
    public static function getReservedCustomVariableKeys()
    {
        // Note: _pk_scat and _pk_scount has been used for site search, but aren't in use anymore
        return array('_pks', '_pkn', '_pkc', '_pkp', '_pk_scat', '_pk_scount');
    }

    /**
     * Returns custom variable values for a specific custom variable name row.
     *
     * @param int|string|int[] $idSite Website ID(s) to query.
     *                         - Single site ID (e.g. 1)
     *                         - Multiple site IDs (e.g. [1, 4, 5])
     *                         - Comma-separated list ("1,4,5") or "all"
     * @param 'day'|'week'|'month'|'year'|'range' $period The period to process, processes data for the period
     *                                                    containing the specified date.
     * @param string $date The date or date range to process.
     *                     'YYYY-MM-DD', magic keywords (today, yesterday, lastWeek, lastMonth, lastYear),
     *                     or date range (ie, 'YYYY-MM-DD,YYYY-MM-DD', lastX, previousX).
     * @param int|string|false $idSubtable Subtable ID to load, 'all' to load all subtables, or false for root.
     * @param string|false|null $segment Custom segment to filter the report.
     *                                   Example: "referrerName==example.com"
     *                                   Supports AND (;) and OR (,) operators.
     * @param bool $_leavePriceViewedColumn Whether to keep the `price_viewed` column instead of renaming it to `price`.
     * @return DataTable|DataTable\Map Custom variable values report for the requested custom variable name row.
     */
    public function getCustomVariablesValuesFromNameId($idSite, $period, $date, $idSubtable, $segment = false, $_leavePriceViewedColumn = false)
    {
        Piwik::checkUserHasViewAccess($idSite);

        $dataTable = $this->getDataTable($idSite, $period, $date, $segment, $expanded = false, $flat = false, $idSubtable);

        if (!$_leavePriceViewedColumn) {
            $dataTable->deleteColumn('price_viewed');
        } else {
            // Hack Ecommerce product price tracking to display correctly
            $dataTable->renameColumn('price_viewed', 'price');
        }

        $dataTable->filter('Piwik\Plugins\CustomVariables\DataTable\Filter\CustomVariablesValuesFromNameId');

        return $dataTable;
    }

    /**
     * Returns all custom variable slots and the names used in each slot since the beginning of the website.
     *
     * @param int $idSite The numeric ID of the website to query.
     *
     * @return array<int, array{
     *     scope: string,
     *     index: int,
     *     usages: array<int, array{name: mixed, nb_visits: mixed, nb_actions: mixed}>
     * }> List of custom variable slot usages grouped by scope and index.
     */
    public function getUsagesOfSlots($idSite)
    {
        Piwik::checkUserHasAdminAccess($idSite);

        $numVars = CustomVariables::getNumUsableCustomVariables();

        $usedCustomVariables = array(
            'visit' => array_fill(1, $numVars, array()),
            'page'  => array_fill(1, $numVars, array()),
        );

        /** @var DataTable $customVarUsages */
        $today = StaticContainer::get('CustomVariables.today');
        $date = '2008-12-12,' . $today;
        $customVarUsages = Request::processRequest(
            'CustomVariables.getCustomVariables',
            array('idSite' => $idSite, 'period' => 'range', 'date' => $date,
                  'format' => 'original')
        );

        foreach ($customVarUsages->getRows() as $row) {
            $slots = $row->getMetadata('slots');

            if (!empty($slots)) {
                foreach ($slots as $slot) {
                    $usedCustomVariables[$slot['scope']][$slot['index']][] = array(
                        'name' => $row->getColumn('label'),
                        'nb_visits' => $row->getColumn('nb_visits'),
                        'nb_actions' => $row->getColumn('nb_actions'),
                    );
                }
            }
        }

        $grouped = array();
        foreach ($usedCustomVariables as $scope => $scopes) {
            foreach ($scopes as $index => $cvars) {
                $grouped[] = array(
                    'scope' => $scope,
                    'index' => $index,
                    'usages' => $cvars
                );
            }
        }

        return $grouped;
    }
}
