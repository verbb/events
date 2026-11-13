# Ticket Queries

You can fetch tickets in your templates or PHP code using **ticket queries**.

::: code
```twig Twig
{# Create a new ticket query #}
{% set myQuery = craft.events.tickets() %}
```

```php PHP
// Create a new ticket query
$myQuery = \verbb\events\elements\Ticket::find();
```
:::

Once you’ve created a ticket query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Ticket](docs:reference/ticket) objects will be returned.

See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries.html) in the Craft docs to learn how element queries work.

## Example

A ticket is the purchasable for one ticket type in one session, so you can list what a customer can buy for an event by doing the following.

1. Create a ticket query with `craft.events.tickets()`.
2. Set the [event](#event) parameter on it.
3. Fetch the tickets with `.all()`.
4. Loop through the tickets using a [for](https://twig.symfony.com/doc/3.x/tags/for.html) tag to output their contents.

This example assumes an `event` variable, such as on an event’s template.

```twig
{# Create a ticket query with the 'event' parameter #}
{% set tickets = craft.events.tickets()
    .event(event)
    .all() %}

{# Display their contents #}
{% for ticket in tickets %}
    <p>{{ ticket.title }}, {{ ticket.price|commerceCurrency(cart.currency) }}</p>
{% endfor %}
```

The `cart` variable is Craft Commerce’s current cart, available as `craft.commerce.carts.cart`.

## Parameters

Ticket queries support the following parameters.

::: reference
### `asArray`

Causes the query to return matching tickets as arrays of data, rather than [Ticket](docs:reference/ticket) objects.

::: code
```twig Twig
{# Fetch tickets as arrays #}
{% set tickets = craft.events.tickets()
    .asArray()
    .all() %}
```

```php PHP
// Fetch tickets as arrays
$tickets = \verbb\events\elements\Ticket::find()
    ->asArray()
    ->all();
```
:::
:::

::: reference
### `dateCreated`

Narrows the query results based on the tickets’ creation dates.

| Value | Fetches tickets… |
| --- | --- |
| `'>= 2026-04-01'` | that were created on or after 2026-04-01. |
| `'< 2026-05-01'` | that were created before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were created between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch tickets created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set tickets = craft.events.tickets()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch tickets created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$tickets = \verbb\events\elements\Ticket::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::
:::

::: reference
### `dateUpdated`

Narrows the query results based on the tickets’ last-updated dates.

| Value | Fetches tickets… |
| --- | --- |
| `'>= 2026-04-01'` | that were updated on or after 2026-04-01. |
| `'< 2026-05-01'` | that were updated before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were updated between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch tickets updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set tickets = craft.events.tickets()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
// Fetch tickets updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$tickets = \verbb\events\elements\Ticket::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::
:::

::: reference
### `event`

Narrows the query results to tickets for an event.

| Value | Fetches tickets… |
| --- | --- |
| an `Event` object | for the event. |
| `1` | for an event with an ID of 1. |

::: code
```twig Twig
{# Fetch tickets for an event #}
{% set tickets = craft.events.tickets()
    .event(event)
    .all() %}
```

```php PHP
// Fetch tickets for an event
$tickets = \verbb\events\elements\Ticket::find()
    ->event($event)
    ->all();
```
:::
:::

::: reference
### `eventId`

Narrows the query results based on the tickets’ events, per the events’ IDs.

| Value | Fetches tickets… |
| --- | --- |
| `1` | for an event with an ID of 1. |
| `'not 1'` | not for an event with an ID of 1. |
| `[1, 2]` | for an event with an ID of 1 or 2. |
| `['not', 1, 2]` | not for an event with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch tickets for the event with an ID of 1 #}
{% set tickets = craft.events.tickets()
    .eventId(1)
    .all() %}
```

```php PHP
// Fetch tickets for the event with an ID of 1
$tickets = \verbb\events\elements\Ticket::find()
    ->eventId(1)
    ->all();
```
:::
:::

::: reference
### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig Twig
{# Fetch tickets in a specific order #}
{% set tickets = craft.events.tickets()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php PHP
// Fetch tickets in a specific order
$tickets = \verbb\events\elements\Ticket::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::
:::

::: reference
### `hasEvent`

Narrows the query results to tickets whose event matches an event query, or an array of event query criteria.

| Value | Fetches tickets… |
| --- | --- |
| an `EventQuery` object | whose event matches the query. |
| an array of criteria | whose event matches the criteria. |

::: code
```twig Twig
{# Fetch tickets for live conference events #}
{% set tickets = craft.events.tickets()
    .hasEvent({ type: 'conference', status: 'live' })
    .all() %}
```

```php PHP
// Fetch tickets for live conference events
$tickets = \verbb\events\elements\Ticket::find()
    ->hasEvent(['type' => 'conference', 'status' => 'live'])
    ->all();
```
:::
:::

::: reference
### `id`

Narrows the query results based on the tickets’ IDs.

| Value | Fetches tickets… |
| --- | --- |
| `1` | with an ID of 1. |
| `'not 1'` | not with an ID of 1. |
| `[1, 2]` | with an ID of 1 or 2. |
| `['not', 1, 2]` | not with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch the ticket by its ID #}
{% set ticket = craft.events.tickets()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the ticket by its ID
$ticket = \verbb\events\elements\Ticket::find()
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
{# Fetch tickets in reverse #}
{% set tickets = craft.events.tickets()
    .inReverse()
    .all() %}
```

```php PHP
// Fetch tickets in reverse
$tickets = \verbb\events\elements\Ticket::find()
    ->inReverse()
    ->all();
```
:::
:::

::: reference
### `limit`

Determines the number of tickets that should be returned.

::: code
```twig Twig
{# Fetch up to 10 tickets #}
{% set tickets = craft.events.tickets()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 tickets
$tickets = \verbb\events\elements\Ticket::find()
    ->limit(10)
    ->all();
```
:::
:::

::: reference
### `offset`

Determines how many tickets should be skipped in the results.

::: code
```twig Twig
{# Fetch all tickets except for the first 3 #}
{% set tickets = craft.events.tickets()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all tickets except for the first 3
$tickets = \verbb\events\elements\Ticket::find()
    ->offset(3)
    ->all();
```
:::
:::

::: reference
### `orderBy`

Determines the order that the tickets should be returned in. Tickets are ordered by their session, then their ticket type, following the order on their event by default.

::: code
```twig Twig
{# Fetch all tickets in order of date created #}
{% set tickets = craft.events.tickets()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all tickets in order of date created
$tickets = \verbb\events\elements\Ticket::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::
:::

::: reference
### `price`

Narrows the query results based on the tickets’ prices. Tickets are Craft Commerce purchasables, so Commerce’s other purchasable parameters, such as `availableForPurchase`, also work on ticket queries.

| Value | Fetches tickets… |
| --- | --- |
| `20` | with a price of 20. |
| `'< 20'` | with a price less than 20. |
| `['and', '>= 10', '<= 50']` | with a price between 10 and 50. |

::: code
```twig Twig
{# Fetch tickets that cost less than 20 #}
{% set tickets = craft.events.tickets()
    .price('< 20')
    .all() %}
```

```php PHP
// Fetch tickets that cost less than 20
$tickets = \verbb\events\elements\Ticket::find()
    ->price('< 20')
    ->all();
```
:::
:::

::: reference
### `relatedTo`

Narrows the query results to only tickets that are related to certain other elements. See [Relations](https://craftcms.com/docs/5.x/system/relations.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all tickets that are related to myCategory #}
{% set tickets = craft.events.tickets()
    .relatedTo(myCategory)
    .all() %}
```

```php PHP
// Fetch all tickets that are related to myCategory
$tickets = \verbb\events\elements\Ticket::find()
    ->relatedTo($myCategory)
    ->all();
```
:::
:::

::: reference
### `search`

Narrows the query results to only tickets that match a search query. See [Searching](https://craftcms.com/docs/5.x/system/searching.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all tickets that match the search query #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{% set tickets = craft.events.tickets()
    .search(searchQuery)
    .all() %}
```

```php PHP
// Fetch all tickets that match the search query
$searchQuery = \Craft::$app->getRequest()->getQueryParam('q');

$tickets = \verbb\events\elements\Ticket::find()
    ->search($searchQuery)
    ->all();
```
:::
:::

::: reference
### `session`

Narrows the query results to tickets for a session.

| Value | Fetches tickets… |
| --- | --- |
| a `Session` object | for the session. |
| `1` | for a session with an ID of 1. |

::: code
```twig Twig
{# Fetch tickets for a session #}
{% set tickets = craft.events.tickets()
    .session(session)
    .all() %}
```

```php PHP
// Fetch tickets for a session
$tickets = \verbb\events\elements\Ticket::find()
    ->session($session)
    ->all();
```
:::
:::

::: reference
### `sessionId`

Narrows the query results based on the tickets’ sessions, per the sessions’ IDs.

| Value | Fetches tickets… |
| --- | --- |
| `1` | for a session with an ID of 1. |
| `'not 1'` | not for a session with an ID of 1. |
| `[1, 2]` | for a session with an ID of 1 or 2. |
| `['not', 1, 2]` | not for a session with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch tickets for the session with an ID of 1 #}
{% set tickets = craft.events.tickets()
    .sessionId(1)
    .all() %}
```

```php PHP
// Fetch tickets for the session with an ID of 1
$tickets = \verbb\events\elements\Ticket::find()
    ->sessionId(1)
    ->all();
```
:::
:::

::: reference
### `sku`

Narrows the query results based on the tickets’ SKUs.

| Value | Fetches tickets… |
| --- | --- |
| `'foo'` | with a SKU of `foo`. |
| `'foo*'` | with a SKU that begins with `foo`. |
| `'*foo'` | with a SKU that ends with `foo`. |
| `'*foo*'` | with a SKU that contains `foo`. |
| `'not *foo*'` | with a SKU that doesn’t contain `foo`. |
| `['*foo*', '*bar*']` | with a SKU that contains `foo` or `bar`. |
| `['not', '*foo*', '*bar*']` | with a SKU that doesn’t contain `foo` or `bar`. |

::: code
```twig Twig
{# Fetch the ticket with a SKU of CONF-ADULT #}
{% set ticket = craft.events.tickets()
    .sku('CONF-ADULT')
    .one() %}
```

```php PHP
// Fetch the ticket with a SKU of CONF-ADULT
$ticket = \verbb\events\elements\Ticket::find()
    ->sku('CONF-ADULT')
    ->one();
```
:::
:::

::: reference
### `status`

Narrows the query results based on the tickets’ statuses. Pass `null` to fetch tickets regardless of their status.

| Value | Fetches tickets… |
| --- | --- |
| `'enabled'` _(default)_ | that are enabled. |
| `'disabled'` | that are disabled. |
| `['enabled', 'disabled']` | that are enabled or disabled. |
| `null` | regardless of status. |

::: code
```twig Twig
{# Fetch disabled tickets #}
{% set tickets = craft.events.tickets()
    .status('disabled')
    .all() %}
```

```php PHP
// Fetch disabled tickets
$tickets = \verbb\events\elements\Ticket::find()
    ->status('disabled')
    ->all();
```
:::
:::

::: reference
### `type`

Narrows the query results to tickets of a ticket type.

| Value | Fetches tickets… |
| --- | --- |
| a `TicketType` object | of the ticket type. |
| `1` | of a ticket type with an ID of 1. |

::: code
```twig Twig
{# Fetch tickets of a ticket type #}
{% set tickets = craft.events.tickets()
    .type(ticketType)
    .all() %}
```

```php PHP
// Fetch tickets of a ticket type
$tickets = \verbb\events\elements\Ticket::find()
    ->type($ticketType)
    ->all();
```
:::
:::

::: reference
### `typeId`

Narrows the query results based on the tickets’ ticket types, per the ticket types’ IDs.

| Value | Fetches tickets… |
| --- | --- |
| `1` | of a ticket type with an ID of 1. |
| `'not 1'` | not of a ticket type with an ID of 1. |
| `[1, 2]` | of a ticket type with an ID of 1 or 2. |
| `['not', 1, 2]` | not of a ticket type with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch tickets of the ticket type with an ID of 1 #}
{% set tickets = craft.events.tickets()
    .typeId(1)
    .all() %}
```

```php PHP
// Fetch tickets of the ticket type with an ID of 1
$tickets = \verbb\events\elements\Ticket::find()
    ->typeId(1)
    ->all();
```
:::
:::

::: reference
### `uid`

Narrows the query results based on the tickets’ UIDs.

::: code
```twig Twig
{# Fetch the ticket by its UID #}
{% set ticket = craft.events.tickets()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the ticket by its UID
$ticket = \verbb\events\elements\Ticket::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::
:::

::: reference
### `with`

Causes the query to return matching tickets eager-loaded with related elements. See [Eager-Loading Elements](https://craftcms.com/docs/5.x/development/eager-loading.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch tickets eager-loaded with the “Related” field’s relations #}
{% set tickets = craft.events.tickets()
    .with(['related'])
    .all() %}
```

```php PHP
// Fetch tickets eager-loaded with the “Related” field’s relations
$tickets = \verbb\events\elements\Ticket::find()
    ->with(['related'])
    ->all();
```
:::
:::
