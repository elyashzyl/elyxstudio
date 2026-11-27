<template>
    <Head :title="seatwork.title + ' — Seatwork'" />

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
            href="/teacher/seatworks"
            class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
            style="
                background: var(--gl-surface);
                color: var(--gl-text-secondary);
                border: 1px solid var(--gl-border);
            "
        >
            <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2" />
            <span>Back to seatworks</span>
        </Link>

        <!-- Quick actions -->
        <div class="flex items-center gap-2">
            <button
                v-if="!isClosed"
                @click="togglePublish"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95"
                :style="
                    seatwork.is_published
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
                    seatwork.is_published ? 'Unpublish' : 'Publish'
                }}</span>
            </button>
            <button
                v-if="seatwork.is_published && !isClosed"
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
                v-if="!seatwork.is_published"
                :href="`/teacher/seatworks/${seatwork.id}/edit`"
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
                            rgba(16, 185, 129, 0.2),
                            rgba(59, 130, 246, 0.15)
                        );
                        border: 1px solid rgba(16, 185, 129, 0.3);
                        box-shadow: 0 0 20px rgba(16, 185, 129, 0.15);
                    "
                >
                    <BookOpenCheck
                        class="h-6 w-6"
                        style="color: var(--gl-success)"
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
                            Seatwork
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
                            v-else-if="seatwork.closes_at"
                            class="rounded-full px-2.5 py-0.5 text-[10px] font-semibold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            Closes {{ timeRemaining(seatwork.closes_at) }}
                        </span>
                    </div>

                    <h1
                        class="mt-1.5 text-2xl font-extrabold tracking-tight sm:text-3xl"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ seatwork.title }}
                    </h1>

                    <p
                        v-if="seatwork.instructions"
                        class="mt-2 max-w-3xl text-sm leading-relaxed"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ seatwork.instructions }}
                    </p>

                    <!-- Meta details pills -->
                    <div class="mt-4 flex flex-wrap items-center gap-3 text-xs">
                        <span
                            v-if="seatwork.grade_levels?.length"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <Users class="h-3.5 w-3.5" :stroke-width="2" />
                            <span>{{
                                seatwork.grade_levels
                                    .map((g: any) => g.name)
                                    .join(', ')
                            }}</span>
                        </span>
                        <span
                            v-else-if="seatwork.grade"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <Users class="h-3.5 w-3.5" :stroke-width="2" />
                            <span>{{ seatwork.grade }}</span>
                        </span>

                        <span
                            v-if="seatwork.time_limit_minutes"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <Clock class="h-3.5 w-3.5" :stroke-width="2" />
                            <span
                                >{{ seatwork.time_limit_minutes }} min
                                limit</span
                            >
                        </span>

                        <span
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <RotateCcw class="h-3.5 w-3.5" :stroke-width="2" />
                            <span>{{
                                seatwork.max_attempts
                                    ? `${seatwork.max_attempts} attempt${seatwork.max_attempts > 1 ? 's' : ''} allowed`
                                    : 'Unlimited attempts'
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
                            <span>Recorded: {{ policyLabel(seatwork.grading_policy) }}</span>
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
                        :selected="t.id === seatwork.teacher_id"
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
        <!-- Total Questions -->
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
                    >Questions</span
                >
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg"
                    style="background: rgba(16, 185, 129, 0.15)"
                >
                    <HelpCircle
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
                {{ seatwork.questions.length }}
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                Total activity items
            </p>
        </div>

        <!-- Total Points -->
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
                {{ totalPoints }}
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                Possible score
            </p>
        </div>

        <!-- Attempts -->
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
                {{ submittedAttempts.length }} completed
            </p>
        </div>

        <!-- Average Score -->
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
                    >Average</span
                >
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg"
                    style="background: rgba(251, 191, 36, 0.18)"
                >
                    <Trophy
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
                {{ averagePercentage }}%
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                {{ averageScore }}/{{ totalPoints }} pts avg
            </p>
        </div>
    </div>

    <!-- Main Content Grid (Questions on Left, Attempts on Right) -->
    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        <!-- Questions Column (2/3 width) -->
        <div class="space-y-4 xl:col-span-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: rgba(16, 185, 129, 0.12)"
                    >
                        <ListFilter
                            class="h-4 w-4"
                            style="color: var(--gl-success)"
                            :stroke-width="2"
                        />
                    </div>
                    <h3
                        class="text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Questions Overview
                    </h3>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                    >
                        {{ seatwork.questions.length }}
                    </span>
                </div>
                <span
                    class="text-xs font-medium"
                    style="color: var(--gl-text-muted)"
                >
                    {{ totalPoints }} total pts
                </span>
            </div>

            <!-- Question Card Loop -->
            <div
                v-for="(q, qi) in seatwork.questions"
                :key="q.id"
                class="rounded-2xl p-5 transition-all"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-6 w-6 items-center justify-center rounded-lg text-xs font-extrabold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                            "
                        >
                            {{ qi + 1 }}
                        </span>
                        <span
                            class="rounded-lg px-2.5 py-0.5 text-[11px] font-bold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            {{ typeLabel(q.type) }}
                        </span>
                    </div>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums"
                        style="
                            background: rgba(16, 185, 129, 0.12);
                            color: var(--gl-success);
                        "
                    >
                        {{ q.points }} pt{{ q.points !== 1 ? 's' : '' }}
                    </span>
                </div>

                <p
                    class="text-sm leading-relaxed font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ q.question_text }}
                </p>

                <!-- Type: Multiple Choice & True/False -->
                <div
                    v-if="
                        q.type === 'multiple_choice' || q.type === 'true_false'
                    "
                    class="mt-3 space-y-2"
                >
                    <div
                        v-for="(opt, oi) in q.options"
                        :key="oi"
                        class="flex items-center justify-between rounded-xl px-3.5 py-2 text-xs transition-colors"
                        :style="
                            opt.is_correct
                                ? {
                                      background: 'var(--gl-success-bg)',
                                      color: 'var(--gl-success)',
                                      border: '1px solid rgba(16,185,129,0.2)',
                                  }
                                : {
                                      background: 'var(--gl-surface-2)',
                                      color: 'var(--gl-text-secondary)',
                                      border: '1px solid transparent',
                                  }
                        "
                    >
                        <div class="flex items-center gap-2">
                            <CheckCircle2
                                v-if="opt.is_correct"
                                class="h-4 w-4 shrink-0"
                                :stroke-width="2"
                                style="color: var(--gl-success)"
                            />
                            <Circle
                                v-else
                                class="h-4 w-4 shrink-0 opacity-40"
                                :stroke-width="2"
                                style="color: var(--gl-text-muted)"
                            />
                            <span
                                :class="opt.is_correct ? 'font-semibold' : ''"
                                >{{ opt.option_text }}</span
                            >
                        </div>
                        <span
                            v-if="opt.is_correct"
                            class="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                            style="
                                background: rgba(16, 185, 129, 0.15);
                                color: var(--gl-success);
                            "
                        >
                            Correct Answer
                        </span>
                    </div>
                </div>

                <!-- Type: Identification -->
                <div
                    v-if="q.type === 'identification'"
                    class="mt-3 rounded-xl p-3 text-xs"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <span
                        class="font-medium"
                        style="color: var(--gl-text-muted)"
                        >Accepted Answer:</span
                    >
                    <span
                        class="ml-2 font-bold"
                        style="color: var(--gl-success)"
                    >
                        {{ q.options?.[0]?.option_text || '—' }}
                    </span>
                </div>

                <!-- Type: Enumeration -->
                <div
                    v-if="q.type === 'enumeration'"
                    class="mt-3 space-y-1.5 rounded-xl p-3 text-xs"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <span
                        class="font-medium"
                        style="color: var(--gl-text-muted)"
                        >Accepted Items:</span
                    >
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <span
                            v-for="(opt, oi) in q.options"
                            :key="oi"
                            class="rounded-lg px-2.5 py-1 text-xs font-semibold"
                            style="
                                background: var(--gl-surface);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            {{ oi + 1 }}. {{ opt.option_text }}
                        </span>
                    </div>
                </div>

                <!-- Type: Matching -->
                <div
                    v-if="q.type === 'matching'"
                    class="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2"
                >
                    <div
                        v-for="(p, pi) in q.matching_pairs"
                        :key="pi"
                        class="flex items-center justify-between rounded-xl px-3 py-2"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <span
                            class="font-medium"
                            style="color: var(--gl-text-primary)"
                            >{{ p.left_text }}</span
                        >
                        <div
                            class="flex items-center gap-1.5 font-semibold"
                            style="color: var(--gl-success)"
                        >
                            <ArrowRight
                                class="h-3 w-3"
                                style="color: var(--gl-text-muted)"
                            />
                            <span>{{ p.right_text }}</span>
                        </div>
                    </div>
                </div>

                <!-- Type: Essay (AI Graded) -->
                <div
                    v-if="q.type === 'essay'"
                    class="mt-3 space-y-1.5 rounded-xl p-3.5 text-xs"
                    style="
                        background: linear-gradient(
                            135deg,
                            rgba(59, 130, 246, 0.06),
                            rgba(124, 58, 237, 0.06)
                        );
                        border: 1px solid rgba(59, 130, 246, 0.2);
                    "
                >
                    <div
                        class="flex items-center gap-1.5 font-bold"
                        style="color: var(--gl-primary)"
                    >
                        <Sparkles class="h-3.5 w-3.5" :stroke-width="2" />
                        <span>AI Grading Criteria Rubric</span>
                    </div>
                    <p
                        class="leading-relaxed whitespace-pre-line"
                        style="color: var(--gl-text-primary)"
                    >
                        {{
                            q.criteria ||
                            'Standard relevance, coherence, and depth evaluation.'
                        }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Student Attempts Column (1/3 width) -->
        <div class="space-y-4">
            <div class="flex items-center justify-between flex-wrap gap-2">
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

                <button
                    v-if="attempts.length"
                    type="button"
                    @click="autoRecheckAll"
                    :disabled="isRecheckingAll"
                    class="inline-flex items-center gap-1 rounded-xl px-2.5 py-1.5 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                    style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.3);"
                >
                    <LoaderCircle v-if="isRecheckingAll" :size="13" class="animate-spin" />
                    <Sparkles v-else :size="13" />
                    <span>{{ isRecheckingAll ? 'Checking...' : 'AI Check All' }}</span>
                </button>
            </div>

            <!-- Arrangement and Section Filters -->
            <div
                v-if="attempts.length"
                class="flex flex-wrap items-center justify-between gap-2"
            >
                <!-- Arrangement: Rank or Alphabetical -->
                <div
                    class="flex items-center gap-1 rounded-xl p-1"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                    "
                    role="group"
                    aria-label="Arrangement"
                >
                    <button
                        type="button"
                        @click="sortMode = 'score'"
                        class="rounded-lg px-2.5 py-1 text-[11px] font-bold transition-all"
                        :style="
                            sortMode === 'score'
                                ? {
                                      background: 'var(--gl-primary)',
                                      color: '#fff',
                                  }
                                : { color: 'var(--gl-text-muted)' }
                        "
                        title="Arrange by score rank"
                    >
                        Rank
                    </button>
                    <button
                        type="button"
                        @click="sortMode = 'alpha'"
                        class="rounded-lg px-2.5 py-1 text-[11px] font-bold transition-all"
                        :style="
                            sortMode === 'alpha'
                                ? {
                                      background: 'var(--gl-primary)',
                                      color: '#fff',
                                  }
                                : { color: 'var(--gl-text-muted)' }
                        "
                        title="Arrange alphabetically (A-Z)"
                    >
                        Alphabetical
                    </button>
                </div>

                <!-- Gender Filter -->
                <select
                    v-model="genderFilter"
                    class="rounded-xl border px-2.5 py-1.5 text-xs outline-none transition-colors"
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

                <!-- Section Filter -->
                <select
                    v-if="sections.length > 0"
                    v-model="sectionFilter"
                    class="rounded-xl border px-2.5 py-1.5 text-xs outline-none transition-colors"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                    "
                    title="Filter by section"
                >
                    <option value="">All sections</option>
                    <option v-for="s in sections" :key="s.id" :value="String(s.id)">
                        {{ s.name }}
                    </option>
                </select>
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

            <!-- Empty State -->
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
                    {{ attempts.length ? 'No submissions found' : 'No submissions yet' }}
                </h4>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    {{
                        attempts.length
                            ? (sectionFilter || genderFilter || searchQuery
                                ? 'No students match the selected filters.'
                                : 'No submissions match your query.')
                            : 'When students submit this seatwork, their scores and essay evaluations will appear here.'
                    }}
                </p>
                <button
                    v-if="attempts.length && (sectionFilter || genderFilter || searchQuery)"
                    type="button"
                    @click="sectionFilter = ''; genderFilter = ''; searchQuery = ''"
                    class="mt-3 inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition-all hover:scale-105"
                    style="
                        background: var(--gl-surface-2);
                        color: var(--gl-text-primary);
                        border: 1px solid var(--gl-border);
                    "
                >
                    Clear filters
                </button>
            </div>

            <!-- Attempt list cards -->
            <div v-else class="space-y-2.5">
                <div
                    v-for="(a, index) in searchedAttempts"
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
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white shadow-xs"
                            :style="{ background: avatarBg(a.student?.name) }"
                        >
                            {{
                                (a.student?.name || '?').charAt(0).toUpperCase()
                            }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span
                                    v-if="sortMode === 'score' && a.status === 'submitted'"
                                    class="rounded px-1.5 py-0.5 text-[9px] font-extrabold"
                                    style="background: rgba(124, 58, 237, 0.14); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.25);"
                                >
                                    Rank #{{ index + 1 }}
                                </span>
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
                                <span
                                    v-if="a.attempt_number"
                                    class="rounded px-1.5 py-0.5 text-[9px] font-bold"
                                    style="background: var(--gl-surface-2); color: var(--gl-text-muted);"
                                >
                                    #{{ a.attempt_number }}
                                </span>
                                <span
                                    v-if="a.is_recorded && a.status === 'submitted'"
                                    class="rounded px-1.5 py-0.5 text-[9px] font-bold"
                                    style="background: rgba(16, 185, 129, 0.15); color: var(--gl-success); border: 1px solid rgba(16, 185, 129, 0.3);"
                                >
                                    Recorded
                                </span>
                            </div>
                            <div
                                class="flex items-center gap-1 text-[11px] truncate"
                                style="color: var(--gl-text-muted)"
                            >
                                <span
                                    v-if="a.student?.section?.name"
                                    class="font-medium"
                                    style="color: var(--gl-text-secondary)"
                                >
                                    {{ a.student.section.name }} ·
                                </span>
                                <span>
                                    {{
                                        a.status === 'submitted'
                                            ? formatDate(a.submitted_at)
                                            : 'In progress'
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            v-if="a.status === 'submitted'"
                            class="rounded-lg px-2.5 py-1 text-xs font-extrabold tabular-nums"
                            :style="{
                                background:
                                    a.score / (a.total_points || 1) >= 0.8
                                        ? 'var(--gl-success-bg)'
                                        : a.score / (a.total_points || 1) >= 0.6
                                          ? 'var(--gl-warning-bg)'
                                          : 'var(--gl-danger-bg)',
                                color:
                                    a.score / (a.total_points || 1) >= 0.8
                                        ? 'var(--gl-success)'
                                        : a.score / (a.total_points || 1) >= 0.6
                                          ? 'var(--gl-accent)'
                                          : 'var(--gl-danger)',
                            }"
                        >
                            {{ a.score }}/{{ a.total_points }}
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

                        <!-- Dropdown Menu -->
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
                                <template v-if="a.status === 'submitted'">
                                    <button
                                        v-if="!a.is_recorded"
                                        @click.stop="
                                            openMenu = null;
                                            setRecorded(a.id);
                                        "
                                        class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-surface-2)]"
                                        style="color: var(--gl-accent)"
                                    >
                                        <Award
                                            class="h-3.5 w-3.5"
                                            style="color: var(--gl-accent)"
                                            :stroke-width="2"
                                        />
                                        <span>Set as Recorded Grade</span>
                                    </button>
                                    <button
                                        @click.stop="
                                            openMenu = null;
                                            autoRecheck(a.id);
                                        "
                                        class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-surface-2)]"
                                        style="color: var(--gl-text-primary)"
                                    >
                                        <RotateCw
                                            class="h-3.5 w-3.5"
                                            style="color: var(--gl-primary)"
                                            :stroke-width="2"
                                        />
                                        <span>Auto recheck (AI)</span>
                                    </button>
                                    <Link
                                        :href="recheckUrl(a.id)"
                                        @click="openMenu = null; saveCurrentAttempt(a.id)"
                                        class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-surface-2)]"
                                        style="color: var(--gl-text-primary)"
                                    >
                                        <Eye
                                            class="h-3.5 w-3.5"
                                            style="color: var(--gl-secondary)"
                                            :stroke-width="2"
                                        />
                                        <span>Review &amp; Edit Score</span>
                                    </Link>
                                    <button
                                        @click.stop="
                                            openMenu = null;
                                            deleteAttempt(a);
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
                                </template>
                                <span
                                    v-else
                                    class="block px-3 py-2 text-xs"
                                    style="color: var(--gl-text-muted)"
                                >
                                    Student is taking seatwork
                                </span>
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
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    Award,
    BookOpenCheck,
    CheckCircle2,
    Circle,
    ClipboardCheck,
    ClipboardList,
    Clock,
    Ellipsis,
    Eye,
    HelpCircle,
    ListFilter,
    LoaderCircle,
    Lock,
    Pencil,
    RefreshCw,
    RotateCcw,
    RotateCw,
    Search,
    Sparkles,
    Star,
    Trash2,
    Trophy,
    UploadCloud,
    Users,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';

