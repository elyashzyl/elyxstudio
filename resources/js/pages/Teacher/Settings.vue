<template>
    <Head title="System Settings — Admin" />

    <div class="px-4 py-4 sm:px-6 sm:py-6">
        <!-- Flash Message -->
        <div
            v-if="flash?.success"
            class="gl-fade-in mb-6 rounded-2xl px-5 py-3.5 text-sm font-medium"
            style="
                background: rgba(16, 185, 129, 0.12);
                border: 1px solid rgba(16, 185, 129, 0.3);
                color: var(--gl-success);
            "
        >
            {{ flash.success }}
        </div>
        <div
            v-if="flash?.error"
            class="gl-fade-in mb-6 rounded-2xl px-5 py-3.5 text-sm font-medium"
            style="
                background: var(--gl-danger-bg);
                border: 1px solid rgba(239, 68, 68, 0.3);
                color: var(--gl-danger);
            "
        >
            {{ flash.error }}
        </div>
        <div
            v-if="errorsList.length > 0"
            class="gl-fade-in mb-6 rounded-2xl px-5 py-3.5 text-sm font-medium"
            style="
                background: var(--gl-danger-bg);
                border: 1px solid rgba(239, 68, 68, 0.3);
                color: var(--gl-danger);
            "
        >
            <p class="font-bold mb-1">Configuration save errors:</p>
            <ul class="list-disc list-inside space-y-0.5 text-xs">
                <li v-for="err in errorsList" :key="err">{{ err }}</li>
            </ul>
        </div>

        <!-- Header -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase"
                        style="
                            background: rgba(59, 130, 246, 0.12);
                            color: var(--gl-primary);
                        "
                    >
                        Super Admin Control
                    </span>
                    <span class="text-[11px] font-medium" style="color: var(--gl-text-muted)">
                        System Configuration
                    </span>
                </div>
                <h1
                    class="mt-1.5 text-2xl font-extrabold tracking-tight sm:text-3xl"
                    style="color: var(--gl-text-primary)"
                >
                    Settings & Configuration
                </h1>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Manage platform branding, communication permissions, gamification rewards, and security limits.
                </p>
            </div>

            <!-- Action buttons -->
            <div class="flex flex-wrap items-center gap-2.5">
                <button
                    type="button"
                    @click="showResetModal = true"
                    class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-secondary);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <RotateCcw :size="14" :stroke-width="2.2" />
                    <span>Reset Defaults</span>
                </button>

                <button
                    type="button"
                    @click="saveSettings"
                    :disabled="saving"
                    class="inline-flex items-center gap-1.5 rounded-xl px-5 py-2 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                    style="
                        background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary));
                        box-shadow: 0 0 16px var(--gl-primary-glow);
                    "
                >
                    <LoaderCircle v-if="saving" :size="14" class="animate-spin" />
                    <Save v-else :size="14" :stroke-width="2.2" />
                    <span>{{ saving ? 'Saving Changes...' : 'Save Settings' }}</span>
                </button>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="mb-6 flex flex-wrap items-center gap-2 border-b pb-4" style="border-color: var(--gl-border)">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                @click="activeTab = tab.id"
                class="flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all"
                :style="
                    activeTab === tab.id
                        ? {
                              background: 'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                              color: '#fff',
                              boxShadow: '0 2px 10px var(--gl-primary-glow)',
                          }
                        : {
                              background: 'var(--gl-surface)',
                              color: 'var(--gl-text-secondary)',
                              border: '1px solid var(--gl-border)',
                          }
                "
            >
                <component :is="tab.icon" :size="15" :stroke-width="2.2" />
                <span>{{ tab.name }}</span>
            </button>
        </div>

        <form @submit.prevent="saveSettings" novalidate>
            <!-- 2. Platform & Branding Tab -->
            <div
                v-show="activeTab === 'platform'"
                class="rounded-3xl p-6 sm:p-7 space-y-6"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border);"
            >
                <div>
                    <h2 class="text-base font-extrabold" style="color: var(--gl-text-primary)">
                        Platform Branding & Operations
                    </h2>
                    <p class="mt-0.5 text-xs" style="color: var(--gl-text-muted)">
                        System-wide branding titles, user registration toggles, and system maintenance flags.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Platform App Name
                        </label>
                        <input
                            v-model="form.app_name"
                            type="text"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none transition-all"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            placeholder="e.g. EduPulse"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">App title displayed in header navigation and browser tabs.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            System Administrator Support Email
                        </label>
                        <input
                            v-model="form.support_email"
                            type="email"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none transition-all"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            placeholder="e.g. ely.ashzyl@gmail.com"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Primary address for system notifications and support inquiries.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Payment & Inquiries Contact Email
                        </label>
                        <input
                            v-model="form.payment_email"
                            type="email"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none transition-all"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            placeholder="e.g. ely.ashzyl@gmail.com"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Billing, subscriptions, and commercial inquiries contact address.</p>
                    </div>
                </div>

                <!-- Toggle Options -->
                <div class="space-y-4 pt-2">
                    <div
                        class="flex items-center justify-between rounded-2xl p-4 transition-all"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);"
                    >
                        <div>
                            <p class="text-sm font-bold" style="color: var(--gl-text-primary)">
                                Allow Student Self-Registration
                            </p>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                When enabled, new students can sign up independently on the registration page.
                            </p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                :checked="form.allow_registration === 'true'"
                                @change="form.allow_registration = form.allow_registration === 'true' ? 'false' : 'true'"
                                class="peer sr-only"
                            />
                            <div class="peer h-6 w-11 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-primary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>

                    <div
                        class="flex items-center justify-between rounded-2xl p-4 transition-all"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);"
                    >
                        <div>
                            <p class="text-sm font-bold" style="color: var(--gl-text-primary)">
                                Maintenance Notice Banner
                            </p>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                Display a persistent maintenance announcement banner across all student & teacher screens.
                            </p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                :checked="form.maintenance_mode === 'true'"
                                @change="form.maintenance_mode = form.maintenance_mode === 'true' ? 'false' : 'true'"
                                class="peer sr-only"
                            />
                            <div class="peer h-6 w-11 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-danger)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>

                    <div v-if="form.maintenance_mode === 'true'">
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Maintenance Announcement Text
                        </label>
                        <textarea
                            v-model="form.maintenance_message"
                            rows="2"
                            class="w-full rounded-xl border p-3 text-xs font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            placeholder="Enter the maintenance warning message to display..."
                        ></textarea>
                    </div>
                </div>
            </div>

            <!-- Cloud Storage Adapter Tab -->
            <div
                v-show="activeTab === 'storage'"
                class="rounded-3xl p-6 sm:p-7 space-y-6"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border);"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-extrabold" style="color: var(--gl-text-primary)">
                                External Cloud Storage (S3 / Cloudflare R2)
                            </h2>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                :style="{
                                    background: form.storage_driver === 'local' ? 'rgba(107, 114, 128, 0.15)' : 'rgba(16, 185, 129, 0.15)',
                                    color: form.storage_driver === 'local' ? 'var(--gl-text-muted)' : 'var(--gl-success)'
                                }"
                            >
                                {{ form.storage_driver === 'local' ? 'Local Disk' : (form.storage_driver === 'r2' ? 'Cloudflare R2 Active' : 'Amazon S3 Active') }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                            Store teaching materials, student practical submissions, messenger voice notes, and media attachments on scalable object storage with zero egress fees or database self-healing fallbacks.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="testStorageConnection"
                            :disabled="testingStorage"
                            class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-primary);"
                        >
                            <LoaderCircle v-if="testingStorage" :size="13" class="animate-spin" />
                            <Activity v-else :size="13" class="text-emerald-400" />
                            <span>{{ testingStorage ? 'Probing Bucket...' : 'Test Connection' }}</span>
                        </button>

                        <button
                            type="button"
                            @click="saveStorageSettings"
                            :disabled="savingStorage"
                            class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary)); box-shadow: 0 0 16px var(--gl-primary-glow);"
                        >
                            <LoaderCircle v-if="savingStorage" :size="13" class="animate-spin" />
                            <Save v-else :size="13" />
                            <span>{{ savingStorage ? 'Saving...' : 'Save Storage' }}</span>
                        </button>
                    </div>
                </div>

                <!-- Test Connection Results Feedback Banner -->
                <div
                    v-if="storageTestResult"
                    class="gl-fade-in rounded-2xl p-4 text-xs font-medium flex items-start justify-between gap-3"
                    :style="storageTestResult.success ? {
                        background: 'rgba(16, 185, 129, 0.1)',
                        border: '1px solid rgba(16, 185, 129, 0.3)',
                        color: 'var(--gl-success)'
                    } : {
                        background: 'rgba(239, 68, 68, 0.1)',
                        border: '1px solid rgba(239, 68, 68, 0.3)',
                        color: 'var(--gl-danger)'
                    }"
                >
                    <div class="flex items-start gap-2.5">
                        <CheckCircle2 v-if="storageTestResult.success" :size="16" class="mt-0.5 shrink-0 text-emerald-400" />
                        <AlertCircle v-else :size="16" class="mt-0.5 shrink-0 text-rose-400" />
                        <div>
                            <p class="font-bold">{{ storageTestResult.message }}</p>
                            <p v-if="storageTestResult.latency_ms !== undefined" class="text-[11px] opacity-80 mt-0.5">
                                Probe latency: <span class="font-mono font-bold">{{ storageTestResult.latency_ms }}ms</span>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="storageTestResult = null"
                        class="text-[10px] font-bold uppercase tracking-wider opacity-60 hover:opacity-100"
                    >
                        Dismiss
                    </button>
                </div>

                <!-- Storage Driver Selector -->
                <div>
                    <label class="block text-xs font-bold mb-2" style="color: var(--gl-text-primary)">
                        Storage Driver
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <!-- Local Driver -->
                        <div
                            @click="selectDriver('local')"
                            class="cursor-pointer rounded-2xl p-4 transition-all border"
                            :style="form.storage_driver === 'local' ? {
                                background: 'rgba(99, 102, 241, 0.12)',
                                borderColor: 'var(--gl-primary)',
                                boxShadow: '0 0 12px var(--gl-primary-glow)'
                            } : {
                                background: 'var(--gl-surface-2)',
                                borderColor: 'var(--gl-border)'
                            }"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Server :size="16" class="text-indigo-400" />
                                    <span class="text-xs font-bold" style="color: var(--gl-text-primary)">Local Disk</span>
                                </div>
                                <span v-if="form.storage_driver === 'local'" class="h-2 w-2 rounded-full bg-indigo-400 animate-pulse"></span>
                            </div>
                            <p class="mt-2 text-[11px] leading-relaxed" style="color: var(--gl-text-muted)">
                                Private storage on local server volume. Safe default for single-container setups.
                            </p>
                        </div>

                        <!-- Cloudflare R2 -->
                        <div
                            @click="selectDriver('r2')"
                            class="cursor-pointer rounded-2xl p-4 transition-all border"
                            :style="form.storage_driver === 'r2' ? {
                                background: 'rgba(249, 115, 22, 0.12)',
                                borderColor: '#f97316',
                                boxShadow: '0 0 12px rgba(249, 115, 22, 0.3)'
                            } : {
                                background: 'var(--gl-surface-2)',
                                borderColor: 'var(--gl-border)'
                            }"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <Cloud :size="16" class="text-orange-400" />
                                    <span class="text-xs font-bold" style="color: var(--gl-text-primary)">Cloudflare R2</span>
                                </div>
                                <span class="rounded-full px-1.5 py-0.2 text-[9px] font-extrabold bg-orange-500/20 text-orange-400">
                                    Zero Egress
                                </span>
                            </div>
                            <p class="mt-2 text-[11px] leading-relaxed" style="color: var(--gl-text-muted)">
                                Recommended. S3-compatible, ultra-low latency, 0$/GB outbound egress fees.
                            </p>
                        </div>

                        <!-- AWS S3 / Compatible -->
                        <div
                            @click="selectDriver('s3')"
                            class="cursor-pointer rounded-2xl p-4 transition-all border"
                            :style="form.storage_driver === 's3' ? {
                                background: 'rgba(59, 130, 246, 0.12)',
                                borderColor: 'var(--gl-primary)',
                                boxShadow: '0 0 12px var(--gl-primary-glow)'
                            } : {
                                background: 'var(--gl-surface-2)',
                                borderColor: 'var(--gl-border)'
                            }"
                        >
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <HardDrive :size="16" class="text-sky-400" />
                                    <span class="text-xs font-bold" style="color: var(--gl-text-primary)">Amazon S3</span>
                                </div>
                                <span v-if="form.storage_driver === 's3'" class="h-2 w-2 rounded-full bg-sky-400 animate-pulse"></span>
                            </div>
                            <p class="mt-2 text-[11px] leading-relaxed" style="color: var(--gl-text-muted)">
                                Standard AWS S3, MinIO, or DigitalOcean Spaces S3-compatible storage.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Provider Specific Guidance -->
                <div
                    v-if="form.storage_driver === 'r2'"
                    class="rounded-2xl p-4 border"
                    style="background: rgba(249, 115, 22, 0.06); border-color: rgba(249, 115, 22, 0.25);"
                >
                    <div class="flex items-start gap-2.5">
                        <Info :size="16" class="mt-0.5 shrink-0 text-orange-400" />
                        <div class="text-xs space-y-1">
                            <p class="font-bold text-orange-300">Cloudflare R2 Setup Guide:</p>
                            <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                1. In Cloudflare Dashboard, go to <strong>R2 Object Storage</strong> &rarr; <strong>Create bucket</strong>.
                            </p>
                            <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                2. Create an API token with <strong>Object Read & Write</strong> permissions.
                            </p>
                            <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                3. Set the Endpoint to: <code class="px-1 py-0.5 rounded bg-black/30 font-mono text-orange-200">https://&lt;ACCOUNT_ID&gt;.r2.cloudflarestorage.com</code>
                            </p>
                            <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                4. Leave Region as <code class="px-1 py-0.5 rounded bg-black/30 font-mono text-orange-200">auto</code> and keep <strong>Path Style Endpoint</strong> enabled.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Credentials Form (Only shown if r2 or s3 selected) -->
                <div v-if="form.storage_driver !== 'local'" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Bucket Name -->
                        <div>
                            <label class="block text-xs font-bold mb-1.5" style="color: var(--gl-text-primary)">
                                Bucket Name <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.storage_s3_bucket"
                                type="text"
                                placeholder="e.g. edupulse-storage"
                                class="w-full rounded-xl border p-2.5 text-xs font-semibold outline-none"
                                style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            />
                        </div>

                        <!-- Region -->
                        <div>
                            <label class="block text-xs font-bold mb-1.5" style="color: var(--gl-text-primary)">
                                Region
                            </label>
                            <input
                                v-model="form.storage_s3_region"
                                type="text"
                                :placeholder="form.storage_driver === 'r2' ? 'auto' : 'us-east-1'"
                                class="w-full rounded-xl border p-2.5 text-xs font-semibold outline-none"
                                style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            />
                            <p class="mt-1 text-[10px]" style="color: var(--gl-text-muted)">
                                {{ form.storage_driver === 'r2' ? 'Use auto for Cloudflare R2.' : 'Standard AWS region like us-east-1, ap-southeast-1, eu-west-1.' }}
                            </p>
                        </div>

                        <!-- Access Key ID -->
                        <div>
                            <label class="block text-xs font-bold mb-1.5" style="color: var(--gl-text-primary)">
                                Access Key ID <span class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.storage_s3_key"
                                type="text"
                                placeholder="e.g. AKIAIOSFODNN7EXAMPLE or R2 Access Key"
                                class="w-full rounded-xl border p-2.5 text-xs font-mono outline-none"
                                style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            />
                        </div>

                        <!-- Secret Access Key -->
                        <div>
                            <label class="block text-xs font-bold mb-1.5" style="color: var(--gl-text-primary)">
                                Secret Access Key <span class="text-rose-400">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.storage_s3_secret"
                                    :type="showS3Secret ? 'text' : 'password'"
                                    placeholder="e.g. wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY"
                                    class="w-full rounded-xl border p-2.5 pr-9 text-xs font-mono outline-none"
                                    style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                />
                                <button
                                    type="button"
                                    @click="showS3Secret = !showS3Secret"
                                    class="absolute right-2.5 top-2.5 opacity-60 hover:opacity-100"
                                >
                                    <EyeOff v-if="showS3Secret" :size="14" />
                                    <Eye v-else :size="14" />
                                </button>
                            </div>
                        </div>

                        <!-- Endpoint URL -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold mb-1.5" style="color: var(--gl-text-primary)">
                                Endpoint URL <span v-if="form.storage_driver === 'r2'" class="text-rose-400">*</span>
                            </label>
                            <input
                                v-model="form.storage_s3_endpoint"
                                type="text"
                                :placeholder="form.storage_driver === 'r2' ? 'https://<ACCOUNT_ID>.r2.cloudflarestorage.com' : 'Optional for standard AWS. Required for MinIO / Spaces / R2.'"
                                class="w-full rounded-xl border p-2.5 text-xs font-mono outline-none"
                                style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            />
                        </div>

                        <!-- Public CDN Domain / URL -->
                        <div>
                            <label class="block text-xs font-bold mb-1.5" style="color: var(--gl-text-primary)">
                                Public CDN URL / Custom Domain (Optional)
                            </label>
                            <input
                                v-model="form.storage_s3_public_url"
                                type="text"
                                placeholder="e.g. https://media.myschool.edu or https://pub-<hash>.r2.dev"
                                class="w-full rounded-xl border p-2.5 text-xs font-mono outline-none"
                                style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            />
                            <p class="mt-1 text-[10px]" style="color: var(--gl-text-muted)">
                                If set, large PDF and audio previews stream directly from this CDN domain.
                            </p>
                        </div>

                        <!-- Path Style Endpoint -->
                        <div>
                            <label class="block text-xs font-bold mb-1.5" style="color: var(--gl-text-primary)">
                                Path-Style Endpoint
                            </label>
                            <select
                                v-model="form.storage_s3_use_path_style"
                                class="w-full rounded-xl border p-2.5 text-xs font-semibold outline-none"
                                style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            >
                                <option value="true">Enabled (true) — Required for R2 and MinIO</option>
                                <option value="false">Disabled (false) — Virtual-hosted style (AWS default)</option>
                            </select>
                            <p class="mt-1 text-[10px]" style="color: var(--gl-text-muted)">
                                Format: bucket-in-path vs bucket-in-subdomain.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Multi-Tier Safety Guarantee -->
                <div
                    class="rounded-2xl p-4 border flex items-start gap-3"
                    style="background: rgba(99, 102, 241, 0.05); border-color: rgba(99, 102, 241, 0.2);"
                >
                    <Shield :size="18" class="text-indigo-400 mt-0.5 shrink-0" />
                    <div class="text-xs space-y-1">
                        <p class="font-bold text-indigo-300">Multi-Tier Redundancy & Self-Healing Architecture</p>
                        <p class="text-[11px] leading-relaxed" style="color: var(--gl-text-muted)">
                            • <strong>Tier 1: Cloud Storage (S3/R2)</strong> handles high-bandwidth file uploads, direct streaming, and scalable storage.<br />
                            • <strong>Tier 2: Local Disk Cache</strong> accelerates recurrent reads and offline access.<br />
                            • <strong>Tier 3: Database Base64 Backup</strong> stores core files inside SQLite to guarantee zero file loss across ephemeral container redeployments.<br />
                            • If a file is missing from any tier, the <strong>Autonomous AI System Manager</strong> automatically repairs and synchronizes copies across tiers.
                        </p>
                    </div>
                </div>
            </div>

            <!-- 3. AI Configuration Tab -->
            <div
                v-show="activeTab === 'ai'"
                class="rounded-3xl p-6 sm:p-7 space-y-6"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border);"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-base font-extrabold" style="color: var(--gl-text-primary)">
                                Artificial Intelligence Configuration
                            </h2>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-black uppercase"
                                style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary)"
                            >
                                Active: {{ form.ai_provider }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs" style="color: var(--gl-text-muted)">
                            Configure Google Gemini, OpenAI, or offline generators for automated activity generation and AI essay grading.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="saveAiSettings"
                            :disabled="savingAi"
                            class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            style="
                                background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary));
                                box-shadow: 0 0 16px var(--gl-primary-glow);
                            "
                        >
                            <LoaderCircle v-if="savingAi" :size="13" class="animate-spin" />
                            <Save v-else :size="13" :stroke-width="2.2" />
                            <span>{{ savingAi ? 'Saving AI...' : 'Save AI Configuration' }}</span>
                        </button>
                        <button
                            type="button"
                            @click="testAiConnection()"
                            :disabled="testingAi"
                            class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);"
                        >
                            <LoaderCircle v-if="testingProvider === 'active'" :size="13" class="animate-spin" />
                            <Zap v-else :size="13" style="color: var(--gl-accent)" />
                            <span>{{ testingProvider === 'active' ? 'Testing...' : 'Test Active AI' }}</span>
                        </button>
                    </div>
                </div>

                <!-- AI Dedicated Save Notification Banner -->
                <div
                    v-if="aiSaveNotice"
                    id="ai-save-banner"
                    class="gl-fade-in flex items-center justify-between rounded-2xl p-4 transition-all"
                    :style="
                        aiSaveNotice.type === 'success'
                            ? {
                                  background: 'rgba(16, 185, 129, 0.12)',
                                  border: '1px solid rgba(16, 185, 129, 0.3)',
                                  color: 'var(--gl-success)',
                              }
                            : {
                                  background: 'var(--gl-danger-bg)',
                                  border: '1px solid rgba(239, 68, 68, 0.3)',
                                  color: 'var(--gl-danger)',
                              }
                    "
                >
                    <div class="flex items-center gap-2.5 text-xs font-bold">
                        <CheckCircle2 v-if="aiSaveNotice.type === 'success'" :size="17" class="shrink-0" />
                        <AlertCircle v-else :size="17" class="shrink-0" />
                        <span>{{ aiSaveNotice.text }}</span>
                    </div>
                    <button
                        type="button"
                        @click="aiSaveNotice = null"
                        class="text-xs font-bold opacity-60 hover:opacity-100 ml-3"
                    >
                        Dismiss
                    </button>
                </div>

                <!-- Test Connection Feedback Banner -->
                <div
                    v-if="testResult"
                    id="ai-test-banner"
                    class="gl-fade-in flex items-start gap-3 rounded-2xl p-4 transition-all"
                    :style="
                        testResult.success
                            ? {
                                  background: 'rgba(16, 185, 129, 0.12)',
                                  border: '1px solid rgba(16, 185, 129, 0.3)',
                                  color: 'var(--gl-success)',
                              }
                            : {
                                  background: 'var(--gl-danger-bg)',
                                  border: '1px solid rgba(239, 68, 68, 0.3)',
                                  color: 'var(--gl-danger)',
                              }
                    "
                >
                    <CheckCircle2 v-if="testResult.success" :size="18" class="shrink-0 mt-0.5" />
                    <AlertCircle v-else :size="18" class="shrink-0 mt-0.5" />
                    <div class="min-w-0 flex-1 text-xs">
                        <p class="font-bold">{{ testResult.message }}</p>
                        <p v-if="testResult.latency_ms" class="mt-0.5 font-mono text-[11px] opacity-80">
                            Provider: {{ testResult.provider }} · Model: {{ testResult.model || 'N/A' }} · Response Time: {{ testResult.latency_ms }}ms
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="testResult = null"
                        class="text-xs font-bold opacity-60 hover:opacity-100"
                    >
                        Dismiss
                    </button>
                </div>

                <!-- Provider Selection Cards -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider mb-2.5" style="color: var(--gl-text-secondary)">
                        Select Primary AI Provider
                    </label>
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
                        <div
                            @click="form.ai_provider = 'auto'"
                            class="cursor-pointer rounded-2xl p-4 transition-all"
                            :style="
                                form.ai_provider === 'auto'
                                    ? {
                                          background: 'rgba(124, 58, 237, 0.08)',
                                          border: '2px solid var(--gl-secondary)',
                                      }
                                    : {
                                          background: 'var(--gl-surface-2)',
                                          border: '1px solid var(--gl-border)',
                                      }
                            "
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold" style="color: var(--gl-text-primary)">Auto Detect</span>
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold" style="background: rgba(124, 58, 237, 0.15); color: var(--gl-secondary)">Recommended</span>
                            </div>
                            <p class="mt-1 text-[11px] leading-relaxed" style="color: var(--gl-text-muted)">
                                Routes across OmniRoute, 9Router, Gemini, OpenAI, or offline heuristic.
                            </p>
                        </div>

                        <div
                            @click="form.ai_provider = 'omniroute'"
                            class="cursor-pointer rounded-2xl p-4 transition-all"
                            :style="
                                form.ai_provider === 'omniroute'
                                    ? {
                                          background: 'rgba(124, 58, 237, 0.12)',
                                          border: '2px solid var(--gl-secondary)',
                                      }
                                    : {
                                          background: 'var(--gl-surface-2)',
                                          border: '1px solid var(--gl-border)',
                                      }
                            "
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold" style="color: var(--gl-text-primary)">OmniRoute</span>
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold" style="background: rgba(124, 58, 237, 0.2); color: var(--gl-secondary)">Multi-LLM</span>
                            </div>
                            <p class="mt-1 text-[11px] leading-relaxed" style="color: var(--gl-text-muted)">
                                Intelligent proxy router with concise, exact output formatting.
                            </p>
                        </div>

                        <div
                            @click="form.ai_provider = '9router'"
                            class="cursor-pointer rounded-2xl p-4 transition-all"
                            :style="
                                form.ai_provider === '9router' || form.ai_provider === 'ninerouter'
                                    ? {
                                          background: 'rgba(236, 72, 153, 0.12)',
                                          border: '2px solid #ec4899',
                                      }
                                    : {
                                          background: 'var(--gl-surface-2)',
                                          border: '1px solid var(--gl-border)',
                                      }
                            "
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold" style="color: var(--gl-text-primary)">9Router</span>
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold" style="background: rgba(236, 72, 153, 0.2); color: #ec4899">Smart 3-Tier</span>
                            </div>
                            <p class="mt-1 text-[11px] leading-relaxed" style="color: var(--gl-text-muted)">
                                60+ providers, local gateway (localhost:20128), zero downtime fallback.
                            </p>
                        </div>

                        <div
                            @click="form.ai_provider = 'gemini'"
                            class="cursor-pointer rounded-2xl p-4 transition-all"
                            :style="
                                form.ai_provider === 'gemini'
                                    ? {
                                          background: 'rgba(59, 130, 246, 0.08)',
                                          border: '2px solid var(--gl-primary)',
                                      }
                                    : {
                                          background: 'var(--gl-surface-2)',
                                          border: '1px solid var(--gl-border)',
                                      }
                            "
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold" style="color: var(--gl-text-primary)">Google Gemini</span>
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6">Free Tier</span>
                            </div>
                            <p class="mt-1 text-[11px] leading-relaxed" style="color: var(--gl-text-muted)">
                                High speed, generous free token limits, and structured JSON output.
                            </p>
                        </div>

                        <div
                            @click="form.ai_provider = 'openai'"
                            class="cursor-pointer rounded-2xl p-4 transition-all"
                            :style="
                                form.ai_provider === 'openai'
                                    ? {
                                          background: 'rgba(16, 185, 129, 0.08)',
                                          border: '2px solid var(--gl-success)',
                                      }
                                    : {
                                          background: 'var(--gl-surface-2)',
                                          border: '1px solid var(--gl-border)',
                                      }
                            "
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold" style="color: var(--gl-text-primary)">OpenAI</span>
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold" style="background: rgba(16, 185, 129, 0.15); color: var(--gl-success)">GPT-4o</span>
                            </div>
                            <p class="mt-1 text-[11px] leading-relaxed" style="color: var(--gl-text-muted)">
                                Industry-standard reasoning via GPT-4o-mini and ChatGPT API keys.
                            </p>
                        </div>

                        <div
                            @click="form.ai_provider = 'heuristic'"
                            class="cursor-pointer rounded-2xl p-4 transition-all"
                            :style="
                                form.ai_provider === 'heuristic'
                                    ? {
                                          background: 'rgba(245, 158, 11, 0.08)',
                                          border: '2px solid var(--gl-accent)',
                                      }
                                    : {
                                          background: 'var(--gl-surface-2)',
                                          border: '1px solid var(--gl-border)',
                                      }
                            "
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold" style="color: var(--gl-text-primary)">Offline Heuristic</span>
                                <span class="rounded-full px-2 py-0.5 text-[9px] font-bold" style="background: rgba(245, 158, 11, 0.15); color: var(--gl-accent)">No API</span>
                            </div>
                            <p class="mt-1 text-[11px] leading-relaxed" style="color: var(--gl-text-muted)">
                                100% offline rule-based parser. Operates without any internet API keys.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- OmniRoute Parameters -->
                <div id="omniroute-section" class="rounded-2xl p-5 space-y-4" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full" style="background: var(--gl-secondary)"></span>
                            <h3 class="text-sm font-extrabold" style="color: var(--gl-text-primary)">
                                OmniRoute Router & Style
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="testAiConnection('omniroute')"
                            :disabled="testingAi"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-bold transition-all hover:bg-[var(--gl-surface)] disabled:opacity-50"
                            style="color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.3)"
                        >
                            <LoaderCircle v-if="testingProvider === 'omniroute'" :size="11" class="animate-spin" />
                            <span>{{ testingProvider === 'omniroute' ? 'Testing OmniRoute...' : 'Test OmniRoute API' }}</span>
                        </button>
                    </div>

                    <!-- Inline Feedback for OmniRoute -->
                    <div
                        v-if="testResult && testResult.provider === 'omniroute'"
                        class="gl-fade-in space-y-2 rounded-xl p-3 text-xs"
                        :style="
                            testResult.success
                                ? { background: 'rgba(16, 185, 129, 0.12)', border: '1px solid rgba(16, 185, 129, 0.3)', color: 'var(--gl-success)' }
                                : { background: 'var(--gl-danger-bg)', border: '1px solid rgba(239, 68, 68, 0.3)', color: 'var(--gl-danger)' }
                        "
                    >
                        <div class="flex items-start gap-2.5">
                            <CheckCircle2 v-if="testResult.success" :size="16" class="shrink-0 mt-0.5" />
                            <AlertCircle v-else :size="16" class="shrink-0 mt-0.5" />
                            <div class="min-w-0 flex-1">
                                <p class="font-bold">{{ testResult.message }}</p>
                                <p v-if="testResult.latency_ms" class="mt-0.5 font-mono text-[10px] opacity-80">
                                    Model: {{ testResult.model || 'N/A' }} · Response Time: {{ testResult.latency_ms }}ms
                                </p>
                            </div>
                        </div>

                        <!-- Detected models on server -->
                        <div v-if="testResult.available_models && testResult.available_models.length" class="pt-2 border-t border-current/20">
                            <p class="text-[11px] font-semibold opacity-90 mb-1.5">Detected models on this OmniRoute server (click to select):</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="m in testResult.available_models"
                                    :key="m"
                                    type="button"
                                    @click="form.omniroute_model = m"
                                    class="rounded-md px-2 py-0.5 font-mono text-[10px] font-bold transition-all hover:scale-105 active:scale-95"
                                    :class="form.omniroute_model === m ? 'bg-white text-black font-extrabold shadow-sm' : 'bg-black/20 hover:bg-black/30 text-current'"
                                >
                                    {{ m }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                                OmniRoute API Key
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.omniroute_api_key"
                                    :type="showOmniRouteKey ? 'text' : 'password'"
                                    class="w-full rounded-xl border py-2.5 pr-10 pl-3.5 text-xs font-mono outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                    placeholder="omni_sk_..."
                                />
                                <button
                                    type="button"
                                    @click="showOmniRouteKey = !showOmniRouteKey"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 p-1"
                                    style="color: var(--gl-text-muted)"
                                >
                                    <EyeOff v-if="showOmniRouteKey" :size="14" />
                                    <Eye v-else :size="14" />
                                </button>
                            </div>
                            <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">
                                Key for OmniRoute multi-LLM proxy router.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                                OmniRoute Model
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.omniroute_model"
                                    type="text"
                                    class="flex-1 rounded-xl border px-3.5 py-2.5 text-xs font-mono outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                    placeholder="omni/auto"
                                />
                                <select
                                    @change="form.omniroute_model = ($event.target as HTMLSelectElement).value"
                                    class="rounded-xl border px-2.5 py-2.5 text-xs font-bold outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-secondary);"
                                >
                                    <option value="">Presets</option>
                                    <option value="agy/gemini-3.7-flash-high">agy/gemini-3.7-flash-high (active)</option>
                                    <option value="agy/gemini-3.8-flash-high">agy/gemini-3.8-flash-high</option>
                                    <option value="omni/auto">omni/auto (balanced)</option>
                                    <option value="omni/fast">omni/fast (fast)</option>
                                    <option value="omni/smart">omni/smart (quality)</option>
                                    <option value="gpt-4o-mini">gpt-4o-mini</option>
                                    <option value="gemini-1.5-flash">gemini-1.5-flash</option>
                                    <option value="claude-3-5-sonnet">claude-3-5-sonnet</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                                OmniRoute Base URL
                            </label>
                            <input
                                v-model="form.omniroute_base_url"
                                type="text"
                                class="w-full rounded-xl border px-3.5 py-2.5 text-xs font-mono outline-none"
                                style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                placeholder="http://admin-omniroute-cec098-100-104-13-10.sslip.io/v1"
                            />
                            <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">
                                Base URL (supports OmniRoute, OpenRouter, custom proxy).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- 9Router Parameters -->
                <div id="9router-section" class="rounded-2xl p-5 space-y-4" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full" style="background: #ec4899"></span>
                            <h3 class="text-sm font-extrabold" style="color: var(--gl-text-primary)">
                                9Router Gateway & Fallback
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="testAiConnection('9router')"
                            :disabled="testingAi"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-bold transition-all hover:bg-[var(--gl-surface)] disabled:opacity-50"
                            style="color: #ec4899; border: 1px solid rgba(236, 72, 153, 0.3)"
                        >
                            <LoaderCircle v-if="testingProvider === '9router'" :size="11" class="animate-spin" />
                            <span>{{ testingProvider === '9router' ? 'Testing 9Router...' : 'Test 9Router API' }}</span>
                        </button>
                    </div>

                    <!-- Inline Feedback for 9Router -->
                    <div
                        v-if="testResult && (testResult.provider === '9router' || testResult.provider === 'ninerouter')"
                        class="gl-fade-in space-y-2 rounded-xl p-3 text-xs"
                        :style="
                            testResult.success
                                ? { background: 'rgba(16, 185, 129, 0.12)', border: '1px solid rgba(16, 185, 129, 0.3)', color: 'var(--gl-success)' }
                                : { background: 'var(--gl-danger-bg)', border: '1px solid rgba(239, 68, 68, 0.3)', color: 'var(--gl-danger)' }
                        "
                    >
                        <div class="flex items-start gap-2.5">
                            <CheckCircle2 v-if="testResult.success" :size="16" class="shrink-0 mt-0.5" />
                            <AlertCircle v-else :size="16" class="shrink-0 mt-0.5" />
                            <div class="min-w-0 flex-1">
                                <p class="font-bold">{{ testResult.message }}</p>
                                <p v-if="testResult.latency_ms" class="mt-0.5 font-mono text-[10px] opacity-80">
                                    Model: {{ testResult.model || 'N/A' }} · Response Time: {{ testResult.latency_ms }}ms
                                </p>
                            </div>
                        </div>

                        <!-- Detected models on server -->
                        <div v-if="testResult.available_models && testResult.available_models.length" class="pt-2 border-t border-current/20">
                            <p class="text-[11px] font-semibold opacity-90 mb-1.5">Detected models on this 9Router server (click to select):</p>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="m in testResult.available_models"
                                    :key="m"
                                    type="button"
                                    @click="form.ninerouter_model = m"
                                    class="rounded-md px-2 py-0.5 font-mono text-[10px] font-bold transition-all hover:scale-105 active:scale-95"
                                    :class="form.ninerouter_model === m ? 'bg-white text-black font-extrabold shadow-sm' : 'bg-black/20 hover:bg-black/30 text-current'"
                                >
                                    {{ m }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                                9Router API Key
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.ninerouter_api_key"
                                    :type="showNineRouterKey ? 'text' : 'password'"
                                    class="w-full rounded-xl border py-2.5 pr-10 pl-3.5 text-xs font-mono outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                    placeholder="Optional for local router (localhost:20128)"
                                />
                                <button
                                    type="button"
                                    @click="showNineRouterKey = !showNineRouterKey"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 p-1"
                                    style="color: var(--gl-text-muted)"
                                >
                                    <EyeOff v-if="showNineRouterKey" :size="14" />
                                    <Eye v-else :size="14" />
                                </button>
                            </div>
                            <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">
                                Key for 9Router proxy (optional if using local instance).
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                                9Router Model
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.ninerouter_model"
                                    type="text"
                                    class="flex-1 rounded-xl border px-3.5 py-2.5 text-xs font-mono outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                    placeholder="auto"
                                />
                                <select
                                    @change="form.ninerouter_model = ($event.target as HTMLSelectElement).value"
                                    class="rounded-xl border px-2.5 py-2.5 text-xs font-bold outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-secondary);"
                                >
                                    <option value="">Presets</option>
                                    <option value="auto">auto (smart 3-tier)</option>
                                    <option value="gpt-4o-mini">gpt-4o-mini</option>
                                    <option value="gemini-2.0-flash">gemini-2.0-flash</option>
                                    <option value="claude-3-5-sonnet">claude-3-5-sonnet</option>
                                    <option value="deepseek-chat">deepseek-chat</option>
                                    <option value="qwen-turbo">qwen-turbo</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                                9Router Base URL
                            </label>
                            <input
                                v-model="form.ninerouter_base_url"
                                type="text"
                                class="w-full rounded-xl border px-3.5 py-2.5 text-xs font-mono outline-none"
                                style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                placeholder="http://localhost:20128/v1"
                            />
                            <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">
                                Base URL (e.g. http://localhost:20128/v1 or custom host).
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Google Gemini Parameters -->
                <div id="gemini-section" class="rounded-2xl p-5 space-y-4" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full" style="background: #3b82f6"></span>
                            <h3 class="text-sm font-extrabold" style="color: var(--gl-text-primary)">
                                Google Gemini Credentials & Model
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="testAiConnection('gemini')"
                            :disabled="testingAi"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-bold transition-all hover:bg-[var(--gl-surface)] disabled:opacity-50"
                            style="color: #3b82f6; border: 1px solid rgba(59, 130, 246, 0.3)"
                        >
                            <LoaderCircle v-if="testingProvider === 'gemini'" :size="11" class="animate-spin" />
                            <span>{{ testingProvider === 'gemini' ? 'Testing Gemini...' : 'Test Gemini API' }}</span>
                        </button>
                    </div>

                    <!-- Inline Feedback for Gemini -->
                    <div
                        v-if="testResult && testResult.provider === 'gemini'"
                        class="gl-fade-in flex items-start gap-2.5 rounded-xl p-3 text-xs"
                        :style="
                            testResult.success
                                ? { background: 'rgba(16, 185, 129, 0.12)', border: '1px solid rgba(16, 185, 129, 0.3)', color: 'var(--gl-success)' }
                                : { background: 'var(--gl-danger-bg)', border: '1px solid rgba(239, 68, 68, 0.3)', color: 'var(--gl-danger)' }
                        "
                    >
                        <CheckCircle2 v-if="testResult.success" :size="16" class="shrink-0 mt-0.5" />
                        <AlertCircle v-else :size="16" class="shrink-0 mt-0.5" />
                        <div class="min-w-0 flex-1">
                            <p class="font-bold">{{ testResult.message }}</p>
                            <p v-if="testResult.latency_ms" class="mt-0.5 font-mono text-[10px] opacity-80">
                                Model: {{ testResult.model || 'N/A' }} · Response Time: {{ testResult.latency_ms }}ms
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                                Gemini API Key
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.gemini_api_key"
                                    :type="showGeminiKey ? 'text' : 'password'"
                                    class="w-full rounded-xl border py-2.5 pr-10 pl-3.5 text-xs font-mono outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                    placeholder="AIzaSy..."
                                />
                                <button
                                    type="button"
                                    @click="showGeminiKey = !showGeminiKey"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 p-1"
                                    style="color: var(--gl-text-muted)"
                                >
                                    <EyeOff v-if="showGeminiKey" :size="14" />
                                    <Eye v-else :size="14" />
                                </button>
                            </div>
                            <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">
                                Get your API key from Google AI Studio (<a href="https://aistudio.google.com/app/apikey" target="_blank" class="underline text-blue-500">aistudio.google.com</a>).
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                                Gemini Model Name
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.gemini_model"
                                    type="text"
                                    class="flex-1 rounded-xl border px-3.5 py-2.5 text-xs font-mono outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                    placeholder="gemini-1.5-flash"
                                />
                                <select
                                    @change="form.gemini_model = ($event.target as HTMLSelectElement).value"
                                    class="rounded-xl border px-2.5 py-2.5 text-xs font-bold outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-secondary);"
                                >
                                    <option value="">Presets</option>
                                    <option value="gemini-1.5-flash">gemini-1.5-flash (recommended)</option>
                                    <option value="gemini-2.5-flash">gemini-2.5-flash</option>
                                    <option value="gemini-1.5-pro">gemini-1.5-pro</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- OpenAI Parameters -->
                <div id="openai-section" class="rounded-2xl p-5 space-y-4" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full" style="background: var(--gl-success)"></span>
                            <h3 class="text-sm font-extrabold" style="color: var(--gl-text-primary)">
                                OpenAI Credentials & Model
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="testAiConnection('openai')"
                            :disabled="testingAi"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-bold transition-all hover:bg-[var(--gl-surface)] disabled:opacity-50"
                            style="color: var(--gl-success); border: 1px solid rgba(16, 185, 129, 0.3)"
                        >
                            <LoaderCircle v-if="testingProvider === 'openai'" :size="11" class="animate-spin" />
                            <span>{{ testingProvider === 'openai' ? 'Testing OpenAI...' : 'Test OpenAI API' }}</span>
                        </button>
                    </div>

                    <!-- Inline Feedback for OpenAI -->
                    <div
                        v-if="testResult && testResult.provider === 'openai'"
                        class="gl-fade-in flex items-start gap-2.5 rounded-xl p-3 text-xs"
                        :style="
                            testResult.success
                                ? { background: 'rgba(16, 185, 129, 0.12)', border: '1px solid rgba(16, 185, 129, 0.3)', color: 'var(--gl-success)' }
                                : { background: 'var(--gl-danger-bg)', border: '1px solid rgba(239, 68, 68, 0.3)', color: 'var(--gl-danger)' }
                        "
                    >
                        <CheckCircle2 v-if="testResult.success" :size="16" class="shrink-0 mt-0.5" />
                        <AlertCircle v-else :size="16" class="shrink-0 mt-0.5" />
                        <div class="min-w-0 flex-1">
                            <p class="font-bold">{{ testResult.message }}</p>
                            <p v-if="testResult.latency_ms" class="mt-0.5 font-mono text-[10px] opacity-80">
                                Model: {{ testResult.model || 'N/A' }} · Response Time: {{ testResult.latency_ms }}ms
                            </p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                                OpenAI API Key
                            </label>
                            <div class="relative">
                                <input
                                    v-model="form.openai_api_key"
                                    :type="showOpenAiKey ? 'text' : 'password'"
                                    class="w-full rounded-xl border py-2.5 pr-10 pl-3.5 text-xs font-mono outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                    placeholder="sk-..."
                                />
                                <button
                                    type="button"
                                    @click="showOpenAiKey = !showOpenAiKey"
                                    class="absolute top-1/2 right-3 -translate-y-1/2 p-1"
                                    style="color: var(--gl-text-muted)"
                                >
                                    <EyeOff v-if="showOpenAiKey" :size="14" />
                                    <Eye v-else :size="14" />
                                </button>
                            </div>
                            <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">
                                Get your OpenAI API key from <a href="https://platform.openai.com/api-keys" target="_blank" class="underline text-emerald-500">platform.openai.com</a>.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                                OpenAI Model Name
                            </label>
                            <div class="flex items-center gap-2">
                                <input
                                    v-model="form.openai_model"
                                    type="text"
                                    class="flex-1 rounded-xl border px-3.5 py-2.5 text-xs font-mono outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                    placeholder="gpt-4o-mini"
                                />
                                <select
                                    @change="form.openai_model = ($event.target as HTMLSelectElement).value"
                                    class="rounded-xl border px-2.5 py-2.5 text-xs font-bold outline-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-secondary);"
                                >
                                    <option value="">Presets</option>
                                    <option value="gpt-4o-mini">gpt-4o-mini (recommended)</option>
                                    <option value="gpt-4o">gpt-4o (flagship)</option>
                                    <option value="gpt-4-turbo">gpt-4-turbo</option>
                                    <option value="gpt-3.5-turbo">gpt-3.5-turbo</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Limits & Feature Switches -->
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            API Request Timeout (Seconds)
                        </label>
                        <input
                            v-model.number="form.ai_request_timeout"
                            type="number"
                            min="5"
                            max="300"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Maximum HTTP wait time for AI completion (default: 60s).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Max Source Document Characters
                        </label>
                        <input
                            v-model.number="form.ai_max_source_chars"
                            type="number"
                            min="500"
                            max="30000"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Character cut-off for uploaded material parsing (default: 6000).</p>
                    </div>
                </div>

                <div class="space-y-3 pt-1">
                    <div
                        class="flex items-center justify-between rounded-2xl p-4"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);"
                    >
                        <div>
                            <p class="text-sm font-bold" style="color: var(--gl-text-primary)">
                                AI Activity Generator
                            </p>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                Allow teachers to generate quizzes, seatworks, practicals, and games from lesson materials.
                            </p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                :checked="form.ai_activity_generation_enabled === 'true'"
                                @change="form.ai_activity_generation_enabled = form.ai_activity_generation_enabled === 'true' ? 'false' : 'true'"
                                class="peer sr-only"
                            />
                            <div class="peer h-6 w-11 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-primary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>

                    <div
                        class="flex items-center justify-between rounded-2xl p-4"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);"
                    >
                        <div>
                            <p class="text-sm font-bold" style="color: var(--gl-text-primary)">
                                AI Automatic Essay Evaluation
                            </p>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                Automatically evaluate student open-ended essay answers against teacher-defined rubrics.
                            </p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                :checked="form.ai_essay_grading_enabled === 'true'"
                                @change="form.ai_essay_grading_enabled = form.ai_essay_grading_enabled === 'true' ? 'false' : 'true'"
                                class="peer sr-only"
                            />
                            <div class="peer h-6 w-11 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-secondary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>
                </div>

                <!-- AI Behavior & System Skill Configuration -->
                <div class="rounded-2xl p-5 space-y-4" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <span class="h-2 w-2 rounded-full" style="background: var(--gl-accent)"></span>
                            <h3 class="text-sm font-extrabold" style="color: var(--gl-text-primary)">
                                AI System Skill & Behavior Directives
                            </h3>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="background: rgba(245, 158, 11, 0.15); color: var(--gl-accent)">
                            Prompt Skills
                        </span>
                    </div>
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        Tune how the AI behaves, evaluates student submissions, and generates assessments from lesson materials.
                    </p>

                    <!-- Persona / Skill Preset -->
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-2" style="color: var(--gl-text-secondary)">
                            Evaluation Skill Persona Preset
                        </label>
                        <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-4">
                            <div
                                @click="form.ai_system_skill = 'omniroute_terse'"
                                class="cursor-pointer rounded-xl p-3 transition-all"
                                :style="form.ai_system_skill === 'omniroute_terse'
                                    ? { background: 'rgba(124, 58, 237, 0.12)', border: '2px solid var(--gl-secondary)' }
                                    : { background: 'var(--gl-surface)', border: '1px solid var(--gl-border)' }"
                            >
                                <p class="text-xs font-bold" style="color: var(--gl-text-primary)">OmniRoute Terse</p>
                                <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Concise, direct, zero fluff, strictly criteria-grounded.</p>
                            </div>
                            <div
                                @click="form.ai_system_skill = 'academic_strict'"
                                class="cursor-pointer rounded-xl p-3 transition-all"
                                :style="form.ai_system_skill === 'academic_strict'
                                    ? { background: 'rgba(59, 130, 246, 0.12)', border: '2px solid var(--gl-primary)' }
                                    : { background: 'var(--gl-surface)', border: '1px solid var(--gl-border)' }"
                            >
                                <p class="text-xs font-bold" style="color: var(--gl-text-primary)">Strict Examiner</p>
                                <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Demanding academic standards, precise vocabulary.</p>
                            </div>
                            <div
                                @click="form.ai_system_skill = 'supportive_mentor'"
                                class="cursor-pointer rounded-xl p-3 transition-all"
                                :style="form.ai_system_skill === 'supportive_mentor'
                                    ? { background: 'rgba(16, 185, 129, 0.12)', border: '2px solid var(--gl-success)' }
                                    : { background: 'var(--gl-surface)', border: '1px solid var(--gl-border)' }"
                            >
                                <p class="text-xs font-bold" style="color: var(--gl-text-primary)">Supportive Educator</p>
                                <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Constructive feedback, highlights opportunities.</p>
                            </div>
                            <div
                                @click="form.ai_system_skill = 'custom'"
                                class="cursor-pointer rounded-xl p-3 transition-all"
                                :style="form.ai_system_skill === 'custom'
                                    ? { background: 'rgba(245, 158, 11, 0.12)', border: '2px solid var(--gl-accent)' }
                                    : { background: 'var(--gl-surface)', border: '1px solid var(--gl-border)' }"
                            >
                                <p class="text-xs font-bold" style="color: var(--gl-text-primary)">Custom Directive</p>
                                <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">User-defined instructions specified below.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Grading Instructions -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-secondary)">
                                AI Activity Grading & Checking Instructions
                            </label>
                            <button
                                type="button"
                                @click="form.ai_grading_instructions = 'Evaluate student work strictly against provided rubric criteria or expected answers. Prioritize conceptual understanding and factual accuracy. Accept paraphrased or near-synonym answers if the underlying concept is demonstrated.'"
                                class="text-[11px] font-semibold text-blue-500 hover:underline"
                            >
                                Reset to Default
                            </button>
                        </div>
                        <textarea
                            v-model="form.ai_grading_instructions"
                            rows="3"
                            class="w-full rounded-xl border p-3 text-xs font-medium outline-none"
                            style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            placeholder="Rules and instructions for evaluating student quiz answers, seatworks, essays, and practical rubrics..."
                        ></textarea>
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">
                            Guides how AI checks identification, essay open-ended answers, and scores practical rubric criteria.
                        </p>
                    </div>

                    <!-- Material Generation Instructions -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-secondary)">
                                AI Material Assessment Generation Guidelines
                            </label>
                            <button
                                type="button"
                                @click="form.ai_generation_instructions = 'Create high-quality assessment items directly grounded in the provided reference text. Ensure distractors are plausible and correct answers are factually irrefutable based on the material.'"
                                class="text-[11px] font-semibold text-blue-500 hover:underline"
                            >
                                Reset to Default
                            </button>
                        </div>
                        <textarea
                            v-model="form.ai_generation_instructions"
                            rows="3"
                            class="w-full rounded-xl border p-3 text-xs font-medium outline-none"
                            style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                            placeholder="Guidelines for generating quizzes, seatworks, practicals, and games from uploaded materials..."
                        ></textarea>
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">
                            Directs the question style, difficulty, and fact-checking standards when parsing uploaded teaching materials.
                        </p>
                    </div>

                    <!-- Autonomous AI System Manager (Supervisor) Section -->
                    <div class="rounded-2xl p-5 space-y-5 border" style="background: var(--gl-surface-2); border-color: var(--gl-border)">
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" style="background: rgba(124, 58, 237, 0.15)">
                                    <Bot :size="20" style="color: var(--gl-secondary)" />
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-extrabold" style="color: var(--gl-text-primary)">
                                            Autonomous AI System Manager (Supervisor)
                                        </h3>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[9px] font-black uppercase"
                                            :style="form.ai_manager_enabled === 'true'
                                                ? { background: 'rgba(16, 185, 129, 0.15)', color: 'var(--gl-success)' }
                                                : { background: 'rgba(239, 68, 68, 0.15)', color: 'var(--gl-danger)' }"
                                        >
                                            {{ form.ai_manager_enabled === 'true' ? 'Active' : 'Disabled' }}
                                        </span>
                                    </div>
                                    <p class="text-xs" style="color: var(--gl-text-muted)">
                                        Autonomous background daemon for scheduled auto-grading, disk healing, and anomaly watchdog.
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2">
                                <a
                                    href="/teacher/ai-manager"
                                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                                    style="background: var(--gl-surface); color: var(--gl-text-secondary); border: 1px solid var(--gl-border);"
                                >
                                    <ExternalLink :size="13" />
                                    <span>View Audit Logs</span>
                                </a>
                                <button
                                    type="button"
                                    @click="runManualInspection"
                                    :disabled="runningManualInspection"
                                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                                    style="background: var(--gl-surface); color: var(--gl-primary); border: 1px solid var(--gl-primary);"
                                >
                                    <LoaderCircle v-if="runningManualInspection" :size="13" class="animate-spin" />
                                    <Play v-else :size="13" />
                                    <span>Run Inspection Now</span>
                                </button>
                            </div>
                        </div>

                        <!-- Master Switch & Operating Mode Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Master Toggle -->
                            <div class="rounded-xl p-4 border flex items-center justify-between" style="background: var(--gl-surface); border-color: var(--gl-border)">
                                <div>
                                    <p class="text-xs font-bold" style="color: var(--gl-text-primary)">
                                        Master Supervisor Toggle
                                    </p>
                                    <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                        Halt or permit all autonomous background operations
                                    </p>
                                </div>
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input
                                        type="checkbox"
                                        :checked="form.ai_manager_enabled === 'true'"
                                        @change="form.ai_manager_enabled = form.ai_manager_enabled === 'true' ? 'false' : 'true'"
                                        class="peer sr-only"
                                    />
                                    <div class="peer h-6 w-11 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-primary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                                </label>
                            </div>

                            <!-- Interval Minutes -->
                            <div class="rounded-xl p-4 border" style="background: var(--gl-surface); border-color: var(--gl-border)">
                                <label class="block text-xs font-bold" style="color: var(--gl-text-primary)">
                                    Execution Interval (Minutes)
                                </label>
                                <p class="text-[11px] mb-2" style="color: var(--gl-text-muted)">
                                    Cadence of background scheduler ticks (5 - 1440 min)
                                </p>
                                <input
                                    type="number"
                                    min="5"
                                    max="1440"
                                    v-model="form.ai_manager_interval_minutes"
                                    class="w-full rounded-lg border p-2 text-xs font-semibold outline-none"
                                    style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                />
                            </div>
                        </div>

                        <!-- Mode Selector Cards -->
                        <div>
                            <label class="block text-xs font-bold mb-2" style="color: var(--gl-text-primary)">
                                Operational Mode
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div
                                    @click="form.ai_manager_mode = 'advisory'"
                                    class="cursor-pointer rounded-xl p-3.5 border transition-all"
                                    :style="form.ai_manager_mode === 'advisory'
                                        ? { background: 'rgba(59, 130, 246, 0.08)', borderColor: 'var(--gl-primary)', boxShadow: '0 0 10px var(--gl-primary-glow)' }
                                        : { background: 'var(--gl-surface)', borderColor: 'var(--gl-border)' }"
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-extrabold" style="color: var(--gl-text-primary)">Advisory (Watchdog)</span>
                                        <span v-if="form.ai_manager_mode === 'advisory'" class="h-2 w-2 rounded-full bg-blue-500"></span>
                                    </div>
                                    <p class="text-[10px]" style="color: var(--gl-text-muted)">
                                        Inspects system state and logs alerts to admin without altering database records.
                                    </p>
                                </div>

                                <div
                                    @click="form.ai_manager_mode = 'approval'"
                                    class="cursor-pointer rounded-xl p-3.5 border transition-all"
                                    :style="form.ai_manager_mode === 'approval'
                                        ? { background: 'rgba(245, 158, 11, 0.08)', borderColor: '#f59e0b', boxShadow: '0 0 10px rgba(245, 158, 11, 0.2)' }
                                        : { background: 'var(--gl-surface)', borderColor: 'var(--gl-border)' }"
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-extrabold" style="color: var(--gl-text-primary)">Approval-Required</span>
                                        <span v-if="form.ai_manager_mode === 'approval'" class="h-2 w-2 rounded-full bg-amber-500"></span>
                                    </div>
                                    <p class="text-[10px]" style="color: var(--gl-text-muted)">
                                        Generates action proposals; teacher or superadmin must click apply or dismiss.
                                    </p>
                                </div>

                                <div
                                    @click="form.ai_manager_mode = 'autonomous'"
                                    class="cursor-pointer rounded-xl p-3.5 border transition-all"
                                    :style="form.ai_manager_mode === 'autonomous'
                                        ? { background: 'rgba(16, 185, 129, 0.08)', borderColor: 'var(--gl-success)', boxShadow: '0 0 10px rgba(16, 185, 129, 0.2)' }
                                        : { background: 'var(--gl-surface)', borderColor: 'var(--gl-border)' }"
                                >
                                    <div class="flex items-center justify-between mb-1">
                                        <span class="text-xs font-extrabold" style="color: var(--gl-text-primary)">Autonomous (Full Guardrails)</span>
                                        <span v-if="form.ai_manager_mode === 'autonomous'" class="h-2 w-2 rounded-full bg-emerald-500"></span>
                                    </div>
                                    <p class="text-[10px]" style="color: var(--gl-text-muted)">
                                        Automatically executes whitelisted routines on schedule with immutable audit logs.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Granular Task Toggles -->
                        <div class="space-y-3 pt-2">
                            <label class="block text-xs font-bold" style="color: var(--gl-text-primary)">
                                Sub-Task Execution Permissions
                            </label>

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                                <div class="flex items-center justify-between rounded-xl p-3 border" style="background: var(--gl-surface); border-color: var(--gl-border)">
                                    <div>
                                        <p class="text-xs font-bold" style="color: var(--gl-text-primary)">Auto-Grade</p>
                                        <p class="text-[10px]" style="color: var(--gl-text-muted)">Practical & essays</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input
                                            type="checkbox"
                                            :checked="form.ai_manager_auto_grade === 'true'"
                                            @change="form.ai_manager_auto_grade = form.ai_manager_auto_grade === 'true' ? 'false' : 'true'"
                                            class="peer sr-only"
                                        />
                                        <div class="peer h-5 w-9 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-primary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between rounded-xl p-3 border" style="background: var(--gl-surface); border-color: var(--gl-border)">
                                    <div>
                                        <p class="text-xs font-bold" style="color: var(--gl-text-primary)">Self-Heal Storage</p>
                                        <p class="text-[10px]" style="color: var(--gl-text-muted)">Restore missing disk files</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input
                                            type="checkbox"
                                            :checked="form.ai_manager_self_heal_files === 'true'"
                                            @change="form.ai_manager_self_heal_files = form.ai_manager_self_heal_files === 'true' ? 'false' : 'true'"
                                            class="peer sr-only"
                                        />
                                        <div class="peer h-5 w-9 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-primary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between rounded-xl p-3 border" style="background: var(--gl-surface); border-color: var(--gl-border)">
                                    <div>
                                        <p class="text-xs font-bold" style="color: var(--gl-text-primary)">Anomaly Watchdog</p>
                                        <p class="text-[10px]" style="color: var(--gl-text-muted)">Fast submits & orphans</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input
                                            type="checkbox"
                                            :checked="form.ai_manager_anomaly_detection === 'true'"
                                            @change="form.ai_manager_anomaly_detection = form.ai_manager_anomaly_detection === 'true' ? 'false' : 'true'"
                                            class="peer sr-only"
                                        />
                                        <div class="peer h-5 w-9 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-primary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                                    </label>
                                </div>

                                <div class="flex items-center justify-between rounded-xl p-3 border" style="background: var(--gl-surface); border-color: var(--gl-border)">
                                    <div>
                                        <p class="text-xs font-bold" style="color: var(--gl-text-primary)">Approve Reopens</p>
                                        <p class="text-[10px]" style="color: var(--gl-text-muted)">Game retake requests</p>
                                    </div>
                                    <label class="relative inline-flex cursor-pointer items-center">
                                        <input
                                            type="checkbox"
                                            :checked="form.ai_manager_game_reopen !== 'false'"
                                            @change="form.ai_manager_game_reopen = form.ai_manager_game_reopen === 'false' ? 'true' : 'false'"
                                            class="peer sr-only"
                                        />
                                        <div class="peer h-5 w-9 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-primary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Confidence Floor Slider -->
                        <div class="rounded-xl p-4 border" style="background: var(--gl-surface); border-color: var(--gl-border)">
                            <div class="flex items-center justify-between mb-1.5">
                                <div>
                                    <span class="text-xs font-bold" style="color: var(--gl-text-primary)">
                                        Auto-Approval Confidence Floor
                                    </span>
                                    <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                        Requires AI confidence &ge; this value to approve scores autonomously. Submissions below floor are flagged for teacher review.
                                    </p>
                                </div>
                                <span class="text-sm font-black px-2.5 py-0.5 rounded-lg" style="background: rgba(16, 185, 129, 0.12); color: var(--gl-success)">
                                    {{ Math.round((parseFloat(form.ai_manager_confidence_threshold) || 0.85) * 100) }}%
                                </span>
                            </div>
                            <input
                                type="range"
                                min="0.70"
                                max="0.95"
                                step="0.05"
                                v-model="form.ai_manager_confidence_threshold"
                                class="w-full accent-[var(--gl-primary)] cursor-pointer"
                            />
                            <div class="flex justify-between text-[10px] mt-1" style="color: var(--gl-text-muted)">
                                <span>70% (Permissive)</span>
                                <span>85% (Recommended)</span>
                                <span>95% (Strict)</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dedicated Save Button Inside AI Tab -->
                    <div class="pt-2 flex flex-wrap items-center justify-between gap-3 border-t" style="border-color: var(--gl-border)">
                        <p class="text-[11px]" style="color: var(--gl-text-muted)">
                            Saves API credentials, models, endpoints, and behavioral prompts independently.
                        </p>
                        <button
                            type="button"
                            @click="saveAiSettings"
                            :disabled="savingAi"
                            class="inline-flex items-center gap-1.5 rounded-xl px-5 py-2.5 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary)); box-shadow: 0 0 16px var(--gl-primary-glow);"
                        >
                            <LoaderCircle v-if="savingAi" :size="14" class="animate-spin" />
                            <Save v-else :size="14" :stroke-width="2.2" />
                            <span>{{ savingAi ? 'Saving AI Configuration...' : 'Save AI Configuration' }}</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. Communication & Messaging Tab -->
            <div
                v-show="activeTab === 'communication'"
                class="rounded-3xl p-6 sm:p-7 space-y-6"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border);"
            >
                <div>
                    <h2 class="text-base font-extrabold" style="color: var(--gl-text-primary)">
                        Platform Messenger & Chat Policies
                    </h2>
                    <p class="mt-0.5 text-xs" style="color: var(--gl-text-muted)">
                        System-level permissions governing student chat, teacher messaging, and media transfers.
                    </p>
                </div>

                <div class="space-y-4">
                    <div
                        class="flex items-center justify-between rounded-2xl p-4"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);"
                    >
                        <div>
                            <p class="text-sm font-bold" style="color: var(--gl-text-primary)">
                                Master Messenger Switch
                            </p>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                Enable or completely disable the real-time messenger module across the entire platform.
                            </p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                :checked="form.messenger_system_enabled === 'true'"
                                @change="form.messenger_system_enabled = form.messenger_system_enabled === 'true' ? 'false' : 'true'"
                                class="peer sr-only"
                            />
                            <div class="peer h-6 w-11 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-primary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>

                    <div
                        class="flex items-center justify-between rounded-2xl p-4"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);"
                    >
                        <div>
                            <p class="text-sm font-bold" style="color: var(--gl-text-primary)">
                                Student-to-Student Direct Messaging
                            </p>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                Allow students in the same class/section to send direct messages to each other.
                            </p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                :checked="form.classmate_messaging_enabled === 'true'"
                                @change="form.classmate_messaging_enabled = form.classmate_messaging_enabled === 'true' ? 'false' : 'true'"
                                class="peer sr-only"
                            />
                            <div class="peer h-6 w-11 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-secondary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>

                    <div
                        class="flex items-center justify-between rounded-2xl p-4"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);"
                    >
                        <div>
                            <p class="text-sm font-bold" style="color: var(--gl-text-primary)">
                                Chat File & Image Attachments
                            </p>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                Permit uploading screenshots, homework files, and documents directly in chat threads.
                            </p>
                        </div>
                        <label class="relative inline-flex cursor-pointer items-center">
                            <input
                                type="checkbox"
                                :checked="form.allow_chat_attachments === 'true'"
                                @change="form.allow_chat_attachments = form.allow_chat_attachments === 'true' ? 'false' : 'true'"
                                class="peer sr-only"
                            />
                            <div class="peer h-6 w-11 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-primary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- 4. Gamification & Rules Tab -->
            <div
                v-show="activeTab === 'gamification'"
                class="rounded-3xl p-6 sm:p-7 space-y-6"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border);"
            >
                <div>
                    <h2 class="text-base font-extrabold" style="color: var(--gl-text-primary)">
                        Gamification, XP Tiers & Activity Scoring
                    </h2>
                    <p class="mt-0.5 text-xs" style="color: var(--gl-text-muted)">
                        Configure XP points formula thresholds, maximum attempts, and default activity benchmarks.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Pass Score Threshold (%)
                        </label>
                        <input
                            v-model.number="form.xp_passing_percentage"
                            type="number"
                            min="1"
                            max="100"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Minimum score % to qualify for passing XP points (default: 60%).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Mastery Score Threshold (%)
                        </label>
                        <input
                            v-model.number="form.xp_mastery_percentage"
                            type="number"
                            min="1"
                            max="100"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Score % to unlock top-tier mastery bonus XP (default: 80%).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Mastery XP Award
                        </label>
                        <input
                            v-model.number="form.xp_award_mastery"
                            type="number"
                            min="0"
                            max="1000"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Points given when a student achieves mastery (default: 10 XP).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Passing XP Award
                        </label>
                        <input
                            v-model.number="form.xp_award_pass"
                            type="number"
                            min="0"
                            max="1000"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Points given when a student passes (default: 5 XP).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Attempt Participation XP
                        </label>
                        <input
                            v-model.number="form.xp_award_attempt"
                            type="number"
                            min="0"
                            max="100"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Participation points for attempting an activity (default: 1 XP).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            XP Required Per Level
                        </label>
                        <input
                            v-model.number="form.xp_per_level"
                            type="number"
                            min="10"
                            max="10000"
                            step="10"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Points needed to climb each level rank (default: 100 XP per level).</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Exam Default Max Score
                        </label>
                        <input
                            v-model.number="form.exam_max_score"
                            type="number"
                            min="1"
                            max="1000"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Default maximum possible score for newly created exams.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Practical Max Allowed Attempts
                        </label>
                        <input
                            v-model.number="form.practical_max_attempts"
                            type="number"
                            min="1"
                            max="50"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Default retry limit on student coding / project practicals.</p>
                    </div>
                </div>

                <!-- Per-Level Progression & Secret Rewards Section -->
                <div class="pt-6 border-t" style="border-color: var(--gl-border)">
                    <div class="flex flex-wrap items-center justify-between gap-4 mb-5">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="p-1.5 rounded-lg" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                                    <Gift :size="16" :stroke-width="2.2" />
                                </span>
                                <h3 class="text-sm font-extrabold uppercase tracking-wider" style="color: var(--gl-text-primary)">
                                    Per-Level Student Rewards & Secret Milestones
                                </h3>
                                <span class="rounded-full px-2.5 py-0.5 text-[10px] font-bold" style="background: var(--gl-surface-2); color: var(--gl-text-secondary); border: 1px solid var(--gl-border);">
                                    Levels 1 – {{ sortedRewards.length }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                                Students unlock these secret badges and power-up rewards on their profile upon reaching each level. All rewards stay hidden until unlocked.
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2.5">
                            <div class="relative min-w-[200px]">
                                <Search :size="13" class="absolute left-3 top-1/2 -translate-y-1/2" style="color: var(--gl-text-muted);" />
                                <input
                                    v-model="rewardFilter"
                                    type="text"
                                    placeholder="Filter levels or rewards..."
                                    class="w-full pl-8 pr-3 py-1.5 text-xs rounded-xl border outline-none"
                                    style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                />
                            </div>

                            <button
                                type="button"
                                @click="openRewardPreview(1)"
                                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                                style="background: linear-gradient(135deg, rgba(251, 191, 36, 0.16), rgba(59, 130, 246, 0.12)); border: 1px solid rgba(251, 191, 36, 0.35); color: var(--gl-accent);"
                                title="Preview how students see, unlock, and use rewards on their profile"
                            >
                                <Eye :size="13" :stroke-width="2.2" />
                                <span>Preview Student View</span>
                            </button>

                            <button
                                type="button"
                                @click="resetAllRewardsToDefault"
                                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                                style="background: var(--gl-surface-2); border: 1px solid var(--gl-border); color: var(--gl-text-secondary);"
                                title="Reset all reward names and descriptions back to default game config"
                            >
                                <RotateCcw :size="12" :stroke-width="2.2" />
                                <span>Reset Rewards</span>
                            </button>
                        </div>
                    </div>

                    <!-- Formula summary banner -->
                    <div class="mb-5 rounded-2xl p-3.5 flex flex-wrap items-center justify-between gap-3 text-xs" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                        <div class="flex items-center gap-2 font-medium" style="color: var(--gl-text-secondary);">
                            <Zap :size="14" style="color: var(--gl-primary);" />
                            <span>Progression formula:</span>
                            <code class="px-2 py-0.5 rounded-md font-mono text-[11px] font-bold" style="background: var(--gl-surface); border: 1px solid var(--gl-border); color: var(--gl-text-primary);">
                                Level = floor(XP / {{ form.xp_per_level || 100 }}) + 1
                            </code>
                        </div>
                        <div class="text-[11px]" style="color: var(--gl-text-muted);">
                            Next level requires <strong style="color: var(--gl-text-primary);">{{ form.xp_per_level || 100 }} XP</strong> each step
                        </div>
                    </div>

                    <!-- Rewards Card Grid -->
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <div
                            v-for="item in filteredRewards"
                            :key="item.level"
                            class="rounded-2xl p-4 transition-all space-y-3"
                            style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);"
                        >
                            <!-- Card Header -->
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-flex items-center justify-center rounded-xl px-2.5 py-1 text-xs font-black tracking-wide"
                                        :class="getLevelBadgeClasses(item.level)"
                                    >
                                        LVL {{ item.level }}
                                    </span>
                                    <div>
                                        <div class="text-xs font-extrabold" style="color: var(--gl-text-primary)">
                                            {{ item.title }}
                                        </div>
                                        <div class="text-[10px] font-semibold" style="color: var(--gl-primary)">
                                            {{ xpForLevel(item.level).toLocaleString() }} XP Required
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="openRewardPreview(item.level)"
                                        class="inline-flex items-center gap-1 text-[10px] font-bold transition-colors hover:underline"
                                        style="color: var(--gl-primary);"
                                        title="Preview this reward as a student"
                                    >
                                        <Eye :size="10" />
                                        <span>Preview</span>
                                    </button>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider"
                                        style="background: rgba(168, 85, 247, 0.12); color: #a855f7;"
                                    >
                                        <Lock :size="9" />
                                        Secret
                                    </span>
                                    <button
                                        v-if="isRewardModified(item.level)"
                                        type="button"
                                        @click="resetSingleReward(item.level)"
                                        class="text-[10px] underline transition-colors"
                                        style="color: var(--gl-text-muted);"
                                        title="Reset this reward to default"
                                    >
                                        Reset
                                    </button>
                                </div>
                            </div>

                            <!-- Reward Name Input -->
                            <div v-if="rewardsState[item.level]">
                                <label class="block text-[10px] font-bold uppercase tracking-wider mb-1" style="color: var(--gl-text-secondary)">
                                    Reward Title
                                </label>
                                <input
                                    v-model="rewardsState[item.level].name"
                                    type="text"
                                    required
                                    class="w-full rounded-xl border px-3 py-1.5 text-xs font-semibold outline-none transition-colors"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                    placeholder="e.g. Mystery Item"
                                />
                            </div>

                            <!-- Reward Description Input -->
                            <div v-if="rewardsState[item.level]">
                                <label class="block text-[10px] font-bold uppercase tracking-wider mb-1" style="color: var(--gl-text-secondary)">
                                    Perk Description
                                </label>
                                <textarea
                                    v-model="rewardsState[item.level].description"
                                    rows="2"
                                    class="w-full rounded-xl border px-3 py-1.5 text-xs outline-none transition-colors resize-none"
                                    style="background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-primary);"
                                    placeholder="Description shown to student upon unlocking..."
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="filteredRewards.length === 0"
                        class="text-center py-8 rounded-2xl"
                        style="background: var(--gl-surface-2); border: 1px dashed var(--gl-border); color: var(--gl-text-muted);"
                    >
                        No level rewards match "{{ rewardFilter }}".
                    </div>
                </div>
            </div>

            <!-- 5. Security & System Tab -->
            <div
                v-show="activeTab === 'security'"
                class="rounded-3xl p-6 sm:p-7 space-y-6"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border);"
            >
                <div>
                    <h2 class="text-base font-extrabold" style="color: var(--gl-text-primary)">
                        Security Limits & System Audit
                    </h2>
                    <p class="mt-0.5 text-xs" style="color: var(--gl-text-muted)">
                        Session lifetime duration, brute-force rate-limiting, and audit trail preferences.
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Session Timeout (Minutes)
                        </label>
                        <input
                            v-model.number="form.session_lifetime_minutes"
                            type="number"
                            min="1"
                            max="1440"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Inactivity period before users are automatically logged out.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider mb-1.5" style="color: var(--gl-text-secondary)">
                            Max Failed Login Attempts
                        </label>
                        <input
                            v-model.number="form.max_login_attempts"
                            type="number"
                            min="1"
                            max="20"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary);"
                        />
                        <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Consecutive failed attempts before throttling a user IP.</p>
                    </div>
                </div>

                <div
                    class="flex items-center justify-between rounded-2xl p-4"
                    style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);"
                >
                    <div>
                        <p class="text-sm font-bold" style="color: var(--gl-text-primary)">
                            Audit Trail Activity Logging
                        </p>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            Keep record of logins, password updates, submissions, and admin actions in Activity Logs.
                        </p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input
                            type="checkbox"
                            :checked="form.activity_logging_enabled === 'true'"
                            @change="form.activity_logging_enabled = form.activity_logging_enabled === 'true' ? 'false' : 'true'"
                            class="peer sr-only"
                        />
                        <div class="peer h-6 w-11 rounded-full bg-zinc-300 peer-checked:bg-[var(--gl-primary)] peer-focus:outline-none after:absolute after:top-[2px] after:left-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                    </label>
                </div>
            </div>

            <!-- 6. Diagnostic Environment Tab -->
            <div
                v-show="activeTab === 'environment'"
                class="rounded-3xl p-6 sm:p-7 space-y-6"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border);"
            >
                <div>
                    <h2 class="text-base font-extrabold" style="color: var(--gl-text-primary)">
                        Runtime Environment & Host Specs
                    </h2>
                    <p class="mt-0.5 text-xs" style="color: var(--gl-text-muted)">
                        Technical platform parameters, PHP runtime, and database drivers.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="rounded-2xl p-4" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                        <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">PHP Version</p>
                        <p class="mt-1 font-mono text-base font-black" style="color: var(--gl-text-primary)">{{ environment.php_version }}</p>
                    </div>
                    <div class="rounded-2xl p-4" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                        <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">Laravel Framework</p>
                        <p class="mt-1 font-mono text-base font-black" style="color: var(--gl-text-primary)">v{{ environment.laravel_version }}</p>
                    </div>
                    <div class="rounded-2xl p-4" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                        <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">Environment Mode</p>
                        <p class="mt-1 font-mono text-base font-black uppercase" style="color: var(--gl-primary)">{{ environment.app_env }}</p>
                    </div>
                    <div class="rounded-2xl p-4" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                        <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">Database Driver</p>
                        <p class="mt-1 font-mono text-base font-black uppercase" style="color: var(--gl-secondary)">{{ environment.db_connection }}</p>
                    </div>
                </div>

                <div class="rounded-2xl p-4" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                    <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">Server System Time</p>
                    <p class="mt-1 font-mono text-sm font-semibold" style="color: var(--gl-text-primary)">{{ environment.server_time }}</p>
                </div>
            </div>

            <!-- Bottom Sticky Save Footer -->
            <div
                class="mt-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl p-4"
                style="background: var(--gl-surface); border: 1px solid var(--gl-border);"
            >
                <div class="flex items-center gap-2 text-xs font-medium" style="color: var(--gl-text-muted)">
                    <Info :size="15" />
                    <span>All changes apply globally across active sessions and portals.</span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="showResetModal = true"
                        class="rounded-xl px-4 py-2 text-xs font-bold"
                        style="background: var(--gl-surface-2); color: var(--gl-text-secondary); border: 1px solid var(--gl-border);"
                    >
                        Reset Defaults
                    </button>
                    <button
                        type="submit"
                        :disabled="saving"
                        class="inline-flex items-center gap-1.5 rounded-xl px-5 py-2 text-xs font-bold text-white transition-all hover:scale-105 disabled:opacity-50"
                        style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary)); box-shadow: 0 0 16px var(--gl-primary-glow);"
                    >
                        <LoaderCircle v-if="saving" :size="14" class="animate-spin" />
                        <Save v-else :size="14" :stroke-width="2.2" />
                        <span>{{ saving ? 'Saving Changes...' : 'Save Settings' }}</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Reset Confirmation Modal -->
        <Teleport to="body">
            <div
                v-if="showResetModal"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs"
                @click.self="showResetModal = false"
            >
                <div
                    class="w-full max-w-md rounded-3xl p-6 shadow-2xl"
                    style="
                        background: var(--gl-surface);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <div
                        class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
                        style="background: rgba(245, 158, 11, 0.14)"
                    >
                        <RotateCcw :size="24" style="color: var(--gl-accent)" />
                    </div>

                    <h3 class="text-center text-lg font-extrabold" style="color: var(--gl-text-primary)">
                        Reset Configuration?
                    </h3>
                    <p class="mt-2 text-center text-xs leading-relaxed" style="color: var(--gl-text-muted)">
                        Select whether to reset only the current tab (<strong>{{ currentTabName }}</strong>) or all system configuration back to initial defaults.
                    </p>

                    <div class="mt-6 flex flex-col gap-2">
                        <button
                            type="button"
                            @click="confirmReset(activeTab)"
                            :disabled="resetting"
                            class="w-full rounded-xl py-2.5 text-xs font-bold text-white transition-all hover:scale-[1.02] disabled:opacity-50"
                            style="background: var(--gl-accent)"
                        >
                            Reset {{ currentTabName }} Only
                        </button>
                        <button
                            type="button"
                            @click="confirmReset('all')"
                            :disabled="resetting"
                            class="w-full rounded-xl py-2.5 text-xs font-bold text-white transition-all hover:scale-[1.02] disabled:opacity-50"
                            style="background: var(--gl-danger)"
                        >
                            Reset All Settings to Factory Default
                        </button>
                        <button
                            type="button"
                            @click="showResetModal = false"
                            class="mt-1 w-full rounded-xl py-2.5 text-xs font-bold"
                            style="background: var(--gl-surface-2); color: var(--gl-text-secondary); border: 1px solid var(--gl-border);"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
        <!-- Student Reward Experience Preview Modal -->
        <Teleport to="body">
            <div
                v-if="showRewardPreviewModal && selectedPreviewReward"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                style="background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px)"
                @click.self="showRewardPreviewModal = false"
            >
                <div
                    class="relative w-full max-w-xl overflow-hidden rounded-3xl p-6 shadow-2xl transition-all"
                    style="background: var(--gl-surface); border: 1px solid var(--gl-border);"
                >
                    <!-- Modal Header -->
                    <div class="flex items-start justify-between gap-4 border-b pb-4" style="border-color: var(--gl-border);">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl" style="background: rgba(251, 191, 36, 0.16);">
                                <Gift class="h-5 w-5" style="color: var(--gl-accent);" :stroke-width="2" />
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold" style="color: var(--gl-text-primary);">
                                    Student Reward Live Preview
                                </h3>
                                <p class="text-xs" style="color: var(--gl-text-muted);">
                                    Test exactly how students see, unlock, and redeem this reward on their profile.
                                </p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="showRewardPreviewModal = false"
                            class="rounded-xl p-1.5 transition-colors hover:bg-[rgba(255,255,255,0.08)]"
                            style="color: var(--gl-text-muted);"
                        >
                            <X :size="18" />
                        </button>
                    </div>

                    <!-- Level Selector Carousel -->
                    <div class="py-3 border-b overflow-x-auto flex items-center gap-1.5 scrollbar-none" style="border-color: var(--gl-border);">
                        <button
                            v-for="r in sortedRewards"
                            :key="r.level"
                            type="button"
                            @click="previewLevel = r.level; testClaimed = false"
                            class="shrink-0 px-3 py-1 rounded-xl text-xs font-bold transition-all"
                            :style="previewLevel === r.level ? 'background: var(--gl-primary); color: #fff; box-shadow: 0 0 10px var(--gl-primary-glow);' : 'background: var(--gl-surface-2); color: var(--gl-text-secondary); border: 1px solid var(--gl-border);'"
                        >
                            Lvl {{ r.level }}
                        </button>
                    </div>

                    <div class="py-4 space-y-4">
                        <!-- Mode toggle: Unlocked vs Locked Mystery view -->
                        <div class="flex items-center justify-between text-xs font-semibold">
                            <span style="color: var(--gl-text-secondary)">Preview State:</span>
                            <div class="inline-flex rounded-xl p-0.5" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                                <button
                                    type="button"
                                    @click="previewStateMode = 'unlocked'"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all"
                                    :style="previewStateMode === 'unlocked' ? 'background: var(--gl-surface); color: var(--gl-text-primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : 'color: var(--gl-text-muted);'"
                                >
                                    Unlocked Card
                                </button>
                                <button
                                    type="button"
                                    @click="previewStateMode = 'locked'"
                                    class="px-2.5 py-1 rounded-lg text-xs font-bold transition-all"
                                    :style="previewStateMode === 'locked' ? 'background: var(--gl-surface); color: var(--gl-text-primary); box-shadow: 0 1px 3px rgba(0,0,0,0.1);' : 'color: var(--gl-text-muted);'"
                                >
                                    Locked Mystery
                                </button>
                            </div>
                        </div>

                        <!-- Card Preview Container (Exact replica of Student/Profile.vue) -->
                        <div class="rounded-2xl p-4" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                            <!-- UNLOCKED STATE -->
                            <div
                                v-if="previewStateMode === 'unlocked'"
                                class="gl-reward-in relative overflow-hidden rounded-xl p-4 shadow-sm"
                                style="
                                    background: linear-gradient(135deg, rgba(251, 191, 36, 0.12), rgba(59, 130, 246, 0.08));
                                    border: 1px solid rgba(251, 191, 36, 0.28);
                                "
                            >
                                <div class="mb-2 flex items-center justify-between">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg" style="background: rgba(251, 191, 36, 0.2)">
                                        <Gift class="h-4.5 w-4.5" style="color: var(--gl-accent)" :stroke-width="2" />
                                    </div>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" style="background: rgba(59, 130, 246, 0.12); color: var(--gl-primary);">
                                        Level {{ selectedPreviewReward.level }} · {{ xpForLevel(selectedPreviewReward.level).toLocaleString() }} XP
                                    </span>
                                </div>
                                <p class="text-sm font-semibold" style="color: var(--gl-text-primary)">
                                    {{ selectedPreviewReward.name || 'Untitled Reward' }}
                                </p>
                                <p class="mt-1 text-xs leading-relaxed" style="color: var(--gl-text-secondary)">
                                    {{ selectedPreviewReward.description || 'No description provided.' }}
                                </p>

                                <!-- Interactive Test Claim Button -->
                                <div class="mt-3.5 pt-3 border-t flex items-center justify-between gap-3" style="border-color: rgba(251, 191, 36, 0.2);">
                                    <button
                                        v-if="!testClaimed"
                                        type="button"
                                        @click="simulateClaimReward"
                                        class="w-full rounded-lg px-3 py-2 text-xs font-semibold text-white transition-all hover:scale-[1.02]"
                                        style="
                                            background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary));
                                            box-shadow: 0 0 10px var(--gl-primary-glow);
                                        "
                                    >
                                        {{ testClaiming ? 'Using...' : 'Use Reward (Test)' }}
                                    </button>
                                    <div v-else class="w-full flex items-center justify-between">
                                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] font-semibold" style="background: var(--gl-surface); color: var(--gl-text-muted);">
                                            <Check class="h-3.5 w-3.5" :stroke-width="2.5" />
                                            Used
                                        </span>
                                        <button
                                            type="button"
                                            @click="testClaimed = false"
                                            class="text-[10px] font-bold underline"
                                            style="color: var(--gl-primary);"
                                        >
                                            Reset Test
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- LOCKED MYSTERY STATE -->
                            <div
                                v-else
                                class="flex flex-col items-center justify-center rounded-xl border border-dashed p-6 text-center"
                                style="background: var(--gl-surface); border-color: var(--gl-border);"
                            >
                                <Lock class="mb-1.5 h-6 w-6" style="color: var(--gl-text-muted);" :stroke-width="2" />
                                <p class="text-sm font-bold" style="color: var(--gl-text-muted);">???</p>
                                <p class="mt-0.5 text-xs" style="color: var(--gl-text-muted);">
                                    Reach Level {{ selectedPreviewReward.level }} ({{ xpForLevel(selectedPreviewReward.level).toLocaleString() }} XP) to unlock
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Footer actions: simulate Level-Up modal -->
                    <div class="border-t pt-4 flex flex-wrap items-center justify-between gap-3" style="border-color: var(--gl-border);">
                        <button
                            type="button"
                            @click="triggerSimulationReveal"
                            class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:scale-105"
                            style="background: linear-gradient(135deg, rgba(251, 191, 36, 0.2), rgba(168, 85, 247, 0.2)); border: 1px solid rgba(251, 191, 36, 0.3); color: var(--gl-accent);"
                        >
                            <Sparkles :size="13" />
                            <span>Simulate Level-Up Animation</span>
                        </button>
                        <button
                            type="button"
                            @click="showRewardPreviewModal = false"
                            class="rounded-xl px-4 py-2 text-xs font-bold"
                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);"
                        >
                            Close Preview
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Simulated Level-Up Secret Reward Reveal Modal -->
        <Teleport to="body">
            <div
                v-if="showSimulationReveal && selectedPreviewReward"
                class="fixed inset-0 z-[100] flex items-center justify-center"
                style="background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px)"
                @click.self="showSimulationReveal = false"
            >
                <div
                    class="gl-reveal-pop gl-shine relative mx-4 w-full max-w-md overflow-hidden rounded-3xl p-8 text-center"
                    style="
                        background: linear-gradient(160deg, var(--gl-surface), var(--gl-surface-2));
                        border: 1px solid rgba(251, 191, 36, 0.35);
                        box-shadow: 0 8px 48px rgba(0, 0, 0, 0.35);
                    "
                >
                    <span
                        v-for="(s, i) in previewSparkles"
                        :key="i"
                        class="gl-sparkle absolute rounded-full"
                        :style="{
                            left: s.x + '%',
                            top: s.y + '%',
                            width: s.size + 'px',
                            height: s.size + 'px',
                            background: s.color,
                            animationDelay: s.delay + 's',
                        }"
                    ></span>
                    <div
                        class="gl-pulse-glow mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl"
                        style="
                            background: linear-gradient(135deg, var(--gl-accent), #f59e0b);
                            box-shadow: 0 0 24px var(--gl-accent-glow);
                        "
                    >
                        <Gift class="h-8 w-8 text-white" :stroke-width="2" />
                    </div>
                    <p class="text-xs font-bold tracking-[0.2em] uppercase" style="color: var(--gl-accent)">
                        Secret Reward Unlocked
                    </p>
                    <h2
                        class="mt-1 text-3xl font-extrabold"
                        style="
                            background: linear-gradient(135deg, var(--gl-accent), var(--gl-primary));
                            -webkit-background-clip: text;
                            background-clip: text;
                            color: transparent;
                        "
                    >
                        LEVEL UP!
                    </h2>
                    <p class="mt-2 text-sm font-semibold" style="color: var(--gl-text-primary)">
                        You reached
                        <span class="rounded-full px-2 py-0.5" style="background: rgba(59, 130, 246, 0.12); color: var(--gl-primary);">
                            Level {{ selectedPreviewReward.level }} · {{ selectedPreviewReward.title }}
                        </span>
                    </p>
                    <div class="mt-5 rounded-2xl p-4 text-left" style="background: var(--gl-surface); border: 1px solid rgba(251, 191, 36, 0.25);">
                        <p class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-accent);">
                            Unlocked Secret Perk
                        </p>
                        <p class="mt-1 text-sm font-bold" style="color: var(--gl-text-primary);">
                            {{ selectedPreviewReward.name }}
                        </p>
                        <p class="mt-1 text-xs leading-relaxed" style="color: var(--gl-text-secondary);">
                            {{ selectedPreviewReward.description }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="showSimulationReveal = false"
                        class="mt-6 w-full rounded-xl py-3 text-sm font-extrabold text-white transition-all hover:scale-[1.02]"
                        style="
                            background: linear-gradient(135deg, var(--gl-accent), #d97706);
                            box-shadow: 0 0 20px var(--gl-accent-glow);
                        "
                    >
                        Claim Reward
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    CheckCircle2,
    Cpu,
    Eye,
    EyeOff,
    Gift,
    Globe,
    Info,
    LoaderCircle,
    Lock,
    MessageCircle,
    RotateCcw,
    Save,
    Search,
    Shield,
    Sliders,
    Sparkles,
    Trophy,
    Zap,
    Bot,
    Play,
    ExternalLink,
    HardDrive,
    Cloud,
    Server,
    Check,
    X,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps<{
    settings: Record<string, any>;
    levelRewards?: Array<{
        level: number;
        title: string;
        xp_required: number;
        name: string;
        description: string;
    }>;
    environment: {
        php_version: string;
        laravel_version: string;
        server_time: string;
        app_env: string;
        db_connection: string;
    };
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);
const errorsList = computed(() => {
    const errs = (page.props as any).errors;

    return errs ? Object.values(errs) : [];
});

const activeTab = ref('platform');
const saving = ref(false);
const resetting = ref(false);
const showResetModal = ref(false);
const runningManualInspection = ref(false);

function runManualInspection() {
    runningManualInspection.value = true;
    router.post('/teacher/ai-manager/run', {}, {
        onFinish: () => {
            runningManualInspection.value = false;
        },
    });
}

const tabs = [
    { id: 'platform', name: 'Branding & Ops', icon: Globe },
    { id: 'storage', name: 'Cloud Storage (S3 / R2)', icon: HardDrive },
    { id: 'ai', name: 'AI Configuration', icon: Sparkles },
    { id: 'communication', name: 'Messaging', icon: MessageCircle },
    { id: 'gamification', name: 'XP & Scoring', icon: Trophy },
    { id: 'security', name: 'Security & Limits', icon: Shield },
    { id: 'environment', name: 'Environment', icon: Cpu },
];

const currentTabName = computed(() => {
    return tabs.find((t) => t.id === activeTab.value)?.name ?? 'Tab';
});

const form = reactive<Record<string, any>>({ ...props.settings });

if (form.gemini_model === 'gemini-2.0-flash' || !form.gemini_model) {
    form.gemini_model = 'gemini-1.5-flash';
}

if (!form.omniroute_model) {
    form.omniroute_model = 'agy/gemini-3.7-flash-high';
}

if (!form.omniroute_base_url) {
    form.omniroute_base_url = 'http://admin-omniroute-cec098-100-104-13-10.sslip.io/v1';
}

if (!form.ninerouter_model) {
    form.ninerouter_model = 'auto';
}

if (!form.ninerouter_base_url) {
    form.ninerouter_base_url = 'http://localhost:20128/v1';
}

if (!form.ai_system_skill) {
    form.ai_system_skill = 'omniroute_terse';
}

if (!form.ai_grading_instructions) {
    form.ai_grading_instructions = 'Evaluate student work strictly against provided rubric criteria or expected answers. Prioritize conceptual understanding and factual accuracy. Accept paraphrased or near-synonym answers if the underlying concept is demonstrated.';
}

if (!form.ai_generation_instructions) {
    form.ai_generation_instructions = 'Create high-quality assessment items directly grounded in the provided reference text. Ensure distractors are plausible and correct answers are factually irrefutable based on the material.';
}

if (!form.storage_driver) {
    form.storage_driver = 'local';
}

if (!form.storage_s3_use_path_style) {
    form.storage_s3_use_path_style = 'true';
}

const showS3Secret = ref(false);
const savingStorage = ref(false);
const testingStorage = ref(false);
const storageTestResult = ref<any>(null);

function selectDriver(driver: string) {
    form.storage_driver = driver;

    if (driver === 'r2') {
        if (!form.storage_s3_region || form.storage_s3_region === 'us-east-1') {
            form.storage_s3_region = 'auto';
        }

        form.storage_s3_use_path_style = 'true';
    } else if (driver === 's3') {
        if (!form.storage_s3_region || form.storage_s3_region === 'auto') {
            form.storage_s3_region = 'us-east-1';
        }
    }
}

async function saveStorageSettings() {
    savingStorage.value = true;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const res = await fetch('/teacher/system-settings/storage', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                storage_driver: form.storage_driver,
                storage_s3_key: form.storage_s3_key,
                storage_s3_secret: form.storage_s3_secret,
                storage_s3_bucket: form.storage_s3_bucket,
                storage_s3_region: form.storage_s3_region,
                storage_s3_endpoint: form.storage_s3_endpoint,
                storage_s3_public_url: form.storage_s3_public_url,
                storage_s3_use_path_style: form.storage_s3_use_path_style,
            }),
        });

        const data = await res.json();
        storageTestResult.value = {
            success: data.success ?? true,
            message: data.message || 'Storage settings saved successfully.',
        };
    } catch (err: any) {
        storageTestResult.value = {
            success: false,
            message: err.message || 'Network error while saving storage settings.',
        };
    } finally {
        savingStorage.value = false;
    }
}

