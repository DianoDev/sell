import { markRaw, reactive } from 'vue';
import type { Component } from 'vue';

export type PopupSize = 'sm' | 'md' | 'lg' | 'xl';

export type PopupOptions = {
    component: Component;
    title: string;
    description?: string;
    size?: PopupSize;
    props?: Record<string, unknown>;
};

type PopupState = {
    open: boolean;
    component: Component | null;
    title: string;
    description: string;
    size: PopupSize;
    props: Record<string, unknown>;
};

export type UsePopupReturn = {
    state: PopupState;
    open: (options: PopupOptions) => void;
    close: () => void;
};

/** Single shared popup: `<Popup />` is mounted once in the app layouts. */
const state = reactive<PopupState>({
    open: false,
    component: null,
    title: '',
    description: '',
    size: 'lg',
    props: {},
});

function open(options: PopupOptions): void {
    state.component = markRaw(options.component);
    state.title = options.title;
    state.description = options.description ?? '';
    state.size = options.size ?? 'lg';
    state.props = options.props ?? {};
    state.open = true;
}

function close(): void {
    state.open = false;
}

export function usePopup(): UsePopupReturn {
    return { state, open, close };
}
