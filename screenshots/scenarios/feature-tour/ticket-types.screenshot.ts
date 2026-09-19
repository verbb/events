import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedEventsFixture } from '../../support/fixtures';

let eventRoute = '/admin/events/events/festival';

export default defineScreenshotScenario({
    id: 'events-feature-tour-ticket-types',
    output: 'feature-tour/ticket-types.png',
    route: () => eventRoute,
    viewport: { width: 1440, height: 1100, deviceScaleFactor: 2 },
    async setup(context) {
        eventRoute = (await seedEventsFixture(context)).eventRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Melbourne Design Weekend' },
        { type: 'text', text: 'Ticket Types' },
        { type: 'text', text: 'Weekend pass' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                (() => {
                    document.activeElement?.blur();
                    const heading = [...document.querySelectorAll('label, h2, h3, legend')]
                        .find((element) => element.textContent?.trim() === 'Ticket Types');
                    const field = heading?.closest('.field') ?? heading?.parentElement;
                    if (!field) {
                        throw new Error('Unable to locate the Ticket Types field.');
                    }
                    field.id = 'events-ticket-types-screenshot';
                    field.scrollIntoView({ block: 'center' });
                })();
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 300 } },
    ],
    target: {
        type: 'selector',
        selector: '#events-ticket-types-screenshot',
        padding: { top: 16, right: 0, bottom: 16, left: 0 },
    },
    caption: 'Reusable ticket types with their capacity and price inside the current Craft 5 event editor.',
    intent: 'Show the genuine Events ticket-type manager and its relationship to event capacity.',
});
