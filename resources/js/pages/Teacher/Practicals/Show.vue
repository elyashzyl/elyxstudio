<template>
    <Head :title="practical.title + ' — Practical'" />

    <!-- Flash notification -->
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

    <!-- Navigation / Breadcrumb -->
    <div class="mb-5 flex items-center justify-between">
        <Link
            href="/teacher/practicals"
            class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
            style="
                background: var(--gl-surface);
                color: var(--gl-text-secondary);
                border: 1px solid var(--gl-border);
            "
        >
            <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2" />
            <span>Back to practicals</span>
        </Link>

        <!-- Quick actions -->
        <div class="flex items-center gap-2">
            <button
                v-if="!isClosed"
                @click="togglePublish"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95"
                :style="
                    practical.is_published
                        ? {
                              background: 'var(--gl-surface)',
                              color: 'var(--gl-text-secondary)',
                              border: '1px solid var(--gl-border)',
                          }
                        : {
                              background: 'var(--gl-primary)',
                              color: '#fff',
                              boxShadow: '0 0 16px var(--gl-primary-glow)',
                          }
                "
            >
                <UploadCloud class="h-3.5 w-3.5" :stroke-width="2" />
                <span>{{
                    practical.is_published ? 'Unpublish' : 'Publish'
                }}</span>
            </button>
            <button
                v-if="practical.is_published && !isClosed"
                @click="closeNow"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95"
                style="
                    color: var(--gl-danger);
                    border: 1px solid rgba(239, 68, 68, 0.3);
                    background: var(--gl-danger-bg);
                "
            >
                <Lock class="h-3.5 w-3.5" :stroke-width="2" />
                <span>Close now</span>
            </button>
            <button
                v-if="isClosed"
                @click="toggleReopen"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-primary);
                    border: 1px solid rgba(59, 130, 246, 0.35);
                "
            >
                <RefreshCw class="h-3.5 w-3.5" :stroke-width="2" />
                <span>Reopen</span>
            </button>
            <Link
                :href="`/teacher/practicals/${practical.id}/edit`"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border: 1px solid var(--gl-border);
                "
            >
                <Pencil class="h-3.5 w-3.5" :stroke-width="2" />
                <span>Edit</span>
            </Link>
            <button
                @click="confirmDestroy"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95"
                style="
                    color: var(--gl-danger);
                    border: 1px solid rgba(239, 68, 68, 0.3);
                    background: var(--gl-danger-bg);
                "
            >
                <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />
                <span>Delete</span>
            </button>
        </div>
    </div>

    <!-- Header Banner -->
    <div
        class="mb-6 rounded-3xl p-6 transition-all"
        style="
            background: var(--gl-surface);
            border: 1px solid var(--gl-border);
        "
    >
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-4">
                <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl"
                    style="
                        background: linear-gradient(
                            135deg,
                            rgba(251, 191, 36, 0.2),
                            rgba(249, 115, 22, 0.15)
                        );
                        border: 1px solid rgba(251, 191, 36, 0.3);
                        box-shadow: 0 0 20px rgba(251, 191, 36, 0.15);
                    "
                >
                    <Award
                        class="h-6 w-6"
                        style="color: var(--gl-accent)"
                        :stroke-width="2"
                    />
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-full px-2.5 py-0.5 text-[10px] font-bold tracking-wider uppercase"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-muted);
                            "
                        >
                            Practical
                        </span>
                        <StatusBadge :status="statusLabel" />
                        <span
                            v-if="isClosed"
                            class="rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                            style="
                                background: var(--gl-danger-bg);
                                color: var(--gl-danger);
                            "
                        >
                            Closed
                        </span>
                        <span
                            v-else-if="practical.closes_at"
                            class="rounded-full px-2.5 py-0.5 text-[10px] font-semibold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            Closes {{ timeRemaining(practical.closes_at) }}
                        </span>
                    </div>

                    <h1
                        class="mt-1.5 text-2xl font-extrabold tracking-tight sm:text-3xl"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ practical.title }}
                    </h1>

                    <p
                        v-if="practical.instructions"
                        class="mt-2 max-w-3xl text-sm leading-relaxed whitespace-pre-line"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ practical.instructions }}
                    </p>

                    <!-- Meta details pills -->
                    <div class="mt-4 flex flex-wrap items-center gap-3 text-xs">
                        <span
                            v-if="practical.grade_levels?.length"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <Users class="h-3.5 w-3.5" :stroke-width="2" />
                            <span>{{
                                practical.grade_levels
                                    .map((g: any) => g.name)
                                    .join(', ')
                            }}</span>
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <Award class="h-3.5 w-3.5" :stroke-width="2" />
                            <span
                                >{{ practical.criteria.length }} rubric
                                criteria</span
                            >
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reassign Row (if superadmin/teachers exist) -->
        <div
            v-if="teachers?.length"
            class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-[var(--gl-border)] pt-4 text-xs"
        >
            <div class="flex items-center gap-2">
                <span class="font-medium" style="color: var(--gl-text-muted)"
                    >Assigned Teacher:</span
                >
                <select
                    ref="reassignSelect"
                    class="rounded-xl border px-3 py-1.5 text-xs transition-colors outline-none"
                    style="
                        background: var(--gl-surface-2);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                    "
                >
                    <option
                        v-for="t in teachers"
                        :key="t.id"
                        :value="t.id"
                        :selected="t.id === practical.teacher_id"
                    >
                        {{ t.name }}
                    </option>
                </select>
                <button
                    @click="reassign"
                    class="rounded-xl px-3 py-1.5 text-xs font-bold transition-all hover:scale-105"
                    style="
                        background: var(--gl-surface-2);
                        color: var(--gl-primary);
                        border: 1px solid var(--gl-border);
                    "
                >
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- Stat Cards Row -->
    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        <!-- Criteria -->
        <div
            class="rounded-2xl p-4 transition-all"
            style="
                background: rgba(251, 191, 36, 0.07);
                border: 1px solid rgba(251, 191, 36, 0.18);
            "
        >
            <div class="mb-2 flex items-center justify-between">
                <span
                    class="text-xs font-medium"
                    style="color: var(--gl-text-secondary)"
                    >Criteria</span
                >
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg"
                    style="background: rgba(251, 191, 36, 0.15)"
                >
                    <Award
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
                {{ practical.criteria.length }}
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                Rubric dimensions
            </p>
        </div>

        <!-- Max Points -->
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
                    >Max Points</span
                >
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg"
                    style="background: rgba(59, 130, 246, 0.15)"
                >
                    <Star
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
                {{ totalMax }}
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                Total possible score
            </p>
        </div>

        <!-- Submissions -->
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
                    >Submissions</span
                >
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg"
                    style="background: rgba(124, 58, 237, 0.15)"
                >
                    <ClipboardCheck
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
                {{ attempts.length }}
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                {{
                    attempts.filter((a) => a.status === 'submitted').length
                }}
                completed
            </p>
        </div>

        <!-- Average Score -->
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
                    >Average</span
                >
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg"
                    style="background: rgba(16, 185, 129, 0.15)"
                >
                    <Trophy
                        class="h-4 w-4"
                        style="color: var(--gl-success)"
                        :stroke-width="2"
                    />
                </div>
            </div>
            <p
                class="text-2xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{
                    totalMax > 0
                        ? Math.round((sectionAvg / totalMax) * 100)
                        : 0
                }}%
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                {{ sectionAvg }}/{{ totalMax }} pts avg
            </p>
        </div>
    </div>

    <!-- Main Grid (Rubric Left, Submissions Right) -->
    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        <!-- Scoring Rubric Column (2/3 width) -->
        <div class="space-y-4 xl:col-span-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: rgba(251, 191, 36, 0.14)"
                    >
                        <Award
                            class="h-4 w-4"
                            style="color: var(--gl-accent)"
                            :stroke-width="2"
                        />
                    </div>
                    <h3
                        class="text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Scoring Rubric Dimensions
                    </h3>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                    >
                        {{ practical.criteria.length }}
                    </span>
                </div>
                <span
                    class="text-xs font-medium"
                    style="color: var(--gl-text-muted)"
                >
                    {{ totalMax }} total pts
                </span>
            </div>

            <!-- Rubric Card Loop -->
            <div class="space-y-3">
                <div
                    v-for="(c, ci) in practical.criteria"
                    :key="c.id"
                    class="rounded-2xl p-5 transition-all"
                    style="
                        background: var(--gl-surface);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-xs font-extrabold"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                "
                            >
                                {{ ci + 1 }}
                            </span>
                            <div>
                                <h4
                                    class="text-sm font-bold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ c.criterion_name }}
                                </h4>
                                <p
                                    v-if="c.description"
                                    class="mt-1 text-xs leading-relaxed"
                                    style="color: var(--gl-text-secondary)"
                                >
                                    {{ c.description }}
                                </p>
                            </div>
                        </div>

                        <span
                            class="shrink-0 rounded-full px-3 py-1 text-xs font-extrabold tabular-nums"
                            style="
                                background: rgba(251, 191, 36, 0.12);
                                color: var(--gl-accent);
                            "
                        >
                            {{ c.max_points }} pts
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submissions & Leaderboard (1/3 width) -->
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: rgba(124, 58, 237, 0.12)"
                    >
                        <Trophy
                            class="h-4 w-4"
                            style="color: var(--gl-secondary)"
                            :stroke-width="2"
                        />
                    </div>
                    <h3
                        class="text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Submissions
                    </h3>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                    >
                        {{ attempts.length }}
                    </span>
                </div>

                <div class="flex items-center gap-1.5 flex-wrap">
                    <button
                        v-if="attempts.length"
                        type="button"
                        @click="aiGradeAll"
                        :disabled="isGradingAll"
                        class="inline-flex items-center gap-1 rounded-xl px-2.5 py-1.5 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                        style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.3);"
                    >
                        <LoaderCircle v-if="isGradingAll" :size="13" class="animate-spin" />
                        <Sparkles v-else :size="13" />
                        <span>{{ isGradingAll ? 'Grading...' : 'AI Grade All' }}</span>
                    </button>

                    <div
                        class="flex items-center gap-1 rounded-xl p-1"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <button
                            @click="sortMode = 'score'"
                            class="rounded-lg px-2.5 py-1 text-[10px] font-bold transition-all"
                            :style="
                                sortMode === 'score'
                                    ? {
                                          background: 'var(--gl-primary)',
                                          color: '#fff',
                                      }
                                    : { color: 'var(--gl-text-muted)' }
                            "
                        >
                            Rank
                        </button>
                        <button
                            @click="sortMode = 'alpha'"
                            class="rounded-lg px-2.5 py-1 text-[10px] font-bold transition-all"
                            :style="
                                sortMode === 'alpha'
                                    ? {
                                          background: 'var(--gl-primary)',
                                          color: '#fff',
                                      }
                                    : { color: 'var(--gl-text-muted)' }
                            "
                        >
                            A-Z
                        </button>
                    </div>

                    <!-- Gender Filter -->
                    <select
                        v-model="genderFilter"
                        class="rounded-xl border px-2 py-1.5 text-xs outline-none"
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

                    <select
                        v-if="sections.length > 0"
                        v-model="sectionFilter"
                        class="rounded-xl border px-2 py-1.5 text-xs outline-none"
                        style="
                            background: var(--gl-surface);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                    >
                        <option value="">All sections</option>
                        <option v-for="s in sections" :key="s.id" :value="s.id">
                            {{ s.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Search student filter -->
            <div v-if="attempts.length > 3" class="relative">
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2"
                    style="color: var(--gl-text-muted)"
                    :stroke-width="2"
                />
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Filter students..."
                    class="w-full rounded-xl py-2 pr-3 pl-9 text-xs transition-all outline-none"
                    style="
                        background: var(--gl-surface);
                        border: 1px solid var(--gl-border);
                        color: var(--gl-text-primary);
                    "
                />
            </div>

            <!-- Empty -->
            <div
                v-if="!searchedAttempts.length"
                class="rounded-2xl p-8 text-center"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div
                    class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl"
                    style="
                        background: var(--gl-surface-2);
                        color: var(--gl-text-muted);
                    "
                >
                    <Users class="h-6 w-6" :stroke-width="1.5" />
                </div>
                <h4
                    class="text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    No submissions found
                </h4>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    {{
                        sectionFilter
                            ? 'No students from this section match.'
                            : 'Results will appear here once students submit the practical.'
                    }}
                </p>
            </div>

            <!-- Attempt cards loop -->
            <div v-else class="space-y-2.5">
                <div
                    v-for="a in searchedAttempts"
                    :key="a.id"
                    :id="'attempt-' + a.id"
                    class="relative flex items-center justify-between rounded-2xl p-3.5 transition-all hover:scale-[1.01]"
                    :class="[
                        openMenu === a.id ? 'z-30 !scale-100 shadow-xl' : '',
                        lastAttemptId === a.id
                            ? 'ring-2 ring-[var(--gl-primary)] shadow-lg shadow-[var(--gl-primary-glow)]'
                            : '',
                    ]"
                    style="
                        background: var(--gl-surface);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-extrabold text-white shadow-xs"
                            :style="{ background: avatarBg(a.student?.name) }"
                        >
                            {{
                                (a.student?.name || '?').charAt(0).toUpperCase()
                            }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <p
                                    class="truncate text-xs font-bold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ a.student?.name }}
                                </p>
                                <span
                                    v-if="a.student?.gender"
                                    class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider"
                                    :style="
                                        a.student.gender.toLowerCase() === 'female'
                                            ? { background: 'rgba(236, 72, 153, 0.12)', color: '#ec4899', border: '1px solid rgba(236, 72, 153, 0.25)' }
                                            : { background: 'rgba(59, 130, 246, 0.12)', color: 'var(--gl-primary)', border: '1px solid rgba(59, 130, 246, 0.25)' }
                                    "
                                >
                                    {{ a.student.gender }}
                                </span>
                            </div>
                            <p
                                class="text-[11px]"
                                style="color: var(--gl-text-muted)"
                            >
                                {{
                                    a.submitted_at
                                        ? formatDate(a.submitted_at)
                                        : 'In progress'
                                }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            v-if="a.status === 'submitted'"
                            class="rounded-lg px-2.5 py-1 text-xs font-extrabold tabular-nums"
                            :style="{
                                background:
                                    a.total_score / (totalMax || 1) >= 0.8
                                        ? 'var(--gl-success-bg)'
                                        : a.total_score / (totalMax || 1) >= 0.6
                                          ? 'var(--gl-warning-bg)'
                                          : 'var(--gl-danger-bg)',
                                color:
                                    a.total_score / (totalMax || 1) >= 0.8
                                        ? 'var(--gl-success)'
                                        : a.total_score / (totalMax || 1) >= 0.6
                                          ? 'var(--gl-accent)'
                                          : 'var(--gl-danger)',
                            }"
                        >
                            {{ a.total_score }}/{{ totalMax }}
                        </span>
                        <span
                            v-else
                            class="rounded-lg px-2 py-0.5 text-[10px] font-bold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-muted);
                            "
                        >
                            In progress
                        </span>

                        <!-- Actions dropdown -->
                        <div class="relative">
                            <button
                                @click.stop="
                                    openMenu = openMenu === a.id ? null : a.id
                                "
                                title="Actions"
                                class="flex h-7 w-7 items-center justify-center rounded-lg transition-colors hover:bg-[var(--gl-surface-2)]"
                                style="color: var(--gl-text-muted)"
                            >
                                <Ellipsis class="h-4 w-4" :stroke-width="2" />
                            </button>
                            <div
                                v-if="openMenu === a.id"
                                class="absolute top-full right-0 z-40 mt-1 w-44 overflow-hidden rounded-xl py-1 shadow-2xl backdrop-blur-md"
                                style="
                                    background: var(--gl-surface);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                <button
                                    type="button"
                                    @click.stop="
                                        openMenu = null;
                                        autoRecheck(a.id);
                                    "
                                    class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-surface-2)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <Sparkles
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-primary)"
                                        :stroke-width="2"
                                    />
                                    <span>Auto recheck</span>
                                </button>
                                <Link
                                    :href="gradeUrl(a.id)"
                                    @click="openMenu = null; saveCurrentAttempt(a.id)"
                                    class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-surface-2)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <ClipboardCheck
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-primary)"
                                        :stroke-width="2"
                                    />
                                    <span>Grade submission</span>
                                </Link>
                                <Link
                                    :href="recheckUrl(a.id)"
                                    @click="openMenu = null; saveCurrentAttempt(a.id)"
                                    class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-surface-2)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <RefreshCw
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-secondary)"
                                        :stroke-width="2"
                                    />
                                    <span>Recheck criteria</span>
                                </Link>
                                <button
                                    v-if="a.submission_file_url"
                                    @click="
                                        openMenu = null;
                                        previewImg = a.submission_file_url;
                                    "
                                    class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-surface-2)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <Eye
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-accent)"
                                        :stroke-width="2"
                                    />
                                    <span>View attached work</span>
                                </button>
                                <button
                                    @click.stop="
                                        openMenu = null;
                                        confirmDelete(a);
                                    "
                                    class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-danger-bg)]"
                                    style="color: var(--gl-danger)"
                                >
                                    <Trash2
                                        class="h-3.5 w-3.5"
                                        :stroke-width="2"
                                    />
                                    <span>Delete attempt</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backdrop for actions menu -->
    <div
        v-if="openMenu !== null"
        class="fixed inset-0 z-20"
        @click="openMenu = null"
    ></div>

    <!-- Image Preview Modal -->
    <Teleport to="body">
        <div
            v-if="previewImg"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(2px)"
            @click.self="previewImg = null"
        >
            <div class="relative max-h-[90vh] max-w-3xl p-4">
                <button
                    @click="previewImg = null"
                    class="absolute -top-2 -right-2 rounded-full p-1.5 transition-colors hover:scale-105"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-muted);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <X class="h-4 w-4" :stroke-width="2" />
                </button>
                <img
                    :src="previewImg"
                    class="max-h-[85vh] rounded-2xl shadow-2xl"
                    style="border: 1px solid var(--gl-border)"
                />
            </div>
        </div>

        <!-- Delete Attempt Modal -->
        <div
            v-if="deleting"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(2px)"
            @click.self="deleting = null"
        >
            <div
                class="mx-4 w-full max-w-sm rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <h3
                    class="mb-2 text-base font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Delete attempt?
                </h3>
                <p
                    class="text-sm leading-relaxed"
                    style="color: var(--gl-text-secondary)"
                >
                    This will permanently delete
                    <strong>{{ deleting?.student?.name }}</strong
                    >'s attempt. They will be permitted to retake.
                </p>
                <div class="mt-5 flex justify-end gap-3">
                    <button
                        @click="deleting = null"
                        class="rounded-xl px-4 py-2 text-xs font-semibold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        Cancel
                    </button>
                    <button
                        @click="destroyAttempt"
                        class="rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105"
                        style="background: var(--gl-danger)"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Delete Practical Modal -->
        <div
            v-if="deletingPractical"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(2px)"
            @click.self="deletingPractical = false"
        >
            <div
                class="mx-4 w-full max-w-sm rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <h3
                    class="mb-2 text-base font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Delete practical?
                </h3>
                <p
                    class="text-sm leading-relaxed"
                    style="color: var(--gl-text-secondary)"
                >
                    This will permanently delete
                    <strong>{{ practical.title }}</strong> and all associated
                    student attempts.
                </p>
                <div class="mt-5 flex justify-end gap-3">
                    <button
                        @click="deletingPractical = false"
                        class="rounded-xl px-4 py-2 text-xs font-semibold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        Cancel
                    </button>
                    <button
                        @click="destroyPractical"
                        class="rounded-xl px-4 py-2 text-xs font-bold text-white transition-all hover:scale-105"
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
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Award,
    ClipboardCheck,
    Clock,
    Ellipsis,
    Eye,
    LoaderCircle,
    Lock,
    Pencil,
    RefreshCw,
    Search,
    Sparkles,
    Star,
    Trash2,
    Trophy,
    UploadCloud,
    Users,
    X,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';

