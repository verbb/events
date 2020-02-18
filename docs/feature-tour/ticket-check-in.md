# Ticket Check In

Once tickets have been purchased and delivered to the customer, you'll likely need a way to validate their ticket purchase at the venue on the day of the event. This is where the "Check In" functionality comes in handy.

Events provides a simple controller action endpoint for you to trigger. This will also check if a customer has already checked in, preventing ticket re-use.

Additionally, if producing a PDF template using our example, a QR code will be generated. This code is the full URL to this action endpoint.

Simply trigger the following action URL in your templates:

```
actions/events/ticket/checkin?sku=<sku>
```

### Parameter

- `<sku>`: This is the ticket SKU which gets generated automatically at the purchase of the ticket. This SKU is unique.

### Return

The controller returns a JSON response. On an **error** the response contains a simple error message. On **success** the response contains following:

- `success`: Contains the string "Ticket checked in.".
- `checkedInDate`: The check in date in [DATE\_ATOM](http://php.net/manual/en/class.datetime.php#datetime.constants.atom) format.

### Example Form

There are a number of ways you could setup this mechanism, but commonly you could setup a simple form on your website to allow staff at the door to check a customer in. Something similar to the below:

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