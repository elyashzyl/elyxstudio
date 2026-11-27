<template>
    <Head :title="exam.title + ' — Exam'" />

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
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <Link
            href="/teacher/exams"
            class="inline-flex items-center gap-2 rounded-xl px-3 py-1.5 text-xs font-semibold transition-all hover:scale-105 active:scale-95"
            style="
                background: var(--gl-surface);
                color: var(--gl-text-secondary);
                border: 1px solid var(--gl-border);
            "
        >
            <ArrowLeft class="h-3.5 w-3.5" :stroke-width="2" />
            <span>Back to exams</span>
        </Link>

        <!-- Quick actions -->
        <div class="flex flex-wrap items-center gap-2">
            <button
                v-if="!isClosed"
                @click="togglePublish"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95"
                :style="
                    exam.is_published
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
                <span>{{ exam.is_published ? 'Unpublish' : 'Publish' }}</span>
            </button>

            <button
                v-if="exam.is_published && !isClosed"
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

            <button
                v-if="attempts.length"
                type="button"
                @click="aiGradeAll"
                :disabled="isGradingAll"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95 disabled:opacity-50"
                style="
                    background: rgba(124, 58, 237, 0.12);
                    color: var(--gl-secondary);
                    border: 1px solid rgba(124, 58, 237, 0.25);
                "
            >
                <LoaderCircle v-if="isGradingAll" class="h-3.5 w-3.5 animate-spin" />
                <Sparkles v-else class="h-3.5 w-3.5" />
                <span>{{ isGradingAll ? 'Grading...' : 'AI Grade All' }}</span>
            </button>

            <Link
                v-if="attempts.length"
                :href="`/teacher/exams/${exam.id}/attempts?return_to=${encodeURIComponent(currentUrl())}`"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold transition-all hover:scale-105 active:scale-95"
                style="
                    background: rgba(124, 58, 237, 0.12);
                    color: var(--gl-secondary);
                    border: 1px solid rgba(124, 58, 237, 0.25);
                "
            >
                <ClipboardCheck class="h-3.5 w-3.5" :stroke-width="2" />
                <span>Grade Attempts ({{ attempts.length }})</span>
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
                            rgba(99, 102, 241, 0.2),
                            rgba(168, 85, 247, 0.15)
                        );
                        border: 1px solid rgba(99, 102, 241, 0.3);
                        box-shadow: 0 0 20px rgba(99, 102, 241, 0.15);
                    "
                >
                    <GraduationCap
                        class="h-6 w-6"
                        style="color: var(--gl-secondary)"
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
                            Exam
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
                            v-else-if="exam.closes_at"
                            class="rounded-full px-2.5 py-0.5 text-[10px] font-semibold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            Closes {{ timeRemaining(exam.closes_at) }}
                        </span>
                    </div>

                    <h1
                        class="mt-1.5 text-2xl font-extrabold tracking-tight sm:text-3xl"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ exam.title }}
                    </h1>

                    <p
                        v-if="exam.instructions"
                        class="mt-2 max-w-3xl text-sm leading-relaxed whitespace-pre-line"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ exam.instructions }}
                    </p>

                    <!-- Meta details pills -->
                    <div class="mt-4 flex flex-wrap items-center gap-3 text-xs">
                        <span
                            v-if="exam.grade_levels?.length"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <Users class="h-3.5 w-3.5" :stroke-width="2" />
                            <span>{{
                                exam.grade_levels
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
                            <Layers class="h-3.5 w-3.5" :stroke-width="2" />
                            <span
                                >{{ exam.sections?.length || 0 }} sections</span
                            >
                        </span>
                        <span
                            v-if="exam.time_limit_minutes"
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <Clock class="h-3.5 w-3.5" :stroke-width="2" />
                            <span>{{ exam.time_limit_minutes }} min limit</span>
                        </span>
                        <span
                            class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                            "
                        >
                            <Star class="h-3.5 w-3.5" :stroke-width="2" />
                            <span>{{ exam.max_score }} max pts</span>
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
                        :selected="t.id === exam.teacher_id"
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
        <!-- Sections -->
        <div
            class="rounded-2xl p-4 transition-all"
            style="
                background: rgba(99, 102, 241, 0.07);
                border: 1px solid rgba(99, 102, 241, 0.18);
            "
        >
            <div class="mb-2 flex items-center justify-between">
                <span
                    class="text-xs font-medium"
                    style="color: var(--gl-text-secondary)"
                    >Sections</span
                >
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg"
                    style="background: rgba(99, 102, 241, 0.15)"
                >
                    <Layers
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
                {{ exam.sections?.length || 0 }}
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                {{ questionSectionsCount }} questions ·
                {{ practicalSectionsCount }} practical
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
                {{ exam.max_score }}
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                Total possible score
            </p>
        </div>

        <!-- Submissions -->
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
                    >Submissions</span
                >
                <div
                    class="flex h-7 w-7 items-center justify-center rounded-lg"
                    style="background: rgba(16, 185, 129, 0.15)"
                >
                    <ClipboardCheck
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
                {{ attempts.length }}
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                {{ attempts.length }} student attempts
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
                    exam.max_score > 0
                        ? Math.round((averageScore / exam.max_score) * 100)
                        : 0
                }}%
            </p>
            <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                {{ averageScore }}/{{ exam.max_score }} pts avg
            </p>
        </div>
    </div>

    <!-- Exam Content + Submissions Grid (2 Columns on xl) -->
    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        <!-- Sections Breakdown Column (2/3 width) -->
        <div class="space-y-5 xl:col-span-2">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: rgba(99, 102, 241, 0.12)"
                    >
                        <ListFilter
                            class="h-4 w-4"
                            style="color: var(--gl-secondary)"
                            :stroke-width="2"
                        />
                    </div>
                    <h3
                        class="text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Exam Structure & Content
                    </h3>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                    >
                        {{ exam.sections?.length || 0 }} sections
                    </span>
                </div>
                <span
                    class="text-xs font-medium"
                    style="color: var(--gl-text-muted)"
                >
                    {{ exam.max_score }} total pts
                </span>
            </div>

            <!-- Empty Sections -->
            <div
                v-if="!exam.sections?.length"
                class="rounded-2xl p-8 text-center"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <p class="text-sm" style="color: var(--gl-text-muted)">
                    No sections have been added to this exam.
                </p>
            </div>

            <!-- Sections List -->
            <div
                v-for="(sec, si) in exam.sections"
                :key="sec.id"
                class="rounded-3xl p-6 transition-all"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <!-- Section Header -->
                <div
                    class="mb-4 flex flex-wrap items-start justify-between gap-3 border-b border-[var(--gl-border)] pb-4"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-2xl"
                            :style="
                                sec.section_type === 'questions'
                                    ? {
                                          background:
                                              'rgba(59, 130, 246, 0.12)',
                                          border: '1px solid rgba(59, 130, 246, 0.25)',
                                      }
                                    : {
                                          background:
                                              'rgba(251, 191, 36, 0.12)',
                                          border: '1px solid rgba(251, 191, 36, 0.25)',
                                      }
                            "
                        >
                            <FileText
                                v-if="sec.section_type === 'questions'"
                                class="h-5 w-5"
                                :stroke-width="2"
                                style="color: var(--gl-primary)"
                            />
                            <Award
                                v-else
                                class="h-5 w-5"
                                :stroke-width="2"
                                style="color: var(--gl-accent)"
                            />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    style="
                                        background: var(--gl-surface-2);
                                        color: var(--gl-text-muted);
                                    "
                                >
                                    Section {{ si + 1 }}
                                </span>
                                <span
                                    class="rounded-full px-2.5 py-0.5 text-[10px] font-bold capitalize"
                                    :style="
                                        sec.section_type === 'questions'
                                            ? {
                                                  background:
                                                      'rgba(59, 130, 246, 0.12)',
                                                  color: 'var(--gl-primary)',
                                              }
                                            : {
                                                  background:
                                                      'rgba(251, 191, 36, 0.12)',
                                                  color: 'var(--gl-accent)',
                                              }
                                    "
                                >
                                    {{ sec.section_type }}
                                </span>
                            </div>
                            <h4
                                class="mt-1 text-base font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ sec.title }}
                            </h4>
                        </div>
                    </div>

                    <span
                        class="rounded-full px-3 py-1 text-xs font-extrabold tabular-nums"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        {{ sectionPoints(sec) }} pts
                    </span>
                </div>

                <p
                    v-if="sec.instructions"
                    class="mb-4 text-xs leading-relaxed whitespace-pre-line"
                    style="color: var(--gl-text-secondary)"
                >
                    {{ sec.instructions }}
                </p>

                <!-- Questions Section Body -->
                <div
                    v-if="
                        sec.section_type === 'questions' &&
                        sec.questions?.length
                    "
                    class="space-y-4"
                >
                    <div
                        v-for="(q, qi) in sec.questions"
                        :key="q.id"
                        class="rounded-2xl p-4 transition-all"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <!-- Question Header -->
                        <div
                            class="mb-2.5 flex items-center justify-between gap-2"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="flex h-6 w-6 items-center justify-center rounded-lg text-xs font-extrabold"
                                    style="
                                        background: var(--gl-surface);
                                        color: var(--gl-text-primary);
                                        border: 1px solid var(--gl-border);
                                    "
                                >
                                    {{ qi + 1 }}
                                </span>
                                <span
                                    class="rounded-lg px-2.5 py-0.5 text-[11px] font-bold"
                                    style="
                                        background: var(--gl-surface);
                                        color: var(--gl-text-secondary);
                                        border: 1px solid var(--gl-border);
                                    "
                                >
                                    {{ formatQuestionType(q.type) }}
                                </span>
                            </div>
                            <span
                                class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums"
                                style="
                                    background: rgba(59, 130, 246, 0.12);
                                    color: var(--gl-primary);
                                "
                            >
                                {{ q.points }} pt{{ q.points === 1 ? '' : 's' }}
                            </span>
                        </div>

                        <!-- Question Text -->
                        <p
                            class="text-sm leading-relaxed font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ q.question_text }}
                        </p>

                        <!-- Multiple Choice & True/False Options Preview -->
                        <div
                            v-if="
                                (q.type === 'multiple_choice' ||
                                    q.type === 'true_false') &&
                                q.options?.length
                            "
                            class="mt-3 space-y-2"
                        >
                            <div
                                v-for="option in q.options"
                                :key="option.id"
                                class="flex items-center justify-between rounded-xl px-3.5 py-2 text-xs transition-colors"
                                :style="
                                    option.is_correct
                                        ? {
                                              background:
                                                  'var(--gl-success-bg)',
                                              color: 'var(--gl-success)',
                                              border: '1px solid rgba(16,185,129,0.2)',
                                          }
                                        : {
                                              background: 'var(--gl-surface)',
                                              color: 'var(--gl-text-secondary)',
                                              border: '1px solid var(--gl-border)',
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
                                    <span
                                        :class="
                                            option.is_correct
                                                ? 'font-semibold'
                                                : ''
                                        "
                                        >{{ option.option_text }}</span
                                    >
                                </div>
                                <span
                                    v-if="option.is_correct"
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

                        <!-- Matching Pairs Preview -->
                        <div
                            v-if="
                                q.type === 'matching' &&
                                q.matching_pairs?.length
                            "
                            class="mt-3 space-y-2"
                        >
                            <div
                                v-for="pair in q.matching_pairs"
                                :key="pair.id"
                                class="flex items-center justify-between rounded-xl p-2.5 text-xs"
                                style="
                                    background: var(--gl-surface);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                <span
                                    class="font-medium"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ pair.left_text }}
                                </span>
                                <div class="flex items-center gap-2">
                                    <ArrowRight
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-text-muted)"
                                    />
                                    <span
                                        class="rounded-lg px-2.5 py-1 font-semibold"
                                        style="
                                            background: var(--gl-success-bg);
                                            color: var(--gl-success);
                                            border: 1px solid
                                                rgba(16, 185, 129, 0.2);
                                        "
                                    >
                                        {{ pair.right_text }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Practical Section Body -->
                <div
                    v-if="
                        sec.section_type === 'practical' && sec.criteria?.length
                    "
                    class="space-y-3"
                >
                    <div
                        v-for="(c, ci) in sec.criteria"
                        :key="c.id"
                        class="flex items-start justify-between gap-3 rounded-2xl p-4 transition-colors"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <div class="flex min-w-0 items-start gap-3">
                            <span
                                class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-xs font-extrabold"
                                style="
                                    background: var(--gl-surface);
                                    color: var(--gl-text-primary);
                                    border: 1px solid var(--gl-border);
                                "
                            >
                                {{ ci + 1 }}
                            </span>
                            <div class="min-w-0">
                                <p
                                    class="text-xs font-bold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ c.criterion_name }}
                                </p>
                                <p
                                    v-if="c.description"
                                    class="mt-1 text-[11px] leading-relaxed"
                                    style="color: var(--gl-text-muted)"
                                >
                                    {{ c.description }}
                                </p>
                            </div>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums"
                            style="
                                background: rgba(251, 191, 36, 0.12);
                                color: var(--gl-accent);
                                border: 1px solid rgba(251, 191, 36, 0.25);
                            "
                        >
                            {{ c.max_points }} pt{{
                                c.max_points === 1 ? '' : 's'
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Submissions Column (1/3 width) -->
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-xl"
                        style="background: rgba(16, 185, 129, 0.12)"
                    >
                        <Trophy
                            class="h-4 w-4"
                            style="color: var(--gl-success)"
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

                    <!-- Section Filter -->
                    <select
                        v-if="sectionsList.length > 0"
                        v-model="sectionFilter"
                        class="rounded-xl border px-2.5 py-1.5 text-xs outline-none"
                        style="
                            background: var(--gl-surface);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                        title="Filter by section"
                    >
                        <option value="">All sections</option>
                        <option v-for="s in sectionsList" :key="s.id" :value="String(s.id)">
                            {{ s.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Grade Attempts Callout when attempts exist -->
            <div
                v-if="attempts.length"
                class="rounded-2xl p-4 transition-all"
                style="
                    background: linear-gradient(
                        135deg,
                        rgba(99, 102, 241, 0.08),
                        rgba(168, 85, 247, 0.08)
                    );
                    border: 1px solid rgba(99, 102, 241, 0.2);
                "
            >
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p
                            class="text-xs font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            Practical Evaluation
                        </p>
                        <p
                            class="mt-0.5 text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Grade practical sections for submitted attempts
                        </p>
                    </div>
                    <Link
                        :href="`/teacher/exams/${exam.id}/attempts?return_to=${encodeURIComponent(currentUrl())}`"
                        class="inline-flex shrink-0 items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition-all hover:scale-105 active:scale-95"
                        style="
                            background: var(--gl-primary);
                            color: #fff;
                            box-shadow: 0 0 12px var(--gl-primary-glow);
                        "
                    >
                        <span>Open Grader</span>
                        <ArrowRight class="h-3 w-3" :stroke-width="2.5" />
                    </Link>
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

            <!-- Empty Submissions -->
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
                        searchQuery
                            ? 'No students match your search.'
                            : 'Results will appear here once students submit the exam.'
                    }}
                </p>
            </div>

            <!-- Submissions List Cards -->
            <div v-else class="space-y-2.5">
                <div
                    v-for="a in searchedAttempts"
                    :key="a.id"
                    :id="'attempt-' + a.id"
                    class="flex items-center justify-between rounded-2xl p-3.5 transition-all hover:scale-[1.01]"
                    :class="{
                        'ring-2 ring-[var(--gl-primary)] shadow-lg shadow-[var(--gl-primary-glow)]':
                            lastAttemptId === a.id,
                    }"
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
                                class="truncate text-[11px]"
                                style="color: var(--gl-text-muted)"
                            >
                                <span v-if="a.student?.section?.name" class="font-medium" style="color: var(--gl-text-secondary)">
                                    {{ a.student.section.name }} ·
                                </span>
                                Grade {{ a.student?.grade ?? '—' }} ·
                                {{ formatDate(a.submitted_at) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <span
                            class="rounded-lg px-2.5 py-1 text-xs font-extrabold tabular-nums"
                            :style="{
                                background:
                                    exam.max_score > 0 &&
                                    a.total_score / exam.max_score >= 0.8
                                        ? 'var(--gl-success-bg)'
                                        : exam.max_score > 0 &&
                                            a.total_score / exam.max_score >=
                                                0.6
                                          ? 'var(--gl-warning-bg)'
                                          : 'var(--gl-danger-bg)',
                                color:
                                    exam.max_score > 0 &&
                                    a.total_score / exam.max_score >= 0.8
                                        ? 'var(--gl-success)'
                                        : exam.max_score > 0 &&
                                            a.total_score / exam.max_score >=
                                                0.6
                                          ? 'var(--gl-accent)'
                                          : 'var(--gl-danger)',
                            }"
                        >
                            {{ a.total_score }}/{{ exam.max_score }}
                        </span>

                        <Link
                            :href="gradeUrl(a.id)"
                            @click="saveCurrentAttempt(a.id)"
                            title="Review & Grade attempt"
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl transition-all hover:scale-105 active:scale-95"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-secondary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <Eye class="h-4 w-4" :stroke-width="2" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirm Delete Modal -->
    <Teleport to="body">
        <div
            v-if="deletingExam"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(2px)"
            @click.self="deletingExam = false"
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
                    Delete exam?
                </h3>
                <p
                    class="text-sm leading-relaxed"
                    style="color: var(--gl-text-secondary)"
                >
                    This will permanently delete
                    <strong>{{ exam.title }}</strong> and all associated student
                    attempts and scores.
                </p>
                <div class="mt-5 flex justify-end gap-3">
                    <button
                        @click="deletingExam = false"
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
                        @click="destroyExam"
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
    ArrowRight,
    Award,
    CheckCircle2,
    Circle,
    ClipboardCheck,
    Clock,
    Eye,
    FileText,
    GraduationCap,
    Layers,
    ListFilter,
    LoaderCircle,
    Lock,
    RefreshCw,
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
    exam: any;
    attempts: any[];
    sections?: any[];
    teachers?: any[];
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash || {});

const lastAttemptId = ref<number | null>(null);
const isGradingAll = ref(false);

function aiGradeAll() {
    if (isGradingAll.value) {
return;
}

    isGradingAll.value = true;
    router.post(
        `/teacher/exams/${props.exam.id}/ai-grade-all`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                isGradingAll.value = false;
            },
        },
    );
}

