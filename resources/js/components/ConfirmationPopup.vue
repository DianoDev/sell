<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useConfirm } from '@/composables/useConfirm';

const { state, answer } = useConfirm();

const onOpenChange = (open: boolean): void => {
    if (!open) {
        answer(false);
    }
};
</script>

<template>
    <Dialog :open="state.open" @update:open="onOpenChange">
        <DialogContent class="sm:max-w-md" @interact-outside.prevent>
            <DialogHeader>
                <DialogTitle>{{ state.title }}</DialogTitle>
                <!-- Interpolated on purpose: the message may carry user-typed text. -->
                <DialogDescription>{{ state.message }}</DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="answer(false)">
                    {{ state.cancelText }}
                </Button>
                <Button
                    :variant="state.destructive ? 'destructive' : 'default'"
                    data-test="confirmation-confirm-button"
                    @click="answer(true)"
                >
                    {{ state.confirmText }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
