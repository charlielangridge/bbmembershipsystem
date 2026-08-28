<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Account = {
    id: number;
    name: string;
    email: string;
    email_verified_at: string | null;
    created_at: string | null;
};

const { account } = defineProps<{
    account: Account;
}>();

const joinedAt = account.created_at
    ? new Intl.DateTimeFormat('en-GB', { dateStyle: 'long' }).format(
          new Date(account.created_at),
      )
    : 'Not available';
</script>

<template>
    <Head :title="`${account.name} — Account`" />

    <div class="flex max-w-3xl flex-col gap-6 p-4">
        <Heading
            title="Account"
            description="Core membership account details."
        />

        <Card>
            <CardHeader>
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex flex-col gap-1.5">
                        <CardTitle>{{ account.name }}</CardTitle>
                        <CardDescription
                            >Member #{{ account.id }}</CardDescription
                        >
                    </div>
                    <Badge
                        :variant="
                            account.email_verified_at ? 'default' : 'secondary'
                        "
                    >
                        {{
                            account.email_verified_at
                                ? 'Email verified'
                                : 'Email unverified'
                        }}
                    </Badge>
                </div>
            </CardHeader>
            <CardContent>
                <dl class="grid gap-5 sm:grid-cols-2">
                    <div class="flex flex-col gap-1">
                        <dt class="text-sm font-medium text-muted-foreground">
                            Email address
                        </dt>
                        <dd class="text-sm break-all">{{ account.email }}</dd>
                    </div>
                    <div class="flex flex-col gap-1">
                        <dt class="text-sm font-medium text-muted-foreground">
                            Member since
                        </dt>
                        <dd class="text-sm">{{ joinedAt }}</dd>
                    </div>
                </dl>
            </CardContent>
        </Card>
    </div>
</template>
