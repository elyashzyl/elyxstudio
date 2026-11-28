<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Check, ShoppingBag, Store } from '@lucide/vue';
import { ref } from 'vue';
import ElyxAuthShell from '@/components/ElyxAuthShell.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{ passwordRules: string }>();

const role = ref('buyer');
</script>

<template>
    <Head title="Create an ELYX account" />

    <ElyxAuthShell
        eyebrow="Start building"
        title="Make room for what is next."
        description="Join ELYX to discover useful products, commission a website, or share your own work with the studio community."
    >
        <Form
            action="/register"
            method="post"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="grid gap-5 lg:grid-cols-2 lg:gap-x-4 lg:gap-y-2"
        >
            <div class="grid gap-2">
                <Label for="name" class="text-[13px] font-bold text-[#355d78]"
                    >Full name</Label
                >
                <Input
                    id="name"
                    name="name"
                    required
                    autofocus
                    autocomplete="name"
                    placeholder="Your name"
                    class="h-12 rounded-xl border-[#c7e3f3] bg-[#fbfeff] text-[#102a43] shadow-none placeholder:text-[#8aa4b8] focus-visible:border-[#27a9e8] focus-visible:ring-[#27a9e8]/20 lg:h-10"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email" class="text-[13px] font-bold text-[#355d78]"
                    >Email address</Label
                >
                <Input
                    id="email"
                    name="email"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="you@example.com"
                    class="h-12 rounded-xl border-[#c7e3f3] bg-[#fbfeff] text-[#102a43] shadow-none placeholder:text-[#8aa4b8] focus-visible:border-[#27a9e8] focus-visible:ring-[#27a9e8]/20 lg:h-10"
                />
                <InputError :message="errors.email" />
            </div>

            <fieldset class="grid gap-3 lg:col-span-2 lg:gap-2">
                <legend class="text-[13px] font-bold text-[#355d78]">
                    How will you use ELYX?
                </legend>
                <div class="grid gap-3 sm:grid-cols-2">
                    <button
                        type="button"
                        class="relative rounded-2xl border p-4 text-left transition-all focus-visible:ring-2 focus-visible:ring-[#27a9e8]/40 focus-visible:outline-none"
                        :class="
                            role === 'buyer'
                                ? 'border-[#27a9e8] bg-[#e8f7fe] shadow-[0_8px_20px_rgba(39,169,232,0.1)]'
                                : 'border-[#d7ebf8] bg-[#fbfeff] hover:border-[#a8d8ef] hover:bg-[#f4fbff]'
                        "
                        @click="role = 'buyer'"
                    >
                        <span
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-white text-[#0f6ea8] shadow-sm lg:mb-2"
                        >
                            <ShoppingBag class="h-4 w-4" />
                        </span>
                        <strong class="block text-sm text-[#102a43]"
                            >Shop as a buyer</strong
                        >
                        <span
                            class="mt-1 block text-xs leading-5 text-[#55718a]"
                            >Discover products and track orders.</span
                        >
                        <Check
                            v-if="role === 'buyer'"
                            class="absolute top-4 right-4 h-4 w-4 text-[#0f6ea8]"
                        />
                    </button>

                    <button
                        type="button"
                        class="relative rounded-2xl border p-4 text-left transition-all focus-visible:ring-2 focus-visible:ring-[#27a9e8]/40 focus-visible:outline-none"
                        :class="
                            role === 'seller'
                                ? 'border-[#27a9e8] bg-[#e8f7fe] shadow-[0_8px_20px_rgba(39,169,232,0.1)]'
                                : 'border-[#d7ebf8] bg-[#fbfeff] hover:border-[#a8d8ef] hover:bg-[#f4fbff]'
                        "
                        @click="role = 'seller'"
                    >
                        <span
                            class="mb-3 flex h-9 w-9 items-center justify-center rounded-xl bg-white text-[#0f6ea8] shadow-sm lg:mb-2"
                        >
                            <Store class="h-4 w-4" />
                        </span>
                        <strong class="block text-sm text-[#102a43]"
                            >Sell with ELYX</strong
                        >
                        <span
                            class="mt-1 block text-xs leading-5 text-[#55718a]"
                            >Submit work for review and approval.</span
                        >
                        <Check
                            v-if="role === 'seller'"
                            class="absolute top-4 right-4 h-4 w-4 text-[#0f6ea8]"
                        />
                    </button>
                </div>
                <input type="hidden" name="role" :value="role" />
            </fieldset>

            <div class="grid gap-2">
                <Label
                    for="password"
                    class="text-[13px] font-bold text-[#355d78]"
                    >Password</Label
                >
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    class="h-12 rounded-xl border-[#c7e3f3] bg-[#fbfeff] text-[#102a43] shadow-none placeholder:text-[#8aa4b8] focus-visible:border-[#27a9e8] focus-visible:ring-[#27a9e8]/20 lg:h-10"
                />
                <p
                    class="text-[11px] leading-5 text-[#8aa4b8] lg:text-[10px] lg:leading-4"
                >
                    {{ props.passwordRules }}
                </p>
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label
                    for="password_confirmation"
                    class="text-[13px] font-bold text-[#355d78]"
                    >Confirm password</Label
                >
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    class="h-12 rounded-xl border-[#c7e3f3] bg-[#fbfeff] text-[#102a43] shadow-none placeholder:text-[#8aa4b8] focus-visible:border-[#27a9e8] focus-visible:ring-[#27a9e8]/20 lg:h-10"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="h-12 w-full rounded-xl bg-[#0f6ea8] text-sm font-bold text-white shadow-[0_10px_22px_rgba(15,110,168,0.2)] transition hover:bg-[#0b527e] lg:col-span-2"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                <span v-else>Create your ELYX account</span>
            </Button>
        </Form>

        <template #footer>
            Already have an account?
            <TextLink
                href="/login"
                class="ml-1 font-bold text-[#0f6ea8] hover:text-[#0b527e]"
                >Sign in</TextLink
            >
        </template>
    </ElyxAuthShell>
</template>
