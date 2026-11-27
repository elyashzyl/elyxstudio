<template>
    <Head title="All Results" />

    <!-- Header -->
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p
                class="text-xs font-semibold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Progress
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                All Results
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ filtered.length }} of {{ activities.length }} results ·
                {{ avgScore }}% average
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
                    placeholder="Search"
                    class="rounded-full border py-2.5 pr-4 pl-9 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                        width: 180px;
                    "
                />
            </div>
            <div class="flex items-center gap-1.5">
                <button
                    v-for="t in typeTabs"
                    :key="t"
                    @click="typeFilter = t === 'All' ? '' : t"
                    class="rounded-full px-3.5 py-2 text-xs font-bold transition-all"
                    :style="
                        (typeFilter === '' && t === 'All') || typeFilter === t
                            ? { background: '#1F2937', color: '#fff' }
                            : {
                                  background: 'var(--gl-surface)',
                                  border: '1px solid var(--gl-border)',
                                  color: 'var(--gl-text-secondary)',
                              }
                    "
                >
                    {{ t }}
                </button>
            </div>
        </div>
    </div>

    <!-- Stat cards -->
    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(59, 130, 246, 0.08);
                border: 1px solid rgba(59, 130, 246, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(59, 130, 246, 0.16)"
            >
                <ClipboardCheck
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-primary)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ activities.length }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Total results
            </p>
        </div>
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(251, 146, 60, 0.1);
                border: 1px solid rgba(251, 146, 60, 0.2);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(251, 146, 60, 0.2)"
            >
                <Target
                    class="h-[18px] w-[18px]"
                    style="color: #fb923c"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ avgScore }}<span class="text-xs font-semibold">%</span>
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Average score
            </p>
        </div>
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(192, 132, 252, 0.1);
                border: 1px solid rgba(192, 132, 252, 0.22);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(192, 132, 252, 0.2)"
            >
                <Trophy
                    class="h-[18px] w-[18px]"
                    style="color: #c084fc"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ topScore }}<span class="text-xs font-semibold">%</span>
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Highest score
            </p>
        </div>
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(16, 185, 129, 0.08);
                border: 1px solid rgba(16, 185, 129, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(16, 185, 129, 0.16)"
            >
                <Users
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-success)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ studentCount }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Students
            </p>
        </div>
    </div>

    <!-- Extra filters -->
    <div class="mb-5 flex flex-wrap items-center gap-2.5">
        <select
            v-if="teachersList && teachersList.length > 0"
            v-model="teacherFilter"
            class="rounded-xl border px-3 py-2.5 text-sm outline-none"
            style="
                background: var(--gl-surface);
                color: var(--gl-text-primary);
                border-color: var(--gl-border);
            "
        >
            <option value="">All teachers</option>
            <option v-for="t in teachersList" :key="t.id" :value="t.id">
                {{ t.name }}
            </option>
        </select>
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
            <option v-for="g in gradeLevels" :key="g.id" :value="g.id">
                {{ g.name }}
            </option>
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
            <option v-for="s in filteredSections" :key="s.id" :value="s.id">
                {{ s.name }}
            </option>
        </select>
        <select
            v-model="genderFilter"
            class="rounded-xl border px-3 py-2.5 text-sm outline-none"
            style="
                background: var(--gl-surface);
                color: var(--gl-text-primary);
                border-color: var(--gl-border);
            "
            title="Filter by gender"
        >
            <option value="">All genders</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
    </div>

    <div
        v-if="!filtered.length"
        class="gl-glow-card flex flex-col items-center justify-center px-8 py-16 text-center"
    >
        <div
            class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
            style="background: var(--gl-surface-2)"
        >
            <ClipboardCheck
                class="h-7 w-7"
                style="color: var(--gl-text-muted)"
                :stroke-width="1.5"
            />
        </div>
        <p class="text-sm font-medium" style="color: var(--gl-text-secondary)">
            No results found
        </p>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
            No submitted activities match your filters.
        </p>
    </div>

    <div v-else>
        <div class="mb-3 flex flex-wrap items-center justify-between gap-2 px-1">
            <label
                class="flex cursor-pointer items-center gap-2 text-xs font-medium"
                style="color: var(--gl-text-muted)"
            >
                <input
                    type="checkbox"
                    :checked="allChecked"
                    @change="toggleAll"
                    class="gl-check cursor-pointer"
                />
                <span>Select all {{ filtered.length }} result{{ filtered.length === 1 ? '' : 's' }}</span>
            </label>
            <button
                v-if="!selected.length"
                type="button"
                @click="autoRecheckFiltered"
                :disabled="isRecheckingBulk || !filtered.length"
                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.3);"
            >
                <LoaderCircle v-if="isRecheckingBulk" :size="13" class="animate-spin" />
                <Sparkles v-else :size="13" />
                <span>{{ isRecheckingBulk ? 'AI Checking...' : `AI Check All (${filtered.length})` }}</span>
            </button>
        </div>

        <!-- Bulk Action Bar -->
        <div
            v-if="selected.length"
            class="mb-3 flex flex-wrap items-center gap-2 rounded-2xl px-4 py-3"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <span
                class="text-xs font-semibold"
                style="color: var(--gl-text-primary)"
            >
                {{ selected.length }} selected
            </span>
            <button
                type="button"
                @click="bulkAiCheck"
                :disabled="isRecheckingBulk || !selectedAiCheckable.length"
                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                style="
                    background: rgba(124, 58, 237, 0.14);
                    color: var(--gl-secondary);
                    border: 1px solid rgba(124, 58, 237, 0.3);
                "
                :title="selectedAiCheckable.length ? 'Run AI check answers on selected attempts' : 'No AI-checkable attempts selected'"
            >
                <LoaderCircle v-if="isRecheckingBulk" :size="13" class="animate-spin" />
                <Sparkles v-else :size="13" />
                <span>{{ isRecheckingBulk ? 'AI Checking...' : `AI Check Answers (${selectedAiCheckable.length})` }}</span>
            </button>
            <button
                type="button"
                @click="confirmBulkDelete"
                :disabled="isDeletingBulk"
                class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-medium transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                style="
                    background: var(--gl-danger-bg);
                    color: var(--gl-danger);
                    border: 1px solid rgba(239, 68, 68, 0.25);
                "
            >
                <LoaderCircle v-if="isDeletingBulk" :size="13" class="animate-spin" />
                <Trash2 v-else class="h-3.5 w-3.5" :stroke-width="2" />
                <span>Delete</span>
            </button>
            <button
                type="button"
                @click="selected = []"
                class="ml-auto text-xs font-medium transition-colors hover:opacity-80"
                style="color: var(--gl-text-muted)"
            >
                Clear
            </button>
        </div>

        <div
            class="mb-1 hidden grid-cols-[1fr_110px_130px_120px_40px] items-center gap-3 px-4 pb-2 text-[11px] font-semibold tracking-wider uppercase sm:grid"
            style="color: var(--gl-text-muted)"
        >
            <div class="flex items-center gap-2">
                <input
                    type="checkbox"
                    :checked="allChecked"
                    @change="toggleAll"
                    class="gl-check cursor-pointer"
                    title="Select all"
                />
                <span>Student</span>
            </div>
            <span>Activity</span>
            <span>Score</span>
            <span class="text-right">Date</span>
            <span></span>
        </div>
        <div class="space-y-2">
            <div v-for="item in filtered" :key="item.type + item.id"
                :id="'result-attempt-' + item.type.toLowerCase() + '-' + item.id"
                :data-attempt-id="item.id"
                :data-type="item.type.toLowerCase()"
                :class="{
                    'ring-2 ring-[var(--gl-primary)] shadow-lg shadow-[var(--gl-primary-glow)]': lastAttemptKey === `${item.type.toLowerCase()}-${item.id}` || lastAttemptId === item.id,
                    'border-[var(--gl-primary)] bg-[rgba(59,130,246,0.06)]': selected.includes(itemKey(item))
                }"
                class="grid grid-cols-[auto_1fr_40px] items-center gap-3 rounded-2xl px-4 py-3 transition-all sm:grid-cols-[1fr_110px_130px_120px_40px]"
                :style="{ background: tint(item.type), border: '1px solid ' + tintBorder(item.type) }">

                <div class="flex min-w-0 items-center gap-3">
                    <input
                        type="checkbox"
                        :value="itemKey(item)"
                        v-model="selected"
                        @click.stop
                        class="gl-check cursor-pointer shrink-0"
                        :title="'Select ' + item.student_name + ' - ' + item.title"
                    />
                    <div
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-[11px] font-bold text-white"
                        :style="{ background: avatarBg(item.student_name) }"
                    >
                        {{ (item.student_name || '?').charAt(0).toUpperCase() }}
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <p
                                class="truncate text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ item.student_name }}
                            </p>
                            <span
                                v-if="item.student_gender"
                                class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider"
                                :style="
                                    item.student_gender.toLowerCase() === 'female'
                                        ? { background: 'rgba(236, 72, 153, 0.12)', color: '#ec4899', border: '1px solid rgba(236, 72, 153, 0.25)' }
                                        : { background: 'rgba(59, 130, 246, 0.12)', color: 'var(--gl-primary)', border: '1px solid rgba(59, 130, 246, 0.25)' }
                                "
                            >
                                {{ item.student_gender }}
                            </span>
                        </div>
                        <p
                            class="truncate text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            {{ item.student_grade }}
                        </p>
                        <p
                            class="mt-0.5 truncate text-[11px] sm:hidden"
                            style="color: var(--gl-text-secondary)"
                        >
                            <span
                                class="font-bold"
                                :style="{
                                    color:
                                        item.type === 'Quiz'
                                            ? '#3B82F6'
                                            : item.type === 'Exam'
                                              ? '#F59E0B'
                                              : item.type === 'Seatwork'
                                                ? '#10B981'
                                                : item.type === 'Practical'
                                                  ? '#7C3AED'
                                                  : '#22D3EE',
                                }"
                                >{{ item.type }}</span
                            >
                            · {{ item.title }}
                            <span
                                v-if="item.is_live"
                                class="ml-1.5 inline-flex items-center gap-1 rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-black text-amber-600 dark:text-amber-400 border border-amber-500/30"
                            >
                                <Trophy class="h-2.5 w-2.5 text-amber-500" />
                                Live Arena · Rank #{{ item.rank }}
                            </span>
                            <span
                                v-if="item.attempt_number"
                                class="ml-1 text-[10px] font-semibold"
                                style="color: var(--gl-text-muted)"
                            >
                                (#{{ item.attempt_number }})
                            </span>
                            <span
                                v-if="item.is_recorded"
                                class="ml-1 text-[10px] font-bold"
                                style="color: var(--gl-success)"
                            >
                                [Recorded]
                            </span>
                        </p>
                    </div>
                </div>
                <div class="hidden min-w-0 sm:block">
                    <span
                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                        :class="badgeClass(item.type)"
                    >
                        <component
                            :is="typeIcon(item.type)"
                            class="h-3 w-3"
                            :stroke-width="2"
                        />
                        {{ item.type }}
                    </span>
                    <p
                        class="mt-0.5 truncate text-[11px]"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ item.title }}
                    </p>
                    <div
                        v-if="item.attempt_number"
                        class="mt-1 flex items-center gap-1.5 flex-wrap"
                    >
                        <span
                            class="rounded px-1.5 py-0.5 text-[9px] font-bold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-muted);
                            "
                        >
                            Attempt #{{ item.attempt_number }}
                        </span>
                        <span
                            v-if="item.is_recorded"
                            class="rounded px-1.5 py-0.5 text-[9px] font-bold"
                            style="
                                background: rgba(16, 185, 129, 0.15);
                                color: var(--gl-success);
                                border: 1px solid rgba(16, 185, 129, 0.3);
                            "
                        >
                            Recorded
                        </span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div class="relative h-9 w-9 shrink-0 sm:h-10 sm:w-10">
                        <svg
                            viewBox="0 0 36 36"
                            class="h-full w-full -rotate-90"
                        >
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="4"
                                style="stroke: var(--gl-surface-2)"
                            />
                            <circle
                                cx="18"
                                cy="18"
                                r="15.915"
                                fill="none"
                                stroke-width="4"
                                stroke-linecap="round"
                                pathLength="100"
                                :stroke-dasharray="`${item.percentage} 100`"
                                :style="{ stroke: ringColor(item.percentage) }"
                            />
                        </svg>
                        <span
                            class="absolute inset-0 flex items-center justify-center text-[9px] font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                            >{{ item.percentage }}</span
                        >
                    </div>
                    <span
                        class="hidden text-xs font-bold tabular-nums sm:inline"
                        style="color: var(--gl-text-primary)"
                        >{{ item.score }}/{{ item.total }}</span
                    >
                </div>
                <span
                    class="hidden truncate text-right text-[11px] sm:block"
                    style="color: var(--gl-text-muted)"
                    >{{ formatDate(item.submitted_at) }}</span
                >
                <div class="relative flex justify-end">
                    <button
                        type="button"
                        @click.stop="
                            openMenu =
                                openMenu === item.type + item.id
                                    ? null
                                    : item.type + item.id
                        "
                        title="Actions"
                        class="flex h-8 w-8 items-center justify-center rounded-full transition-all hover:scale-105"
                        :style="{
                            background:
                                openMenu === item.type + item.id
                                    ? 'var(--gl-surface-2)'
                                    : 'transparent',
                            color:
                                openMenu === item.type + item.id
                                    ? 'var(--gl-text-primary)'
                                    : 'var(--gl-text-muted)',
                            border: '1px solid var(--gl-border)',
                        }"
                    >
                        <Ellipsis class="h-4 w-4" :stroke-width="2.5" />
                    </button>
                    <div
                        v-if="openMenu === item.type + item.id"
                        class="absolute top-full right-0 z-40 mt-1.5 w-44 overflow-hidden rounded-xl py-1 shadow-xl"
                        style="
                            background: var(--gl-surface);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <button
                            @click="
                                openMenu = null;
                                autoRecheck(item);
                            "
                            class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                            style="color: var(--gl-text-primary)"
                        >
                            <RotateCw
                                class="h-3.5 w-3.5"
                                style="color: var(--gl-primary)"
                                :stroke-width="2"
                            />
                            Auto recheck
                        </button>
                        <button
                            v-if="item.type === 'Seatwork' && !item.is_recorded"
                            @click="
                                openMenu = null;
                                setRecordedSeatwork(item);
                            "
                            class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-surface-2)]"
                            style="color: var(--gl-accent)"
                        >
                            <Award
                                class="h-3.5 w-3.5"
                                :stroke-width="2"
                                style="color: var(--gl-accent)"
                            />
                            Set as Recorded
                        </button>
                        <Link
                            :href="recheckUrl(item)"
                            class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(124,58,237,0.08)]"
                            style="color: var(--gl-text-primary)"
                        >
                            <ClipboardCheck
                                v-if="item.type === 'Practical'"
                                class="h-3.5 w-3.5"
                                style="color: var(--gl-secondary)"
                                :stroke-width="2"
                            />
                            <Eye
                                v-else
                                class="h-3.5 w-3.5"
                                style="color: var(--gl-secondary)"
                                :stroke-width="2"
                            />
                            {{ item.type === 'Practical' ? 'Recheck' : 'View' }}
                        </Link>
                        <button
                            @click="
                                openMenu = null;
                                confirmDelete(item);
                            "
                            class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-danger-bg)]"
                            style="color: var(--gl-danger)"
                        >
                            <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <p class="mt-4 text-xs" style="color: var(--gl-text-muted)">
            {{ filtered.length }} of {{ activities.length }} results
        </p>
    </div>

    <!-- Click-away for action dropdown -->
    <div
        v-if="openMenu !== null"
        class="fixed inset-0 z-20"
        @click="openMenu = null"
    ></div>

    <Teleport to="body">
        <div
            v-if="deleteItem"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="deleteItem = null"
        >
            <div
                class="mx-4 w-full max-w-sm rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
                    style="background: var(--gl-danger-bg)"
                >
                    <Trash2
                        class="h-7 w-7"
                        style="color: var(--gl-danger)"
                        :stroke-width="2.5"
                    />
                </div>
                <h3
                    class="mb-1 text-center text-lg font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Delete attempt?
                </h3>
                <p
                    class="mb-6 text-center text-sm"
                    style="color: var(--gl-text-secondary)"
                >
                    This will remove
                    <strong>{{ deleteItem?.student_name }}</strong
                    >'s attempt on <strong>{{ deleteItem?.title }}</strong
                    >.
                </p>
                <div class="flex justify-center gap-3">
                    <button
                        @click="deleteItem = null"
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
                        @click="deleteAttempt"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
                        style="background: var(--gl-danger)"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <div
            v-if="bulkDeleteConfirm"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="bulkDeleteConfirm = false"
        >
            <div
                class="mx-4 w-full max-w-sm rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
                    style="background: var(--gl-danger-bg)"
                >
                    <Trash2
                        class="h-7 w-7"
                        style="color: var(--gl-danger)"
                        :stroke-width="2.5"
                    />
                </div>
                <h3
                    class="mb-1 text-center text-lg font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Delete {{ selected.length }} attempt{{ selected.length === 1 ? '' : 's' }}?
                </h3>
                <p
                    class="mb-6 text-center text-sm"
                    style="color: var(--gl-text-secondary)"
                >
                    This will remove the selected attempts. Students will be able to retake them.
                </p>
                <div class="flex justify-center gap-3">
                    <button
                        @click="bulkDeleteConfirm = false"
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
                        @click="executeBulkDelete"
                        :disabled="isDeletingBulk"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-50"
                        style="background: var(--gl-danger)"
                    >
                        {{ isDeletingBulk ? 'Deleting...' : 'Delete' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Award,
    ClipboardCheck,
    Search,
    Eye,
    RotateCw,
    FileQuestion,
    FileText,
    ClipboardList,
    FlaskConical,
    Gamepad2,
    Trash2,
    Target,
    Trophy,
    Users,
    Ellipsis,
    Sparkles,
    LoaderCircle,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        activities?: any[];
        teachersList?: any[];
        gradeLevels?: any[];
        sections?: any[];
    }>(),
    {
        activities: () => [],
        teachersList: () => [],
        gradeLevels: () => [],
        sections: () => [],
    },
);

