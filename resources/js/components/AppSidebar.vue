<script setup>
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuItem } from '@/components/ui/sidebar';

import { Link, usePage } from '@inertiajs/vue3';
import { ClipboardList, LogIn, LayoutGrid } from 'lucide-vue-next';

const page = usePage();
const user = page.props.auth.user;

const mainNavItems = [
    {
        title: 'Members',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Tools & Equipment',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Activity',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Stats',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Proposals',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Resources',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Expenses',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Groups',
        href: '/dashboard',
        icon: LayoutGrid,
    },
];

const footerNavItems = page.props.auth.user
    ? []
    : [
          {
              title: 'Login',
              href: route('login'),
              icon: LogIn,
          },
          {
              title: 'Become a member',
              href: route('register'),
              icon: ClipboardList,
          },
      ];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <!--                    <SidebarMenuButton size="lg" as-child>-->
                    <Link :href="page.props.auth.user ? route('dashboard') : route('home')">
                        <img src="/img/logo.png" alt="Build Brighton" :class="[page.props.auth.user ? 'size-24 mb-4' : 'mb-6 w-full', 'mix-blend-darken']" />
                    </Link>
                    <!--                    </SidebarMenuButton>-->
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser v-if="user" />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
