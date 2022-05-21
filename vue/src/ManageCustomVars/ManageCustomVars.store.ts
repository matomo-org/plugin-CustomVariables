/*!
 * Matomo - free/libre analytics platform
 *
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import {
  reactive,
  computed,
  readonly,
} from 'vue';
import { AjaxHelper } from 'CoreHome';
import { CustomVariableUsage } from '../types';

interface CustomVarsStoreState {
  customVariables: CustomVariableUsage[];
  isLoading: boolean;
  hasCustomVariablesInGeneral: boolean;
  hasAtLeastOneUsage: boolean;
  numSlotsAvailable: number;
}

class ManageCustomVarsStore {
  private privateState = reactive<CustomVarsStoreState>({
    customVariables: [],
    isLoading: false,
    hasCustomVariablesInGeneral: false,
    hasAtLeastOneUsage: false,
    numSlotsAvailable: 5,
  });

  readonly state = computed(() => readonly(this.privateState));

  init(): Promise<void> {
    return this.fetchUsages();
  }

  fetchCustomVariables(): Promise<void> {
    return AjaxHelper.fetch<unknown[]>({
      method: 'CustomVariables.getCustomVariables',
      period: 'year',
      date: 'today',
      filter_limit: 1,
    }).then((customVariables) => {
      this.privateState.hasCustomVariablesInGeneral = customVariables?.length > 0;
    });
  }

  fetchUsages(): Promise<void> {
    this.privateState.isLoading = true;
    return Promise.all([
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