const avgScore = computed(() => {
    if (!props.activities.length) {
return 0;
}

    return Math.round(
        props.activities.reduce((a, i) => a + i.percentage, 0) /
            props.activities.length,
    );
});
const topScore = computed(() => {
    if (!props.activities.length) {
return 0;
}

    return Math.max(...props.activities.map((i) => i.percentage));
});
const studentCount = computed(
    () => new Set(props.activities.map((i) => i.student_name)).size,
);

const typeTabs = ['All', 'Quiz', 'Exam', 'Seatwork', 'Practical', 'Game'];

const rk = 'teacher_results_' + (typeof window !== 'undefined' ? window.location.pathname : '');

const lastAttemptKey = ref<string | null>(null);
const lastAttemptId = ref<number | null>(null);

function scrollToAttempt(target: string | number) {
    if (typeof window === 'undefined') {
return;
}

    const str = String(target).trim();

    if (!str) {
return;
}

    let targetType = '';
    let targetId: number | null = null;

    if (str.includes('-')) {
        const parts = str.split('-');
        targetType = parts[0].toLowerCase();
        targetId = parseInt(parts[1], 10);
    } else {
        targetId = parseInt(str, 10);
    }

    if (targetId && !isNaN(targetId)) {
        lastAttemptId.value = targetId;
        lastAttemptKey.value = targetType ? `${targetType}-${targetId}` : String(targetId);
        sessionStorage.setItem('last_checked_result_id', lastAttemptKey.value);

        const matchingItem = props.activities.find((a: any) => {
            if (targetType) {
                return a.type.toLowerCase() === targetType && Number(a.id) === targetId;
            }

            return Number(a.id) === targetId;
        });

        if (matchingItem) {
            if (typeFilter.value && matchingItem.type !== typeFilter.value) {
                typeFilter.value = '';
            }

            if (teacherFilter.value && matchingItem.teacher_id !== Number(teacherFilter.value)) {
                teacherFilter.value = '';
            }

            if (gradeFilter.value && matchingItem.grade_level_id !== Number(gradeFilter.value)) {
                gradeFilter.value = '';
            }

            if (sectionFilter.value && matchingItem.section_id !== Number(sectionFilter.value)) {
                sectionFilter.value = '';
            }
        }
    }

    let attempts = 0;
    const maxAttempts = 25;
    const interval = setInterval(() => {
        attempts++;
        const el = (targetType && targetId ? document.getElementById(`result-attempt-${targetType}-${targetId}`) : null)
            || (targetId ? document.querySelector(`[data-attempt-id="${targetId}"]`) : null)
            || (targetId ? document.getElementById(`result-attempt-${targetId}`) : null);

        if (el) {
            clearInterval(interval);
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else if (attempts >= maxAttempts) {
            clearInterval(interval);
        }
    }, 40);
}

let pollTimer: ReturnType<typeof setInterval> | null = null;
let isPolling = false;

function pollActivities() {
    if (isPolling || (typeof document !== 'undefined' && document.hidden)) {
return;
}

    isPolling = true;
    router.reload({
        only: ['activities'],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isPolling = false;
        },
    });
}

