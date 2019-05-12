# Changelog

## 1.1.0 - 2019-05-12

### Added
- Add Title Format to event types.

### Changed
- When calling `craft.events.events()` in your templates, it will now only show events with a start date after today.
- Change default sort to be the startDate ascending (oldest first).
- Tickets are now not required when saving an event.

### Fixed
- Fix incorrect editable event types being returned.
- Fix error ocurring in an events field modal.
- Add missing `startDate` and `endDate` query params.
- Ensure tickets have a title value.

## 1.0.2.1 - 2019-04-30

### Fixed
- Fix incorrect casing of `events.js`. (thanks @johnnynotsolucky).  

## 1.0.2 - 2019-04-30

### Changed
- Quantity for tickets is no longer required.

### Fixed
- Fix deprecation error. 
- Fix filtering events by event type.
- Fix errors when trying to view tickets in an element select interface.
- Fix being unable to add new events in some instances.

## 1.0.1 - 2019-04-24

### Fixed
- Ensure Commerce 2.1 is required.
- Update `endroid/qrcode` to `endroid/qr-code`.
- Fixed some issues during install.

## 1.0.0 - 2019-04-23

### Added
- Craft 3 version.

## 0.1.1 - 2018-07-31

### Fixed
- Ensure cart-adding supports notes and options.
- Switch events to be output ascending by default.
- Fix incorrect references for PDF config files.
- Fix displaying price incorrectly for some locales.

## 0.1.0 - 2018-02-09

### Added
- Initial beta release.