async function testStorageConnection() {
    testingStorage.value = true;
    storageTestResult.value = null;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const res = await fetch('/teacher/system-settings/test-storage', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                storage_driver: form.storage_driver,
                storage_s3_key: form.storage_s3_key,
                storage_s3_secret: form.storage_s3_secret,
                storage_s3_bucket: form.storage_s3_bucket,
                storage_s3_region: form.storage_s3_region,
                storage_s3_endpoint: form.storage_s3_endpoint,
                storage_s3_public_url: form.storage_s3_public_url,
                storage_s3_use_path_style: form.storage_s3_use_path_style,
            }),
        });

        const data = await res.json();
        storageTestResult.value = data;
    } catch (err: any) {
        storageTestResult.value = {
            success: false,
            message: err.message || 'Failed to communicate with probe endpoint.',
        };
    } finally {
        testingStorage.value = false;
    }
}

const showGeminiKey = ref(false);
const showOpenAiKey = ref(false);
const showOmniRouteKey = ref(false);
const showNineRouterKey = ref(false);
const testingAi = ref(false);
const testingProvider = ref<string | null>(null);
const testResult = ref<any>(null);
const savingAi = ref(false);
const aiSaveNotice = ref<{ type: 'success' | 'error'; text: string } | null>(null);

const rewardFilter = ref('');
const rewardsState = reactive<
    Record<
        number,
        {
            level: number;
            title: string;
            name: string;
            description: string;
        }
    >