function handleVisibilityChange() {
    if (typeof document !== 'undefined' && !document.hidden) {
        pollActivities();
    }
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        const hash = window.location.hash;

        if (hash.startsWith('#result-attempt-')) {
            const raw = hash.replace('#result-attempt-', '');

            if (raw) {
                scrollToAttempt(raw);

                return;
            }
        }

        const saved = sessionStorage.getItem('last_checked_result_id');

        if (saved) {
            scrollToAttempt(saved);
        }
    }

    pollTimer = setInterval(pollActivities, 3000);

    if (typeof document !== 'undefined') {
        document.addEventListener('visibilitychange', handleVisibilityChange);
    }
});

onUnmounted(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }

    if (typeof document !== 'undefined') {
        document.removeEventListener('visibilitychange', handleVisibilityChange);
    }
});

const search = ref(typeof window !== 'undefined' ? (sessionStorage.getItem(rk + '_search') ?? '') : '');
const teacherFilter = ref(typeof window !== 'undefined' ? (sessionStorage.getItem(rk + '_teacher') ?? '') : '');
const typeFilter = ref(typeof window !== 'undefined' ? (sessionStorage.getItem(rk + '_type') ?? '') : '');
const gradeFilter = ref(typeof window !== 'undefined' ? (sessionStorage.getItem(rk + '_grade') ?? '') : '');
const sectionFilter = ref(typeof window !== 'undefined' ? (sessionStorage.getItem(rk + '_section') ?? '') : '');
const genderFilter = ref(typeof window !== 'undefined' ? (sessionStorage.getItem(rk + '_gender') ?? '') : '');
const openMenu = ref<string | null>(null);

