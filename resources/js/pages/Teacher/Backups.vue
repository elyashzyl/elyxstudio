<template>
    <Head title="Backups & Disaster Recovery — Admin" />

    <div class="px-4 py-4 sm:px-6 sm:py-6">
        <!-- Flash notifications -->
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

        <!-- Header Banner -->
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
            <div>
                <span
                    class="rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase"
                    style="
                        background: rgba(59, 130, 246, 0.12);
                        color: var(--gl-primary);
                    "
                >
                    System Maintenance
                </span>
                <h1
                    class="mt-1.5 text-2xl font-extrabold tracking-tight sm:text-3xl"
                    style="color: var(--gl-text-primary)"
                >
                    Backups & Disaster Recovery
                </h1>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Create full system archives or atomic database snapshots.
                    Download, upload, and restore with automatic pre-restore
                    safety backups.
                </p>
            </div>

            <!-- Quick Action Buttons -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-primary),
                            var(--gl-secondary)
                        );
                        box-shadow: 0 0 16px var(--gl-primary-glow);
                    "
                >
                    <Plus class="h-4 w-4" :stroke-width="2.5" />
                    <span>Create Backup</span>
                </button>

                <button
                    @click="showUploadModal = true"
                    class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <Upload class="h-3.5 w-3.5" :stroke-width="2" />
                    <span>Upload Backup</span>
                </button>
            </div>
        </div>

        <!-- Stat Cards Row -->
        <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <!-- Total Backups -->
            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: rgba(59, 130, 246, 0.07);
                    border: 1px solid rgba(59, 130, 246, 0.18);
                "
            >
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="text-xs font-medium"
                        style="color: var(--gl-text-secondary)"
                        >Available Backups</span
                    >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg"
                        style="background: rgba(59, 130, 246, 0.15)"
                    >
                        <Database
                            class="h-4 w-4"
                            style="color: var(--gl-primary)"
                            :stroke-width="2"
                        />
                    </div>
                </div>
                <p
                    class="text-2xl font-extrabold tabular-nums"
                    style="color: var(--gl-text-primary)"
                >
                    {{ stats.total_backups }}
                </p>
                <p
                    class="mt-0.5 text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    {{ stats.total_backup_size_formatted }} total archive space
                </p>
            </div>

            <!-- Last Backup Time -->
            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: rgba(16, 185, 129, 0.07);
                    border: 1px solid rgba(16, 185, 129, 0.18);
                "
            >
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="text-xs font-medium"
                        style="color: var(--gl-text-secondary)"
                        >Last Backup</span
                    >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg"
                        style="background: rgba(16, 185, 129, 0.15)"
                    >
                        <Clock
                            class="h-4 w-4"
                            style="color: var(--gl-success)"
                            :stroke-width="2"
                        />
                    </div>
                </div>
                <p
                    class="text-2xl font-extrabold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ stats.last_backup_human }}
                </p>
                <p
                    class="mt-0.5 text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    {{
                        stats.last_backup_at
                            ? formatDate(stats.last_backup_at)
                            : 'No backups created yet'
                    }}
                </p>
            </div>

            <!-- Live Database Size -->
            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: rgba(124, 58, 237, 0.07);
                    border: 1px solid rgba(124, 58, 237, 0.18);
                "
            >
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="text-xs font-medium"
                        style="color: var(--gl-text-secondary)"
                        >Database Size</span
                    >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg"
                        style="background: rgba(124, 58, 237, 0.15)"
                    >
                        <HardDrive
                            class="h-4 w-4"
                            style="color: var(--gl-secondary)"
                            :stroke-width="2"
                        />
                    </div>
                </div>
                <p
                    class="text-2xl font-extrabold tabular-nums"
                    style="color: var(--gl-text-primary)"
                >
                    {{ stats.live_db_size_formatted }}
                </p>
                <p
                    class="mt-0.5 text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    Live SQLite database
                </p>
            </div>

            <!-- Uploads / Storage Size -->
            <div
                class="rounded-2xl p-4 transition-all"
                style="
                    background: rgba(251, 191, 36, 0.08);
                    border: 1px solid rgba(251, 191, 36, 0.2);
                "
            >
                <div class="mb-2 flex items-center justify-between">
                    <span
                        class="text-xs font-medium"
                        style="color: var(--gl-text-secondary)"
                        >Uploads Storage</span
                    >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-lg"
                        style="background: rgba(251, 191, 36, 0.18)"
                    >
                        <FolderArchive
                            class="h-4 w-4"
                            style="color: var(--gl-accent)"
                            :stroke-width="2"
                        />
                    </div>
                </div>
                <p
                    class="text-2xl font-extrabold tabular-nums"
                    style="color: var(--gl-text-primary)"
                >
                    {{ stats.storage_size_formatted }}
                </p>
                <p
                    class="mt-0.5 text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    Materials & student uploads
                </p>
            </div>
        </div>

        <!-- Safety Notice Banner -->
        <div
            class="mb-6 flex items-start gap-3 rounded-2xl p-4 text-xs"
            style="
                background: rgba(59, 130, 246, 0.06);
                border: 1px solid rgba(59, 130, 246, 0.2);
                color: var(--gl-text-secondary);
            "
        >
            <ShieldCheck
                class="mt-0.5 h-4 w-4 shrink-0 text-blue-500"
                :stroke-width="2"
            />
            <div>
                <span class="font-bold text-[var(--gl-text-primary)]"
                    >Automated Safety Rollback:
                </span>
                Every time you trigger a system or database restore, EduPulse
                automatically captures an immediate pre-restore snapshot of the
                current state before applying changes, ensuring you can revert
                at any time.
            </div>
        </div>

        <!-- Backups List Section -->
        <div
            class="overflow-hidden rounded-3xl"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <!-- Table Header / Controls -->
            <div
                class="flex flex-wrap items-center justify-between gap-3 border-b p-4 sm:px-6"
                style="border-color: var(--gl-border)"
            >
                <div class="flex items-center gap-2">
                    <h3
                        class="text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Stored Backup Archives
                    </h3>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                    >
                        {{ filteredBackups.length }}
                    </span>
                </div>

                <div class="relative min-w-[220px]">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2"
                        style="color: var(--gl-text-muted)"
                        :stroke-width="2"
                    />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search backups..."
                        class="w-full rounded-xl border py-1.5 pr-3 pl-9 text-xs transition-all outline-none"
                        style="
                            background: var(--gl-surface-2);
                            border-color: var(--gl-border);
                            color: var(--gl-text-primary);
                        "
                    />
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="!filteredBackups.length" class="py-16 text-center">
                <div
                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl"
                    style="
                        background: var(--gl-surface-2);
                        color: var(--gl-text-muted);
                    "
                >
                    <Database class="h-6 w-6" :stroke-width="1.5" />
                </div>
                <h4
                    class="text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    No backups found
                </h4>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    {{
                        searchQuery
                            ? 'No archives match your search term.'
                            : 'Click "Create Backup" above to generate your first system snapshot.'
                    }}
                </p>
            </div>

            <!-- Table Rows -->
            <div v-else class="divide-y divide-[var(--gl-border)]">
                <div
                    v-for="b in filteredBackups"
                    :key="b.filename"
                    class="gl-fade-in flex flex-col gap-3 p-4 transition-colors hover:bg-[var(--gl-surface-2)] md:flex-row md:items-center md:justify-between md:px-6 md:py-4"
                >
                    <!-- Left: Icon, Name & Type -->
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl"
                            :style="
                                b.type === 'full'
                                    ? {
                                          background:
                                              'rgba(59, 130, 246, 0.12)',
                                          border: '1px solid rgba(59, 130, 246, 0.25)',
                                      }
                                    : {
                                          background:
                                              'rgba(124, 58, 237, 0.12)',
                                          border: '1px solid rgba(124, 58, 237, 0.25)',
                                      }
                            "
                        >
                            <Archive
                                v-if="b.type === 'full'"
                                class="h-5 w-5"
                                style="color: var(--gl-primary)"
                                :stroke-width="2"
                            />
                            <Database
                                v-else
                                class="h-5 w-5"
                                style="color: var(--gl-secondary)"
                                :stroke-width="2"
                            />
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p
                                    class="truncate text-xs font-bold sm:text-sm"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ b.filename }}
                                </p>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[9px] font-bold tracking-wider uppercase"
                                    :style="
                                        b.type === 'full'
                                            ? {
                                                  background:
                                                      'rgba(59, 130, 246, 0.12)',
                                                  color: 'var(--gl-primary)',
                                              }
                                            : {
                                                  background:
                                                      'rgba(124, 58, 237, 0.12)',
                                                  color: 'var(--gl-secondary)',
                                              }
                                    "
                                >
                                    {{
                                        b.type === 'full'
                                            ? 'Full System'
                                            : 'Database Only'
                                    }}
                                </span>
                                <span
                                    v-if="b.synced_to_cloud"
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-bold tracking-wider uppercase"
                                    style="
                                        background: rgba(16, 185, 129, 0.12);
                                        color: var(--gl-success);
                                        border: 1px solid rgba(16, 185, 129, 0.25);
                                    "
                                    title="Synchronized to Cloud Storage"
                                >
                                    <Cloud class="h-2.5 w-2.5" />
                                    <span>Cloud</span>
                                </span>
                            </div>

                            <p
                                class="mt-0.5 text-[11px]"
                                style="color: var(--gl-text-muted)"
                            >
                                Created {{ b.created_at_human }} ·
                                {{ formatDate(b.created_at) }}
                            </p>
                        </div>
                    </div>

                    <!-- Right: Size & Action buttons -->
                    <div
                        class="flex items-center justify-between gap-4 md:justify-end"
                    >
                        <span
                            class="rounded-xl px-2.5 py-1 text-xs font-extrabold tabular-nums"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            {{ b.formatted_size }}
                        </span>

                        <div class="flex items-center gap-1.5">
                            <!-- Cloud Sync -->
                            <button
                                v-if="stats.cloud_enabled && !b.synced_to_cloud"
                                @click="syncToCloud(b)"
                                :disabled="syncingFile === b.filename"
                                class="flex h-8 items-center gap-1.5 rounded-xl px-2.5 text-xs font-semibold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                                style="
                                    background: rgba(59, 130, 246, 0.12);
                                    color: var(--gl-primary);
                                    border: 1px solid rgba(59, 130, 246, 0.25);
                                "
                                :title="'Upload archive to ' + stats.cloud_driver"
                            >
                                <CloudUpload
                                    class="h-3.5 w-3.5"
                                    :class="{
                                        'animate-bounce':
                                            syncingFile === b.filename,
                                    }"
                                    :stroke-width="2"
                                />
                                <span class="hidden sm:inline">Sync Cloud</span>
                            </button>

                            <!-- Download -->
                            <a
                                :href="`/teacher/backups/${encodeURIComponent(b.filename)}/download`"
                                class="flex h-8 w-8 items-center justify-center rounded-xl transition-all hover:scale-105 active:scale-95"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border: 1px solid var(--gl-border);
                                "
                                title="Download backup file"
                            >
                                <Download
                                    class="h-3.5 w-3.5"
                                    :stroke-width="2"
                                />
                            </a>

                            <!-- Restore -->
                            <button
                                @click="askRestore(b)"
                                class="flex h-8 items-center gap-1.5 rounded-xl px-3 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
                                style="
                                    background: rgba(251, 191, 36, 0.12);
                                    color: var(--gl-accent);
                                    border: 1px solid rgba(251, 191, 36, 0.3);
                                "
                                title="Restore this backup"
                            >
                                <RotateCcw
                                    class="h-3 w-3"
                                    :stroke-width="2.5"
                                />
                                <span>Restore</span>
                            </button>

                            <!-- Delete -->
                            <button
                                @click="askDelete(b)"
                                class="flex h-8 w-8 items-center justify-center rounded-xl transition-all hover:scale-105 active:scale-95"
                                style="
                                    background: var(--gl-danger-bg);
                                    color: var(--gl-danger);
                                    border: 1px solid rgba(239, 68, 68, 0.25);
                                "
                                title="Delete backup"
                            >
                                <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Backup Modal -->
    <Teleport to="body">
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(2px)"
            @click.self="showCreateModal = false"
        >
            <div
                class="mx-4 w-full max-w-md rounded-3xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);
                "
            >
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    rgba(59, 130, 246, 0.2),
                                    rgba(124, 58, 237, 0.2)
                                );
                                border: 1px solid rgba(59, 130, 246, 0.3);
                            "
                        >
                            <Database
                                class="h-5 w-5"
                                style="color: var(--gl-primary)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                Create System Backup
                            </h3>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                Archive system database and assets
                            </p>
                        </div>
                    </div>
                    <button
                        @click="showCreateModal = false"
                        class="rounded-lg p-1 text-[var(--gl-text-muted)] hover:bg-[var(--gl-surface-2)]"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <!-- Backup Type Selection -->
                    <div class="space-y-2">
                        <label
                            class="block text-xs font-semibold"
                            style="color: var(--gl-text-secondary)"
                        >
                            Select Backup Scope:
                        </label>
                        <div class="grid grid-cols-1 gap-2.5">
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl p-3.5 transition-all"
                                :style="
                                    createType === 'full'
                                        ? {
                                              background:
                                                  'rgba(59, 130, 246, 0.08)',
                                              border: '1px solid var(--gl-primary)',
                                          }
                                        : {
                                              background: 'var(--gl-surface-2)',
                                              border: '1px solid var(--gl-border)',
                                          }
                                "
                            >
                                <input
                                    type="radio"
                                    v-model="createType"
                                    value="full"
                                    class="mt-1"
                                />
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p
                                            class="text-xs font-bold"
                                            style="
                                                color: var(--gl-text-primary);
                                            "
                                        >
                                            Full System Backup (.zip)
                                        </p>
                                        <span
                                            class="py-0.2 rounded bg-blue-500/20 px-1.5 text-[9px] font-bold text-blue-400"
                                            >Recommended</span
                                        >
                                    </div>
                                    <p
                                        class="mt-0.5 text-[11px] leading-relaxed"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        Includes the database, uploaded teaching
                                        materials, practical submission files,
                                        and metadata manifest.
                                    </p>
                                </div>
                            </label>

                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-2xl p-3.5 transition-all"
                                :style="
                                    createType === 'database'
                                        ? {
                                              background:
                                                  'rgba(124, 58, 237, 0.08)',
                                              border: '1px solid var(--gl-secondary)',
                                          }
                                        : {
                                              background: 'var(--gl-surface-2)',
                                              border: '1px solid var(--gl-border)',
                                          }
                                "
                            >
                                <input
                                    type="radio"
                                    v-model="createType"
                                    value="database"
                                    class="mt-1"
                                />
                                <div class="min-w-0">
                                    <p
                                        class="text-xs font-bold"
                                        style="color: var(--gl-text-primary)"
                                    >
                                        Database Snapshot Only (.sqlite)
                                    </p>
                                    <p
                                        class="mt-0.5 text-[11px] leading-relaxed"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        Fast atomic vacuum snapshot of users,
                                        quizzes, attempts, and logs. Excludes
                                        large file uploads.
                                    </p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-1.5">
                        <label
                            class="block text-xs font-semibold"
                            style="color: var(--gl-text-secondary)"
                        >
                            Optional Notes:
                        </label>
                        <input
                            v-model="createNotes"
                            type="text"
                            placeholder="e.g. Pre-upgrade backup, Quarter 2 final backup..."
                            class="w-full rounded-xl border px-3.5 py-2 text-xs outline-none"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                                color: var(--gl-text-primary);
                            "
                        />
                    </div>

                    <!-- Cloud Sync Option -->
                    <div
                        v-if="stats.cloud_enabled"
                        class="rounded-2xl p-3"
                        style="
                            background: rgba(59, 130, 246, 0.08);
                            border: 1px solid rgba(59, 130, 246, 0.2);
                        "
                    >
                        <label class="flex cursor-pointer items-center gap-2.5">
                            <input
                                type="checkbox"
                                v-model="createSyncToCloud"
                                class="h-4 w-4 rounded accent-blue-600"
                            />
                            <div class="min-w-0 flex-1">
                                <p
                                    class="text-xs font-bold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    Upload copy to {{ stats.cloud_driver }}
                                </p>
                                <p
                                    class="text-[10px]"
                                    style="color: var(--gl-text-muted)"
                                >
                                    Bucket: {{ stats.cloud_bucket || 'configured' }}
                                </p>
                            </div>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="rounded-xl px-4 py-2 text-xs font-semibold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                            "
                        >
                            <span
                                v-if="isSubmitting"
                                class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"
                            ></span>
                            <Plus
                                v-else
                                class="h-3.5 w-3.5"
                                :stroke-width="2.5"
                            />
                            <span>{{
                                isSubmitting ? 'Generating...' : 'Start Backup'
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Upload Backup Modal -->
    <Teleport to="body">
        <div
            v-if="showUploadModal"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(2px)"
            @click.self="showUploadModal = false"
        >
            <div
                class="mx-4 w-full max-w-md rounded-3xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);
                "
            >
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl"
                            style="
                                background: rgba(59, 130, 246, 0.12);
                                border: 1px solid rgba(59, 130, 246, 0.3);
                            "
                        >
                            <Upload
                                class="h-5 w-5"
                                style="color: var(--gl-primary)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                Upload External Backup
                            </h3>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                Import an existing archive or snapshot
                            </p>
                        </div>
                    </div>
                    <button
                        @click="showUploadModal = false"
                        class="rounded-lg p-1 text-[var(--gl-text-muted)] hover:bg-[var(--gl-surface-2)]"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitUpload" class="space-y-4">
                    <div class="space-y-1.5">
                        <label
                            class="block text-xs font-semibold"
                            style="color: var(--gl-text-secondary)"
                        >
                            Select Archive File (.zip, .sqlite, .sql):
                        </label>
                        <input
                            type="file"
                            accept=".zip,.sqlite,.sql"
                            required
                            @change="handleFileUpload"
                            class="w-full rounded-xl border p-2 text-xs outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-blue-600 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                                color: var(--gl-text-primary);
                            "
                        />
                    </div>

                    <label
                        class="flex cursor-pointer items-center gap-2.5 text-xs font-medium"
                        style="color: var(--gl-text-secondary)"
                    >
                        <input
                            type="checkbox"
                            v-model="uploadRestoreImmediately"
                            class="rounded"
                        />
                        <span
                            >Restore system from this file immediately after
                            upload</span
                        >
                    </label>

                    <div class="mt-6 flex justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="showUploadModal = false"
                            class="rounded-xl px-4 py-2 text-xs font-semibold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="!selectedUploadFile || isSubmitting"
                            class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                            style="background: var(--gl-primary)"
                        >
                            <span
                                v-if="isSubmitting"
                                class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"
                            ></span>
                            <Upload
                                v-else
                                class="h-3.5 w-3.5"
                                :stroke-width="2"
                            />
                            <span>{{
                                isSubmitting ? 'Uploading...' : 'Upload Backup'
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Restore Confirmation Modal -->
    <Teleport to="body">
        <div
            v-if="restoreTarget"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(2px)"
            @click.self="restoreTarget = null"
        >
            <div
                class="mx-4 w-full max-w-md rounded-3xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);
                "
            >
                <div class="mb-4 flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-2xl"
                        style="background: rgba(251, 191, 36, 0.15)"
                    >
                        <AlertTriangle
                            class="h-5 w-5"
                            style="color: var(--gl-accent)"
                            :stroke-width="2"
                        />
                    </div>
                    <div>
                        <h3
                            class="text-base font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            Confirm System Restore
                        </h3>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            Database & Asset Replacement
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-2xl p-3.5 text-xs leading-relaxed"
                    style="
                        background: var(--gl-surface-2);
                        color: var(--gl-text-secondary);
                    "
                >
                    You are about to restore the system from:
                    <strong
                        class="mt-1 block font-mono text-[var(--gl-text-primary)]"
                        >{{ restoreTarget.filename }}</strong
                    >
                </div>

                <p
                    class="mt-3 text-xs leading-relaxed"
                    style="color: var(--gl-text-muted)"
                >
                    This will replace the live database (and files if restoring
                    a full system backup). A safety copy of your current
                    database will automatically be generated in your backups
                    folder before proceeding.
                </p>

                <div class="mt-6 flex justify-end gap-2.5">
                    <button
                        @click="restoreTarget = null"
                        class="rounded-xl px-4 py-2 text-xs font-semibold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-secondary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmRestore"
                        :disabled="isRestoring"
                        class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                        style="background: #f59e0b"
                    >
                        <span
                            v-if="isRestoring"
                            class="h-3.5 w-3.5 animate-spin rounded-full border-2 border-white border-t-transparent"
                        ></span>
                        <RotateCcw
                            v-else
                            class="h-3.5 w-3.5"
                            :stroke-width="2.5"
                        />
                        <span>{{
                            isRestoring
                                ? 'Restoring System...'
                                : 'Yes, Restore Now'
                        }}</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
        <div
            v-if="deleteTarget"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(2px)"
            @click.self="deleteTarget = null"
        >
            <div
                class="mx-4 w-full max-w-sm rounded-3xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 12px 36px rgba(0, 0, 0, 0.45);
                "
            >
                <div class="mb-4 flex items-center gap-3">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-2xl"
                        style="background: var(--gl-danger-bg)"
                    >
                        <Trash2
                            class="h-5 w-5"
                            style="color: var(--gl-danger)"
                            :stroke-width="2"
                        />
                    </div>
                    <div>
                        <h3
                            class="text-base font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            Delete Backup?
                        </h3>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            Permanent file removal
                        </p>
                    </div>
                </div>

                <p
                    class="text-xs leading-relaxed"
                    style="color: var(--gl-text-secondary)"
                >
                    Are you sure you want to permanently delete
                    <strong class="font-mono text-[var(--gl-text-primary)]">{{
                        deleteTarget.filename
                    }}</strong
                    >? This action cannot be undone.
                </p>

                <div class="mt-6 flex justify-end gap-2.5">
                    <button
                        @click="deleteTarget = null"
                        class="rounded-xl px-4 py-2 text-xs font-semibold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-secondary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmDelete"
                        class="rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105 active:scale-95"
                        style="background: var(--gl-danger)"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Archive,
    Clock,
    Cloud,
    CloudUpload,
    Database,
    Download,
    FolderArchive,
    HardDrive,
    Plus,
    RotateCcw,
    Search,
    ShieldCheck,
    Trash2,
    Upload,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    backups: any[];
    stats: {
        total_backups: number;
        total_backup_size: number;
        total_backup_size_formatted: string;
        last_backup_at: string | null;
        last_backup_human: string;
        live_db_size: number;
        live_db_size_formatted: string;
        storage_size: number;
        storage_size_formatted: string;
        cloud_enabled?: boolean;
        cloud_driver?: string;
        cloud_bucket?: string | null;
        cloud_auto_sync?: boolean;
    };
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash || {});

const searchQuery = ref('');

const showCreateModal = ref(false);
const createType = ref('full');
const createNotes = ref('');
const createSyncToCloud = ref(props.stats.cloud_enabled ?? false);
const isSubmitting = ref(false);
const syncingFile = ref<string | null>(null);

const showUploadModal = ref(false);
const selectedUploadFile = ref<File | null>(null);
const uploadRestoreImmediately = ref(false);

const restoreTarget = ref<any | null>(null);
const isRestoring = ref(false);

const deleteTarget = ref<any | null>(null);

const filteredBackups = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();

    if (!q) {
return props.backups;
}

    return props.backups.filter(
        (b) =>
            b.filename.toLowerCase().includes(q) ||
            b.type.toLowerCase().includes(q),
    );
});

function formatDate(dateStr?: string | null): string {
    if (!dateStr) {
return '—';
}

    return new Date(dateStr).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function submitCreate() {
    isSubmitting.value = true;
    router.post(
        '/teacher/backups',
        {
            type: createType.value,
            notes: createNotes.value,
            sync_to_cloud: createSyncToCloud.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isSubmitting.value = false;
                showCreateModal.value = false;
                createNotes.value = '';
            },
        },
    );
}

function syncToCloud(b: any) {
    if (syncingFile.value) {
return;
}

    syncingFile.value = b.filename;
    router.post(
        `/teacher/backups/${encodeURIComponent(b.filename)}/sync`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                syncingFile.value = null;
            },
        },
    );
}

