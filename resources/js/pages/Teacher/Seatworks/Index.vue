<template>
    <Head title="Seatworks" />

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
                Practice
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Seatworks
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                {{ localSeatworks.length }} seatworks · {{ totalAttempts }} attempts
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
                href="/teacher/ai-creator?activity=seatwork"
                class="flex items-center gap-1.5 rounded-xl px-3.5 py-2 text-xs font-bold text-white transition-all hover:scale-105 shadow-sm"
                style="
                    background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary));
                    box-shadow: 0 4px 14px var(--gl-primary-glow);
                "
                title="Create seatwork using AI file upload"
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
                title="New seatwork"
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
                background: rgba(16, 185, 129, 0.08);
                border: 1px solid rgba(16, 185, 129, 0.18);
            "
        >
            <div
                class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                style="background: rgba(16, 185, 129, 0.16)"
            >
                <ClipboardCheck
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-success)"
                    :stroke-width="2"
                />
            </div>
            <p
                class="text-xl font-extrabold tabular-nums"
                style="color: var(--gl-text-primary)"
            >
                {{ localSeatworks.length }}
            </p>
            <p class="text-xs" style="color: var(--gl-text-secondary)">
                Total seatworks
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
                <UploadCloud
                    class="h-[18px] w-[18px]"
                    style="color: var(--gl-primary)"
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
            <ClipboardCheck
                class="h-6 w-6"
                style="color: var(--gl-text-muted)"
                :stroke-width="1.75"
            />
        </div>
        <p class="text-sm font-medium" style="color: var(--gl-text-secondary)">
            {{ localSeatworks.length ? 'No matches' : 'No seatworks yet' }}
        </p>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
            {{
                localSeatworks.length
                    ? 'Try a different search or filter.'
                    : 'Create your first seatwork activity.'
            }}
        </p>
        <button
            v-if="!localSeatworks.length"
            @click="openCreate"
            class="mt-4 flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
            style="
                background: linear-gradient(
                    135deg,
                    #10b981,
                    var(--gl-secondary)
                );
                box-shadow: 0 0 12px rgba(16, 185, 129, 0.3);
            "
        >
            <Plus class="h-4 w-4" :stroke-width="2" /> New Seatwork
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
                v-for="sw in filtered"
                :key="sw.id"
                class="grid cursor-pointer grid-cols-[auto_1fr_40px] items-center gap-3 rounded-2xl px-4 py-3 transition-all sm:grid-cols-[1fr_110px_90px_90px_40px]"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
                @click="goView(sw)"
            >
                <div class="min-w-0">
                    <p
                        class="truncate text-sm font-semibold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ sw.title }}
                    </p>
                    <p
                        class="truncate text-[11px]"
                        style="color: var(--gl-text-muted)"
                    >
                        <span
                            v-if="sw.quarter"
                            class="mr-1.5 inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                            style="background: rgba(249, 79, 43, 0.12); color: #f94f2b;"
                        >{{ sw.quarter.code }}</span>
                        <span
                            v-if="sw.school_year"
                            class="mr-1.5 inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-bold"
                            style="background: rgba(16, 185, 129, 0.12); color: var(--gl-success);"
                        >{{ sw.school_year.name }}</span>
                        <span v-if="isSuperadmin && sw.teacher?.name"
                            >{{ sw.teacher.name }} · </span
                        >{{ targetLabel(sw) }}
                    </p>
                </div>
                <span class="hidden sm:block"
                    ><StatusBadge :status="statusLabel(sw)"
                /></span>
                <span
                    class="hidden text-center text-sm font-semibold tabular-nums sm:block"
                    style="color: var(--gl-text-primary)"
                    >{{ sw.questions_count }}</span
                >
                <span
                    class="hidden text-center text-sm font-semibold tabular-nums sm:block"
                    style="color: var(--gl-text-primary)"
                    >{{ sw.attempts_count }}</span
                >
                <div class="relative flex justify-end">
                    <button
                        @click.stop="
                            openMenu = openMenu === sw.id ? null : sw.id
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
                        v-if="openMenu === sw.id"
                        class="absolute top-8 right-0 z-20 w-44 overflow-hidden rounded-xl py-1"
                        style="
                            background: var(--gl-surface);
                            border: 1px solid var(--gl-border);
                            box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
                        "
                    >
                        <template v-if="isClosed(sw)">
                            <Link
                                @click.stop="openMenu = null"
                                :href="`/teacher/seatworks/${sw.id}/edit`"
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
                                :href="`/teacher/seatworks/${sw.id}`"
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
                                    reopen(sw);
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
                                    togglePublish(sw);
                                "
                                class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)]"
                                style="color: var(--gl-text-primary)"
                            >
                                <UploadCloud
                                    class="h-3.5 w-3.5"
                                    style="color: var(--gl-text-secondary)"
                                    :stroke-width="2"
                                />
                                {{ sw.is_published ? 'Unpublish' : 'Publish' }}
                            </button>
                            <Link
                                @click.stop="openMenu = null"
                                :href="`/teacher/seatworks/${sw.id}/edit`"
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
                                :href="`/teacher/seatworks/${sw.id}`"
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
                                destroySw(sw);
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
            {{ filtered.length }} of {{ localSeatworks.length }} seatworks
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
                            style="background: linear-gradient(135deg, #10B981, #059669); box-shadow: 0 0 14px rgba(16,185,129,0.35);">
                            <Plus class="h-5 w-5 text-white" :stroke-width="2.5" />
                        </div>
                        <div>
                            <h3 class="text-base font-extrabold tracking-tight" style="color: var(--gl-text-primary)">New seatwork</h3>
                            <p class="text-[11px]" style="color: var(--gl-text-muted)">Build it question by question</p>
                        </div>
                    </div>
                    <button @click="showCreate = false" class="flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-surface-2)]" style="color: var(--gl-text-muted);"><X class="h-4 w-4" :stroke-width="2.5" /></button>
                </div>

                <form @submit.prevent="submitForm" class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(280px,0.8fr)_minmax(0,1.5fr)]">
                    <div class="rounded-2xl p-4" style="background: rgba(16,185,129,0.05); border: 1px solid rgba(16,185,129,0.16);">
                        <div class="mb-3 flex items-center gap-2">
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(16,185,129,0.14);">
                                <ClipboardList class="h-3.5 w-3.5" style="color: var(--gl-success);" :stroke-width="2" />
                            </div>
                            <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Details</h4>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Title</label>
                                <input v-model="form.title" type="text" placeholder="e.g. Chapter 1 Seatwork" required
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                    style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Instructions (optional)</label>
                                <textarea v-model="form.instructions" rows="2" placeholder="Instructions for students"
                                    class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                    style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);"></textarea>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Time limit (minutes, optional)</label>
                                    <input v-model="form.time_limit_minutes" type="number" min="1" placeholder="e.g. 20"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                </div>
                                <div v-if="props.schoolYears?.length">
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">School year</label>
                                    <select v-model="form.school_year_id"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" required>
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
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Allowed Attempts</label>
                                    <select v-model.number="form.max_attempts"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);">
                                        <option :value="1">1 Attempt (Default)</option>
                                        <option :value="2">2 Attempts</option>
                                        <option :value="3">3 Attempts</option>
                                        <option :value="5">5 Attempts</option>
                                        <option :value="10">10 Attempts</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Recorded Grade Policy</label>
                                    <select v-model="form.grading_policy"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);">
                                        <option value="first">1st Attempt</option>
                                        <option value="second">2nd Attempt</option>
                                        <option value="third">3rd Attempt</option>
                                        <option value="highest">Highest Score</option>
                                        <option value="latest">Latest Attempt</option>
                                        <option value="manual">Manual Choice</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Grade</label>
                                <select v-model="selectedGradeId" class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none" style="background: var(--gl-surface); color: var(--gl-text-primary); border-color: var(--gl-border);" required>
                                    <option :value="null">Select a grade</option>
                                    <option v-for="gl in gradeLevels" :key="gl.id" :value="gl.id">{{ gl.name }}</option>
                                </select>
                                <p v-if="form.errors.grade_level_ids" class="mt-1 text-xs" style="color: var(--gl-danger)">{{ form.errors.grade_level_ids }}</p>
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

                    <div class="rounded-2xl p-4" style="background: rgba(124,58,237,0.05); border: 1px solid rgba(124,58,237,0.16);">
                        <div class="mb-3 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="flex h-7 w-7 items-center justify-center rounded-lg" style="background: rgba(124,58,237,0.14);">
                                    <FileQuestion class="h-3.5 w-3.5" style="color: var(--gl-secondary);" :stroke-width="2" />
                                </div>
                                <h4 class="text-sm font-bold" style="color: var(--gl-text-primary)">Questions</h4>
                                <span class="rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums" style="background: var(--gl-surface-2); color: var(--gl-text-muted);">{{ form.questions.length }}</span>
                            </div>
                            <button type="button" @click="addQ" title="Add question"
                                class="flex h-8 w-8 items-center justify-center rounded-full text-white transition-all hover:scale-105"
                                style="background: linear-gradient(135deg, var(--gl-secondary), var(--gl-primary)); box-shadow: 0 0 10px var(--gl-secondary-glow);">
                                <Plus class="h-4 w-4" :stroke-width="2.5" />
                            </button>
                        </div>

                        <div v-for="(q, qi) in form.questions" :key="qi" class="mb-3 rounded-2xl p-4" style="background: var(--gl-surface); border: 1px solid var(--gl-border);">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <label class="mb-1.5 block text-xs font-bold" style="color: var(--gl-success);">Question {{ qi + 1 }}</label>
                                    <input v-model="q.question_text" type="text" placeholder="Enter your question" required
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                </div>
                                <div class="flex shrink-0 items-center gap-1.5">
                                    <select v-model="q.type" @change="onTypeChange(q)"
                                        class="rounded-xl border px-2 py-2 text-xs font-semibold outline-none"
                                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);">
                                        <option value="identification">Identification</option>
                                        <option value="enumeration">Enumeration</option>
                                        <option value="true_false">True/False</option>
                                        <option value="multiple_choice">Multiple Choice</option>
                                        <option value="matching">Matching Type</option>
                                    </select>
                                    <input v-model.number="q.points" type="number" min="1" title="Points"
                                        class="w-14 rounded-xl border px-2 py-2 text-center text-xs font-bold tabular-nums outline-none"
                                        style="background: rgba(251,191,36,0.1); color: var(--gl-accent); border-color: rgba(251,191,36,0.25);" />
                                    <button v-if="form.questions.length > 1" type="button" @click="removeQ(qi)" title="Remove"
                                        class="flex h-8 w-8 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);">
                                        <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />

                                    </button>
                                </div>
                            </div>

                            <template v-if="q.type === 'identification'">
                                <div>
                                    <label class="mb-1.5 block text-xs font-semibold" style="color: var(--gl-text-secondary);">Correct answer</label>
                                    <input v-model="q.correct_answer" type="text" placeholder="Expected answer"
                                        class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                                        style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" />

                                </div>
                            </template>

                            <template v-if="q.type === 'enumeration'">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-semibold" style="color: var(--gl-text-secondary);">Expected answers</label>
                                        <button type="button" @click="q.enum_items.push('')" class="text-xs font-bold transition-colors hover:opacity-80" style="color: var(--gl-primary);">+ Add item</button>
                                    </div>
                                    <div v-for="(item, ei) in q.enum_items" :key="ei" class="flex items-center gap-2">
                                        <span class="text-xs font-bold tabular-nums" style="color: var(--gl-text-muted)">{{ ei + 1 }}.</span>
                                        <input v-model="q.enum_items[ei]" type="text" :placeholder="'Answer ' + (ei + 1)"
                                            class="flex-1 rounded-xl border px-3 py-2 text-sm outline-none"
                                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                        <button v-if="q.enum_items.length > 1" type="button" @click="q.enum_items.splice(ei, 1)"
                                            class="rounded-md p-1 transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);"><X class="h-3 w-3" :stroke-width="2" /></button>

                                    </div>
                                </div>
                            </template>

                            <template v-if="q.type === 'true_false' || q.type === 'multiple_choice'">
                                <div class="space-y-1.5">
                                    <div v-for="(opt, oi) in q.options" :key="oi" class="flex items-center gap-2 rounded-xl px-2.5 py-1.5 transition-colors" :style="opt.is_correct ? { background: 'rgba(16,185,129,0.08)' } : {}">
                                        <input :checked="opt.is_correct" @change="setCorrect(q, oi)" type="radio" :name="'opt_' + qi" class="h-4 w-4 shrink-0" style="accent-color: var(--gl-success);" title="Mark correct" />
                                        <input v-model="opt.option_text" type="text"
                                            :placeholder="q.type === 'true_false' ? (oi === 0 ? 'True' : 'False') : 'Option ' + (oi + 1)"
                                            :readonly="q.type === 'true_false'" required
                                            class="flex-1 rounded-lg border px-3 py-2 text-sm outline-none"
                                            :style="opt.is_correct ? 'background: var(--gl-surface); color: var(--gl-text-primary); border-color: rgba(16,185,129,0.3);' : 'background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);'" />
                                        <button v-if="q.type === 'multiple_choice' && q.options.length > 2" type="button" @click="q.options.splice(oi, 1)"
                                            class="rounded-md p-1 transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);"><X class="h-3 w-3" :stroke-width="2" /></button>
                                    </div>
                                    <button v-if="q.type === 'multiple_choice'" type="button" @click="q.options.push({ option_text: '', is_correct: false })" class="mt-1 text-xs font-bold transition-colors hover:opacity-80" style="color: var(--gl-primary);">+ Add option</button>

                                </div>
                            </template>

                            <template v-if="q.type === 'matching'">
                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <label class="text-xs font-semibold" style="color: var(--gl-text-secondary);">Matching pairs</label>
                                        <button type="button" @click="q.matching_pairs.push({ left_text: '', right_text: '' })" class="text-xs font-bold transition-colors hover:opacity-80" style="color: var(--gl-primary);">+ Add pair</button>
                                    </div>
                                    <div v-for="(pair, pi) in q.matching_pairs" :key="pi" class="flex items-center gap-2">
                                        <input v-model="pair.left_text" type="text" placeholder="Left item"
                                            class="flex-1 rounded-xl border px-3 py-2 text-sm outline-none"
                                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                        <span class="text-xs font-bold" style="color: var(--gl-text-muted)">→</span>
                                        <input v-model="pair.right_text" type="text" placeholder="Right item"
                                            class="flex-1 rounded-xl border px-3 py-2 text-sm outline-none"
                                            style="background: var(--gl-surface-2); color: var(--gl-text-primary); border-color: var(--gl-border);" />
                                        <button v-if="q.matching_pairs.length > 2" type="button" @click="q.matching_pairs.splice(pi, 1)"
                                            class="rounded-md p-1 transition-colors hover:bg-[var(--gl-danger-bg)]" style="color: var(--gl-danger);"><X class="h-3 w-3" :stroke-width="2" /></button>

                                    </div>
                                </div>
                            </template>

                            <template v-if="q.type === 'essay'">
                                <div class="space-y-1.5">
                                    <label
                                        class="field-label flex items-center justify-between text-xs"
                                    >
                                        <span
                                            >Grading Criteria (Evaluated by
                                            AI)</span
                                        >
                                        <span
                                            class="text-[11px] font-normal"
                                            style="color: var(--gl-text-muted)"
                                            >Teacher rubric guidelines</span
                                        >
                                    </label>
                                    <textarea
                                        v-model="q.criteria"
                                        rows="3"
                                        class="input-field"
                                        placeholder="e.g. 1. Accurate explanation of core concepts (4 pts). 2. Supporting examples provided (3 pts). 3. Logical structure and clarity (3 pts)."
                                        required
                                    ></textarea>
                                    <p
                                        class="text-[11px]"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        AI will check student submissions
                                        automatically against your criteria.
                                    </p>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-1 lg:col-span-2">
                        <button type="button" @click="showCreate = false" class="rounded-full px-5 py-2.5 text-xs font-bold transition-all hover:scale-[1.02]" style="background: var(--gl-surface-2); color: var(--gl-text-primary); border: 1px solid var(--gl-border);">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="rounded-full px-5 py-2.5 text-xs font-bold text-white transition-all hover:scale-[1.02] disabled:opacity-50" style="background: linear-gradient(135deg, #10B981, #059669); box-shadow: 0 0 14px rgba(16,185,129,0.35);">Create</button>

                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