watch(gradeFilter, () => {
    sectionFilter.value = '';
});
watch([search, teacherFilter, typeFilter, gradeFilter, sectionFilter, genderFilter], () => {
    openMenu.value = null;

    if (typeof window !== 'undefined') {
        sessionStorage.setItem(rk + '_search', search.value);
        sessionStorage.setItem(rk + '_teacher', teacherFilter.value);
        sessionStorage.setItem(rk + '_type', typeFilter.value);
        sessionStorage.setItem(rk + '_grade', gradeFilter.value);
        sessionStorage.setItem(rk + '_section', sectionFilter.value);
        sessionStorage.setItem(rk + '_gender', genderFilter.value);
    }
});

const filteredSections = computed(() => {
    if (!gradeFilter.value) {
return props.sections;
}

    return props.sections.filter(
        (s: any) => s.grade_level_id === Number(gradeFilter.value),
    );
});

const filtered = computed(() => {
    return props.activities.filter((a: any) => {
        if (search.value) {
            const q = search.value.toLowerCase();

            if (
                !a.title.toLowerCase().includes(q) &&
                !a.student_name.toLowerCase().includes(q)
            ) {
return false;
}
        }

        if (teacherFilter.value && a.teacher_id !== Number(teacherFilter.value)) {
return false;
}

        if (typeFilter.value && a.type !== typeFilter.value) {
return false;
}

        if (gradeFilter.value && a.grade_level_id !== Number(gradeFilter.value)) {
return false;
}

        if (sectionFilter.value && a.section_id !== Number(sectionFilter.value)) {
return false;
}

        if (genderFilter.value && (a.student_gender || '').toLowerCase() !== genderFilter.value.toLowerCase()) {
return false;
}

        return true;
    });
});

