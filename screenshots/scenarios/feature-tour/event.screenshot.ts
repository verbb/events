import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedEventsFixture } from '../../support/fixtures';

let eventRoute = '/admin/events/events/festival';

export default defineScreenshotScenario({
    id: 'events-feature-tour-event',
    output: 'feature-tour/event.png',
    route: () => eventRoute,
    viewport: { width: 1440, height: 1500, deviceScaleFactor: 2 },
    async setup(context) {
        eventRoute = (await seedEventsFixture(context)).eventRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Melbourne Design Weekend' },
        { type: 'text', text: 'Sessions' },
        { type: 'text', text: 'Weekend pass' },
    ],
    steps: [
        { type: 'wait', waitFor: { type: 'timeout', ms: 300 } },
        {
            type: 'evaluate',
            expression: `
                document.activeElement?.blur();
                window.scrollTo(0, 0);
                document.querySelectorAll('html, body, body *').forEach((element) => {
                    element.scrollTop = 0;
                    element.scrollLeft = 0;
                });

                document.querySelector('#header').style.paddingTop = '24px';
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 50 } },
    ],
    target: {
        type: 'anchoredClip',
        selector: '#header',
        x: 0,
        y: 0,
        width: 1200,
        height: 946,
    },
    caption: 'A current Craft 5 event with scheduled sessions and ticket types ready for sale.',
    intent: 'Show the complete modern event-editing context and how sessions and ticket types belong to one real event.',
});
