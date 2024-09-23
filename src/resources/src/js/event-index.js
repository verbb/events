// ==========================================================================

// Events Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

(function($) {

if (typeof Craft.Events === 'undefined') {
    Craft.Events = {};
}

Craft.Events.EventIndex = Craft.BaseElementIndex.extend({
    editableEventTypes: null,
    $newEventBtnEventType: null,
    $newEventBtn: null,

    init: function(elementType, $container, settings) {
        this.on('selectSource', $.proxy(this, 'updateButton'));
        this.on('selectSite', $.proxy(this, 'updateButton'));
        this.base(elementType, $container, settings);
    },

    afterInit: function() {
        // Find which of the visible eventTypes the user has permission to create new events in
        this.editableEventTypes = [];

        for (const eventType of Craft.Events.editableEventTypes) {
            if (this.getSourceByKey('eventType:' + eventType.uid)) {
                this.editableEventTypes.push(eventType);
            }
        }

        this.base();
    },

    getDefaultSourceKey: function() {
        // Did they request a specific event type in the URL?
        if (this.settings.context === 'index' && typeof defaultEventTypeHandle !== 'undefined') {
            for (var i = 0; i < this.$sources.length; i++) {
                var $source = $(this.$sources[i]);
                
                if ($source.data('handle') === defaultEventTypeHandle) {
                    return $source.data('key');
                }
            }
        }

        return this.base();
    },

    updateButton: function() {
        if (!this.$source) {
            return;
        }

        // Get the handle of the selected source
        const eventTypeHandle = this.$source.data('handle');

        // Update the New Event button
        // ---------------------------------------------------------------------

        if (this.editableEventTypes.length) {
            // Remove the old button, if there is one
            if (this.$newEventBtnGroup) {
                this.$newEventBtnGroup.remove();
            }

            // Determine if they are viewing a eventType that they have permission to create events in
            const selectedEventType = this.editableEventTypes.find((t) => t.handle === eventTypeHandle);

            this.$newEventBtnGroup = $('<div class="btngroup submit" data-wrapper/>');
            
            let $menuBtn;
            const menuId = `new-event-menu-${Craft.randomString(10)}`;

            // If they are, show a primary "New event" button, and a dropdown of the other eventTypes (if any).
            // Otherwise only show a menu button
            if (selectedEventType) {
                const visibleLabel = this.settings.context === 'index' ? Craft.t('events', 'New event') : Craft.t('events', 'New {eventType} event', {
                    eventType: selectedEventType.name,
                });

                const ariaLabel = this.settings.context === 'index' ? Craft.t('events', 'New {eventType} event', {
                    eventType: selectedEventType.name,
                }) : visibleLabel;

                // In index contexts, the button functions as a link
                // In non-index contexts, the button triggers a slideout editor
                const role = this.settings.context === 'index' ? 'link' : null;

                this.$newEventBtn = Craft.ui.createButton({
                    label: visibleLabel,
                    ariaLabel: ariaLabel,
                    spinner: true,
                    role: role,
                })
                .addClass('submit add icon')
                .appendTo(this.$newEventBtnGroup);

                this.addListener(this.$newEventBtn, 'click mousedown', (ev) => {
                    // If this is the element index, check for Ctrl+clicks and middle button clicks
                    if (this.settings.context === 'index' && ((ev.type === 'click' && Garnish.isCtrlKeyPressed(ev)) || (ev.type === 'mousedown' && ev.originalEvent.button === 1))) {
                        window.open(Craft.getUrl(`events/events/${eventType.handle}/new`));
                    } else if (ev.type === 'click') {
                        this._createEvent(selectedEventType.id);
                    }
                });

                if (this.editableEventTypes.length > 1) {
                    $menuBtn = $('<button/>', {
                        type: 'button',
                        class: 'btn submit menubtn btngroup-btn-last',
                        'aria-controls': menuId,
                        'data-disclosure-trigger': '',
                        'aria-label': Craft.t('events', 'New event, choose a type'),
                    }).appendTo(this.$newEventBtnGroup);
                }
            } else {
                this.$newEventBtn = $menuBtn = Craft.ui.createButton({
                    label: Craft.t('app', 'New event'),
                    ariaLabel: Craft.t('app', 'New event, choose a type'),
                    spinner: true,
                })
                .addClass('submit add icon menubtn btngroup-btn-last')
                .attr('aria-controls', menuId)
                .attr('data-disclosure-trigger', '')
                .appendTo(this.$newEventBtnGroup);
            }

            this.addButton(this.$newEventBtnGroup);

            if ($menuBtn) {
                const $menuContainer = $('<div/>', {
                    id: menuId,
                    class: 'menu menu--disclosure',
                }).appendTo(this.$newEventBtnGroup);
                
                const $ul = $('<ul/>').appendTo($menuContainer);

                for (const eventType of this.editableEventTypes) {
                    const anchorRole = this.settings.context === 'index' ? 'link' : 'button';

                    if (this.settings.context === 'index' || eventType !== selectedEventType) {
                        const $li = $('<li/>').appendTo($ul);
                        const $a = $('<a/>', {
                            role: anchorRole === 'button' ? 'button' : null,
                            href: Craft.getUrl(`events/events/${eventType.handle}/new`),
                            type: anchorRole === 'button' ? 'button' : null,
                            text: Craft.t('events', 'New {eventType} event', {
                                eventType: eventType.name,
                            }),
                        }).appendTo($li);

                        this.addListener($a, 'activate', () => {
                            $menuBtn.data('trigger').hide();
                            this._createEvent(eventType.id);
                        });

                        if (anchorRole === 'button') {
                            this.addListener($a, 'keydown', (event) => {
                                if (event.keyCode === Garnish.SPACE_KEY) {
                                    event.preventDefault();
                                    $menuBtn.data('trigger').hide();
                                    this._createEvent(eventType.id);
                                }
                            });
                        }
                    }
                }

                new Garnish.DisclosureMenu($menuBtn);
            }
        }

        // Update the URL if we're on the Events index
        // ---------------------------------------------------------------------

        if (this.settings.context === 'index') {
            var uri = 'events/events';

            if (eventTypeHandle) {
                uri += '/' + eventTypeHandle;
            }

            Craft.setPath(uri);
        }
    },

    _createEvent: function (eventTypeId) {
        if (this.$newEventBtn.hasClass('loading')) {
            console.warn('New event creation already in progress.');
            return;
        }

        // Find the event type
        const eventType = this.editableEventTypes.find((t) => t.id === eventTypeId);

        if (!eventType) {
            throw `Invalid event type ID: ${eventTypeId}`;
        }

        this.$newEventBtn.addClass('loading');

        Craft.sendActionRequest('POST', 'events/events/create', {
            data: {
                siteId: this.siteId,
                eventType: eventType.handle,
            },
        })
        .then(({ data }) => {
            if (this.settings.context === 'index') {
                document.location.href = Craft.getUrl(data.cpEditUrl, { fresh: 1 });
            } else {
                const slideout = Craft.createElementEditor(this.elementType, {
                    siteId: this.siteId,
                    elementId: data.event.id,
                    draftId: data.event.draftId,
                    params: {
                        fresh: 1,
                    },
                });
               
                slideout.on('submit', () => {
                    this.clearSearch();
                    this.setSelectedSortAttribute('dateCreated', 'desc');
                    this.selectElementAfterUpdate(data.event.id);
                    this.updateElements();
                });
            }
        })
        .finally(() => {
            this.$newEventBtn.removeClass('loading');
        });
    },
});

// Register it!
Craft.registerElementIndexClass('verbb\\events\\elements\\Event', Craft.Events.EventIndex);

})(jQuery);

