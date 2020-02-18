(function($) {

if (typeof Craft.Events === 'undefined') {
    Craft.Events = {};
}

Craft.Events.TicketEdit = Garnish.Base.extend({
    rowHtml: '',
    tickets: null,
    ticketTypeHtml: '',
    totalNewRows: 0,

    $container: null,
    $ticketContainer: null,
    $ticketRows: null,
    $addBtn: null,
    $capacity: null,
    $quantity: null,

    currentStartTime: null,
    currentEndTime: null,
    $startDate: null,
    $startTime: null,
    $endDate: null,
    $endTime: null,
    $allDay: null,

    init: function(id, tickets, rowHtml, ticketTypeHtml) {
        this.rowHtml = rowHtml;
        this.tickets = tickets;
        this.ticketTypeHtml = ticketTypeHtml;
        this.$container = $('#' + id);

        this.$ticketContainer = this.$container.find('.create-tickets-container');
        this.$ticketRows = this.$ticketContainer.find('.create-tickets');
        this.$addBtn = this.$container.find('.add-ticket');
        this.$capacity = this.$container.find('#capacity');
        this.$quantity = this.$container.find('.ticket-quantity');

        for (var i = 0; i < this.$ticketRows.length; i++) {
            var id = $(this.$ticketRows[i]).data('id');

            new Craft.Events.TicketEditRow(this, this.$ticketRows[i], i);

            // Is this a new ticket?
            var newMatch = (typeof id === 'string' && id.match(/new(\d+)/));

            if (newMatch && newMatch[1] > this.totalNewRows) {
                this.totalNewRows = parseInt(newMatch[1]);
            }
        }
        
        this.addListener(this.$addBtn, 'click', 'addTicket');
        this.addListener(this.$quantity, 'change', 'sumAllQuantities');
    },

    addTicket: function() {
        this.totalNewRows++;

        var id = 'new' + this.totalNewRows;

        var bodyHtml = this.getParsedBlockHtml(this.rowHtml.bodyHtml, id),
            footHtml = this.getParsedBlockHtml(this.rowHtml.footHtml, id);

        var $newRow = $(bodyHtml).appendTo(this.$ticketContainer);

        Garnish.$bod.append(footHtml);
        
        Craft.initUiElements($newRow);

        new Craft.Events.TicketEditRow(this, $newRow, id);
    },

    getParsedBlockHtml: function(html, id) {
        if (typeof html == 'string') {
            return html.replace(/__ROWID__/g, id);
        } else {
            return '';
        }
    },

    sumAllQuantities: function() {
        var quantity = 0;

        $.each($('body').find('.ticket-quantity'), function() {
            quantity += Number($(this).val());
        });

        if (this.$capacity.val() == '') {
            this.$capacity.val(quantity);
        }
    }
});

Craft.Events.TicketEditRow = Garnish.Base.extend({
    id: null,
    editContainer: null,

    $container: null,
    $settingsContainer: null,

    $ticketTypeFields: null,
    $settingsBtn: null,
    $deleteBtn: null,
    $capacity: null,
    $quantity: null,

    init: function(editContainer, row, id) {
        this.id = id;
        this.editContainer = editContainer;

        this.$container = $(row);
        this.$settingsContainer = this.$container.find('.create-tickets-settings');

        this.$ticketTypeFields = this.$container.find('.ticket-type-fields');
        this.$elementSelect = this.$container.find('.elementselect');
        this.$settingsBtn = this.$container.find('.settings.icon');
        this.$deleteBtn = this.$container.find('.delete.icon.button');
        this.$capacity = $('body').find('#capacity');
        this.$quantity = this.$container.find('.ticket-quantity');

        // Wait until the element select field is ready
        Garnish.requestAnimationFrame($.proxy(function() {
            var elementSelect = this.$elementSelect.data('elementSelect');

            // Attach an on-select and on-remove handler
            elementSelect.settings.onSelectElements = $.proxy(this, 'onSelectElements');
            elementSelect.settings.onRemoveElements = $.proxy(this, 'onRemoveElements');            
        }, this));

        this.addListener(this.$settingsBtn, 'click', 'settingsRow');
        this.addListener(this.$deleteBtn, 'click', 'deleteRow');
        this.addListener(this.$quantity, 'change', 'sumAllQuantities');
    },

    onSelectElements: function(elements) {
        var ticketTypeId = elements[0].id;
        var ticketTypeHtml = this.editContainer.ticketTypeHtml[ticketTypeId];

        var id = 'new' + this.editContainer.totalNewRows;

        // Check for an existing ticket ID
        if (this.id != null) {
            var ticket = this.editContainer.tickets[this.id];

            if (ticket) {
                id = ticket.id;
            }
        }

        var bodyHtml = this.getParsedBlockHtml(ticketTypeHtml.bodyHtml, id),
            footHtml = this.getParsedBlockHtml(ticketTypeHtml.footHtml, id);

        var $newRow = this.$ticketTypeFields.html(bodyHtml);

        Garnish.$bod.append(footHtml);
        Craft.initUiElements($newRow);
    },

    onRemoveElements: function() {
        this.$ticketTypeFields.html('');
    },

    settingsRow: function() {
        if (this.$settingsContainer.is(':visible')) {
            this.$settingsBtn.removeClass('active');
            this.$settingsContainer.velocity('slideUp');
        } else {
            this.$settingsBtn.addClass('active');
            this.$settingsContainer.velocity('slideDown');
        }
    },

    deleteRow: function() {
        var deleteRow = confirm(Craft.t('events', 'Are you sure you want to delete this ticket type?'));

        if (deleteRow) {
            this.$container.remove();

            this.sumAllQuantities();
        }
    },

    sumAllQuantities: function() {
        var quantity = 0;

        $.each($('body').find('.ticket-quantity'), function() {
            quantity += Number($(this).val());
        });

        if (this.$capacity.val() == '') {
            this.$capacity.val(quantity);
        }
    },

    getParsedBlockHtml: function(html, id) {
        if (typeof html == 'string') {
            return html.replace(/__ROWID__/g, id);
        } else {
            return '';
        }
    },
});


})(jQuery);
