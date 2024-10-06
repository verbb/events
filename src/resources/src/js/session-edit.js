// ==========================================================================

// Events Plugin for Craft CMS
// Author: Verbb - https://verbb.io/

// ==========================================================================

(function($) {

if (typeof Craft.Events === 'undefined') {
    Craft.Events = {};
}

Craft.Events.SessionEdit = Garnish.Base.extend({
    init: function(id) {
        // Target the entire slide-out pane (we can't edit sessions in the traditional sense outside)
        this.$pane = $('#' + id);
        this.$container = this.$pane.parents('form');

        this.$startDate = this.$container.find('[data-attribute="start-date"]');
        this.$startDateInput = this.$startDate.find('.datewrapper input[type="text"]');
        this.$startTimeInput = this.$startDate.find('.timewrapper input[type="text"]');
        this.$startDateOffsetInput = this.$container.find('[name*="[occurrenceRange][startDateOffset]"]');

        this.$endDate = this.$container.find('[data-attribute="end-date"]');
        this.$endDateInput = this.$endDate.find('.datewrapper input[type="text"]');
        this.$endTimeInput = this.$endDate.find('.timewrapper input[type="text"]');
        this.$endDateOffsetInput = this.$container.find('[name*="[occurrenceRange][endDateOffset]"]');

        this.$frequency = this.$container.find('[data-attribute="frequency-data-type"] select');
        this.frequency = this.$frequency.length ? this.$frequency.val() : null;

        this.$occurrenceType = this.$container.find('.occurrence-range-field input');
        this.$occurrenceTypeStartDate = this.$container.find('[data-attribute="occurrence-range-start-date"]');
        this.$occurrenceTypeStartDateInput = this.$occurrenceTypeStartDate.find('.datewrapper input[type="text"]');
        this.$occurrenceTypeEndDate = this.$container.find('[data-attribute="occurrence-range-end-date"]');
        this.$occurrenceTypeEndDateInput = this.$occurrenceTypeEndDate.find('.datewrapper input[type="text"]');

        this.$allDay = this.$container.find('[data-attribute="allDay"] .lightswitch');

        // Store the current dates in case we modify them to determine a diff
        this.currentStartDate = this.getDate(this.$startDate);
        this.currentStartTime = this.getTime(this.$startDate);
        this.currentEndDate = this.getDate(this.$endDate);
        this.currentEndTime = this.getTime(this.$endDate);

        this.addListener(this.$allDay, 'change', 'checkAllDay');

        // Week "Repeat On" values must include the current day
        this.addListener(this.$startDateInput, 'change', 'updateWeekRepeatOptions');
        this.addListener(this.$startTimeInput, 'change', 'updateWeekRepeatOptions');

        // Month "Repeat On" dates are dynamic
        this.addListener(this.$startDateInput, 'change', 'updateMonthRepeatOptions');
        this.addListener(this.$startTimeInput, 'change', 'updateMonthRepeatOptions');

        // When editing an occurence, we should update the tooltip with the proposed changes
        this.addListener(this.$startDateInput, 'change', 'updateOccurenceHint');
        this.addListener(this.$startTimeInput, 'change', 'updateOccurenceHint');
        this.addListener(this.$endDateInput, 'change', 'updateOccurenceHint');
        this.addListener(this.$endTimeInput, 'change', 'updateOccurenceHint');
        this.addListener(this.$occurrenceType, 'change', 'updateOccurenceHint');
        this.addListener(this.$occurrenceTypeStartDateInput, 'change', 'updateOccurenceHint');
        this.addListener(this.$occurrenceTypeEndDateInput, 'change', 'updateOccurenceHint');

        // Update the offset values to send to the server when date/times change
        this.addListener(this.$startDateInput, 'change', 'updateOffsets');
        this.addListener(this.$startTimeInput, 'change', 'updateOffsets');
        this.addListener(this.$endDateInput, 'change', 'updateOffsets');
        this.addListener(this.$endTimeInput, 'change', 'updateOffsets');

        // For some reason the occurence toggle isn't working...
        this.addListener(this.$occurrenceType, 'change', 'onToggleChange');

        // Trigger immediately (even if not using)
        this.$startDateInput.trigger('change');
        this.$endDateInput.trigger('change');
        this.$occurrenceType.trigger('change');
        this.$allDay.trigger('change');
    },

    updateOffsets(e) {
        // Get the new start and end date/times
        let startDate = this.getDate(this.$startDate);
        let endDate = this.getDate(this.$endDate);

        if (!startDate || !endDate) {
            return;
        }

        // Offset in seconds
        const startOffset = Math.floor((startDate - this.currentStartDate) / 1000);
        const endOffset = Math.floor((endDate - this.currentEndDate) / 1000);

        this.$startDateOffsetInput.val(startOffset);
        this.$endDateOffsetInput.val(endOffset);
    },

    onToggleChange(e) {
        if ($(e.target).prop('checked') && $(e.target).val() === 'custom') {
            this.$container.find('.apply-changes--custom').removeClass('hidden');
        } else {
            this.$container.find('.apply-changes--custom').addClass('hidden');
        }
    },

    updateOccurenceHint(e) {
        const $hint = this.$container.find('.occurrence-hint');

        if (!$hint.length) {
            return;
        }

        // Get the new start and end date/times
        let startDate = this.getDate(this.$startDate);
        let endDate = this.getDate(this.$endDate);

        if (!startDate || !endDate) {
            return;
        }

        // Calculate the differences
        let startDiff = startDate - this.currentStartDate;
        let endDiff = endDate - this.currentEndDate;

        let startMessage = this.getDifferenceMessage(startDiff, 'start');
        let endMessage = this.getDifferenceMessage(endDiff, 'finish');

        // Construct the final hint message
        let hintMessage = '';
        let occurrenceText = '';

        // Determine the occurrence type
        const occurrenceType = this.$container.find('.occurrence-range-field input:checked').val();

        switch (occurrenceType) {
            case 'single':
                occurrenceText = Craft.t('events', 'This occurrence');
                break;
            case 'all':
                occurrenceText = Craft.t('events', 'All occurrences');
                break;
            case 'future':
                occurrenceText = Craft.t('events', 'This and all future occurrences');
                break;
            case 'custom':
                let customStart = this.getDate(this.$container.find('[data-attribute="occurrence-range-start-date"]'));
                let customEnd = this.getDate(this.$container.find('[data-attribute="occurrence-range-end-date"]'));

                if (customStart && customEnd) {
                    let userLocale = navigator.language || 'default';

                    occurrenceText = Craft.t('events', 'All occurrences between {start} and {end}', {
                        start: customStart.toLocaleString(userLocale, {
                            year: 'numeric',
                            month: 'long',
                            day: '2-digit',
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true,
                        }),
                        end: customEnd.toLocaleString(userLocale, {
                            year: 'numeric',
                            month: 'long',
                            day: '2-digit',
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true,
                        }),
                    });
                } else {
                    hintMessage = '';
                }

                break;
            default:
                occurrenceText = Craft.t('events', 'This occurrence');
        }

        if (occurrenceText && startMessage && endMessage) {
            hintMessage = Craft.t('events', '{occurrenceText} will now {startMessage} and {endMessage}.', {
                occurrenceText: occurrenceText,
                startMessage: startMessage,
                endMessage: endMessage,
            });
        } else if (occurrenceText && startMessage) {
            hintMessage = Craft.t('events', '{occurrenceText} will now {startMessage}.', {
                occurrenceText: occurrenceText,
                startMessage: startMessage,
            });
        } else if (occurrenceText && endMessage) {
            hintMessage = Craft.t('events', '{occurrenceText} will now {endMessage}.', {
                occurrenceText: occurrenceText,
                endMessage: endMessage,
            });
        }

        (hintMessage === '') ? $hint.addClass('hidden') : $hint.removeClass('hidden');

        $hint.find('.hint-text').text(hintMessage);
    },

    updateWeekRepeatOptions(e) {
        let startDate = this.getDate(this.$startDate);

        if (!startDate) {
            return;
        }

        // Get the day of the week (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
        let dayOfWeekIndex = startDate.getDay();

        // Map index to day name as per your checkbox values (e.g., "sunday", "monday")
        const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        let dayName = dayNames[dayOfWeekIndex];

        // Find the checkboxes for weekly repeat days
        this.$weeklyRepeatCheckboxes = this.$container.find('[data-attribute="frequency-data-weekly-repeat-days"] input[type="checkbox"]');

        if (!this.$weeklyRepeatCheckboxes.length) {
            return;
        }

        // Remove the change listener from all checkboxes to reset their state
        this.$weeklyRepeatCheckboxes.off('change');

        // Find the checkbox that matches the newly selected day
        let $checkboxToEnforce = this.$weeklyRepeatCheckboxes.filter(`[value="${dayName}"]`);

        if ($checkboxToEnforce.length) {
            // Check the checkbox for the selected day
            $checkboxToEnforce.prop('checked', true);

            // Add a change listener to this checkbox to prevent unchecking
            $checkboxToEnforce.on('change', function() {
                if (!$(this).prop('checked')) {
                    $(this).prop('checked', true);
                }
            });
        }
    },

    updateMonthRepeatOptions(e) {
        let startDate = this.getDate(this.$startDate);

        if (!startDate) {
            return;
        }

        // Get day of the month (17th, 18th, etc.)
        let dayOfMonth = startDate.getDate();
        let daySuffix = this.getDaySuffix(dayOfMonth);

        // Get the user's locale
        let userLocale = navigator.language || 'default';
        
        // Get day of the week (e.g., "Tuesday")
        let dayOfWeek = startDate.toLocaleString(userLocale, { weekday: 'long' });
        
        // Get the nth occurrence of the day in the month (e.g., "third")
        let ordinalWeek = this.getOrdinalWeek(startDate, userLocale);
        
        // Create options
        let option1 = Craft.t('events', 'On the {date}', { date: `${dayOfMonth}${daySuffix}` });
        let option2 = Craft.t('events', 'On the {date}', { date: `${ordinalWeek} ${dayOfWeek}` });
        
        // Clear existing options and add new ones
        this.$monthlyRepeatSelect = this.$container.find('[data-attribute="frequency-data-monthly-repeat-day"] select');
        
        if (this.$monthlyRepeatSelect.length) {
            $(this.$monthlyRepeatSelect).empty();
            $(this.$monthlyRepeatSelect).append(new Option(option1, 'onDate'));
            $(this.$monthlyRepeatSelect).append(new Option(option2, 'onDay'));
        }
    },

    getDate($el) {
        if (!$el || !$el.length) {
            return null;
        }

        const $dateInput = $el.find('.datewrapper input[type="text"]');
        const $timeInput = $el.find('.timewrapper input[type="text"]');

        if (!$dateInput.length && $timeInput.length) {
            return $timeInput.timepicker('getTime');
        }

        if ($dateInput.length && !$timeInput.length) {
            return $dateInput.datepicker('getDate');
        }

        if ($dateInput.length && $timeInput.length) {
            var date = $dateInput.datepicker('getDate');
            var datetime = $timeInput.timepicker('getTime', date);

            return datetime;
        }

        return null;
    },

    getTime($el) {
        if (!$el || !$el.length) {
            return null;
        }

        const $timeInput = $el.find('.timewrapper input[type="text"]');

        if ($timeInput.length) {
            return $timeInput.val();
        }

        return null;
    },

    getDaySuffix(day) {
        // Helper function to get day suffix (st, nd, rd, th)
        if (day > 3 && day < 21) return 'th';

        switch (day % 10) {
            case 1: return 'st';
            case 2: return 'nd';
            case 3: return 'rd';
            default: return 'th';
        }
    },

    getOrdinalWeek(date, locale) {
        let day = date.getDate();
        let weekNumber = Math.floor((day - 1) / 7) + 1;

        // Create an array of ordinal numbers
        let ordinalNumbers = new Intl.ListFormat(locale, { type: 'unit', style: 'narrow' });
        let ordinals = ['first', 'second', 'third', 'fourth', 'fifth'];
        
        return ordinals[weekNumber - 1];
    },

    getDifferenceMessage: function(diff, type) {
        if (diff === 0) {
            return '';
        }

        let isLater = diff > 0;;

        diff = Math.abs(diff);

        // Convert diff to days, hours, and minutes
        let minutes = Math.floor((diff / (1000 * 60)) % 60);
        let hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
        let days = Math.floor(diff / (1000 * 60 * 60 * 24));

        let parts = [];

        // While this is more duplication, the aim here is to provide as few translatable strings as possible
        // and ensuring that we don't have lots of singular "start" or "later" strings to translate, which are
        // tricky to translate due to their lack of context. However, we keep `day(s)`, `hour(s)`, `minute(s)`
        // as their own strings, because they're contextually easier to translate.
        const items = [];

        if (days > 0) {
            items.push(Craft.t('events', '{num, number} {num, plural, =1{day} other{days}}', { num: days }));
        }

        if (hours > 0) {
            items.push(Craft.t('events', '{num, number} {num, plural, =1{hour} other{hours}}', { num: hours }));
        }

        if (minutes > 0) {
            items.push(Craft.t('events', '{num, number} {num, plural, =1{minute} other{minutes}}', { num: minutes }));
        }

        let diffValues = items[0] || '';

        if (items.length === 2) {
            diffValues = Craft.t('events', '{diff1} and {diff2}', {
                diff1: items[0],
                diff2: items[1]
            });
        } else if (items.length === 3) {
            diffValues = Craft.t('events', '{diff1}, {diff2} and {diff3}', {
                diff1: items[0],
                diff2: items[1],
                diff3: items[2],
            });
        }

        if (type === 'start') {
            if (isLater) {
                parts.push(Craft.t('events', 'start {diff} later', { diff: diffValues }));
            } else {
                parts.push(Craft.t('events', 'start {diff} earlier', { diff: diffValues }));
            }
        } else if (type === 'finish') {
            if (isLater) {
                parts.push(Craft.t('events', 'finish {diff} later', { diff: diffValues }));
            } else {
                parts.push(Craft.t('events', 'finish {diff} earlier', { diff: diffValues }));
            }
        }

        if (parts.length === 2) {
            return Craft.t('events', '{diff1} and {diff2}', {
                diff1: parts[0],
                diff2: items[1],
            });
        }

        return parts.join('');
    },

    checkAllDay: function() {
        if (this.$allDay.hasClass('on')) {
            this.hideTime();
        } else {
            this.showTime();
        }
    },

    hideTime: function() {
        this.$startTimeInput.addClass('disabled');
        this.$startTimeInput.prop('disabled', true);
        this.currentStartTime = this.$startTimeInput.val();
        this.$startTimeInput.val('12:00 AM');

        this.$endTimeInput.addClass('disabled');
        this.$endTimeInput.prop('disabled', true);
        this.currentEndTime = this.$endTimeInput.val();
        this.$endTimeInput.val('12:00 AM');
    },

    showTime: function() {
        this.$startTimeInput.removeClass('disabled');
        this.$startTimeInput.prop('disabled', false);
        this.$startTimeInput.val(this.currentStartTime);
        
        this.$endTimeInput.removeClass('disabled');
        this.$endTimeInput.prop('disabled', false);
        this.$endTimeInput.val(this.currentEndTime);
    },
});


})(jQuery);
