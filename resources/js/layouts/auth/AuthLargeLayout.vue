<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const name = page.props.name;

defineProps({
    title: { type: String },
    description: { type: String },
});
</script>

<template>
    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 md:max-w-none md:grid-cols-8 md:px-0">
        <div class="bg-muted relative hidden h-full flex-col p-10 text-white md:flex dark:border-r">
            <div class="absolute inset-0 bg-zinc-900 bg-[url(/img/rule-zero.jpg)] bg-cover bg-center bg-no-repeat" />
            <Link :href="route('home')" class="relative z-20 mx-auto flex items-center text-lg font-medium">
                <img src="/img/logo.png" :alt="name" class="h-auto w-24 xl:w-36 shrink-0 fill-current text-white" />
            </Link>
        </div>
        <div class="md:col-span-7 md:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:max-w-7xl">
                <img src="/img/logo.png" :alt="page.props.name" class="mx-auto mb-6 size-24 md:mb-12 md:hidden" />
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-3xl font-medium tracking-tight" v-if="title">{{ title }}</h1>
                    <p class="text-muted-foreground text-sm text-pretty max-w-3xl mx-auto" v-if="description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
