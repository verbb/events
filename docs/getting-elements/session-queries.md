# Session Queries

You can fetch sessions in your templates or PHP code using **session queries**.

::: code
```twig Twig
{# Create a new session query #}
{% set myQuery = craft.events.sessions() %}
```

```php PHP
// Create a new session query
$myQuery = \verbb\events\elements\Session::find();
```
:::

Once you’ve created a session query, you can set parameters on it to narrow down the results, and then execute it by calling `.all()`. An array of [Session](docs:reference/session) objects will be returned.

See [Element Queries](https://craftcms.com/docs/5.x/development/element-queries.html) in the Craft docs to learn how element queries work.

## Example

You can list the upcoming sessions for an event by doing the following.

1. Create a session query with `craft.events.sessions()`.
2. Set the [event](#event) and [startDate](#startdate) parameters on it.
3. Fetch the sessions with `.all()`.
4. Loop through the sessions using a [for](https://twig.symfony.com/doc/3.x/tags/for.html) tag to output their contents.

This example assumes an `event` variable, such as on an event’s template.

```twig
{% set today = date('today')|atom %}

{# Create a session query with the 'event' and 'startDate' parameters #}
{% set sessions = craft.events.sessions()
    .event(event)
    .startDate(">= #{today}")
    .all() %}

{# Display their contents #}
{% for session in sessions %}
    <p>{{ session.title }}, {{ session.startDate|date('short') }}</p>
{% endfor %}
```

## Parameters

Session queries support the following parameters.

::: reference
### `allowOwnerDrafts`

Narrows the query results based on whether the sessions’ events can be drafts. Pass `false` to exclude sessions that belong to event drafts.

::: code
```twig Twig
{# Fetch sessions that don’t belong to event drafts #}
{% set sessions = craft.events.sessions()
    .allowOwnerDrafts(false)
    .all() %}
```

```php PHP
// Fetch sessions that don’t belong to event drafts
$sessions = \verbb\events\elements\Session::find()
    ->allowOwnerDrafts(false)
    ->all();
```
:::
:::

::: reference
### `allowOwnerRevisions`

Narrows the query results based on whether the sessions’ events can be revisions. Pass `false` to exclude sessions that belong to event revisions.

::: code
```twig Twig
{# Fetch sessions that don’t belong to event revisions #}
{% set sessions = craft.events.sessions()
    .allowOwnerRevisions(false)
    .all() %}
```

```php PHP
// Fetch sessions that don’t belong to event revisions
$sessions = \verbb\events\elements\Session::find()
    ->allowOwnerRevisions(false)
    ->all();
```
:::
:::

::: reference
### `asArray`

Causes the query to return matching sessions as arrays of data, rather than [Session](docs:reference/session) objects.

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
:::

::: reference
### `capacity`

Narrows the query results based on the sessions’ shared capacity.

| Value | Fetches sessions… |
| --- | --- |
| `100` | with a capacity of 100. |
| `'>= 100'` | with a capacity of 100 or more. |
| `':empty:'` | with no capacity set. |

::: code
```twig Twig
{# Fetch sessions with a capacity of 100 or more #}
{% set sessions = craft.events.sessions()
    .capacity('>= 100')
    .all() %}
```

```php PHP
// Fetch sessions with a capacity of 100 or more
$sessions = \verbb\events\elements\Session::find()
    ->capacity('>= 100')
    ->all();
```
:::
:::

::: reference
### `dateCreated`

Narrows the query results based on the sessions’ creation dates.

| Value | Fetches sessions… |
| --- | --- |
| `'>= 2026-04-01'` | that were created on or after 2026-04-01. |
| `'< 2026-05-01'` | that were created before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were created between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch sessions created last month #}
{% set start = date('first day of last month')|atom %}
{% set end = date('first day of this month')|atom %}

{% set sessions = craft.events.sessions()
    .dateCreated(['and', ">= #{start}", "< #{end}"])
    .all() %}
```

```php PHP
// Fetch sessions created last month
$start = (new \DateTime('first day of last month'))->format(\DateTime::ATOM);
$end = (new \DateTime('first day of this month'))->format(\DateTime::ATOM);

$sessions = \verbb\events\elements\Session::find()
    ->dateCreated(['and', ">= {$start}", "< {$end}"])
    ->all();
```
:::
:::

::: reference
### `dateUpdated`

Narrows the query results based on the sessions’ last-updated dates.

| Value | Fetches sessions… |
| --- | --- |
| `'>= 2026-04-01'` | that were updated on or after 2026-04-01. |
| `'< 2026-05-01'` | that were updated before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that were updated between 2026-04-01 and 2026-05-01. |

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
$lastWeek = (new \DateTime('1 week ago'))->format(\DateTime::ATOM);

$sessions = \verbb\events\elements\Session::find()
    ->dateUpdated(">= {$lastWeek}")
    ->all();
```
:::
:::

::: reference
### `endDate`

Narrows the query results based on the sessions’ end dates.

| Value | Fetches sessions… |
| --- | --- |
| `'>= 2026-04-01'` | that finish on or after 2026-04-01. |
| `'< 2026-05-01'` | that finish before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that finish between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch sessions that finish today or later #}
{% set today = date('today')|atom %}

{% set sessions = craft.events.sessions()
    .endDate(">= #{today}")
    .all() %}
```

```php PHP
// Fetch sessions that finish today or later
$today = (new \DateTime('today'))->format(\DateTime::ATOM);

$sessions = \verbb\events\elements\Session::find()
    ->endDate(">= {$today}")
    ->all();
```
:::
:::

::: reference
### `event`

Narrows the query results to sessions that belong to an event.

| Value | Fetches sessions… |
| --- | --- |
| an `Event` object | that belong to the event. |
| `1` | that belong to an event with an ID of 1. |

::: code
```twig Twig
{# Fetch sessions for an event #}
{% set sessions = craft.events.sessions()
    .event(event)
    .all() %}
```

```php PHP
// Fetch sessions for an event
$sessions = \verbb\events\elements\Session::find()
    ->event($event)
    ->all();
```
:::
:::

::: reference
### `eventId`

Narrows the query results based on the sessions’ events, per the events’ IDs.

| Value | Fetches sessions… |
| --- | --- |
| `1` | that belong to an event with an ID of 1. |
| `'not 1'` | not that belong to an event with an ID of 1. |
| `[1, 2]` | that belong to an event with an ID of 1 or 2. |
| `['not', 1, 2]` | not that belong to an event with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch sessions for the event with an ID of 1 #}
{% set sessions = craft.events.sessions()
    .eventId(1)
    .all() %}
```

```php PHP
// Fetch sessions for the event with an ID of 1
$sessions = \verbb\events\elements\Session::find()
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
:::

::: reference
### `groupUid`

Narrows the query results to sessions in a recurring group, per the group’s UID. Sessions generated from the same frequency share a group UID.

::: code
```twig Twig
{# Fetch the other sessions in the same recurring group #}
{% set sessions = craft.events.sessions()
    .groupUid(session.groupUid)
    .all() %}
```

```php PHP
// Fetch the other sessions in the same recurring group
$sessions = \verbb\events\elements\Session::find()
    ->groupUid($session->groupUid)
    ->all();
```
:::
:::

::: reference
### `hasEvent`

Narrows the query results to sessions whose event matches an event query, or an array of event query criteria.

| Value | Fetches sessions… |
| --- | --- |
| an `EventQuery` object | whose event matches the query. |
| an array of criteria | whose event matches the criteria. |

::: code
```twig Twig
{# Fetch sessions for live conference events #}
{% set sessions = craft.events.sessions()
    .hasEvent({ type: 'conference', status: 'live' })
    .all() %}
```

```php PHP
// Fetch sessions for live conference events
$sessions = \verbb\events\elements\Session::find()
    ->hasEvent(['type' => 'conference', 'status' => 'live'])
    ->all();
```
:::
:::

::: reference
### `id`

Narrows the query results based on the sessions’ IDs.

| Value | Fetches sessions… |
| --- | --- |
| `1` | with an ID of 1. |
| `'not 1'` | not with an ID of 1. |
| `[1, 2]` | with an ID of 1 or 2. |
| `['not', 1, 2]` | not with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch the session by its ID #}
{% set session = craft.events.sessions()
    .id(1)
    .one() %}
```

```php PHP
// Fetch the session by its ID
$session = \verbb\events\elements\Session::find()
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
:::

::: reference
### `isRecurring`

Narrows the query results based on whether the sessions were generated from a recurring frequency.

::: code
```twig Twig
{# Fetch recurring sessions #}
{% set sessions = craft.events.sessions()
    .isRecurring(true)
    .all() %}
```

```php PHP
// Fetch recurring sessions
$sessions = \verbb\events\elements\Session::find()
    ->isRecurring(true)
    ->all();
```
:::
:::

::: reference
### `limit`

Determines the number of sessions that should be returned.

::: code
```twig Twig
{# Fetch up to 10 sessions #}
{% set sessions = craft.events.sessions()
    .limit(10)
    .all() %}
```

```php PHP
// Fetch up to 10 sessions
$sessions = \verbb\events\elements\Session::find()
    ->limit(10)
    ->all();
```
:::
:::

::: reference
### `offset`

Determines how many sessions should be skipped in the results.

::: code
```twig Twig
{# Fetch all sessions except for the first 3 #}
{% set sessions = craft.events.sessions()
    .offset(3)
    .all() %}
```

```php PHP
// Fetch all sessions except for the first 3
$sessions = \verbb\events\elements\Session::find()
    ->offset(3)
    ->all();
```
:::
:::

::: reference
### `orderBy`

Determines the order that the sessions should be returned in. Sessions are ordered by the order they appear in on their event by default.

::: code
```twig Twig
{# Fetch all sessions in order of date created #}
{% set sessions = craft.events.sessions()
    .orderBy('elements.dateCreated asc')
    .all() %}
```

```php PHP
// Fetch all sessions in order of date created
$sessions = \verbb\events\elements\Session::find()
    ->orderBy('elements.dateCreated asc')
    ->all();
```
:::
:::

::: reference
### `owner`

Narrows the query results to sessions owned by an event, and sets the site to the event’s site. Use [event](#event) when you only need to match the event ID.

::: code
```twig Twig
{# Fetch sessions owned by an event #}
{% set sessions = craft.events.sessions()
    .owner(event)
    .all() %}
```

```php PHP
// Fetch sessions owned by an event
$sessions = \verbb\events\elements\Session::find()
    ->owner($event)
    ->all();
```
:::
:::

::: reference
### `ownerId`

Narrows the query results based on the IDs of the events that own the sessions.

| Value | Fetches sessions… |
| --- | --- |
| `1` | owned by an event with an ID of 1. |
| `'not 1'` | not owned by an event with an ID of 1. |
| `[1, 2]` | owned by an event with an ID of 1 or 2. |
| `['not', 1, 2]` | not owned by an event with an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch sessions owned by the event with an ID of 1 #}
{% set sessions = craft.events.sessions()
    .ownerId(1)
    .all() %}
```

```php PHP
// Fetch sessions owned by the event with an ID of 1
$sessions = \verbb\events\elements\Session::find()
    ->ownerId(1)
    ->all();
```
:::
:::

::: reference
### `primaryOwner`

Narrows the query results to sessions whose primary owner is an event, and sets the site to the event’s site. A session is only ever created on one event, which is its primary owner, though drafts and revisions of that event also own it.

::: code
```twig Twig
{# Fetch sessions created on an event #}
{% set sessions = craft.events.sessions()
    .primaryOwner(event)
    .all() %}
```

```php PHP
// Fetch sessions created on an event
$sessions = \verbb\events\elements\Session::find()
    ->primaryOwner($event)
    ->all();
```
:::
:::

::: reference
### `primaryOwnerId`

Narrows the query results based on the IDs of the sessions’ primary owners.

| Value | Fetches sessions… |
| --- | --- |
| `1` | whose primary owner has an ID of 1. |
| `'not 1'` | not whose primary owner has an ID of 1. |
| `[1, 2]` | whose primary owner has an ID of 1 or 2. |
| `['not', 1, 2]` | not whose primary owner has an ID of 1 or 2. |

::: code
```twig Twig
{# Fetch sessions created on the event with an ID of 1 #}
{% set sessions = craft.events.sessions()
    .primaryOwnerId(1)
    .all() %}
```

```php PHP
// Fetch sessions created on the event with an ID of 1
$sessions = \verbb\events\elements\Session::find()
    ->primaryOwnerId(1)
    ->all();
```
:::
:::

::: reference
### `relatedTo`

Narrows the query results to only sessions that are related to certain other elements. See [Relations](https://craftcms.com/docs/5.x/system/relations.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all sessions that are related to myCategory #}
{% set sessions = craft.events.sessions()
    .relatedTo(myCategory)
    .all() %}
```

```php PHP
// Fetch all sessions that are related to myCategory
$sessions = \verbb\events\elements\Session::find()
    ->relatedTo($myCategory)
    ->all();
```
:::
:::

::: reference
### `search`

Narrows the query results to only sessions that match a search query. See [Searching](https://craftcms.com/docs/5.x/system/searching.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch all sessions that match the search query #}
{% set searchQuery = craft.app.request.getQueryParam('q') %}

{% set sessions = craft.events.sessions()
    .search(searchQuery)
    .all() %}
```

```php PHP
// Fetch all sessions that match the search query
$searchQuery = \Craft::$app->getRequest()->getQueryParam('q');

$sessions = \verbb\events\elements\Session::find()
    ->search($searchQuery)
    ->all();
```
:::
:::

::: reference
### `site`

Determines which site the sessions should be queried in. The current site is used by default.

| Value | Fetches sessions… |
| --- | --- |
| `'foo'` | from the site with a handle of `foo`. |
| `['foo', 'bar']` | from a site with a handle of `foo` or `bar`. |
| `'*'` | from any site. |
| a `craft\models\Site` object | from the site represented by the object. |

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
:::

::: reference
### `siteId`

Determines which site the sessions should be queried in, per the site’s ID. The current site is used by default.

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
:::

::: reference
### `startDate`

Narrows the query results based on the sessions’ start dates.

| Value | Fetches sessions… |
| --- | --- |
| `'>= 2026-04-01'` | that start on or after 2026-04-01. |
| `'< 2026-05-01'` | that start before 2026-05-01. |
| `['and', '>= 2026-04-01', '< 2026-05-01']` | that start between 2026-04-01 and 2026-05-01. |

::: code
```twig Twig
{# Fetch sessions that start today or later #}
{% set today = date('today')|atom %}

{% set sessions = craft.events.sessions()
    .startDate(">= #{today}")
    .all() %}
```

```php PHP
// Fetch sessions that start today or later
$today = (new \DateTime('today'))->format(\DateTime::ATOM);

$sessions = \verbb\events\elements\Session::find()
    ->startDate(">= {$today}")
    ->all();
```
:::
:::

::: reference
### `status`

Narrows the query results based on the sessions’ statuses. Pass `null` to fetch sessions regardless of their status.

| Value | Fetches sessions… |
| --- | --- |
| `'enabled'` _(default)_ | that are enabled. |
| `'disabled'` | that are disabled. |
| `['enabled', 'disabled']` | that are enabled or disabled. |
| `null` | regardless of status. |

::: code
```twig Twig
{# Fetch disabled sessions #}
{% set sessions = craft.events.sessions()
    .status('disabled')
    .all() %}
```

```php PHP
// Fetch disabled sessions
$sessions = \verbb\events\elements\Session::find()
    ->status('disabled')
    ->all();
```
:::
:::

::: reference
### `title`

Narrows the query results based on the sessions’ titles.

| Value | Fetches sessions… |
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
:::

::: reference
### `uid`

Narrows the query results based on the sessions’ UIDs.

::: code
```twig Twig
{# Fetch the session by its UID #}
{% set session = craft.events.sessions()
    .uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    .one() %}
```

```php PHP
// Fetch the session by its UID
$session = \verbb\events\elements\Session::find()
    ->uid('xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx')
    ->one();
```
:::
:::

::: reference
### `with`

Causes the query to return matching sessions eager-loaded with related elements. See [Eager-Loading Elements](https://craftcms.com/docs/5.x/development/eager-loading.html) in the Craft docs for a full explanation of how to work with this parameter.

::: code
```twig Twig
{# Fetch sessions eager-loaded with the “Related” field’s relations #}
{% set sessions = craft.events.sessions()
    .with(['related'])
    .all() %}
```

```php PHP
// Fetch sessions eager-loaded with the “Related” field’s relations
$sessions = \verbb\events\elements\Session::find()
    ->with(['related'])
    ->all();
```
:::
:::
