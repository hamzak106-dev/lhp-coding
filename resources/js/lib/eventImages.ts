export function eventImageUrl(url: string | null): string | null {
    if (!url) {
        return null;
    }

    const storageWrappedRemote = url.match(/^\/?storage\/(https?:\/\/.+)$/);

    if (storageWrappedRemote) {
        return storageWrappedRemote[1];
    }

    try {
        const parsed = new URL(url, window.location.origin);

        const parsedStorageWrappedRemote = parsed.pathname.match(
            /^\/storage\/(https?:\/\/.+)$/,
        );

        if (parsedStorageWrappedRemote) {
            return `${parsedStorageWrappedRemote[1]}${parsed.search}${parsed.hash}`;
        }

        if (parsed.pathname.startsWith('/storage/events/')) {
            return `/event-images/events/${parsed.pathname.replace('/storage/events/', '')}`;
        }

        if (parsed.origin !== window.location.origin) {
            return parsed.href;
        }

        return `${parsed.pathname}${parsed.search}${parsed.hash}`;
    } catch {
        return url;
    }
}
