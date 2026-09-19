import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedEventsFixture } from '../../support/fixtures';

let ticketRoute = '/events-screenshot-ticket';

export default defineScreenshotScenario({
    id: 'events-feature-tour-pdf-ticket',
    output: 'feature-tour/pdf-ticket.png',
    route: () => ticketRoute,
    viewport: { width: 1320, height: 600, deviceScaleFactor: 2 },
    async setup(context) {
        ticketRoute = (await seedEventsFixture(context)).ticketRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'selector', selector: '.ticket', state: 'visible' },
        { type: 'text', text: 'Melbourne Design Weekend' },
    ],
    target: {
        type: 'selector',
        selector: '.ticket',
        padding: { top: 0, right: 0, bottom: 2, left: 0 },
    },
    caption: 'A printable event ticket with current dates and an inert demonstration QR code.',
    intent: 'Show the kind of branded PDF ticket Events can render without exposing a usable check-in URL.',
});
