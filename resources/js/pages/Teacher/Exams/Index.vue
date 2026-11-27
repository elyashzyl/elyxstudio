<template>
    <Head title="Exams" />

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
                Exams
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ localExams.length }} exams · {{ totalAttempts }} attempts
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
            <button
                @click="openCompileModal"
                class="inline-flex items-center gap-1.5 rounded-xl px-3.5 py-2.5 text-xs font-bold text-white transition-all hover:scale-105"
                style="
                    background: linear-gradient(135deg, #7C3AED, #6366F1);
                    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.35);
                "
                title="Compile exam from activities with AI"
            >
                <Sparkles class="h-4 w-4" :stroke-width="2" />
                <span>Compile with AI</span>
            </button>
            <button
                @click="openCreate"
                class="flex h-10 w-10 items-center justify-center rounded-xl text-white transition-all hover:scale-105"
                style="
                    background: linear-gradient(135deg, #ff6a45, #f94f2b);
                    box-shadow: 0 4px 14px rgba(249, 79, 43, 0.4);
                "
                title="New exam"
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
                background: rgba(239, 68, 68, 0.08);
                border: 1px solid rgba(239, 68, 68, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(239, 68, 68, 0.16)"
            >
                <FileText
                    class="h-[18px] w-[18px]"
                    style="color: #ef4444"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ localExams.length }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Total exams
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
            <FileText
                class="h-6 w-6"
                style="color: var(--gl-text-muted)"
                :stroke-width="1.75"
            />
        </div>
        <p class="text-sm font-medium" style="color: var(--gl-text-secondary)">
            {{ localExams.length ? 'No matches' : 'No exams yet' }}
        </p>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
            {{
                localExams.length
                    ? 'Try a different search or filter.'
                    : 'Create a composite exam with multiple sections.'
            }}
        </p>
        <button
            v-if="!localExams.length"
            @click="openCreate"
            class="mt-4 flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
            style="
                background: linear-gradient(
                    135deg,
                    var(--gl-secondary),
                    var(--gl-primary)
                );
                box-shadow: 0 0 12px var(--gl-secondary-glow);
            "
        >
            <Plus class="h-4 w-4" :stroke-width="2" /> New Exam
        </button>
    </div>

    <div v-else>
        <div
            class="mb-1 hidden grid-cols-[1fr_110px_90px_90px_40px] items-center gap-3 px-4 pb-2 text-[11px] font-semibold tracking-wider uppercase sm:grid"
            style="color: var(--gl-text-muted)"
        >
            <span>Title</span><span>Status</span
            ><span class="text-center">Sections</span
            ><span class="text-center">Attempts</span><span></span>
        </div>
        <div class="space-y-2">
            <div
                v-for="e in filtered"
                :key="e.id"
                class="grid cursor-pointer grid-cols-[auto_1fr_40px] items-center gap-3 rounded-2xl px-4 py-3 transition-all sm:grid-cols-[1fr_110px_90px_90px_40px]"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
                @click="goView(e)"
            >
                <div class="min-w-0">
                    <p
                        class="truncate text-sm font-semibold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ e.title }}
                    </p>
                    <p
                        class="truncate text-[11px]"
                        style="color: var(--gl-text-muted)"
                    >
                        <span
                            v-if="e.quarter"
                            class="mr-1.5 inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                            style="background: rgba(249, 79, 43, 0.12); color: #f94f2b;"
                        >{{ e.quarter.code }}</span>
                        <span
                            v-if="e.school_year"
                            class="mr-1.5 inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                            style="background: rgba(239, 68, 68, 0.12); color: #EF4444;"
                        >{{ e.school_year.name }}</span>
                        <span v-if="isSuperadmin && e.teacher?.name"
                            >{{ e.teacher.name }} · </span
                        >{{ targetLabel(e) }}
                    </p>
                </div>
                <span class="hidden sm:block"
                    ><StatusBadge :status="statusLabel(e)"
                /></span>
                <span
                    class="hidden text-center text-sm font-semibold tabular-nums sm:block"
                    style="color: var(--gl-text-primary)"
                    >{{ e.sections_count }}</span
                >
                <span
                    class="hidden text-center text-sm font-semibold tabular-nums sm:block"
                    style="color: var(--gl-text-primary)"
                    >{{ e.attempts_count }}</span
                >
                <div class="relative flex justify-end">
                    <button
                        @click.stop="openMenu = openMenu === e.id ? null : e.id"
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
                        v-if="openMenu === e.id"
                        class="absolute top-8 right-0 z-20 w-44 overflow-hidden rounded-xl py-1"
                        style="
                            background: var(--gl-surface);
                            border: 1px solid var(--gl-border);
                            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
                        "
                    >
                        <template v-if="isClosed(e)">
                            <Link
                                @click.stop="openMenu = null"
                                :href="`/teacher/exams/${e.id}/edit`"
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
                                :href="`/teacher/exams/${e.id}`"
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
                                    reopen(e);
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
                                    togglePublish(e);
                                "
                                class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                                style="color: var(--gl-text-primary)"
                            >
                                <UploadCloud
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-text-secondary)"
                                    :stroke-width="2"
                                />
                                {{ e.is_published ? 'Unpublish' : 'Publish' }}
                            </button>
                            <Link
                                @click.stop="openMenu = null"
                                :href="`/teacher/exams/${e.id}/edit`"
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
                                :href="`/teacher/exams/${e.id}`"
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
                                destroyE(e);
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
            {{ filtered.length }} of {{ localExams.length }} exams
        </p>
    </div>

    <div
        v-if="openMenu !== null"
        class="fixed inset-0 z-10"
        style="background: rgba(0, 0, 0, 0.25)"
        @click="openMenu = null"
    ></div>

    <Teleport to="body">
        <div v-if="showCreate" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-8" style="background: rgba(0,0,0,0.25); backdrop-filter: blur(2px);" @click.self="showCreate = false">
            <div class="no-scrollbar my-auto max-h-[92vh] w-full max-w-5xl overflow-y-auto rounded-3xl p-6" style="background: var(--gl-surface); border: 1px solid var(--gl-border); box-shadow: 0 8px 32px rgba(0,0,0,0.4);">
                <div class="mb-5 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-2xl"
                            style="background: linear-gradient(135deg, #EF4444, #DC2626); box-shadow: 0 0 14px rgba(239,68,68,0.35);">
                            <Plus class="h-5 w-5 text-white" :stroke-width="2.5" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold tracking-tight" style="color: var(--gl-text-primary)">New exam</h3>
                            <p class="text-[11px]" style="color: var(--gl-text-muted)">Build it section by section</p>
                        </div>
                    </div>
                    <button @click="showCreate = false" class="flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-surface-2)]" style="color: var(--gl-text-muted);"><X class="h-4 w-4" :stroke-width="2.5" /></button>
                </div>

                <form @submit.prevent="submitForm" class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.5fr)]">
                    <div class="rounded-2xl p-4" style="background: rgba(239,68,68,0.05); border: 1px solid rgba(239,68,68,0.16);">
                        <div class="mb-3 flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(239,68,68,0.14);">
                                <FileText class="h-3.5 w-3.5" style="color: #EF4444;" :stroke-width="2" />
                            </div>
                            <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Details</h4>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Title</label>
                                <input v-model="form.title" type="text" placeholder="e.g. Midterm Exam" required
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                    style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Grade</label>
                                <select v-model="selectedGradeId" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" required>
                                    <option :value="null">Select a grade</option>
                                    <option v-for="gl in gradeLevels" :key="gl.id" :value="gl.id">{{ gl.name }}</option>
                                </select>
                                <p v-if="form.errors.grade_level_ids" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ form.errors.grade_level_ids }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Instructions (optional)</label>
                                <textarea v-model="form.instructions" rows="2" placeholder="General instructions for the exam"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                    style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);"></textarea>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Time limit (min)</label>
                                    <input v-model="form.time_limit_minutes" type="number" min="1"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                </div>
                                <div v-if="props.schoolYears?.length">
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">School year</label>
                                    <select v-model="form.school_year_id" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" required>
                                        <option :value="null">Select a school year</option>
                                        <option v-for="year in props.schoolYears" :key="year.id" :value="year.id">{{ year.name }}</option>
                                    </select>
                                </div>
                                <div v-if="props.quarters?.length">
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Quarter</label>
                                    <select v-model="form.quarter_id"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);">
                                        <option :value="null">All Quarters / Unassigned</option>
                                        <option v-for="qtr in props.quarters" :key="qtr.id" :value="qtr.id">{{ qtr.code }} · {{ qtr.name }}</option>
                                    </select>
                                </div>
                                <div v-if="teachers?.length" class="sm:col-span-2">
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Assign to teacher</label>
                                    <select v-model="form.teacher_id"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);">
                                        <option :value="null">Select a teacher</option>
                                        <option v-for="t in teachers" :key="t.id" :value="t.id">{{ t.name }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl p-4" style="background: rgba(124,58,237,0.05); border: 1px solid rgba(124,58,237,0.16);">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(124,58,237,0.14);">
                                    <Layers class="h-3.5 w-3.5" style="color: var(--gl-secondary);" :stroke-width="2" />
                                </div>
                                <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Sections</h4>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums" style="background: var(--gl-surface-2); color: var(--gl-text-muted);">{{ form.sections.length }}</span>
                            </div>
                            <div class="flex gap-1.5 flex-wrap">
                                <button type="button" @click="openCompileModal" class="rounded-full px-3 py-1.5 text-[11px] font-bold transition-all hover:scale-105 inline-flex items-center gap-1.5" style="background: rgba(124, 58, 237, 0.14); border: 1px solid rgba(124, 58, 237, 0.35); color: var(--gl-secondary);">
                                    <Sparkles class="h-3 w-3" />
                                    Compile Activities
                                </button>
                                <button type="button" @click="addSection('questions')" class="rounded-full px-3 py-1.5 text-[11px] font-bold transition-all hover:scale-105" style="background: var(--gl-surface); border: 1px solid var(--gl-border); color: var(--gl-text-secondary);">+ Questions</button>
                                <button type="button" @click="addSection('practical')" class="rounded-full px-3 py-1.5 text-[11px] font-bold transition-all hover:scale-105" style="background: var(--gl-surface); border: 1px solid var(--gl-border); color: var(--gl-text-secondary);">+ Practical</button>
                            </div>
                        </div>

                        <div v-for="(sec, si) in form.sections" :key="si" class="mb-3 rounded-2xl p-4" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
                            <div class="mb-3 flex items-center justify-between gap-2">
                                <span class="flex min-w-0 items-center gap-2 text-xs font-extrabold uppercase" style="color: var(--gl-text-primary); letter-spacing: 0.06em;">
                                    <FileText v-if="sec.section_type === 'questions'" class="h-3.5 w-3.5 shrink-0" style="color: var(--gl-secondary);" :stroke-width="2" />
                                    <ClipboardCheck v-else class="h-3.5 w-3.5 shrink-0" style="color: var(--gl-secondary);" :stroke-width="2" />
                                    <span class="truncate">Section {{ si + 1 }} — {{ sec.section_type === 'questions' ? 'Questions' : 'Practical' }}</span>
                                </span>
                                <button type="button" @click="form.sections.splice(si, 1)" title="Remove section"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);">
                                    <X class="h-3.5 w-3.5" :stroke-width="2" />

                                </button>
                            </div>

                            <div class="mb-3 grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Title</label>
                                    <input v-model="sec.title" type="text" placeholder="e.g. Part I" required
                                        class="w-full rounded-xl border px-3 py-2 text-sm outline-none"
                                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Instructions</label>
                                    <input v-model="sec.instructions" type="text" placeholder="Section instructions"
                                        class="w-full rounded-xl border px-3 py-2 text-sm outline-none"
                                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" />

                                </div>
                            </div>

                            <template v-if="sec.section_type === 'questions'">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-xs font-bold" style="color: var(--gl-text-secondary)">Questions</span>
                                    <button type="button" @click="addQuestion(si)" class="text-[11px] font-bold transition-colors hover:opacity-80" style="color: var(--gl-primary);">+ Add question</button>
                                </div>

                                <div v-for="(q, qi) in sec.questions" :key="qi" class="mb-2 rounded-xl p-3" style="background: var(--gl-surface-2);">
                                    <div class="mb-2 flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <label class="mb-1.5 block text-xs font-bold" style="color: var(--gl-primary);">Question {{ qi + 1 }}</label>
                                            <textarea v-model="q.question_text" rows="1" :placeholder="q.type === 'identification' ? 'e.g. What is the capital of France?' : 'Question text'" required
                                                class="w-full rounded-xl border px-3 py-2 text-sm outline-none"
                                                style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);"></textarea>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1.5">
                                            <select v-model="q.type" class="rounded-xl border px-2 py-2 text-xs font-semibold outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);">
                                                <option value="multiple_choice">Multiple Choice</option>
                                                <option value="true_false">True or False</option>
                                                <option value="identification">Identification</option>
                                                <option value="enumeration">Enumeration</option>
                                                <option value="matching">Matching</option>
                                                <option value="essay">Essay</option>
                                            </select>
                                            <input v-model.number="q.points" type="number" min="1" title="Points"
                                                class="w-14 rounded-xl border px-2 py-2 text-center text-xs font-bold tabular-nums outline-none"
                                                style="background: rgba(251,191,36,0.08); color: var(--gl-accent); border-color: rgba(251,191,36,0.25);" />
                                            <button type="button" @click="sec.questions.splice(qi, 1)" title="Remove"
                                                class="rounded-md p-1 transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);">
                                                <X class="h-3.5 w-3.5" :stroke-width="2" />

                                            </button>
                                        </div>
                                    </div>

                                    <template v-if="q.type === 'multiple_choice'">
                                        <div v-for="(opt, oi) in q.options" :key="oi" class="mt-1 flex items-center gap-2">
                                            <input v-model="opt.option_text" type="text" :placeholder="`Option ${oi + 1}`" required
                                                class="flex-1 rounded-lg border px-2.5 py-1.5 text-xs outline-none"
                                                style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                            <label class="flex shrink-0 cursor-pointer items-center gap-1 text-[11px] font-semibold" style="color: var(--gl-text-secondary)">
                                                <input type="radio" :name="'mc-correct-' + si + '-' + qi" :checked="opt.is_correct" @change="setCorrectOption(sec.questions, qi, oi)" class="h-3.5 w-3.5" style="accent-color: var(--gl-success);" />
                                                Correct
                                            </label>
                                            <button v-if="q.options.length > 2" type="button" @click="q.options.splice(oi, 1)"
                                                class="rounded-md p-0.5 transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);">
                                                <X class="h-3 w-3" :stroke-width="2" />
                                            </button>
                                        </div>
                                        <button type="button" @click="q.options.push({ option_text: '', is_correct: false })" class="mt-1 text-[11px] font-bold transition-colors hover:opacity-80" style="color: var(--gl-primary);">+ Add option</button>
                                    </template>

                                    <template v-if="q.type === 'true_false'">
                                        <div class="mt-1 flex gap-4">
                                            <label class="flex cursor-pointer items-center gap-1.5 text-xs font-semibold" style="color: var(--gl-text-primary)">
                                                <input type="radio" :name="'tf-' + si + '-' + qi" value="true" v-model="q.correct_answer" class="h-3.5 w-3.5" style="accent-color: var(--gl-success);" /> True
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-1.5 text-xs font-semibold" style="color: var(--gl-text-primary)">
                                                <input type="radio" :name="'tf-' + si + '-' + qi" value="false" v-model="q.correct_answer" class="h-3.5 w-3.5" style="accent-color: var(--gl-danger);" /> False

                                            </label>
                                        </div>
                                    </template>

                                    <template v-if="q.type === 'identification'">
                                        <input v-model="q.correct_answer" type="text" placeholder="Correct answer"
                                            class="mt-1 w-full rounded-xl border px-3 py-2 text-xs outline-none"
                                            style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                    </template>

                                    <template v-if="q.type === 'enumeration'">
                                        <div v-for="(item, ii) in q.enum_items" :key="ii" class="mt-1 flex items-center gap-2">
                                            <span class="text-[11px] font-bold tabular-nums" style="color: var(--gl-text-muted)">{{ ii + 1 }}.</span>
                                            <input v-model="q.enum_items[ii]" type="text" :placeholder="`Item ${ii + 1}`"
                                                class="flex-1 rounded-xl border px-3 py-2 text-xs outline-none"
                                                style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                            <button v-if="q.enum_items.length > 1" type="button" @click="q.enum_items.splice(ii, 1)"
                                                class="rounded-md p-0.5 transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);">
                                                <X class="h-3 w-3" :stroke-width="2" />

                                            </button>
                                        </div>
                                        <button type="button" @click="q.enum_items.push('')" class="mt-1 text-[11px] font-bold transition-colors hover:opacity-80" style="color: var(--gl-primary);">+ Add item</button>
                                    </template>

                                    <template v-if="q.type === 'matching'">
                                        <div class="flex items-center justify-between">
                                            <label class="text-[11px] font-bold" style="color: var(--gl-text-secondary);">Matching pairs</label>
                                            <button type="button" @click="q.matching_pairs.push({ left_text: '', right_text: '' })" class="text-[11px] font-bold transition-colors hover:opacity-80" style="color: var(--gl-primary);">+ Add pair</button>
                                        </div>
                                        <div v-for="(pair, pi) in q.matching_pairs" :key="pi" class="mt-1 flex items-center gap-2">
                                            <input v-model="pair.left_text" type="text" placeholder="Left item"
                                                class="flex-1 rounded-xl border px-3 py-2 text-xs outline-none"
                                                style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                            <span class="text-xs font-bold" style="color: var(--gl-text-muted)">→</span>
                                            <input v-model="pair.right_text" type="text" placeholder="Right item"
                                                class="flex-1 rounded-xl border px-3 py-2 text-xs outline-none"
                                                style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                            <button v-if="q.matching_pairs.length > 2" type="button" @click="q.matching_pairs.splice(pi, 1)"
                                                class="rounded-md p-0.5 transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);">
                                                <X class="h-3 w-3" :stroke-width="2" />
                                            </button>

                                        </div>
                                    </template>

                                    <template v-if="q.type === 'essay'">
                                        <div class="mt-1 space-y-1">
                                            <label class="text-[11px] font-bold" style="color: var(--gl-text-secondary);">Grading Criteria (AI Graded)</label>
                                            <textarea v-model="q.criteria" rows="2" placeholder="Explain scoring criteria..."
                                                class="w-full rounded-xl border px-3 py-2 text-xs outline-none"
                                                style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);"></textarea>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template v-if="sec.section_type === 'practical'">
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="text-xs font-bold" style="color: var(--gl-text-secondary)">Rubric criteria</span>
                                    <button type="button" @click="addCriterion(si)" class="text-[11px] font-bold transition-colors hover:opacity-80" style="color: var(--gl-primary);">+ Add criterion</button>
                                </div>

                                <div v-for="(c, ci) in sec.criteria" :key="ci" class="mb-2 rounded-xl p-3" style="background: var(--gl-surface-2);">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 flex-1">
                                            <label class="mb-1.5 block text-xs font-bold" style="color: var(--gl-accent);">Criterion {{ ci + 1 }}</label>
                                            <input v-model="c.criterion_name" type="text" placeholder="e.g. Accuracy" required
                                                class="w-full rounded-xl border px-3 py-2 text-xs outline-none"
                                                style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                        </div>
                                        <div class="flex shrink-0 items-center gap-1.5">
                                            <div class="w-16">
                                                <label class="mb-1.5 block text-center text-[10px] font-bold" style="color: var(--gl-text-muted);">Max pts</label>
                                                <input v-model.number="c.max_points" type="number" min="1"
                                                    class="w-full rounded-xl border px-2 py-2 text-center text-xs font-bold tabular-nums outline-none"
                                                    style="background: rgba(251,191,36,0.08); color: var(--gl-accent); border-color: rgba(251,191,36,0.25);" />
                                            </div>
                                            <button v-if="sec.criteria.length > 1" type="button" @click="sec.criteria.splice(ci, 1)" title="Remove"
                                                class="mt-5 rounded-md p-1 transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);">
                                                <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Description (optional)</label>
                                        <input v-model="c.description" type="text" placeholder="Describe what to assess"
                                            class="w-full rounded-xl border px-3 py-2 text-xs outline-none"
                                            style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />

                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-1 lg:col-span-2">
                        <button type="button" @click="showCreate = false" class="rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02]" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="rounded-full px-5 py-2.5 text-xs font-bold text-white transition-all hover:scale-[1.02] disabled:opacity-50" style="background: linear-gradient(135deg, #EF4444, #DC2626); box-shadow: 0 0 14px rgba(239,68,68,0.35);">Create</button>

                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Compile Activities with AI Modal -->
    <Teleport to="body">
        <div v-if="showCompile" class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto py-8" style="background: rgba(0,0,0,0.4); backdrop-filter: blur(2px);" @click.self="showCompile = false">
            <div class="no-scrollbar my-auto max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl p-6" style="background: var(--gl-surface); border: 1px solid var(--gl-border); box-shadow: 0 8px 32px rgba(0,0,0,0.4);">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl" style="background: rgba(124, 58, 237, 0.16);">
                            <Sparkles class="h-4.5 w-4.5" style="color: var(--gl-secondary);" :stroke-width="2" />
                        </div>
                        <div>
                            <h3 class="text-base font-bold" style="color: var(--gl-text-primary)">Compile Exam from Activities</h3>
                            <p class="text-xs" style="color: var(--gl-text-muted)">Select Quizzes, Seatworks, and Practicals to compile. AI can automatically rephrase the questions so students cannot rely on memorized phrasing.</p>
                        </div>
                    </div>
                    <button @click="showCompile = false" class="flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-surface-2)]" style="color: var(--gl-text-muted);"><X class="h-4 w-4" :stroke-width="2.5" /></button>
                </div>

                <!-- Category Selector Tabs -->
                <div class="mb-4 flex items-center gap-1.5 border-b border-[var(--gl-border)] pb-3">
                    <button
                        type="button"
                        v-for="cat in compileTabs"
                        :key="cat.id"
                        @click="activeCompileTab = cat.id as any"
                        class="rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                        :style="activeCompileTab === cat.id
                            ? { background: 'var(--gl-surface-2)', color: 'var(--gl-primary)', border: '1px solid var(--gl-border)' }
                            : { color: 'var(--gl-text-muted)' }"
                    >
                        {{ cat.label }} ({{ cat.count }})
                    </button>
                </div>

                <!-- List: Quizzes -->
                <div v-show="activeCompileTab === 'quizzes'" class="space-y-2 max-h-64 overflow-y-auto no-scrollbar">
                    <div v-if="!(teacherQuizzes?.length)" class="py-8 text-center text-xs" style="color: var(--gl-text-muted);">
                        No quizzes found.
                    </div>
                    <label
                        v-for="q in teacherQuizzes"
                        :key="'q-' + q.id"
                        class="flex cursor-pointer items-center justify-between rounded-2xl p-3 transition-colors hover:bg-[var(--gl-surface-2)]"
                        :style="{ border: '1px solid var(--gl-border)', background: selectedQuizIds.includes(q.id) ? 'rgba(59, 130, 246, 0.08)' : 'var(--gl-surface)' }"
                    >
                        <div class="flex items-center gap-3">
                            <input type="checkbox" :value="q.id" v-model="selectedQuizIds" class="gl-check cursor-pointer" />
                            <div>
                                <p class="text-xs font-bold" style="color: var(--gl-text-primary)">{{ q.title }}</p>
                                <p class="text-[11px]" style="color: var(--gl-text-muted)">{{ q.grade ? q.grade + ' · ' : '' }}{{ q.questions_count }} question{{ q.questions_count === 1 ? '' : 's' }}</p>
                            </div>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="background: rgba(59, 130, 246, 0.12); color: var(--gl-primary)">Quiz</span>
                    </label>
                </div>

                <!-- List: Seatworks -->
                <div v-show="activeCompileTab === 'seatworks'" class="space-y-2 max-h-64 overflow-y-auto no-scrollbar">
                    <div v-if="!(teacherSeatworks?.length)" class="py-8 text-center text-xs" style="color: var(--gl-text-muted);">
                        No seatworks found.
                    </div>
                    <label
                        v-for="s in teacherSeatworks"
                        :key="'s-' + s.id"
                        class="flex cursor-pointer items-center justify-between rounded-2xl p-3 transition-colors hover:bg-[var(--gl-surface-2)]"
                        :style="{ border: '1px solid var(--gl-border)', background: selectedSeatworkIds.includes(s.id) ? 'rgba(16, 185, 129, 0.08)' : 'var(--gl-surface)' }"
                    >
                        <div class="flex items-center gap-3">
                            <input type="checkbox" :value="s.id" v-model="selectedSeatworkIds" class="gl-check cursor-pointer" />
                            <div>
                                <p class="text-xs font-bold" style="color: var(--gl-text-primary)">{{ s.title }}</p>
                                <p class="text-[11px]" style="color: var(--gl-text-muted)">{{ s.grade ? s.grade + ' · ' : '' }}{{ s.questions_count }} question{{ s.questions_count === 1 ? '' : 's' }}</p>
                            </div>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="background: rgba(16, 185, 129, 0.12); color: var(--gl-success)">Seatwork</span>
                    </label>
                </div>

                <!-- List: Practicals -->
                <div v-show="activeCompileTab === 'practicals'" class="space-y-2 max-h-64 overflow-y-auto no-scrollbar">
                    <div v-if="!(teacherPracticals?.length)" class="py-8 text-center text-xs" style="color: var(--gl-text-muted);">
                        No practicals found.
                    </div>
                    <label
                        v-for="p in teacherPracticals"
                        :key="'p-' + p.id"
                        class="flex cursor-pointer items-center justify-between rounded-2xl p-3 transition-colors hover:bg-[var(--gl-surface-2)]"
                        :style="{ border: '1px solid var(--gl-border)', background: selectedPracticalIds.includes(p.id) ? 'rgba(251, 191, 36, 0.08)' : 'var(--gl-surface)' }"
                    >
                        <div class="flex items-center gap-3">
                            <input type="checkbox" :value="p.id" v-model="selectedPracticalIds" class="gl-check cursor-pointer" />
                            <div>
                                <p class="text-xs font-bold" style="color: var(--gl-text-primary)">{{ p.title }}</p>
                                <p class="text-[11px]" style="color: var(--gl-text-muted)">{{ p.grade ? p.grade + ' · ' : '' }}{{ p.criteria_count }} rubric criteria</p>
                            </div>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-bold" style="background: rgba(251, 191, 36, 0.15); color: var(--gl-accent)">Practical</span>
                    </label>
                </div>

                <!-- AI Rephrase Option -->
                <div class="mt-4 rounded-2xl p-3.5 border border-[var(--gl-border)]" style="background: var(--gl-surface-2)">
                    <label class="flex cursor-pointer items-start gap-2.5">
                        <input type="checkbox" v-model="rephraseWithAi" class="gl-check mt-0.5" />
                        <div>
                            <p class="text-xs font-bold" style="color: var(--gl-text-primary)">Rephrase questions with AI</p>
                            <p class="text-[11px] leading-relaxed" style="color: var(--gl-text-muted)">
                                Generates fresh phrasing for each question while preserving correct answers, scoring points, options, and rubric dimensions.
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Compile Action Buttons -->
                <div class="mt-5 flex items-center justify-between gap-3 pt-2 border-t border-[var(--gl-border)]">
                    <span class="text-xs font-medium" style="color: var(--gl-text-muted)">
                        {{ totalSelectedActivities }} activit{{ totalSelectedActivities === 1 ? 'y' : 'ies' }} selected
                    </span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="showCompile = false" class="rounded-full px-4 py-2 text-xs font-bold" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);">
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="executeCompile"
                            :disabled="isCompiling || totalSelectedActivities === 0"
                            class="inline-flex items-center gap-1.5 rounded-full px-5 py-2 text-xs font-bold text-white transition-all hover:scale-105 disabled:opacity-50"
                            style="background: linear-gradient(135deg, #7C3AED, #6366F1); box-shadow: 0 0 14px rgba(124, 58, 237, 0.35);"
                        >
                            <LoaderCircle v-if="isCompiling" class="h-3.5 w-3.5 animate-spin" />
                            <Sparkles v-else class="h-3.5 w-3.5" />
                            <span>{{ isCompiling ? (rephraseWithAi ? 'Rephrasing with AI...' : 'Compiling...') : 'Compile into Exam' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { Plus, Eye, Trash2, UploadCloud, FileQuestion, FileText, ClipboardCheck, Layers, X, Check, RefreshCw, Search, Ellipsis, Pencil, Users, Sparkles, LoaderCircle } from '@lucide/vue';

import { ref, computed, watch } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';

const props = defineProps<{
    exams: any[];
    isSuperadmin?: boolean;
    teachers?: any[];
    gradeLevels: any[];
    quarters?: any[];
    schoolYears?: any[];
    defaultSchoolYearId?: number | null;
    teacherQuizzes?: any[];
    teacherSeatworks?: any[];
    teacherPracticals?: any[];
}>();

const showCompile = ref(false);
const activeCompileTab = ref<'quizzes' | 'seatworks' | 'practicals'>('quizzes');
const selectedQuizIds = ref<number[]>([]);
const selectedSeatworkIds = ref<number[]>([]);
const selectedPracticalIds = ref<number[]>([]);
const rephraseWithAi = ref(true);
const isCompiling = ref(false);

const totalSelectedActivities = computed(
    () => selectedQuizIds.value.length + selectedSeatworkIds.value.length + selectedPracticalIds.value.length,
);

const compileTabs = computed(() => [
    { id: 'quizzes', label: 'Quizzes', count: props.teacherQuizzes?.length || 0 },
    { id: 'seatworks', label: 'Seatworks', count: props.teacherSeatworks?.length || 0 },
    { id: 'practicals', label: 'Practicals', count: props.teacherPracticals?.length || 0 },
]);

function openCompileModal() {
    selectedQuizIds.value = [];
    selectedSeatworkIds.value = [];
    selectedPracticalIds.value = [];
    showCompile.value = true;
}

async function executeCompile() {
    if (isCompiling.value || totalSelectedActivities.value === 0) {
return;
}

    isCompiling.value = true;

    try {
        const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content;
        const res = await fetch('/teacher/exams/compile-activities', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf || '',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                quiz_ids: selectedQuizIds.value,
                seatwork_ids: selectedSeatworkIds.value,
                practical_ids: selectedPracticalIds.value,
                rephrase: rephraseWithAi.value,
            }),
        });

        const data = await res.json();

        if (data.ok && Array.isArray(data.sections)) {
            if (!showCreate.value) {
                openCreate();
            }

            form.sections.push(...data.sections);

            if (!form.title) {
                form.title = data.suggested_title || 'Compiled Assessment Exam';
            }

            if (data.grade_level_ids?.length) {
                form.grade_level_ids = Array.from(new Set([...form.grade_level_ids, ...data.grade_level_ids]));
            }

            if (!form.time_limit_minutes && data.suggested_time_limit) {
                form.time_limit_minutes = data.suggested_time_limit;
            }

            showCompile.value = false;
        } else {
            alert(data.message || 'Failed to compile activities.');
        }
    } catch (e: any) {
        alert('Failed to compile activities: ' + (e?.message || 'Network error'));
    } finally {
        isCompiling.value = false;
    }
}

