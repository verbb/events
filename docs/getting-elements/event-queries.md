# Event Queries

You can fetch events in your templates or PHP code using **event queries**.

:::code
```twig
{# Create a new event query #}
{% set myQuery = craft.events.events() %}
```

```php
// Create a new event query
$myQuery = \verbb\events\elements\Event::find();
```
:::

Once you’ve created a event query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Event](docs:developers/event) objects will be returned.

:::tip
See Introduction to [Element Queries](https://docs.craftcms.com/v3/dev/element-queries/) in the Craft docs to learn about how element queries work.
:::

## Example

We can display events for a given type by doing the following:

1. Create an event query with `craft.events.events()`.
2. Set the [type](#type) and [limit](#limit) parameters on it.
3. Fetch all events with `.all()` and output.
4. Loop through the events using a [for](https://twig.symfony.com/doc/2.x/tags/for.html) tag to output the contents.

```twig
{# Create a events query with the 'type' and 'limit' parameters #}
{% set eventsQuery = craft.events.events()
    .type('generalEvents')
    .limit(10) %}

{# Fetch the Events #}
{% set events = eventsQuery.all() %}

{# Display their contents #}
{% for event in events %}
    <p>{{ event.title }}</p>
{% endfor %}
```

:::warning
By default, only current events will be returned when calling `craft.events.events()`. To change this, use `craft.events.events({ endDate: null })`. Events are also ordered from the oldest startDate to the newest, which you can also change with the `orderBy` parameter.
:::

## Parameters

Event queries support the following parameters:

<!-- BEGIN PARAMS -->

### `after`

Narrows the query results to only events that were posted on or after a certain date.

Possible values include:

| Value | Fetches events…
| - | -
| `'2018-04-01'` | that were posted after 2018-04-01.
| a [DateTime](http://php.net/class.datetime) object | that were posted after the date represented by the object.

::: code
```twig
{# Fetch events posted this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set events = craft.events.events()
    .after(firstDayOfMonth)
    .all() %}
```

```php
// Fetch events posted this month
$firstDayOfMonth = new \DateTime('first day of this month');

$events = \verbb\events\elements\Event::find()
    ->after($firstDayOfMonth)
    ->all();
```
:::



### `anyStatus`

Clears out the [status()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-status) and [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) parameters.

::: code
```twig
{# Fetch all events, regardless of status #}
{% set events = craft.events.events()
    .anyStatus()
    .all() %}
```

```php
// Fetch all events, regardless of status
$events = \verbb\events\elements\Event::find()
    ->anyStatus()
    ->all();
```
:::



### `asArray`

Causes the query to return matching events as arrays of data, rather than [Event](docs:developers/event) objects.

::: code
```twig
{# Fetch events as arrays #}
{% set events = craft.events.events()
    .asArray()
    .all() %}
```

```php
// Fetch events as arrays
$events = \verbb\events\elements\Event::find()
    ->asArray()
    ->all();
```
:::



### `availableForPurchase`

Narrows the query results to only events that are available for purchase.

::: code
```twig
{# Fetch events that are available for purchase #}
{% set events = craft.events.events()
    .availableForPurchase()
    .all() %}
```

```php
// Fetch events that are available for purchase
$events = \verbb\events\elements\Event::find()
    ->availableForPurchase()
    ->all();
````
:::



### `before`

Narrows the query results to only events that were posted before a certain date.

Possible values include:

| Value | Fetches events…
| - | -
| `'2018-04-01'` | that were posted before 2018-04-01.
| a [DateTime](http://php.net/class.datetime) object | that were posted before the date represented by the object.

::: code
```twig
{# Fetch events posted before this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set events = craft.events.events()
    .before(firstDayOfMonth)
    .all() %}
```

```php
// Fetch events posted before this month
$firstDayOfMonth = new \DateTime('first day of this month');

$events = \verbb\events\elements\Event::find()
    ->before($firstDayOfMonth)
    ->all();
```
:::



### `customer`

Narrows the query results to only events that have been purchased by a customer.

::: code
```twig
{# Fetch events that have been purchased by a customer #}
{% set events = craft.events.events()
    .customer(craft.commerce.getCart().customer)
    .all() %}
```

```php
// Fetch events that have been purchased by a customer
$events = \verbb\events\elements\Event::find()
    ->customer($customer)
    ->all();
````
:::



### `dateCreated`

Narrows the query results based on the events’ creation dates.

Possible values include:

| Value | Fetches events…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.

::: code
```twig
{# Fetch events created last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set events = craft.events.events()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch events created last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$events = \verbb\events\elements\Event::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateUpdated`

Narrows the query results based on the events’ last-updated dates.

Possible values include:

| Value | Fetches events…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.

::: code
```twig
{# Fetch events updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set events = craft.events.events()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php
// Fetch events updated in the last week
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$events = \verbb\events\elements\Event::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::



### `endDate`

Narrows the query results based on the events’ end date.

Possible values include:

| Value | Fetches events…
| - | -
| `'>= 2018-04-01'` | that has an end date on or after 2018-04-01.
| `'< 2018-05-01'` | that has an end date before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that has an end date between 2018-04-01 and 2018-05-01.

::: code
```twig
{# Fetch events finishing after today #}
{% set end = date('today') | atom %}

{% set events = craft.events.events()
    .endDate(">= #{end}")
    .all() %}
```

```php
// Fetch events created last month
$end = new \DateTime('today')->format(\DateTime::ATOM);

$events = \verbb\events\elements\Event::find()
    ->endDate(">= {$end}"])
    ->all();
```
:::



### `expiryDate`

Narrows the query results based on the events’ expiry dates.

Possible values include:

| Value | Fetches events…
| - | -
| `'>= 2020-04-01'` | that will expire on or after 2020-04-01.
| `'< 2020-05-01'` | that will expire before 2020-05-01
| `['and', '>= 2020-04-04', '< 2020-05-01']` | that will expire between 2020-04-01 and 2020-05-01.

::: code
```twig
{# Fetch events expiring this month #}
{% set nextMonth = date('first day of next month')|atom %}

{% set events = craft.events.events()
    .expiryDate("< #{nextMonth}")
    .all() %}
```

```php
// Fetch events expiring this month
$nextMonth = new \DateTime('first day of next month')->format(\DateTime::ATOM);

$events = \verbb\events\elements\Event::find()
    ->expiryDate("< {$nextMonth}")
    ->all();
```
:::



### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig
{# Fetch events in a specific order #}
{% set events = craft.events.events()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php
// Fetch events in a specific order
$events = \verbb\events\elements\Event::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::



### `hasTicket`

Narrows the query results to only events that have certain tickets.

Possible values include:

| Value | Fetches events…
| - | -
| a TicketQuery object | with tickets that match the query.



### `id`

Narrows the query results based on the events’ IDs.

Possible values include:

| Value | Fetches events…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.

::: code
```twig
{# Fetch the event by its ID #}
{% set event = craft.events.events()
    .id(1)
    .one() %}
```

```php
// Fetch the event by its ID
$event = \verbb\events\elements\Event::find()
    ->id(1)
    ->one();
```
:::

::: tip
This can be combined with [fixedOrder](#fixedorder) if you want the results to be returned in a specific order.
:::



### `inReverse`

Causes the query results to be returned in reverse order.

::: code
```twig
{# Fetch events in reverse #}
{% set events = craft.events.events()
    .inReverse()
    .all() %}
```

```php
// Fetch events in reverse
$events = \verbb\events\elements\Event::find()
    ->inReverse()
    ->all();
```
:::



### `limit`

Determines the number of events that should be returned.

::: code
```twig
{# Fetch up to 10 events  #}
{% set events = craft.events.events()
    .limit(10)
    .all() %}
```

```php
// Fetch up to 10 events
$events = \verbb\events\elements\Event::find()
    ->limit(10)
    ->all();
```
:::



### `offset`

Determines how many events should be skipped in the results.

::: code
```twig
{# Fetch all events except for the first 3 #}
{% set events = craft.events.events()
    .offset(3)
    .all() %}
```

```php
// Fetch all events except for the first 3
$events = \verbb\events\elements\Event::find()
    ->offset(3)
    ->all();
```
:::



### `orderBy`

Determines the order that the events should be returned in.

::: code
```twig
{# Fetch all events in order of date created #}
{% set events = craft.events.events()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php
// Fetch all events in order of date created
$events = \verbb\events\elements\Event::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::



### `postDate`

Narrows the query results based on the events’ post dates.

Possible values include:

| Value | Fetches events…
| - | -
| `'>= 2018-04-01'` | that were posted on or after 2018-04-01.
| `'< 2018-05-01'` | that were posted before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were posted between 2018-04-01 and 2018-05-01.

::: code
```twig
{# Fetch events posted last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set events = craft.events.events()
    .postDate(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch events posted last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$events = \verbb\events\elements\Event::find()
    ->postDate(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `relatedTo`

Narrows the query results to only events that are related to certain other elements.

See [Relations](https://docs.craftcms.com/v3/relations.html) for a full explanation of how to work with this parameter.

::: code
```twig
{# Fetch all events that are related to myCategory #}
{% set events = craft.events.events()
    .relatedTo(myCategory)
    .all() %}
```

```php
// Fetch all events that are related to $myCategory
$events = \verbb\events\elements\Event::find()
    ->relatedTo($myCategory)
    ->all();
```
:::



### `search`

Narrows the query results to only events that match a search query.

See [Searching](https://docs.craftcms.com/v3/searching.html) for a full explanation of how to work with this parameter.

::: code
```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.request.getQueryParam('q') %}

{# Fetch all events that match the search query #}
{% set events = craft.events.events()
    .search(searchQuery)
    .all() %}
```

```php
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->request->getQueryParam('q');

// Fetch all events that match the search query
$events = \verbb\events\elements\Event::find()
    ->search($searchQuery)
    ->all();
```
:::



### `site`

Determines which site the events should be queried in.

The current site will be used by default.

Possible values include:

| Value | Fetches events…
| - | -
| `'foo'` | from the site with a handle of `foo`.
| a `\craft\commerce\elements\db\Site` object | from the site represented by the object.

::: code
```twig
{# Fetch events from the Foo site #}
{% set events = craft.events.events()
    .site('foo')
    .all() %}
```

```php
// Fetch events from the Foo site
$events = \verbb\events\elements\Event::find()
    ->site('foo')
    ->all();
```
:::



### `siteId`

Determines which site the events should be queried in, per the site’s ID.

The current site will be used by default.

::: code
```twig
{# Fetch events from the site with an ID of 1 #}
{% set events = craft.events.events()
    .siteId(1)
    .all() %}
```

```php
// Fetch events from the site with an ID of 1
$events = \verbb\events\elements\Event::find()
    ->siteId(1)
    ->all();
```
:::



### `slug`

Narrows the query results based on the events’ slugs.

Possible values include:

| Value | Fetches events…
| - | -
| `'foo'` | with a slug of `foo`.
| `'foo*'` | with a slug that begins with `foo`.
| `'*foo'` | with a slug that ends with `foo`.
| `'*foo*'` | with a slug that contains `foo`.
| `'not *foo*'` | with a slug that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a slug that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a slug that doesn’t contain `foo` or `bar`.

::: code
```twig
{# Get the requested event slug from the URL #}
{% set requestedSlug = craft.app.request.getSegment(3) %}

{# Fetch the event with that slug #}
{% set event = craft.events.events()
    .slug(requestedSlug|literal)
    .one() %}
```

```php
// Get the requested event slug from the URL
$requestedSlug = \Craft::$app->request->getSegment(3);

// Fetch the event with that slug
$event = \verbb\events\elements\Event::find()
    ->slug(\craft\helpers\Db::escapeParam($requestedSlug))
    ->one();
```
:::



### `startDate`

Narrows the query results based on the events’ start date.

Possible values include:

| Value | Fetches events…
| - | -
| `'>= 2018-04-01'` | that has a start date on or after 2018-04-01.
| `'< 2018-05-01'` | that has a start date before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that has a start date between 2018-04-01 and 2018-05-01.

::: code
```twig
{# Fetch events from today onwards #}
{% set start = date('today') | atom %}

{% set events = craft.events.events()
    .startDate(">= #{start}")
    .all() %}
```

```php
// Fetch events created last month
$start = new \DateTime('today')->format(\DateTime::ATOM);

$events = \verbb\events\elements\Event::find()
    ->startDate(">= {$start}"])
    ->all();
```
:::



### `status`

Narrows the query results based on the events’ statuses.

Possible values include:

| Value | Fetches events…
| - | -
| `'live'` _(default)_ | that are live.
| `'pending'` | that are pending (enabled with a Post Date in the future).
| `'expired'` | that are expired (enabled with an Expiry Date in the past).
| `'disabled'` | that are disabled.
| `['live', 'pending']` | that are live or pending.

::: code
```twig
{# Fetch disabled events #}
{% set events = {twig-function}
    .status('disabled')
    .all() %}
```

```php
// Fetch disabled events
$events = \verbb\events\elements\Event::find()
    ->status('disabled')
    ->all();
```
:::



### `title`

Narrows the query results based on the events’ titles.

Possible values include:

| Value | Fetches events…
| - | -
| `'Foo'` | with a title of `Foo`.
| `'Foo*'` | with a title that begins with `Foo`.
| `'*Foo'` | with a title that ends with `Foo`.
| `'*Foo*'` | with a title that contains `Foo`.
| `'not *Foo*'` | with a title that doesn’t contain `Foo`.
| `['*Foo*', '*Bar*'` | with a title that contains `Foo` or `Bar`.
| `['not', '*Foo*', '*Bar*']` | with a title that doesn’t contain `Foo` or `Bar`.

::: code
```twig
{# Fetch events with a title that contains "Foo" #}
{% set events = craft.events.events()
    .title('*Foo*')
    .all() %}
```

```php
// Fetch events with a title that contains "Foo"
$events = \verbb\events\elements\Event::find()
    ->title('*Foo*')
    ->all();
```
:::



### `type`

Narrows the query results based on the events’ types.

Possible values include:

| Value | Fetches events…
| - | -
| `'foo'` | of a type with a handle of `foo`.
| `'not foo'` | not of a type with a handle of `foo`.
| `['foo', 'bar']` | of a type with a handle of `foo` or `bar`.
| `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`.
| an [EventType](docs:developers/event-type) object | of a type represented by the object.

::: code
```twig
{# Fetch events with a Foo event type #}
{% set events = craft.events.events()
    .type('foo')
    .all() %}
```

```php
// Fetch events with a Foo event type
$events = \verbb\events\elements\Event::find()
    ->type('foo')
    ->all();
```
:::



### `typeId`

Narrows the query results based on the events’ types, per the types’ IDs.

Possible values include:

| Value | Fetches events…
| - | -
| `1` | of a type with an ID of 1.
| `'not 1'` | not of a type with an ID of 1.
| `[1, 2]` | of a type with an ID of 1 or 2.
| `['not', 1, 2]` | not of a type with an ID of 1 or 2.

::: code
```twig
{# Fetch events of the event type with an ID of 1 #}
{% set events = craft.events.events()
    .typeId(1)
    .all() %}
```

```php
// Fetch events of the event type with an ID of 1
$events = \verbb\events\elements\Event::find()
    ->typeId(1)
    ->all();
```
:::



### `uid`

Narrows the query results based on the events’ UIDs.

::: code
```twig
{# Fetch the event by its UID #}
{% set event = craft.events.events()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php
// Fetch the event by its UID
$event = \verbb\events\elements\Event::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::



### `uri`

Narrows the query results based on the events’ URIs.

Possible values include:

| Value | Fetches events…
| - | -
| `'foo'` | with a URI of `foo`.
| `'foo*'` | with a URI that begins with `foo`.
| `'*foo'` | with a URI that ends with `foo`.
| `'*foo*'` | with a URI that contains `foo`.
| `'not *foo*'` | with a URI that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a URI that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a URI that doesn’t contain `foo` or `bar`.

::: code
```twig
{# Get the requested URI #}
{% set requestedUri = craft.app.request.getPathInfo() %}

{# Fetch the event with that URI #}
{% set event = craft.events.events()
    .uri(requestedUri|literal)
    .one() %}
```

```php
// Get the requested URI
$requestedUri = \Craft::$app->request->getPathInfo();

// Fetch the event with that URI
$event = \verbb\events\elements\Event::find()
    ->uri(\craft\helpers\Db::escapeParam($requestedUri))
    ->one();
```
:::



### `with`

Causes the query to return matching events eager-loaded with related elements.

See [Eager-Loading Elements](https://docs.craftcms.com/v3/dev/eager-loading-elements.html) for a full explanation of how to work with this parameter.

::: code
```twig
{# Fetch events eager-loaded with the "Related" field’s relations #}
{% set events = craft.events.events()
    .with(['related'])
    .all() %}
```

```php
// Fetch events eager-loaded with the "Related" field’s relations
$events = \verbb\events\elements\Event::find()
    ->with(['related'])
    ->all();
```
:::


<!-- END PARAMS -->
