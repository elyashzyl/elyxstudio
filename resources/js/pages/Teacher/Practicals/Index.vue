<template>
    <Head title="Practicals" />

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
                Hands-on
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Practicals
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ localPracticals.length }} practicals ·
                {{ totalAttempts }} attempts
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
                href="/teacher/ai-creator?activity=practical"
                class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold text-white transition-all hover:scale-105 shadow-sm"
                style="
                    background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary));
                    box-shadow: 0 4px 14px var(--gl-primary-glow);
                "
                title="Create practical using AI file upload"
            >
                <Sparkles class="h-4 w-4" :stroke-width="2.2" />
                <span>AI File Creator</span>
            </Link>
            <button
                @click="openCreate"
                class="flex h-10 w-10 items-center justify-center rounded-xl text-white transition-all hover:scale-105"
                style="
                    background: linear-gradient(135deg, #ff6a45, #f94f2b);
                    box-shadow: 0 4px 14px rgba(249, 79, 43, 0.4);
                "
                title="New practical"
            >
                <Plus class="h-5 w-5" :stroke-width="2.5" />
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="mb-5 grid grid-cols-2 gap-4 lg:grid-cols-4">
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
                <FlaskConical
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-secondary)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-2xl font-black"
                style="color: var(--gl-text-primary)"
            >
                {{ localPracticals.length }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Total practicals
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
                <CheckCircle2
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
                <Zap
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-accent)"
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
                Total attempts
            </p>
        </div>
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
                {{ draftCount }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Drafts
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
            <FlaskConical
                class="h-6 w-6"
                style="color: var(--gl-text-muted)"
                :stroke-width="1.75"
            />
        </div>
        <p class="text-sm font-medium" style="color: var(--gl-text-secondary)">
            {{ localPracticals.length ? 'No matches' : 'No practicals yet' }}
        </p>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
            {{
                localPracticals.length
                    ? 'Try a different search or filter.'
                    : 'Create a practical activity with rubric criteria.'
            }}
        </p>
        <button
            v-if="!localPracticals.length"
            @click="openCreate"
            class="mt-4 flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
            style="
                background: linear-gradient(
                    135deg,
                    #ef4444,
                    var(--gl-secondary)
                );
                box-shadow: 0 0 12px rgba(239, 68, 68, 0.3);
            "
        >
            <Plus class="h-4 w-4" :stroke-width="2" /> New Practical
        </button>
    </div>

    <div v-else>
        <div
            class="mb-1 hidden grid-cols-[1fr_110px_90px_90px_40px] items-center gap-3 px-4 pb-2 text-[11px] font-semibold tracking-wider uppercase sm:grid"
            style="color: var(--gl-text-muted)"
        >
            <span>Title</span><span>Status</span
            ><span class="text-center">Criteria</span
            ><span class="text-center">Attempts</span><span></span>
        </div>
        <div class="space-y-2">
            <div
                v-for="p in filtered"
                :key="p.id"
                class="grid cursor-pointer grid-cols-[auto_1fr_40px] items-center gap-3 rounded-2xl px-4 py-3 transition-all sm:grid-cols-[1fr_110px_90px_90px_40px]"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
                @click="goView(p)"
            >
                <div class="min-w-0">
                    <p
                        class="truncate text-sm font-semibold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ p.title }}
                    </p>
                    <p
                        class="truncate text-[11px]"
                        style="color: var(--gl-text-muted)"
                    >
                        <span
                            v-if="p.quarter"
                            class="mr-1.5 inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                            style="background: rgba(249, 79, 43, 0.12); color: #f94f2b;"
                        >{{ p.quarter.code }}</span>
                        <span
                            v-if="p.school_year"
                            class="mr-1.5 inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                            style="background: rgba(124, 58, 237, 0.12); color: var(--gl-secondary);"
                        >{{ p.school_year.name }}</span>
                        <span v-if="isSuperadmin && p.teacher?.name"
                            >{{ p.teacher.name }} · </span
                        >{{ targetLabel(p) }} · {{ p.max_attempts }}/stu
                    </p>
                </div>
                <span class="hidden sm:block"
                    ><StatusBadge :status="statusLabel(p)"
                /></span>
                <span
                    class="hidden text-center text-sm font-semibold tabular-nums sm:block"
                    style="color: var(--gl-text-primary)"
                    >{{ p.criteria_count }}</span
                >
                <span
                    class="hidden text-center text-sm font-semibold tabular-nums sm:block"
                    style="color: var(--gl-text-primary)"
                    >{{ p.attempts_count }}</span
                >
                <div class="relative flex justify-end">
                    <button
                        @click.stop="openMenu = openMenu === p.id ? null : p.id"
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
                        v-if="openMenu === p.id"
                        class="absolute top-8 right-0 z-20 w-44 overflow-hidden rounded-xl py-1"
                        style="
                            background: var(--gl-surface);
                            border: 1px solid var(--gl-border);
                            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
                        "
                    >
                        <template v-if="isClosed(p)">
                            <Link
                                @click.stop="openMenu = null"
                                :href="`/teacher/practicals/${p.id}/edit`"
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
                                :href="`/teacher/practicals/${p.id}`"
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
                                    reopen(p);
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
                                    togglePublish(p);
                                "
                                class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                                style="color: var(--gl-text-primary)"
                            >
                                <UploadCloud
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-text-secondary)"
                                    :stroke-width="2"
                                />
                                {{ p.is_published ? 'Unpublish' : 'Publish' }}
                            </button>
                            <Link
                                @click.stop="openMenu = null"
                                :href="`/teacher/practicals/${p.id}/edit`"
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
                                :href="`/teacher/practicals/${p.id}`"
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
                                destroyP(p);
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
            {{ filtered.length }} of {{ localPracticals.length }} practicals
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
                            style="background: linear-gradient(135deg, #7C3AED, #6D28D9); box-shadow: 0 0 14px rgba(124,58,237,0.35);">
                            <Plus class="h-5 w-5 text-white" :stroke-width="2.5" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold tracking-tight" style="color: var(--gl-text-primary)">New practical</h3>
                            <p class="text-[11px]" style="color: var(--gl-text-muted)">Design it criterion by criterion</p>
                        </div>
                    </div>
                    <button @click="showCreate = false" class="flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-surface-2)]" style="color: var(--gl-text-muted);"><X class="h-4 w-4" :stroke-width="2.5" /></button>
                </div>

                <form @submit.prevent="submitForm" class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.5fr)]">
                    <div class="rounded-2xl p-4" style="background: rgba(124,58,237,0.05); border: 1px solid rgba(124,58,237,0.16);">
                        <div class="mb-3 flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(124,58,237,0.14);">
                                <FlaskConical class="h-3.5 w-3.5" style="color: var(--gl-secondary);" :stroke-width="2" />
                            </div>
                            <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Details</h4>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Title</label>
                                <input v-model="form.title" type="text" placeholder="e.g. Lab Activity 1" required
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                    style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Instructions (optional)</label>
                                <textarea v-model="form.instructions" rows="2" placeholder="Instructions for students"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                    style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);"></textarea>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Grade</label>
                                <select v-model="selectedGradeId" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" required>
                                    <option :value="null">Select a grade</option>
                                    <option v-for="gl in gradeLevels" :key="gl.id" :value="gl.id">{{ gl.name }}</option>
                                </select>
                                <p v-if="form.errors.grade_level_ids" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ form.errors.grade_level_ids }}</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Time (min)</label>
                                    <input v-model="form.time_limit_minutes" type="number" min="1"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Max score</label>
                                    <input v-model.number="form.max_score" type="number" min="1"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-bold tabular-nums outline-none"
                                        style="background: rgba(251,191,36,0.08); color: var(--gl-accent); border-color: rgba(251,191,36,0.25);" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">XP reward</label>
                                    <input v-model.number="form.xp_reward" type="number" min="1" max="500"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm font-bold tabular-nums outline-none"
                                        style="background: rgba(124,58,237,0.08); color: var(--gl-secondary); border-color: rgba(124,58,237,0.25);" />
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Attempts</label>
                                    <input v-model.number="form.max_attempts" type="number" min="1" max="10"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                </div>
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
                            <div v-if="teachers?.length">
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

                    <div class="rounded-2xl p-4" style="background: rgba(251,191,36,0.05); border: 1px solid rgba(251,191,36,0.16);">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(251,191,36,0.14);">
                                    <Award class="h-3.5 w-3.5" style="color: var(--gl-accent);" :stroke-width="2" />
                                </div>
                                <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Rubric criteria</h4>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums" style="background: var(--gl-surface-2); color: var(--gl-text-muted);">{{ form.criteria.length }}</span>
                            </div>
                            <button type="button" @click="addCriterion" title="Add criterion"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-white transition-all hover:scale-105"
                                style="background: linear-gradient(135deg, var(--gl-accent), #F59E0B); box-shadow: 0 0 10px var(--gl-accent-glow);">
                                <Plus class="h-4 w-4" :stroke-width="2.5" />
                            </button>
                        </div>

                        <div v-for="(c, ci) in form.criteria" :key="ci" class="mb-2.5 rounded-2xl p-3.5" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <label class="mb-1.5 block text-xs font-bold" style="color: var(--gl-accent);">Criterion {{ ci + 1 }}</label>
                                    <input v-model="c.criterion_name" type="text" placeholder="e.g. Accuracy" required
                                        class="w-full rounded-xl border px-3 py-2 text-sm outline-none"
                                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <div class="w-[70px]">
                                        <label class="mb-1.5 block text-center text-[10px] font-bold" style="color: var(--gl-text-muted);">Max pts</label>
                                        <input v-model.number="c.max_points" type="number" min="1"
                                            class="w-full rounded-xl border px-2 py-2 text-center text-xs font-bold tabular-nums outline-none"
                                            style="background: rgba(251,191,36,0.08); color: var(--gl-accent); border-color: rgba(251,191,36,0.25);" />
                                    </div>
                                    <button v-if="form.criteria.length > 1" type="button" @click="form.criteria.splice(ci, 1)" title="Remove"
                                        class="mt-5 flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);">
                                        <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />

                                    </button>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Description (optional)</label>
                                <input v-model="c.description" type="text" placeholder="Describe what to assess"
                                    class="w-full rounded-xl border px-3 py-2 text-sm outline-none"
                                    style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" />

                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-1 lg:col-span-2">
                        <button type="button" @click="showCreate = false" class="rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02]" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="rounded-full px-5 py-2.5 text-xs font-bold text-white transition-all hover:scale-[1.02] disabled:opacity-50" style="background: linear-gradient(135deg, #7C3AED, #6D28D9); box-shadow: 0 0 14px rgba(124,58,237,0.35);">Create</button>

                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { Plus, Eye, Pencil, Trash2, UploadCloud, FlaskConical, X, Check, RefreshCw, CheckCircle2, Zap, ClipboardCheck, Search, Ellipsis, Users, Award, Sparkles } from '@lucide/vue';

