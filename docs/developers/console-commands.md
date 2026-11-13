# Console Commands

Events includes console commands you can run from your Craft project’s directory.

## Purchased Tickets

::: reference
### `events/purchased-tickets/purge-trashed`

Permanently deletes purchased tickets that are in the trash. Purchased tickets stay in the trash until Craft’s garbage collection removes them, or until the [purchasedTicketTrashRetentionDays](docs:get-started/configuration#purchasedtickettrashretentiondays) setting purges them, so run this when you need to clear them sooner. The command is a dry run by default, listing how many would be deleted.

| Option | Description | Default |
| --- | --- | --- |
| `--dry-run` | Whether to only report what would be deleted. Set to `0` to delete the purchased tickets. | `1` |
| `--event-id` | Only purge purchased tickets for the event with this ID. | |
| `--older-than` | Only purge purchased tickets that were trashed at least this many days ago. | |

```shell
# See how many trashed purchased tickets would be deleted
php craft events/purchased-tickets/purge-trashed

# Delete trashed purchased tickets for one event, trashed over 30 days ago
php craft events/purchased-tickets/purge-trashed --event-id=123 --older-than=30 --dry-run=0
```
:::

## Tickets

::: reference
### `events/tickets/cleanup-orphaned`

Finds tickets whose event, session or ticket type no longer exists or is in the trash, and moves them to the trash. Tickets that still have purchased tickets are skipped, so attendee records keep their ticket. The command is a dry run by default, listing the tickets it would trash.

| Option | Description | Default |
| --- | --- | --- |
| `--dry-run` | Whether to only list the orphaned tickets. Set to `0` to move them to the trash. | `1` |

```shell
# List orphaned tickets
php craft events/tickets/cleanup-orphaned

# Move orphaned tickets without purchases to the trash
php craft events/tickets/cleanup-orphaned --dry-run=0
```
:::

## Resaving Elements

Events adds its elements to Craft’s `resave` command. Each accepts Craft’s usual [resave options](https://craftcms.com/docs/5.x/reference/cli.html#resave), such as `--element-id`, `--site` and `--queue`.

::: reference
### `resave/events-events`

Re-saves Event elements, such as after changing an event type’s URI format or field layout.

```shell
php craft resave/events-events
```
:::

::: reference
### `resave/events-sessions`

Re-saves sessions.

```shell
php craft resave/events-sessions
```
:::

::: reference
### `resave/events-ticket-types`

Re-saves ticket types.

```shell
php craft resave/events-ticket-types
```
:::

::: reference
### `resave/events-tickets`

Re-saves tickets, such as after changing the ticket SKU format.

```shell
php craft resave/events-tickets
```
:::

::: reference
### `resave/events-purchased-tickets`

Re-saves purchased tickets, such as after changing the purchased ticket title format.

```shell
php craft resave/events-purchased-tickets
```
:::

## Tests

::: reference
### `events/tests`

Runs Events’ built-in checks of the dates that recurring sessions generate, for each frequency type. It reports each check and exits with an error code when one fails. It’s mainly useful when reporting a recurring session problem to us.

```shell
php craft events/tests
```
:::

::: reference
### `events/tests/check-in-response`

Runs Events’ built-in check that the check-in response only includes the details a check-in screen should show. It exits with an error code when the check fails.

```shell
php craft events/tests/check-in-response
```
:::
