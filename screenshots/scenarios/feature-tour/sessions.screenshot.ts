import { defineScreenshotScenario } from '@verbb/craft-screenshots/api';

import { seedEventsFixture } from '../../support/fixtures';

let eventRoute = '/admin/events/events/festival';

export default defineScreenshotScenario({
    id: 'events-feature-tour-sessions',
    output: 'feature-tour/sessions.png',
    route: () => eventRoute,
    viewport: { width: 1440, height: 1000, deviceScaleFactor: 2 },
    async setup(context) {
        eventRoute = (await seedEventsFixture(context)).eventRoute;
    },
    waitFor: [
        { type: 'loadState', state: 'networkidle' },
        { type: 'text', text: 'Melbourne Design Weekend' },
        { type: 'text', text: 'Sessions' },
    ],
    steps: [
        {
            type: 'evaluate',
            expression: `
                (() => {
                    document.activeElement?.blur();
                    const heading = [...document.querySelectorAll('label, h2, h3, legend')]
                        .find((element) => element.textContent?.trim() === 'Sessions');
                    const field = heading?.closest('.field') ?? heading?.parentElement;
                    if (!field) {
                        throw new Error('Unable to locate the Sessions field.');
                    }
                    field.id = 'events-sessions-screenshot';
                    field.scrollIntoView({ block: 'center' });
                })();
            `,
        },
        { type: 'wait', waitFor: { type: 'timeout', ms: 300 } },
    ],
    target: {
        type: 'selector',
        selector: '#events-sessions-screenshot',
        padding: { top: 16, right: 0, bottom: 16, left: 0 },
    },
    caption: 'A current Craft 5 event with three separately scheduled sessions.',
    intent: 'Show the genuine Events session manager in the current event editor.',
});