const localExams = ref<any[]>([...(props.exams || [])]);

watch(
    () => props.exams,
    (val) => {
        localExams.value = [...(val || [])];
    },
    { deep: true },
);

const page = usePage();
const flash = computed(() => (page.props as any).flash);
const showCreate = ref(false);

function openCreate() {
    form.reset();
    form.clearErrors();
    form.grade_level_ids = [];
    form.school_year_id = props.defaultSchoolYearId ?? null;
    form.sections = [];
    form.transform((data) => data);
    showCreate.value = true;
}

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

const list = computed(() => localExams.value ?? []);

const filtered = computed(() => {
    const q = query.value.trim().toLowerCase();

    return list.value.filter((item: any) => {
        if (statusTab.value !== 'all' && statusLabel(item) !== statusTab.value) {
return false;
}

        if (quarterFilter.value !== '' && Number(item.quarter_id) !== Number(quarterFilter.value)) {
return false;
}

        if (q && !(item.title || '').toLowerCase().includes(q)) {
return false;
}

        return true;
    });
});

const publishedCount = computed(
    () => list.value.filter((i: any) => statusLabel(i) === 'published').length,
);
const draftCount = computed(
    () => list.value.filter((i: any) => statusLabel(i) === 'draft').length,
);
const totalAttempts = computed(() =>
    list.value.reduce((a: number, i: any) => a + (i.attempts_count ?? 0), 0),
);