>({});

const defaultRewardsSnapshot = ref<
    Record<number, { name: string; description: string; title: string }>
>({});

function initRewards(list?: typeof props.levelRewards) {
    const source = list || props.levelRewards || [];
    source.forEach((r) => {
        rewardsState[r.level] = {
            level: r.level,
            title: r.title,
            name: r.name,
            description: r.description,
        };

        if (!defaultRewardsSnapshot.value[r.level]) {
            defaultRewardsSnapshot.value[r.level] = {
                title: r.title,
                name: r.name,
                description: r.description,
            };
        }
    });
}
initRewards();

const sortedRewards = computed(() => {
    return Object.values(rewardsState).sort((a, b) => a.level - b.level);
});

const filteredRewards = computed(() => {
    const q = rewardFilter.value.trim().toLowerCase();

    if (!q) {
return sortedRewards.value;
}

    return sortedRewards.value.filter((r) => {
        return (
            r.level.toString().includes(q) ||
            r.title.toLowerCase().includes(q) ||
            r.name.toLowerCase().includes(q) ||
            r.description.toLowerCase().includes(q)
        );
    });
});

function xpForLevel(level: number): number {
    const step = Number(form.xp_per_level) > 0 ? Number(form.xp_per_level) : 100;

    return Math.max(0, level - 1) * step;
}

