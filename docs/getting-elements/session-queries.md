# Session Queries
You can fetch sessions in your templates or PHP code using **session queries**.

:::code
```twig Twig
{# Create a new session query #}
{% set myQuery = craft.events.sessions() %}
```

```php PHP
// Create a new session query
$myQuery = \verbb\events\elements\Session::find();
```
:::

Once you’ve created an session query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Session](docs:developers/session) objects will be returned.

:::tip
See Introduction to [Element Queries](https://craftcms.com/docs/4.x/element-queries/) in the Craft docs to learn about how element queries work.
:::

## Example
We can display sessions for a given type by doing the following:

1. Create a session query with `craft.events.sessions()`.
2. Set the [type](#type) and [limit](#limit) parameters on it.
3. Fetch all sessions with `.all()` and output.
4. Loop through the sessions using a [for](https://twig.symfony.com/doc/2.x/tags/for.html) tag to output the contents.

```twig
{# Create a sessions query with the 'type' and 'limit' parameters #}
{% set sessionsQuery = craft.events.sessions()
    .type('generalEvents')
    .limit(10) %}

{# Fetch the sessions #}
{% set sessions = sessionsQuery.all() %}

{# Display their contents #}
{% for session in sessions %}
    <p>{{ session.title }}</p>
{% endfor %}
```

## Parameters
Event queries support the following parameters:

<!-- BEGIN PARAMS -->

### `after`
Narrows the query results to only events that were posted on or after a certain date.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'2018-04-01'` | that were posted after 2018-04-01.
| a [DateTime](http://php.net/class.datetime) object | that were posted after the date represented by the object.

::: code
```twig Twig
{# Fetch sessions posted this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set sessions = craft.events.sessions()
    .after(firstDayOfMonth)
    .all() %}
```

```php PHP
// Fetch sessions posted this month
$firstDayOfMonth = new \DateTime('first day of this month');

$sessions = \verbb\events\elements\Session::find()
    ->after($firstDayOfMonth)
    ->all();
```
:::



### `anyStatus`
Clears out the [status()](https://docs.craftcms.com/api/v4/craft-elements-db-elementquery.html#method-status) and [enabledForSite()](https://docs.craftcms.com/api/v4/craft-elements-db-elementquery.html#method-enabledforsite) parameters.

::: code
```twig Twig
{# Fetch all events, regardless of status #}
{% set sessions = craft.events.sessions()
    .anyStatus()
    .all() %}
```

```php PHP
// Fetch all events, regardless of status
$sessions = \verbb\events\elements\Session::find()
    ->anyStatus()
    ->all();
```
:::



### `asArray`
Causes the query to return matching events as arrays of data, rather than [Event](docs:developers/event) objects.

::: code
```twig Twig
{# Fetch sessions as arrays #}
{% set sessions = craft.events.sessions()
    .asArray()
    .all() %}
```

```php PHP
// Fetch sessions as arrays
$sessions = \verbb\events\elements\Session::find()
    ->asArray()
    ->all();
```
:::



### `before`
Narrows the query results to only events that were posted before a certain date.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'2018-04-01'` | that were posted before 2018-04-01.
| a [DateTime](http://php.net/class.datetime) object | that were posted before the date represented by the object.

::: code
```twig Twig
{# Fetch sessions posted before this month #}
{% set firstDayOfMonth = date('first day of this month') %}

{% set sessions = craft.events.sessions()
    .before(firstDayOfMonth)
    .all() %}
```

```php PHP
// Fetch sessions posted before this month
$firstDayOfMonth = new \DateTime('first day of this month');

$sessions = \verbb\events\elements\Session::find()
    ->before($firstDayOfMonth)
    ->all();
```
:::



### `customer`
Narrows the query results to only events that have been purchased by a customer.

::: code
```twig Twig
{# Fetch sessions that have been purchased by a customer #}
{% set sessions = craft.events.sessions()
    .customer(craft.commerce.getCarts().getCart().customer)
    .all() %}
```

```php PHP
// Fetch sessions that have been purchased by a customer
$sessions = \verbb\events\elements\Session::find()
    ->customer($customer)
    ->all();
````
:::



### `dateCreated`
Narrows the query results based on the sessions’ creation dates.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch sessions created last month #}
{% set start = date('first day of last month') | atom %}
{% set end = date('first day of this month') | atom %}

{% set sessions = craft.events.sessions()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch sessions created last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$sessions = \verbb\events\elements\Session::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateUpdated`
Narrows the query results based on the sessions’ last-updated dates.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch sessions updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set sessions = craft.events.sessions()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php PHP
// Fetch sessions updated in the last week
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$sessions = \verbb\events\elements\Session::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::



### `endDate`
Narrows the query results based on the sessions’ end date.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'>= 2018-04-01'` | that has an end date on or after 2018-04-01.
| `'< 2018-05-01'` | that has an end date before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that has an end date between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch sessions finishing after today #}
{% set end = date('today') | atom %}

{% set sessions = craft.events.sessions()
    .endDate(">= #{end}")
    .all() %}
```

```php PHP
// Fetch sessions created last month
$end = new \DateTime('today')->format(\DateTime::ATOM);

$sessions = \verbb\events\elements\Session::find()
    ->endDate(">= {$end}"])
    ->all();
```
:::



### `expiryDate`
Narrows the query results based on the sessions’ expiry dates.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'>= 2020-04-01'` | that will expire on or after 2020-04-01.
| `'< 2020-05-01'` | that will expire before 2020-05-01
| `['and', '>= 2020-04-04', '< 2020-05-01']` | that will expire between 2020-04-01 and 2020-05-01.

::: code
```twig Twig
{# Fetch sessions expiring this month #}
{% set nextMonth = date('first day of next month')|atom %}

{% set sessions = craft.events.sessions()
    .expiryDate("< #{nextMonth}")
    .all() %}
```

```php PHP
// Fetch sessions expiring this month
$nextMonth = new \DateTime('first day of next month')->format(\DateTime::ATOM);

$sessions = \verbb\events\elements\Session::find()
    ->expiryDate("< {$nextMonth}")
    ->all();
```
:::



### `fixedOrder`
Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig Twig
{# Fetch sessions in a specific order #}
{% set sessions = craft.events.sessions()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php PHP
// Fetch sessions in a specific order
$sessions = \verbb\events\elements\Session::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::



### `hasTicket`
Narrows the query results to only events that have certain tickets.

Possible values include:

| Value | Fetches sessions…
| - | -
| a TicketQuery object | with tickets that match the query.



### `id`
Narrows the query results based on the sessions’ IDs.

Possible values include:

| Value | Fetches sessions…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch the event by its ID #}
{% set event = craft.events.sessions()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the event by its ID
$event = \verbb\events\elements\Session::find()
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
```twig Twig
{# Fetch sessions in reverse #}
{% set sessions = craft.events.sessions()
    .inReverse()
    .all() %}
```

```php PHP
// Fetch sessions in reverse
$sessions = \verbb\events\elements\Session::find()
    ->inReverse()
    ->all();
```
:::



### `limit`
Determines the number of events that should be returned.

::: code
```twig Twig
{# Fetch up to 10 events  #}
{% set sessions = craft.events.sessions()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 events
$sessions = \verbb\events\elements\Session::find()
    ->limit(10)
    ->all();
```
:::



### `offset`
Determines how many events should be skipped in the results.

::: code
```twig Twig
{# Fetch all events except for the first 3 #}
{% set sessions = craft.events.sessions()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all events except for the first 3
$sessions = \verbb\events\elements\Session::find()
    ->offset(3)
    ->all();
```
:::



### `orderBy`
Determines the order that the events should be returned in.

::: code
```twig Twig
{# Fetch all events in order of date created #}
{% set sessions = craft.events.sessions()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all events in order of date created
$sessions = \verbb\events\elements\Session::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::



### `postDate`
Narrows the query results based on the sessions’ post dates.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'>= 2018-04-01'` | that were posted on or after 2018-04-01.
| `'< 2018-05-01'` | that were posted before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were posted between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch sessions posted last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set sessions = craft.events.sessions()
    .postDate(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch sessions posted last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$sessions = \verbb\events\elements\Session::find()
    ->postDate(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `relatedTo`
Narrows the query results to only events that are related to certain other elements.

See [Relations](https://craftcms.com/docs/4.x/relations.html) for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all events that are related to myCategory #}
{% set sessions = craft.events.sessions()
    .relatedTo(myCategory)
    .all() %}
```

```php PHP
// Fetch all events that are related to $myCategory
$sessions = \verbb\events\elements\Session::find()
    ->relatedTo($myCategory)
    ->all();
```
:::



### `search`
Narrows the query results to only events that match a search query.

See [Searching](https://craftcms.com/docs/4.x/searching.html) for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{# Fetch all events that match the search query #}
{% set sessions = craft.events.sessions()
    .search(searchQuery)
    .all() %}
```

```php PHP
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->getRequest()->getQueryParam('q');

// Fetch all events that match the search query
$sessions = \verbb\events\elements\Session::find()
    ->search($searchQuery)
    ->all();
```
:::



### `site`
Determines which site the events should be queried in.

The current site will be used by default.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'foo'` | from the site with a handle of `foo`.
| a `\craft\commerce\elements\db\Site` object | from the site represented by the object.

::: code
```twig Twig
{# Fetch sessions from the Foo site #}
{% set sessions = craft.events.sessions()
    .site('foo')
    .all() %}
```

```php PHP
// Fetch sessions from the Foo site
$sessions = \verbb\events\elements\Session::find()
    ->site('foo')
    ->all();
```
:::



### `siteId`
Determines which site the events should be queried in, per the site’s ID.

The current site will be used by default.

::: code
```twig Twig
{# Fetch sessions from the site with an ID of 1 #}
{% set sessions = craft.events.sessions()
    .siteId(1)
    .all() %}
```

```php PHP
// Fetch sessions from the site with an ID of 1
$sessions = \verbb\events\elements\Session::find()
    ->siteId(1)
    ->all();
```
:::



### `slug`
Narrows the query results based on the sessions’ slugs.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'foo'` | with a slug of `foo`.
| `'foo*'` | with a slug that begins with `foo`.
| `'*foo'` | with a slug that ends with `foo`.
| `'*foo*'` | with a slug that contains `foo`.
| `'not *foo*'` | with a slug that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a slug that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a slug that doesn’t contain `foo` or `bar`.

::: code
```twig Twig
{# Get the requested event slug from the URL #}
{% set requestedSlug = craft.app.request.getSegment(3) %}

{# Fetch the event with that slug #}
{% set event = craft.events.sessions()
    .slug(requestedSlug|literal)
    .one() %}
```

```php PHP
// Get the requested event slug from the URL
$requestedSlug = \Craft::$app->getRequest()->getSegment(3);

// Fetch the event with that slug
$event = \verbb\events\elements\Session::find()
    ->slug(\craft\helpers\Db::escapeParam($requestedSlug))
    ->one();
```
:::



### `startDate`
Narrows the query results based on the sessions’ start date.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'>= 2018-04-01'` | that has a start date on or after 2018-04-01.
| `'< 2018-05-01'` | that has a start date before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that has a start date between 2018-04-01 and 2018-05-01.

::: code
```twig Twig
{# Fetch sessions from today onwards #}
{% set start = date('today') | atom %}

{% set sessions = craft.events.sessions()
    .startDate(">= #{start}")
    .all() %}
```

```php PHP
// Fetch sessions created last month
$start = new \DateTime('today')->format(\DateTime::ATOM);

$sessions = \verbb\events\elements\Session::find()
    ->startDate(">= {$start}"])
    ->all();
```
:::



### `status`
Narrows the query results based on the sessions’ statuses.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'live'` _(default)_ | that are live.
| `'pending'` | that are pending (enabled with a Post Date in the future).
| `'expired'` | that are expired (enabled with an Expiry Date in the past).
| `'disabled'` | that are disabled.
| `['live', 'pending']` | that are live or pending.

::: code
```twig Twig
{# Fetch disabled events #}
{% set sessions = craft.events.sessions()
    .status('disabled')
    .all() %}
```

```php PHP
// Fetch disabled events
$sessions = \verbb\events\elements\Session::find()
    ->status('disabled')
    ->all();
```
:::



### `title`
Narrows the query results based on the sessions’ titles.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'Foo'` | with a title of `Foo`.
| `'Foo*'` | with a title that begins with `Foo`.
| `'*Foo'` | with a title that ends with `Foo`.
| `'*Foo*'` | with a title that contains `Foo`.
| `'not *Foo*'` | with a title that doesn’t contain `Foo`.
| `['*Foo*', '*Bar*'` | with a title that contains `Foo` or `Bar`.
| `['not', '*Foo*', '*Bar*']` | with a title that doesn’t contain `Foo` or `Bar`.

::: code
```twig Twig
{# Fetch sessions with a title that contains "Foo" #}
{% set sessions = craft.events.sessions()
    .title('*Foo*')
    .all() %}
```

```php PHP
// Fetch sessions with a title that contains "Foo"
$sessions = \verbb\events\elements\Session::find()
    ->title('*Foo*')
    ->all();
```
:::



### `type`
Narrows the query results based on the sessions’ types.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'foo'` | of a type with a handle of `foo`.
| `'not foo'` | not of a type with a handle of `foo`.
| `['foo', 'bar']` | of a type with a handle of `foo` or `bar`.
| `['not', 'foo', 'bar']` | not of a type with a handle of `foo` or `bar`.
| an Event Type object | of a type represented by the object.

::: code
```twig Twig
{# Fetch sessions with a Foo event type #}
{% set sessions = craft.events.sessions()
    .type('foo')
    .all() %}
```

```php PHP
// Fetch sessions with a Foo event type
$sessions = \verbb\events\elements\Session::find()
    ->type('foo')
    ->all();
```
:::



### `typeId`
Narrows the query results based on the sessions’ types, per the types’ IDs.

Possible values include:

| Value | Fetches sessions…
| - | -
| `1` | of a type with an ID of 1.
| `'not 1'` | not of a type with an ID of 1.
| `[1, 2]` | of a type with an ID of 1 or 2.
| `['not', 1, 2]` | not of a type with an ID of 1 or 2.

::: code
```twig Twig
{# Fetch sessions of the event type with an ID of 1 #}
{% set sessions = craft.events.sessions()
    .typeId(1)
    .all() %}
```

```php PHP
// Fetch sessions of the event type with an ID of 1
$sessions = \verbb\events\elements\Session::find()
    ->typeId(1)
    ->all();
```
:::



### `uid`
Narrows the query results based on the sessions’ UIDs.

::: code
```twig Twig
{# Fetch the event by its UID #}
{% set event = craft.events.sessions()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the event by its UID
$event = \verbb\events\elements\Session::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::



### `uri`
Narrows the query results based on the sessions’ URIs.

Possible values include:

| Value | Fetches sessions…
| - | -
| `'foo'` | with a URI of `foo`.
| `'foo*'` | with a URI that begins with `foo`.
| `'*foo'` | with a URI that ends with `foo`.
| `'*foo*'` | with a URI that contains `foo`.
| `'not *foo*'` | with a URI that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a URI that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a URI that doesn’t contain `foo` or `bar`.

::: code
```twig Twig
{# Get the requested URI #}
{% set requestedUri = craft.app.request.getPathInfo() %}

{# Fetch the event with that URI #}
{% set event = craft.events.sessions()
    .uri(requestedUri|literal)
    .one() %}
```

```php PHP
// Get the requested URI
$requestedUri = \Craft::$app->getRequest()->getPathInfo();

// Fetch the event with that URI
$event = \verbb\events\elements\Session::find()
    ->uri(\craft\helpers\Db::escapeParam($requestedUri))
    ->one();
```
:::



### `with`
Causes the query to return matching events eager-loaded with related elements.

See [Eager-Loading Elements](https://craftcms.com/docs/4.x/eager-loading-elements.html) for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch sessions eager-loaded with the "Related" field’s relations #}
{% set sessions = craft.events.sessions()
    .with(['related'])
    .all() %}
```

```php PHP
// Fetch sessions eager-loaded with the "Related" field’s relations
$sessions = \verbb\events\elements\Session::find()
    ->with(['related'])
    ->all();
```
:::


<!-- END PARAMS -->
