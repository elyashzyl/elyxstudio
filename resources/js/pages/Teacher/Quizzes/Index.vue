<template>
    <Head title="Quizzes" />

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
                Assess
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Quizzes
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ localQuizzes.length }} quizzes · {{ totalAttempts }} attempts
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
                    v-model="query"
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
            <select
                v-if="props.quarters?.length"
                v-model="quarterFilter"
                class="rounded-full border py-2 px-3 text-xs font-semibold outline-none transition-all"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border-color: var(--gl-border);
                "
            >
                <option value="">All Quarters</option>
                <option v-for="qtr in props.quarters" :key="qtr.id" :value="qtr.id">{{ qtr.code }} · {{ qtr.name }}</option>
            </select>
            <div class="flex items-center gap-1.5">
                <button
                    v-for="t in statusTabs"
                    :key="t.value"
                    @click="statusTab = t.value"
                    class="rounded-full px-3.5 py-2 text-xs font-bold transition-all"
                    :style="
                        statusTab === t.value
                            ? { background: '#1F2937', color: '#fff' }
                            : {
                                  background: 'var(--gl-surface)',
                                  border: '1px solid var(--gl-border)',
                                  color: 'var(--gl-text-secondary)',
                              }
                    "
                >
                    {{ t.label }}
                </button>
            </div>
            <Link
                href="/teacher/ai-creator?activity=quiz"
                class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold text-white transition-all hover:scale-105 shadow-sm"
                style="
                    background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary));
                    box-shadow: 0 4px 14px var(--gl-primary-glow);
                "
                title="Create quiz using AI file upload"
            >
                <Sparkles class="h-4 w-4" :stroke-width="2.2" />
                <span>AI File Creator</span>
            </Link>
            <button
                @click="openCreateModal"
                class="flex h-10 w-10 items-center justify-center rounded-xl text-white transition-all hover:scale-105"
                style="
                    background: linear-gradient(135deg, #ff6a45, #f94f2b);
                    box-shadow: 0 4px 14px rgba(249, 79, 43, 0.4);
                "
                title="New quiz"
            >
                <Plus class="h-5 w-5" :stroke-width="2.5" />
            </button>
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
                <FileQuestion
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-primary)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-2xl font-black"
                style="color: var(--gl-text-primary)"
            >
                {{ localQuizzes.length }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Total quizzes
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
                <UploadCloud
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-success)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ publishedCount }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Published
            </p>
        </div>
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(251, 191, 36, 0.1);
                border: 1px solid rgba(251, 191, 36, 0.2);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(251, 191, 36, 0.2)"
            >
                <Pencil
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-accent)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ draftCount }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Drafts
            </p>
        </div>
        <div
            class="rounded-2xl p-4"
            style="
                background: rgba(124, 58, 237, 0.08);
                border: 1px solid rgba(124, 58, 237, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(124, 58, 237, 0.16)"
            >
                <Users
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-secondary)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ totalAttempts }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Attempts
            </p>
        </div>
    </div>

    <div
        v-if="!filtered.length"
        class="gl-glow-card flex flex-col items-center justify-center px-8 py-16 text-center"
    >
        <div
            class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl"
            style="background: var(--gl-surface-2)"
        >
            <FileQuestion
                class="h-6 w-6"
                style="color: var(--gl-text-muted)"
                :stroke-width="1.75"
            />
        </div>
        <p class="text-sm font-medium" style="color: var(--gl-text-secondary)">
            {{ localQuizzes.length ? 'No matches' : 'No quizzes yet' }}
        </p>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
            {{
                localQuizzes.length
                    ? 'Try a different search or filter.'
                    : 'Create your first quiz to start assessing your students.'
            }}
        </p>
        <button
            v-if="!localQuizzes.length"
            @click="openCreateModal"
            class="mt-4 flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
            style="
                background: linear-gradient(
                    135deg,
                    var(--gl-primary),
                    var(--gl-secondary)
                );
                box-shadow: 0 0 12px var(--gl-primary-glow);
            "
        >
            <Plus class="h-4 w-4" :stroke-width="2" /> New Quiz
        </button>
    </div>

    <div v-else>
        <div
            class="mb-1 hidden grid-cols-[1fr_110px_90px_90px_40px] items-center gap-3 px-4 pb-2 text-[11px] font-semibold tracking-wider uppercase sm:grid"
            style="color: var(--gl-text-muted)"
        >
            <span>Title</span><span>Status</span
            ><span class="text-center">Questions</span
            ><span class="text-center">Attempts</span><span></span>
        </div>
        <div class="space-y-2">
            <div
                v-for="quiz in filtered"
                :key="quiz.id"
                class="grid cursor-pointer grid-cols-[auto_1fr_40px] items-center gap-3 rounded-2xl px-4 py-3 transition-all sm:grid-cols-[1fr_110px_90px_90px_40px]"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
                @click="goView(quiz)"
            >
                <div class="min-w-0">
                    <p
                        class="truncate text-sm font-semibold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ quiz.title }}
                    </p>
                    <p
                        class="truncate text-[11px]"
                        style="color: var(--gl-text-muted)"
                    >
                        <span
                            v-if="quiz.quarter"
                            class="mr-1.5 inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                            style="background: rgba(249, 79, 43, 0.12); color: #f94f2b;"
                        >{{ quiz.quarter.code }}</span>
                        <span
                            v-if="quiz.school_year"
                            class="mr-1.5 inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                            style="background: rgba(59, 130, 246, 0.12); color: var(--gl-primary);"
                        >{{ quiz.school_year.name }}</span>
                        <span v-if="isSuperadmin && quiz.teacher?.name"
                            >{{ quiz.teacher.name }} · </span
                        >{{ targetLabel(quiz) }}
                    </p>
                </div>
                <span class="hidden sm:block"
                    ><StatusBadge :status="statusLabel(quiz)"
                /></span>
                <span
                    class="hidden text-center text-sm font-semibold tabular-nums sm:block"
                    style="color: var(--gl-text-primary)"
                    >{{ quiz.questions_count }}</span
                >
                <span
                    class="hidden text-center text-sm font-semibold tabular-nums sm:block"
                    style="color: var(--gl-text-primary)"
                    >{{ quiz.attempts_count }}</span
                >
                <div class="relative flex justify-end">
                    <button
                        @click.stop="
                            openMenu = openMenu === quiz.id ? null : quiz.id
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
                        v-if="openMenu === quiz.id"
                        class="absolute top-8 right-0 z-20 w-44 overflow-hidden rounded-xl py-1"
                        style="
                            background: var(--gl-surface);
                            border: 1px solid var(--gl-border);
                            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
                        "
                    >
                        <template v-if="isClosed(quiz)">
                            <Link
                                @click.stop="openMenu = null"
                                :href="`/teacher/quizzes/${quiz.id}/edit`"
                                class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                                style="color: var(--gl-text-primary)"
                                ><Pencil
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-text-secondary)"
                                    :stroke-width="2"
                                />
                                Edit</Link
                            >
                            <Link
                                @click.stop="openMenu = null"
                                :href="`/teacher/quizzes/${quiz.id}`"
                                class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                                style="color: var(--gl-text-primary)"
                                ><Eye
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-primary)"
                                    :stroke-width="2"
                                />
                                Results</Link
                            >
                            <button
                                @click.stop="
                                    openMenu = null;
                                    reopen(quiz);
                                "
                                class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                                style="color: var(--gl-text-primary)"
                            >
                                <RefreshCw
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-primary)"
                                    :stroke-width="2"
                                />
                                Reopen
                            </button>
                        </template>
                        <template v-else>
                            <button
                                @click.stop="
                                    openMenu = null;
                                    togglePublish(quiz);
                                "
                                class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                                style="color: var(--gl-text-primary)"
                            >
                                <UploadCloud
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-text-secondary)"
                                    :stroke-width="2"
                                />
                                {{
                                    quiz.is_published ? 'Unpublish' : 'Publish'
                                }}
                            </button>
                            <Link
                                @click.stop="openMenu = null"
                                :href="`/teacher/quizzes/${quiz.id}/edit`"
                                class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                                style="color: var(--gl-text-primary)"
                                ><Pencil
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-text-secondary)"
                                    :stroke-width="2"
                                />
                                Edit</Link
                            >
                            <Link
                                @click.stop="openMenu = null"
                                :href="`/teacher/quizzes/${quiz.id}`"
                                class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                                style="color: var(--gl-text-primary)"
                                ><Eye
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-text-secondary)"
                                    :stroke-width="2"
                                />
                                View</Link
                            >
                        </template>
                        <button
                            @click.stop="
                                openMenu = null;
                                destroyQuiz(quiz);
                            "
                            class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-danger-bg)]"
                            style="color: var(--gl-danger)"
                        >
                            <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />
                            Delete
                        </button>
                        <div
                            v-if="teachers?.length"
                            class="border-t px-3.5 py-2"
                            style="border-color: var(--gl-border)"
                        >
                            <label
                                class="mb-1 block text-[10px] font-semibold"
                                style="color: var(--gl-text-muted)"
                                >Reassign:</label
                            >
                            <select
                                :value="quiz.teacher_id"
                                @change="reassignQuiz(quiz, ($event.target as HTMLSelectElement).value)"
                                @click.stop
                                class="w-full rounded-lg border px-2 py-1 text-xs outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            >
                                <option
                                    v-if="!teachers.some((t: any) => t.id === quiz.teacher_id)"
                                    :value="quiz.teacher_id"
                                    disabled
                                >
                                    {{ quiz.teacher?.name || 'Admin / Unassigned' }}
                                </option>
                                <option
                                    v-for="t in teachers"
                                    :key="t.id"
                                    :value="t.id"
                                >
                                    {{ t.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <p class="mt-4 text-xs" style="color: var(--gl-text-muted)">
            {{ filtered.length }} of {{ localQuizzes.length }} quizzes
        </p>
    </div>

    <div
        v-if="openMenu !== null"
        class="fixed inset-0 z-10"
        style="background: rgba(0, 0, 0, 0.25)"
        @click="openMenu = null"
    ></div>

    <!-- Create Modal -->
    <Teleport to="body">
        <div v-if="showCreate" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-8" style="background: rgba(0,0,0,0.25); backdrop-filter: blur(2px);" @click.self="showCreate = false">
            <div class="no-scrollbar my-auto max-h-[92vh] w-full max-w-5xl overflow-y-auto rounded-3xl p-6" style="background: var(--gl-surface); border: 1px solid var(--gl-border); box-shadow: 0 8px 32px rgba(0,0,0,0.4);">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl"
                            style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary)); box-shadow: 0 0 14px var(--gl-primary-glow);">
                            <Plus class="h-5 w-5 text-white" :stroke-width="2.5" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold tracking-tight" style="color: var(--gl-text-primary)">New quiz</h3>
                            <p class="text-[11px]" style="color: var(--gl-text-muted)">Build it question by question</p>
                        </div>
                    </div>
                    <button @click="showCreate = false" class="flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-surface-2)]" style="color: var(--gl-text-muted);"><X class="h-4 w-4" :stroke-width="2.5" /></button>
                </div>
                <form @submit.prevent="submitQuiz" class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.5fr)]">
                    <div class="rounded-2xl p-4" style="background: rgba(59,130,246,0.05); border: 1px solid rgba(59,130,246,0.16);">
                        <div class="mb-3 flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(59,130,246,0.14);">
                                <FileQuestion class="h-3.5 w-3.5" style="color: var(--gl-primary);" :stroke-width="2" />
                            </div>
                            <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Quiz details</h4>
                        </div>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="sm:col-span-2"><label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Title</label><input v-model="quizForm.title" type="text" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" placeholder="e.g. Photosynthesis Chapter Review" required /><p v-if="quizForm.errors?.title" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ quizForm.errors.title }}</p></div>
                            <div class="sm:col-span-2"><label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Description</label><textarea v-model="quizForm.description" rows="2" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" placeholder="Brief instructions for students"></textarea></div>
                            <div><label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Grade</label><select v-model="selectedGradeId" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" required><option :value="null">Select a grade</option><option v-for="gl in gradeLevels" :key="gl.id" :value="gl.id">{{ gl.name }}</option></select><p v-if="quizForm.errors?.grade_level_ids" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ quizForm.errors.grade_level_ids }}</p></div>
                            <div v-if="quizForm.grade_level_ids.length" class="sm:col-span-2"><label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Sections</label><div class="grid grid-cols-2 gap-2 sm:grid-cols-3"><button v-for="sec in filteredSections" :key="sec.id" type="button" class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-xs font-semibold transition-all" :style="quizForm.section_ids.includes(sec.id) ? 'border-color: var(--gl-primary); background: rgba(59,130,246,0.1); color: var(--gl-primary);' : 'border-color: var(--gl-border); color: var(--gl-text-secondary); background: var(--gl-surface);'" @click="toggleSection(sec.id)"><span class="flex h-4 w-4 shrink-0 items-center justify-center rounded" :style="quizForm.section_ids.includes(sec.id) ? 'background: var(--gl-primary);' : 'border: 1.5px solid var(--gl-text-muted);'"><Check v-if="quizForm.section_ids.includes(sec.id)" class="h-3 w-3 text-white" :stroke-width="3.5" /></span>{{ sec.name }}</button></div></div>
                            <div><label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Time limit (min)</label><input v-model="quizForm.time_limit_minutes" type="number" min="1" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" placeholder="e.g. 20" /></div>
                            <div v-if="props.schoolYears?.length">
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">School year</label>
                                <select v-model="quizForm.school_year_id" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" required>
                                    <option :value="null">Select a school year</option>
                                    <option v-for="year in props.schoolYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                                </select>
                            </div>
                            <div v-if="props.quarters?.length">
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Quarter</label>
                                <select v-model="quizForm.quarter_id" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);">
                                    <option :value="null">All Quarters / Unassigned</option>
                                    <option v-for="qtr in props.quarters" :key="qtr.id" :value="qtr.id">{{ qtr.code }} · {{ qtr.name }}</option>
                                </select>
                            </div>
                            <div v-if="teachers?.length" class="sm:col-span-2">
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Assign to teacher</label>
                                <select v-model="quizForm.teacher_id" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);">
                                    <option :value="null">Select a teacher</option>
                                    <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl p-4" style="background: rgba(124,58,237,0.05); border: 1px solid rgba(124,58,237,0.16);">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(124,58,237,0.14);">
                                    <ClipboardList class="h-3.5 w-3.5" style="color: var(--gl-secondary);" :stroke-width="2" />
                                </div>
                                <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Questions</h4>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums" style="background: var(--gl-surface-2); color: var(--gl-text-muted);">{{ quizForm.questions.length }}</span>
                            </div>
                            <button type="button" @click="addQuestion" class="flex h-8 w-8 items-center justify-center rounded-full text-white transition-all hover:scale-105" style="background: linear-gradient(135deg, var(--gl-secondary), var(--gl-primary)); box-shadow: 0 0 10px var(--gl-secondary-glow);" title="Add question"><Plus class="h-4 w-4" :stroke-width="2.5" /></button>
                        </div>
                        <div v-for="(q, qi) in quizForm.questions" :key="qi" class="mb-3 rounded-2xl p-4" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1"><label class="mb-1.5 block text-xs font-bold" style="color: var(--gl-primary);">Question {{ qi + 1 }}</label><input v-model="q.question_text" type="text" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" placeholder="Enter your question" required /></div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <select v-model="q.type" @change="onQuizTypeChange(q)" class="rounded-xl border px-2 py-2 text-xs font-semibold outline-none" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);">
                                        <option value="identification">Identification</option>
                                        <option value="enumeration">Enumeration</option>
                                        <option value="true_false">True/False</option>
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <option value="matching">Matching Type</option>
                                        <option value="essay">Essay</option>
                                    </select>
                                    <input v-model.number="q.points" type="number" min="1" class="w-14 rounded-xl border px-2 py-2 text-center text-xs font-bold tabular-nums outline-none" style="background: rgba(251,191,36,0.1); color: var(--gl-accent); border-color: rgba(251,191,36,0.25);" title="Points" />
                                    <button v-if="quizForm.questions.length > 1" type="button" @click="removeQuestion(qi)" class="flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);" title="Remove"><Trash2 class="h-3.5 w-3.5" :stroke-width="2" /></button>
                                </div>
                            </div>

                            <template v-if="q.type === 'identification'">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Correct answer</label>
                                    <input v-model="q.correct_answer" type="text" placeholder="Expected answer"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" required />
                                </div>
                            </template>

                            <template v-else-if="q.type === 'enumeration'">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-semibold" style="color: var(--gl-text-secondary);">Expected answers</label>
                                        <button type="button" @click="q.enum_items.push('')" class="text-xs font-bold transition-colors hover:opacity-80" style="color: var(--gl-primary);">+ Add item</button>
                                    </div>
                                    <div v-for="(item, ei) in q.enum_items" :key="ei" class="flex items-center gap-2">
                                        <span class="text-xs font-bold tabular-nums" style="color: var(--gl-text-muted)">{{ ei + 1 }}.</span>
                                        <input v-model="q.enum_items[ei]" type="text" :placeholder="'Answer ' + (ei + 1)"
                                            class="flex-1 rounded-xl border px-3 py-2 text-sm outline-none"
                                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" required />
                                        <button v-if="q.enum_items.length > 1" type="button" @click="q.enum_items.splice(ei, 1)"
                                            class="rounded-md p-1 transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);"><X class="h-3 w-3" :stroke-width="2" /></button>
                                    </div>
                                </div>
                            </template>

                            <template v-else-if="q.type === 'true_false' || q.type === 'multiple_choice'">
                                <div class="space-y-1.5">
                                    <div v-for="(opt, oi) in q.options" :key="oi" class="flex items-center gap-2 rounded-xl px-2.5 py-1.5 transition-colors" :style="opt.is_correct ? { background: 'rgba(16,185,129,0.08)' } : {}">
                                        <input :checked="opt.is_correct" @change="setCorrect(q, oi)" type="radio" :name="'correct_' + qi" class="h-4 w-4 shrink-0" style="accent-color: var(--gl-success);" title="Mark correct" />
                                        <input v-model="opt.option_text" type="text" class="flex-1 rounded-lg border px-3 py-2 text-sm outline-none" :style="opt.is_correct ? 'background: var(--gl-surface); color: var(--gl-text-primary); border-color: rgba(16,185,129,0.3);' : 'background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);'" :placeholder="q.type === 'true_false' ? (oi === 0 ? 'True' : 'False') : 'Option ' + (oi + 1)" :readonly="q.type === 'true_false'" required />
                                        <button v-if="q.type === 'multiple_choice' && q.options.length > 2" type="button" @click="removeOption(q, oi)" class="rounded-md p-1 transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);"><X class="h-3 w-3" :stroke-width="2" /></button>
                                    </div>
                                    <button v-if="q.type === 'multiple_choice'" type="button" @click="addOption(q)" class="mt-1 text-xs font-bold transition-colors hover:opacity-80" style="color: var(--gl-primary);">+ Add option</button>
                                </div>
                            </template>

                            <template v-else-if="q.type === 'matching'">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-semibold" style="color: var(--gl-text-secondary);">Matching pairs</label>
                                        <button type="button" @click="q.matching_pairs.push({ left_text: '', right_text: '' })" class="text-xs font-bold transition-colors hover:opacity-80" style="color: var(--gl-primary);">+ Add pair</button>
                                    </div>
                                    <div v-for="(pair, pi) in q.matching_pairs" :key="pi" class="flex items-center gap-2">
                                        <input v-model="pair.left_text" type="text" placeholder="Left item"
                                            class="flex-1 rounded-xl border px-3 py-2 text-sm outline-none"
                                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" required />
                                        <span class="text-xs font-bold" style="color: var(--gl-text-muted)">→</span>
                                        <input v-model="pair.right_text" type="text" placeholder="Right item"
                                            class="flex-1 rounded-xl border px-3 py-2 text-sm outline-none"
                                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" required />
                                        <button v-if="q.matching_pairs.length > 2" type="button" @click="q.matching_pairs.splice(pi, 1)"
                                            class="rounded-md p-1 transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);"><X class="h-3 w-3" :stroke-width="2" /></button>
                                    </div>
                                </div>
                            </template>

                            <template v-else-if="q.type === 'essay'">
                                <div class="space-y-1.5">
                                    <label class="flex items-center justify-between text-xs font-semibold" style="color: var(--gl-text-secondary);">
                                        <span>Grading Criteria (Evaluated by AI)</span>
                                        <span class="text-[11px] font-normal" style="color: var(--gl-text-muted)">Teacher rubric guidelines</span>
                                    </label>
                                    <textarea v-model="q.criteria" rows="3"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);"
                                        placeholder="e.g. 1. Accurate explanation of core concepts (4 pts). 2. Supporting examples provided (3 pts). 3. Logical structure and clarity (3 pts)."
                                        required></textarea>
                                    <p class="text-[11px]" style="color: var(--gl-text-muted)">AI will check student submissions automatically against your criteria.</p>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2.5 pt-1 lg:col-span-2">
                        <button type="button" @click="showCreate = false" class="rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02]" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);">Cancel</button>
                        <button type="submit" :disabled="quizForm.processing" class="rounded-full px-5 py-2.5 text-xs font-bold text-white transition-all hover:scale-[1.02] disabled:opacity-50" style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary)); box-shadow: 0 0 14px var(--gl-primary-glow);">Create Quiz</button>

                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { Plus, Eye, Trash2, UploadCloud, FileQuestion, ClipboardList, Check, X, RefreshCw, Search, Ellipsis, Pencil, Users, Sparkles } from '@lucide/vue';

