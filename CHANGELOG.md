# Changelog

## 1.2.3 - 2020-05-10

### Fixed
- Fix saving event content in the incorrect site.

## 1.2.2 - 2020-05-06

### Fixed
- Fix site dropdown redirecting incorrectly for events.

## 1.2.1 - 2020-01-18

### Added
- Add `customer` query param to `craft.events.purchasedTickets()`.
- Add `customer` query param to `craft.events.tickets()`.
- Add `customer` query param to `craft.events.events()`.
- Add `craft.events.hasTicket(order)`.
- Add Event name, Ticket name, or Order reference to list of searchable attributes for purchased ticket elements.

## 1.2.0 - 2020-01-07

### Added
- Add purchased tickets screen to CP, showing individual tickets and purchases.
- Purchased tickets are now elements! Edit and delete purchased tickets is supported.
- Show purchased tickets in the CP with their quantity, and a summary table below the ticket definitions.

### Changed
- Improve ticket-type custom field display. Now, whenever a ticket type is selected, all ticket type fields are shown, rather than having to save and reload the event.

### Fixed
- Cleanup purchasable options.
- Fix event types permission.
- Fix available ticket quantity checks.

## 1.1.7.1 - 2019-12-02

### Fixed
- Fixed typo. (thanks @samuelbirch).

## 1.1.7 - 2019-12-02

### Added
- Add `cp.events.event.edit.details` template hook. (thanks @samuelbirch).
- Add better warning when Commerce isn’t installed.
- Add `hasTickets` to event types.
- Add confirm when deleting an events’ ticket type.

### Changed
- No longer override Total Capacity field when changing ticket type quantity.
- Provide better handling for either event capacity or ticket quantity not being set.
- Do not include custom fields in ticket snapshot.

### Fixed
- Fix ticket type permissions.
- Fix ticket check-in not working.
- Fix error thrown in `isTicket`.

## 1.1.6 - 2019-09-03

### Added
- Add Check-in template and nicer default template instead of JSON.

### Fixed
- Fix error when running live preview on an event.

## 1.1.5 - 2019-08-09

### Fixed 
- Fix incorrect available ticket calculation when adding to cart (again).

## 1.1.4 - 2019-08-08

### Fixed 
- Fixed sharing preview not working correctly for anonymous requests.
- Fix capacity database column set incorrectly, in some cases (upgrading from older plugin versions).
- Fix lack of total event capacity checks when adding to cart.
- Fix `isAvailable` checks on event and ticket incorrectly reporting what’s available.
- Fix incorrect available ticket calculation when adding to cart.

## 1.1.3 - 2019-07-28

### Fixed
- Fix `craft.events.events()` not including currently-on events.

## 1.1.2 - 2019-07-25

### Added
- Add support for Klaviyo Connect plugin.

### Fixed
- Fix error when trying to save an event after validation failed.
- Fix deleting ticket type not deleting associated tickets.
- Fix error with empty tickets for an event.
- Allow Commerce 3 to work.
- Fix invalid element type class being registered for events.
- Fix error with project config rebuild. (thanks @ttempleton).
- Fix incorrectly reporting tickets as unavailable.
- Fix being unable to delete tickets from event.

## 1.1.1 - 2019-05-17

### Fixed
- Fix error thrown when creating new tickets for event.
- Show an error settings icon when saving an event with required ticket type fields not set.

## 1.1.0 - 2019-05-12

### Added
- Add Title Format to event types.

### Changed
- When calling `craft.events.events()` in your templates, it will now only show events with a start date after today.
- Change default sort to be the startDate ascending (oldest first).
- Tickets are now not required when saving an event.

### Fixed
- Fix incorrect editable event types being returned.
- Fix error occurring in an events field modal.
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
