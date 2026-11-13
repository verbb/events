# Event Queries

You can fetch events in your templates or PHP code using **event queries**.

::: code
```twig Twig
{# Create a new event query #}
{% set myQuery = craft.events.events() %}
```

```php PHP
// Create a new event query
$myQuery = \verbb\events\elements\Event::find();
```
:::

Once you’ve created an event query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Event](docs:reference/event) objects will be returned.

See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries.html) in the Craft docs to learn how element queries work.

::: tip
`craft.events.events()` only returns current and upcoming events, because it sets [endDate](#enddate) to the current date and time. Call `.endDate(null)` to include past events. Querying with `Event::find()` in PHP doesn't apply this default.
:::

## Example

You can display the next 10 events of a given type by doing the following.

1. Create an event query with `craft.events.events()`.
2. Set the [type](#type) and [limit](#limit) parameters on it.
3. Fetch the events with `.all()`.
4. Loop through the events using a [for](https://twig.symfony.com/doc/3.x/tags/for.html) tag to output their contents.

```twig
{# Create an event query with the 'type' and 'limit' parameters #}
{% set eventsQuery = craft.events.events()
    .type('conference')
    .limit(10) %}

{# Fetch the events #}
{% set events = eventsQuery.all() %}

{# Display their contents #}
{% for event in events %}
    <p>{{ event.title }}, {{ event.startDate|date('short') }}</p>
{% endfor %}
```

## Parameters

Event queries support the following parameters.

::: reference
### `asArray`

Causes the query to return matching events as arrays of data, rather than [Event](docs:reference/event) objects.

::: code
```twig Twig
{# Fetch events as arrays #}
{% set events = craft.events.events()
    .asArray()
    .all() %}
```

```php PHP
// Fetch events as arrays
$events = \verbb\events\elements\Event::find()
    ->asArray()
    ->all();
```
:::
:::

::: reference
### `dateCreated`

Narrows the query results based on the events’ creation dates.

| Value | Fetches events… |
| --- | --- |
| `'>= 2026-04-01'` | that were created on or after 2026-04-01. |
| `'< 2026-05-01'` | that were created before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were created between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch events created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set events = craft.events.events()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch events created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$events = \verbb\events\elements\Event::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::
:::

::: reference
### `dateUpdated`

Narrows the query results based on the events’ last-updated dates.

| Value | Fetches events… |
| --- | --- |
| `'>= 2026-04-01'` | that were updated on or after 2026-04-01. |
| `'< 2026-05-01'` | that were updated before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were updated between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch events updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set events = craft.events.events()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
// Fetch events updated in the last week
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$events = \verbb\events\elements\Event::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::
:::

::: reference
### `editable`

Narrows the query results to only events in event types the current user has permission to edit. The query returns nothing when no user is logged in.

::: code
```twig Twig
{# Fetch events the current user can edit #}
{% set events = craft.events.events()
    .editable()
    .all() %}
```

```php PHP
// Fetch events the current user can edit
$events = \verbb\events\elements\Event::find()
    ->editable()
    ->all();
```
:::
:::

::: reference
### `endDate`

Narrows the query results based on the events’ end dates, which is the end date of their last enabled session. `craft.events.events()` sets this to the current date and time by default, so only current and upcoming events are returned. Pass `null` to include past events.

| Value | Fetches events… |
| --- | --- |
| `'>= 2026-04-01'` | that finish on or after 2026-04-01. |
| `'< 2026-05-01'` | that finish before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that finish between 2026-04-01 and 2026-05-01. |
| `null` | regardless of their end date. |

::: code
```twig Twig
{# Fetch events that finish today or later #}
{% set today = date('today')|atom %}

{% set events = craft.events.events()
    .endDate(">= #{today}")
    .all() %}
```

```php PHP
// Fetch events that finish today or later
$today = (new \DateTime('today'))->format(\DateTime::ATOM);

$events = \verbb\events\elements\Event::find()
    ->endDate(">= {$today}")
    ->all();
```
:::
:::

::: reference
### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig Twig
{# Fetch events in a specific order #}
{% set events = craft.events.events()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php PHP
// Fetch events in a specific order
$events = \verbb\events\elements\Event::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::
:::

::: reference
### `id`

Narrows the query results based on the events’ IDs.

| Value | Fetches events… |
| --- | --- |
| `1` | with an ID of 1. |
| `'not 1'` | not with an ID of 1. |
| `[1, 2]` | with an ID of 1 or 2. |
| `['not', 1, 2]` | not with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch the event by its ID #}
{% set event = craft.events.events()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the event by its ID
$event = \verbb\events\elements\Event::find()
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
{# Fetch events in reverse #}
{% set events = craft.events.events()
    .inReverse()
    .all() %}
```

```php PHP
// Fetch events in reverse
$events = \verbb\events\elements\Event::find()
    ->inReverse()
    ->all();
```
:::
:::

::: reference
### `limit`

Determines the number of events that should be returned.

::: code
```twig Twig
{# Fetch up to 10 events #}
{% set events = craft.events.events()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 events
$events = \verbb\events\elements\Event::find()
    ->limit(10)
    ->all();
```
:::
:::

::: reference
### `offset`

Determines how many events should be skipped in the results.

::: code
```twig Twig
{# Fetch all events except for the first 3 #}
{% set events = craft.events.events()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all events except for the first 3
$events = \verbb\events\elements\Event::find()
    ->offset(3)
    ->all();
```
:::
:::

::: reference
### `orderBy`

Determines the order that the events should be returned in. Events are ordered by their post date, newest first, by default.

::: code
```twig Twig
{# Fetch all events in order of date created #}
{% set events = craft.events.events()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all events in order of date created
$events = \verbb\events\elements\Event::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::
:::

::: reference
### `relatedTo`

Narrows the query results to only events that are related to certain other elements. See [Relations](https://craftcms.com/docs/5.x/system/relations.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all events that are related to myCategory #}
{% set events = craft.events.events()
    .relatedTo(myCategory)
    .all() %}
```

```php PHP
// Fetch all events that are related to myCategory
$events = \verbb\events\elements\Event::find()
    ->relatedTo($myCategory)
    ->all();
```
:::
:::

::: reference
### `search`

Narrows the query results to only events that match a search query. See [Searching](https://craftcms.com/docs/5.x/system/searching.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all events that match the search query #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{% set events = craft.events.events()
    .search(searchQuery)
    .all() %}
```

```php PHP
// Fetch all events that match the search query
$searchQuery = \Craft::$app->getRequest()->getQueryParam('q');

$events = \verbb\events\elements\Event::find()
    ->search($searchQuery)
    ->all();
```
:::
:::

::: reference
### `site`

Determines which site the events should be queried in. The current site is used by default.

| Value | Fetches events… |
| --- | --- |
| `'foo'` | from the site with a handle of `foo`. |
| `['foo', 'bar']` | from a site with a handle of `foo` or `bar`. |
| `'*'` | from any site. |
| a `craft\models\Site` object | from the site represented by the object. |

::: code
```twig Twig
{# Fetch events from the Foo site #}
{% set events = craft.events.events()
    .site('foo')
    .all() %}
```

```php PHP
// Fetch events from the Foo site
$events = \verbb\events\elements\Event::find()
    ->site('foo')
    ->all();
```
:::
:::

::: reference
### `siteId`

Determines which site the events should be queried in, per the site’s ID. The current site is used by default.

::: code
```twig Twig
{# Fetch events from the site with an ID of 1 #}
{% set events = craft.events.events()
    .siteId(1)
    .all() %}
```

```php PHP
// Fetch events from the site with an ID of 1
$events = \verbb\events\elements\Event::find()
    ->siteId(1)
    ->all();
```
:::
:::

::: reference
### `slug`

Narrows the query results based on the events’ slugs.

| Value | Fetches events… |
| --- | --- |
| `'foo'` | with a slug of `foo`. |
| `'foo*'` | with a slug that begins with `foo`. |
| `'*foo'` | with a slug that ends with `foo`. |
| `'*foo*'` | with a slug that contains `foo`. |
| `'not *foo*'` | with a slug that doesn’t contain `foo`. |
| `['*foo*', '*bar*']` | with a slug that contains `foo` or `bar`. |
| `['not', '*foo*', '*bar*']` | with a slug that doesn’t contain `foo` or `bar`. |

::: code
```twig Twig
{# Fetch the event with the slug in the URL #}
{% set requestedSlug = craft.app.request.getSegment(3) %}

{% set event = craft.events.events()
    .slug(requestedSlug|literal)
    .one() %}
```

```php PHP
// Fetch the event with the slug in the URL
$requestedSlug = \Craft::$app->getRequest()->getSegment(3);

$event = \verbb\events\elements\Event::find()
    ->slug(\craft\helpers\Db::escapeParam($requestedSlug))
    ->one();
```
:::
:::

::: reference
### `startDate`

Narrows the query results based on the events’ start dates, which is the start date of their first enabled session.

| Value | Fetches events… |
| --- | --- |
| `'>= 2026-04-01'` | that start on or after 2026-04-01. |
| `'< 2026-05-01'` | that start before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that start between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch events that start today or later #}
{% set today = date('today')|atom %}

{% set events = craft.events.events()
    .startDate(">= #{today}")
    .all() %}
```

```php PHP
// Fetch events that start today or later
$today = (new \DateTime('today'))->format(\DateTime::ATOM);

$events = \verbb\events\elements\Event::find()
    ->startDate(">= {$today}")
    ->all();
```
:::
:::

::: reference
### `status`

Narrows the query results based on the events’ statuses. Pass `null` to fetch events regardless of their status.

| Value | Fetches events… |
| --- | --- |
| `'live'` _(default)_ | that are live. |
| `'pending'` | that are pending (enabled with a Post Date in the future). |
| `'expired'` | that are expired (enabled with an Expiry Date in the past). |
| `'disabled'` | that are disabled. |
| `['live', 'pending']` | that are live or pending. |
| `null` | regardless of status. |

::: code
```twig Twig
{# Fetch disabled events #}
{% set events = craft.events.events()
    .status('disabled')
    .all() %}
```

```php PHP
// Fetch disabled events
$events = \verbb\events\elements\Event::find()
    ->status('disabled')
    ->all();
```
:::
:::

::: reference
### `title`

Narrows the query results based on the events’ titles.

| Value | Fetches events… |
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
{# Fetch events with a title that contains "Foo" #}
{% set events = craft.events.events()
    .title('*Foo*')
    .all() %}
```

```php PHP
// Fetch events with a title that contains "Foo"
$events = \verbb\events\elements\Event::find()
    ->title('*Foo*')
    ->all();
```
:::
:::

::: reference
### `type`

Narrows the query results based on the events’ types, per the event types’ handles.

| Value | Fetches events… |
| --- | --- |
| `'foo'` | of a type with a handle of `foo`. |
| `'not foo'` | not of a type with a handle of `foo`. |
| `['foo', 'bar']` | of a type with a handle of `foo` or `bar`. |
| `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`. |
| an `EventType` object | of a type represented by the object. |

::: code
```twig Twig
{# Fetch events with a Conference event type #}
{% set events = craft.events.events()
    .type('conference')
    .all() %}
```

```php PHP
// Fetch events with a Conference event type
$events = \verbb\events\elements\Event::find()
    ->type('conference')
    ->all();
```
:::
:::

::: reference
### `typeId`

Narrows the query results based on the events’ types, per the event types’ IDs.

| Value | Fetches events… |
| --- | --- |
| `1` | of a type with an ID of 1. |
| `'not 1'` | not of a type with an ID of 1. |
| `[1, 2]` | of a type with an ID of 1 or 2. |
| `['not', 1, 2]` | not of a type with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch events of the event type with an ID of 1 #}
{% set events = craft.events.events()
    .typeId(1)
    .all() %}
```

```php PHP
// Fetch events of the event type with an ID of 1
$events = \verbb\events\elements\Event::find()
    ->typeId(1)
    ->all();
```
:::
:::

::: reference
### `uid`

Narrows the query results based on the events’ UIDs.

::: code
```twig Twig
{# Fetch the event by its UID #}
{% set event = craft.events.events()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the event by its UID
$event = \verbb\events\elements\Event::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::
:::

::: reference
### `uri`

Narrows the query results based on the events’ URIs.

| Value | Fetches events… |
| --- | --- |
| `'foo'` | with a URI of `foo`. |
| `'foo*'` | with a URI that begins with `foo`. |
| `'*foo'` | with a URI that ends with `foo`. |
| `'*foo*'` | with a URI that contains `foo`. |
| `'not *foo*'` | with a URI that doesn’t contain `foo`. |
| `['*foo*', '*bar*']` | with a URI that contains `foo` or `bar`. |
| `['not', '*foo*', '*bar*']` | with a URI that doesn’t contain `foo` or `bar`. |

::: code
```twig Twig
{# Fetch the event with the requested URI #}
{% set requestedUri = craft.app.request.getPathInfo() %}

{% set event = craft.events.events()
    .uri(requestedUri|literal)
    .one() %}
```

```php PHP
// Fetch the event with the requested URI
$requestedUri = \Craft::$app->getRequest()->getPathInfo();

$event = \verbb\events\elements\Event::find()
    ->uri(\craft\helpers\Db::escapeParam($requestedUri))
    ->one();
```
:::
:::

::: reference
### `with`

Causes the query to return matching events eager-loaded with related elements. See [Eager-Loading Elements](https://craftcms.com/docs/5.x/development/eager-loading.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch events eager-loaded with the “Related” field’s relations #}
{% set events = craft.events.events()
    .with(['related'])
    .all() %}
```

```php PHP
// Fetch events eager-loaded with the “Related” field’s relations
$events = \verbb\events\elements\Event::find()
    ->with(['related'])
    ->all();
```
:::
:::
