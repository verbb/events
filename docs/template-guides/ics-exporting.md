# ICS Exporting

Events has support for ICS files for single events, entire event types, or for [Event Queries](docs:getting-elements/event-queries). It can also generate the ICS content in its raw fashion, allowing linking directly to the feed, or complete customisation in templates.

For each Event Type, you can define some additional properties for the ICS feeds:

**Forced ICS Event Timezone**
Choose a timezone that will be forced upon exported ICS event dates. All events will be forced to this provided timezone.

**ICS Description Field**
Set the field to be used for event description when exporting ICS file.

**ICS Location Field**
Set the field to be used for event location when exporting ICS file.

## Single Event
Use `{{ event.getIcsUrl() }}` to generate the URL for a single event, as an ICS file. Use this URL to either download ICS files on-demand, or subscribing to them. See [Event](docs:developers/event).

## Event Types
You can also generate an ICS file for all events in an event type.

```twig
{% set eventType = craft.events.getEventTypeByHandle('myEvents') %}

{{ eventType.getIcsUrl() }}
```

## Event Queries
You can also generate an ICS feed for arbitrary [Event Queries](docs:getting-elements/event-queries). This directly outputs the content of the ICS data on the page, so you'll likely want to create a separate template just for this content.

For example, you might have the following in a `templates/events/export.html` template file:

```twig
{% set events = craft.events.events({
    type: 'general',
}).all() %}

{{ craft.events.getIcsFeed(events) }}
```

Which would output the raw ICS data. You could also change this to force downloading of the ICS file, by adding some PHP headers with Twig:

```twig
{% set events = craft.events.events({
    type: 'general',
}).all() %}

{% header "Content-Type: application/octet-stream" %}
{% header "Content-Transfer-Encoding: binary" %}
{% header "Content-Disposition: attachment; filename=calendar.ics" %}

{{ craft.events.getIcsFeed(events) }}
```