function goView(item: any) {
    openMenu.value = null;
    router.get(`/teacher/exams/${item.id}`);
}

function targetLabel(item: any): string {
    const grades = item.grade_levels
        ? item.grade_levels.map((g: any) => g.name).join(', ')
        : (item.grade ?? '');

    return (grades || '').trim() || '—';
}

interface Section {
    title: string;
    section_type: 'questions' | 'practical';
    instructions: string;
    questions: Question[];
    criteria: Criterion[];
}

interface Question {
    question_text: string;
    type: string;
    points: number;
    options: { option_text: string; is_correct: boolean }[];
    matching_pairs: { left_text: string; right_text: string }[];
    correct_answer: string;
    enum_items: string[];
    criteria?: string;
}

interface Criterion {
    criterion_name: string;
    description: string;
    max_points: number;
}

function blankSection(type: 'questions' | 'practical'): Section {
    return {
        title: '',
        section_type: type,
        instructions: '',
        questions: type === 'questions' ? [blankQuestion()] : [],
        criteria: type === 'practical' ? [blankCriterion()] : [],
    };
}

function blankQuestion(): Question {
    return {
        question_text: '',
        type: 'multiple_choice',
        points: 1,
        options: [
            { option_text: '', is_correct: true },
            { option_text: '', is_correct: false },
        ],
        matching_pairs: [
            { left_text: '', right_text: '' },
            { left_text: '', right_text: '' },
            { left_text: '', right_text: '' },
        ],
        correct_answer: 'true',
        enum_items: [''],
        criteria: '',
    };
}

