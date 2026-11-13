# Purchased Ticket Queries

You can fetch purchased tickets in your templates or PHP code using **purchased ticket queries**.

::: code
```twig Twig
{# Create a new purchased ticket query #}
{% set myQuery = craft.events.purchasedTickets() %}
```

```php PHP
// Create a new purchased ticket query
$myQuery = \verbb\events\elements\PurchasedTicket::find();
```
:::

Once you’ve created a purchased ticket query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [PurchasedTicket](docs:reference/purchased-ticket) objects will be returned.

See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries.html) in the Craft docs to learn how element queries work.

Purchased tickets follow Craft’s enabled and disabled statuses. To find cancelled tickets, use [reservationStatus](#reservationstatus) rather than [status](#status). To fetch purchased tickets through GraphQL, see [GraphQL Queries](docs:graphql/queries).

## Example

You can list the attendees for an event by doing the following.

1. Create a purchased ticket query with `craft.events.purchasedTickets()`.
2. Set the [event](#event) and [reservationStatus](#reservationstatus) parameters on it.
3. Fetch the purchased tickets with `.all()`.
4. Loop through the purchased tickets using a [for](https://twig.symfony.com/doc/3.x/tags/for.html) tag to output their contents.

This example assumes an `event` variable, such as on an event’s template.

```twig
{# Create a purchased ticket query with the 'event' and 'reservationStatus' parameters #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .event(event)
    .reservationStatus('active')
    .all() %}

{# Display their contents #}
{% for purchasedTicket in purchasedTickets %}
    <p>{{ purchasedTicket.firstName }} {{ purchasedTicket.lastName }}, {{ purchasedTicket.ticketType.title }}</p>
{% endfor %}
```

## Parameters

Purchased Ticket queries support the following parameters.

::: reference
### `asArray`

Causes the query to return matching purchased tickets as arrays of data, rather than [PurchasedTicket](docs:reference/purchased-ticket) objects.

::: code
```twig Twig
{# Fetch purchased tickets as arrays #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .asArray()
    .all() %}
```

```php PHP
// Fetch purchased tickets as arrays
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->asArray()
    ->all();
```
:::
:::

::: reference
### `checkedIn`

Narrows the query results based on whether the ticket holders have checked in.

::: code
```twig Twig
{# Fetch purchased tickets that have been checked in #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .checkedIn(true)
    .all() %}
```

```php PHP
// Fetch purchased tickets that have been checked in
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->checkedIn(true)
    ->all();
```
:::
:::

::: reference
### `checkedInDate`

Narrows the query results based on the date and time the ticket holders checked in.

| Value | Fetches purchased tickets… |
| --- | --- |
| `'>= 2026-04-01'` | that were checked in on or after 2026-04-01. |
| `'< 2026-05-01'` | that were checked in before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were checked in between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch purchased tickets checked in today #}
{% set today = date('today')|atom %}

{% set purchasedTickets = craft.events.purchasedTickets()
    .checkedInDate(">= #{today}")
    .all() %}
```

```php PHP
// Fetch purchased tickets checked in today
$today = (new \DateTime('today'))->format(\DateTime::ATOM);

$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->checkedInDate(">= {$today}")
    ->all();
```
:::
:::

::: reference
### `customer`

Narrows the query results to purchased tickets from completed orders placed by a customer.

::: code
```twig Twig
{# Fetch the current user’s purchased tickets #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .customer(currentUser)
    .all() %}
```

```php PHP
// Fetch the current user’s purchased tickets
$currentUser = \Craft::$app->getUser()->getIdentity();

$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->customer($currentUser)
    ->all();
```
:::
:::

::: reference
### `customerId`

Narrows the query results to purchased tickets from completed orders placed by a customer, per the customer’s user ID.

::: code
```twig Twig
{# Fetch purchased tickets for the customer with an ID of 1 #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .customerId(1)
    .all() %}
```

```php PHP
// Fetch purchased tickets for the customer with an ID of 1
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->customerId(1)
    ->all();
```
:::
:::

::: reference
### `dateCreated`

Narrows the query results based on the purchased tickets’ creation dates.

| Value | Fetches purchased tickets… |
| --- | --- |
| `'>= 2026-04-01'` | that were created on or after 2026-04-01. |
| `'< 2026-05-01'` | that were created before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were created between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch purchased tickets created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set purchasedTickets = craft.events.purchasedTickets()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch purchased tickets created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::
:::

::: reference
### `dateUpdated`

Narrows the query results based on the purchased tickets’ last-updated dates.

| Value | Fetches purchased tickets… |
| --- | --- |
| `'>= 2026-04-01'` | that were updated on or after 2026-04-01. |
| `'< 2026-05-01'` | that were updated before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were updated between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch purchased tickets updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set purchasedTickets = craft.events.purchasedTickets()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
// Fetch purchased tickets updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::
:::

::: reference
### `event`

Narrows the query results to purchased tickets for an event.

| Value | Fetches purchased tickets… |
| --- | --- |
| an `Event` object | for the event. |
| `1` | for an event with an ID of 1. |

::: code
```twig Twig
{# Fetch purchased tickets for an event #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .event(event)
    .all() %}
```

```php PHP
// Fetch purchased tickets for an event
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->event($event)
    ->all();
```
:::
:::

::: reference
### `eventId`

Narrows the query results based on the purchased tickets’ events, per the events’ IDs.

| Value | Fetches purchased tickets… |
| --- | --- |
| `1` | for an event with an ID of 1. |
| `'not 1'` | not for an event with an ID of 1. |
| `[1, 2]` | for an event with an ID of 1 or 2. |
| `['not', 1, 2]` | not for an event with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch purchased tickets for the event with an ID of 1 #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .eventId(1)
    .all() %}
```

```php PHP
// Fetch purchased tickets for the event with an ID of 1
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->eventId(1)
    ->all();
```
:::
:::

::: reference
### `eventTypeId`

Narrows the query results based on the event types of the purchased tickets’ events, per the event types’ IDs.

| Value | Fetches purchased tickets… |
| --- | --- |
| `1` | for events of a type with an ID of 1. |
| `[1, 2]` | for events of a type with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch purchased tickets for events of the event type with an ID of 1 #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .eventTypeId(1)
    .all() %}
```

```php PHP
// Fetch purchased tickets for events of the event type with an ID of 1
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->eventTypeId(1)
    ->all();
```
:::
:::

::: reference
### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig Twig
{# Fetch purchased tickets in a specific order #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php PHP
// Fetch purchased tickets in a specific order
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::
:::

::: reference
### `id`

Narrows the query results based on the purchased tickets’ IDs.

| Value | Fetches purchased tickets… |
| --- | --- |
| `1` | with an ID of 1. |
| `'not 1'` | not with an ID of 1. |
| `[1, 2]` | with an ID of 1 or 2. |
| `['not', 1, 2]` | not with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch the purchased ticket by its ID #}
{% set purchasedTicket = craft.events.purchasedTickets()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the purchased ticket by its ID
$purchasedTicket = \verbb\events\elements\PurchasedTicket::find()
    ->id(1)
    ->one();
```
:::

::: tip
This can be combined with [fixedOrder](#fixedorder) if you want the results to be returned in a specific order.
:::
:::

::: reference
### `inReverse`

Causes the query results to be returned in reverse order.

::: code
```twig Twig
{# Fetch purchased tickets in reverse #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .inReverse()
    .all() %}
```

```php PHP
// Fetch purchased tickets in reverse
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->inReverse()
    ->all();
```
:::
:::

::: reference
### `limit`

Determines the number of purchased tickets that should be returned.

::: code
```twig Twig
{# Fetch up to 10 purchased tickets #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 purchased tickets
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->limit(10)
    ->all();
```
:::
:::

::: reference
### `lineItemId`

Narrows the query results based on the Commerce line items the purchased tickets were bought in, per the line items’ IDs.

::: code
```twig Twig
{# Fetch purchased tickets for a line item #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .lineItemId(lineItem.id)
    .all() %}
```

```php PHP
// Fetch purchased tickets for a line item
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->lineItemId($lineItem->id)
    ->all();
```
:::
:::

::: reference
### `offset`

Determines how many purchased tickets should be skipped in the results.

::: code
```twig Twig
{# Fetch all purchased tickets except for the first 3 #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all purchased tickets except for the first 3
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->offset(3)
    ->all();
```
:::
:::

::: reference
### `orderBy`

Determines the order that the purchased tickets should be returned in. Purchased tickets are ordered by their creation date, newest first, by default.

::: code
```twig Twig
{# Fetch all purchased tickets in order of date created #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all purchased tickets in order of date created
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::
:::

::: reference
### `orderId`

Narrows the query results based on the Commerce orders the purchased tickets were bought in, per the orders’ IDs.

::: code
```twig Twig
{# Fetch purchased tickets for an order #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .orderId(order.id)
    .all() %}
```

```php PHP
// Fetch purchased tickets for an order
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->orderId($order->id)
    ->all();
```
:::
:::

::: reference
### `relatedTo`

Narrows the query results to only purchased tickets that are related to certain other elements. See [Relations](https://craftcms.com/docs/5.x/system/relations.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all purchased tickets that are related to myCategory #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .relatedTo(myCategory)
    .all() %}
```

```php PHP
// Fetch all purchased tickets that are related to myCategory
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->relatedTo($myCategory)
    ->all();
```
:::
:::

::: reference
### `reservationStatus`

Narrows the query results based on the purchased tickets’ reservation status. Cancelled tickets keep their record but no longer hold a place.

| Value | Fetches purchased tickets… |
| --- | --- |
| `'active'` | that hold an active reservation. |
| `'cancelled'` | that have been cancelled. |

::: code
```twig Twig
{# Fetch active purchased tickets #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .reservationStatus('active')
    .all() %}
```

```php PHP
// Fetch active purchased tickets
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->reservationStatus('active')
    ->all();
```
:::
:::

::: reference
### `search`

Narrows the query results to only purchased tickets that match a search query. See [Searching](https://craftcms.com/docs/5.x/system/searching.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all purchased tickets that match the search query #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{% set purchasedTickets = craft.events.purchasedTickets()
    .search(searchQuery)
    .all() %}
```

```php PHP
// Fetch all purchased tickets that match the search query
$searchQuery = \Craft::$app->getRequest()->getQueryParam('q');

$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->search($searchQuery)
    ->all();
```
:::
:::

::: reference
### `session`

Narrows the query results to purchased tickets for a session.

| Value | Fetches purchased tickets… |
| --- | --- |
| a `Session` object | for the session. |
| `1` | for a session with an ID of 1. |

::: code
```twig Twig
{# Fetch purchased tickets for a session #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .session(session)
    .all() %}
```

```php PHP
// Fetch purchased tickets for a session
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->session($session)
    ->all();
```
:::
:::

::: reference
### `sessionId`

Narrows the query results based on the purchased tickets’ sessions, per the sessions’ IDs.

| Value | Fetches purchased tickets… |
| --- | --- |
| `1` | for a session with an ID of 1. |
| `'not 1'` | not for a session with an ID of 1. |
| `[1, 2]` | for a session with an ID of 1 or 2. |
| `['not', 1, 2]` | not for a session with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch purchased tickets for the session with an ID of 1 #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .sessionId(1)
    .all() %}
```

```php PHP
// Fetch purchased tickets for the session with an ID of 1
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->sessionId(1)
    ->all();
```
:::
:::

::: reference
### `status`

Narrows the query results based on the purchased tickets’ statuses. Pass `null` to fetch purchased tickets regardless of their status.

| Value | Fetches purchased tickets… |
| --- | --- |
| `'enabled'` _(default)_ | that are enabled. |
| `'disabled'` | that are disabled. |
| `['enabled', 'disabled']` | that are enabled or disabled. |
| `null` | regardless of status. |

::: code
```twig Twig
{# Fetch disabled purchased tickets #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .status('disabled')
    .all() %}
```

```php PHP
// Fetch disabled purchased tickets
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->status('disabled')
    ->all();
```
:::
:::

::: reference
### `ticket`

Narrows the query results to purchased tickets bought from a ticket.

| Value | Fetches purchased tickets… |
| --- | --- |
| a `Ticket` object | bought from the ticket. |
| `1` | bought from a ticket with an ID of 1. |

::: code
```twig Twig
{# Fetch purchased tickets for a ticket #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .ticket(ticket)
    .all() %}
```

```php PHP
// Fetch purchased tickets for a ticket
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->ticket($ticket)
    ->all();
```
:::
:::

::: reference
### `ticketId`

Narrows the query results based on the purchased tickets’ tickets, per the tickets’ IDs.

| Value | Fetches purchased tickets… |
| --- | --- |
| `1` | bought from a ticket with an ID of 1. |
| `'not 1'` | not bought from a ticket with an ID of 1. |
| `[1, 2]` | bought from a ticket with an ID of 1 or 2. |
| `['not', 1, 2]` | not bought from a ticket with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch purchased tickets for the ticket with an ID of 1 #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .ticketId(1)
    .all() %}
```

```php PHP
// Fetch purchased tickets for the ticket with an ID of 1
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->ticketId(1)
    ->all();
```
:::
:::

::: reference
### `ticketType`

Narrows the query results to purchased tickets of a ticket type.

| Value | Fetches purchased tickets… |
| --- | --- |
| a `TicketType` object | of the ticket type. |

::: code
```twig Twig
{# Fetch purchased tickets of a ticket type #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .ticketType(ticketType)
    .all() %}
```

```php PHP
// Fetch purchased tickets of a ticket type
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->ticketType($ticketType)
    ->all();
```
:::
:::

::: reference
### `ticketTypeId`

Narrows the query results based on the purchased tickets’ ticket types, per the ticket types’ IDs.

| Value | Fetches purchased tickets… |
| --- | --- |
| `1` | of a ticket type with an ID of 1. |
| `'not 1'` | not of a ticket type with an ID of 1. |
| `[1, 2]` | of a ticket type with an ID of 1 or 2. |
| `['not', 1, 2]` | not of a ticket type with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch purchased tickets of the ticket type with an ID of 1 #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .ticketTypeId(1)
    .all() %}
```

```php PHP
// Fetch purchased tickets of the ticket type with an ID of 1
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->ticketTypeId(1)
    ->all();
```
:::
:::

::: reference
### `uid`

Narrows the query results based on the purchased tickets’ UIDs.

::: code
```twig Twig
{# Fetch the purchased ticket by its UID #}
{% set purchasedTicket = craft.events.purchasedTickets()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the purchased ticket by its UID
$purchasedTicket = \verbb\events\elements\PurchasedTicket::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::
:::

::: reference
### `with`

Causes the query to return matching purchased tickets eager-loaded with related elements. See [Eager-Loading Elements](https://craftcms.com/docs/5.x/development/eager-loading.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch purchased tickets eager-loaded with the “Related” field’s relations #}
{% set purchasedTickets = craft.events.purchasedTickets()
    .with(['related'])
    .all() %}
```

```php PHP
// Fetch purchased tickets eager-loaded with the “Related” field’s relations
$purchasedTickets = \verbb\events\elements\PurchasedTicket::find()
    ->with(['related'])
    ->all();
```
:::
:::