import { computed, ref, watch } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';

const props = defineProps<{
    quizzes: any[];
    gradeLevels: any[];
    allSections: any[];
    quarters?: any[];
    schoolYears?: any[];
    defaultSchoolYearId?: number | null;
    isSuperadmin?: boolean;
    teachers?: any[];
}>();
const page = usePage();
const flash = computed(() => (page.props as any).flash);

const localQuizzes = ref<any[]>([...(props.quizzes || [])]);

watch(
    () => props.quizzes,
    (val) => {
        localQuizzes.value = [...(val || [])];
    },
    { deep: true },
);

const query = ref('');
const statusTab = ref('all');
const quarterFilter = ref<number | ''>('');
const openMenu = ref<number | null>(null);
const statusTabs = [
    { value: 'all', label: 'All' },
    { value: 'draft', label: 'Drafts' },
    { value: 'published', label: 'Published' },
    { value: 'finished', label: 'Finished' },
];

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();

    return localQuizzes.value.filter((quiz) => {
        if (statusTab.value !== 'all' && statusLabel(quiz) !== statusTab.value) {
return false;
}

        if (quarterFilter.value !== '' && Number(quiz.quarter_id) !== Number(quarterFilter.value)) {
return false;
}

        if (q && !(quiz.title || '').toLowerCase().includes(q)) {
return false;
}

        return true;
    });
});

