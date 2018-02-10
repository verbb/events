(function($){

if (typeof Craft.Events === typeof undefined) {
	Craft.Events = {};
}

var elementTypeClass = 'Events_Event';

Craft.Events.EventIndex = Craft.BaseElementIndex.extend({

	eventTypes: null,

	$newEventBtnGroup: null,
	$newEventBtn: null,

	canCreateEvents: false,

	afterInit: function() {
		// Find which event types are being shown as sources
		this.eventTypes = [];

		for (var i = 0; i < this.$sources.length; i++) {
			var $source = this.$sources.eq(i),
				key = $source.data('key'),
				match = key.match(/^eventType:(\d+)$/);

			if (match) {
				this.eventTypes.push({
					id: parseInt(match[1]),
					handle: $source.data('handle'),
					name: $source.text(),
					editable: $source.data('editable')
				});

				if (!this.canCreateEvents && $source.data('editable')) {
					this.canCreateEvents = true;
				}
			}
		}

		this.base();
	},

	getDefaultSourceKey: function() {
		// Did they request a specific event type in the URL?
		if (this.settings.context == 'index' && typeof defaultEventTypeHandle != typeof undefined) {
			for (var i = 0; i < this.$sources.length; i++) {
				var $source = $(this.$sources[i]);
				if ($source.data('handle') == defaultEventTypeHandle) {
					return $source.data('key');
				}
			}
		}

		return this.base();
	},

	onSelectSource: function() {
		// Get the handle of the selected source
		var selectedSourceHandle = this.$source.data('handle');

		// Update the New Event button
		// ---------------------------------------------------------------------

		// Remove the old button, if there is one
		if (this.$newEventBtnGroup) {
			this.$newEventBtnGroup.remove();
		}

		// Are they viewing a event type source?
		var selectedEventType;
		if (selectedSourceHandle) {
			for (var i = 0; i < this.eventTypes.length; i++) {
				if (this.eventTypes[i].handle == selectedSourceHandle) {
					selectedEventType = this.eventTypes[i];
					break;
				}
			}
		}

		// Are they allowed to create new events?
		if (this.canCreateEvents) {
			this.$newEventBtnGroup = $('<div class="btngroup submit"/>');
			var $menuBtn;

			// If they are, show a primany "New event" button, and a dropdown of the other event types (if any).
			// Otherwise only show a menu button
			if (selectedEventType) {
				var href = this._getEventTypeTriggerHref(selectedEventType),
					label = (this.settings.context == 'index' ? Craft.t('New event') : Craft.t('New {eventType} event', {eventType: selectedEventType.name}));
				this.$newEventBtn = $('<a class="btn submit add icon" '+href+'>'+label+'</a>').appendTo(this.$newEventBtnGroup);

				if (this.settings.context != 'index') {
					this.addListener(this.$newEventBtn, 'click', function(ev) {
						this._openCreateEventModal(ev.currentTarget.getAttribute('data-id'));
					});
				}

				if (this.eventTypes.length > 1) {
					$menuBtn = $('<div class="btn submit menubtn"></div>').appendTo(this.$newEventBtnGroup);
				}
			} else {
				this.$newEventBtn = $menuBtn = $('<div class="btn submit add icon menubtn">'+Craft.t('New event')+'</div>').appendTo(this.$newEventBtnGroup);
			}

			if ($menuBtn) {
				var menuHtml = '<div class="menu"><ul>';

				for (var i = 0; i < this.eventTypes.length; i++) {
					var eventType = this.eventTypes[i];

					if (this.settings.context == 'index' || eventType != selectedEventType) {
						var href = this._getEventTypeTriggerHref(eventType),
							label = (this.settings.context == 'index' ? eventType.name : Craft.t('New {eventType} event', {eventType: eventType.name}));
						menuHtml += '<li><a '+href+'">'+label+'</a></li>';
					}
				}

				menuHtml += '</ul></div>';

				var $menu = $(menuHtml).appendTo(this.$newEventBtnGroup),
					menuBtn = new Garnish.MenuBtn($menuBtn);

				if (this.settings.context != 'index') {
					menuBtn.on('optionSelect', $.proxy(function(ev) {
						this._openCreateEventModal(ev.option.getAttribute('data-id'));
					}, this));
				}
			}

			this.addButton(this.$newEventBtnGroup);
		}

		// Update the URL if we're on the Events index
		// ---------------------------------------------------------------------

		if (this.settings.context == 'index' && typeof history != typeof undefined) {
			var uri = 'events/events';
			if (selectedSourceHandle) {
				uri += '/'+selectedSourceHandle;
			}
			history.replaceState({}, '', Craft.getUrl(uri));
		}

		this.base();
	},

	_getEventTypeTriggerHref: function(eventType)
	{
		if (this.settings.context == 'index') {
			return 'href="'+Craft.getUrl('events/events/'+eventType.handle+'/new')+'"';
		} else {
			return 'data-id="'+eventType.id+'"';
		}
	},

	_openCreateEventModal: function(eventTypeId)
	{
		if (this.$newEventBtn.hasClass('loading')) {
			return;
		}

		// Find the event type
		var eventType;

		for (var i = 0; i < this.eventTypes.length; i++) {
			if (this.eventTypes[i].id == eventTypeId) {
				eventType = this.eventTypes[i];
				break;
			}
		}

		if (!eventType) {
			return;
		}

		this.$newEventBtn.addClass('inactive');
		var newEventBtnText = this.$newEventBtn.text();
		this.$newEventBtn.text(Craft.t('New {eventType} event', {eventType: eventType.name}));

		new Craft.ElementEditor({
			hudTrigger: this.$newEventBtnGroup,
			elementType: elementTypeClass,
			locale: this.locale,
			attributes: {
				typeId: eventTypeId
			},
			onBeginLoading: $.proxy(function() {
				this.$newEventBtn.addClass('loading');
			}, this),
			onEndLoading: $.proxy(function() {
				this.$newEventBtn.removeClass('loading');
			}, this),
			onHideHud: $.proxy(function() {
				this.$newEventBtn.removeClass('inactive').text(newEventBtnText);
			}, this),
			onSaveElement: $.proxy(function(response) {
				// Make sure the right event type is selected
				var eventTypeSourceKey = 'eventType:'+eventTypeId;

				if (this.sourceKey != eventTypeSourceKey) {
					this.selectSourceByKey(eventTypeSourceKey);
				}

				this.selectElementAfterUpdate(response.id);
				this.updateElements();
			}, this)
		});
	}
});

// Register it!
try {
	Craft.registerElementIndexClass(elementTypeClass, Craft.Events.EventIndex);
}
catch(e) {
	// Already registered
}

})(jQuery);
