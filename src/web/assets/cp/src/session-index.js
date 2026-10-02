// ==========================================================================

// Events Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

(function($) {

if (typeof Craft.Events === 'undefined') {
    Craft.Events = {};
}

Craft.Events.DeleteSessionModal = Garnish.Modal.extend({
    id: null,
    sessionId: null,

    $deleteActionRadios: null,
    $deleteSubmitBtn: null,

    _deleting: false,

    init: function (sessionId, settings) {
        this.id = Math.floor(Math.random() * 1000000000);
        this.sessionId = sessionId;
        settings = $.extend(Craft.Events.DeleteSessionModal.defaults, settings);

        this.$form = $(
            '<form class="modal fitted delete-session-modal" method="post" accept-charset="UTF-8">' +
                Craft.getCsrfInput() +
                '<input type="hidden" name="action" value="events/sessions/delete-session"/>' +
                (!Array.isArray(this.sessionId) ? '<input type="hidden" name="sessionId" value="' + this.sessionId + '"/>' : '') +
                (settings.redirect ? '<input type="hidden" name="redirect" value="' + settings.redirect + '"/>' : '') +
            '</form>'
        ).appendTo(Garnish.$bod);
                                    
        const id1 = 'radio' + Math.floor(Math.random() * 1000000000);
        const id2 = 'radio' + Math.floor(Math.random() * 1000000000);
        const id3 = 'radio' + Math.floor(Math.random() * 1000000000);
        const id4 = 'radio' + Math.floor(Math.random() * 1000000000);

        this.$body = $(
            '<div class="body">' +
                '<div class="options">' +

                    '<fieldset class="field">' + 
                        '<legend class="h6">' + Craft.t('events', 'Apply changes to') + '</legend>' + 

                        '<div>' + 
                            '<div class="radio-group">' + 
                                '<div>' + 
                                    '<input type="radio" id="' + id1 + '" class="radio" name="contentAction" value="single">' + 
                                    '<label for="' + id1 + '">' + Craft.t('events', 'This occurrence') + '</label>' + 
                                '</div>' + 

                                '<div>' + 
                                    '<input type="radio" id="' + id2 + '" class="radio" name="contentAction" value="all">' + 
                                    '<label for="' + id2 + '">' + Craft.t('events', 'All occurrences') + '</label>' + 
                                '</div>' + 

                                '<div>' + 
                                    '<input type="radio" id="' + id3 + '" class="radio" name="contentAction" value="future">' + 
                                    '<label for="' + id3 + '">' + Craft.t('events', 'This and all future occurrences') + '</label>' + 
                                '</div>' + 

                                '<div>' + 
                                    '<input type="radio" id="' + id4 + '" class="radio" name="contentAction" value="custom">' + 
                                    '<label for="' + id4 + '">' + Craft.t('events', 'Custom range') + '</label>' + 
                                '</div>' + 
                            '</div>' + 
                        '</div>' + 
                    '</fieldset>' + 

                    '<div class="apply-changes--custom hidden"></div>' +
                '</div>' +
            '</div>'
        ).appendTo(this.$form);

        var $flex = $('<div/>', { class: 'date-container flex flex-nowrap padded' }).appendTo(this.$body.find('.apply-changes--custom'));

        this.$startDate = Craft.ui.createDateField({
            label: Craft.t('events', 'Start Date'),
            required: true,
            name: 'contentActionStartDate',
        }).appendTo($flex);

        this.$endDate = Craft.ui.createDateField({
            label: Craft.t('events', 'End Date'),
            required: true,
            name: 'contentActionEndDate',
        }).appendTo($flex);

        this.$startDateInput = this.$startDate.find('.datewrapper input[type="text"]');
        this.$endDateInput = this.$endDate.find('.datewrapper input[type="text"]');

        let $buttons = $('<div class="buttons right"/>').appendTo(this.$body);
        
        let $cancelBtn = $('<button/>', {
            type: 'button',
            class: 'btn',
            text: Craft.t('app', 'Cancel'),
        }).appendTo($buttons);

        this.$deleteActionRadios = this.$body.find('input[type="radio"]');

        this.$deleteSubmitBtn = Craft.ui.createSubmitButton({
            class: 'disabled',
            label: Craft.t('events', 'Delete {num, plural, =1{session} other{sessions}}', {
                num: Array.isArray(this.sessionId) ? this.sessionId.length : 1,
            }),
            spinner: true,
        }).appendTo($buttons);

        this.addListener($cancelBtn, 'click', 'hide');
        this.addListener(this.$startDateInput, 'change', 'validateDeleteInputs');
        this.addListener(this.$endDateInput, 'change', 'validateDeleteInputs');
        this.addListener(this.$deleteActionRadios, 'change', 'validateDeleteInputs');
        this.addListener(this.$deleteActionRadios, 'change', 'onToggleChange');
        this.addListener(this.$form, 'submit', 'handleSubmit');

        this.base(this.$form, settings);
    },

    validateDeleteInputs: function () {
        var validates = false;

        if (this.$deleteActionRadios.slice(0, 3).is(':checked')) {
            validates = true;
        } else {
            if (this.$startDateInput.datepicker('getDate') && this.$endDateInput.datepicker('getDate')) {
                validates = true;
            }
        }

        if (validates) {
            this.$deleteSubmitBtn.removeClass('disabled');
        } else {
            this.$deleteSubmitBtn.addClass('disabled');
        }

        return validates;
    },

    onToggleChange(e) {
        if ($(e.target).prop('checked') && $(e.target).val() === 'custom') {
            this.$form.find('.apply-changes--custom').removeClass('hidden');
        } else {
            this.$form.find('.apply-changes--custom').addClass('hidden');
        }

        this.updateSizeAndPosition();
    },

    handleSubmit: function (ev) {
        if (this._deleting || !this.validateDeleteInputs()) {
            ev.preventDefault();
            return;
        }

        this.$deleteSubmitBtn.addClass('loading');
        this.disable();
        this._deleting = true;

        // Let the onSubmit callback prevent the form from getting submitted
        try {
            if (this.settings.onSubmit() === false) {
                ev.preventDefault();
            }
        } catch (e) {
            ev.preventDefault();
            this.$deleteSubmitBtn.removeClass('loading');
            throw e;
        }
    },
},
{
    defaults: {
        onSubmit: $.noop,
        redirect: null,
    },
});


})(jQuery);