import { Plus, Eye, Pencil, Trash2, UploadCloud, ClipboardCheck, ClipboardList, FileQuestion, Check, X, RefreshCw, Search, Ellipsis, Users, Sparkles } from '@lucide/vue';

import { ref, computed, watch } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';

const props = defineProps<{
    seatworks: any[];
    isSuperadmin?: boolean;
    teachers?: any[];
    gradeLevels: any[];
    quarters?: any[];
    schoolYears?: any[];
    defaultSchoolYearId?: number | null;
}>();

const localSeatworks = ref<any[]>([...(props.seatworks || [])]);

watch(
    () => props.seatworks,
    (val) => {
        localSeatworks.value = [...(val || [])];
    },
    { deep: true },
);

const page = usePage();
const flash = computed(() => (page.props as any).flash);

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

    return (localSeatworks.value ?? []).filter((item: any) => {
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
    () =>
        (localSeatworks.value ?? []).filter(
            (i: any) => statusLabel(i) === 'published',
        ).length,
);
const draftCount = computed(
    () =>
        (localSeatworks.value ?? []).filter((i: any) => statusLabel(i) === 'draft')
            .length,
);
const totalAttempts = computed(() =>
    (localSeatworks.value ?? []).reduce(
        (a: number, i: any) => a + (i.attempts_count ?? 0),
        0,
    ),
);

function goView(item: any) {
    openMenu.value = null;
    router.get(`/teacher/seatworks/${item.id}`);
}

function targetLabel(item: any): string {
    const grades = item.grade_levels
        ? item.grade_levels.map((g: any) => g.name).join(', ')
        : (item.grade ?? '');

    return (grades || '').trim() || '—';
}

interface Opt {
    option_text: string;
    is_correct: boolean;
}
interface Q {
    question_text: string;
    type: string;
    points: number;
    options: Opt[];
    matching_pairs: { left_text: string; right_text: string }[];
    correct_answer: string;
    enum_items: string[];
    criteria?: string;
}

function blankQ(): Q {
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

function onTypeChange(q: Q) {
    if (q.type === 'true_false') {
        q.options = [
            { option_text: 'True', is_correct: true },
            { option_text: 'False', is_correct: false },
        ];
        q.matching_pairs = [];
        q.enum_items = [];
    } else if (q.type === 'multiple_choice') {
        q.options = [
            { option_text: '', is_correct: true },
            { option_text: '', is_correct: false },
            { option_text: '', is_correct: false },
            { option_text: '', is_correct: false },
        ];
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

function setCorrect(q: Q, oi: number) {
    q.options.forEach((o, i) => {
        o.is_correct = i === oi;
    });
}

const showCreate = ref(false);

const form = useForm({
    title: '',
    instructions: '',
    time_limit_minutes: '',
    max_attempts: 1,
    grading_policy: 'first',
    teacher_id: null as number | null,
    school_year_id: props.defaultSchoolYearId ?? null,
    quarter_id: null as number | null,
    grade_level_ids: [] as number[],
    questions: [blankQ()],
});

function openCreate() {
    form.reset();
    form.clearErrors();
    form.grade_level_ids = [];
    form.school_year_id = props.defaultSchoolYearId ?? null;
    form.questions = [blankQ()];
    showCreate.value = true;
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

function addQ() {
    form.questions.push(blankQ());
}
function removeQ(i: number) {
    form.questions.splice(i, 1);
}

function submitForm() {
    if (!form.grade_level_ids.length) {
        form.setError('grade_level_ids', 'Please select at least one grade level.');

        return;
    }

    form.questions.forEach((q) => {
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
        }
    });

    form.post('/teacher/seatworks', {
        preserveScroll: true,
        onSuccess: () => {
            showCreate.value = false;
            form.reset();
            form.clearErrors();
            form.grade_level_ids = [];
            form.school_year_id = props.defaultSchoolYearId ?? null;
            form.questions = [blankQ()];
        },
    });
}

function togglePublish(sw: any) {
    const prev = sw.is_published;
    sw.is_published = !sw.is_published;
    router.patch(
        `/teacher/seatworks/${sw.id}/publish`,
        {},
        {
            preserveScroll: true,
            onError: () => {
                sw.is_published = prev;
            },
        },
    );
}

function isClosed(sw: any): boolean {
    if (!sw.closes_at) {
return false;
}

    return new Date(sw.closes_at) < new Date();
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

function reopen(sw: any) {
    const prev = sw.closes_at;
    sw.closes_at = null;
    router.patch(
        `/teacher/seatworks/${sw.id}/reopen`,
        {},
        {
            preserveScroll: true,
            onError: () => {
                sw.closes_at = prev;
            },
        },
    );
}
function destroySw(sw: any) {
    if (!confirm(`Delete "${sw.title}"?`)) {
return;
}

    openMenu.value = null;
    const backup = [...localSeatworks.value];
    localSeatworks.value = localSeatworks.value.filter((item: any) => item.id !== sw.id);
    router.delete(`/teacher/seatworks/${sw.id}`, {
        preserveScroll: true,
        onError: () => {
            localSeatworks.value = backup;
        },
    });
}
</script>
