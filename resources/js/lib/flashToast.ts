import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import type { FlashToast } from '@/types/ui';

/**
 * Surfaces server-side flash toasts (shared as `flash.toast` from
 * HandleInertiaRequests) after every successful Inertia visit.
 */
export function initializeFlashToast(): void {
    router.on('success', (event) => {
        const page = (event as CustomEvent).detail?.page;
        const data = (page?.props?.flash as { toast?: FlashToast } | undefined)
            ?.toast;

        if (!data) {
            return;
        }

        toast[data.type](data.message);
    });
}
