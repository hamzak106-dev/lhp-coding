/**
 * Formats an event's start time. Events are global, so we always render the
 * time in the event's own timezone and — when the viewer is elsewhere — also in
 * the viewer's local timezone, so nobody has to do the math themselves.
 */

const viewerTimezone =
    typeof Intl !== 'undefined'
        ? Intl.DateTimeFormat().resolvedOptions().timeZone
        : 'UTC';

export interface FormattedEventTime {
    /** e.g. "Thu, Jun 25, 2026, 8:00 PM EDT" (event timezone). */
    eventLabel: string;
    /** Same instant in the viewer's timezone, or null if it matches. */
    localLabel: string | null;
    /** Short day-of-month badge parts for cards. */
    day: string;
    month: string;
    /** Full ISO date (yyyy-mm-dd) in the event timezone, for grouping. */
    isoDate: string;
}

function format(iso: string, timeZone: string): string {
    return new Intl.DateTimeFormat(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        timeZoneName: 'short',
        timeZone,
    }).format(new Date(iso));
}

export function formatEventTime(
    startIso: string | null,
    eventTimezone: string,
): FormattedEventTime {
    if (!startIso) {
        return {
            eventLabel: 'Date to be announced',
            localLabel: null,
            day: '—',
            month: '',
            isoDate: '',
        };
    }

    const date = new Date(startIso);
    const sameZone = viewerTimezone === eventTimezone;

    const dayParts = new Intl.DateTimeFormat('en-US', {
        day: 'numeric',
        month: 'short',
        timeZone: eventTimezone,
    }).formatToParts(date);

    return {
        eventLabel: format(startIso, eventTimezone),
        localLabel: sameZone ? null : format(startIso, viewerTimezone),
        day: dayParts.find((p) => p.type === 'day')?.value ?? '',
        month: dayParts.find((p) => p.type === 'month')?.value ?? '',
        isoDate: new Intl.DateTimeFormat('en-CA', {
            timeZone: eventTimezone,
        }).format(date),
    };
}

export function formatPrice(price: number, currency: string): string {
    if (!price || price <= 0) {
        return 'Free';
    }

    try {
        return new Intl.NumberFormat(undefined, {
            style: 'currency',
            currency,
            maximumFractionDigits: 0,
        }).format(price);
    } catch {
        return `${currency} ${price.toFixed(0)}`;
    }
}

export { viewerTimezone };