const AVATAR_BG = [
    'linear-gradient(135deg, #3B82F6, #7C3AED)',
    'linear-gradient(135deg, #10B981, #06B6D4)',
    'linear-gradient(135deg, #F59E0B, #EF4444)',
    'linear-gradient(135deg, #EC4899, #8B5CF6)',
    'linear-gradient(135deg, #14B8A6, #3B82F6)',
];

function avatarBg(name: string): string {
    let h = 0;
    const n = name || '';

    for (let i = 0; i < n.length; i++) {
        h = n.charCodeAt(i) + ((h << 5) - h);
    }

    return AVATAR_BG[Math.abs(h) % AVATAR_BG.length];
}

function typeIcon(type: string) {
    const icons: Record<string, any> = {
        Quiz: FileQuestion,
        Exam: FileText,
        Seatwork: ClipboardList,
        Practical: FlaskConical,
        Game: Gamepad2,
    };

    return icons[type] ?? ClipboardCheck;
}

function badgeClass(type: string) {
    const classes: Record<string, string> = {
        Quiz: 'bg-[rgba(59,130,246,0.12)] text-[#3B82F6]',
        Exam: 'bg-[rgba(251,191,36,0.12)] text-[#FBBF24]',
        Seatwork: 'bg-[rgba(16,185,129,0.12)] text-[#10B981]',
        Practical: 'bg-[rgba(124,58,237,0.12)] text-[#7C3AED]',
        Game: 'bg-[rgba(34,211,238,0.12)] text-[#22D3EE]',
    };

    return (
        classes[type] ??
        'bg-[var(--gl-surface-2)] text-[var(--gl-text-secondary)]'
    );
}

