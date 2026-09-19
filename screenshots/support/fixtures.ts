import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

import type { ScreenshotSetupContext } from '@verbb/craft-screenshots/types';

type EventsFixture = {
    eventRoute: string;
    ticketRoute: string;
    sessionCount: number;
    ticketTypeCount: number;
};

const supportDir = dirname(fileURLToPath(import.meta.url));
const seedScript = readFileSync(join(supportDir, 'seed', 'seed-events.php'), 'utf8');

/** Seed one current event with sessions, ticket types and an illustrative ticket template. */
export async function seedEventsFixture(context: ScreenshotSetupContext): Promise<EventsFixture> {
    const output = await context.runCraftScript(seedScript, { label: 'seed-events' });
    const fixture = JSON.parse(output.trim()) as EventsFixture;

    if (!fixture.eventRoute || !fixture.ticketRoute || fixture.sessionCount < 3 || fixture.ticketTypeCount < 2) {
        throw new Error(`Invalid Events fixture payload: ${output}`);
    }

    return fixture;
}
