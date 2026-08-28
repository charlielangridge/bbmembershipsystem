<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { show } from '@/wayfinder/routes/members';

type Member = {
    id: number;
    name: string;
    tagline: string | null;
    photo_url: string | null;
};

defineProps<{
    members: Member[];
}>();
</script>

<template>
    <Head title="Members" />

    <div class="flex max-w-6xl flex-col gap-6 p-4">
        <Heading
            title="Members"
            description="Profiles shared with your current audience."
        />

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="member in members" :key="member.id">
                <img
                    v-if="member.photo_url"
                    :src="member.photo_url"
                    :alt="`${member.name}'s profile photo`"
                    class="aspect-square w-full rounded-t-xl object-cover"
                />
                <CardHeader>
                    <CardTitle>
                        <Link
                            :href="show(member.id)"
                            class="hover:underline"
                            prefetch
                        >
                            {{ member.name }}
                        </Link>
                    </CardTitle>
                    <CardDescription>Member #{{ member.id }}</CardDescription>
                </CardHeader>
                <CardContent>
                    <p class="text-sm text-muted-foreground">
                        {{ member.tagline ?? 'No profile introduction yet.' }}
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
