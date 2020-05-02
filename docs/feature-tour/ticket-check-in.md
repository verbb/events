# Ticket Check In

Once tickets have been purchased and delivered to the customer, you'll likely need a way to validate their ticket purchase at the venue on the day of the event. This is where the "Check In" functionality comes in handy.

Events provides a simple controller action endpoint for you to trigger. This will also check if a customer has already checked in, preventing ticket re-use.

Additionally, if producing a PDF template using our example, a QR code will be generated. This code is the full URL to this action endpoint.

Visit the following action URL in your templates:

```
actions/events/ticket/checkin?sku=<sku>
```

### Parameter

- `<sku>`: This is the ticket SKU which gets generated automatically at the purchase of the ticket. This SKU is unique.

### Return

The controller will render a simple template with either an error, or a success message. An **error** the response contains a simple error message. On **success** the response contains following:

If making the request via Ajax, a JSON response will be returned.

The returned response (JSON or template) will provide the following variables:

- `success`: Contains the string "Ticket checked in.".
- `checkedInDate`: The check in date in [DATE\_ATOM](http://php.net/manual/en/class.datetime.php#datetime.constants.atom) format.
- `purchasedTicket`: If successful, a [Purchased Ticket](docs:developers/purchased-ticket).

### Custom Template

The template returned by this controller can be changed to a custom one of your choosing. Use ths [checkinTemplate](https://verbb.io/craft-plugins/events/docs/get-started/configuration) config variable. In this instance, you could provide a template to show the appropriate response in a way that suits your site.

An example of this template could be the following:

```twig
<html>
<head>
    <link href="https://unpkg.com/tailwindcss@^1.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="p-4">

{% if not success %}
    <div role="alert" class="border px-4 py-3 rounded bg-red-100 border-red-400 text-red-700">
        {{ message }}
    </div>
{% else %}
    <div role="alert" class="border px-4 py-3 rounded bg-green-100 border-green-400 text-green-500">
        Success! Checked in at {{ purchasedTicket.checkedInDate | date('short') }}
    </div>
{% endif %}

</body>
</html>
```

### Example Checkin Form

In addition to the QR code in PDF tickets, you can also setup a form on your site to check a ticket in.

```twig
{# Show an error if one exists #}
{% if message is defined %}
    {{ message }}
{% endif %}

{# Show the check-in was successful #}
{% if success is defined and success %}
    Success!
{% endif %}

<form method="post" accept-charset="UTF-8">
    <input type="hidden" name="action" value="events/ticket/checkin">
    {{ csrfInput() }}
    
    <input type="text" name="sku">
    <input type="submit" value="Check in to event">
</form>
```