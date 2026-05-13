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
    },
});


})(jQuery);
