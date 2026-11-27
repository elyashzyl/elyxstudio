<template>
    <Head title="School Years" />

    <div
        v-if="flash?.success"
        class="mb-6 rounded-xl px-4 py-3 text-sm font-medium"
        style="
            background: var(--gl-success-bg, #dceee3);
            color: var(--gl-success, #2f7a54);
            border: 1px solid rgba(16, 185, 129, 0.2);
        "
    >
        {{ flash.success }}
    </div>

    <!-- Header -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span
                    class="rounded-md px-2 py-0.5 text-[11px] font-bold tracking-wider uppercase"
                    style="
                        background: rgba(59, 130, 246, 0.12);
                        color: var(--gl-primary);
                    "
                >
                    Academic Calendar
                </span>
                <span
                    v-if="selectedSchoolYearLabel"
                    class="rounded-md px-2 py-0.5 text-[11px] font-bold"
                    style="
                        background: rgba(16, 185, 129, 0.12);
                        color: var(--gl-success, #10b981);
                    "
                >
                    Viewing: {{ selectedSchoolYearLabel }}
                </span>
            </div>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                School Years
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                All existing content is organized under <strong>2026-2027</strong>. When a new school year starts, it begins fresh — and you can move all or selected items from previous years anytime.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button
                v-if="selectedSchoolYearId !== 'all'"
                type="button"
                @click="switchSchoolYearView('all')"
                class="flex items-center gap-1.5 rounded-xl border px-3.5 py-2.5 text-xs font-bold transition-all hover:bg-[var(--gl-surface-2)]"
                style="
                    background: var(--gl-surface);
                    border-color: var(--gl-border);
                    color: var(--gl-text-secondary);
                "
            >
                <Eye class="h-3.5 w-3.5" :stroke-width="2" />
                View All School Years
            </button>
            <button
                type="button"
                @click="openAddModal"
                class="flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-bold text-white shadow-md transition-all hover:scale-[1.02] active:scale-[0.98]"
                style="background: linear-gradient(135deg, var(--gl-primary), #2563eb)"
            >
                <Plus class="h-4 w-4" :stroke-width="2.5" />
                New School Year
            </button>
        </div>
    </div>

    <!-- School Years Cards -->
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <div
            v-for="sy in localSchoolYears"
            :key="sy.id"
            class="relative flex flex-col justify-between overflow-hidden rounded-2xl border p-5 transition-all hover:shadow-md"
            :style="{
                background: 'var(--gl-surface)',
                borderColor: sy.is_current ? 'var(--gl-primary)' : 'var(--gl-border)',
                boxShadow: sy.is_current ? '0 0 0 1px var(--gl-primary)' : 'none',
            }"
        >
            <div>
                <!-- Top Badges & Edit/Delete -->
                <div class="flex items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span
                            v-if="sy.is_current"
                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-extrabold tracking-wide uppercase"
                            style="
                                background: rgba(16, 185, 129, 0.14);
                                color: var(--gl-success, #10b981);
                            "
                        >
                            <CheckCircle2 class="h-3 w-3" :stroke-width="2.5" />
                            Current School Year
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-semibold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-muted);
                            "
                        >
                            {{ sy.is_active ? 'Archive / Ready' : 'Inactive' }}
                        </span>

                        <span
                            v-if="String(selectedSchoolYearId) === String(sy.id)"
                            class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                            style="
                                background: rgba(59, 130, 246, 0.14);
                                color: var(--gl-primary);
                            "
                        >
                            <Eye class="h-3 w-3" :stroke-width="2.2" />
                            Viewing
                        </span>
                    </div>

                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="openEdit(sy)"
                            class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-surface-2)]"
                            style="color: var(--gl-text-secondary)"
                            title="Edit school year"
                        >
                            <Pencil class="h-3.5 w-3.5" :stroke-width="2" />
                        </button>
                        <button
                            v-if="localSchoolYears.length > 1"
                            type="button"
                            @click="confirmDelete(sy)"
                            class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-danger-bg,rgba(239,68,68,0.1))]"
                            style="color: var(--gl-danger, #ef4444)"
                            title="Delete school year"
                        >
                            <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />
                        </button>
                    </div>
                </div>

                <!-- Title & Description -->
                <div class="mt-3 flex items-baseline justify-between gap-2">
                    <h3
                        class="text-xl font-black tracking-tight"
                        style="color: var(--gl-text-primary)"
                    >
                        S.Y. {{ sy.name }}
                    </h3>
                    <span
                        class="text-xs font-bold tabular-nums"
                        style="color: var(--gl-text-muted)"
                    >
                        {{ totalItemsForYear(sy) }} total items
                    </span>
                </div>

                <p
                    v-if="sy.description"
                    class="mt-1 line-clamp-2 text-xs"
                    style="color: var(--gl-text-secondary)"
                >
                    {{ sy.description }}
                </p>

                <!-- Date range -->
                <div
                    class="mt-2.5 flex items-center gap-1.5 text-xs font-medium"
                    style="color: var(--gl-text-muted)"
                >
                    <Calendar class="h-3.5 w-3.5 shrink-0" :stroke-width="2" />
                    <span v-if="sy.start_date || sy.end_date">
                        {{ formatDate(sy.start_date) }} – {{ formatDate(sy.end_date) }}
                    </span>
                    <span v-else class="italic">Full Academic Year</span>
                </div>
            </div>

            <!-- Counts Grid -->
            <div
                class="mt-4 grid grid-cols-3 gap-1.5 rounded-xl p-2.5 text-center"
                style="background: var(--gl-surface-2)"
            >
                <div class="rounded-lg p-1.5" style="background: var(--gl-surface)">
                    <p class="text-xs font-extrabold tabular-nums" style="color: var(--gl-text-primary)">
                        {{ sy.teaching_materials_count ?? 0 }}
                    </p>
                    <p class="text-[10px]" style="color: var(--gl-text-muted)">Materials</p>
                </div>
                <div class="rounded-lg p-1.5" style="background: var(--gl-surface)">
                    <p class="text-xs font-extrabold tabular-nums" style="color: var(--gl-text-primary)">
                        {{ sy.games_count ?? 0 }}
                    </p>
                    <p class="text-[10px]" style="color: var(--gl-text-muted)">Games</p>
                </div>
                <div class="rounded-lg p-1.5" style="background: var(--gl-surface)">
                    <p class="text-xs font-extrabold tabular-nums" style="color: var(--gl-text-primary)">
                        {{ sy.quizzes_count ?? 0 }}
                    </p>
                    <p class="text-[10px]" style="color: var(--gl-text-muted)">Quizzes</p>
                </div>
                <div class="rounded-lg p-1.5" style="background: var(--gl-surface)">
                    <p class="text-xs font-extrabold tabular-nums" style="color: var(--gl-text-primary)">
                        {{ sy.seatworks_count ?? 0 }}
                    </p>
                    <p class="text-[10px]" style="color: var(--gl-text-muted)">Seatworks</p>
                </div>
                <div class="rounded-lg p-1.5" style="background: var(--gl-surface)">
                    <p class="text-xs font-extrabold tabular-nums" style="color: var(--gl-text-primary)">
                        {{ sy.practicals_count ?? 0 }}
                    </p>
                    <p class="text-[10px]" style="color: var(--gl-text-muted)">Practicals</p>
                </div>
                <div class="rounded-lg p-1.5" style="background: var(--gl-surface)">
                    <p class="text-xs font-extrabold tabular-nums" style="color: var(--gl-text-primary)">
                        {{ sy.exams_count ?? 0 }}
                    </p>
                    <p class="text-[10px]" style="color: var(--gl-text-muted)">Exams</p>
                </div>
            </div>

            <!-- Card Action Buttons -->
            <div class="mt-4 flex flex-wrap items-center gap-2">
                <button
                    v-if="String(selectedSchoolYearId) !== String(sy.id)"
                    type="button"
                    @click="switchSchoolYearView(sy.id)"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-bold transition-all hover:bg-[var(--gl-surface-2)]"
                    style="
                        border-color: var(--gl-border);
                        color: var(--gl-text-primary);
                    "
                >
                    <Eye class="h-3.5 w-3.5" :stroke-width="2" />
                    Switch View Here
                </button>
                <button
                    v-if="!sy.is_current"
                    type="button"
                    @click="setAsCurrent(sy)"
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold text-white transition-all hover:scale-[1.01]"
                    style="background: var(--gl-primary)"
                >
                    <CheckCircle2 class="h-3.5 w-3.5" :stroke-width="2" />
                    Set as Current S.Y.
                </button>
                <button
                    v-if="totalItemsForYear(sy) > 0 && localSchoolYears.length > 1"
                    type="button"
                    @click="prepareTransferFrom(sy.id)"
                    class="flex items-center justify-center gap-1 rounded-xl border px-3 py-2 text-xs font-semibold transition-all hover:bg-[var(--gl-surface-2)]"
                    style="
                        border-color: var(--gl-border);
                        color: var(--gl-text-secondary);
                    "
                    title="Move content from this school year to another"
                >
                    <ArrowRightLeft class="h-3.5 w-3.5" :stroke-width="2" />
                    Move Content
                </button>
            </div>
        </div>
    </div>

    <!-- Move / Carry Over Content Section -->
    <div
        ref="transferSectionRef"
        class="mt-8 rounded-2xl border p-6"
        style="
            background: var(--gl-surface);
            border-color: var(--gl-border);
        "
    >
        <div class="flex flex-wrap items-start justify-between gap-4 border-b pb-5" style="border-color: var(--gl-border)">
            <div>
                <div class="flex items-center gap-2">
                    <span
                        class="rounded-md px-2 py-0.5 text-[11px] font-bold tracking-wider uppercase"
                        style="
                            background: rgba(245, 158, 11, 0.14);
                            color: #f59e0b;
                        "
                    >
                        Carry Over Tool
                    </span>
                </div>
                <h2
                    class="mt-1 text-lg font-extrabold tracking-tight sm:text-xl"
                    style="color: var(--gl-text-primary)"
                >
                    Move Content Between School Years
                </h2>
                <p class="mt-0.5 text-xs" style="color: var(--gl-text-muted)">
                    Move everything from a previous school year (like 2026-2027) into a new school year, or select individual materials, games, and assessments to move.
                </p>
            </div>

            <!-- Mode Switcher -->
            <div
                class="inline-flex rounded-xl p-1"
                style="background: var(--gl-surface-2); border: 1px solid var(--gl-border)"
            >
                <button
                    type="button"
                    @click="transferMode = 'selected'"
                    class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all"
                    :style="
                        transferMode === 'selected'
                            ? {
                                  background: 'var(--gl-primary)',
                                  color: '#fff',
                              }
                            : {
                                  color: 'var(--gl-text-secondary)',
                              }
                    "
                >
                    Select Specific Items
                </button>
                <button
                    type="button"
                    @click="transferMode = 'all'"
                    class="rounded-lg px-3.5 py-1.5 text-xs font-bold transition-all"
                    :style="
                        transferMode === 'all'
                            ? {
                                  background: 'var(--gl-primary)',
                                  color: '#fff',
                              }
                            : {
                                  color: 'var(--gl-text-secondary)',
                              }
                    "
                >
                    Move All From School Year
                </button>
            </div>
        </div>

        <!-- Source & Target School Year Selectors -->
        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label
                    class="mb-1.5 block text-xs font-bold"
                    style="color: var(--gl-text-secondary)"
                >
                    From School Year (Source)
                </label>
                <select
                    v-model="sourceSchoolYearId"
                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                    style="
                        background: var(--gl-surface-2);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                    "
                >
                    <option
                        v-for="sy in localSchoolYears"
                        :key="sy.id"
                        :value="sy.id"
                    >
                        S.Y. {{ sy.name }}{{ sy.is_current ? ' (Current)' : '' }} — {{ totalItemsForYear(sy) }} items
                    </option>
                </select>
            </div>

            <div>
                <label
                    class="mb-1.5 block text-xs font-bold"
                    style="color: var(--gl-text-secondary)"
                >
                    To School Year (Target Destination)
                </label>
                <select
                    v-model="targetSchoolYearId"
                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-semibold outline-none"
                    style="
                        background: var(--gl-surface-2);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                    "
                >
                    <option :value="null" disabled>Select target school year...</option>
                    <option
                        v-for="sy in localSchoolYears"
                        :key="sy.id"
                        :value="sy.id"
                        :disabled="sy.id === sourceSchoolYearId"
                    >
                        S.Y. {{ sy.name }}{{ sy.is_current ? ' (Current)' : '' }}
                    </option>
                </select>
                <p
                    v-if="localSchoolYears.length < 2"
                    class="mt-1.5 text-[11px]"
                    style="color: #f59e0b"
                >
                    Tip: Click "+ New School Year" above (for example, <strong>2027-2028</strong>) to create a target school year to move items into.
                </p>
            </div>
        </div>

        <!-- MODE 1: Move All by Category -->
        <div v-if="transferMode === 'all'" class="mt-6">
            <p class="mb-3 text-xs font-bold" style="color: var(--gl-text-secondary)">
                Choose which categories to move from
                <strong>S.Y. {{ sourceYearObj?.name || 'Source' }}</strong>
                to
                <strong>S.Y. {{ targetYearObj?.name || '...' }}</strong>:
            </p>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <label
                    v-for="cat in categoryOptions"
                    :key="cat.id"
                    class="flex cursor-pointer flex-col justify-between rounded-xl border p-3.5 transition-all"
                    :style="
                        selectedCategories.includes(cat.id)
                            ? {
                                  background: 'rgba(59, 130, 246, 0.08)',
                                  borderColor: 'var(--gl-primary)',
                              }
                            : {
                                  background: 'var(--gl-surface-2)',
                                  borderColor: 'var(--gl-border)',
                              }
                    "
                >
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold" style="color: var(--gl-text-primary)">
                            {{ cat.label }}
                        </span>
                        <input
                            v-model="selectedCategories"
                            type="checkbox"
                            :value="cat.id"
                            class="h-4 w-4 rounded"
                            style="accent-color: var(--gl-primary)"
                        />
                    </div>
                    <p class="mt-2 text-lg font-black tabular-nums" style="color: var(--gl-text-primary)">
                        {{ sourceCategoryCount(cat.countKey) }}
                    </p>
                    <p class="text-[10px]" style="color: var(--gl-text-muted)">
                        available in source
                    </p>
                </label>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 rounded-xl p-4" style="background: var(--gl-surface-2)">
                <div class="text-xs" style="color: var(--gl-text-secondary)">
                    Ready to move <strong>{{ totalMoveAllCount }}</strong> item(s) across
                    <strong>{{ selectedCategories.length }}</strong> selected categor{{ selectedCategories.length === 1 ? 'y' : 'ies' }}.
                </div>
                <button
                    type="button"
                    :disabled="!canSubmitMoveAll || transferring"
                    @click="submitMoveAll"
                    class="flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-md transition-all hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-50"
                    style="background: linear-gradient(135deg, var(--gl-primary), #2563eb)"
                >
                    <ArrowRightLeft class="h-4 w-4" :stroke-width="2.2" />
                    {{ transferring ? 'Moving Content...' : `Move All (${totalMoveAllCount}) to S.Y. ${targetYearObj?.name || 'Target'}` }}
                </button>
            </div>
        </div>

        <!-- MODE 2: Select Specific Items -->
        <div v-else class="mt-6">
            <!-- Filters & Search -->
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        v-for="tab in itemTypeTabs"
                        :key="tab.value"
                        type="button"
                        @click="itemTypeFilter = tab.value"
                        class="rounded-full px-3 py-1.5 text-xs font-bold transition-all"
                        :style="
                            itemTypeFilter === tab.value
                                ? {
                                      background: 'var(--gl-primary)',
                                      color: '#fff',
                                  }
                                : {
                                      background: 'var(--gl-surface-2)',
                                      color: 'var(--gl-text-secondary)',
                                      border: '1px solid var(--gl-border)',
                                  }
                        "
                    >
                        {{ tab.label }} ({{ countByTypeInSource(tab.value) }})
                    </button>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div
                        class="flex items-center gap-2 rounded-xl border px-3 py-1.5"
                        style="
                            background: var(--gl-surface-2);
                            border-color: var(--gl-border);
                        "
                    >
                        <Search class="h-3.5 w-3.5" style="color: var(--gl-text-muted)" />
                        <input
                            v-model="itemSearch"
                            type="text"
                            placeholder="Search title or subject..."
                            class="w-44 bg-transparent text-xs outline-none sm:w-56"
                            style="color: var(--gl-text-primary)"
                        />
                    </div>

                    <button
                        type="button"
                        @click="selectAllVisible"
                        class="rounded-xl border px-3 py-1.5 text-xs font-semibold transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="
                            border-color: var(--gl-border);
                            color: var(--gl-text-primary);
                        "
                    >
                        Select Visible ({{ filteredSourceItems.length }})
                    </button>
                    <button
                        v-if="selectedItemKeys.length > 0"
                        type="button"
                        @click="selectedItemKeys = []"
                        class="rounded-xl border px-3 py-1.5 text-xs font-semibold transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="
                            border-color: var(--gl-border);
                            color: var(--gl-text-muted);
                        "
                    >
                        Clear ({{ selectedItemKeys.length }})
                    </button>
                </div>
            </div>

            <!-- Items List -->
            <div
                v-if="filteredSourceItems.length"
                class="max-h-96 overflow-y-auto rounded-xl border"
                style="border-color: var(--gl-border)"
            >
                <label
                    v-for="item in filteredSourceItems"
                    :key="item.key"
                    class="flex cursor-pointer items-center justify-between gap-3 border-b px-4 py-3 transition-colors last:border-b-0 hover:bg-[var(--gl-surface-2)]"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <input
                            v-model="selectedItemKeys"
                            type="checkbox"
                            :value="item.key"
                            class="h-4 w-4 shrink-0 rounded"
                            style="accent-color: var(--gl-primary)"
                        />
                        <span
                            class="shrink-0 rounded-md px-2 py-0.5 text-[10px] font-extrabold uppercase"
                            :style="typeBadgeStyle(item.type)"
                        >
                            {{ item.type_label }}
                        </span>
                        <div class="min-w-0">
                            <p
                                class="truncate text-xs font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ item.title }}
                            </p>
                            <p
                                class="truncate text-[11px]"
                                style="color: var(--gl-text-muted)"
                            >
                                {{ item.meta }}
                                <span v-if="item.quarter_name"> · {{ item.quarter_name }}</span>
                                <span v-if="isSuperadmin && item.teacher_name"> · {{ item.teacher_name }}</span>
                            </p>
                        </div>
                    </div>

                    <span
                        class="shrink-0 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-secondary);
                        "
                    >
                        S.Y. {{ item.school_year_name }}
                    </span>
                </label>
            </div>
            <div
                v-else
                class="rounded-xl border py-10 text-center"
                style="
                    background: var(--gl-surface-2);
                    border-color: var(--gl-border);
                "
            >
                <p class="text-xs font-semibold" style="color: var(--gl-text-secondary)">
                    No matching items found in S.Y. {{ sourceYearObj?.name || 'selected school year' }}.
                </p>
            </div>

            <!-- Action Bar -->
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl p-4" style="background: var(--gl-surface-2)">
                <div class="text-xs" style="color: var(--gl-text-secondary)">
                    <strong>{{ selectedItemKeys.length }}</strong> item(s) selected to move to
                    <strong>S.Y. {{ targetYearObj?.name || '...' }}</strong>
                </div>
                <button
                    type="button"
                    :disabled="!canSubmitMoveSelected || transferring"
                    @click="submitMoveSelected"
                    class="flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs font-bold text-white shadow-md transition-all hover:scale-[1.01] disabled:cursor-not-allowed disabled:opacity-50"
                    style="background: linear-gradient(135deg, var(--gl-primary), #2563eb)"
                >
                    <ArrowRightLeft class="h-4 w-4" :stroke-width="2.2" />
                    {{ transferring ? 'Moving Selected...' : `Move Selected (${selectedItemKeys.length}) Items` }}
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Form (Add / Edit School Year) -->
    <Teleport to="body">
        <div
            v-if="showModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(4px)"
            @click.self="showModal = false"
        >
            <div
                class="w-full max-w-md rounded-2xl border p-6 shadow-2xl transition-all"
                style="
                    background: var(--gl-surface);
                    border-color: var(--gl-border);
                "
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3
                        class="text-base font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ editingSchoolYear ? 'Edit School Year' : 'Create New School Year' }}
                    </h3>
                    <button
                        type="button"
                        @click="showModal = false"
                        class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="color: var(--gl-text-muted)"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <form @submit.prevent="submitSchoolYear" class="space-y-4">
                    <div>
                        <label
                            class="mb-1 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                        >
                            School Year Name *
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            placeholder="e.g. 2027-2028"
                            required
                            class="w-full rounded-xl border px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                        <p v-if="formErrors.name" class="mt-1 text-xs text-rose-500">
                            {{ formErrors.name }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                            >
                                Start Date (optional)
                            </label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full rounded-xl border px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                        </div>
                        <div>
                            <label
                                class="mb-1 block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                            >
                                End Date (optional)
                            </label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full rounded-xl border px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                        >
                            Description (optional)
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="2"
                            placeholder="Notes for this academic school year..."
                            class="w-full rounded-xl border px-3.5 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        ></textarea>
                    </div>

                    <div class="space-y-2.5 rounded-xl p-3" style="background: var(--gl-surface-2)">
                        <div class="flex items-start gap-2.5">
                            <input
                                id="sy_is_current"
                                v-model="form.is_current"
                                type="checkbox"
                                class="mt-0.5 h-4 w-4 rounded border-[var(--gl-border)]"
                                style="accent-color: var(--gl-primary)"
                            />
                            <label
                                for="sy_is_current"
                                class="cursor-pointer text-xs"
                                style="color: var(--gl-text-primary)"
                            >
                                <span class="font-bold">Set as Current School Year</span>
                                <span class="block text-[11px]" style="color: var(--gl-text-muted)">
                                    Starts the workspace fresh for this school year. Previous years remain safe and can be viewed or moved anytime.
                                </span>
                            </label>
                        </div>

                        <div class="flex items-center gap-2.5">
                            <input
                                id="sy_is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-[var(--gl-border)]"
                                style="accent-color: var(--gl-primary)"
                            />
                            <label
                                for="sy_is_active"
                                class="cursor-pointer text-xs font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                Active school year
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2">
                        <button
                            type="button"
                            @click="showModal = false"
                            class="rounded-xl border px-4 py-2 text-xs font-semibold transition-colors hover:bg-[var(--gl-surface-2)]"
                            style="
                                border-color: var(--gl-border);
                                color: var(--gl-text-secondary);
                            "
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-xl px-5 py-2 text-xs font-bold text-white shadow-sm transition-all hover:scale-[1.02] active:scale-[0.98]"
                            style="background: var(--gl-primary)"
                        >
                            {{ editingSchoolYear ? 'Save Changes' : 'Create School Year' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Plus,
    Calendar,
    Pencil,
    Trash2,
    X,
    CheckCircle2,
    ArrowRightLeft,
    Search,
    Eye,
} from '@lucide/vue';
import { ref, computed, watch } from 'vue';

const props = defineProps<{
    schoolYears: any[];
    transferableItems: any[];
    currentSchoolYearId?: number | null;
    selectedSchoolYearId?: number | string | null;
    isSuperadmin?: boolean;
}>();

const localSchoolYears = ref<any[]>([...(props.schoolYears || [])]);

watch(
    () => props.schoolYears,
    (val) => {
        localSchoolYears.value = [...(val || [])];
    },
    { deep: true },
);

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const selectedSchoolYearLabel = computed(() => {
    if (props.selectedSchoolYearId === 'all') {
        return 'All School Years';
    }

    const found = localSchoolYears.value.find(
        (s) => Number(s.id) === Number(props.selectedSchoolYearId),
    );

    return found ? `S.Y. ${found.name}` : '';
});

function totalItemsForYear(sy: any): number {
    return (
        (sy.teaching_materials_count ?? 0) +
        (sy.games_count ?? 0) +
        (sy.quizzes_count ?? 0) +
        (sy.seatworks_count ?? 0) +
        (sy.practicals_count ?? 0) +
        (sy.exams_count ?? 0)
    );
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) {
return '—';
}

    try {
        const d = new Date(dateStr);

        return d.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
}

// ---- Add / Edit School Year Modal ----
const showModal = ref(false);
const editingSchoolYear = ref<any>(null);
const form = ref({
    name: '',
    description: '',
    start_date: '',
    end_date: '',
    is_current: false,
    is_active: true,
});
const formErrors = ref<any>({});

function suggestNextSchoolYearName(): string {
    const names = localSchoolYears.value
        .map((s) => String(s.name || ''))
        .filter((n) => /^\d{4}-\d{4}$/.test(n))
        .sort();

    if (names.length > 0) {
        const last = names[names.length - 1];
        const parts = last.split('-').map(Number);

        if (parts.length === 2 && !isNaN(parts[0]) && !isNaN(parts[1])) {
            return `${parts[0] + 1}-${parts[1] + 1}`;
        }
    }

    return '2027-2028';
}

function openAddModal() {
    editingSchoolYear.value = null;
    form.value = {
        name: suggestNextSchoolYearName(),
        description: '',
        start_date: '',
        end_date: '',
        is_current: false,
        is_active: true,
    };
    formErrors.value = {};
    showModal.value = true;
}

function openEdit(sy: any) {
    editingSchoolYear.value = sy;
    form.value = {
        name: sy.name || '',
        description: sy.description || '',
        start_date: sy.start_date ? String(sy.start_date).substring(0, 10) : '',
        end_date: sy.end_date ? String(sy.end_date).substring(0, 10) : '',
        is_current: Boolean(sy.is_current),
        is_active: Boolean(sy.is_active),
    };
    formErrors.value = {};
    showModal.value = true;
}

function submitSchoolYear() {
    formErrors.value = {};

    if (editingSchoolYear.value) {
        router.put(
            `/teacher/school-years/${editingSchoolYear.value.id}`,
            form.value,
            {
                preserveScroll: true,
                onSuccess: () => {
                    showModal.value = false;
                },
                onError: (errs) => {
                    formErrors.value = errs;
                },
            },
        );
    } else {
        router.post('/teacher/school-years', form.value, {
            preserveScroll: true,
            onSuccess: () => {
                showModal.value = false;
            },
            onError: (errs) => {
                formErrors.value = errs;
            },
        });
    }
}

function setAsCurrent(sy: any) {
    router.post(
        `/teacher/school-years/${sy.id}/set-current`,
        {},
        { preserveScroll: true },
    );
}

function switchSchoolYearView(id: number | 'all') {
    router.post(
        '/teacher/school-years/switch',
        { school_year_id: id },
        { preserveScroll: true },
    );
}

function confirmDelete(sy: any) {
    if (
        confirm(
            `Delete School Year "${sy.name}"? Content linked to this year will not be deleted.`,
        )
    ) {
        router.delete(`/teacher/school-years/${sy.id}`, {
            preserveScroll: true,
        });
    }
}

// ---- Transfer / Move Content Between School Years ----
const transferSectionRef = ref<HTMLElement | null>(null);
const transferMode = ref<'selected' | 'all'>('selected');

const defaultSourceYear = computed(() => {
    const y2026 = localSchoolYears.value.find((s) => s.name === '2026-2027');

    return y2026?.id ?? localSchoolYears.value[0]?.id ?? null;
});

const sourceSchoolYearId = ref<number | null>(defaultSourceYear.value);
const targetSchoolYearId = ref<number | null>(
    localSchoolYears.value.find((s) => s.id !== defaultSourceYear.value)?.id ??
        null,
);

watch(
    localSchoolYears,
    (years) => {
        if (
            !sourceSchoolYearId.value ||
            !years.some((y) => y.id === sourceSchoolYearId.value)
        ) {
            sourceSchoolYearId.value = years[0]?.id ?? null;
        }

        if (
            !targetSchoolYearId.value ||
            targetSchoolYearId.value === sourceSchoolYearId.value
        ) {
            const other = years.find((y) => y.id !== sourceSchoolYearId.value);
            targetSchoolYearId.value = other?.id ?? null;
        }
    },
    { deep: true },
);

watch(sourceSchoolYearId, (newSource) => {
    selectedItemKeys.value = [];

    if (targetSchoolYearId.value === newSource) {
        const other = localSchoolYears.value.find((y) => y.id !== newSource);
        targetSchoolYearId.value = other?.id ?? null;
    }
});

const sourceYearObj = computed(() =>
    localSchoolYears.value.find((s) => s.id === sourceSchoolYearId.value),
);
const targetYearObj = computed(() =>
    localSchoolYears.value.find((s) => s.id === targetSchoolYearId.value),
);

const categoryOptions = [
    { id: 'materials', label: 'Materials', countKey: 'teaching_materials_count' },
    { id: 'games', label: 'Games', countKey: 'games_count' },
    { id: 'quizzes', label: 'Quizzes', countKey: 'quizzes_count' },
    { id: 'seatworks', label: 'Seatworks', countKey: 'seatworks_count' },
    { id: 'practicals', label: 'Practicals', countKey: 'practicals_count' },
    { id: 'exams', label: 'Exams', countKey: 'exams_count' },
];

const selectedCategories = ref<string[]>([
    'materials',
    'games',
    'quizzes',
    'seatworks',
    'practicals',
    'exams',
]);

function sourceCategoryCount(countKey: string): number {
    if (!sourceYearObj.value) {
return 0;
}

    return Number(sourceYearObj.value[countKey] ?? 0);
}

const totalMoveAllCount = computed(() => {
    return categoryOptions
        .filter((c) => selectedCategories.value.includes(c.id))
        .reduce((sum, c) => sum + sourceCategoryCount(c.countKey), 0);
});

const canSubmitMoveAll = computed(() => {
    return (
        Boolean(sourceSchoolYearId.value) &&
        Boolean(targetSchoolYearId.value) &&
        sourceSchoolYearId.value !== targetSchoolYearId.value &&
        selectedCategories.value.length > 0 &&
        totalMoveAllCount.value > 0
    );
});

const itemTypeFilter = ref<string>('all');
const itemSearch = ref<string>('');
const selectedItemKeys = ref<string[]>([]);
const transferring = ref(false);

const itemTypeTabs = [
    { value: 'all', label: 'All' },
    { value: 'material', label: 'Materials' },
    { value: 'game', label: 'Games' },
    { value: 'quiz', label: 'Quizzes' },
    { value: 'seatwork', label: 'Seatworks' },
    { value: 'practical', label: 'Practicals' },
    { value: 'exam', label: 'Exams' },
];

const itemsInSourceYear = computed(() => {
    return (props.transferableItems || []).filter(
        (item) => Number(item.school_year_id) === Number(sourceSchoolYearId.value),
    );
});

function countByTypeInSource(type: string): number {
    if (type === 'all') {
return itemsInSourceYear.value.length;
}

    return itemsInSourceYear.value.filter((i) => i.type === type).length;
}

const filteredSourceItems = computed(() => {
    const q = itemSearch.value.trim().toLowerCase();

    return itemsInSourceYear.value.filter((item) => {
        if (itemTypeFilter.value !== 'all' && item.type !== itemTypeFilter.value) {
            return false;
        }

        if (!q) {
return true;
}

        return (
            String(item.title || '')
                .toLowerCase()
                .includes(q) ||
            String(item.meta || '')
                .toLowerCase()
                .includes(q) ||
            String(item.quarter_name || '')
                .toLowerCase()
                .includes(q)
        );
    });
});

function selectAllVisible() {
    const visibleKeys = filteredSourceItems.value.map((i) => i.key);
    const merged = new Set([...selectedItemKeys.value, ...visibleKeys]);
    selectedItemKeys.value = Array.from(merged);
}

const canSubmitMoveSelected = computed(() => {
    return (
        Boolean(targetSchoolYearId.value) &&
        targetSchoolYearId.value !== sourceSchoolYearId.value &&
        selectedItemKeys.value.length > 0
    );
});

function prepareTransferFrom(syId: number) {
    sourceSchoolYearId.value = syId;
    const other = localSchoolYears.value.find((y) => y.id !== syId);

    if (other) {
        targetSchoolYearId.value = other.id;
    }

    transferSectionRef.value?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function submitMoveAll() {
    if (!canSubmitMoveAll.value || transferring.value) {
return;
}

    transferring.value = true;
    router.post(
        '/teacher/school-years/transfer',
        {
            mode: 'all',
            source_school_year_id: sourceSchoolYearId.value,
            target_school_year_id: targetSchoolYearId.value,
            categories: selectedCategories.value,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                transferring.value = false;
            },
        },
    );
}

function submitMoveSelected() {
    if (!canSubmitMoveSelected.value || transferring.value) {
return;
}

    const itemMap = new Map(
        (props.transferableItems || []).map((i) => [i.key, i]),
    );
    const payloadItems = selectedItemKeys.value
        .map((k) => itemMap.get(k))
        .filter(Boolean)
        .map((i: any) => ({ type: i.type, id: i.id }));

    if (!payloadItems.length) {
return;
}

    transferring.value = true;
    router.post(
        '/teacher/school-years/transfer',
        {
            mode: 'selected',
            source_school_year_id: sourceSchoolYearId.value,
            target_school_year_id: targetSchoolYearId.value,
            items: payloadItems,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedItemKeys.value = [];
            },
            onFinish: () => {
                transferring.value = false;
            },
        },
    );
}

function typeBadgeStyle(type: string) {
    switch (type) {
        case 'material':
            return { background: 'rgba(239, 68, 68, 0.12)', color: '#ef4444' };
        case 'game':
            return { background: 'rgba(16, 185, 129, 0.12)', color: '#10b981' };
        case 'quiz':
            return { background: 'rgba(59, 130, 246, 0.12)', color: '#3b82f6' };
        case 'seatwork':
            return { background: 'rgba(245, 158, 11, 0.12)', color: '#f59e0b' };
        case 'practical':
            return { background: 'rgba(139, 92, 246, 0.12)', color: '#8b5cf6' };
        case 'exam':
            return { background: 'rgba(236, 72, 153, 0.12)', color: '#ec4899' };
        default:
            return {
                background: 'var(--gl-surface-2)',
                color: 'var(--gl-text-secondary)',
            };
    }
}
</script>
