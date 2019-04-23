(function($) {

if (typeof Craft.Events === 'undefined') {
	Craft.Events = {};
}

var elementTypeClass = 'verbb\\events\\elements\\Ticket';

Craft.Events.TicketIndex = Craft.BaseElementIndex.extend({
	afterInit: function() {
		var href = 'href="' + Craft.getUrl('events/tickets/new') + '"',
			label = Craft.t('events', 'New ticket');

		this.$newEventBtnGroup = $('<div class="btngroup submit"/>');
		this.$newEventBtn = $('<a class="btn submit add icon" ' + href + '>' + label + '</a>').appendTo(this.$newEventBtnGroup);
		
		this.addButton(this.$newEventBtnGroup);
		
		this.base();
	}
});

// Register it!
try {
	Craft.registerElementIndexClass(elementTypeClass, Craft.Events.TicketIndex);
}
catch(e) {
	// Already registered
}

})(jQuery);
