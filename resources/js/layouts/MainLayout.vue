<script setup>
import AppLayout from '@/layouts/app/AppSidebarLayout.vue';

defineProps({
    breadcrumbs: { default: () => [] },
    contentBackgroundImage: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs" :contentBackgroundImage="contentBackgroundImage">

        <slot />
    </AppLayout>
</template>

<style scoped></style>
