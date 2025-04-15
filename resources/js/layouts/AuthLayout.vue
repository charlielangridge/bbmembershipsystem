<script setup>
import AuthLargeLayout from '@/layouts/auth/AuthLargeLayout.vue';
import AuthLayout from '@/layouts/auth/AuthSplitLayout.vue';

defineProps({
    title: {
        type: String,
        required: false,
    },
    description: {
        type: String,
        required: false,
    },
    fullLayout: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <AuthLargeLayout v-if="fullLayout" :title="title" :description="description">
        <slot />
    </AuthLargeLayout>
    <AuthLayout :title="title" :description="description" v-else>
        <slot />
    </AuthLayout>
</template>
