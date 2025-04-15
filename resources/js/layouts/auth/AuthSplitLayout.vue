<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const name = page.props.name;

defineProps({
    title: { type: String },
    description: { type: String },
});

const quotes = [
    { quote: 'The best way to predict the future is to invent it.', author: 'Alan Kay' },
    { quote: 'Creativity is contagious. Pass it on.', author: 'Albert Einstein' },
    { quote: "If you can't make it good, at least make it look good.", author: 'Bill Gates' },
    {
        quote: 'Invention, it must be humbly admitted, does not consist in creating out of void, but out of chaos.',
        author: 'Mary Shelley',
    },
    { quote: 'Innovation distinguishes between a leader and a follower.', author: 'Steve Jobs' },
    { quote: 'The only way to learn a new programming language is by writing programs in it.', author: 'Dennis Ritchie' },
    { quote: 'Every act of creation is first an act of destruction.', author: 'Pablo Picasso' },
    { quote: 'The true sign of intelligence is not knowledge but imagination.', author: 'Albert Einstein' },
    { quote: 'Art is what happens when you dare to be who you really are.', author: 'Scott Adams' },
    { quote: "All of us are makers. We're born makers.", author: 'Chris Anderson' },
    { quote: 'To invent, you need a good imagination and a pile of junk.', author: 'Thomas Edison' },
    { quote: 'Great things are done by a series of small things brought together.', author: 'Vincent Van Gogh' },
    { quote: 'Stay hungry, stay foolish.', author: 'Steve Jobs' },
    { quote: 'If you want something new, you have to stop doing something old.', author: 'Peter F. Drucker' },
    { quote: 'The most dangerous phrase in the language is: ‘We’ve always done it this way.’', author: 'Grace Hopper' },
];

const randomQuote = quotes[Math.floor(Math.random() * quotes.length)];
</script>

<template>

    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <div class="bg-muted relative hidden h-full flex-col p-10 text-white lg:flex dark:border-r">
            <div class="absolute inset-0 bg-zinc-900 bg-[url(/img/rule-zero.jpg)] bg-cover bg-center bg-no-repeat" />
            <Link :href="route('home')" class="relative z-20 flex items-center text-lg font-medium">
                <img src="/img/logo.png" :alt="name" class="mr-2 size-24 fill-current text-white" />
            </Link>
            <div v-if="randomQuote" class="relative z-20 mt-auto bg-white/20 p-6 backdrop-blur-sm rounded-lg">
                <blockquote class="space-y-2">
                    <p class="text-lg text-black">&ldquo;{{ randomQuote.quote }}&rdquo;</p>
                    <footer class="text-sm text-neutral-300">{{ randomQuote.author }}</footer>
                </blockquote>
            </div>
        </div>
        <div class="lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <img src="/img/logo.png" :alt="page.props.name" class="size-24 md:size-36 mb-6 md:mb-12 mx-auto">
                <div class="flex flex-col space-y-2 text-center">
                    <h1 class="text-xl font-medium tracking-tight" v-if="title">{{ title }}</h1>
                    <p class="text-muted-foreground text-sm" v-if="description">{{ description }}</p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