function tint(type: string): string {
    const map: Record<string, string> = {
        Quiz: 'rgba(59,130,246,0.05)',
        Exam: 'rgba(251,191,36,0.05)',
        Seatwork: 'rgba(16,185,129,0.05)',
        Practical: 'rgba(124,58,237,0.05)',
        Game: 'rgba(34,211,238,0.05)',
    };

    return map[type] ?? 'var(--gl-surface)';
}

function tintBorder(type: string): string {
    const map: Record<string, string> = {
        Quiz: 'rgba(59,130,246,0.16)',
        Exam: 'rgba(251,191,36,0.16)',
        Seatwork: 'rgba(16,185,129,0.16)',
        Practical: 'rgba(124,58,237,0.16)',
        Game: 'rgba(34,211,238,0.16)',
    };

    return map[type] ?? 'var(--gl-border)';
}

function ringColor(pct: number): string {
    if (pct >= 80) {
return 'var(--gl-success)';
}

    if (pct >= 60) {
return 'var(--gl-accent)';
}

    return 'var(--gl-danger)';
}

function autoRecheck(item: any) {
    const key = `${item.type.toLowerCase()}-${item.id}`;
    lastAttemptId.value = item.id;
    lastAttemptKey.value = key;
    sessionStorage.setItem('last_checked_result_id', key);
    const routes: Record<string, string> = {
        Quiz: `/teacher/quizzes/${item.activity_id}/auto-recheck/${item.id}`,
        Seatwork: `/teacher/seatworks/${item.activity_id}/auto-recheck/${item.id}`,
        Practical: `/teacher/practicals/${item.activity_id}/auto-recheck/${item.id}`,
        Exam: `/teacher/exams/attempts/${item.id}/auto-recheck`,
    };
    const url = routes[item.type];

    if (url) {
        router.put(
            url,
            {},
            {
                preserveScroll: true,
                preserveState: true,
                onSuccess: () => {
                    scrollToAttempt(key);
                },
            },
        );
    }
}

