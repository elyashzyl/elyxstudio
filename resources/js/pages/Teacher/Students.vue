<template>
    <Head title="Students" />

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
                Students
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ filteredStudents.length }} enrolled ·
                {{ totalXp.toLocaleString() }} total XP
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2.5">
            <div class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2"
                    :stroke-width="2"
                    style="color: var(--gl-text-muted)"
                />
                <input
                    v-model="search"
                    type="text"
                    placeholder="Search students..."
                    class="rounded-xl border py-2.5 pr-3 pl-9 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                        width: 210px;
                    "
                />
            </div>
            <select
                v-model="gradeFilter"
                class="rounded-xl border px-3 py-2.5 text-sm outline-none"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border-color: var(--gl-border);
                "
            >
                <option value="">All grades</option>
                <option v-for="g in grades" :key="g" :value="g">{{ g }}</option>
            </select>
            <select
                v-model="sectionFilter"
                class="rounded-xl border px-3 py-2.5 text-sm outline-none"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border-color: var(--gl-border);
                "
            >
                <option value="">All sections</option>
                <option v-for="s in sections" :key="s" :value="s">
                    {{ s }}
                </option>
            </select>
            <button
                v-if="isSuper"
                @click="openMerge()"
                class="flex items-center gap-1.5 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all hover:opacity-90"
                style="
                    background: var(--gl-surface);
                    border-color: rgba(245, 158, 11, 0.35);
                    color: #f59e0b;
                "
                title="Merge duplicate student accounts"
            >
                <GitMerge class="h-4 w-4" style="color: #f59e0b" :stroke-width="2" />
                <span>Merge Students</span>
            </button>
            <button
                @click="exportScores()"
                class="flex items-center gap-1.5 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all hover:opacity-90"
                style="
                    background: var(--gl-surface);
                    border-color: var(--gl-border);
                    color: var(--gl-text-primary);
                "
                title="Export gradebook to CSV"
            >
                <Download class="h-4 w-4" style="color: var(--gl-primary)" :stroke-width="2" />
                <span>Export CSV</span>
            </button>
            <button
                @click="exportReportCardPdf()"
                class="flex items-center gap-1.5 rounded-xl border px-3.5 py-2.5 text-sm font-medium transition-all hover:opacity-90"
                style="
                    background: var(--gl-surface);
                    border-color: var(--gl-border);
                    color: var(--gl-text-primary);
                "
                title="Export official student report cards to PDF"
            >
                <FileText class="h-4 w-4" style="color: #ef4444" :stroke-width="2" />
                <span>Report Cards (PDF)</span>
            </button>
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
            @click="bulkRemove"
            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
            style="
                background: var(--gl-danger-bg);
                color: var(--gl-danger);
                border: 1px solid rgba(239, 68, 68, 0.25);
            "
        >
            <X class="h-3.5 w-3.5" :stroke-width="2" /> Remove from class
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
        Select all {{ filteredStudents.length }} students
    </label>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
        <!-- LIST (2/3) -->
        <div class="xl:col-span-2">
            <div
                v-if="!filteredStudents.length"
                class="gl-glow-card flex flex-col items-center justify-center px-8 py-20 text-center"
            >
                <div
                    class="mb-5 flex h-20 w-20 items-center justify-center rounded-2xl"
                    style="
                        background: linear-gradient(
                            135deg,
                            rgba(59, 130, 246, 0.1),
                            rgba(124, 58, 237, 0.05)
                        );
                    "
                >
                    <GraduationCap
                        class="h-10 w-10"
                        style="color: var(--gl-primary)"
                        :stroke-width="1.5"
                    />
                </div>
                <p
                    class="text-lg font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    No Students Yet
                </p>
                <p
                    class="mt-2 max-w-md text-sm"
                    style="color: var(--gl-text-secondary)"
                >
                    Students will appear here after they register under your
                    account.
                </p>
            </div>

            <template v-else>
                <div
                    class="mb-1 hidden grid-cols-[1fr_110px_80px_90px_40px] items-center gap-3 px-4 pb-2 text-[11px] font-semibold tracking-wider uppercase sm:grid"
                    style="color: var(--gl-text-muted)"
                >
                    <span>Name</span><span>Section</span
                    ><span class="text-center">Quizzes</span
                    ><span class="text-right">XP</span><span></span>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="s in paged"
                        :key="s.id"
                        @click="selectedId = s.id"
                        class="grid cursor-pointer grid-cols-[auto_1fr_40px] items-center gap-3 rounded-2xl px-4 py-3 transition-all sm:grid-cols-[1fr_110px_80px_90px_40px]"
                        :style="
                            s.id === selectedId
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
                                :value="s.id"
                                v-model="selected"
                                @click.stop
                                class="gl-check"
                                :title="'Select ' + s.name"
                            />
                            <div
                                class="relative flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full text-[11px] font-bold text-white"
                                :style="{
                                    background: s.avatar
                                        ? 'transparent'
                                        : 'linear-gradient(135deg, ' +
                                          avatarColor(s.name) +
                                          ', ' +
                                          avatarColor2(s.name) +
                                          ')',
                                }"
                            >
                                <img
                                    v-if="s.avatar"
                                    :src="s.avatar"
                                    :alt="s.name"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ initials(s.name) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold"
                                    :style="{
                                        color:
                                            s.id === selectedId
                                                ? '#fff'
                                                : 'var(--gl-text-primary)',
                                    }"
                                >
                                    {{ s.name }}
                                </p>
                                <p
                                    class="truncate text-[11px]"
                                    :style="{
                                        color:
                                            s.id === selectedId
                                                ? 'rgba(255,255,255,0.75)'
                                                : 'var(--gl-text-muted)',
                                    }"
                                >
                                    {{ s.email }}
                                </p>
                            </div>
                        </div>
                        <span
                            class="hidden truncate text-xs sm:block"
                            :style="{
                                color:
                                    s.id === selectedId
                                        ? 'rgba(255,255,255,0.9)'
                                        : 'var(--gl-text-secondary)',
                            }"
                            >{{ s.section?.name ?? '—' }}</span
                        >
                        <span
                            class="hidden text-center text-sm font-semibold tabular-nums sm:block"
                            :style="{
                                color:
                                    s.id === selectedId
                                        ? '#fff'
                                        : 'var(--gl-text-primary)',
                            }"
                            >{{ s.completed_quizzes_count ?? 0 }}</span
                        >
                        <span
                            class="hidden text-right text-sm font-extrabold tabular-nums sm:block"
                            :style="{
                                color:
                                    s.id === selectedId
                                        ? '#fff'
                                        : 'var(--gl-text-primary)',
                            }"
                            >{{ (s.total_points ?? 0).toLocaleString() }}</span
                        >
                        <div class="relative flex justify-end">
                            <button
                                @click.stop="
                                    openMenu = openMenu === s.id ? null : s.id
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
                                v-if="openMenu === s.id"
                                class="absolute top-9 right-0 z-20 w-44 overflow-hidden rounded-xl py-1"
                                style="
                                    background: var(--gl-surface);
                                    border: 1px solid var(--gl-border);
                                    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
                                "
                            >
                                <button
                                    @click="
                                        openMenu = null;
                                        openXpModal(s);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(251,191,36,0.08)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <Zap
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-accent)"
                                        :stroke-width="2"
                                    />
                                    Award XP
                                </button>
                                <button
                                    @click="
                                        openMenu = null;
                                        openPointsHistory(s);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(16,185,129,0.08)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <History
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-success)"
                                        :stroke-width="2"
                                    />
                                    XP history
                                </button>
                                <button
                                    @click="
                                        openMenu = null;
                                        viewScores(s);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <Eye
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-primary)"
                                        :stroke-width="2"
                                    />
                                    View scores
                                </button>
                                <button
                                    @click="
                                        openMenu = null;
                                        exportScores(s.id);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(16,185,129,0.08)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <Download
                                        class="h-3.5 w-3.5"
                                        style="color: #10b981"
                                        :stroke-width="2"
                                    />
                                    Export scores (CSV)
                                </button>
                                <button
                                    @click="
                                        openMenu = null;
                                        exportReportCardPdf(s.id);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(239,68,68,0.08)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <FileText
                                        class="h-3.5 w-3.5"
                                        style="color: #ef4444"
                                        :stroke-width="2"
                                    />
                                    Report Card (PDF)
                                </button>
                                <button
                                    @click="
                                        openMenu = null;
                                        openEdit(s);
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
                                    v-if="canImpersonate(s)"
                                    @click="
                                        openMenu = null;
                                        impersonateStudent(s);
                                    "
                                    :disabled="impersonatingId === s.id"
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)] disabled:opacity-50"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <Loader2
                                        v-if="impersonatingId === s.id"
                                        class="h-3.5 w-3.5 animate-spin"
                                        style="color: var(--gl-primary)"
                                    />
                                    <UserCheck
                                        v-else
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-primary)"
                                        :stroke-width="2"
                                    />
                                    {{ impersonatingId === s.id ? 'Impersonating...' : 'Impersonate' }}
                                </button>
                                <button
                                    v-if="isSuper"
                                    @click="
                                        openMenu = null;
                                        openMerge(s);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(245,158,11,0.08)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <GitMerge
                                        class="h-3.5 w-3.5 text-amber-500"
                                        :stroke-width="2"
                                    />
                                    Merge into...
                                </button>
                                <button
                                    @click="
                                        openMenu = null;
                                        confirmRemove(s);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-danger-bg)]"
                                    style="color: var(--gl-danger)"
                                >
                                    <X class="h-3.5 w-3.5" :stroke-width="2" />
                                    Remove
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
            v-if="selectedStudent"
            class="rounded-2xl p-5 xl:sticky xl:top-6"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-3 min-w-0">
                    <div
                        class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-bold text-white"
                        :style="{
                            background: selectedStudent.avatar
                                ? 'transparent'
                                : 'linear-gradient(135deg, ' +
                                  avatarColor(selectedStudent.name) +
                                  ', ' +
                                  avatarColor2(selectedStudent.name) +
                                  ')',
                        }"
                    >
                        <img
                            v-if="selectedStudent.avatar"
                            :src="selectedStudent.avatar"
                            :alt="selectedStudent.name"
                            class="h-full w-full object-cover"
                        />
                        <span v-else>{{ initials(selectedStudent.name) }}</span>
                    </div>
                    <div class="min-w-0">
                        <h2
                            class="truncate text-lg font-extrabold tracking-tight"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ selectedStudent.name }}
                        </h2>
                        <p
                            class="truncate text-xs"
                            style="color: var(--gl-text-muted)"
                        >
                            {{ selectedStudent.email }}
                        </p>
                    </div>
                </div>
                <span
                    class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-bold"
                    style="
                        background: rgba(59, 130, 246, 0.1);
                        color: var(--gl-primary);
                    "
                    >Lv.{{ levelOf(selectedStudent) }}</span
                >
            </div>

            <p
                class="mt-5 mb-2 text-[11px] font-bold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Summary
            </p>
            <div v-if="summaryLoading" class="flex justify-center py-6">
                <div
                    class="h-5 w-5 animate-spin rounded-full border-2"
                    style="
                        border-color: var(--gl-surface-2);
                        border-top-color: var(--gl-primary);
                    "
                ></div>
            </div>
            <div
                v-else-if="summary"
                class="flex items-center justify-around gap-2"
            >
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
                                :stroke-dasharray="`${summary.avg} 100`"
                                style="stroke: #f94f2b"
                            />
                        </svg>
                        <span
                            class="absolute inset-0 flex items-center justify-center text-[11px] font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                            >{{ summary.avg }}%</span
                        >
                    </div>
                    <p
                        class="mt-1 text-[11px]"
                        style="color: var(--gl-text-secondary)"
                    >
                        Average
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
                                :stroke-dasharray="`${summary.completion} 100`"
                                style="stroke: var(--gl-primary)"
                            />
                        </svg>
                        <span
                            class="absolute inset-0 flex items-center justify-center text-[11px] font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                            >{{ summary.completion }}%</span
                        >
                    </div>
                    <p
                        class="mt-1 text-[11px]"
                        style="color: var(--gl-text-secondary)"
                    >
                        Completion
                    </p>
                </div>
                <div class="text-center">
                    <p
                        class="text-lg font-extrabold tabular-nums"
                        style="color: var(--gl-text-primary)"
                    >
                        {{
                            (selectedStudent.total_points ?? 0).toLocaleString()
                        }}
                    </p>
                    <p
                        class="mt-1 text-[11px]"
                        style="color: var(--gl-text-secondary)"
                    >
                        Total XP
                    </p>
                </div>
            </div>

            <div v-if="summary && summary.top?.length" class="mt-5">
                <p
                    class="mb-2 text-[11px] font-bold tracking-widest uppercase"
                    style="color: var(--gl-text-muted)"
                >
                    Top activities · Score
                </p>
                <div class="space-y-2.5">
                    <div v-for="t in summary.top" :key="t.type + t.title">
                        <div class="flex items-center justify-between gap-2">
                            <p
                                class="truncate text-xs font-medium"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ t.title }}
                            </p>
                            <span
                                class="shrink-0 text-[11px] font-bold tabular-nums"
                                style="color: var(--gl-text-secondary)"
                                >{{ t.pct }}%</span
                            >
                        </div>
                        <div
                            class="mt-1 h-1.5 overflow-hidden rounded-full"
                            style="background: var(--gl-surface-2)"
                        >
                            <div
                                class="h-full rounded-full"
                                :style="{
                                    width: Math.min(100, t.pct) + '%',
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
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <p class="text-[11px]" style="color: var(--gl-text-muted)">
                            Email
                        </p>
                        <p
                            class="truncate text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ selectedStudent.email }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px]" style="color: var(--gl-text-muted)">
                            Gender
                        </p>
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ selectedStudent.gender ?? 'Not specified' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px]" style="color: var(--gl-text-muted)">
                            Grade & Section
                        </p>
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ selectedStudent.grade_level?.name ?? selectedStudent.grade ?? '—' }}
                            <span v-if="selectedStudent.section?.name"> · {{ selectedStudent.section.name }}</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-[11px]" style="color: var(--gl-text-muted)">
                            Messaging
                        </p>
                        <p
                            class="text-xs font-semibold"
                            :style="selectedStudent.messenger_enabled === false ? 'color: var(--gl-danger)' : 'color: var(--gl-success)'"
                        >
                            {{ selectedStudent.messenger_enabled === false ? 'Direct messaging off' : 'Direct messaging on' }}
                        </p>
                    </div>
                </div>
                <div v-if="selectedStudent.bio" class="rounded-xl p-3" style="background: var(--gl-surface-2)">
                    <p class="text-[11px]" style="color: var(--gl-text-muted)">Student Notes</p>
                    <p class="mt-1 whitespace-pre-wrap text-xs leading-relaxed" style="color: var(--gl-text-secondary)">{{ selectedStudent.bio }}</p>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <div>
                        <p
                            class="text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Level
                        </p>
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ titleOf(selectedStudent) }}
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button
                            @click="openXpModal(selectedStudent)"
                            class="rounded-lg p-2 transition-colors hover:bg-[rgba(251,191,36,0.1)]"
                            style="
                                color: var(--gl-accent);
                                border: 1px solid rgba(251, 191, 36, 0.25);
                            "
                            title="Award XP"
                        >
                            <Zap class="h-4 w-4" :stroke-width="2" />
                        </button>
                        <button
                            @click="viewScores(selectedStudent)"
                            class="rounded-lg p-2 transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                            style="
                                color: var(--gl-primary);
                                border: 1px solid var(--gl-border);
                            "
                            title="View scores"
                        >
                            <Eye class="h-4 w-4" :stroke-width="2" />
                        </button>
                        <button
                            v-if="canImpersonate(selectedStudent)"
                            @click="impersonateStudent(selectedStudent)"
                            :disabled="impersonatingId === selectedStudent.id"
                            class="rounded-lg p-2 transition-colors hover:bg-[rgba(59,130,246,0.08)] disabled:opacity-50"
                            style="
                                color: var(--gl-primary);
                                border: 1px solid var(--gl-border);
                            "
                            title="Impersonate"
                        >
                            <Loader2
                                v-if="impersonatingId === selectedStudent.id"
                                class="h-4 w-4 animate-spin"
                            />
                            <UserCheck v-else class="h-4 w-4" :stroke-width="2" />
                        </button>
                        <button
                            v-if="isSuper"
                            @click="openMerge(selectedStudent)"
                            class="rounded-lg p-2 transition-colors hover:bg-[rgba(245,158,11,0.08)]"
                            style="
                                color: #f59e0b;
                                border: 1px solid rgba(245, 158, 11, 0.3);
                            "
                            title="Merge Student"
                        >
                            <GitMerge class="h-4 w-4" :stroke-width="2" />
                        </button>
                        <button
                            @click="openEdit(selectedStudent)"
                            class="rounded-lg p-2 transition-colors hover:bg-[rgba(124,58,237,0.08)]"
                            style="
                                color: var(--gl-text-secondary);
                                border: 1px solid var(--gl-border);
                            "
                            title="Edit"
                        >
                            <Pencil class="h-4 w-4" :stroke-width="2" />
                        </button>
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

    <!-- Scores Modal -->
    <Teleport to="body">
        <div v-if="viewingScores" class="fixed inset-0 z-50 flex items-center justify-center" style="background: rgba(0,0,0,0.25); backdrop-filter: blur(2px);" @click.self="viewingScores = null">
            <div class="max-h-[80vh] w-full max-w-2xl overflow-y-auto rounded-2xl p-6" style="background: var(--gl-surface); border: 1px solid var(--gl-border); box-shadow: 0 8px 32px rgba(0,0,0,0.4);">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold" style="color: var(--gl-text-primary)">{{ viewingScores.name }}</h3>
                        <p class="text-sm" style="color: var(--gl-text-secondary)">Scores · all activities</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            @click="exportReportCardPdf(viewingScores.id)"
                            class="flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium transition-colors hover:opacity-90"
                            style="background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-primary)"
                            title="Download Report Card PDF"
                        >
                            <FileText class="h-3.5 w-3.5" style="color: #ef4444" :stroke-width="2" />
                            <span>Download PDF</span>
                        </button>
                        <button @click="viewingScores = null" class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-surface-2)]" style="color: var(--gl-text-muted);"><X class="h-5 w-5" :stroke-width="2" /></button>
                    </div>
                </div>
                <div v-if="scoresLoading" class="flex justify-center py-12"><div class="h-5 w-5 animate-spin rounded-full border-2" style="border-color: var(--gl-surface-2); border-top-color: var(--gl-primary);"></div></div>
                <div v-else-if="!scoresData.length" class="py-8 text-center text-sm" style="color: var(--gl-text-muted)">No attempts yet.</div>
                <table v-else class="w-full text-sm"><thead><tr style="color: var(--gl-text-muted); border-bottom: 1px solid var(--gl-border);"><th class="px-4 py-2.5 text-left font-medium">Type</th><th class="px-4 py-2.5 text-left font-medium">Activity</th><th class="px-4 py-2.5 text-left font-medium">Score</th><th class="px-4 py-2.5 text-left font-medium">Status</th><th class="px-4 py-2.5 text-left font-medium">Date</th></tr></thead>
                <tbody class="divide-y" style="border-color: var(--gl-border);"><tr v-for="a in scoresData" :key="a.id" class="hover:bg-[rgba(59,130,246,0.03)]"><td class="px-4 py-2.5"><span class="rounded-full px-2 py-0.5 text-[11px] font-bold" :style="scoreTypeStyle(a.type)">{{ a.type }}</span></td><td class="px-4 py-2.5" style="color: var(--gl-text-primary)">{{ a.title ?? '—' }}</td><td class="px-4 py-2.5 tabular-nums" style="color: var(--gl-text-secondary)">{{ a.score ?? '—' }} / {{ a.total ?? '—' }}</td><td class="px-4 py-2.5"><StatusBadge :status="a.status" /></td><td class="px-4 py-2.5 text-xs" style="color: var(--gl-text-muted)">{{ formatDate(a.at) }}</td></tr></tbody></table>

            </div>
        </div>
    </Teleport>

    <!-- XP History Modal -->
    <Teleport to="body">
        <div
            v-if="viewingPoints"
            class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="viewingPoints = null"
        >
            <div
                class="no-scrollbar max-h-[85vh] w-full max-w-2xl overflow-y-auto rounded-2xl p-4 sm:p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3
                            class="text-base font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ viewingPoints.name }}
                        </h3>
                        <p
                            class="text-sm"
                            style="color: var(--gl-text-secondary)"
                        >
                            XP history ·
                            <span
                                style="
                                    color: var(--gl-accent);
                                    font-weight: 600;
                                "
                                >{{ pointsTotal }} XP total</span
                            >
                        </p>
                    </div>
                    <button
                        @click="viewingPoints = null"
                        class="rounded-lg p-1.5 transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="color: var(--gl-text-muted)"
                    >
                        <X class="h-5 w-5" :stroke-width="2" />
                    </button>
                </div>

                <div v-if="pointsLoading" class="flex justify-center py-12">
                    <div
                        class="h-5 w-5 animate-spin rounded-full border-2"
                        style="
                            border-color: var(--gl-surface-2);
                            border-top-color: var(--gl-primary);
                        "
                    ></div>
                </div>

                <template v-else-if="pointsData.length">
                    <div class="mb-4 flex flex-wrap gap-2">
                        <span
                            v-for="sum in typeSums"
                            :key="sum.type"
                            class="rounded-full px-2.5 py-1 text-[11px] font-medium"
                            :style="{
                                background: typeColor(sum.type).bg + '22',
                                color: typeColor(sum.type).fg,
                            }"
                        >
                            {{ sum.type }}:
                            <b
                                >{{ sum.points > 0 ? '+' : ''
                                }}{{ sum.points }} XP</b
                            >
                            <span v-if="sum.count > 1" style="opacity: 0.7">
                                · {{ sum.count }}×</span
                            >
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full min-w-[480px] text-sm">
                            <thead>
                                <tr
                                    style="
                                        color: var(--gl-text-muted);
                                        border-bottom: 1px solid
                                            var(--gl-border);
                                    "
                                >
                                    <th
                                        class="px-4 py-2.5 text-left font-medium"
                                    >
                                        Type
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-left font-medium"
                                    >
                                        Reason
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-right font-medium"
                                    >
                                        XP
                                    </th>
                                    <th
                                        class="px-4 py-2.5 text-left font-medium"
                                    >
                                        Date
                                    </th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y"
                                style="border-color: var(--gl-border)"
                            >
                                <tr
                                    v-for="p in pointsData"
                                    :key="p.id"
                                    class="hover:bg-[rgba(59,130,246,0.03)]"
                                >
                                    <td class="px-4 py-2.5">
                                        <span
                                            class="rounded-full px-2 py-0.5 text-[11px] font-medium"
                                            :style="{
                                                background:
                                                    typeColor(p.activity_type)
                                                        .bg + '22',
                                                color: typeColor(
                                                    p.activity_type,
                                                ).fg,
                                            }"
                                            >{{ p.activity_type }}</span
                                        >
                                    </td>
                                    <td
                                        class="px-4 py-2.5"
                                        style="color: var(--gl-text-primary)"
                                    >
                                        {{ p.reason }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-right font-semibold"
                                        :style="
                                            p.points >= 0
                                                ? 'color: var(--gl-success);'
                                                : 'color: var(--gl-danger);'
                                        "
                                    >
                                        {{ p.points > 0 ? '+' : ''
                                        }}{{ p.points }}
                                    </td>
                                    <td
                                        class="px-4 py-2.5 text-xs"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        {{ formatDate(p.created_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <div
                    v-else
                    class="py-8 text-center text-sm"
                    style="color: var(--gl-text-muted)"
                >
                    No XP earned yet.
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Edit Modal -->
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
                <div
                    class="flex items-center justify-between border-b px-6 py-4"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-xl"
                            style="background: rgba(59, 130, 246, 0.12); color: var(--gl-primary)"
                        >
                            <GraduationCap class="h-5 w-5" :stroke-width="2" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold sm:text-lg" style="color: var(--gl-text-primary)">
                                Edit Student Details
                            </h3>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                Update profile, academic assignment, and communication settings for
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

                <form @submit.prevent="saveEdit" class="flex flex-1 flex-col overflow-y-auto">
                    <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">
                        <div class="space-y-4">
                            <div class="flex items-center gap-2 border-b pb-1" style="border-color: var(--gl-border)">
                                <GraduationCap class="h-4 w-4" style="color: var(--gl-primary)" />
                                <h4 class="text-xs font-bold tracking-wider uppercase" style="color: var(--gl-text-primary)">
                                    Personal & Academic
                                </h4>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary)">
                                    Full Name <span style="color: var(--gl-danger)">*</span>
                                </label>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    required
                                    placeholder="Student's full name"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition-all focus:border-[var(--gl-primary)] focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                                    style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)"
                                />
                                <p v-if="editError.name" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ editError.name }}</p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary)">
                                    Email Address <span style="color: var(--gl-danger)">*</span>
                                </label>
                                <input
                                    v-model="editForm.email"
                                    type="email"
                                    required
                                    placeholder="student@example.com"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition-all focus:border-[var(--gl-primary)] focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                                    style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)"
                                />
                                <p v-if="editError.email" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ editError.email }}</p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary)">Gender</label>
                                <select
                                    v-model="editForm.gender"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none transition-all focus:border-[var(--gl-primary)]"
                                    style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)"
                                >
                                    <option value="">Not specified</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <p v-if="editError.gender" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ editError.gender }}</p>
                            </div>

                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary)">Grade Level</label>
                                    <select
                                        v-model="editForm.grade_level_id"
                                        @change="onGradeLevelChange"
                                        class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none transition-all focus:border-[var(--gl-primary)]"
                                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)"
                                    >
                                        <option :value="null">None</option>
                                        <option v-for="g in props.gradeLevels || []" :key="g.id" :value="g.id">{{ g.name }}</option>
                                    </select>
                                    <p v-if="editError.grade_level_id" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ editError.grade_level_id }}</p>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary)">Section</label>
                                    <select
                                        v-model="editForm.section_id"
                                        :disabled="!editForm.grade_level_id"
                                        class="w-full rounded-xl border px-3 py-2.5 text-sm outline-none transition-all focus:border-[var(--gl-primary)] disabled:opacity-50"
                                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)"
                                    >
                                        <option :value="null">None</option>
                                        <option v-for="s in editFilteredSections" :key="s.id" :value="s.id">{{ s.name }}</option>
                                    </select>
                                    <p v-if="editError.section_id" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ editError.section_id }}</p>
                                </div>
                            </div>

                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <label class="text-xs font-semibold" style="color: var(--gl-text-secondary)">Student Notes / Bio</label>
                                    <span class="text-[10px]" style="color: var(--gl-text-muted)">{{ (editForm.bio || '').length }} / 1000</span>
                                </div>
                                <textarea
                                    v-model="editForm.bio"
                                    rows="4"
                                    maxlength="1000"
                                    placeholder="Learning notes, support needs, or other information..."
                                    class="w-full resize-none rounded-xl border px-3.5 py-2.5 text-xs outline-none transition-all focus:border-[var(--gl-primary)] focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                                    style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)"
                                ></textarea>
                                <p v-if="editError.bio" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ editError.bio }}</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="flex items-center gap-2 border-b pb-1" style="border-color: var(--gl-border)">
                                <Lock class="h-4 w-4" style="color: var(--gl-secondary)" />
                                <h4 class="text-xs font-bold tracking-wider uppercase" style="color: var(--gl-text-primary)">
                                    Account & Communication
                                </h4>
                            </div>

                            <div class="rounded-xl border p-3" style="background: rgba(0,0,0,0.02); border-color: var(--gl-border)">
                                <label class="mb-1 flex items-center justify-between text-xs font-semibold" style="color: var(--gl-text-secondary)">
                                    <span>Reset Password</span>
                                    <span class="text-[10px] font-normal" style="color: var(--gl-text-muted)">Optional</span>
                                </label>
                                <input
                                    v-model="editForm.password"
                                    type="password"
                                    placeholder="Leave blank to keep current password"
                                    class="w-full rounded-lg border px-3 py-2 text-xs outline-none transition-all focus:border-[var(--gl-primary)]"
                                    style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border)"
                                />
                                <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">Minimum 8 characters if changing password.</p>
                                <p v-if="editError.password" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ editError.password }}</p>
                            </div>

                            <div class="space-y-2.5 rounded-xl border p-3" style="background: var(--gl-surface-2); border-color: var(--gl-border)">
                                <p class="text-xs font-bold" style="color: var(--gl-text-primary)">Communication Settings</p>
                                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg p-2 transition-colors hover:bg-[var(--gl-surface)]">
                                    <input v-model="editForm.messenger_enabled" type="checkbox" class="gl-check mt-0.5" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold" style="color: var(--gl-text-primary)">Direct Messaging</p>
                                        <p class="text-[11px]" style="color: var(--gl-text-muted)">Allow this student to send and receive direct messages.</p>
                                    </div>
                                </label>
                                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg p-2 transition-colors hover:bg-[var(--gl-surface)]">
                                    <input v-model="editForm.allow_classmate_messaging" type="checkbox" class="gl-check mt-0.5" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold" style="color: var(--gl-text-primary)">Classmate Messaging</p>
                                        <p class="text-[11px]" style="color: var(--gl-text-muted)">Allow this student to participate in classmate messaging when enabled for the class.</p>
                                    </div>
                                </label>
                            </div>
                            <p v-if="editError.messenger_enabled || editError.allow_classmate_messaging" class="text-xs" style="color: var(--gl-danger)">
                                {{ editError.messenger_enabled || editError.allow_classmate_messaging }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-auto flex justify-end gap-3 border-t px-6 py-4" style="border-color: var(--gl-border)">
                        <button
                            type="button"
                            @click="editing = null"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium"
                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border)"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="isSaving"
                            class="flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02] disabled:cursor-not-allowed disabled:opacity-60"
                            style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))"
                        >
                            <Loader2 v-if="isSaving" class="h-4 w-4 animate-spin" />
                            {{ isSaving ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Add XP Modal -->
    <Teleport to="body">
        <div
            v-if="xpStudent"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="xpStudent = null"
        >
            <div
                class="w-full max-w-sm rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <h3
                    class="mb-4 text-base font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ xpMode === 'add' ? 'Award XP to' : 'Deduct XP from' }}
                    {{ xpStudent.name }}
                </h3>
                <div
                    class="mb-4 flex rounded-lg border p-0.5"
                    style="border-color: var(--gl-border)"
                >
                    <button
                        @click="xpMode = 'add'"
                        class="flex-1 rounded-md py-1.5 text-xs font-medium transition-all"
                        :style="
                            xpMode === 'add'
                                ? 'background: linear-gradient(135deg, var(--gl-success), #059669); color: #FFF;'
                                : 'color: var(--gl-text-secondary);'
                        "
                    >
                        Award
                    </button>
                    <button
                        @click="xpMode = 'deduct'"
                        class="flex-1 rounded-md py-1.5 text-xs font-medium transition-all"
                        :style="
                            xpMode === 'deduct'
                                ? 'background: linear-gradient(135deg, var(--gl-danger), #DC2626); color: #FFF;'
                                : 'color: var(--gl-text-secondary);'
                        "
                    >
                        Deduct
                    </button>
                </div>
                <div class="space-y-4">
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >XP Amount</label
                        >
                        <input
                            v-model.number="xpAmount"
                            type="number"
                            min="1"
                            max="1000"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                            placeholder="e.g. 50"
                        />
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Reason (optional)</label
                        >
                        <input
                            v-model="xpReason"
                            type="text"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                            :placeholder="
                                xpMode === 'add'
                                    ? 'e.g. Class participation'
                                    : 'e.g. Late submission'
                            "
                        />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            @click="xpStudent = null"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            Cancel
                        </button>
                        <button
                            @click="awardXp"
                            :disabled="!xpAmount || xpSaving"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-50"
                            :style="
                                xpMode === 'add'
                                    ? 'background: linear-gradient(135deg, var(--gl-accent), #F59E0B); box-shadow: 0 0 12px rgba(251,191,36,0.3);'
                                    : 'background: linear-gradient(135deg, var(--gl-danger), #DC2626); box-shadow: 0 0 12px rgba(239,68,68,0.3);'
                            "
                        >
                            <Zap
                                v-if="xpMode === 'add'"
                                class="mr-1 inline h-4 w-4"
                                :stroke-width="2"
                            />
                            {{
                                xpSaving
                                    ? 'Saving...'
                                    : xpMode === 'add'
                                      ? 'Award XP'
                                      : 'Deduct XP'
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- User Merge Modal -->
    <UserMergeModal
        v-model:is-open="showMergeModal"
        :users="localStudents"
        :initial-source-id="mergeSourceId"
        :initial-target-id="mergeTargetId"
        lock-role="student"
        base-path="/admin/users"
    />
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Search,
    Eye,
    Pencil,
    X,
    GraduationCap,
    Zap,
    History,
    Ellipsis,
    UserCheck,
    Loader2,
    Download,
    FileText,
    GitMerge,
    Lock,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import UserMergeModal from '@/components/UserMergeModal.vue';
import { titleForLevel, levelForPoints } from '@/lib/levels';

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const props = defineProps<{
    students: any[];
    gradeLevels?: any[];
    sections?: any[];
    isSuperadmin?: boolean;
}>();

const isSuper = computed(() => !!props.isSuperadmin || !!(page.props.auth as any)?.user?.is_superadmin);
const isImpersonating = computed(() => !!(page.props as any).impersonated_by);
const impersonatingId = ref<number | null>(null);

const showMergeModal = ref(false);
const mergeSourceId = ref<number | null>(null);
const mergeTargetId = ref<number | null>(null);

function openMerge(student?: any) {
    mergeSourceId.value = student ? student.id : null;
    mergeTargetId.value = null;
    showMergeModal.value = true;
}

function canImpersonate(s: any): boolean {
    return isSuper.value && !isImpersonating.value && s && s.id !== (page.props.auth as any)?.user?.id;
}

function impersonateStudent(s: any) {
    if (impersonatingId.value) {
return;
}

    impersonatingId.value = s.id;
    router.post(
        `/admin/users/${s.id}/impersonate`,
        {},
        {
            onError: () => {
                window.location.href = `/admin/users/${s.id}/impersonate`;
            },
            onFinish: () => {
                impersonatingId.value = null;
            },
        },
    );
}

const localStudents = ref<any[]>([...(props.students || [])]);

watch(
    () => props.students,
    (val) => {
        localStudents.value = [...(val || [])];
    },
    { deep: true },
);

const search = ref('');
const gradeFilter = ref('');
const sectionFilter = ref('');
const openMenu = ref<number | null>(null);

const grades = computed(() => {
    const set = new Set<string>();

    for (const s of localStudents.value) {
        if (s.grade) {
set.add(s.grade);
}
    }

    return [...set].sort();
});
const sections = computed(() => {
    const set = new Set<string>();

    for (const s of localStudents.value) {
        if (s.section?.name) {
set.add(s.section.name);
}
    }

    return [...set].sort();
});

const filteredStudents = computed(() =>
    localStudents.value.filter((s) => {
        if (search.value) {
            const q = search.value.toLowerCase();

            if (
                !s.name.toLowerCase().includes(q) &&
                !s.email.toLowerCase().includes(q)
            ) {
return false;
}
        }

        if (gradeFilter.value && s.grade !== gradeFilter.value) {
return false;
}

        if (sectionFilter.value && s.section?.name !== sectionFilter.value) {
return false;
}

        return true;
    }),
);

const totalXp = computed(() =>
    localStudents.value.reduce((a, s) => a + (s.total_points ?? 0), 0),
);

// Selection + pagination
const selectedId = ref<number | null>(null);
const pageNum = ref(1);
const PAGE_SIZE = 10;

const selectedStudent = computed(() => {
    const list = filteredStudents.value;

    if (!list.length) {
return null;
}

    return list.find((s) => s.id === selectedId.value) ?? list[0];
});

// Summary (detail panel) — declared before the watcher so the
// immediate callback never hits these in TDZ.
const summaryCache = new Map<number, any>();
const summary = ref<any>(null);
const summaryLoading = ref(false);

watch(
    selectedStudent,
    (s) => {
        if (s) {
            selectedId.value = s.id;
            fetchSummary(s.id);
        }
    },
    { immediate: true },
);

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredStudents.value.length / PAGE_SIZE)),
);
const paged = computed(() =>
    filteredStudents.value.slice(
        (pageNum.value - 1) * PAGE_SIZE,
        pageNum.value * PAGE_SIZE,
    ),
);
const rangeLabel = computed(() => {
    const total = filteredStudents.value.length;

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

watch([search, gradeFilter, sectionFilter], () => {
    pageNum.value = 1;
    openMenu.value = null;
});
watch(filteredStudents, () => {
    if (pageNum.value > totalPages.value) {
pageNum.value = totalPages.value;
}
});

// Summary (detail panel)
async function fetchSummary(id: number) {
    if (summaryCache.has(id)) {
        summary.value = summaryCache.get(id);

        return;
    }

    summaryLoading.value = true;
    summary.value = null;

    try {
        const res = await fetch(`/teacher/students/${id}/summary`);
        const data = await res.json();
        summaryCache.set(id, data);

        if (selectedId.value === id) {
summary.value = data;
}
    } catch {
        summary.value = null;
    } finally {
        summaryLoading.value = false;
    }
}

function levelOf(s: any) {
    return levelForPoints(s.total_points ?? 0);
}
function titleOf(s: any) {
    return titleForLevel(levelOf(s));
}
function initials(name: string) {
    return name
        .split(' ')
        .map((w) => w[0])
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

    for (let i = 0; i < name.length; i++) {
        h = name.charCodeAt(i) + ((h << 5) - h);
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

    for (let i = 0; i < name.length; i++) {
        h = name.charCodeAt(i) + ((h << 7) - h);
    }

    return c[Math.abs(h) % c.length];
}
function formatDate(value: string): string {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

function exportScores(studentId?: number) {
    const params = new URLSearchParams();

    if (studentId) {
        params.append('student_id', String(studentId));
    } else {
        if (gradeFilter.value) {
params.append('grade', gradeFilter.value);
}

        if (sectionFilter.value) {
params.append('section', sectionFilter.value);
}
    }

    const query = params.toString() ? `?${params.toString()}` : '';
    window.location.href = `/teacher/students/export-scores${query}`;
}

function exportReportCardPdf(studentId?: number) {
    if (!studentId && filteredStudents.value.length > 60) {
        alert(`You have selected ${filteredStudents.value.length} students. Please filter by grade or section before exporting PDF (maximum 60 students per export).`);

        return;
    }

    const params = new URLSearchParams();

    if (studentId) {
        params.append('student_id', String(studentId));
    } else {
        if (gradeFilter.value) {
params.append('grade', gradeFilter.value);
}

        if (sectionFilter.value) {
params.append('section', sectionFilter.value);
}
    }

    const query = params.toString() ? `?${params.toString()}` : '';
    window.location.href = `/teacher/students/export-report-card-pdf${query}`;
}

const viewingScores = ref<any>(null); const scoresLoading = ref(false); const scoresData = ref<any[]>([]);
function scoreTypeStyle(type: string) {
    const map: Record<string, { background: string; color: string }> = {
        Quiz: { background: 'rgba(59,130,246,0.12)', color: '#3B82F6' },
        Exam: { background: 'rgba(251,191,36,0.12)', color: '#FBBF24' },
        Seatwork: { background: 'rgba(16,185,129,0.12)', color: '#10B981' },
        Practical: { background: 'rgba(124,58,237,0.12)', color: '#7C3AED' },
    };

    return map[type] ?? { background: 'var(--gl-surface-2)', color: 'var(--gl-text-secondary)' };
}

async function viewScores(s: any) {
    viewingScores.value = s;
    scoreTab.value = 'All';
    scoresLoading.value = true;
    scoresData.value = [];

    try {
        const res = await fetch(`/teacher/students/${s.id}/scores`);
        const data = await res.json();

        scoresData.value = data.attempts ?? [];
    } catch {
        scoresData.value = [];
    } finally {
        scoresLoading.value = false;
    }
}

const viewingPoints = ref<any>(null);
const pointsLoading = ref(false);
const pointsData = ref<any[]>([]);
async function openPointsHistory(s: any) {
    viewingPoints.value = s;
    pointsLoading.value = true;
    pointsData.value = [];

    try {
        const res = await fetch(`/teacher/students/${s.id}/points`);
        const data = await res.json();
        pointsData.value = data.points ?? [];
    } catch {
        pointsData.value = [];
    } finally {
        pointsLoading.value = false;
    }
}
const pointsTotal = computed(() =>
    pointsData.value.reduce((a, p) => a + (p.points ?? 0), 0),
);
const typeSums = computed(() => {
    const map = new Map<
        string,
        { type: string; points: number; count: number }
    >();

    for (const p of pointsData.value) {
        const t = p.activity_type || 'Other';
        const entry = map.get(t) ?? { type: t, points: 0, count: 0 };
        entry.points += p.points ?? 0;
        entry.count++;
        map.set(t, entry);
    }

    return [...map.values()].sort((a, b) => b.points - a.points);
});
function typeColor(type: string) {
    const map: Record<string, { bg: string; fg: string }> = {
        Quiz: { bg: '#3B82F6', fg: '#60A5FA' },
        Seatwork: { bg: '#06B6D4', fg: '#22D3EE' },
        Practical: { bg: '#8B5CF6', fg: '#A78BFA' },
        Exam: { bg: '#EC4899', fg: '#F472B6' },
        Game: { bg: '#10B981', fg: '#34D399' },
        Login: { bg: '#F59E0B', fg: '#FBBF24' },
        Message: { bg: '#6366F1', fg: '#818CF8' },
        Manual: { bg: '#22C55E', fg: '#4ADE80' },
        Deduction: { bg: '#EF4444', fg: '#F87171' },
    };

    return map[type] ?? { bg: '#64748B', fg: '#94A3B8' };
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

function openEdit(s: any) {
    editForm.value = {
        name: s.name || '',
        email: s.email || '',
        gender: s.gender || '',
        bio: s.bio || '',
        grade_level_id: s.grade_level_id ?? (s.grade_level?.id ?? null),
        section_id: s.section_id ?? (s.section?.id ?? null),
        password: '',
        messenger_enabled: s.messenger_enabled !== false,
        allow_classmate_messaging: s.allow_classmate_messaging !== false,
    };
    editError.value = {};
    isSaving.value = false;
    editing.value = s;
}

function saveEdit() {
    if (!editing.value) {
return;
}

    editError.value = {};
    isSaving.value = true;
    router.put(`/teacher/students/${editing.value.id}`, editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            summaryCache.delete(editing.value.id);
            editing.value = null;
            isSaving.value = false;
        },
        onError: (errors) => {
            editError.value = errors;
            isSaving.value = false;
        },
    });
}
function confirmRemove(s: any) {
    if (!confirm(`Remove "${s.name}" from your class?`)) {
return;
}

    openMenu.value = null;
    const backup = [...localStudents.value];
    localStudents.value = localStudents.value.filter((item: any) => item.id !== s.id);
    selected.value = selected.value.filter((id) => id !== s.id);
    router.delete(`/teacher/students/${s.id}/remove`, {
        preserveScroll: true,
        onError: () => {
            localStudents.value = backup;
        },
    });
}

const selected = ref<number[]>([]);
const allChecked = computed(
    () =>
        filteredStudents.value.length > 0 &&
        selected.value.length === filteredStudents.value.length,
);

function toggleAll() {
    selected.value = allChecked.value
        ? []
        : filteredStudents.value.map((s: any) => s.id);
}

function bulkRemove() {
    if (!selected.value.length) {
return;
}

    if (!confirm(`Remove ${selected.value.length} student(s) from your class?`)) {
return;
}

    const toRemoveIds = new Set(selected.value);
    const backup = [...localStudents.value];
    localStudents.value = localStudents.value.filter((s: any) => !toRemoveIds.has(s.id));
    const ids = [...selected.value];
    selected.value = [];
    router.delete('/teacher/students/bulk-remove', {
        data: { ids },
        preserveScroll: true,
        onError: () => {
            localStudents.value = backup;
        },
    });
}

const xpStudent = ref<any>(null);
const xpAmount = ref<number>(10);
const xpReason = ref('');
const xpSaving = ref(false);
const xpMode = ref<'add' | 'deduct'>('add');
function openXpModal(s: any) {
    xpStudent.value = s;
    xpAmount.value = 10;
    xpReason.value = '';
    xpMode.value = 'add';
}
async function awardXp() {
    if (!xpStudent.value || !xpAmount.value) {
return;
}

    xpSaving.value = true;

    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';
        const res = await fetch(
            `/teacher/students/${xpStudent.value.id}/add-xp`,
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    xp:
                        xpMode.value === 'deduct'
                            ? -xpAmount.value
                            : xpAmount.value,
                    reason: xpReason.value,
                }),
            },
        );

        if (res.ok) {
            const data = await res.json();
            xpStudent.value.total_points = data.total_points;
            summaryCache.delete(xpStudent.value.id);
            xpStudent.value = null;
        } else {
            const msg =
                res.status === 419
                    ? 'Session expired, please refresh the page.'
                    : 'Failed to update XP. Please try again.';
            alert(msg);
        }
    } catch {
    } finally {
        xpSaving.value = false;
    }
}
</script>
