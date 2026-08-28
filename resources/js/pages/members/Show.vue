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
import { index } from '@/wayfinder/routes/members';

type Member = {
    id: number;
    name: string;
    tagline: string | null;
    description: string | null;
    photo_url: string | null;
};

const { member } = defineProps<{
    member: Member;
}>();
</script>

<template>
    <Head :title="`${member.name} — Member profile`" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <Link
            :href="index()"
            class="text-sm text-muted-foreground hover:underline"
        >
            Back to members
        </Link>

        <Heading
            :title="member.name"
            description="Member profile information shared with your current audience."
        />

        <Card>
            <img
                v-if="member.photo_url"
                :src="member.photo_url"
                :alt="`${member.name}'s profile photo`"
                class="aspect-square w-full rounded-t-xl object-cover sm:max-h-96"
            />
            <CardHeader>
                <CardTitle>{{ member.tagline ?? 'Member profile' }}</CardTitle>
                <CardDescription>Member #{{ member.id }}</CardDescription>
            </CardHeader>
            <CardContent>
                <p class="text-sm whitespace-pre-line text-muted-foreground">
                    {{ member.description ?? 'No profile description yet.' }}
                </p>
            </CardContent>
        </Card>
    </div>
</template>
