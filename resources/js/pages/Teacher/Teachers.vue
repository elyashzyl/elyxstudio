<template>
    <Head title="Teachers" />

    <div
        v-if="flash?.success"
        class="gl-fade-in mb-6 rounded-xl px-4 py-3 text-sm"
        style="
            background: var(--gl-success-bg);
            color: var(--gl-success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        "
    >
        {{ flash.success }}
    </div>

    <!-- Header -->
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p
                class="text-xs font-semibold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Manage
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Teachers
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ filteredTeachers.length }} registered ·
                {{ totalStudents }} students taught
            </p>
        </div>
        <div class="relative">
            <Search
                class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2"
                :stroke-width="2"
                style="color: var(--gl-text-muted)"
            />
            <input
                v-model="search"
                type="text"
                placeholder="Search teachers..."
                class="rounded-xl border py-2.5 pr-3 pl-9 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border-color: var(--gl-border);
                    width: 210px;
                "
            />
        </div>
    </div>

    <!-- Bulk bar -->
    <div
        v-if="selected.length"
        class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl px-4 py-3"
        style="
            background: var(--gl-surface);
            border: 1px solid var(--gl-border);
        "
    >
        <span
            class="text-xs font-semibold"
            style="color: var(--gl-text-primary)"
            >{{ selected.length }} selected</span
        >
        <button
            @click="bulkDelete"
            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
            style="
                background: var(--gl-danger-bg);
                color: var(--gl-danger);
                border: 1px solid rgba(239, 68, 68, 0.25);
            "
        >
            <Trash2 class="h-3.5 w-3.5" :stroke-width="2" /> Delete
        </button>
        <button
            @click="selected = []"
            class="ml-auto text-xs font-medium transition-colors hover:opacity-80"
            style="color: var(--gl-text-muted)"
        >
            Clear
        </button>
    </div>
    <label
        v-else
        class="mb-4 flex cursor-pointer items-center gap-2 text-xs font-medium"
        style="color: var(--gl-text-muted)"
    >
        <input
            type="checkbox"
            :checked="allChecked"
            @change="toggleAll"
            class="gl-check"
        />
        Select all {{ filteredTeachers.length }} teachers
    </label>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
        <!-- LIST (2/3) -->
        <div class="xl:col-span-2">
            <div
                v-if="!filteredTeachers.length"
                class="gl-glow-card flex flex-col items-center justify-center px-8 py-16 text-center"
            >
                <div
                    class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl"
                    style="background: var(--gl-surface-2)"
                >
                    <Users
                        class="h-6 w-6"
                        style="color: var(--gl-text-muted)"
                        :stroke-width="1.75"
                    />
                </div>
                <p
                    class="text-sm font-medium"
                    style="color: var(--gl-text-secondary)"
                >
                    No teachers found
                </p>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Try a different search term.
                </p>
            </div>

            <template v-else>
                <div
                    class="mb-1 hidden grid-cols-[1fr_90px_90px_110px_40px] items-center gap-3 px-4 pb-2 text-[11px] font-semibold tracking-wider uppercase sm:grid"
                    style="color: var(--gl-text-muted)"
                >
                    <span>Name</span><span class="text-center">Students</span
                    ><span class="text-center">Activities</span
                    ><span class="text-right">Joined</span><span></span>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="t in paged"
                        :key="t.id"
                        @click="selectedId = t.id"
                        class="grid cursor-pointer grid-cols-[auto_1fr_40px] items-center gap-3 rounded-2xl px-4 py-3 transition-all sm:grid-cols-[1fr_90px_90px_110px_40px]"
                        :style="
                            t.id === selectedId
                                ? {
                                      background:
                                          'linear-gradient(135deg, #FF6A45, #F94F2B)',
                                      boxShadow:
                                          '0 4px 18px rgba(249,79,43,0.35)',
                                      border: '1px solid transparent',
                                  }
                                : {
                                      background: 'var(--gl-surface)',
                                      border: '1px solid var(--gl-border)',
                                  }
                        "
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <input
                                type="checkbox"
                                :value="t.id"
                                v-model="selected"
                                @click.stop
                                class="gl-check"
                                :title="'Select ' + t.name"
                            />
                            <div
                                class="relative flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full text-[11px] font-bold text-white"
                                :style="{
                                    background: t.avatar
                                        ? 'transparent'
                                        : 'linear-gradient(135deg, ' +
                                          avatarColor(t.name) +
                                          ', ' +
                                          avatarColor2(t.name) +
                                          ')',
                                }"
                            >
                                <img
                                    v-if="t.avatar"
                                    :src="t.avatar"
                                    :alt="t.name"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ initials(t.name) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold"
                                    :style="{
                                        color:
                                            t.id === selectedId
                                                ? '#fff'
                                                : 'var(--gl-text-primary)',
                                    }"
                                >
                                    {{ t.name }}
                                </p>
                                <p
                                    class="truncate text-[11px]"
                                    :style="{
                                        color:
                                            t.id === selectedId
                                                ? 'rgba(255,255,255,0.75)'
                                                : 'var(--gl-text-muted)',
                                    }"
                                >
                                    {{ t.email }}
                                </p>
                                <div class="mt-1 flex flex-wrap items-center gap-1">
                                    <span
                                        v-if="t.grade_level?.name || t.grade || t.section?.name"
                                        class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10px] font-semibold"
                                        :style="{
                                            background: t.id === selectedId ? 'rgba(255,255,255,0.22)' : 'rgba(124,58,237,0.12)',
                                            color: t.id === selectedId ? '#fff' : 'var(--gl-secondary)',
                                        }"
                                    >
                                        <School class="h-3 w-3" :stroke-width="2" />
                                        {{ t.grade_level?.name || t.grade }} {{ t.section?.name ? '· ' + t.section.name : '' }}
                                    </span>
                                    <span
                                        v-if="t.gender"
                                        class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold"
                                        :style="{
                                            background: t.id === selectedId ? 'rgba(255,255,255,0.18)' : 'var(--gl-surface-2)',
                                            color: t.id === selectedId ? '#fff' : 'var(--gl-text-secondary)',
                                        }"
                                    >
                                        {{ t.gender }}
                                    </span>
                                    <span
                                        v-for="sub in t.subjects"
                                        :key="sub.id"
                                        class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold"
                                        :style="{
                                            background: t.id === selectedId ? 'rgba(255,255,255,0.22)' : 'rgba(59,130,246,0.12)',
                                            color: t.id === selectedId ? '#fff' : 'var(--gl-primary)',
                                        }"
                                    >
                                        {{ sub.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <span
                            class="hidden text-center text-sm font-semibold tabular-nums sm:block"
                            :style="{
                                color:
                                    t.id === selectedId
                                        ? '#fff'
                                        : 'var(--gl-text-primary)',
                            }"
                            >{{ t.students_count ?? 0 }}</span
                        >
                        <span
                            class="hidden text-center text-sm font-semibold tabular-nums sm:block"
                            :style="{
                                color:
                                    t.id === selectedId
                                        ? '#fff'
                                        : 'var(--gl-text-primary)',
                            }"
                            >{{ contentCount(t) }}</span
                        >
                        <span
                            class="hidden truncate text-right text-xs sm:block"
                            :style="{
                                color:
                                    t.id === selectedId
                                        ? 'rgba(255,255,255,0.9)'
                                        : 'var(--gl-text-secondary)',
                            }"
                            >{{ shortDate(t.created_at) }}</span
                        >
                        <div class="relative flex justify-end">
                            <button
                                @click.stop="
                                    openMenu = openMenu === t.id ? null : t.id
                                "
                                title="Actions"
                                class="flex h-8 w-8 items-center justify-center rounded-full transition-all hover:scale-105 hover:bg-[var(--gl-surface-2)]"
                                style="
                                    background: transparent;
                                    color: var(--gl-text-muted);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                <Ellipsis class="h-4 w-4" :stroke-width="2.5" />
                            </button>
                            <div
                                v-if="openMenu === t.id"
                                class="absolute top-9 right-0 z-20 w-40 overflow-hidden rounded-xl py-1"
                                style="
                                    background: var(--gl-surface);
                                    border: 1px solid var(--gl-border);
                                    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
                                "
                            >
                                <Link
                                    :href="`/teacher/teachers/${t.id}`"
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                                    style="color: var(--gl-text-primary)"
                                    ><Eye
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-primary)"
                                        :stroke-width="2"
                                    />
                                    View profile</Link
                                >
                                <button
                                    @click="
                                        openMenu = null;
                                        openEdit(t);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(124,58,237,0.08)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <Pencil
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-secondary)"
                                        :stroke-width="2"
                                    />
                                    Edit
                                </button>
                                <button
                                    @click="
                                        openMenu = null;
                                        confirmDelete(t);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-danger-bg)]"
                                    style="color: var(--gl-danger)"
                                >
                                    <Trash2
                                        class="h-3.5 w-3.5"
                                        :stroke-width="2"
                                    />
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="mt-4 flex flex-wrap items-center justify-between gap-3"
                >
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        {{ rangeLabel }}
                    </p>
                    <div v-if="totalPages > 1" class="flex items-center gap-1">
                        <button
                            @click="pageNum = Math.max(1, pageNum - 1)"
                            :disabled="pageNum === 1"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-bold disabled:opacity-40"
                            style="
                                background: var(--gl-surface);
                                border: 1px solid var(--gl-border);
                                color: var(--gl-text-secondary);
                            "
                        >
                            ‹
                        </button>
                        <button
                            v-for="p in pageNums"
                            :key="p"
                            @click="pageNum = p"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-bold tabular-nums"
                            :style="
                                p === pageNum
                                    ? {
                                          background:
                                              'linear-gradient(135deg, #FF6A45, #F94F2B)',
                                          color: '#fff',
                                      }
                                    : {
                                          background: 'var(--gl-surface)',
                                          border: '1px solid var(--gl-border)',
                                          color: 'var(--gl-text-secondary)',
                                      }
                            "
                        >
                            {{ p }}
                        </button>
                        <button
                            @click="pageNum = Math.min(totalPages, pageNum + 1)"
                            :disabled="pageNum === totalPages"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-bold disabled:opacity-40"
                            style="
                                background: var(--gl-surface);
                                border: 1px solid var(--gl-border);
                                color: var(--gl-text-secondary);
                            "
                        >
                            ›
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- DETAIL (1/3) -->
        <div
            v-if="selectedTeacher"
            class="rounded-2xl p-5 xl:sticky xl:top-6"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <div class="flex items-start gap-3">
                <div
                    class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-bold text-white"
                    :style="{
                        background: selectedTeacher.avatar
                            ? 'transparent'
                            : 'linear-gradient(135deg, ' +
                              avatarColor(selectedTeacher.name) +
                              ', ' +
                              avatarColor2(selectedTeacher.name) +
                              ')',
                    }"
                >
                    <img
                        v-if="selectedTeacher.avatar"
                        :src="selectedTeacher.avatar"
                        :alt="selectedTeacher.name"
                        class="h-full w-full object-cover"
                    />
                    <span v-else>{{ initials(selectedTeacher.name) }}</span>
                </div>
                <div class="min-w-0">
                    <h2
                        class="truncate text-lg font-extrabold tracking-tight"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ selectedTeacher.name }}
                    </h2>
                    <p
                        class="truncate text-xs"
                        style="color: var(--gl-text-muted)"
                    >
                        {{ selectedTeacher.email }}
                    </p>
                </div>
            </div>

            <p
                class="mt-5 mb-2 text-[11px] font-bold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Snapshot
            </p>
            <div class="flex items-center justify-around gap-2">
                <div class="text-center">
                    <div class="relative h-16 w-16">
                        <svg
                            viewBox="0 0 36 36"
                            class="h-full w-full -rotate-90"
                        >
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="3.5"
                                style="stroke: var(--gl-surface-2)"
                            />
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="3.5"
                                stroke-linecap="round"
                                pathLength="100"
                                :stroke-dasharray="`${studentsPct} 100`"
                                style="stroke: #f94f2b"
                            />
                        </svg>
                        <span
                            class="absolute inset-0 flex items-center justify-center text-[11px] font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                            >{{ selectedTeacher.students_count ?? 0 }}</span
                        >
                    </div>
                    <p
                        class="mt-1 text-[11px]"
                        style="color: var(--gl-text-secondary)"
                    >
                        Students
                    </p>
                </div>
                <div class="text-center">
                    <div class="relative h-16 w-16">
                        <svg
                            viewBox="0 0 36 36"
                            class="h-full w-full -rotate-90"
                        >
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="3.5"
                                style="stroke: var(--gl-surface-2)"
                            />
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="3.5"
                                stroke-linecap="round"
                                pathLength="100"
                                :stroke-dasharray="`${contentPct} 100`"
                                style="stroke: var(--gl-primary)"
                            />
                        </svg>
                        <span
                            class="absolute inset-0 flex items-center justify-center text-[11px] font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                            >{{ contentCount(selectedTeacher) }}</span
                        >
                    </div>
                    <p
                        class="mt-1 text-[11px]"
                        style="color: var(--gl-text-secondary)"
                    >
                        Activities
                    </p>
                </div>
            </div>

            <!-- Advisory & Personal Info -->
            <div class="mt-4 rounded-xl p-3" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                <div class="flex items-center justify-between text-xs">
                    <span class="font-medium" style="color: var(--gl-text-muted)">Advisory Class</span>
                    <span class="font-semibold text-right" style="color: var(--gl-text-primary)">
                        {{ (selectedTeacher.grade_level?.name || selectedTeacher.grade) ? ((selectedTeacher.grade_level?.name || selectedTeacher.grade) + (selectedTeacher.section?.name ? ' · ' + selectedTeacher.section.name : '')) : 'None assigned' }}
                    </span>
                </div>
                <div class="mt-2 flex items-center justify-between border-t pt-2 text-xs" style="border-color: var(--gl-border)">
                    <span class="font-medium" style="color: var(--gl-text-muted)">Gender</span>
                    <span class="font-semibold" style="color: var(--gl-text-primary)">
                        {{ selectedTeacher.gender || 'Not specified' }}
                    </span>
                </div>
            </div>

            <!-- Bio if present -->
            <div v-if="selectedTeacher.bio" class="mt-4">
                <p class="mb-1 text-[11px] font-bold tracking-widest uppercase" style="color: var(--gl-text-muted)">
                    Bio / Notes
                </p>
                <p class="rounded-xl p-3 text-xs leading-relaxed italic" style="background: var(--gl-surface-2); color: var(--gl-text-secondary); border: 1px solid var(--gl-border);">
                    "{{ selectedTeacher.bio }}"
                </p>
            </div>

            <!-- Messaging Permissions -->
            <div class="mt-4">
                <p class="mb-1.5 text-[11px] font-bold tracking-widest uppercase" style="color: var(--gl-text-muted)">
                    Communication
                </p>
                <div class="grid grid-cols-2 gap-2 text-center text-xs">
                    <div
                        class="rounded-xl border p-2"
                        :style="{
                            background: selectedTeacher.messenger_enabled !== false ? 'rgba(16,185,129,0.08)' : 'var(--gl-surface-2)',
                            borderColor: selectedTeacher.messenger_enabled !== false ? 'rgba(16,185,129,0.25)' : 'var(--gl-border)',
                            color: selectedTeacher.messenger_enabled !== false ? 'var(--gl-success)' : 'var(--gl-text-muted)',
                        }"
                    >
                        <p class="text-[10px] font-semibold uppercase">Direct Chat</p>
                        <p class="mt-0.5 font-bold">{{ selectedTeacher.messenger_enabled !== false ? 'Enabled' : 'Disabled' }}</p>
                    </div>
                    <div
                        class="rounded-xl border p-2"
                        :style="{
                            background: selectedTeacher.allow_classmate_messaging !== false ? 'rgba(16,185,129,0.08)' : 'var(--gl-surface-2)',
                            borderColor: selectedTeacher.allow_classmate_messaging !== false ? 'rgba(16,185,129,0.25)' : 'var(--gl-border)',
                            color: selectedTeacher.allow_classmate_messaging !== false ? 'var(--gl-success)' : 'var(--gl-text-muted)',
                        }"
                    >
                        <p class="text-[10px] font-semibold uppercase">Class Chat</p>
                        <p class="mt-0.5 font-bold">{{ selectedTeacher.allow_classmate_messaging !== false ? 'Allowed' : 'Restricted' }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <p
                    class="mb-2 text-[11px] font-bold tracking-widest uppercase"
                    style="color: var(--gl-text-muted)"
                >
                    Assigned Subjects
                </p>
                <div v-if="selectedTeacher.subjects?.length" class="flex flex-wrap gap-1.5">
                    <span
                        v-for="sub in selectedTeacher.subjects"
                        :key="sub.id"
                        class="rounded-lg px-2.5 py-1 text-xs font-semibold"
                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);"
                    >
                        {{ sub.name }}
                    </span>
                </div>
                <p v-else class="text-xs italic" style="color: var(--gl-text-muted)">
                    No subjects assigned yet
                </p>
            </div>

            <div class="mt-5">
                <p
                    class="mb-2 text-[11px] font-bold tracking-widest uppercase"
                    style="color: var(--gl-text-muted)"
                >
                    Top content · Items
                </p>
                <div class="space-y-2.5">
                    <div v-for="c in contentMix" :key="c.label">
                        <div class="flex items-center justify-between gap-2">
                            <p
                                class="truncate text-xs font-medium"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ c.label }}
                            </p>
                            <span
                                class="shrink-0 text-[11px] font-bold tabular-nums"
                                style="color: var(--gl-text-secondary)"
                                >{{ c.count }}</span
                            >
                        </div>
                        <div
                            class="mt-1 h-1.5 overflow-hidden rounded-full"
                            style="background: var(--gl-surface-2)"
                        >
                            <div
                                class="h-full rounded-full"
                                :style="{
                                    width: c.pct + '%',
                                    background:
                                        'linear-gradient(90deg, #FF6A45, #F94F2B)',
                                }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="mt-5 space-y-2.5 border-t pt-4"
                style="border-color: var(--gl-border)"
            >
                <div>
                    <p class="text-[11px]" style="color: var(--gl-text-muted)">
                        Email
                    </p>
                    <p
                        class="truncate text-xs font-semibold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ selectedTeacher.email }}
                    </p>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p
                            class="text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Joined
                        </p>
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ shortDate(selectedTeacher.created_at) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="openEdit(selectedTeacher)"
                            class="flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-semibold transition-all hover:bg-[rgba(124,58,237,0.08)]"
                            style="
                                color: var(--gl-secondary);
                                border: 1px solid rgba(124, 58, 237, 0.3);
                            "
                        >
                            <Pencil class="h-3.5 w-3.5" :stroke-width="2" />
                            Edit
                        </button>
                        <Link
                            :href="`/teacher/teachers/${selectedTeacher.id}`"
                            class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-semibold text-white transition-all hover:scale-[1.03]"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 0 12px var(--gl-primary-glow);
                            "
                        >
                            View profile
                            <ArrowRight class="h-3.5 w-3.5" :stroke-width="2" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- click-away for row menu -->
    <div
        v-if="openMenu !== null"
        class="fixed inset-0 z-10"
        style="background: rgba(0, 0, 0, 0.25)"
        @click="openMenu = null"
    ></div>

    <Teleport to="body">
        <div
            v-if="editing"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4"
            style="background: rgba(0, 0, 0, 0.45); backdrop-filter: blur(4px)"
            @click.self="editing = null"
        >
            <div
                class="no-scrollbar flex max-h-[92vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 16px 48px rgba(0, 0, 0, 0.45);
                "
            >
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between border-b px-6 py-4"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl"
                            style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary)"
                        >
                            <Pencil class="h-5 w-5" :stroke-width="2" />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold sm:text-lg"
                                style="color: var(--gl-text-primary)"
                            >
                                Edit Teacher Details
                            </h3>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                Update profile, advisory class, subjects, and communication permissions for
                                <span class="font-semibold" style="color: var(--gl-text-secondary)">{{ editing.name }}</span>
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="editing = null"
                        class="flex h-8 w-8 items-center justify-center rounded-xl transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="color: var(--gl-text-muted)"
                    >
                        <X class="h-4 w-4" :stroke-width="2" />
                    </button>
                </div>

                <!-- Modal Body -->
                <form @submit.prevent="saveEdit" class="flex flex-1 flex-col overflow-y-auto">
                    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                        <!-- Column 1: Personal & Advisory Account -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 pb-1 border-b" style="border-color: var(--gl-border)">
                                <User class="h-4 w-4" style="color: var(--gl-primary)" />
                                <h4 class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-primary)">
                                    Personal & Advisory
                                </h4>
                            </div>

                            <!-- Name -->
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-semibold"
                                    style="color: var(--gl-text-secondary)"
                                >
                                    Full Name <span style="color: var(--gl-danger)">*</span>
                                </label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    required
                                    placeholder="Teacher's full name"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition-all focus:border-[var(--gl-primary)] focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                                    style="
                                        background: var(--gl-surface-2);
                                        color: var(--gl-text-primary);
                                        border-color: var(--gl-border);
                                    "
                                />
                                <p v-if="editError.name" class="mt-1 text-xs" style="color: var(--gl-danger)">
                                    {{ editError.name }}
                                </p>
                            </div>

                            <!-- Email -->
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-semibold"
                                    style="color: var(--gl-text-secondary)"
                                >
                                    Email Address <span style="color: var(--gl-danger)">*</span>
                                </label>
                                <input
                                    v-model="editForm.email"
                                    type="email"
                                    required
                                    placeholder="teacher@example.com"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition-all focus:border-[var(--gl-primary)] focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                                    style="
                                        background: var(--gl-surface-2);
                                        color: var(--gl-text-primary);
                                        border-color: var(--gl-border);
                                    "
                                />
                                <p v-if="editError.email" class="mt-1 text-xs" style="color: var(--gl-danger)">
                                    {{ editError.email }}
                                </p>
                            </div>

                            <!-- Gender -->
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-semibold"
                                    style="color: var(--gl-text-secondary)"
                                >
                                    Gender
                                </label>
                                <select
                                    v-model="editForm.gender"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition-all focus:border-[var(--gl-primary)]"
                                    style="
                                        background: var(--gl-surface-2);
                                        color: var(--gl-text-primary);
                                        border-color: var(--gl-border);
                                    "
                                >
                                    <option value="">Not specified</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <p v-if="editError.gender" class="mt-1 text-xs" style="color: var(--gl-danger)">
                                    {{ editError.gender }}
                                </p>
                            </div>

                            <!-- Advisory Grade & Section -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="mb-1.5 block text-xs font-semibold"
                                        style="color: var(--gl-text-secondary)"
                                    >
                                        Advisory Grade
                                    </label>
                                    <select
                                        v-model="editForm.grade_level_id"
                                        @change="onGradeLevelChange"
                                        class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none transition-all focus:border-[var(--gl-primary)]"
                                        style="
                                            background: var(--gl-surface-2);
                                            color: var(--gl-text-primary);
                                            border-color: var(--gl-border);
                                        "
                                    >
                                        <option :value="null">None (No advisory)</option>
                                        <option
                                            v-for="g in props.gradeLevels || []"
                                            :key="g.id"
                                            :value="g.id"
                                        >
                                            {{ g.name }}
                                        </option>
                                    </select>
                                    <p v-if="editError.grade_level_id" class="mt-1 text-xs" style="color: var(--gl-danger)">
                                        {{ editError.grade_level_id }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="mb-1.5 block text-xs font-semibold"
                                        style="color: var(--gl-text-secondary)"
                                    >
                                        Advisory Section
                                    </label>
                                    <select
                                        v-model="editForm.section_id"
                                        :disabled="!editForm.grade_level_id"
                                        class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none transition-all focus:border-[var(--gl-primary)] disabled:opacity-50"
                                        style="
                                            background: var(--gl-surface-2);
                                            color: var(--gl-text-primary);
                                            border-color: var(--gl-border);
                                        "
                                    >
                                        <option :value="null">None</option>
                                        <option
                                            v-for="s in editFilteredSections"
                                            :key="s.id"
                                            :value="s.id"
                                        >
                                            {{ s.name }}
                                        </option>
                                    </select>
                                    <p v-if="editError.section_id" class="mt-1 text-xs" style="color: var(--gl-danger)">
                                        {{ editError.section_id }}
                                    </p>
                                </div>
                            </div>

                            <!-- Password Reset -->
                            <div class="rounded-xl border p-3" style="background: rgba(0,0,0,0.02); border-color: var(--gl-border)">
                                <label
                                    class="mb-1 flex items-center justify-between text-xs font-semibold"
                                    style="color: var(--gl-text-secondary)"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <Lock class="h-3.5 w-3.5" style="color: var(--gl-text-muted)" />
                                        Reset Password
                                    </span>
                                    <span class="text-[10px] font-normal" style="color: var(--gl-text-muted)">Optional</span>
                                </label>
                                <input
                                    v-model="editForm.password"
                                    type="password"
                                    placeholder="Leave blank to keep current password"
                                    class="w-full rounded-lg border px-3 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                                    style="
                                        background: var(--gl-surface-2);
                                        color: var(--gl-text-primary);
                                        border-color: var(--gl-border);
                                    "
                                />
                                <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">
                                    Minimum 8 characters if changing password.
                                </p>
                                <p v-if="editError.password" class="mt-1 text-xs" style="color: var(--gl-danger)">
                                    {{ editError.password }}
                                </p>
                            </div>
                        </div>

                        <!-- Column 2: Academic Subjects & Messaging Permissions -->
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 pb-1 border-b" style="border-color: var(--gl-border)">
                                <BookOpen class="h-4 w-4" style="color: var(--gl-secondary)" />
                                <h4 class="text-xs font-bold uppercase tracking-wider" style="color: var(--gl-text-primary)">
                                    Subjects & Permissions
                                </h4>
                            </div>

                            <!-- Assigned Subjects -->
                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <label
                                        class="text-xs font-semibold"
                                        style="color: var(--gl-text-secondary)"
                                    >
                                        Assigned Subjects
                                        <span class="ml-1 text-[11px] font-normal" style="color: var(--gl-text-muted)">
                                            ({{ editForm.subject_ids.length }} selected)
                                        </span>
                                    </label>
                                    <div class="flex items-center gap-2 text-[11px]">
                                        <button
                                            type="button"
                                            @click="selectAllSubjects"
                                            class="hover:underline"
                                            style="color: var(--gl-primary)"
                                        >
                                            Select all
                                        </button>
                                        <span style="color: var(--gl-border)">|</span>
                                        <button
                                            type="button"
                                            @click="editForm.subject_ids = []"
                                            class="hover:underline"
                                            style="color: var(--gl-text-muted)"
                                        >
                                            Clear
                                        </button>
                                    </div>
                                </div>
                                <div
                                    v-if="props.subjects?.length"
                                    class="flex max-h-36 flex-wrap gap-1.5 overflow-y-auto rounded-xl border p-2.5"
                                    style="background: var(--gl-surface-2); border-color: var(--gl-border);"
                                >
                                    <button
                                        type="button"
                                        v-for="sub in props.subjects"
                                        :key="sub.id"
                                        @click="toggleSubject(sub.id)"
                                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold transition-all"
                                        :style="
                                            editForm.subject_ids.includes(sub.id)
                                                ? {
                                                      background: 'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                                                      color: '#fff',
                                                      boxShadow: '0 2px 8px var(--gl-primary-glow)',
                                                  }
                                                : {
                                                      background: 'var(--gl-surface)',
                                                      color: 'var(--gl-text-secondary)',
                                                      border: '1px solid var(--gl-border)',
                                                  }
                                        "
                                    >
                                        <Check v-if="editForm.subject_ids.includes(sub.id)" class="h-3 w-3" :stroke-width="3" />
                                        {{ sub.name }}
                                    </button>
                                </div>
                                <p v-else class="text-xs italic" style="color: var(--gl-text-muted)">
                                    No active subjects available to assign.
                                </p>
                                <p v-if="editError.subject_ids" class="mt-1 text-xs" style="color: var(--gl-danger)">
                                    {{ editError.subject_ids }}
                                </p>
                            </div>

                            <!-- Bio / Notes -->
                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <label
                                        class="text-xs font-semibold"
                                        style="color: var(--gl-text-secondary)"
                                    >
                                        Bio / Teacher Notes
                                    </label>
                                    <span class="text-[10px]" style="color: var(--gl-text-muted)">
                                        {{ (editForm.bio || '').length }} / 1000
                                    </span>
                                </div>
                                <textarea
                                    v-model="editForm.bio"
                                    rows="3"
                                    maxlength="1000"
                                    placeholder="Teacher profile bio, academic background, consultation hours..."
                                    class="w-full resize-none rounded-xl border px-3.5 py-2.5 text-xs outline-none transition-all focus:border-[var(--gl-primary)] focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                                    style="
                                        background: var(--gl-surface-2);
                                        color: var(--gl-text-primary);
                                        border-color: var(--gl-border);
                                    "
                                ></textarea>
                                <p v-if="editError.bio" class="mt-1 text-xs" style="color: var(--gl-danger)">
                                    {{ editError.bio }}
                                </p>
                            </div>

                            <!-- Messaging & Communication Permissions -->
                            <div class="space-y-2.5 rounded-xl border p-3" style="background: var(--gl-surface-2); border-color: var(--gl-border)">
                                <p class="text-xs font-bold" style="color: var(--gl-text-primary)">
                                    Communication Settings
                                </p>

                                <!-- Direct Messaging Toggle -->
                                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg p-2 transition-colors hover:bg-[var(--gl-surface)]">
                                    <input
                                        type="checkbox"
                                        v-model="editForm.messenger_enabled"
                                        class="gl-check mt-0.5"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold" style="color: var(--gl-text-primary)">
                                            Direct Messaging
                                        </p>
                                        <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                            Allow students and teachers to message this teacher directly in the chat system.
                                        </p>
                                    </div>
                                </label>

                                <!-- Classmate Messaging Toggle -->
                                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg p-2 transition-colors hover:bg-[var(--gl-surface)]">
                                    <input
                                        type="checkbox"
                                        v-model="editForm.allow_classmate_messaging"
                                        class="gl-check mt-0.5"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold" style="color: var(--gl-text-primary)">
                                            Advisory Classmate Messaging
                                        </p>
                                        <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                            Allow students in this teacher's advisory class to message their classmates.
                                        </p>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="flex items-center justify-between border-t px-6 py-4"
                        style="border-color: var(--gl-border); background: var(--gl-surface-2)"
                    >
                        <p v-if="Object.keys(editError).length" class="text-xs font-medium" style="color: var(--gl-danger)">
                            Please resolve the errors highlighted above.
                        </p>
                        <span v-else></span>

                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                @click="editing = null"
                                class="rounded-xl px-4 py-2.5 text-xs font-semibold transition-colors hover:bg-[var(--gl-surface)]"
                                style="
                                    background: var(--gl-surface);
                                    color: var(--gl-text-primary);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="isSaving"
                                class="flex items-center gap-1.5 rounded-xl px-5 py-2.5 text-xs font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-50"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        var(--gl-primary),
                                        var(--gl-secondary)
                                    );
                                    box-shadow: 0 4px 14px var(--gl-primary-glow);
                                "
                            >
                                <Loader2 v-if="isSaving" class="h-3.5 w-3.5 animate-spin" />
                                <span>{{ isSaving ? 'Saving...' : 'Save Changes' }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Users,
    Eye,
    Pencil,
    Trash2,
    Search,
    ArrowRight,
    Ellipsis,
    School,
    GraduationCap,
    BookOpen,
    MessageSquare,
    Lock,
    Check,
    X,
    User,
    Loader2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const props = defineProps<{
    teachers: any[];
    subjects?: any[];
    gradeLevels?: any[];
    sections?: any[];
}>();

const localTeachers = ref<any[]>([...(props.teachers || [])]);

watch(
    () => props.teachers,
    (val) => {
        localTeachers.value = [...(val || [])];
    },
    { deep: true },
);

const search = ref('');
const openMenu = ref<number | null>(null);

const filteredTeachers = computed(() => {
    if (!search.value) {
return localTeachers.value;
}

    const q = search.value.toLowerCase();

    return localTeachers.value.filter(
        (t: any) =>
            t.name.toLowerCase().includes(q) ||
            t.email.toLowerCase().includes(q) ||
            (t.gradeLevel?.name && t.gradeLevel.name.toLowerCase().includes(q)) ||
            (t.grade && t.grade.toLowerCase().includes(q)) ||
            (t.section?.name && t.section.name.toLowerCase().includes(q)),
    );
});

const totalStudents = computed(() =>
    localTeachers.value.reduce((a, t) => a + (t.students_count ?? 0), 0),
);

function contentCount(t: any): number {
    return (
        (t.quizzes_count ?? 0) +
        (t.exams_count ?? 0) +
        (t.seatworks_count ?? 0) +
        (t.practicals_count ?? 0)
    );
}

// Selection + pagination
const selectedId = ref<number | null>(null);
const pageNum = ref(1);
const PAGE_SIZE = 10;

const selectedTeacher = computed(() => {
    const list = filteredTeachers.value;

    if (!list.length) {
return null;
}

    const found = list.find((t) => t.id === selectedId.value) ?? list[0];

    return found;
});

watch(
    selectedTeacher,
    (t) => {
        if (t) {
selectedId.value = t.id;
}
    },
    { immediate: true },
);

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredTeachers.value.length / PAGE_SIZE)),
);
const paged = computed(() =>
    filteredTeachers.value.slice(
        (pageNum.value - 1) * PAGE_SIZE,
        pageNum.value * PAGE_SIZE,
    ),
);
const rangeLabel = computed(() => {
    const total = filteredTeachers.value.length;

    if (!total) {
return '0 results';
}

    const from = (pageNum.value - 1) * PAGE_SIZE + 1;

    return `${from}–${Math.min(pageNum.value * PAGE_SIZE, total)} of ${total} results`;
});
const pageNums = computed(() => {
    const t = totalPages.value,
        c = pageNum.value;
    const nums = new Set<number>([1, t, c - 1, c, c + 1]);

    return [...nums].filter((n) => n >= 1 && n <= t).sort((a, b) => a - b);
});