function blankCriterion(): Criterion {
    return { criterion_name: '', description: '', max_points: 10 };
}

const form = useForm({
    title: '',
    instructions: '',
    time_limit_minutes: '',
    teacher_id: null as number | null,
    school_year_id: props.defaultSchoolYearId ?? null,
    quarter_id: null as number | null,
    grade_level_ids: [] as number[],
    sections: [] as any[],
});

function addSection(type: 'questions' | 'practical') {
    form.sections.push(blankSection(type));
}

function addQuestion(si: number) {
    form.sections[si].questions.push(blankQuestion());
}

function addCriterion(si: number) {
    form.sections[si].criteria.push(blankCriterion());
}

const selectedGradeId = computed<number | null>({
    get: () => form.grade_level_ids[0] ?? null,
    set: (value) => {
        form.grade_level_ids = value ? [Number(value)] : [];

        if (value) {
form.clearErrors('grade_level_ids');
}
    },
});

function setCorrectOption(questions: Question[], qi: number, oi: number) {
    questions[qi].options.forEach((o, i) => {
        o.is_correct = i === oi;
    });
}

function submitForm() {
    if (!form.grade_level_ids.length) {
        form.setError('grade_level_ids', 'Please select at least one grade level.');

        return;
    }

    const payload: any = {
        title: form.title,
        grade_level_ids: form.grade_level_ids,
        instructions: form.instructions,
        time_limit_minutes: form.time_limit_minutes || null,
        teacher_id: form.teacher_id || null,
        school_year_id: form.school_year_id || null,
        quarter_id: form.quarter_id || null,
        sections: form.sections.map((sec) => {
            const base: any = {
                title: sec.title,
                section_type: sec.section_type,
                instructions: sec.instructions || null,
            };

            if (sec.section_type === 'questions') {
                base.questions = sec.questions.map((q) => {
                    const qb: any = {
                        question_text: q.question_text,
                        type: q.type,
                        points: q.points || 1,
                    };

                    if (q.type === 'multiple_choice') {
                        qb.options = q.options;
                    }

                    if (q.type === 'true_false') {
                        qb.options = [
                            {
                                option_text: 'True',
                                is_correct: q.correct_answer === 'true',
                            },
                            {
                                option_text: 'False',
                                is_correct: q.correct_answer === 'false',
                            },
                        ];
                    }

                    if (q.type === 'matching') {
                        qb.matching_pairs = q.matching_pairs;
                    }

                    if (q.type === 'identification') {
                        qb.correct_answer = q.correct_answer;
                        qb.options = [
                            {
                                option_text: q.correct_answer,
                                is_correct: true,
                            },
                        ];
                    }

                    if (q.type === 'enumeration') {
                        qb.enum_items = q.enum_items;
                        qb.options = (q.enum_items || [])
                            .filter((i: string) => i && i.trim())
                            .map((i: string) => ({
                                option_text: i.trim(),
                                is_correct: true,
                            }));
                    }

                    if (q.type === 'essay') {
                        qb.criteria = q.criteria || null;
                    }

                    return qb;
                });
            }

            if (sec.section_type === 'practical') {
                base.criteria = sec.criteria;
            }

            return base;
        }),
    };

    form.transform(() => payload).post('/teacher/exams', {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            form.reset();
            form.clearErrors();
            form.grade_level_ids = [];
            form.school_year_id = props.defaultSchoolYearId ?? null;
            form.sections = [];
        },
        onFinish: () => {
            form.transform((data) => data);
        },
    });
}

function togglePublish(e: any) {
    const prev = e.is_published;
    e.is_published = !e.is_published;
    router.patch(
        `/teacher/exams/${e.id}/publish`,
        {},
        {
            preserveScroll: true,
            onError: () => {
                e.is_published = prev;
            },
        },
    );
}

function isClosed(e: any): boolean {
    if (!e.closes_at) {
return false;
}

    return new Date(e.closes_at) < new Date();
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

function reopen(e: any) {
    const prev = e.closes_at;
    e.closes_at = null;
    router.patch(
        `/teacher/exams/${e.id}/reopen`,
        {},
        {
            preserveScroll: true,
            onError: () => {
                e.closes_at = prev;
            },
        },
    );
}

function destroyE(e: any) {
    if (!confirm(`Delete "${e.title}"?`)) {
return;
}

    openMenu.value = null;
    const backup = [...localExams.value];
    localExams.value = localExams.value.filter((item: any) => item.id !== e.id);
    router.delete(`/teacher/exams/${e.id}`, {
        preserveScroll: true,
        onError: () => {
            localExams.value = backup;
        },
    });
}
</script>
