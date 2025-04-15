<script setup>
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';

defineProps({
    breadcrumbs: { default: () => [] },
    contentBackgroundImage: {
        type: Boolean,
        default: false,
    },
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" :contentBackgroundImage="contentBackgroundImage">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" :class="[!breadcrumbs || (breadcrumbs.length < 1 && 'md:hidden')]" />
            <slot />
        </AppContent>
    </AppShell>
</template>