function setRecordedSeatwork(item: any) {
    const key = `seatwork-${item.id}`;
    lastAttemptId.value = item.id;
    lastAttemptKey.value = key;
    router.post(
        `/teacher/seatworks/${item.activity_id}/attempts/${item.id}/record`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                scrollToAttempt(key);
            },
        },
    );
}

const isRecheckingBulk = ref(false);
const isDeletingBulk = ref(false);
const bulkDeleteConfirm = ref(false);
const selected = ref<string[]>([]);

function itemKey(item: any): string {
    return `${item.type}-${item.id}`;
}

const allChecked = computed(() => {
    if (!filtered.value.length) {
return false;
}

    return filtered.value.every((i: any) => selected.value.includes(itemKey(i)));
});

function toggleAll() {
    if (allChecked.value) {
        selected.value = [];
    } else {
        selected.value = filtered.value.map((i: any) => itemKey(i));
    }
}

const selectedItems = computed(() => {
    const set = new Set(selected.value);

    return filtered.value.filter((i: any) => set.has(itemKey(i)));
});

const selectedAiCheckable = computed(() => {
    return selectedItems.value.filter((i: any) =>
        ['Quiz', 'Seatwork', 'Practical', 'Exam'].includes(i.type),
    );
});

function bulkAiCheck() {
    if (isRecheckingBulk.value || !selectedAiCheckable.value.length) {
return;
}

    isRecheckingBulk.value = true;
    const items = selectedAiCheckable.value.map((i: any) => ({
        type: i.type,
        id: i.id,
    }));
    router.post(
        '/teacher/results/auto-recheck-bulk',
        { items },
        {
            preserveScroll: true,
            onFinish: () => {
                isRecheckingBulk.value = false;
            },
            onSuccess: () => {
                selected.value = [];
            },
        },
    );
}