const props = defineProps<{
    practical: any;
    attempts: any[];
    totalMax: number;
    teachers?: any[];
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const openMenu = ref<number | null>(null);
const searchQuery = ref('');
const isGradingAll = ref(false);
const lastAttemptId = ref<number | null>(null);

function saveCurrentAttempt(attemptId: number) {
    lastAttemptId.value = attemptId;
    sessionStorage.setItem(`last_practical_attempt_${props.practical.id}`, String(attemptId));
}

function scrollToAttempt(attemptId: number) {
    lastAttemptId.value = attemptId;
    let attempts = 0;
    const maxAttempts = 25;
    const interval = setInterval(() => {
        attempts++;
        const el = document.getElementById(`attempt-${attemptId}`);

        if (el) {
            clearInterval(interval);
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else if (attempts >= maxAttempts) {
            clearInterval(interval);
        }
    }, 40);
}

function gradeUrl(attemptId: number): string {
    const returnUrl = encodeURIComponent(
        window.location.pathname + window.location.search + `#attempt-${attemptId}`,
    );

    return `/teacher/practicals/${props.practical.id}/grade/${attemptId}?return_to=${returnUrl}`;
}

function recheckUrl(attemptId: number): string {
    const returnUrl = encodeURIComponent(
        window.location.pathname + window.location.search + `#attempt-${attemptId}`,
    );

    return `/teacher/practicals/${props.practical.id}/recheck/${attemptId}?return_to=${returnUrl}`;
}

function autoRecheck(attemptId: number) {
    saveCurrentAttempt(attemptId);
    router.put(
        `/teacher/practicals/${props.practical.id}/auto-recheck/${attemptId}`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                scrollToAttempt(attemptId);
            },
        },
    );
}

