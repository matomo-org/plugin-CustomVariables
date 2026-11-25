<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\CustomVariables\tests\Integration;

use Piwik\API\Request;
use Piwik\DataTable;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group CustomVariables
 * @group RecordBuilderTest
 * @group Plugins
 */
class RecordBuilderTest extends IntegrationTestCase
{
    private const DATE = '2026-10-01';

    public function setUp(): void
    {
        parent::setUp();

        Fixture::createSuperUser();
        Fixture::createWebsite('2026-01-01 00:00:00');
    }

    /**
     * @dataProvider getCategoryLists
     *
     * @param list<string> $expectedLabels
     */
    public function test_aggregate_archivesEcommerceCategoryListValuesAsStrings(string $categoryList, array $expectedLabels): void
    {
        $tracker = Fixture::getTracker(1, self::DATE . ' 10:00:00');
        $tracker->setCustomVariable(5, '_pkc', $categoryList, 'page');
        Fixture::checkResponse($tracker->doTrackPageView('Category page'));

        $report = Request::processRequest('CustomVariables.getCustomVariables', [
            'idSite' => 1,
            'period' => 'day',
            'date' => self::DATE,
            'expanded' => 1,
            '_leavePiwikCoreVariables' => 1,
        ]);
        $this->assertInstanceOf(DataTable::class, $report);

        $categories = $report->getRowFromLabel('_pkc')->getSubtable();
        $this->assertInstanceOf(DataTable::class, $categories);
        $labels = $categories->getColumn('label');
        sort($labels);
        $this->assertSame($expectedLabels, $labels);
    }

    public function getCategoryLists(): iterable
    {
        yield 'mixed values' => ['["books",2026,"sale"]', ['2026', 'books', 'sale']];
        yield 'empty values do not count towards the limit' => ['["","a","b","c","d","e","f"]', ['a', 'b', 'c', 'd', 'e']];
    }
}
