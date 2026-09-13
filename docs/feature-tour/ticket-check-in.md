# Ticket Check-In

Check-in records that a Purchased Ticket has been used at the event. Use the ticket's check-in URL, including the QR code in a PDF ticket, to open its confirmation screen. Confirming the action marks the ticket as checked in; opening the URL alone does not do so.

When `checkinLogin` is enabled, the person checking tickets needs the **Check in tickets** permission. Test with the staff account that will operate the scanner or check-in page. A cancelled ticket or a ticket already checked in produces an error.

## Open a Ticket

In a template where `purchasedTicket` is the [Purchased Ticket](docs:developers/purchased-ticket) being checked, use its URL helper:

```twig
<a href="{{ purchasedTicket.getCheckInUrl() }}">Check In This Ticket</a>
```

The helper builds the `events/tickets/check-in` action URL and includes the ticket's `uid`. Use it instead of hard-coding your installation's action prefix. Review the displayed ticket before confirming.

<span id="parameter"></span>

## Request Parameters

`uid` identifies the Purchased Ticket. `confirm` must be truthy to perform the check-in; without it, the action returns the ticket for confirmation. For a custom confirmation form, submit both values:

```twig
<form method="post">
    {{ csrfInput() }}
    {{ actionInput('events/tickets/check-in') }}
    {{ hiddenInput('uid', purchasedTicket.uid) }}
    {{ hiddenInput('confirm', 1) }}
    <button type="submit">Confirm Check-In</button>
</form>
```

Place this only in a context where the intended purchased ticket has been selected. Do not use the generated Ticket's identifier: check-in applies to a particular purchased admission.

<span id="return"></span>

## Response

A request accepting JSON receives the response values as JSON. Otherwise, the controller renders the check-in template. An error response contains `error`. A confirmation response contains `purchasedTicket`; after the confirmation action runs, the response also contains `success: true`.

Inspect the ticket's `checkedIn` state and `checkedInDate` after confirmation. If you customise the PHP check-in hooks, test their result too rather than treating an HTTP success response alone as proof of a state change.

## Custom Template

Set [checkinTemplate](docs:get-started/configuration#checkintemplate) to a site template when you want to customise the confirmation screen. For example:

```twig
{% if error is defined %}
    <p role="alert">{{ error }}</p>
{% elseif success is defined and success %}
    <p>Ticket checked in.</p>
{% elseif purchasedTicket is defined %}
    <p>Confirm check-in for this ticket.</p>
    <form method="post">
        {{ csrfInput() }}
        {{ actionInput('events/tickets/check-in') }}
        {{ hiddenInput('uid', purchasedTicket.uid) }}
        {{ hiddenInput('confirm', 1) }}
        <button type="submit">Confirm Check-In</button>
    </form>
{% endif %}
```

Use a test purchase to open the page, confirm its check-in and inspect the saved ticket in the control panel. Open the same URL again and confirm that it reports the ticket is already checked in.

<span id="example-check-in-form"></span>
