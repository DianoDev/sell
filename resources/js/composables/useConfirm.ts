import { reactive } from 'vue';

export type ConfirmOptions = {
    title?: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    destructive?: boolean;
};

type ConfirmState = Required<ConfirmOptions> & {
    open: boolean;
};

export type UseConfirmReturn = {
    state: ConfirmState;
    confirm: (options?: ConfirmOptions) => Promise<boolean>;
    answer: (confirmed: boolean) => void;
};

const defaults: Required<ConfirmOptions> = {
    title: 'Confirmação',
    message: 'Você tem certeza?',
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
    destructive: false,
};

/** Single shared confirmation: `<ConfirmationPopup />` is mounted once in the app layouts. */
const state = reactive<ConfirmState>({ open: false, ...defaults });

let resolvePending: ((confirmed: boolean) => void) | null = null;

/** Resolves to `true` only when the user confirms. */
function confirm(options: ConfirmOptions = {}): Promise<boolean> {
    resolvePending?.(false);
    Object.assign(state, defaults, options, { open: true });

    return new Promise((resolve) => {
        resolvePending = resolve;
    });
}

function answer(confirmed: boolean): void {
    state.open = false;
    resolvePending?.(confirmed);
    resolvePending = null;
}

export function useConfirm(): UseConfirmReturn {
    return { state, confirm, answer };
}
