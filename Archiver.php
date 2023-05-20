<?php
/**
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CustomVariables;

class Archiver extends \Piwik\Plugin\Archiver
{
    const LABEL_CUSTOM_VALUE_NOT_DEFINED = "Value not defined";
    const CUSTOM_VARIABLE_RECORD_NAME = 'CustomVariables_valueByName';
}