import { computed, ref, watch } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';

const props = defineProps<{
    practicals: any[];
    isSuperadmin?: boolean;
    teachers?: any[];
    gradeLevels: any[];
    quarters?: any[];
    schoolYears?: any[];
    defaultSchoolYearId?: number | null;
}>();

const localPracticals = ref<any[]>([...(props.practicals || [])]);

watch(
    () => props.practicals,
    (val) => {
        localPracticals.value = [...(val || [])];
    },
    { deep: true },
);

const publishedCount = computed(
    () => localPracticals.value.filter((p: any) => p.is_published).length,
);
const totalAttempts = computed(() =>
    localPracticals.value.reduce(
        (s: number, p: any) => s + (p.attempts_count ?? 0),
        0,
    ),
);
const draftCount = computed(
    () => localPracticals.value.filter((p: any) => !p.is_published).length,
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

    return localPracticals.value.filter((item: any) => {
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

function goView(item: any) {
    openMenu.value = null;
    router.get(`/teacher/practicals/${item.id}`);
}

function targetLabel(item: any): string {
    const grades = item.grade_levels
        ? item.grade_levels.map((g: any) => g.name).join(', ')
        : (item.grade ?? '');

    return (grades || '').trim() || '—';
}

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const showCreate = ref(false);

function openCreate() {
    form.reset();
    form.clearErrors();
    form.grade_level_ids = [];
    form.school_year_id = props.defaultSchoolYearId ?? null;
    form.criteria = [{ criterion_name: '', description: '', max_points: 10 }];
    showCreate.value = true;
}

const form = useForm({
    title: '',
    instructions: '',
    time_limit_minutes: '',
    max_score: 100,
    xp_reward: 10,
    max_attempts: 3,
    teacher_id: null as number | null,
    school_year_id: props.defaultSchoolYearId ?? null,
    quarter_id: null as number | null,
    grade_level_ids: [] as number[],
    criteria: [{ criterion_name: '', description: '', max_points: 10 }],
});

const selectedGradeId = computed<number | null>({
    get: () => form.grade_level_ids[0] ?? null,
    set: (value) => {
        form.grade_level_ids = value ? [Number(value)] : [];

        if (value) {
form.clearErrors('grade_level_ids');
}
    },
});

function addCriterion() {
    form.criteria.push({ criterion_name: '', description: '', max_points: 10 });
}

function submitForm() {
    if (!form.grade_level_ids.length) {
        form.setError('grade_level_ids', 'Please select at least one grade level.');

        return;
    }

    form.post('/teacher/practicals', {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            form.reset();
            form.clearErrors();
            form.grade_level_ids = [];
            form.school_year_id = props.defaultSchoolYearId ?? null;
            form.criteria = [{ criterion_name: '', description: '', max_points: 10 }];
        },
    });
}

function togglePublish(p: any) {
    const prev = p.is_published;
    p.is_published = !p.is_published;
    router.patch(
        `/teacher/practicals/${p.id}/publish`,
        {},
        {
            preserveScroll: true,
            onError: () => {
                p.is_published = prev;
            },
        },
    );
}

function isClosed(p: any): boolean {
    if (!p.closes_at) {
return false;
}

    return new Date(p.closes_at) < new Date();
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

function reopen(p: any) {
    const prev = p.closes_at;
    p.closes_at = null;
    router.patch(
        `/teacher/practicals/${p.id}/reopen`,
        {},
        {
            preserveScroll: true,
            onError: () => {
                p.closes_at = prev;
            },
        },
    );
}

function destroyP(p: any) {
    if (!confirm(`Delete "${p.title}"?`)) {
return;
}

    openMenu.value = null;
    const backup = [...localPracticals.value];
    localPracticals.value = localPracticals.value.filter((item: any) => item.id !== p.id);
    router.delete(`/teacher/practicals/${p.id}`, {
        preserveScroll: true,
        onError: () => {
            localPracticals.value = backup;
        },
    });
}
</script>
