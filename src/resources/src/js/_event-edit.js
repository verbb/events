(function($) {

if (typeof Craft.Events === 'undefined') {
    Craft.Events = {};
}

Craft.Events.EventEdit = Garnish.Base.extend({
    $container: null,

    currentStartTime: null,
    currentEndTime: null,
    $startDate: null,
    $startTime: null,
    $endDate: null,
    $endTime: null,
    $allDay: null,

    init: function(id) {
        this.$container = $('#' + id);

        this.$startDate = this.$container.find('[name="startDate[date]"]');
        this.$startTime = this.$container.find('[name="startDate[time]"]');
        this.$endDate = this.$container.find('[name="endDate[date]"]');
        this.$endTime = this.$container.find('[name="endDate[time]"]');
        this.$allDay = this.$container.find('#allDay-field .lightswitch');

        if (this.$allDay.hasClass('on')) {
            this.checkAllDay();
        }

        this.addListener(this.$allDay, 'change', 'checkAllDay');
        this.addListener(this.$startDate, 'change', 'updateEndDate');
        this.addListener(this.$endDate, 'change', 'updateStartDate');
    },

    checkAllDay: function() {
        if (this.$allDay.hasClass('on')) {
            this.hideTime();
        } else {
            this.showTime();
        }
    },

    hideTime: function() {
        this.$startTime.addClass('disabled');
        this.$startTime.prop('disabled', true);
        this.currentStartTime = this.$startTime.val();
        this.$startTime.val('12:00 AM');

        this.$endTime.addClass('disabled');
        this.$endTime.prop('disabled', true);
        this.currentEndTime = this.$endTime.val();
        this.$endTime.val('12:00 AM');
    },

    showTime: function() {
        this.$startTime.removeClass('disabled');
        this.$startTime.prop('disabled', false);
        this.$startTime.val(this.currentStartTime);
        
        this.$endTime.removeClass('disabled');
        this.$endTime.prop('disabled', false);
        this.$endTime.val(this.currentEndTime);
    },

    // Set end date to start date, when end date is before start date or empty
    updateEndDate: function() {
        var startDate = this.$startDate.val();
        var endDate = this.$endDate.val();

        if (Date.parse(startDate) > Date.parse(endDate) || endDate === '') {
            this.$endDate.val(this.$startDate.val());
        }
    },

    // Set start date to end date, when start date is behind end date or empty
    updateStartDate: function() {
        var startDate = this.$startDate.val();
        var endDate = this.$endDate.val();

        if (Date.parse(endDate) < Date.parse(startDate) || startDate === '') {
            this.$startDate.val(this.$endDate.val());
        }
    },
});


})(jQuery);
