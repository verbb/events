(function($){

if (typeof Craft.Events === typeof undefined) {
	Craft.Events = {};
}

var elementTypeClass = 'Events_Ticket';

/**
 * Event index class
 */
Craft.Events.TicketIndex = Craft.BaseElementIndex.extend({

	afterInit: function() {

		this.$btnGroup = $('<div class="btngroup submit"/>');
		var $menuBtn;
		var href = 'href="'+Craft.getUrl('events/tickets/new')+'"',
			label = Craft.t('New ticket');

		this.$newEventBtnGroup = $('<div class="btngroup submit"/>');
		this.$newEventBtn = $('<a class="btn submit add icon" '+href+'>'+label+'</a>').appendTo(this.$newEventBtnGroup);
		
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