const publishedCount = computed(
    () => localQuizzes.value.filter((q) => statusLabel(q) === 'published').length,
);
const draftCount = computed(
    () => localQuizzes.value.filter((q) => statusLabel(q) === 'draft').length,
);
const totalAttempts = computed(() =>
    localQuizzes.value.reduce((a, q) => a + (q.attempts_count ?? 0), 0),
);

function goView(quiz: any) {
    openMenu.value = null;
    router.get(`/teacher/quizzes/${quiz.id}`);
}

function targetLabel(quiz: any): string {
    const grades = quiz.grade_levels
        ? quiz.grade_levels.map((g: any) => g.name).join(', ')
        : (quiz.grade ?? '');
    const secs = quiz.sections?.length
        ? ' — ' + quiz.sections.map((s: any) => s.name).join(', ')
        : '';

    return (grades + secs).trim() || '—';
}

interface Option {
    option_text: string;
    is_correct: boolean;
}
interface Question {
    question_text: string;
    type: string;
    points: number;
    options: Option[];
    matching_pairs: { left_text: string; right_text: string }[];
    correct_answer: string;
    enum_items: string[];
    criteria?: string;
}
function newOption(isCorrect = false): Option {
    return { option_text: '', is_correct: isCorrect };
}
function newQuestion(): Question {
    return {
        question_text: '',
        type: 'identification',
        points: 1,
        options: [],
        matching_pairs: [],
        correct_answer: '',
        enum_items: [''],
        criteria: '',
    };
}

