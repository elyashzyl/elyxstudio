<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ArrowLeft, Mail } from '@lucide/vue';
import ElyxAuthShell from '@/components/ElyxAuthShell.vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

const props = defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Reset your ELYX password" />

    <ElyxAuthShell
        eyebrow="Account recovery"
        title="Get back to your workspace."
        description="Enter the email you use with ELYX and we’ll send a secure link to create a new password."
    >
        <template #status>
            <div
                v-if="props.status"
                class="mb-5 rounded-xl border border-[#b9e8d4] bg-[#f0fdf6] px-4 py-3 text-center text-sm font-medium text-[#087443]"
            >
                {{ props.status }}
            </div>
        </template>

        <Form v-bind="email.form()" v-slot="{ errors, processing }">
            <div class="grid gap-2">
                <Label for="email" class="text-[13px] font-bold text-[#355d78]"
                    >Email address</Label
                >
                <div class="relative">
                    <Mail
                        aria-hidden="true"
                        class="pointer-events-none absolute top-1/2 left-3.5 h-4 w-4 -translate-y-1/2 text-[#8aa4b8]"
                    />
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        autofocus
                        required
                        placeholder="you@example.com"
                        class="h-12 rounded-xl border-[#c7e3f3] bg-[#fbfeff] pl-10 text-[#102a43] shadow-none placeholder:text-[#8aa4b8] focus-visible:border-[#27a9e8] focus-visible:ring-[#27a9e8]/20"
                    />
                </div>
                <InputError :message="errors.email" />
            </div>

            <Button
                type="submit"
                class="mt-6 h-12 w-full rounded-xl bg-[#0f6ea8] text-sm font-bold text-white shadow-[0_10px_22px_rgba(15,110,168,0.2)] transition hover:bg-[#0b527e]"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                <template v-else>
                    <Mail class="h-4 w-4" />
                    Send reset link
                </template>
            </Button>
        </Form>

        <template #footer>
            <TextLink
                :href="login()"
                class="inline-flex items-center gap-1.5 font-bold text-[#0f6ea8] hover:text-[#0b527e]"
            >
                <ArrowLeft class="h-3.5 w-3.5" />
                Back to sign in
            </TextLink>
        </template>
    </ElyxAuthShell>
</template>
