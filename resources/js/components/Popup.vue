<script setup lang="ts">
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { usePopup } from '@/composables/usePopup';
import type { PopupSize } from '@/composables/usePopup';

const { state, close } = usePopup();

const sizeClasses: Record<PopupSize, string> = {
    sm: 'sm:max-w-sm',
    md: 'sm:max-w-lg',
    lg: 'sm:max-w-2xl',
    xl: 'sm:max-w-4xl',
};

const onOpenChange = (open: boolean): void => {
    if (!open) {
        close();
    }
};
</script>

<template>
    <Dialog :open="state.open" @update:open="onOpenChange">
        <DialogContent
            :class="['max-h-[90vh] overflow-y-auto', sizeClasses[state.size]]"
            v-bind="state.description ? {} : { 'aria-describedby': undefined }"
            @interact-outside.prevent
        >
            <DialogHeader>
                <DialogTitle>{{ state.title }}</DialogTitle>
                <DialogDescription v-if="state.description">
                    {{ state.description }}
                </DialogDescription>
            </DialogHeader>

            <component
                :is="state.component"
                v-if="state.component"
                v-bind="state.props"
                @close="close"
            />
        </DialogContent>
    </Dialog>
</template>
