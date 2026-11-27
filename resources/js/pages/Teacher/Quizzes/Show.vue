<template>
    <Head :title="quiz.title + ' — Quiz'" />

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
            href="/teacher/quizzes"
            class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
            style="
                background: var(--gl-surface);
                color: var(--gl-text-secondary);
                border: 1px solid var(--gl-border);
            "
        >
            <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2" />
            <span>Back to quizzes</span>
        </Link>

        <!-- Quick actions -->
        <div class="flex items-center gap-2">
            <button
                v-if="!isClosed"
                @click="togglePublish"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95"
                :style="
                    quiz.is_published
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
                <span>{{ quiz.is_published ? 'Unpublish' : 'Publish' }}</span>
            </button>
            <button
                v-if="quiz.is_published && !isClosed"
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
                v-if="!quiz.is_published"
                :href="`/teacher/quizzes/${quiz.id}/edit`"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border: 1px solid var(--gl-border);
                "
            >
                <PenSquare class="h-3.5 w-3.5" :stroke-width="2" />
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
                            rgba(59, 130, 246, 0.2),
                            rgba(124, 58, 237, 0.15)
                        );
                        border: 1px solid rgba(59, 130, 246, 0.3);
                        box-shadow: 0 0 20px rgba(59, 130, 246, 0.15);
                    "
                >
                    <FileQuestion
                        class="h-6 w-6"
                        style="color: var(--gl-primary)"
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
                            Quiz
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
                            v-else-if="quiz.closes_at"
                            class="rounded-full px-2.5 py-0.5 text-[10px] font-semibold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            Closes {{ timeRemaining(quiz.closes_at) }}
                        </span>
                    </div>

                    <h1
                        class="mt-1.5 text-2xl font-extrabold tracking-tight sm:text-3xl"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ quiz.title }}
                    </h1>

                    <p
                        v-if="quiz.description"
                        class="mt-2 max-w-3xl text-sm leading-relaxed"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ quiz.description }}
                    </p>

                    <!-- Meta details pills -->
                    <div class="mt-4 flex flex-wrap items-center gap-3 text-xs">
                        <span
                            v-if="quiz.grade_levels?.length"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <Users class="h-3.5 w-3.5" :stroke-width="2" />
                            <span>{{
                                quiz.grade_levels
                                    .map((g: any) => g.name)
                                    .join(', ')
                            }}</span>
                        </span>
                        <span
                            v-if="quiz.sections?.length"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <span>{{
                                quiz.sections.map((s: any) => s.name).join(', ')
                            }}</span>
                        </span>
                        <span
                            v-if="quiz.time_limit_minutes"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <Clock class="h-3.5 w-3.5" :stroke-width="2" />
                            <span>{{ quiz.time_limit_minutes }} min limit</span>
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
                        :selected="t.id === quiz.teacher_id"
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
                background: rgba(59, 130, 246, 0.07);
                border: 1px solid rgba(59, 130, 246, 0.18);
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
                    style="background: rgba(59, 130, 246, 0.15)"
                >
                    <HelpCircle
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
                {{ quiz.questions.length }}
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                Quiz question items
            </p>
        </div>

        <!-- Total Points -->
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
                    >Max Points</span
                >
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg"
                    style="background: rgba(16, 185, 129, 0.15)"
                >
                    <Star
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
                {{ totalPoints }}
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
                Total attempts taken
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
                {{
                    totalPoints > 0
                        ? Math.round((sectionAvg / totalPoints) * 100)
                        : 0
                }}%
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                {{ sectionAvg }}/{{ totalPoints }} pts avg
            </p>
        </div>
    </div>

    <!-- Questions + Results Grid (2 Columns on xl) -->
    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        <!-- Questions Column (2/3 width) -->
        <div class="space-y-4 xl:col-span-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: rgba(59, 130, 246, 0.12)"
                    >
                        <ListFilter
                            class="h-4 w-4"
                            style="color: var(--gl-primary)"
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
                        {{ quiz.questions.length }}
                    </span>
                </div>
                <span
                    class="text-xs font-medium"
                    style="color: var(--gl-text-muted)"
                >
                    {{ totalPoints }} total pts
                </span>
            </div>

            <div
                v-for="(question, index) in quiz.questions"
                :key="question.id"
                class="rounded-2xl p-5 transition-all"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="mb-3 flex items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <span
                            class="flex h-6 w-6 items-center justify-center rounded-lg text-xs font-extrabold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                            "
                        >
                            {{ index + 1 }}
                        </span>
                        <span
                            class="rounded-lg px-2.5 py-0.5 text-[11px] font-bold capitalize"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            {{ typeLabel(question.type) }}
                        </span>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums"
                        style="
                            background: rgba(59, 130, 246, 0.1);
                            color: var(--gl-primary);
                        "
                    >
                        {{ question.points }} pt{{
                            question.points === 1 ? '' : 's'
                        }}
                    </span>
                </div>

                <p
                    class="text-sm leading-relaxed font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ question.question_text }}
                </p>

                <!-- Type: Multiple Choice & True/False -->
                <div
                    v-if="question.type === 'multiple_choice' || question.type === 'true_false'"
                    class="mt-3 space-y-2"
                >
                    <div
                        v-for="option in question.options"
                        :key="option.id"
                        class="flex items-center justify-between rounded-xl px-3.5 py-2 text-xs transition-colors"
                        :style="
                            option.is_correct
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
                                v-if="option.is_correct"
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
                            <span :class="option.is_correct ? 'font-semibold' : ''">{{ option.option_text }}</span>
                        </div>
                        <span
                            v-if="option.is_correct"
                            class="rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                            style="background: rgba(16, 185, 129, 0.15); color: var(--gl-success);"
                        >
                            Correct Answer
                        </span>
                    </div>
                </div>

                <!-- Type: Identification -->
                <div v-else-if="question.type === 'identification'" class="mt-3">
                    <div
                        class="flex items-center gap-2 rounded-xl px-3.5 py-2 text-xs font-semibold"
                        style="background: var(--gl-success-bg); color: var(--gl-success); border: 1px solid rgba(16,185,129,0.2);"
                    >
                        <CheckCircle2 class="h-4 w-4 shrink-0" :stroke-width="2" />
                        <span>Accepted answer: {{ question.options?.[0]?.option_text || '—' }}</span>
                    </div>
                </div>

                <!-- Type: Enumeration -->
                <div v-else-if="question.type === 'enumeration'" class="mt-3 space-y-1.5">
                    <p class="text-[11px] font-bold" style="color: var(--gl-text-secondary);">Accepted Items ({{ question.options?.length || 0 }}):</p>
                    <div class="flex flex-wrap gap-1.5">
                        <span
                            v-for="(item, ii) in question.options"
                            :key="ii"
                            class="rounded-lg px-2.5 py-1 text-xs font-semibold"
                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);"
                        >
                            {{ item.option_text }}
                        </span>
                    </div>
                </div>

                <!-- Type: Matching Pairs -->
                <div v-else-if="question.type === 'matching'" class="mt-3 space-y-1.5">
                    <p class="text-[11px] font-bold" style="color: var(--gl-text-secondary);">Matching Pairs ({{ question.matching_pairs?.length || 0 }}):</p>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div
                            v-for="pair in question.matching_pairs"
                            :key="pair.id"
                            class="flex items-center justify-between rounded-xl px-3 py-2 text-xs font-medium"
                            style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);"
                        >
                            <span style="color: var(--gl-text-primary)">{{ pair.left_text }}</span>
                            <span class="rounded px-2 py-0.5 text-[11px] font-semibold" style="background: var(--gl-success-bg); color: var(--gl-success);">
                                {{ pair.right_text }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Type: Essay -->
                <div v-else-if="question.type === 'essay'" class="mt-3">
                    <div class="rounded-xl p-3 text-xs leading-relaxed" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border);">
                        <span class="font-bold" style="color: var(--gl-primary);">Grading Rubric / Criteria:</span>
                        <p class="mt-1 whitespace-pre-line" style="color: var(--gl-text-secondary);">
                            {{ question.criteria || 'Standard essay evaluation criteria' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leaderboard & Submissions (1/3 width) -->
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
                        Leaderboard
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
                        @click="autoRecheckAll"
                        :disabled="isRecheckingAll"
                        class="inline-flex items-center gap-1 rounded-xl px-2.5 py-1.5 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                        style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary); border: 1px solid rgba(124, 58, 237, 0.3);"
                    >
                        <LoaderCircle v-if="isRecheckingAll" :size="13" class="animate-spin" />
                        <Sparkles v-else :size="13" />
                        <span>{{ isRecheckingAll ? 'Checking...' : 'AI Check All' }}</span>
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
                        class="rounded-xl border px-2.5 py-1.5 text-xs outline-none"
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
                        class="rounded-xl border px-2.5 py-1.5 text-xs outline-none"
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
                            : 'Results will appear here once students submit the quiz.'
                    }}
                </p>
            </div>

            <!-- Leaderboard Cards -->
            <div v-else class="space-y-2.5">
                <div
                    v-for="(attempt, index) in searchedAttempts"
                    :key="attempt.id"
                    :id="'attempt-' + attempt.id"
                    class="relative flex items-center justify-between rounded-2xl p-3.5 transition-all hover:scale-[1.01]"
                    :class="[
                        openMenu === attempt.id
                            ? 'z-30 !scale-100 shadow-xl'
                            : '',
                        lastAttemptId === attempt.id
                            ? 'ring-2 ring-[var(--gl-primary)] shadow-lg shadow-[var(--gl-primary-glow)]'
                            : '',
                    ]"
                    style="
                        background: var(--gl-surface);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <!-- Rank or avatar icon -->
                        <div
                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-extrabold text-white shadow-xs"
                            :style="{
                                background: avatarBg(attempt.student?.name),
                            }"
                        >
                            {{
                                (attempt.student?.name || '?')
                                    .charAt(0)
                                    .toUpperCase()
                            }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <p
                                    class="truncate text-xs font-bold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ attempt.student?.name }}
                                </p>
                                <span
                                    v-if="attempt.student?.gender"
                                    class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider"
                                    :style="
                                        attempt.student.gender.toLowerCase() === 'female'
                                            ? { background: 'rgba(236, 72, 153, 0.12)', color: '#ec4899', border: '1px solid rgba(236, 72, 153, 0.25)' }
                                            : { background: 'rgba(59, 130, 246, 0.12)', color: 'var(--gl-primary)', border: '1px solid rgba(59, 130, 246, 0.25)' }
                                    "
                                >
                                    {{ attempt.student.gender }}
                                </span>
                            </div>
                            <p
                                class="truncate text-[11px]"
                                style="color: var(--gl-text-muted)"
                            >
                                {{ attempt.student?.grade
                                }}{{
                                    attempt.student?.section
                                        ? ' — ' + attempt.student.section.name
                                        : ''
                                }}
                            </p>
                            <p
                                v-if="formatAttemptDate(attempt)"
                                class="mt-0.5 inline-flex items-center gap-1 text-[10px]"
                                style="color: var(--gl-text-muted)"
                            >
                                <Calendar class="h-3 w-3 shrink-0" :stroke-width="2" />
                                Submitted: {{ formatAttemptDate(attempt) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            class="rounded-lg px-2.5 py-1 text-xs font-extrabold tabular-nums"
                            :style="{
                                background:
                                    attempt.score /
                                        (attempt.total_points || 1) >=
                                    0.8
                                        ? 'var(--gl-success-bg)'
                                        : attempt.score /
                                                (attempt.total_points || 1) >=
                                            0.6
                                          ? 'var(--gl-warning-bg)'
                                          : 'var(--gl-danger-bg)',
                                color:
                                    attempt.score /
                                        (attempt.total_points || 1) >=
                                    0.8
                                        ? 'var(--gl-success)'
                                        : attempt.score /
                                                (attempt.total_points || 1) >=
                                            0.6
                                          ? 'var(--gl-accent)'
                                          : 'var(--gl-danger)',
                            }"
                        >
                            {{ attempt.score }}/{{ attempt.total_points }}
                        </span>

                        <!-- Actions dropdown -->
                        <div class="relative">
                            <button
                                @click.stop="
                                    openMenu =
                                        openMenu === attempt.id
                                            ? null
                                            : attempt.id
                                "
                                title="Actions"
                                class="flex h-7 w-7 items-center justify-center rounded-lg transition-colors hover:bg-[var(--gl-surface-2)]"
                                style="color: var(--gl-text-muted)"
                            >
                                <Ellipsis class="h-4 w-4" :stroke-width="2" />
                            </button>
                            <div
                                v-if="openMenu === attempt.id"
                                class="absolute top-full right-0 z-40 mt-1 w-44 overflow-hidden rounded-xl py-1 shadow-2xl backdrop-blur-md"
                                style="
                                    background: var(--gl-surface);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                <button
                                    @click.stop="
                                        openMenu = null;
                                        autoRecheck(attempt.id);
                                    "
                                    class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-surface-2)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <RotateCw
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-primary)"
                                        :stroke-width="2"
                                    />
                                    <span>Auto recheck</span>
                                </button>
                                <Link
                                    :href="recheckUrl(attempt.id)"
                                    @click="openMenu = null; saveCurrentAttempt(attempt.id)"
                                    class="flex w-full items-center gap-2 px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-surface-2)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <Eye
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-secondary)"
                                        :stroke-width="2"
                                    />
                                    <span>Review attempt</span>
                                </Link>
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
    Calendar,
    CheckCircle2,
    Circle,
    ClipboardCheck,
    Clock,
    Ellipsis,
    Eye,
    FileQuestion,
    HelpCircle,
    ListFilter,
    LoaderCircle,
    Lock,
    PenSquare,
    RefreshCw,
    RotateCw,
    Search,
    Sparkles,
    Star,
    Trophy,
    UploadCloud,
    Users,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';

