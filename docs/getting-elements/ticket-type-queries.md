# Ticket Type Queries

You can fetch ticket types in your templates or PHP code using **ticket type queries**.

::: code
```twig Twig
{# Create a new ticket type query #}
{% set myQuery = craft.events.ticketTypes() %}
```

```php PHP
// Create a new ticket type query
$myQuery = \verbb\events\elements\TicketType::find();
```
:::

Once you’ve created a ticket type query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [TicketType](docs:reference/ticket-type) objects will be returned.

See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries.html) in the Craft docs to learn how element queries work.

## Example

You can list the ticket types for an event by doing the following.

1. Create a ticket type query with `craft.events.ticketTypes()`.
2. Set the [event](#event) parameter on it.
3. Fetch the ticket types with `.all()`.
4. Loop through the ticket types using a [for](https://twig.symfony.com/doc/3.x/tags/for.html) tag to output their contents.

This example assumes an `event` variable, such as on an event’s template.

```twig
{# Create a ticket type query with the 'event' parameter #}
{% set ticketTypes = craft.events.ticketTypes()
    .event(event)
    .all() %}

{# Display their contents #}
{% for ticketType in ticketTypes %}
    <p>{{ ticketType.title }}, {{ ticketType.price|commerceCurrency(cart.currency) }}</p>
{% endfor %}
```

The `cart` variable is Craft Commerce’s current cart, available as `craft.commerce.carts.cart`.

## Parameters

Ticket Type queries support the following parameters.

::: reference
### `asArray`

Causes the query to return matching ticket types as arrays of data, rather than [TicketType](docs:reference/ticket-type) objects.

::: code
```twig Twig
{# Fetch ticket types as arrays #}
{% set ticketTypes = craft.events.ticketTypes()
    .asArray()
    .all() %}
```

```php PHP
// Fetch ticket types as arrays
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->asArray()
    ->all();
```
:::
:::

::: reference
### `capacity`

Narrows the query results based on the ticket types’ capacity.

| Value | Fetches ticket types… |
| --- | --- |
| `50` | with a capacity of 50. |
| `'>= 50'` | with a capacity of 50 or more. |
| `':empty:'` | with no capacity set. |

::: code
```twig Twig
{# Fetch ticket types with a capacity of 50 or more #}
{% set ticketTypes = craft.events.ticketTypes()
    .capacity('>= 50')
    .all() %}
```

```php PHP
// Fetch ticket types with a capacity of 50 or more
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->capacity('>= 50')
    ->all();
```
:::
:::

::: reference
### `dateCreated`

Narrows the query results based on the ticket types’ creation dates.

| Value | Fetches ticket types… |
| --- | --- |
| `'>= 2026-04-01'` | that were created on or after 2026-04-01. |
| `'< 2026-05-01'` | that were created before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were created between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch ticket types created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set ticketTypes = craft.events.ticketTypes()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch ticket types created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$ticketTypes = \verbb\events\elements\TicketType::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::
:::

::: reference
### `dateUpdated`

Narrows the query results based on the ticket types’ last-updated dates.

| Value | Fetches ticket types… |
| --- | --- |
| `'>= 2026-04-01'` | that were updated on or after 2026-04-01. |
| `'< 2026-05-01'` | that were updated before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were updated between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch ticket types updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set ticketTypes = craft.events.ticketTypes()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
// Fetch ticket types updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$ticketTypes = \verbb\events\elements\TicketType::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::
:::

::: reference
### `event`

Narrows the query results to ticket types that belong to an event.

| Value | Fetches ticket types… |
| --- | --- |
| an `Event` object | that belong to the event. |
| `1` | that belong to an event with an ID of 1. |

::: code
```twig Twig
{# Fetch ticket types for an event #}
{% set ticketTypes = craft.events.ticketTypes()
    .event(event)
    .all() %}
```

```php PHP
// Fetch ticket types for an event
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->event($event)
    ->all();
```
:::
:::

::: reference
### `eventId`

Narrows the query results based on the ticket types’ events, per the events’ IDs.

| Value | Fetches ticket types… |
| --- | --- |
| `1` | that belong to an event with an ID of 1. |
| `'not 1'` | not that belong to an event with an ID of 1. |
| `[1, 2]` | that belong to an event with an ID of 1 or 2. |
| `['not', 1, 2]` | not that belong to an event with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch ticket types for the event with an ID of 1 #}
{% set ticketTypes = craft.events.ticketTypes()
    .eventId(1)
    .all() %}
```

```php PHP
// Fetch ticket types for the event with an ID of 1
$ticketTypes = \verbb\events\elements\TicketType::find()
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
{# Fetch ticket types in a specific order #}
{% set ticketTypes = craft.events.ticketTypes()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php PHP
// Fetch ticket types in a specific order
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::
:::

::: reference
### `hasEvent`

Narrows the query results to ticket types whose event matches an event query, or an array of event query criteria.

| Value | Fetches ticket types… |
| --- | --- |
| an `EventQuery` object | whose event matches the query. |
| an array of criteria | whose event matches the criteria. |

::: code
```twig Twig
{# Fetch ticket types for live conference events #}
{% set ticketTypes = craft.events.ticketTypes()
    .hasEvent({ type: 'conference', status: 'live' })
    .all() %}
```

```php PHP
// Fetch ticket types for live conference events
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->hasEvent(['type' => 'conference', 'status' => 'live'])
    ->all();
```
:::
:::

::: reference
### `id`

Narrows the query results based on the ticket types’ IDs.

| Value | Fetches ticket types… |
| --- | --- |
| `1` | with an ID of 1. |
| `'not 1'` | not with an ID of 1. |
| `[1, 2]` | with an ID of 1 or 2. |
| `['not', 1, 2]` | not with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch the ticket type by its ID #}
{% set ticketType = craft.events.ticketTypes()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the ticket type by its ID
$ticketType = \verbb\events\elements\TicketType::find()
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
{# Fetch ticket types in reverse #}
{% set ticketTypes = craft.events.ticketTypes()
    .inReverse()
    .all() %}
```

```php PHP
// Fetch ticket types in reverse
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->inReverse()
    ->all();
```
:::
:::

::: reference
### `limit`

Determines the number of ticket types that should be returned.

::: code
```twig Twig
{# Fetch up to 10 ticket types #}
{% set ticketTypes = craft.events.ticketTypes()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 ticket types
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->limit(10)
    ->all();
```
:::
:::

::: reference
### `offset`

Determines how many ticket types should be skipped in the results.

::: code
```twig Twig
{# Fetch all ticket types except for the first 3 #}
{% set ticketTypes = craft.events.ticketTypes()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all ticket types except for the first 3
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->offset(3)
    ->all();
```
:::
:::

::: reference
### `orderBy`

Determines the order that the ticket types should be returned in. Ticket types are ordered by the order they appear in on their event by default.

::: code
```twig Twig
{# Fetch all ticket types in order of date created #}
{% set ticketTypes = craft.events.ticketTypes()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all ticket types in order of date created
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::
:::

::: reference
### `owner`

Narrows the query results to ticket types owned by an event, and sets the site to the event’s site. Use [event](#event) when you only need to match the event ID.

::: code
```twig Twig
{# Fetch ticket types owned by an event #}
{% set ticketTypes = craft.events.ticketTypes()
    .owner(event)
    .all() %}
```

```php PHP
// Fetch ticket types owned by an event
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->owner($event)
    ->all();
```
:::
:::

::: reference
### `ownerId`

Narrows the query results based on the IDs of the events that own the ticket types.

| Value | Fetches ticket types… |
| --- | --- |
| `1` | owned by an event with an ID of 1. |
| `'not 1'` | not owned by an event with an ID of 1. |
| `[1, 2]` | owned by an event with an ID of 1 or 2. |
| `['not', 1, 2]` | not owned by an event with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch ticket types owned by the event with an ID of 1 #}
{% set ticketTypes = craft.events.ticketTypes()
    .ownerId(1)
    .all() %}
```

```php PHP
// Fetch ticket types owned by the event with an ID of 1
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->ownerId(1)
    ->all();
```
:::
:::

::: reference
### `price`

Narrows the query results based on the ticket types’ prices.

| Value | Fetches ticket types… |
| --- | --- |
| `20` | with a price of 20. |
| `'< 20'` | with a price less than 20. |
| `['and', '>= 10', '<= 50']` | with a price between 10 and 50. |

::: code
```twig Twig
{# Fetch ticket types that cost less than 20 #}
{% set ticketTypes = craft.events.ticketTypes()
    .price('< 20')
    .all() %}
```

```php PHP
// Fetch ticket types that cost less than 20
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->price('< 20')
    ->all();
```
:::
:::

::: reference
### `primaryOwner`

Narrows the query results to ticket types whose primary owner is an event, and sets the site to the event’s site. A ticket type is only ever created on one event, which is its primary owner, though drafts and revisions of that event also own it.

::: code
```twig Twig
{# Fetch ticket types created on an event #}
{% set ticketTypes = craft.events.ticketTypes()
    .primaryOwner(event)
    .all() %}
```

```php PHP
// Fetch ticket types created on an event
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->primaryOwner($event)
    ->all();
```
:::
:::

::: reference
### `primaryOwnerId`

Narrows the query results based on the IDs of the ticket types’ primary owners.

| Value | Fetches ticket types… |
| --- | --- |
| `1` | whose primary owner has an ID of 1. |
| `'not 1'` | not whose primary owner has an ID of 1. |
| `[1, 2]` | whose primary owner has an ID of 1 or 2. |
| `['not', 1, 2]` | not whose primary owner has an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch ticket types created on the event with an ID of 1 #}
{% set ticketTypes = craft.events.ticketTypes()
    .primaryOwnerId(1)
    .all() %}
```

```php PHP
// Fetch ticket types created on the event with an ID of 1
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->primaryOwnerId(1)
    ->all();
```
:::
:::

::: reference
### `relatedTo`

Narrows the query results to only ticket types that are related to certain other elements. See [Relations](https://craftcms.com/docs/5.x/system/relations.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all ticket types that are related to myCategory #}
{% set ticketTypes = craft.events.ticketTypes()
    .relatedTo(myCategory)
    .all() %}
```

```php PHP
// Fetch all ticket types that are related to myCategory
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->relatedTo($myCategory)
    ->all();
```
:::
:::

::: reference
### `search`

Narrows the query results to only ticket types that match a search query. See [Searching](https://craftcms.com/docs/5.x/system/searching.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all ticket types that match the search query #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{% set ticketTypes = craft.events.ticketTypes()
    .search(searchQuery)
    .all() %}
```

```php PHP
// Fetch all ticket types that match the search query
$searchQuery = \Craft::$app->getRequest()->getQueryParam('q');

$ticketTypes = \verbb\events\elements\TicketType::find()
    ->search($searchQuery)
    ->all();
```
:::
:::

::: reference
### `site`

Determines which site the ticket types should be queried in. The current site is used by default.

| Value | Fetches ticket types… |
| --- | --- |
| `'foo'` | from the site with a handle of `foo`. |
| `['foo', 'bar']` | from a site with a handle of `foo` or `bar`. |
| `'*'` | from any site. |
| a `craft\models\Site` object | from the site represented by the object. |

::: code
```twig Twig
{# Fetch ticket types from the Foo site #}
{% set ticketTypes = craft.events.ticketTypes()
    .site('foo')
    .all() %}
```

```php PHP
// Fetch ticket types from the Foo site
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->site('foo')
    ->all();
```
:::
:::

::: reference
### `siteId`

Determines which site the ticket types should be queried in, per the site’s ID. The current site is used by default.

::: code
```twig Twig
{# Fetch ticket types from the site with an ID of 1 #}
{% set ticketTypes = craft.events.ticketTypes()
    .siteId(1)
    .all() %}
```

```php PHP
// Fetch ticket types from the site with an ID of 1
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->siteId(1)
    ->all();
```
:::
:::

::: reference
### `status`

Narrows the query results based on the ticket types’ statuses. Pass `null` to fetch ticket types regardless of their status.

| Value | Fetches ticket types… |
| --- | --- |
| `'enabled'` _(default)_ | that are enabled. |
| `'disabled'` | that are disabled. |
| `['enabled', 'disabled']` | that are enabled or disabled. |
| `null` | regardless of status. |

::: code
```twig Twig
{# Fetch disabled ticket types #}
{% set ticketTypes = craft.events.ticketTypes()
    .status('disabled')
    .all() %}
```

```php PHP
// Fetch disabled ticket types
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->status('disabled')
    ->all();
```
:::
:::

::: reference
### `title`

Narrows the query results based on the ticket types’ titles.

| Value | Fetches ticket types… |
| --- | --- |
| `'foo'` | with a title of `foo`. |
| `'foo*'` | with a title that begins with `foo`. |
| `'*foo'` | with a title that ends with `foo`. |
| `'*foo*'` | with a title that contains `foo`. |
| `'not *foo*'` | with a title that doesn’t contain `foo`. |
| `['*foo*', '*bar*']` | with a title that contains `foo` or `bar`. |
| `['not', '*foo*', '*bar*']` | with a title that doesn’t contain `foo` or `bar`. |

::: code
```twig Twig
{# Fetch ticket types with a title that contains "Foo" #}
{% set ticketTypes = craft.events.ticketTypes()
    .title('*Foo*')
    .all() %}
```

```php PHP
// Fetch ticket types with a title that contains "Foo"
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->title('*Foo*')
    ->all();
```
:::
:::

::: reference
### `uid`

Narrows the query results based on the ticket types’ UIDs.

::: code
```twig Twig
{# Fetch the ticket type by its UID #}
{% set ticketType = craft.events.ticketTypes()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the ticket type by its UID
$ticketType = \verbb\events\elements\TicketType::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::
:::

::: reference
### `with`

Causes the query to return matching ticket types eager-loaded with related elements. See [Eager-Loading Elements](https://craftcms.com/docs/5.x/development/eager-loading.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch ticket types eager-loaded with the “Related” field’s relations #}
{% set ticketTypes = craft.events.ticketTypes()
    .with(['related'])
    .all() %}
```

```php PHP
// Fetch ticket types eager-loaded with the “Related” field’s relations
$ticketTypes = \verbb\events\elements\TicketType::find()
    ->with(['related'])
    ->all();
```
:::
:::