function handleFileUpload(e: Event) {
    const target = e.target as HTMLInputElement;

    if (target.files?.length) {
        selectedUploadFile.value = target.files[0];
    }
}

function submitUpload() {
    if (!selectedUploadFile.value) {
return;
}

    isSubmitting.value = true;
    const formData = new FormData();
    formData.append('file', selectedUploadFile.value);

    if (uploadRestoreImmediately.value) {
        formData.append('restore_immediately', '1');
    }

    router.post('/teacher/backups/upload', formData, {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
            showUploadModal.value = false;
            selectedUploadFile.value = null;
            uploadRestoreImmediately.value = false;
        },
    });
}

function askRestore(b: any) {
    restoreTarget.value = b;
}

function confirmRestore() {
    if (!restoreTarget.value) {
return;
}

    isRestoring.value = true;
    router.post(
        '/teacher/backups/restore',
        { filename: restoreTarget.value.filename },
        {
            preserveScroll: true,
            onFinish: () => {
                isRestoring.value = false;
                restoreTarget.value = null;
            },
        },
    );
}

function askDelete(b: any) {
    deleteTarget.value = b;
}

function confirmDelete() {
    if (!deleteTarget.value) {
return;
}

    router.delete(
        `/teacher/backups/${encodeURIComponent(deleteTarget.value.filename)}`,
        {
            preserveScroll: true,
            onFinish: () => {
                deleteTarget.value = null;
            },
        },
    );
}
</script>
