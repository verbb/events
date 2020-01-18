# Ticket Queries

You can fetch tickets in your templates or PHP code using **ticket queries**.

::: code
```twig
{# Create a new ticket query #}
{% set myTicketQuery = craft.events.tickets() %}
```
```php
// Create a new ticket query
$myTicketQuery = \verbb\events\elements\Ticket::find();
```
:::

Once you’ve created a ticket query, you can set [parameters](#parameters) on it to narrow down the results, and then [execute it](https://docs.craftcms.com/v3/dev/element-queries/#executing-element-queries) by calling `.all()`. An array of [Ticket](docs:developers/ticket) objects will be returned.

::: tip
See [Introduction to Element Queries](https://docs.craftcms.com/v3/dev/element-queries/) in the Craft docs to learn about how element queries work.
:::

## Example

We can display a specific ticket by its ID by doing the following:

1. Create a ticket query with `craft.events.tickets()`.
2. Set the [id](#id) parameter on it.
3. Fetch the ticket with `.one()`.
4. Output information about the ticket as HTML.

```twig
{# Get the requested ticket ID from the query string #}
{% set ticketId = craft.app.request.getQueryParam('id') %}

{# Create a ticket query with the 'id' parameter #}
{% set myTicketQuery = craft.events.tickets()
    .id(ticketId) %}

{# Fetch the ticket #}
{% set ticket = myTicketQuery.one() %}

{# Make sure it exists #}
{% if not ticket %}
    {% exit 404 %}
{% endif %}

{# Display the ticket #}
<h1>{{ ticket.title }}</h1>
<!-- ... -->
```

<!-- BEGIN PARAMS -->

### `anyStatus`

Clears out the [status()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-status) and [enabledForSite()](https://docs.craftcms.com/api/v3/craft-elements-db-elementquery.html#method-enabledforsite) parameters.

::: code
```twig
{# Fetch all tickets, regardless of status #}
{% set tickets = craft.events.tickets()
    .anyStatus()
    .all() %}
```

```php
// Fetch all tickets, regardless of status
$tickets = \verbb\events\elements\Ticket::find()
    ->anyStatus()
    ->all();
```
:::



### `asArray`

Causes the query to return matching tickets as arrays of data, rather than [Ticket](docs:developers/ticket) objects.

::: code
```twig
{# Fetch tickets as arrays #}
{% set tickets = craft.events.tickets()
    .asArray()
    .all() %}
```

```php
// Fetch tickets as arrays
$tickets = \verbb\events\elements\Ticket::find()
    ->asArray()
    ->all();
```
:::



### `customer`

Narrows the query results to only tickets that have been purchased by a customer.

::: code
```twig
{# Fetch tickets that have been purchased by a customer #}
{% set tickets = craft.events.tickets()
    .customer(craft.commerce.getCart().customer)
    .all() %}
```

```php
// Fetch tickets that have been purchased by a customer
$tickets = \verbb\events\elements\Event::find()
    ->customer($customer)
    ->all();
````
:::



### `dateCreated`

Narrows the query results based on the tickets’ creation dates.

Possible values include:

| Value | Fetches tickets…
| - | -
| `'>= 2018-04-01'` | that were created on or after 2018-04-01.
| `'< 2018-05-01'` | that were created before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were created between 2018-04-01 and 2018-05-01.

::: code
```twig
{# Fetch tickets created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set tickets = craft.events.tickets()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php
// Fetch tickets created last month
$start = new \DateTime('first day of next month')->format(\DateTime::ATOM);
$end = new \DateTime('first day of this month')->format(\DateTime::ATOM);

$tickets = \verbb\events\elements\Ticket::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::



### `dateUpdated`

Narrows the query results based on the tickets’ last-updated dates.

Possible values include:

| Value | Fetches tickets…
| - | -
| `'>= 2018-04-01'` | that were updated on or after 2018-04-01.
| `'< 2018-05-01'` | that were updated before 2018-05-01
| `['and', '>= 2018-04-04', '< 2018-05-01']` | that were updated between 2018-04-01 and 2018-05-01.

::: code
```twig
{# Fetch tickets updated in the last week #}
{% set lastWeek = date('1 week ago')|atom %}

{% set tickets = craft.events.tickets()
    .dateUpdated(">= #{lastWeek}")
    .all() %}
```

```php
// Fetch tickets updated in the last week
$lastWeek = new \DateTime('1 week ago')->format(\DateTime::ATOM);

$tickets = \verbb\events\elements\Ticket::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::



### `fixedOrder`

Causes the query results to be returned in the order specified by [id](#id).

::: code
```twig
{# Fetch tickets in a specific order #}
{% set tickets = craft.events.tickets()
    .id([1, 2, 3, 4, 5])
    .fixedOrder()
    .all() %}
```

```php
// Fetch tickets in a specific order
$tickets = \verbb\events\elements\Ticket::find()
    ->id([1, 2, 3, 4, 5])
    ->fixedOrder()
    ->all();
```
:::



### `hasEvent`

Narrows the query results to only tickets for certain events.

Possible values include:

| Value | Fetches tickets…
| - | -
| a EventQuery object | for events that match the query.



### `id`

Narrows the query results based on the tickets’ IDs.

Possible values include:

| Value | Fetches tickets…
| - | -
| `1` | with an ID of 1.
| `'not 1'` | not with an ID of 1.
| `[1, 2]` | with an ID of 1 or 2.
| `['not', 1, 2]` | not with an ID of 1 or 2.

::: code
```twig
{# Fetch the ticket by its ID #}
{% set ticket = craft.events.tickets()
    .id(1)
    .one() %}
```

```php
// Fetch the ticket by its ID
$ticket = \verbb\events\elements\Ticket::find()
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
{# Fetch tickets in reverse #}
{% set tickets = craft.events.tickets()
    .inReverse()
    .all() %}
```

```php
// Fetch tickets in reverse
$tickets = \verbb\events\elements\Ticket::find()
    ->inReverse()
    ->all();
```
:::



### `limit`

Determines the number of tickets that should be returned.

::: code
```twig
{# Fetch up to 10 tickets  #}
{% set tickets = craft.events.tickets()
    .limit(10)
    .all() %}
```

```php
// Fetch up to 10 tickets
$tickets = \verbb\events\elements\Ticket::find()
    ->limit(10)
    ->all();
```
:::



### `offset`

Determines how many tickets should be skipped in the results.

::: code
```twig
{# Fetch all tickets except for the first 3 #}
{% set tickets = craft.events.tickets()
    .offset(3)
    .all() %}
```

```php
// Fetch all tickets except for the first 3
$tickets = \verbb\events\elements\Ticket::find()
    ->offset(3)
    ->all();
```
:::



### `orderBy`

Determines the order that the tickets should be returned in.

::: code
```twig
{# Fetch all tickets in order of date created #}
{% set tickets = craft.events.tickets()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php
// Fetch all tickets in order of date created
$tickets = \verbb\events\elements\Ticket::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::



### `price`

Narrows the query results based on the tickets’ price.

Possible values include:

| Value | Fetches tickets…
| - | -
| `100` | with a price of 100.
| `'>= 100'` | with a price of at least 100.
| `'< 100'` | with a price of less than 100.



### `event`

Narrows the query results based on the tickets’ event.

Possible values include:

| Value | Fetches tickets…
| - | -
| a [Event](docs:developers/event) object | for a event represented by the object.



### `eventId`

Narrows the query results based on the tickets’ events’ IDs.

Possible values include:

| Value | Fetches tickets…
| - | -
| `1` | for a event with an ID of 1.
| `[1, 2]` | for event with an ID of 1 or 2.
| `['not', 1, 2]` | for event not with an ID of 1 or 2.



### `relatedTo`

Narrows the query results to only tickets that are related to certain other elements.

See [Relations](https://docs.craftcms.com/v3/relations.html) for a full explanation of how to work with this parameter.

::: code
```twig
{# Fetch all tickets that are related to myCategory #}
{% set tickets = craft.events.tickets()
    .relatedTo(myCategory)
    .all() %}
```

```php
// Fetch all tickets that are related to $myCategory
$tickets = \verbb\events\elements\Ticket::find()
    ->relatedTo($myCategory)
    ->all();
```
:::



### `search`

Narrows the query results to only tickets that match a search query.

See [Searching](https://docs.craftcms.com/v3/searching.html) for a full explanation of how to work with this parameter.

::: code
```twig
{# Get the search query from the 'q' query string param #}
{% set searchQuery = craft.request.getQueryParam('q') %}

{# Fetch all tickets that match the search query #}
{% set tickets = craft.events.tickets()
    .search(searchQuery)
    .all() %}
```

```php
// Get the search query from the 'q' query string param
$searchQuery = \Craft::$app->request->getQueryParam('q');

// Fetch all tickets that match the search query
$tickets = \verbb\events\elements\Ticket::find()
    ->search($searchQuery)
    ->all();
```
:::



### `site`

Determines which site the tickets should be queried in.

The current site will be used by default.

Possible values include:

| Value | Fetches tickets…
| - | -
| `'foo'` | from the site with a handle of `foo`.
| a `\craft\commerce\elements\db\Site` object | from the site represented by the object.

::: code
```twig
{# Fetch tickets from the Foo site #}
{% set tickets = craft.events.tickets()
    .site('foo')
    .all() %}
```

```php
// Fetch tickets from the Foo site
$tickets = \verbb\events\elements\Ticket::find()
    ->site('foo')
    ->all();
```
:::



### `siteId`

Determines which site the tickets should be queried in, per the site’s ID.

The current site will be used by default.

::: code
```twig
{# Fetch tickets from the site with an ID of 1 #}
{% set tickets = craft.events.tickets()
    .siteId(1)
    .all() %}
```

```php
// Fetch tickets from the site with an ID of 1
$tickets = \verbb\events\elements\Ticket::find()
    ->siteId(1)
    ->all();
```
:::



### `sku`

Narrows the query results based on the tickets’ SKUs.

Possible values include:

| Value | Fetches tickets…
| - | -
| `'foo'` | with a SKU of `foo`.
| `'foo*'` | with a SKU that begins with `foo`.
| `'*foo'` | with a SKU that ends with `foo`.
| `'*foo*'` | with a SKU that contains `foo`.
| `'not *foo*'` | with a SKU that doesn’t contain `foo`.
| `['*foo*', '*bar*'` | with a SKU that contains `foo` or `bar`.
| `['not', '*foo*', '*bar*']` | with a SKU that doesn’t contain `foo` or `bar`.

::: code
```twig
{# Get the requested ticket SKU from the URL #}
{% set requestedSlug = craft.app.request.getSegment(3) %}

{# Fetch the ticket with that slug #}
{% set ticket = craft.events.tickets()
    .sku(requestedSlug|literal)
    .one() %}
```

```php
// Get the requested ticket SKU from the URL
$requestedSlug = \Craft::$app->request->getSegment(3);

// Fetch the ticket with that slug
$ticket = \verbb\events\elements\Ticket::find()
    ->sku(\craft\helpers\Db::escapeParam($requestedSlug))
    ->one();
```
:::



### `quantity`

Narrows the query results based on the tickets’ quantity.

Possible values include:

| Value | Fetches tickets…
| - | -
| `0` | with no quantity.
| `'>= 5'` | with a quantity of at least 5.
| `'< 10'` | with a quantity of less than 10.



### `typeId`

Narrows the query results based on the tickets’ types, per their IDs.

Possible values include:

| Value | Fetches tickets…
| - | -
| `1` | for a type with an ID of 1.
| `[1, 2]` | for a type with an ID of 1 or 2.
| `['not', 1, 2]` | for a type not with an ID of 1 or 2.



### `uid`

Narrows the query results based on the tickets’ UIDs.

::: code
```twig
{# Fetch the ticket by its UID #}
{% set ticket = craft.events.tickets()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php
// Fetch the ticket by its UID
$ticket = \verbb\events\elements\Ticket::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::



### `with`

Causes the query to return matching tickets eager-loaded with related elements.

See [Eager-Loading Elements](https://docs.craftcms.com/v3/dev/eager-loading-elements.html) for a full explanation of how to work with this parameter.

::: code
```twig
{# Fetch tickets eager-loaded with the "Related" field’s relations #}
{% set tickets = craft.events.tickets()
    .with(['related'])
    .all() %}
```

```php
// Fetch tickets eager-loaded with the "Related" field’s relations
$tickets = \verbb\events\elements\Ticket::find()
    ->with(['related'])
    ->all();
```
:::

<!-- END PARAMS -->