const props = defineProps<{
    quiz: any;
    attempts: any[];
    totalPoints: number;
    teachers?: any[];
}>();

const openMenu = ref<number | null>(null);
const searchQuery = ref('');
const isRecheckingAll = ref(false);
const lastAttemptId = ref<number | null>(null);

function saveCurrentAttempt(attemptId: number) {
    lastAttemptId.value = attemptId;
    sessionStorage.setItem(`last_quiz_attempt_${props.quiz.id}`, String(attemptId));
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

    return `/teacher/quizzes/${props.quiz.id}/recheck/${attemptId}?return_to=${returnUrl}`;
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

    const saved = sessionStorage.getItem(`last_quiz_attempt_${props.quiz.id}`);

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

const storageKey = 'quiz_show_' + (typeof window !== 'undefined' ? window.location.pathname : '');
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

function typeLabel(type: string): string {
    switch (type) {
        case 'true_false': return 'True / False';
        case 'identification': return 'Identification';
        case 'enumeration': return 'Enumeration';
        case 'matching': return 'Matching';
        case 'essay': return 'Essay';
        case 'multiple_choice':
        default: return 'Multiple Choice';
    }
}

const isClosed = computed(() => {
    if (!props.quiz.closes_at) {
return false;
}

    return new Date(props.quiz.closes_at) < new Date();
});

const statusLabel = computed(() => {
    if (!props.quiz.is_published) {
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

    return list.sort((a: any, b: any) => (b.score ?? 0) - (a.score ?? 0));
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
        (acc: number, a: any) => acc + (a.score || 0),
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
        `/teacher/quizzes/${props.quiz.id}/reassign`,
        { teacher_id: reassignSelect.value.value },
        { preserveScroll: true },
    );
}

function autoRecheck(attemptId: number) {
    saveCurrentAttempt(attemptId);
    router.put(
        `/teacher/quizzes/${props.quiz.id}/auto-recheck/${attemptId}`,
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
        `/teacher/quizzes/${props.quiz.id}/auto-recheck-all`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isRecheckingAll.value = false;
            },
        },
    );
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

function formatAttemptDate(attempt: any): string {
    const timestamp = attempt.submitted_at || attempt.created_at;

    if (!timestamp) {
return '';
}

    const date = new Date(timestamp);

    if (Number.isNaN(date.getTime())) {
return '';
}

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(date);
}

function togglePublish() {
    router.patch(
        `/teacher/quizzes/${props.quiz.id}/publish`,
        {},
        { preserveScroll: true },
    );
}

function toggleReopen() {
    router.patch(
        `/teacher/quizzes/${props.quiz.id}/reopen`,
        {},
        { preserveScroll: true },
    );
}

function closeNow() {
    router.patch(
        `/teacher/quizzes/${props.quiz.id}/close-now`,
        {},
        { preserveScroll: true },
    );
}
</script>
