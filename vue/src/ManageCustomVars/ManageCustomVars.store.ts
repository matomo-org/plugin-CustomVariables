/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

// TODO:
// - state property types
// - method signatures
// - method code

import {
  reactive,
  computed,
  readonly,
} from 'vue';
import { AjaxHelper } from 'CoreHome';

interface CustomVarsStoreState {
  customVariables: unknown[];
  extractions: unknown[];
  isLoading: boolean;
  hasCustomVariablesInGeneral: boolean;
  hasAtLeastOneUsage: boolean;
  numSlotsAvailable: number;
}

interface CustomVariableUsage {
  index: number;
  scope: string;
  usages: unknown[];
}

class ManageCustomVarsStore {
  private privateState = reactive<CustomVarsStoreState>({
    customVariables: [],
    extractions: [],
    isLoading: false,
    hasCustomVariablesInGeneral: false,
    hasAtLeastOneUsage: false,
    numSlotsAvailable: 5,
  });

  readonly state = computed(() => readonly(this.privateState));

  init() {
    return this.fetchUsages();
  }

  fetchCustomVariables() {
    return AjaxHelper.fetch<unknown[]>({
      method: 'CustomVariables.getCustomVariables',
      period: 'year',
      date: 'today',
      filter_limit: 1,
    }).then((customVariables) => {
      this.privateState.hasCustomVariablesInGeneral = customVariables?.length > 0;
    });
  }

  fetchUsages() {
    this.privateState.isLoading = true;
    Promise.all([
      this.fetchCustomVariables(),
      AjaxHelper.fetch<CustomVariableUsage[]>({
        method: 'CustomVariables.getUsagesOfSlots',
        filter_limit: '-1',
      }),
    ]).then(([, customVariableUsages]) => {
      this.privateState.customVariables = customVariableUsages as CustomVariableUsage[];
      (customVariableUsages as CustomVariableUsage[]).forEach((customVar) => {
        if (customVar.index > this.state.value.numSlotsAvailable) {
          this.privateState.numSlotsAvailable = customVar.index;
        }

        if (customVar.usages && customVar.usages.length > 0) {
          this.privateState.hasAtLeastOneUsage = true;
        }
      });
    }).finally(() => {
      this.privateState.isLoading = false;
    });
  }
}

export default new ManageCustomVarsStore();
