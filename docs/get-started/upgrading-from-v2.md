# Upgrading from v2
While the [changelog](https://github.com/verbb/events/blob/craft-5/CHANGELOG.md) is the most comprehensive list of changes, this guide provides high-level overview and organizes changes by category.

## Architecture
The biggest change to Events 3 is the major change in content architecture for the plugin and managing events. It's best to familiarise yourself with the change in behaviour, particularly if you customise or extend the Events plugin.

Events 3 will migrate everything for you seamlessly, with only minimal breaking changes.

### Events
Events no longer define the start and end dates for an event. Events still exist as elements, but act as a container around your Sessions and Ticket Types.

This is despite `startDate` and `endDate` still existing on an Event as properties and available as [query params](docs:getting-elements/event-queries), but these now reflect the start and end dates of your collection of Sessions. For any Events created on Events 2, there will be no change in behaviour as Events 2 events will only have a single Session, migrated from the previous architecture.

### Sessions
Sessions are where you define your dates for an event. Events can have multiple sessions. While sessions can be set to be recurring, coming from Events 2, you'll have a single session based off your old event dates.

### Ticket Types
Ticket Types have been repurposed from Events 2, which used them for a different use-case altogether. Instead of creating Tickets in your event which were related to a Ticket Type, in Events 3, you use Ticket Types to define what types of tickets you want.

For example, you might have defined two Tickets in Events 2 for an event:
- Adult, 100 capacity, $55 each
- Child, 100 capacity, $25 each

These were defined as purchasables, but with the introduction of recurring sessions, you no longer have the ability to create tickets manually. Instead, you define this content exactly as above, but as Ticket Types. As such, you can think of Ticket Types as rules and settings that are used to generate tickets.

This is because unlike in Events 2, Tickets are now automatically generated based on the event sessions and ticket types.

### Tickets
Tickets are automatically generated based on the event sessions and ticket types. For every session and ticket type combination, a purchasable ticket is created for your users to add to their cart an purchase.

This is in contrast to Events 2, where you defined the ticket purchasables in the event.

You'll need to regenerate any tickets when you add or remove sessions or ticket types in an event. This can be done from the control panel. Modifying session or ticket types won't require ticket regeneration, being dynamic in nature.

## Templates
With the introduction of sessions and ticket types, you may want to alter your templates. However, things should be backward compatible other than some minor changes with model properties (as detailed below).

## Check In URL
The URL for checking in a purchased ticket has changed. We no longer use the SKU for the purchased ticket, instead we use the UID.

```
// Events 2
https://my-site.test/actions/events/ticket/checkin?sku=3dmMKPGUJu

// Events 3
https://my-site.test/actions/events/tickets/check-in?uid=0907288f-143f-4716-a89f-a35e125fb561
```

In addition, hitting this URL will not automatically check in a user anymore. This prevents accidential checking in when scanning the QR code for example. This will now load your check in template (Events plugin default, or your own), with an action (and warning) for the user to check in.

## Changed Properties
The following properties have been changed.

Old | What to do instead
--- | ---
| `verbb\events\elements\Ticket::name` | `verbb\events\elements\Ticket::title`
| `verbb\events\elements\Ticket::purchasableId` | `verbb\events\elements\Ticket::id`