const showCreate = ref(false);
const quizForm = useForm({
    title: '',
    description: '',
    teacher_id: null as number | null,
    quarter_id: null as number | null,
    school_year_id: null as number | null,
    grade_level_ids: [] as number[],
    section_ids: [] as number[],
    time_limit_minutes: '',
    questions: [newQuestion()],
});

function openCreateModal() {
    quizForm.reset();
    quizForm.clearErrors();
    quizForm.grade_level_ids = [];
    quizForm.school_year_id = props.defaultSchoolYearId ?? null;
    quizForm.questions = [newQuestion()];
    showCreate.value = true;
}
const filteredSections = computed(() =>
    props.allSections.filter((s: any) =>
        quizForm.grade_level_ids.includes(s.grade_level_id),
    ),
);
const selectedGradeId = computed<number | null>({
    get: () => quizForm.grade_level_ids[0] ?? null,
    set: (value) => {
        quizForm.grade_level_ids = value ? [Number(value)] : [];
        quizForm.section_ids = quizForm.section_ids.filter((sid) =>
            filteredSections.value.some((s: any) => s.id === sid),
        );

        if (value) {
quizForm.clearErrors('grade_level_ids');
}
    },
});
function toggleSection(id: number) {
    const idx = quizForm.section_ids.indexOf(id);

    if (idx === -1) {
quizForm.section_ids.push(id);
} else {
quizForm.section_ids.splice(idx, 1);
}
}
function onQuizTypeChange(q: Question) {
    if (q.type === 'true_false') {
        q.options = [
            { option_text: 'True', is_correct: true },
            { option_text: 'False', is_correct: false },
        ];
        q.matching_pairs = [];
        q.enum_items = [];
    } else if (q.type === 'multiple_choice') {
        q.options = [newOption(true), newOption(), newOption(), newOption()];
        q.matching_pairs = [];
        q.enum_items = [];
    } else if (q.type === 'matching') {
        q.matching_pairs = [
            { left_text: '', right_text: '' },
            { left_text: '', right_text: '' },
            { left_text: '', right_text: '' },
        ];
        q.options = [];
        q.enum_items = [];
    } else if (q.type === 'enumeration') {
        q.enum_items = [''];
        q.options = [];
        q.matching_pairs = [];
    } else if (q.type === 'identification') {
        q.options = [];
        q.matching_pairs = [];
        q.enum_items = [];
    } else if (q.type === 'essay') {
        q.options = [];
        q.matching_pairs = [];
        q.enum_items = [];

        if (!q.criteria) {
q.criteria = '';
}
    }
}

