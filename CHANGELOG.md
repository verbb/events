# Changelog

## 2.0.0-beta.10 - 2023-05-27

### Fixed
- Fix an error for Feed Me when no event type is set.
- Update PDF rendering to use correct events.

## 2.0.0-beta.9 - 2023-03-02

### Changed
- Use `defineRules()` instead of `rules()` to allow validation overrides properly.
- Update `Endroid\QrCode` code for new `endroid/qr-code` package version.
- Only admins are now allowed to access plugin settings.

### Fixed
- Fix expired events still showing their front-end templates.
- Fix type for `customer()` purchased ticket element query.
- Fix Customer changeover to User.
- Fix errors when saving events with invalid tickets.

## 2.0.0-beta.8 - 2022-12-15

### Changed
- Update `endroid/qr-code:^4.0.0` dependancy.

### Fixed
- Fix Customer changeover to User (Commerce change).

## 2.0.0-beta.7 - 2022-12-01

### Fixed
- Fix an error when editing an event with validation errors.

## 2.0.0-beta.6 - 2022-11-30

### Fixed
- Fix an error when editing an event.
- Fix an error when creating an event with empty ticket quantities.
- Fix an error when deleting an event type.

## 2.0.0-beta.5 - 2022-11-22

### Fixed
- Fix being unable to manage Events and Purchased tickets in the control panel due to Craft 4.3.2 changes.

## 2.0.0-beta.4 - 2022-09-25

### Added
- Add missing English Translations.

### Fixed
- Fix an error when creating an event with an empty capacity.
- Fix an error running `resave` console commands.
- Fix querying events by type not working correctly for multiple ticket types.
- Fix querying purchased tickets by event type not working correctly for multiple ticket types.
- Fix an error when uninstalling.
- Fix being unable to view purchased ticket in the control panel.

## 2.0.0-beta.3 - 2022-07-01

### Fixed
- Fix an incompatibility with SEOmatic.
- Fix welcome screen icon alignment.

## 2.0.0-beta.2 - 2022-06-08

### Added
- Add resave console command for elements.
- Add checks for registering events for performance.
- Add `archiveTableIfExists()` to install migration.

### Changed
- Memoize all services for performance.
- Rename record classes.
- Rename base plugin methods.
- `jsvrcek/ics` dependency updated for php 8.0 support.
- Now requires Events `1.4.20` in order to update from Craft 3.

### Fixed
- Fix `project-config/rebuild` support.
- Fix Feed Me support.
- Fix an error when uninstalling.
- Fix an error with Craft 4.
- Fix an error with Commerce.
- Fix deprecations.

### Removed
- Removed `Ticket::getPurchasedTicketsForLineItem`.
- Update `minVersionRequired`.
- Remove unneeded migrations.

## 2.0.0-beta.1 - 2022-03-10

### Changed
- Now requires PHP `^8.0.2`.
- Now requires Craft `^4.0.0-beta.1`.
- Now requires Craft Commerce `^4.0.0-beta.1`.

## 1.4.26 - 2023-10-08

### Fixed
- Fix an issue when creating new sites and not propagating event types correctly.

## 1.4.25 - 2023-05-27

### Added
- Add resave commands for events.

## 1.4.24 - 2022-12-25

### Added
- Add error state to the Date/Time tab if a field has errors. (thanks @anchovy).

### Fixed
- Fix an error when saving an event with un-selected ticket type for a ticket.

## 1.4.23 - 2022-09-25

### Added
- Add validation to event ticket available from/to in relation to each other and the event end datetime.
- Add validation for events when setting the start date to be later than the end date.
- Add tabs for events not working correctly in the control panel.

### Fixed
- Fix a validation error with ticket pricing in the control panel.
- Fix an issue when checkin/uncheckin for purchased tickets wasn’t working.
- Fix a potential issue when generating QR codes.

## 1.4.22 - 2022-08-25

### Fixed
- Fix querying events by type not working correctly for multiple ticket types.
- Fix querying purchased tickets by event type not working correctly for multiple ticket types.

## 1.4.21 - 2022-06-28

### Changed
- `jsvrcek/ics` dependency updated for php 8.0 support.

### Fixed
- Fix PDF generation URLs not being correct in headless environments.

## 1.4.20 - 2021-10-30

### Fixed
- Fix an error with querying customer tickets with `sql_mode=only_full_group_by`.

## 1.4.19.2 - 2021-10-12

### Fixed
- Fix an error being thrown when SEOmatic wasn't installed.

## 1.4.19.1 - 2021-10-05

### Fixed
- Fix an error when saving a Event type and the SEOmatic integration.

## 1.4.19 - 2021-10-01

### Added
- Add support for SEOmatic.

### Changed
- Update "Ticket PDF Template" plugin setting field to auto-suggest templates.
- Update "Check-in Template" plugin setting field to auto-suggest templates.

### Fixed
- Fix check-in URL shortcut in control panel (when editing a purchased ticket) not being correct.
- Fix redirecting back to the purchased ticket index when checking/unchecking in from the control panel.

## 1.4.18 - 2021-09-02

### Added
- Add `ticketsShippable` plugin setting.
- Add `craft.events.ticketTypes()`.
- Add order email to "Customer" column for purchased tickets, when ordered by a guest.
- Allow custom fields to be saved when calling `events/purchased-tickets/checkin`.
- Allow `checkedIn` and `checkedIn` attributes to be saved when calling `events/purchased-tickets/save`.