const props = defineProps<{
    seatwork: any;
    attempts: any[];
    sections?: any[];
    totalPoints: number;
    teachers?: any[];
}>();

const openMenu = ref<number | null>(null);
const searchQuery = ref('');
const isRecheckingAll = ref(false);
const lastAttemptId = ref<number | null>(null);

const storageKey =
    'seatwork_show_' +
    (typeof window !== 'undefined' ? window.location.pathname : '');

const sectionFilter = ref(
    (typeof sessionStorage !== 'undefined' &&
        sessionStorage.getItem(storageKey + '_section')) ||
        '',
);

const genderFilter = ref(
    (typeof sessionStorage !== 'undefined' &&
        sessionStorage.getItem(storageKey + '_gender')) ||
        '',
);

const sortMode = ref<'score' | 'alpha'>(
    ((typeof sessionStorage !== 'undefined' &&
        sessionStorage.getItem(storageKey + '_sort')) as 'score' | 'alpha') ||
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

const sections = computed(() => {
    const map = new Map<number, { id: number; name: string }>();

    if (props.sections) {
        props.sections.forEach((s: any) => {
            if (s && s.id) {
map.set(s.id, { id: s.id, name: s.name });
}
        });
    }

    (props.attempts || []).forEach((a: any) => {
        if (a.student?.section) {
            map.set(a.student.section.id, {
                id: a.student.section.id,
                name: a.student.section.name,
            });
        }
    });

    return Array.from(map.values()).sort((a, b) =>
        a.name.localeCompare(b.name),
    );
});

function saveCurrentAttempt(attemptId: number) {
    lastAttemptId.value = attemptId;
    sessionStorage.setItem(`last_seatwork_attempt_${props.seatwork.id}`, String(attemptId));
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

function recheckUrl(attemptId: number): string {
    const returnUrl = encodeURIComponent(
        window.location.pathname + window.location.search + `#attempt-${attemptId}`,
    );

    return `/teacher/seatworks/${props.seatwork.id}/recheck/${attemptId}?return_to=${returnUrl}`;
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

    const saved = sessionStorage.getItem(`last_seatwork_attempt_${props.seatwork.id}`);

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

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const isClosed = computed(() => {
    if (!props.seatwork.closes_at) {
return false;
}

    return new Date(props.seatwork.closes_at) < new Date();
});

const statusLabel = computed(() => {
    if (!props.seatwork.is_published) {
return 'draft';
}

    if (isClosed.value) {
return 'finished';
}

    return 'published';
});

const submittedAttempts = computed(() =>
    props.attempts.filter((a) => a.status === 'submitted'),
);

const currentSubmittedAttempts = computed(() => {
    const base = sectionFilter.value
        ? (props.attempts || []).filter(
              (a: any) => String(a.student?.section_id) === String(sectionFilter.value),
          )
        : (props.attempts || []);

    return base.filter((a: any) => a.status === 'submitted');
});

const officialSubmittedAttempts = computed(() => {
    const recorded = currentSubmittedAttempts.value.filter(
        (a: any) => a.is_recorded,
    );

    return recorded.length ? recorded : currentSubmittedAttempts.value;
});

const filteredAttempts = computed(() => {
    let list = [...(props.attempts || [])];

    if (sectionFilter.value) {
        list = list.filter(
            (a: any) => String(a.student?.section_id) === String(sectionFilter.value),
        );
    }

    if (genderFilter.value) {
        list = list.filter(
            (a: any) => (a.student?.gender || '').toLowerCase() === genderFilter.value.toLowerCase(),
        );
    }

    if (sortMode.value === 'alpha') {
        return list.sort((a: any, b: any) => {
            const nameCmp = (a.student?.name || '').localeCompare(
                b.student?.name || '',
            );

            if (nameCmp !== 0) {
return nameCmp;
}

            return (a.attempt_number || 0) - (b.attempt_number || 0);
        });
    }

    // sortMode === 'score' (Rank)
    return list.sort((a: any, b: any) => {
        // Submitted attempts first
        const aSub = a.status === 'submitted' ? 1 : 0;
        const bSub = b.status === 'submitted' ? 1 : 0;

        if (aSub !== bSub) {
return bSub - aSub;
}

        // Higher score first
        const scoreDiff = (b.score ?? 0) - (a.score ?? 0);

        if (scoreDiff !== 0) {
return scoreDiff;
}

        // If equal score, sort alphabetically by student name
        return (a.student?.name || '').localeCompare(b.student?.name || '');
    });
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

const averageScore = computed(() => {
    if (!officialSubmittedAttempts.value.length) {
return 0;
}

    const sum = officialSubmittedAttempts.value.reduce(
        (acc, a) => acc + (a.score || 0),
        0,
    );

    return Math.round((sum / officialSubmittedAttempts.value.length) * 10) / 10;
});

const averagePercentage = computed(() => {
    if (!props.totalPoints || !officialSubmittedAttempts.value.length) {
return 0;
}

    return Math.round((averageScore.value / props.totalPoints) * 100);
});

const reassignSelect = ref<HTMLSelectElement | null>(null);

function reassign() {
    if (!reassignSelect.value) {
return;
}

    router.patch(
        `/teacher/seatworks/${props.seatwork.id}/reassign`,
        {
            teacher_id: reassignSelect.value.value,
        },
        { preserveScroll: true },
    );
}

function typeLabel(t: string): string {
    const labels: Record<string, string> = {
        identification: 'Identification',
        enumeration: 'Enumeration',
        true_false: 'True/False',
        multiple_choice: 'Multiple Choice',
        matching: 'Matching Type',
        essay: 'Essay (AI Graded)',
    };

    return labels[t] ?? t;
}

function policyLabel(p?: string): string {
    const labels: Record<string, string> = {
        first: '1st Attempt',
        second: '2nd Attempt',
        third: '3rd Attempt',
        highest: 'Highest Score',
        latest: 'Latest Attempt',
        manual: 'Manual Selection',
    };

    return labels[p || 'first'] ?? '1st Attempt';
}

function setRecorded(attemptId: number) {
    saveCurrentAttempt(attemptId);
    router.post(
        `/teacher/seatworks/${props.seatwork.id}/attempts/${attemptId}/record`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                scrollToAttempt(attemptId);
            },
        },
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

function autoRecheck(attemptId: number) {
    saveCurrentAttempt(attemptId);
    router.put(
        `/teacher/seatworks/${props.seatwork.id}/auto-recheck/${attemptId}`,
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

function autoRecheckAll() {
    if (isRecheckingAll.value) {
return;
}

    isRecheckingAll.value = true;
    router.post(
        `/teacher/seatworks/${props.seatwork.id}/auto-recheck-all`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isRecheckingAll.value = false;
            },
        },
    );
}

function deleteAttempt(a: any) {
    if (
        !confirm(
            `Delete ${a.student?.name ?? 'this student'}'s attempt? They will be able to retake.`,
        )
    ) {
        return;
    }

    router.delete('/teacher/results/attempt', {
        data: { type: 'Seatwork', id: a.id },
        preserveScroll: true,
    });
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
        `/teacher/seatworks/${props.seatwork.id}/publish`,
        {},
        { preserveScroll: true },
    );
}

function toggleReopen() {
    router.patch(
        `/teacher/seatworks/${props.seatwork.id}/reopen`,
        {},
        { preserveScroll: true },
    );
}

function closeNow() {
    router.patch(
        `/teacher/seatworks/${props.seatwork.id}/close-now`,
        {},
        { preserveScroll: true },
    );
}
</script>