watch(search, () => {
    pageNum.value = 1;
    openMenu.value = null;
});
watch(filteredTeachers, () => {
    if (pageNum.value > totalPages.value) {
pageNum.value = totalPages.value;
}
});

// Detail panel data
const maxStudents = computed(() =>
    Math.max(1, ...filteredTeachers.value.map((t) => t.students_count ?? 0)),
);
const maxContent = computed(() =>
    Math.max(1, ...filteredTeachers.value.map((t) => contentCount(t))),
);

const studentsPct = computed(() => {
    if (!selectedTeacher.value) {
return 0;
}

    return Math.round(
        ((selectedTeacher.value.students_count ?? 0) / maxStudents.value) * 100,
    );
});
const contentPct = computed(() => {
    if (!selectedTeacher.value) {
return 0;
}

    return Math.round(
        (contentCount(selectedTeacher.value) / maxContent.value) * 100,
    );
});
const contentMix = computed(() => {
    const t = selectedTeacher.value;

    if (!t) {
return [];
}

    const rows = [
        { label: 'Quizzes', count: t.quizzes_count ?? 0 },
        { label: 'Exams', count: t.exams_count ?? 0 },
        { label: 'Seatworks', count: t.seatworks_count ?? 0 },
        { label: 'Practicals', count: t.practicals_count ?? 0 },
    ];
    const max = Math.max(1, ...rows.map((r) => r.count));

    return rows.map((r) => ({ ...r, pct: Math.round((r.count / max) * 100) }));
});

