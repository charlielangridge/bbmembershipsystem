<script setup>
import { Button } from '@/components/ui/button';
import MainLayout from '@/layouts/MainLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs = ref([]);
</script>

<template>
    <Head title="Welcome" />

    <MainLayout :breadcrumbs="breadcrumbs" contentBackgroundImage>
        <div class="h-full flex items-center justify-center p-6">
            <div class="rounded-lg bg-white p-7 text-center text-lg shadow-md max-w-4xl">
                <img src="/img/logo.png" alt="Build Brighton Logo" class="mx-auto mb-8 size-32" />
                <!--                <h1 class="mb-4 text-4xl text-gray-800">Build Brighton</h1>-->
                <p class="mb-4 text-xl text-gray-800">Welcome to the Build Brighton Member System</p>
                <p class="mb-2 text-gray-800">
                    You can use this site to sign up to Build Brighton as well as managing your subscription and various other aspects of your
                    membership.
                </p>
                <Link :href="route('register')">
                    <Button class="my-4">Become a member</Button>
                </Link>
                <p class="text-md mt-2 text-gray-800">
                    For more information on Build Brighton please visit
                    <a href="http://www.buildbrighton.com" class="text-indigo-600 hover:underline">www.buildbrighton.com</a>
                </p>
                <p class="text-md mt-2 text-gray-800">
                    Already part of Build Brighton? <Link :href="route('login')" class="text-indigo-600 hover:underline">Login</Link>
                </p>
            </div>
        </div>
    </MainLayout>
</template>

<style scoped></style>