function aiGradeAll() {
    if (isGradingAll.value) {
return;
}

    isGradingAll.value = true;
    router.post(
        `/teacher/practicals/${props.practical.id}/ai-grade-all`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isGradingAll.value = false;
            },
        },
    );
}

let pollTimer: ReturnType<typeof setInterval> | null = null;
let isPolling = false;

function pollAttempts() {
    if (isPolling || (typeof document !== 'undefined' && document.hidden)) {
return;
}

    isPolling = true;
    router.reload({
        only: ['attempts'],
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isPolling = false;
        },
    });
}

function handleVisibilityChange() {
    if (typeof document !== 'undefined' && !document.hidden) {
        pollAttempts();
    }
}

onMounted(() => {
    if (window.location.hash.startsWith('#attempt-')) {
        const id = parseInt(window.location.hash.replace('#attempt-', ''), 10);

        if (id) {
            scrollToAttempt(id);

            return;
        }
    }

    const saved = sessionStorage.getItem(`last_practical_attempt_${props.practical.id}`);

    if (saved) {
        const id = parseInt(saved, 10);

        if (id) {
scrollToAttempt(id);
}
    }

    pollTimer = setInterval(pollAttempts, 3000);

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
const previewImg = ref<string | null>(null);
const deleting = ref<any>(null);
const deletingPractical = ref(false);

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

const storageKey = 'practical_show_' + (typeof window !== 'undefined' ? window.location.pathname : '');
const sectionFilter = ref(
    (typeof sessionStorage !== 'undefined' && sessionStorage.getItem(storageKey + '_section')) || '',
);
const genderFilter = ref(
    (typeof sessionStorage !== 'undefined' && sessionStorage.getItem(storageKey + '_gender')) || '',
);
const sortMode = ref<'alpha' | 'score'>(
    ((typeof sessionStorage !== 'undefined' && sessionStorage.getItem(storageKey + '_sort')) as 'alpha' | 'score') ||
        'score',
);

function persistState() {
    if (typeof sessionStorage !== 'undefined') {
        sessionStorage.setItem(storageKey + '_section', sectionFilter.value);
        sessionStorage.setItem(storageKey + '_gender', genderFilter.value);
        sessionStorage.setItem(storageKey + '_sort', sortMode.value);
    }
}
watch([sectionFilter, genderFilter, sortMode], persistState);

const isClosed = computed(() => {
    if (!props.practical.closes_at) {
return false;
}

    return new Date(props.practical.closes_at) < new Date();
});

const statusLabel = computed(() => {
    if (!props.practical.is_published) {
return 'draft';
}

    if (isClosed.value) {
return 'finished';
}

    return 'published';
});

const sections = computed(() => {
    const seen = new Set<number>();

    return props.attempts
        .map((a: any) => a.student?.section)
        .filter((s: any) => s && !seen.has(s.id) && seen.add(s.id));
});

const filteredAttempts = computed(() => {
    let list = sectionFilter.value
        ? props.attempts.filter(
              (a: any) => String(a.student?.section_id) === String(sectionFilter.value),
          )
        : [...props.attempts];

    if (genderFilter.value) {
        list = list.filter(
            (a: any) => (a.student?.gender || '').toLowerCase() === genderFilter.value.toLowerCase(),
        );
    }

    if (sortMode.value === 'alpha') {
        return list.sort((a: any, b: any) =>
            (a.student?.name || '').localeCompare(b.student?.name || ''),
        );
    }

    return list.sort(
        (a: any, b: any) => (b.total_score ?? 0) - (a.total_score ?? 0),
    );
});

const searchedAttempts = computed(() => {
    const q = searchQuery.value.trim().toLowerCase();

    if (!q) {
return filteredAttempts.value;
}

    return filteredAttempts.value.filter((a: any) =>
        (a.student?.name || '').toLowerCase().includes(q),
    );
});

const sectionAvg = computed(() => {
    if (!filteredAttempts.value.length) {
return 0;
}

    const sum = filteredAttempts.value.reduce(
        (acc: number, a: any) => acc + (a.total_score || 0),
        0,
    );

    return Math.round((sum / filteredAttempts.value.length) * 10) / 10;
});

const reassignSelect = ref<HTMLSelectElement | null>(null);

function reassign() {
    if (!reassignSelect.value) {
return;
}

    router.patch(
        `/teacher/practicals/${props.practical.id}/reassign`,
        { teacher_id: reassignSelect.value.value },
        { preserveScroll: true },
    );
}

function formatDate(v: string): string {
    return v
        ? new Date(v).toLocaleDateString(undefined, {
              year: 'numeric',
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '—';
}

function timeRemaining(dt: string): string {
    const diff = new Date(dt).getTime() - Date.now();

    if (diff <= 0) {
return 'now';
}

    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);

    return `in ${h}h ${m}m`;
}

function togglePublish() {
    router.patch(
        `/teacher/practicals/${props.practical.id}/publish`,
        {},
        { preserveScroll: true },
    );
}

function toggleReopen() {
    router.patch(
        `/teacher/practicals/${props.practical.id}/reopen`,
        {},
        { preserveScroll: true },
    );
}

function closeNow() {
    router.patch(
        `/teacher/practicals/${props.practical.id}/close-now`,
        {},
        { preserveScroll: true },
    );
}

function confirmDelete(attempt: any) {
    deleting.value = attempt;
}

function destroyAttempt() {
    if (!deleting.value) {
return;
}

    router.delete('/teacher/results/attempt', {
        data: { type: 'Practical', id: deleting.value.id },
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = null;
        },
    });
}

function confirmDestroy() {
    deletingPractical.value = true;
}

function destroyPractical() {
    router.delete(`/teacher/practicals/${props.practical.id}`, {
        onSuccess: () => {
            deletingPractical.value = false;
        },
    });
}
</script>
