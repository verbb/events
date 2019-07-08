# PDF Template

To generate a PDF ticket you need to create a extra template. You can use simple HTML tags, CSS styling and even Twig tags.

### Display Tickets

You can create PDFs for your tickets, using a template that you have total control over.

Start by ensuring you've setup the **Tickets PDF Template** under the [General Settings](docs:get-started/installation-setup) for Events.

Also check out [Configuration](docs:get-started/configuration) for more options to adjust your PDF.

### Create a link for customers to download

Next, you'll want to produce an actual link for your customer to download their PDF tickets. In most cases, this will be on the order summary page, an email, or both.

For example, on our order summary template (`shop/customer/order.html`), we have the following code looping through line items for the order. You can use the following single-line Twig variable:

Displaying a list of all purchased tickets for an order just use:

```twig
{% for item in order.lineItems %}
    <a href="{{ craft.events.getPdfUrl(item) }}">Download Ticket</a>
{% endfor %}
```

This will output an anchor tag with a link to the PDF ticket. You can also render all tickets in a single PDF, as opposed to each line item as a separate PDF. Simply amend the template call to:

```twig
<a href="{{ craft.events.getOrderPdfUrl(order) }}">Download All Tickets<a>
```

In both cases, the URL will look something similar to:

`https://mysite.local/actions/events/downloads/pdf?number=ba018a32b43cfef51c031f61ec4d2c48&lineItemId=12`

This URL will be using the template you have defined under the Events plugin settings.

### Additional parameters

You may find the additional parameters useful, especially during testing and development of these templates. Simply use one of the following values to append to the URL produced above.

- `&attach=false` - Add this to not force the PDF to download. Instead, it'll be rendered inside the browser window. This will still render as a PDF and is useful for debugging layout issues.
- `&format=plain` - Produces the same template as HTML, as opposed to PDF. Again, useful for debugging layout issues, or quickly prototyping layouts.

### Template variables

In the template itself, you'll have access to the following Twig variables:

#### order

The parent order that this ticket was purchased from

#### tickets

A collection of [Purchased Tickets](docs:developers/purchased-ticket). Depending on if you are using the functionality to show all purchased tickets for the order, or just per line item will dictate the amount of models in this collection.

### QR Code

Rather than a standard URL, you can produce a QR code:

```twig
<img src="{{ ticket.qrCode }}" />
```

### Example template

Below we've prepared a ready-to-go template, complete with provided CSS to get you started quickly.

```twig
<html>
<head>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<style>

    html {
        margin-top:0.2in !important;
        margin-left:0.2in !important;
    }

    body {
        font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
        font-size: 13px;
        line-height:1.4em;
        font-weight:bold;
    }

    .ticket {
        width:8in;
        height:2.7in;
        background-size:cover;
        background-repeat:no-repeat;
        position:relative;
        margin-bottom: 0.2in;
    }

    .ticket-img {
        max-width: 100%;
        height: auto;
        position: relative;
        /*display: inline-block;*/
    }

    #event-info {
        display:inline-block;
        position:absolute;
        left:0.9in;
        top:0.12in;
        width:4.7in;
    }

    .label {
        color:#768690;
        display:block;
        text-transform:uppercase;
    }

    .value {
        display:block;
        color:#121212;
        text-transform:uppercase;
        overflow:hidden;
        font-size:16px;
    }

    #title {
        height:0.4in;
    }

    #location {
        height:0.8in;
    }

    #stub-info {
        display:block;
        position:absolute;
        top:0.06in;
        left:6in;
        width:1.9in;
        text-align:center;
    }

    #purchased-on {
        display:inline-block;
        color:#fff;
        text-transform:uppercase;
        font-size:9px;
        text-align:center;
        width:100%;
        position:relative;
    }

    #qrcode {
        position:relative;
        width: 70%;
        height: auto;
        margin-top: 0.3in;
        margin-left: -1.9in;
    }

    #ticket-num {
        display:block;
        text-transform:uppercase;
        text-align:center;
        width:100%;
        position:relative;
        top: 0;
        left: 0;
        font-weight:bold;
        font-size: 12px;
    }

    #attendee-info {
        text-align:left;
        font-size:10px;
        position:relative;
        top:0.18in;
        line-height: 1.6em;
    }

    #attendee-info .value {
        font-size:10px;
    }

    {# Annoying alignedment issues when rending in PDF #}
    {% if craft.app.request.getParam('format') == 'plain' %}

        #purchased-on {
            display: block;
        }

        #qrcode {
            display: inline;
            margin: -0.1in 0 0 0;
        }

    {% endif %}
</style>
</head>

<body>
{% for ticket in tickets %}
    <div class="ticket">
        <img class="ticket-img" src="https://verbb.io/uploads/plugins/events/ticket-trans-notext.jpg" />

        <div id="event-info">
            <span class="label">EVENT</span>
            <span id="title" class="value">{{ ticket.event.title }}</span>

            <span class="label">DATE AND TIME</span>
            <span class="value">{{ ticket.event.startDate | date("M j, Y \\a\\t g:i A") }}</span>

            <span class="label">to</span>
            <span class="value">{{ ticket.event.endDate | date("M j, Y \\a\\t g:i A") }}</span>
        </div>

        <div id="stub-info">
            <span id="purchased-on">Purchased on {{ order.dateOrdered | date("M j, Y \\a\\t g:i A") }}</span>

            {# Display QR Code #}
            <img id="qrcode" src="{{ ticket.qrCode }}" />
            <span id="ticket-num" class="value">#{{ ticket }}</span>

            <div id="attendee-info">
                <span class="label">1 {{ ticket.ticketName }} Pass</span>

                {# Order details #}
                {% if order.customer.user %}
                    <span id="name" class="value">{{ order.customer.user.name }}</span>
                {% else %}
                    {% set address = ticket.getOrder().customer.addresses[0] %}
                    <span id="name" class="value">{{ address.firstName }} {{ address.lastName }}</span>
                {% endif %}
                <span id="email" class="value">{{ order.customer.email }}</span>
            </div>
        </div>
    </div>
{% endfor %}
</body>
</html>
```

The above will produce a design similar to the below, which we of course encourage you to change to your needs!

![Ticket Demo](/docs/screenshots/ticket-demo.png)