function resetSingleReward(level: number) {
    const original = defaultRewardsSnapshot.value[level];

    if (original && rewardsState[level]) {
        rewardsState[level].name = original.name;
        rewardsState[level].description = original.description;
    }
}

function resetAllRewardsToDefault() {
    Object.keys(defaultRewardsSnapshot.value).forEach((lvlStr) => {
        const lvl = Number(lvlStr);
        const original = defaultRewardsSnapshot.value[lvl];

        if (original && rewardsState[lvl]) {
            rewardsState[lvl].name = original.name;
            rewardsState[lvl].description = original.description;
        }
    });
}

function getLevelBadgeClasses(level: number): string {
    if (level <= 3) {
        return 'bg-amber-500/15 text-amber-500 border border-amber-500/30';
    } else if (level <= 6) {
        return 'bg-blue-500/15 text-blue-500 border border-blue-500/30';
    } else if (level <= 9) {
        return 'bg-purple-500/15 text-purple-400 border border-purple-500/30';
    } else if (level <= 12) {
        return 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30';
    }

    return 'bg-rose-500/20 text-rose-400 border border-rose-500/40 shadow-sm';
}

const showRewardPreviewModal = ref(false);
const previewLevel = ref(1);
const previewStateMode = ref<'unlocked' | 'locked'>('unlocked');
const testClaiming = ref(false);
const testClaimed = ref(false);
const showSimulationReveal = ref(false);

