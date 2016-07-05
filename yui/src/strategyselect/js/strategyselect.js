/*global M*/
var SELECTORS = {
    STRATSELECT: '#id_strategy',
    SELOPTIONS: '#selected_strategy_options',
    FORMELEMENTS: 'div.fcontainer'
};

M.mod_ratingallocate = M.mod_ratingallocate || {};
M.mod_ratingallocate.strategyselect = {
    lastStrategy: null,

    init: function () {
        "use strict";
        Y.one(SELECTORS.STRATSELECT).on('valuechange', this.showOptions, this);
        this.showOptions();
    },

    showOptions: function () {
        "use strict";
        var sel, strat, oldOpts, newOpts, optDest;

        // Get the selected strategy.
        sel = Y.one(SELECTORS.STRATSELECT);
        if (!sel) {
            return;
        }
        strat = sel.get('value');
        if (this.lastStrategy === strat) {
            return;
        }
        optDest = Y.one(SELECTORS.SELOPTIONS);

        // Move any existing items back to their original location.
        if (this.lastStrategy) {
            oldOpts = Y.one('#id_strategy_' + this.lastStrategy + '_fieldset');
            if (oldOpts) {
                optDest.all(SELECTORS.FORMELEMENTS).each(function (node) {
                    oldOpts.appendChild(node);
                });
            }
        }
        this.lastStrategy = strat;

        // Move the new items below the strategy select.
        newOpts = Y.one('#id_strategy_' + strat + '_fieldset');
        if (newOpts) {
            newOpts.all(SELECTORS.FORMELEMENTS).each(function (node) {
                optDest.appendChild(node);
            });
        }
    }
};