function setCorrect(q: Question, oi: number) {
    q.options.forEach((o, i) => {
        o.is_correct = i === oi;
    });
}
function addQuestion() {
    quizForm.questions.push(newQuestion());
}
function removeQuestion(i: number) {
    quizForm.questions.splice(i, 1);
}
function addOption(q: Question) {
    q.options.push(newOption());
}
function removeOption(q: Question, i: number) {
    q.options.splice(i, 1);
}
function submitQuiz() {
    quizForm.questions.forEach((q) => {
        if (q.type === 'true_false') {
            q.options = [
                {
                    option_text: 'True',
                    is_correct: q.options[0]?.is_correct ?? true,
                },
                {
                    option_text: 'False',
                    is_correct: q.options[1]?.is_correct ?? false,
                },
            ];
        } else if (q.type === 'identification') {
            q.options = [
                {
                    option_text: q.correct_answer || '',
                    is_correct: true,
                },
            ];
        } else if (q.type === 'enumeration') {
            q.options = (q.enum_items || [])
                .filter((item) => item.trim() !== '')
                .map((item) => ({
                    option_text: item,
                    is_correct: true,
                }));
        }
    });

    quizForm.post('/teacher/quizzes', {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            quizForm.reset();
            quizForm.school_year_id = props.defaultSchoolYearId ?? null;
            quizForm.questions = [newQuestion()];
        },
    });
}
function reassignQuiz(quiz: any, teacherId: string | number) {
    if (!teacherId || Number(teacherId) === quiz.teacher_id) {
return;
}

    openMenu.value = null;
    router.patch(
        `/teacher/quizzes/${quiz.id}/reassign`,
        { teacher_id: Number(teacherId) },
        { preserveScroll: true },
    );
}
function togglePublish(quiz: any) {
    const prev = quiz.is_published;
    quiz.is_published = !quiz.is_published;
    router.patch(
        `/teacher/quizzes/${quiz.id}/publish`,
        {},
        {
            preserveScroll: true,
            onError: () => {
                quiz.is_published = prev;
            },
        },
    );
}
function isClosed(quiz: any): boolean {
    if (!quiz.closes_at) {
return false;
}

    return new Date(quiz.closes_at) < new Date();
}
function statusLabel(a: any): string {
    if (!a.is_published) {
return 'draft';
}

    if (a.closes_at && new Date(a.closes_at) < new Date()) {
return 'finished';
}

    return 'published';
}
function reopen(quiz: any) {
    const prev = quiz.closes_at;
    quiz.closes_at = null;
    router.patch(
        `/teacher/quizzes/${quiz.id}/reopen`,
        {},
        {
            preserveScroll: true,
            onError: () => {
                quiz.closes_at = prev;
            },
        },
    );
}
function destroyQuiz(quiz: any) {
    if (!confirm(`Delete "${quiz.title}"? This cannot be undone.`)) {
return;
}

    openMenu.value = null;
    const backup = [...localQuizzes.value];
    localQuizzes.value = localQuizzes.value.filter((q: any) => q.id !== quiz.id);
    router.delete(`/teacher/quizzes/${quiz.id}`, {
        preserveScroll: true,
        onError: () => {
            localQuizzes.value = backup;
        },
    });
}
</script>