const selectedPreviewReward = computed(() => {
    return rewardsState[previewLevel.value] || sortedRewards.value[0] || null;
});

function openRewardPreview(level: number) {
    previewLevel.value = level;
    testClaimed.value = false;
    previewStateMode.value = 'unlocked';
    showRewardPreviewModal.value = true;
}

function simulateClaimReward() {
    testClaiming.value = true;
    setTimeout(() => {
        testClaiming.value = false;
        testClaimed.value = true;
    }, 300);
}

function triggerSimulationReveal() {
    showSimulationReveal.value = true;
}

const sparklePalette = ['#FBBF24', '#3B82F6', '#7C3AED', '#10B981', '#F59E0B'];
const previewSparkles = computed(() =>
    Array.from({ length: 12 }, (_, i) => ({
        x: 8 + ((i * 37) % 84),
        y: 4 + ((i * 53) % 82),
        size: 4 + ((i * 7) % 9),
        color: sparklePalette[i % sparklePalette.length],
        delay: (i % 6) * 0.15,
    }))
);

function isRewardModified(level: number): boolean {
    const original = defaultRewardsSnapshot.value[level];

    if (!original || !rewardsState[level]) {
return false;
}

    return (
        rewardsState[level].name !== original.name ||
        rewardsState[level].description !== original.description
    );
}

