(function(global, factory) {
  typeof exports === "object" && typeof module !== "undefined" ? factory(exports, require("vue"), require("CoreHome")) : typeof define === "function" && define.amd ? define(["exports", "vue", "CoreHome"], factory) : (global = typeof globalThis !== "undefined" ? globalThis : global || self, factory(global.CustomVariables = {}, global.Vue, global.CoreHome));
})(this, (function(exports2, vue, CoreHome) {
  "use strict";var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);

  /*!
   * Matomo - free/libre analytics platform
   *
   * @link https://matomo.org
   * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
   */
  class ManageCustomVarsStore {
    constructor() {
      __publicField(this, "privateState", vue.reactive({
        customVariables: [],
        isLoading: false,
        hasCustomVariablesInGeneral: false,
        hasAtLeastOneUsage: false,
        numSlotsAvailable: 5
      }));
      __publicField(this, "state", vue.computed(() => vue.readonly(this.privateState)));
    }
    init() {
      return this.fetchUsages();
    }
    fetchCustomVariables() {
      return CoreHome.AjaxHelper.fetch({
        method: "CustomVariables.getCustomVariables",
        period: "year",
        date: "today",
        filter_limit: 1
      }).then((customVariables) => {
        this.privateState.hasCustomVariablesInGeneral = (customVariables == null ? void 0 : customVariables.length) > 0;
      });
    }
    fetchUsages() {
      this.privateState.isLoading = true;
      return Promise.all([
        this.fetchCustomVariables(),
        CoreHome.AjaxHelper.fetch({
          method: "CustomVariables.getUsagesOfSlots",
          filter_limit: "-1"
        })
      ]).then(([, customVariableUsages]) => {
        this.privateState.customVariables = customVariableUsages;
        customVariableUsages.forEach((customVar) => {
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
  const ManageCustomVarsStore$1 = new ManageCustomVarsStore();
  const _sfc_main = vue.defineComponent({
    components: {
      EnrichedHeadline: CoreHome.EnrichedHeadline,
      ContentBlock: CoreHome.ContentBlock
    },
    directives: {
      ContentIntro: CoreHome.ContentIntro,
      ContentTable: CoreHome.ContentTable,
      SelectOnFocus: CoreHome.SelectOnFocus
    },
    data() {
      return {
        siteName: CoreHome.Matomo.siteName,
        scopes: [
          {
            value: "visit",
            name: CoreHome.translate("General_TrackingScopeVisit")
          },
          {
            value: "page",
            name: CoreHome.translate("General_TrackingScopePage")
          }
        ]
      };
    },
    created() {
      ManageCustomVarsStore$1.init();
    },
    methods: {
      sortUsages(customVar) {
        const result = [...customVar.usages];
        result.sort((lhs, rhs) => {
          const rhsActions = `${rhs.nb_actions}`;
          const lhsActions = `${lhs.nb_actions}`;
          return parseInt(rhsActions, 10) - parseInt(lhsActions, 10);
        });
        return result;
      }
    },
    computed: {
      isLoading() {
        return ManageCustomVarsStore$1.state.value.isLoading;
      },
      hasCustomVariablesInGeneral() {
        return ManageCustomVarsStore$1.state.value.hasCustomVariablesInGeneral;
      },
      hasAtLeastOneUsage() {
        return ManageCustomVarsStore$1.state.value.hasAtLeastOneUsage;
      },
      numSlotsAvailable() {
        return ManageCustomVarsStore$1.state.value.numSlotsAvailable;
      },
      customVariablesByScope() {
        const result = {};
        ManageCustomVarsStore$1.state.value.customVariables.forEach((customVar) => {
          result[customVar.scope] = result[customVar.scope] || [];
          result[customVar.scope].push(customVar);
        });
        return result;
      },
      currentAvailableCustomVariablesText() {
        return CoreHome.translate(
          "CustomVariables_CurrentAvailableCustomVariables",
          `<strong>${this.numSlotsAvailable}</strong>`
        );
      },
      setMaxCustomVariablesCmd() {
        return `./console customvariables:set-max-custom-variables ${this.numSlotsAvailable + 1}`;
      }
    }
  });
  const _export_sfc = (sfc, props) => {
    const target = sfc.__vccOpts || sfc;
    for (const [key, val] of props) {
      target[key] = val;
    }
    return target;
  };
  const _hoisted_1 = { class: "manageCustomVars" };
  const _hoisted_2 = ["innerHTML"];
  const _hoisted_3 = { class: "index" };
  const _hoisted_4 = ["title"];
  const _hoisted_5 = { key: 0 };
  const _hoisted_6 = /* @__PURE__ */ vue.createElementVNode("br", null, null, -1);
  const _hoisted_7 = /* @__PURE__ */ vue.createElementVNode("br", null, null, -1);
  const _hoisted_8 = ["innerHTML"];
  const _hoisted_9 = /* @__PURE__ */ vue.createElementVNode("br", null, null, -1);
  const _hoisted_10 = /* @__PURE__ */ vue.createElementVNode("br", null, null, -1);
  const _hoisted_11 = /* @__PURE__ */ vue.createElementVNode("br", null, null, -1);
  const _hoisted_12 = ["textContent"];
  function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_EnrichedHeadline = vue.resolveComponent("EnrichedHeadline");
    const _component_ContentBlock = vue.resolveComponent("ContentBlock");
    const _directive_content_intro = vue.resolveDirective("content-intro");
    const _directive_content_table = vue.resolveDirective("content-table");
    const _directive_select_on_focus = vue.resolveDirective("select-on-focus");
    return vue.openBlock(), vue.createElementBlock("div", _hoisted_1, [
      vue.withDirectives((vue.openBlock(), vue.createElementBlock("div", null, [
        vue.createElementVNode("h2", null, [
          vue.createVNode(_component_EnrichedHeadline, { "help-url": "https://matomo.org/docs/custom-variables/" }, {
            default: vue.withCtx(() => [
              vue.createTextVNode(vue.toDisplayString(_ctx.translate("CustomVariables_CustomVariables")), 1)
            ]),
            _: 1
          })
        ]),
        vue.createElementVNode("p", null, [
          vue.createElementVNode("span", {
            innerHTML: _ctx.$sanitize(_ctx.translate("CustomVariables_ManageDescription", _ctx.siteName))
          }, null, 8, _hoisted_2)
        ])
      ])), [
        [_directive_content_intro]
      ]),
      vue.withDirectives(vue.createElementVNode("div", { class: "alert alert-info" }, vue.toDisplayString(_ctx.translate("CustomVariables_SlotsReportIsGeneratedOverTime")), 513), [
        [vue.vShow, !_ctx.isLoading && _ctx.hasCustomVariablesInGeneral && !_ctx.hasAtLeastOneUsage]
      ]),
      (vue.openBlock(true), vue.createElementBlock(vue.Fragment, null, vue.renderList(_ctx.scopes, (scope) => {
        return vue.openBlock(), vue.createElementBlock("div", {
          key: scope.name
        }, [
          vue.createVNode(_component_ContentBlock, {
            "content-title": _ctx.translate("CustomVariables_ScopeX", scope.name)
          }, {
            default: vue.withCtx(() => [
              vue.withDirectives((vue.openBlock(), vue.createElementBlock("table", null, [
                vue.createElementVNode("thead", null, [
                  vue.createElementVNode("tr", null, [
                    vue.createElementVNode("th", null, vue.toDisplayString(_ctx.translate("CustomVariables_Index")), 1),
                    vue.createElementVNode("th", null, vue.toDisplayString(_ctx.translate("CustomVariables_Usages")), 1)
                  ])
                ]),
                vue.createElementVNode("tbody", null, [
                  vue.createElementVNode("tr", null, [
                    vue.withDirectives(vue.createElementVNode("td", { colspan: "3" }, vue.toDisplayString(_ctx.translate("General_Loading")), 513), [
                      [vue.vShow, _ctx.isLoading]
                    ])
                  ]),
                  (vue.openBlock(true), vue.createElementBlock(vue.Fragment, null, vue.renderList(_ctx.customVariablesByScope[scope.value], (customVariables, index) => {
                    return vue.openBlock(), vue.createElementBlock("tr", { key: index }, [
                      vue.createElementVNode("td", _hoisted_3, vue.toDisplayString(customVariables.index), 1),
                      vue.createElementVNode("td", null, [
                        vue.withDirectives(vue.createElementVNode("span", { class: "unused" }, vue.toDisplayString(_ctx.translate("CustomVariables_Unused")), 513), [
                          [vue.vShow, customVariables.usages.length === 0]
                        ]),
                        (vue.openBlock(true), vue.createElementBlock(vue.Fragment, null, vue.renderList(_ctx.sortUsages(customVariables), (cvar, cvarIndex) => {
                          return vue.withDirectives((vue.openBlock(), vue.createElementBlock("span", { key: cvarIndex }, [
                            vue.createElementVNode("span", {
                              title: _ctx.translate(
                                "CustomVariables_UsageDetails",
                                cvar.nb_visits ? cvar.nb_visits : 0,
                                cvar.nb_actions ? cvar.nb_actions : 0
                              )
                            }, vue.toDisplayString(cvar.name), 9, _hoisted_4),
                            cvarIndex < customVariables.usages.length - 1 ? (vue.openBlock(), vue.createElementBlock("span", _hoisted_5, ", ")) : vue.createCommentVNode("", true)
                          ])), [
                            [vue.vShow, customVariables.usages.length]
                          ]);
                        }), 128))
                      ])
                    ]);
                  }), 128))
                ])
              ])), [
                [_directive_content_table]
              ])
            ]),
            _: 2
          }, 1032, ["content-title"])
        ]);
      }), 128)),
      vue.withDirectives(vue.createVNode(_component_ContentBlock, {
        id: "CustomVariablesCreateNewSlot",
        "content-title": _ctx.translate("CustomVariables_CreateNewSlot")
      }, {
        default: vue.withCtx(() => [
          vue.withDirectives(vue.createElementVNode("div", null, [
            vue.createElementVNode("p", null, [
              vue.createTextVNode(vue.toDisplayString(_ctx.translate("CustomVariables_CreatingCustomVariableTakesTime")) + " ", 1),
              _hoisted_6,
              _hoisted_7,
              vue.createElementVNode("span", {
                innerHTML: _ctx.$sanitize(_ctx.currentAvailableCustomVariablesText)
              }, null, 8, _hoisted_8),
              _hoisted_9,
              _hoisted_10,
              vue.createTextVNode(" " + vue.toDisplayString(_ctx.translate("CustomVariables_ToCreateCustomVarExecute")) + " ", 1),
              _hoisted_11
            ]),
            vue.withDirectives((vue.openBlock(), vue.createElementBlock("pre", null, [
              vue.createElementVNode("code", {
                textContent: vue.toDisplayString(_ctx.setMaxCustomVariablesCmd)
              }, null, 8, _hoisted_12)
            ])), [
              [_directive_select_on_focus, {}]
            ])
          ], 512), [
            [vue.vShow, !_ctx.isLoading]
          ])
        ]),
        _: 1
      }, 8, ["content-title"]), [
        [vue.vShow, !_ctx.isLoading]
      ])
    ]);
  }
  const ManageCustomVars = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
  exports2.ManageCustomVars = ManageCustomVars;
  exports2.ManageCustomVarsStore = ManageCustomVarsStore$1;
  Object.defineProperty(exports2, Symbol.toStringTag, { value: "Module" });
}));