function autoRecheckFiltered() {
    if (isRecheckingBulk.value || !filtered.value.length) {
return;
}

    isRecheckingBulk.value = true;
    const items = filtered.value
        .filter((i: any) => ['Quiz', 'Seatwork', 'Practical', 'Exam'].includes(i.type))
        .slice(0, 50)
        .map((i: any) => ({
            type: i.type,
            id: i.id,
        }));
    router.post(
        '/teacher/results/auto-recheck-bulk',
        { items },
        {
            preserveScroll: true,
            onFinish: () => {
                isRecheckingBulk.value = false;
            },
        },
    );
}

function confirmBulkDelete() {
    if (!selected.value.length) {
return;
}

    bulkDeleteConfirm.value = true;
}

function executeBulkDelete() {
    if (!selectedItems.value.length || isDeletingBulk.value) {
return;
}

    isDeletingBulk.value = true;
    const items = selectedItems.value.map((i: any) => ({
        type: i.type,
        id: i.id,
    }));
    router.delete('/teacher/results/bulk-destroy', {
        data: { items },
        preserveScroll: true,
        onFinish: () => {
            isDeletingBulk.value = false;
            bulkDeleteConfirm.value = false;
        },
        onSuccess: () => {
            selected.value = [];
        },
    });
}

watch(filtered, (newFiltered) => {
    const validKeys = new Set(newFiltered.map((i: any) => itemKey(i)));
    selected.value = selected.value.filter((k) => validKeys.has(k));
});

function recheckUrl(item: any): string {
    if (item.is_live && item.room_pin) {
        return `/teacher/games/rooms/${encodeURIComponent(item.room_pin)}/host`;
    }

    if (item.type === 'Game') {
        return '/teacher/games';
    }

    const key = `${item.type.toLowerCase()}-${item.id}`;
    const returnUrl = typeof window !== 'undefined'
        ? encodeURIComponent(window.location.pathname + window.location.search + `#result-attempt-${key}`)
        : '';
    const routes: Record<string, string> = {
        Quiz: `/teacher/quizzes/${item.activity_id}/recheck/${item.id}?return_to=${returnUrl}`,
        Seatwork: `/teacher/seatworks/${item.activity_id}/recheck/${item.id}?return_to=${returnUrl}`,
        Practical: `/teacher/practicals/${item.activity_id}/grade/${item.id}?return_to=${returnUrl}`,
        Exam: `/teacher/exams/${item.activity_id}/attempts?return_to=${returnUrl}#attempt-${item.id}`,
    };

    return routes[item.type] ?? '#';
}

const deleteItem = ref<any>(null);

function confirmDelete(item: any) {
    deleteItem.value = item;
}

function deleteAttempt() {
    if (!deleteItem.value) {
return;
}

    router.delete('/teacher/results/attempt', {
        data: { type: deleteItem.value.type, id: deleteItem.value.id },
        preserveScroll: true,
        onSuccess: () => {
            deleteItem.value = null;
        },
    });
}

function formatDate(value: string): string {
    if (!value) {
return '—';
}

    return new Date(value).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>
