# Permissions

Events' permissions let you decide who manages which kinds of events, and who works with the tickets customers have bought. A festival might let its programme team create and edit events of one event type, give the box office access to purchased tickets, and give door staff only the permission to check tickets in. Set them in **Settings → Users → User Groups**, or on an individual user's **Permissions** tab.

**Access Events** is listed under **General**, with Craft's other permissions for reaching each plugin's section. The others are listed under an **Events** heading.

## Event Type Permissions

Each event type gets its own set of permissions, generated from the event types you've created in **Events → Event Types**. They're listed first, one group per event type, with the type's name in the top permission. A new event type adds a new group, which nobody but admins holds until you grant it.

| Permission | What It Allows |
| --- | --- |
| View “{type}” events | See events of this type in the Events index and open them. |
| Create events | Create new events of this type and save their unpublished drafts, and duplicate existing ones together with **Edit events**. |
| Edit events | Save changes to existing events of this type. |
| Delete events | Delete events of this type. |
| View sessions | See the sessions of this type's events. |
| Create sessions | Add sessions to this type's events, and duplicate them together with **Edit sessions**. |
| Edit sessions | Save changes to existing sessions. |
| Delete sessions | Delete sessions. |
| View ticket types | See the ticket types of this type's events. |
| Create ticket types | Add ticket types to this type's events, and duplicate them together with **Edit ticket types**. |
| Edit ticket types | Save changes to existing ticket types. |
| Delete ticket types | Delete ticket types. |

Everything for an event type is nested under its **View “{type}” events** permission. **View sessions** and **View ticket types** are nested again, with their create, edit and delete permissions below them. Element indexes for events, sessions, ticket types and tickets only include the event types a person can view and, on a multi-site install, the sites they can edit.

## Purchased Ticket Permissions

These apply to every event type.

| Permission | What It Allows |
| --- | --- |
| View purchased tickets | See **Events → Purchased Tickets**, its index, and the purchased tickets listed on event, session and ticket type edit pages. |
| Edit purchased tickets | Open a purchased ticket's edit page and save it, check it in and out from the control panel, and use the **Cancel tickets** and **Restore tickets** actions. |
| Delete purchased tickets | Delete purchased tickets. |
| Check in purchased tickets | Check tickets in from their check-in URL when `checkinLogin` is enabled. |

**Edit purchased tickets** and **Delete purchased tickets** are nested under **View purchased tickets**. Without **Edit purchased tickets**, someone can see purchased tickets in the index but can't open them.

**Check in purchased tickets** only matters when `checkinLogin` is enabled. While it's off, anyone with a ticket's check-in URL can confirm the check-in, so turn on `checkinLogin` and give this permission to door staff when you want check-in limited to signed-in staff. It's separate from the control panel's check-in, which needs **Edit purchased tickets**. See [Ticket Check-In](ticket-check-in.md).

## Admins and Riskier Permissions

Admins can always do all of this. **Event Types** and **Settings** are for admins only, and only appear where Craft's `allowAdminChanges` setting is on.

Deleting events, sessions and ticket types affects what customers can buy, so keep the delete permissions for the people who plan each event type. **Edit purchased tickets** can cancel tickets customers have paid for, so give it to staff who handle orders and refunds rather than everyone working on the door.
