<script setup lang="ts">
import type { Component } from 'vue';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
import { usePopup } from '@/composables/usePopup';
import type { PopupSize } from '@/composables/usePopup';

type Props = {
    component: Component;
    title: string;
    description?: string;
    size?: PopupSize;
    componentProps?: Record<string, unknown>;
    variant?: ButtonVariants['variant'];
};

const props = withDefaults(defineProps<Props>(), {
    description: undefined,
    size: 'lg',
    componentProps: () => ({}),
    variant: 'default',
});

const popup = usePopup();

const openPopup = (): void => {
    popup.open({
        component: props.component,
        title: props.title,
        description: props.description,
        size: props.size,
        props: props.componentProps,
    });
};
</script>

<template>
    <Button type="button" :variant="variant" @click="openPopup">
        <slot />
    </Button>
</template>