### Fixed
- Fix customer email column.

## 1.4.17 - 2021-07-08

### Added
- Add “Require User Login” plugin setting for checking-in tickets.
- Add “Check-in tickets” user permission.

### Fixed
- Fix an error when generating PDFs and custom fonts, where the temporary folder isn’t writable (or created).
- Fix an error when viewing purchased tickets index where users may not have a first/last name.

## 1.4.16 - 2021-06-04

### Fixed
- Fix an error with purchased tickets when no field layout was set.
- Fix an error when generating an ICS link and setting the location.

## 1.4.15 - 2021-01-15

### Added
- Add `events.edit.details` hook to the edit event page. (thanks @joshangell).
- Add `events.edit.actionbutton` hook to event edit page. (thanks @joshangell).
- Add `ticketType` and `ticketTypeId` query params to Purchase Ticket elements.
- Add `pluralDisplayName` to Event, Purchased Ticket, Ticket and Ticket Type elements.
- Add duplicate element action for Purchased Ticket elements.
- Add duplicate element action for Event elements.
- Add `ticketType` and `ticketTypeId` query params to Purchase Ticket element queries.

## 1.4.14 - 2020-08-20

### Fixed
- Fix migration error for older installs where event types had not re-saved to populate ICS settings.

## 1.4.13 - 2020-08-15

### Fixed
- Fix migration error in Postgres.

## 1.4.12 - 2020-06-17

### Fixed
- Allow `ticketTypes` to be edited with `allowAdminChanges` set to false.

## 1.4.11 - 2020-05-14

### Fixed
- Fix tickets not being site-aware.
- Fix potential error for purchased tickets with no ticket.

## 1.4.10 - 2020-05-10

### Fixed
- Fix saving event content in the incorrect site.

## 1.4.9 - 2020-05-05

### Fixed
- Fix checkin controller redirecting incorrectly.
- Fix site dropdown redirecting incorrectly for events.

## 1.4.8 - 2020-04-28

### Fixed
- Fix typo for Feed Me mapping.

## 1.4.7 - 2020-04-16

### Fixed
- Fix logging error `Call to undefined method setFileLogging()`.

## 1.4.6 - 2020-04-15

### Changed
- File logging now checks if the overall Craft app uses file logging.
- Log files now only include `GET` and `POST` additional variables.

## 1.4.5 - 2020-04-14

### Added
- Add Feed Me support for importing events.
- Add ICS support. See [docs](https://verbb.io/craft-plugins/events/docs/template-guides/ics-exporting).

### Fixed
- Only allow editing of event/ticket types if editable.
- Ensure plugin project config is removed when uninstalling.

## 1.4.4 - 2020-03-16

### Fixed
- Allows getting the ticket download via the purchasedTicket id. (thanks @samuelbirch).
- Use existing `ticketSku` if not passed when saving purchasedTicket. (thanks @samuelbirch).
- Fix error in `purchasedTicket->getTicketType()`. (thanks @samuelbirch).

## 1.4.3 - 2020-03-06

### Fixed
- When generating purchased tickets use serialized field values so that elements etc get regenerated. (thanks @joshangell).
- Fix incorrect error from failed ticket validation.

## 1.4.2 - 2020-02-28

### Changed
- Updated ticket element sources to match that of purchased tickets. (thanks @samuelbirch).

### Fixed
- Fix fatal migration error.
- Fix saving of custom fields for a purchased ticket. (thanks @samuelbirch).

## 1.4.1 - 2020-02-26

### Fixed
- Fix purchased ticket custom fields in element index.

## 1.4.0 - 2020-02-25

### Added 
- Add un-checkin function for purchased tickets. (thanks @samuelbirch).
- Add custom fields to purchased tickets (inherited from the ticket's type). (thanks @samuelbirch).
- Add controller action for manual purchased ticket checkin or un-checkin. (thanks @samuelbirch).
- Add checkin/un-checkin button on the purchased ticket detail page. (thanks @samuelbirch).
- Add checkin action to the purchased ticket index page. (thanks @samuelbirch).
- Upon checkout completion, if a line item option matches the handle of a custom field on a purchased ticket, it'll now push that value onto the custom field. This means you won't have to dive through line item options from now on, instead accessing that info on a purchased ticket element.
- Add customer email and name to purchased ticket element index table, for viewing event attendees.

### Changed
- Update ticket `availableCapacity` to return the events availableCapacity if not quantity is not set. (thanks @samuelbirch).
- Tickets are un-editable by default. (thanks @samuelbirch).
- Purchased tickets are now grouped by their event type in the control panel. (thanks @samuelbirch).

### Fixed
- Fix some translations in the CP not being to the Events plugin's scope.
- Fix minor style issue for meta sidebar for purchased tickets

## 1.3.2 - 2020-02-02

### Fixed
- Fix potential errors when no event or ticket exists anymore for a purchased ticket

## 1.3.1 - 2020-01-30

### Fixed
- Fix check-in error when no ticket is found.

## 1.3.0 - 2020-01-29

### Added
- Craft 3.4 compatibility.
- Commerce 3.0 compatibility.

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