async function saveAiSettings() {
    savingAi.value = true;
    aiSaveNotice.value = null;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const payload = {
            ai_provider: form.ai_provider,
            gemini_api_key: form.gemini_api_key,
            gemini_model: form.gemini_model,
            openai_api_key: form.openai_api_key,
            openai_model: form.openai_model,
            openai_base_url: form.openai_base_url,
            omniroute_api_key: form.omniroute_api_key,
            omniroute_model: form.omniroute_model,
            omniroute_base_url: form.omniroute_base_url,
            ninerouter_api_key: form.ninerouter_api_key,
            ninerouter_model: form.ninerouter_model,
            ninerouter_base_url: form.ninerouter_base_url,
            ai_request_timeout: form.ai_request_timeout,
            ai_max_source_chars: form.ai_max_source_chars,
            ai_essay_grading_enabled: form.ai_essay_grading_enabled,
            ai_activity_generation_enabled: form.ai_activity_generation_enabled,
            ai_system_skill: form.ai_system_skill,
            ai_grading_instructions: form.ai_grading_instructions,
            ai_generation_instructions: form.ai_generation_instructions,
            ai_manager_enabled: form.ai_manager_enabled,
            ai_manager_mode: form.ai_manager_mode,
            ai_manager_interval_minutes: form.ai_manager_interval_minutes,
            ai_manager_auto_grade: form.ai_manager_auto_grade,
            ai_manager_self_heal_files: form.ai_manager_self_heal_files,
            ai_manager_anomaly_detection: form.ai_manager_anomaly_detection,
            ai_manager_game_reopen: form.ai_manager_game_reopen ?? 'true',
            ai_manager_confidence_threshold: form.ai_manager_confidence_threshold,
        };

        const res = await fetch('/teacher/system-settings/ai', {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (res.ok && data.success) {
            aiSaveNotice.value = {
                type: 'success',
                text: data.message || 'AI configuration and skills saved successfully.',
            };

            if (data.settings) {
                Object.assign(form, data.settings);
            }
        } else {
            const errSummary = data.errors
                ? Object.values(data.errors).flat().join(', ')
                : (data.message || 'Failed to save AI configuration.');
            aiSaveNotice.value = { type: 'error', text: errSummary };
        }
    } catch (e: any) {
        aiSaveNotice.value = {
            type: 'error',
            text: 'Error saving AI configuration: ' + (e.message || 'Network error'),
        };
    } finally {
        savingAi.value = false;
        setTimeout(() => {
            const el = document.getElementById('ai-save-banner');

            if (el) {
el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
        }, 100);
    }
}

async function testAiConnection(provider?: string) {
    testingProvider.value = provider || 'active';
    testingAi.value = true;
    testResult.value = null;

    try {
        const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || '';
        const res = await fetch('/teacher/system-settings/test-ai', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                provider: provider || form.ai_provider,
                gemini_api_key: form.gemini_api_key,
                gemini_model: form.gemini_model,
                openai_api_key: form.openai_api_key,
                openai_model: form.openai_model,
                omniroute_api_key: form.omniroute_api_key,
                omniroute_model: form.omniroute_model,
                omniroute_base_url: form.omniroute_base_url,
                ninerouter_api_key: form.ninerouter_api_key,
                ninerouter_model: form.ninerouter_model,
                ninerouter_base_url: form.ninerouter_base_url,
                ai_request_timeout: form.ai_request_timeout,
            }),
        });

        let data: any = null;

        try {
            data = await res.json();
        } catch {
            const raw = await res.text().catch(() => '');
            data = {
                success: false,
                provider: provider || form.ai_provider,
                message: `Server returned HTTP ${res.status}: ${raw ? raw.slice(0, 160) : 'Invalid server response'}`,
            };
        }

        testResult.value = data;

        if (data.switched_model && data.provider === 'gemini') {
            form.gemini_model = data.switched_model;
        }

        setTimeout(() => {
            const targetId = provider ? `${provider}-section` : 'ai-test-banner';
            const el = document.getElementById(targetId) || document.getElementById('ai-test-banner');

            if (el) {
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }, 120);
    } catch (e: any) {
        testResult.value = {
            success: false,
            provider: provider || form.ai_provider,
            message: 'Failed to execute test request: ' + (e.message || 'Network error'),
        };
    } finally {
        testingAi.value = false;
        testingProvider.value = null;
    }
}

