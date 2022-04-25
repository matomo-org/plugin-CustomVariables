/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

interface CustomVariableUsageRow {
  name: string;
  nb_actions: number|string;
  nb_visits: number|string;
}

interface CustomVariableUsage {
  index: number;
  scope: string;
  usages: CustomVariableUsageRow[];
}

export { CustomVariableUsage };
