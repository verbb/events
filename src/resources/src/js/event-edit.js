// ==========================================================================

// Events Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

(function($) {

if (typeof Craft.Events === 'undefined') {
    Craft.Events = {};
}

Craft.Events.EventEdit = Garnish.Base.extend({
    init: function(id) {
        // Target the entire slide-out pane (we can't edit sessions in the traditional sense outside)
        this.$pane = $('#' + id);
        this.$container = this.$pane.parents('form');

        // Scope to event capacity only — other elements (e.g. sessions) also use data-attribute="capacity"
        this.$capacityField = this.$container.find('[data-attribute="event-capacity"]');

        if (this.$capacityField.length) {
            this.$capacityInput = this.$capacityField.find('input');
            this.savedCapacity = this.$capacityInput.val();

            this.$capacityEditBtn = $('<button/>', {
                type: 'button',
                class: 'icon',
                'data-icon': this.savedCapacity ? 'remove' : 'edit',
                style: 'position: absolute; top: 50%; right: -2px; transform: translateY(-50%); width: 24px; color: #586673;',
            });

            if (!this.$capacityField.find('.input button').length) {
                this.$capacityField.find('.input').append(this.$capacityEditBtn);
            }

            this.addListener(this.$capacityEditBtn, 'click', 'toggleCapacityEdit');
        }

        this.initTicketStatus();
    },

    toggleCapacityEdit(e) {
        // Use readOnly (not disabled) for "auto" so the input is still submitted and the server can clear capacity
        if (this.$capacityInput.prop('readOnly')) {
            this.$capacityInput.prop('readOnly', false);
            this.$capacityInput.prop('placeholder', '');
            this.$capacityInput.removeClass('disabled');

            this.$capacityEditBtn.attr('data-icon', 'remove');

            if (this.savedCapacity) {
                this.$capacityInput.val(this.savedCapacity);
            } else {
                this.$capacityInput.val('');
            }
        } else {
            this.$capacityInput.prop('readOnly', true);
            this.$capacityInput.prop('placeholder', Craft.t('events', 'auto'));
            this.$capacityInput.addClass('disabled');

            this.$capacityEditBtn.attr('data-icon', 'edit');

            this.$capacityInput.val('');
        }

        this.$capacityInput.trigger('input').trigger('change');
    },

    initTicketStatus() {
        this.$ticketStatus = this.$container.find('[data-events-ticket-status]');

        if (!this.$ticketStatus.length) {
            return;
        }

        this.statusUrl = this.$ticketStatus.data('statusUrl');
        this.pollInterval = null;
        this.isPolling = false;

        if (this.isActiveState(this.$ticketStatus.data('state'))) {
            this.startPolling();
        }
    },

    isActiveState(state) {
        return state === 'queued' || state === 'running';
    },

    startPolling() {
        if (this.pollInterval) {
            return;
        }

        this.poll();
        this.pollInterval = window.setInterval(() => {
            this.poll();
        }, 2000);
    },

    stopPolling() {
        if (this.pollInterval) {
            window.clearInterval(this.pollInterval);
            this.pollInterval = null;
        }
    },

    kickQueue() {
        if (typeof Craft === 'undefined') {
            return;
        }

        if (Craft.cp && typeof Craft.cp.runQueue === 'function') {
            Craft.cp.runQueue();
            return;
        }

        if (Craft.runQueueAutomatically !== false && typeof Craft.sendActionRequest === 'function') {
            Craft.sendActionRequest('POST', 'queue/run').catch(() => {});
        }
    },

    poll() {
        if (!this.statusUrl || this.isPolling) {
            return;
        }

        this.isPolling = true;
        this.kickQueue();

        fetch(this.statusUrl, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('status');
                }

                return response.json();
            })
            .then((payload) => {
                this.updateTicketStatusUI(payload);
            })
            .catch(() => {})
            .finally(() => {
                this.isPolling = false;
            });
    },

    updateTicketStatusUI(payload) {
        if (!payload || !payload.state) {
            return;
        }

        if (this.isActiveState(payload.state)) {
            this.updateRunningUI(payload);
            return;
        }

        this.stopPolling();
        window.location.reload();
    },

    updateRunningUI(payload) {
        const progress = Math.round((payload.progress || 0) * 100);
        const description = payload.description || Craft.t('events', 'Updating tickets…');

        this.$ticketStatus.attr('data-state', payload.state);

        this.$ticketStatus
            .find('.events-ticket-status__progress')
            .attr('aria-valuenow', progress);

        this.$ticketStatus
            .find('.events-ticket-status__progress-bar')
            .css('width', progress + '%');

        this.$ticketStatus
            .find('.events-ticket-status__description')
            .text(description);

        this.$ticketStatus
            .find('.events-ticket-status__percent')
            .text(progress + '%');
    },
});


})(jQuery);