watch(
    () => props.settings,
    (newSettings) => {
        if (newSettings) {
            Object.assign(form, newSettings);
        }
    },
    { deep: true },
);

watch(
    () => props.levelRewards,
    (newList) => {
        if (newList) {
            initRewards(newList);
        }
    },
    { deep: true },
);

function saveSettings() {
    saving.value = true;
    form.level_rewards = { ...rewardsState };
    router.put('/teacher/system-settings', form, {
        preserveScroll: true,
        onSuccess: (page) => {
            const returnedSettings = (page.props as any).settings;

            if (returnedSettings) {
                Object.assign(form, returnedSettings);
            }

            const returnedRewards = (page.props as any).levelRewards;

            if (returnedRewards) {
                initRewards(returnedRewards);
            }
        },
        onFinish: () => {
            saving.value = false;
        },
    });
}

function confirmReset(category: string) {
    resetting.value = true;
    router.post(
        '/teacher/system-settings/reset',
        { category },
        {
            preserveScroll: true,
            onFinish: () => {
                resetting.value = false;
                showResetModal.value = false;
            },
            onSuccess: (page) => {
                // reload page props into form and rewards
                Object.assign(form, (page.props as any).settings || {});
                const returnedRewards = (page.props as any).levelRewards;

                if (returnedRewards) {
                    initRewards(returnedRewards);
                } else if (category === 'gamification' || category === 'all') {
                    resetAllRewardsToDefault();
                }
            },
        },
    );
}
</script>