function currentUrl(): string {
    return typeof window !== 'undefined'
        ? window.location.pathname + window.location.search
        : `/teacher/exams/${props.exam.id}`;
}

function saveCurrentAttempt(attemptId: number) {
    lastAttemptId.value = attemptId;
    sessionStorage.setItem(`last_exam_attempt_${props.exam.id}`, String(attemptId));
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

    return `/teacher/exams/${props.exam.id}/attempts?return_to=${returnUrl}#attempt-${attemptId}`;
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

    const saved = sessionStorage.getItem(`last_exam_attempt_${props.exam.id}`);

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
const storageKey = 'exam_show_' + (typeof window !== 'undefined' ? window.location.pathname : '');
const sectionFilter = ref(
    (typeof sessionStorage !== 'undefined' && sessionStorage.getItem(storageKey + '_section')) || '',
);
const genderFilter = ref(
    (typeof sessionStorage !== 'undefined' && sessionStorage.getItem(storageKey + '_gender')) || '',
);
const sortMode = ref<'score' | 'alpha'>(
    ((typeof sessionStorage !== 'undefined' && sessionStorage.getItem(storageKey + '_sort')) as 'score' | 'alpha') ||
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

const sectionsList = computed(() => {
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

    return Array.from(map.values()).sort((a, b) => a.name.localeCompare(b.name));
});
const deletingExam = ref(false);
const reassignSelect = ref<HTMLSelectElement | null>(null);

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

const isClosed = computed(() => {
    if (!props.exam.closes_at) {
return false;
}

    return new Date(props.exam.closes_at) < new Date();
});

const statusLabel = computed(() => {
    if (!props.exam.is_published) {
return 'draft';
}

    if (isClosed.value) {
return 'finished';
}

    return 'published';
});

const questionSectionsCount = computed(() => {
    return (
        props.exam.sections?.filter((s: any) => s.section_type === 'questions')
            .length || 0
    );
});

const practicalSectionsCount = computed(() => {
    return (
        props.exam.sections?.filter((s: any) => s.section_type === 'practical')
            .length || 0
    );
});

function sectionPoints(sec: any): number {
    if (sec.section_type === 'questions') {
        return (sec.questions || []).reduce(
            (acc: number, q: any) => acc + (Number(q.points) || 0),
            0,
        );
    }

    return (sec.criteria || []).reduce(
        (acc: number, c: any) => acc + (Number(c.max_points) || 0),
        0,
    );
}

const averageScore = computed(() => {
    if (!props.attempts.length) {
return 0;
}

    const sum = props.attempts.reduce(
        (acc: number, a: any) => acc + (Number(a.total_score) || 0),
        0,
    );

    return Math.round((sum / props.attempts.length) * 10) / 10;
});

function formatQuestionType(type: string): string {
    switch (type) {
        case 'multiple_choice':
            return 'Multiple Choice';
        case 'true_false':
            return 'True/False';
        case 'matching':
            return 'Matching Type';
        case 'identification':
            return 'Identification';
        case 'enumeration':
            return 'Enumeration';
        case 'essay':
            return 'Essay';
        default:
            return type ? type.replace(/_/g, ' ') : 'Question';
    }
}

const searchedAttempts = computed(() => {
    let list = [...props.attempts];

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

    if (searchQuery.value.trim()) {
        const q = searchQuery.value.toLowerCase().trim();
        list = list.filter((a: any) =>
            (a.student?.name || '').toLowerCase().includes(q),
        );
    }

    if (sortMode.value === 'score') {
        list.sort(
            (a: any, b: any) =>
                (Number(b.total_score) || 0) - (Number(a.total_score) || 0),
        );
    } else if (sortMode.value === 'alpha') {
        list.sort((a: any, b: any) =>
            (a.student?.name || '').localeCompare(b.student?.name || ''),
        );
    }

    return list;
});

function reassign() {
    if (!reassignSelect.value) {
return;
}

    router.patch(
        `/teacher/exams/${props.exam.id}/reassign`,
        {
            teacher_id: reassignSelect.value.value,
        },
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
        `/teacher/exams/${props.exam.id}/publish`,
        {},
        { preserveScroll: true },
    );
}

function toggleReopen() {
    router.patch(
        `/teacher/exams/${props.exam.id}/reopen`,
        {},
        { preserveScroll: true },
    );
}

function closeNow() {
    router.patch(
        `/teacher/exams/${props.exam.id}/close-now`,
        {},
        { preserveScroll: true },
    );
}

function confirmDestroy() {
    deletingExam.value = true;
}

function destroyExam() {
    router.delete(`/teacher/exams/${props.exam.id}`, {
        onSuccess: () => {
            deletingExam.value = false;
        },
    });
}
</script>