function initials(name: string) {
    return (name || '')
        .split(' ')
        .map((w: string) => w[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}
function avatarColor(name: string) {
    const c = [
        '#3B82F6',
        '#10B981',
        '#F59E0B',
        '#EF4444',
        '#7C3AED',
        '#EC4899',
        '#06B6D4',
    ];
    let h = 0;
    const n = name || '';

    for (let i = 0; i < n.length; i++) {
        h = n.charCodeAt(i) + ((h << 5) - h);
    }

    return c[Math.abs(h) % c.length];
}
function avatarColor2(name: string) {
    const c = [
        '#2563EB',
        '#059669',
        '#D97706',
        '#DC2626',
        '#6D28D9',
        '#DB2777',
        '#0891B2',
    ];
    let h = 0;
    const n = name || '';

    for (let i = 0; i < n.length; i++) {
        h = n.charCodeAt(i) + ((h << 7) - h);
    }

    return c[Math.abs(h) % c.length];
}
function shortDate(value: string): string {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}

const selected = ref<number[]>([]);
const allChecked = computed(
    () =>
        filteredTeachers.value.length > 0 &&
        selected.value.length === filteredTeachers.value.length,
);

function toggleAll() {
    selected.value = allChecked.value
        ? []
        : filteredTeachers.value.map((t: any) => t.id);
}

function confirmDelete(t: any) {
    if (
        !confirm(
            `Delete teacher "${t.name}" (${t.email})? This cannot be undone.`,
        )
    ) {
return;
}

    openMenu.value = null;
    const backup = [...localTeachers.value];
    localTeachers.value = localTeachers.value.filter((item: any) => item.id !== t.id);
    selected.value = selected.value.filter((id) => id !== t.id);
    router.delete(`/admin/users/${t.id}`, {
        preserveScroll: true,
        onError: () => {
            localTeachers.value = backup;
        },
    });
}

function bulkDelete() {
    if (!selected.value.length) {
return;
}

    if (
        !confirm(
            `Delete ${selected.value.length} teacher(s)? This cannot be undone.`,
        )
    ) {
return;
}

    const toDeleteIds = new Set(selected.value);
    const backup = [...localTeachers.value];
    localTeachers.value = localTeachers.value.filter((t: any) => !toDeleteIds.has(t.id));
    const ids = [...selected.value];
    selected.value = [];
    router.delete('/admin/users/bulk', {
        data: { ids },
        preserveScroll: true,
        onError: () => {
            localTeachers.value = backup;
        },
    });
}

const editing = ref<any>(null);
const isSaving = ref(false);
const editForm = ref({
    name: '',
    email: '',
    gender: '',
    bio: '',
    grade_level_id: null as number | null,
    section_id: null as number | null,
    subject_ids: [] as number[],
    password: '',
    messenger_enabled: true,
    allow_classmate_messaging: true,
});
const editError = ref<any>({});

const editFilteredSections = computed(() => {
    if (!editForm.value.grade_level_id) {
return [];
}

    return (props.sections || []).filter(
        (s: any) => Number(s.grade_level_id) === Number(editForm.value.grade_level_id),
    );
});

function onGradeLevelChange() {
    if (!editForm.value.grade_level_id) {
        editForm.value.section_id = null;

        return;
    }

    const exists = (props.sections || []).some(
        (s: any) =>
            Number(s.grade_level_id) === Number(editForm.value.grade_level_id) &&
            Number(s.id) === Number(editForm.value.section_id),
    );

    if (!exists) {
        editForm.value.section_id = null;
    }
}

function toggleSubject(id: number) {
    const idx = editForm.value.subject_ids.indexOf(id);

    if (idx > -1) {
        editForm.value.subject_ids.splice(idx, 1);
    } else {
        editForm.value.subject_ids.push(id);
    }
}

function selectAllSubjects() {
    editForm.value.subject_ids = (props.subjects || []).map((s: any) => s.id);
}

function openEdit(t: any) {
    editForm.value = {
        name: t.name || '',
        email: t.email || '',
        gender: t.gender || '',
        bio: t.bio || '',
        grade_level_id: t.grade_level_id ?? (t.grade_level?.id ?? null),
        section_id: t.section_id ?? (t.section?.id ?? null),
        subject_ids: (t.subjects || []).map((s: any) => s.id),
        password: '',
        messenger_enabled: t.messenger_enabled !== false,
        allow_classmate_messaging: t.allow_classmate_messaging !== false,
    };
    editError.value = {};
    isSaving.value = false;
    editing.value = t;
}

function saveEdit() {
    if (!editing.value) {
return;
}

    editError.value = {};
    isSaving.value = true;
    router.put(`/teacher/teachers/${editing.value.id}`, editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = null;
            isSaving.value = false;
        },
        onError: (errors) => {
            editError.value = errors;
            isSaving.value = false;
        },
    });
}
</script>
