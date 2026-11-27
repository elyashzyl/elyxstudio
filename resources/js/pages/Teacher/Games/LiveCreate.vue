<template>
    <Head title="Create Live Arena" />

    <div class="mx-auto max-w-5xl space-y-6 pb-12">
        <!-- Top Navigation & Header -->
        <div class="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-5 shadow-sm">
            <div class="flex items-center gap-3">
                <Link
                    href="/teacher/games"
                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-[var(--gl-border)] transition-colors hover:bg-[var(--gl-surface-2)]"
                >
                    <ArrowLeft class="h-5 w-5" style="color: var(--gl-text-secondary)" />
                </Link>
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                            <Zap class="h-3 w-3" />
                            LIVE MULTIPLAYER ARENA
                        </span>
                        <span class="text-xs font-medium text-[var(--gl-text-muted)]">
                            First-to-answer bonus scoring
                        </span>
                    </div>
                    <h1 class="text-2xl font-black text-[var(--gl-text-primary)]">
                        Create Live Arena Match
                    </h1>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="submitCreateLive(false)"
                    :disabled="submitting || cards.length === 0 || !title.trim()"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-4 py-3 text-xs font-bold text-[var(--gl-text-primary)] transition-all hover:bg-[var(--gl-surface)] active:scale-95 disabled:pointer-events-none disabled:opacity-50"
                    title="Save live arena game to your library without opening a room yet"
                >
                    <Save class="h-4 w-4" />
                    <span>Save to Library</span>
                </button>
                <button
                    type="button"
                    @click="submitCreateLive(true)"
                    :disabled="submitting || cards.length === 0 || !title.trim()"
                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-emerald-600/30 transition-all hover:scale-105 hover:bg-emerald-500 active:scale-95 disabled:pointer-events-none disabled:opacity-50"
                >
                    <Play class="h-4 w-4 fill-current" />
                    <span>{{ submitting ? 'Launching Arena...' : 'Launch Live Arena' }}</span>
                </button>
            </div>
        </div>

        <!-- AI Generator Card -->
        <div class="relative overflow-hidden rounded-3xl border border-indigo-500/30 bg-gradient-to-br from-indigo-500/5 via-[var(--gl-surface)] to-purple-500/5 p-6 shadow-md sm:p-8">
            <div class="flex items-center gap-3">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-indigo-500/20 text-indigo-600 dark:text-indigo-400">
                    <Sparkles class="h-6 w-6" />
                </div>
                <div>
                    <h2 class="text-lg font-bold text-[var(--gl-text-primary)]">
                        AI Live Match Generator
                    </h2>
                    <p class="text-xs text-[var(--gl-text-secondary)]">
                        Generate live competition cards based on your uploaded teaching materials or custom lesson topics.
                    </p>
                </div>
            </div>

            <!-- Source Mode Selector -->
            <div class="mt-5 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="aiSource = 'material'"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all"
                    :class="[
                        aiSource === 'material'
                            ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                            : 'border border-[var(--gl-border)] bg-[var(--gl-surface-2)] text-[var(--gl-text-secondary)] hover:text-[var(--gl-text-primary)]'
                    ]"
                >
                    <BookOpen class="h-3.5 w-3.5" />
                    <span>Based on Teaching Material</span>
                    <span
                        v-if="materialsList.length > 0"
                        class="ml-1 rounded-full px-1.5 py-0.2 text-[10px] font-black"
                        :class="aiSource === 'material' ? 'bg-white/20 text-white' : 'bg-indigo-500/10 text-indigo-500'"
                    >
                        {{ materialsList.length }}
                    </span>
                </button>
                <button
                    type="button"
                    @click="aiSource = 'prompt'"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-xs font-bold transition-all"
                    :class="[
                        aiSource === 'prompt'
                            ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30'
                            : 'border border-[var(--gl-border)] bg-[var(--gl-surface-2)] text-[var(--gl-text-secondary)] hover:text-[var(--gl-text-primary)]'
                    ]"
                >
                    <Sparkles class="h-3.5 w-3.5" />
                    <span>Topic / Lesson Notes Prompt</span>
                </button>
            </div>

            <div class="mt-4 space-y-4">
                <!-- Material Mode Content -->
                <div v-if="aiSource === 'material'" class="space-y-3">
                    <div v-if="materialsList.length === 0" class="rounded-2xl border border-dashed border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-6 text-center space-y-2">
                        <BookOpen class="mx-auto h-8 w-8 text-[var(--gl-text-muted)]" />
                        <p class="text-xs font-bold text-[var(--gl-text-primary)]">
                            No Teaching Materials Uploaded Yet
                        </p>
                        <p class="text-xs text-[var(--gl-text-secondary)]">
                            Upload lesson slides (PPT, PPTX) or PDF notes in Teaching Materials, or use the Topic Prompt option.
                        </p>
                        <Link
                            href="/teacher/materials"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600/15 px-3.5 py-2 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-600/25 transition-colors"
                        >
                            <Plus class="h-3.5 w-3.5" />
                            <span>Go to Teaching Materials</span>
                        </Link>
                    </div>

                    <div v-else class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-xs font-bold text-[var(--gl-text-secondary)] uppercase tracking-wider">
                                Select Materials to Compile
                                <span class="ml-1 text-[var(--gl-text-muted)] font-normal">
                                    ({{ selectedMaterialIds.length }} of {{ materialsList.length }} selected)
                                </span>
                            </label>

                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    @click="selectAllMaterials"
                                    class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 hover:underline"
                                >
                                    Select All
                                </button>
                                <span class="text-xs text-[var(--gl-text-muted)]">·</span>
                                <button
                                    type="button"
                                    @click="clearSelectedMaterials"
                                    class="text-[11px] font-bold text-[var(--gl-text-muted)] hover:text-red-500 hover:underline"
                                >
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- Search filter if more than 3 materials -->
                        <div v-if="materialsList.length > 3" class="relative">
                            <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-[var(--gl-text-muted)]" />
                            <input
                                v-model="materialSearchQuery"
                                type="text"
                                placeholder="Search by title, subject, or filename..."
                                class="w-full rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] pl-9 pr-3 py-2 text-xs font-medium text-[var(--gl-text-primary)] outline-none focus:border-indigo-500"
                            />
                        </div>

                        <!-- Materials List / Grid -->
                        <div class="max-h-56 overflow-y-auto rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-2 space-y-1.5">
                            <div
                                v-for="m in filteredMaterials"
                                :key="m.id"
                                @click="toggleMaterial(m.id)"
                                class="flex items-center justify-between gap-3 rounded-xl p-2.5 transition-all cursor-pointer border select-none"
                                :class="[
                                    isMaterialSelected(m.id)
                                        ? 'border-indigo-500/40 bg-indigo-500/10 shadow-sm'
                                        : 'border-transparent hover:bg-[var(--gl-surface)] text-[var(--gl-text-secondary)]'
                                ]"
                            >
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border transition-colors"
                                        :class="[
                                            isMaterialSelected(m.id)
                                                ? 'border-indigo-600 bg-indigo-600 text-white'
                                                : 'border-[var(--gl-border)] bg-[var(--gl-surface)]'
                                        ]"
                                    >
                                        <Check v-if="isMaterialSelected(m.id)" class="h-3 w-3 stroke-[3]" />
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="truncate text-xs font-bold"
                                            :class="isMaterialSelected(m.id) ? 'text-indigo-600 dark:text-indigo-400' : 'text-[var(--gl-text-primary)]'"
                                        >
                                            {{ m.title }}
                                        </p>
                                        <p class="truncate text-[10px] text-[var(--gl-text-muted)]">
                                            {{ m.subject || 'General' }} · {{ m.gradeLevel?.name || 'All Grades' }} · {{ m.file_name }}
                                        </p>
                                    </div>
                                </div>
                                <span class="shrink-0 rounded-md bg-[var(--gl-surface)] px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wider text-[var(--gl-text-muted)] border border-[var(--gl-border)]">
                                    {{ m.file_type || 'file' }}
                                </span>
                            </div>
                            <div v-if="filteredMaterials.length === 0" class="py-4 text-center text-xs text-[var(--gl-text-muted)]">
                                No materials match "{{ materialSearchQuery }}"
                            </div>
                        </div>

                        <!-- Compiled Materials Preview Bar -->
                        <div v-if="selectedMaterials.length > 0" class="rounded-2xl border border-indigo-500/20 bg-indigo-500/5 p-3.5 space-y-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                    <Layers class="h-4 w-4" />
                                    <span>Compiled Dataset ({{ selectedMaterials.length }} {{ selectedMaterials.length === 1 ? 'material' : 'materials' }})</span>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                    <Zap class="h-3 w-3" /> Ready to Cross-Reference & Compile
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-1.5 pt-0.5">
                                <span
                                    v-for="m in selectedMaterials"
                                    :key="m.id"
                                    class="inline-flex items-center gap-1 rounded-lg border border-indigo-500/20 bg-[var(--gl-surface)] px-2 py-1 text-[11px] font-semibold text-[var(--gl-text-primary)]"
                                >
                                    <span class="max-w-[160px] truncate">{{ m.title }}</span>
                                    <button
                                        type="button"
                                        @click.stop="toggleMaterial(m.id)"
                                        class="text-[var(--gl-text-muted)] hover:text-red-500 ml-0.5"
                                    >
                                        <X class="h-3 w-3" />
                                    </button>
                                </span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-[var(--gl-text-secondary)] uppercase tracking-wider mb-1">
                                Specific Focus or Compilation Instructions (Optional)
                            </label>
                            <input
                                v-model="materialInstructions"
                                type="text"
                                placeholder="e.g. Combine definitions from both materials, focus on comparisons, or prioritize Chapter 2..."
                                class="w-full rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-3.5 py-2.5 text-xs font-medium text-[var(--gl-text-primary)] outline-none focus:border-indigo-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- Custom Prompt Mode Content -->
                <div v-else>
                    <label class="block text-xs font-bold text-[var(--gl-text-secondary)] uppercase tracking-wider mb-1.5">
                        Topic / Lesson Notes Prompt
                    </label>
                    <textarea
                        v-model="aiPrompt"
                        rows="3"
                        placeholder="e.g. Grade 10 Cell Biology: Mitochondria, Nucleus, Ribosomes, ATP production, and Cell Wall functions..."
                        class="w-full rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] p-4 text-sm font-medium text-[var(--gl-text-primary)] outline-none focus:border-indigo-500"
                    ></textarea>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-4 pt-1">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-xs font-semibold text-[var(--gl-text-muted)]">Cards to Generate:</span>
                        <div class="flex items-center gap-1.5">
                            <button
                                v-for="cnt in [3, 5, 8, 10, 15, 20]"
                                :key="cnt"
                                type="button"
                                @click="aiCount = cnt"
                                class="rounded-lg px-2.5 py-1 text-xs font-bold transition-colors"
                                :class="[
                                    aiCount === cnt
                                        ? 'bg-indigo-600 text-white'
                                        : 'bg-[var(--gl-surface-2)] text-[var(--gl-text-secondary)] hover:bg-indigo-500/10'
                                ]"
                            >
                                {{ cnt }}
                            </button>
                        </div>

                        <!-- Append Mode Toggle when cards already exist -->
                        <div v-if="cards.length > 0" class="flex items-center gap-1.5 ml-0 sm:ml-2">
                            <button
                                type="button"
                                @click="appendMode = !appendMode"
                                class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-bold transition-colors border"
                                :class="[
                                    appendMode
                                        ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-600'
                                        : 'border-[var(--gl-border)] bg-[var(--gl-surface-2)] text-[var(--gl-text-muted)] hover:text-[var(--gl-text-primary)]'
                                ]"
                                title="When enabled, newly generated cards are added to your existing questions instead of replacing them."
                            >
                                <span class="h-2 w-2 rounded-full" :class="appendMode ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                <span>{{ appendMode ? 'Append to Current Cards' : 'Replace Cards' }}</span>
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="generateWithAi"
                        :disabled="aiGenerating || (aiSource === 'material' ? selectedMaterialIds.length === 0 : !aiPrompt.trim())"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-bold text-white shadow transition-all hover:scale-105 hover:bg-indigo-500 active:scale-95 disabled:pointer-events-none disabled:opacity-50"
                    >
                        <LoaderCircle v-if="aiGenerating" class="h-4 w-4 animate-spin" />
                        <Sparkles v-else class="h-4 w-4" />
                        <span>
                            {{
                                aiGenerating
                                    ? (aiSource === 'material' ? `Compiling ${selectedMaterials.length} Materials & Generating...` : 'Generating Cards...')
                                    : (aiSource === 'material' ? (selectedMaterials.length > 1 ? `Compile ${selectedMaterials.length} Materials & Generate` : 'Generate from Material') : 'Generate with AI')
                            }}
                        </span>
                    </button>
                </div>

                <div v-if="aiError" class="rounded-xl border border-red-500/30 bg-red-500/10 p-3 text-xs font-semibold text-red-600">
                    {{ aiError }}
                </div>
                <div v-if="aiSuccess" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-600">
                    {{ aiSuccess }}
                </div>
            </div>
        </div>

        <!-- Arena Configuration Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left 2 Cols: Question Cards -->
            <div class="space-y-4 lg:col-span-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-[var(--gl-text-primary)]">
                            Arena Questions
                        </h2>
                        <span class="rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-bold text-emerald-600">
                            {{ cards.length }} {{ cards.length === 1 ? 'card' : 'cards' }}
                        </span>
                    </div>

                    <button
                        type="button"
                        @click="addCard"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface)] px-3.5 py-1.5 text-xs font-bold text-[var(--gl-text-primary)] transition-all hover:bg-[var(--gl-surface-2)]"
                    >
                        <Plus class="h-3.5 w-3.5" />
                        <span>Add Question</span>
                    </button>
                </div>

                <div v-if="cards.length === 0" class="rounded-3xl border border-dashed border-[var(--gl-border)] p-12 text-center">
                    <Gamepad2 class="mx-auto h-12 w-12 text-[var(--gl-text-muted)]" />
                    <h3 class="mt-3 text-base font-bold text-[var(--gl-text-primary)]">
                        No Questions Added Yet
                    </h3>
                    <p class="mt-1 text-xs text-[var(--gl-text-secondary)]">
                        Use the AI Generator above or click "Add Question" to build your live match.
                    </p>
                </div>

                <!-- Cards List -->
                <div v-else class="space-y-4">
                    <div
                        v-for="(card, idx) in cards"
                        :key="idx"
                        class="rounded-2xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-5 shadow-sm transition-all"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-extrabold text-white" :style="{ background: card.color || '#10b981' }">
                                {{ idx + 1 }}
                            </span>

                            <div class="flex items-center gap-2">
                                <!-- Color Tag Picker -->
                                <div class="flex items-center gap-1">
                                    <button
                                        v-for="c in ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899']"
                                        :key="c"
                                        type="button"
                                        @click="card.color = c"
                                        class="h-4 w-4 rounded-full transition-transform hover:scale-125"
                                        :style="{
                                            background: c,
                                            boxShadow: card.color === c ? '0 0 0 2px var(--gl-surface), 0 0 0 4px ' + c : 'none'
                                        }"
                                    ></button>
                                </div>

                                <button
                                    type="button"
                                    @click="removeCard(idx)"
                                    class="text-[var(--gl-text-muted)] hover:text-red-500"
                                    title="Delete Card"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Question Input -->
                        <div class="space-y-3">
                            <div>
                                <label class="block text-[11px] font-bold text-[var(--gl-text-muted)] uppercase tracking-wider mb-1">
                                    Question Prompt
                                </label>
                                <input
                                    v-model="card.question"
                                    type="text"
                                    placeholder="e.g. Which organelle generates the majority of chemical energy (ATP)?"
                                    class="w-full rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-3.5 py-2.5 text-xs font-semibold text-[var(--gl-text-primary)] outline-none focus:border-emerald-500"
                                />
                            </div>

                            <div>
                                <label class="block text-[11px] font-bold text-emerald-600 dark:text-emerald-400 uppercase tracking-wider mb-1">
                                    Correct Answer
                                </label>
                                <input
                                    v-model="card.answer"
                                    type="text"
                                    placeholder="e.g. Mitochondria"
                                    class="w-full rounded-xl border border-emerald-500/40 bg-emerald-500/5 px-3.5 py-2.5 text-xs font-bold text-[var(--gl-text-primary)] outline-none focus:border-emerald-500"
                                />
                            </div>

                            <!-- Options (for quiz / speedquiz) -->
                            <div v-if="gameType === 'speedquiz' || gameType === 'quiz' || (card.options && card.options.length)">
                                <label class="block text-[11px] font-bold text-[var(--gl-text-muted)] uppercase tracking-wider mb-1">
                                    Multiple Choice Options (4 Choices)
                                </label>
                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                    <input
                                        v-for="(opt, optIdx) in (card.options || ['', '', '', ''])"
                                        :key="optIdx"
                                        type="text"
                                        :value="opt"
                                        @input="updateCardOption(card, optIdx, ($event.target as HTMLInputElement).value)"
                                        :placeholder="`Option ${optIdx + 1}`"
                                        class="rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-3 py-2 text-xs font-medium text-[var(--gl-text-primary)] outline-none focus:border-indigo-500"
                                    />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Col: Game Settings -->
            <div class="space-y-5">
                <div class="rounded-3xl border border-[var(--gl-border)] bg-[var(--gl-surface)] p-6 shadow-sm space-y-4">
                    <h2 class="text-base font-bold text-[var(--gl-text-primary)]">
                        Match Settings
                    </h2>

                    <div>
                        <label class="block text-xs font-bold text-[var(--gl-text-secondary)] uppercase tracking-wider mb-1">
                            Match Title
                        </label>
                        <input
                            v-model="title"
                            type="text"
                            placeholder="e.g. Biology Cell Sprint"
                            class="w-full rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-3.5 py-2.5 text-xs font-bold text-[var(--gl-text-primary)] outline-none focus:border-emerald-500"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[var(--gl-text-secondary)] uppercase tracking-wider mb-1">
                            Subject
                        </label>
                        <select
                            v-model="subjectId"
                            class="w-full rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-3.5 py-2.5 text-xs font-semibold text-[var(--gl-text-primary)] outline-none focus:border-emerald-500"
                        >
                            <option :value="null">Select subject</option>
                            <option v-for="s in props.subjects || []" :key="s.id" :value="s.id">{{ s.name }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[var(--gl-text-secondary)] uppercase tracking-wider mb-1">
                            Target Grade
                        </label>
                        <select
                            v-model="gradeLevelId"
                            class="w-full rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-3.5 py-2.5 text-xs font-semibold text-[var(--gl-text-primary)] outline-none focus:border-emerald-500"
                        >
                            <option value="">All Grades</option>
                            <option v-for="g in gradeLevels" :key="g.id" :value="g.id">
                                {{ g.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[var(--gl-text-secondary)] uppercase tracking-wider mb-1">
                            Arena Mode
                        </label>
                        <select
                            v-model="gameType"
                            class="w-full rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-3.5 py-2.5 text-xs font-semibold text-[var(--gl-text-primary)] outline-none focus:border-emerald-500"
                        >
                            <option v-for="t in availableTypes" :key="t.value" :value="t.value">
                                {{ t.label }} - {{ t.desc }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-[var(--gl-text-secondary)] uppercase tracking-wider mb-1">
                            XP Reward (Per Winner)
                        </label>
                        <input
                            v-model.number="xpReward"
                            type="number"
                            min="10"
                            max="500"
                            class="w-full rounded-xl border border-[var(--gl-border)] bg-[var(--gl-surface-2)] px-3.5 py-2.5 text-xs font-mono font-bold text-[var(--gl-text-primary)] outline-none focus:border-emerald-500"
                        />
                    </div>
                </div>

                <!-- Launch Banner Box -->
                <div class="rounded-3xl border border-emerald-500/30 bg-emerald-500/10 p-6 text-center space-y-3">
                    <Trophy class="mx-auto h-8 w-8 text-emerald-600" />
                    <h3 class="text-sm font-bold text-[var(--gl-text-primary)]">
                        Ready to Start Match?
                    </h3>
                    <p class="text-xs text-[var(--gl-text-secondary)] leading-relaxed">
                        Launching will instantly generate your 6-digit room PIN and transition to the Live Host Screen.
                    </p>
                    <div class="space-y-2 pt-1">
                        <button
                            type="button"
                            @click="submitCreateLive(true)"
                            :disabled="submitting || cards.length === 0 || !title.trim()"
                            class="w-full rounded-xl bg-emerald-600 py-3 text-xs font-bold text-white shadow-md transition-all hover:bg-emerald-500 active:scale-95 disabled:opacity-50"
                        >
                            {{ submitting ? 'Launching...' : 'Launch Live Arena Now' }}
                        </button>
                        <button
                            type="button"
                            @click="submitCreateLive(false)"
                            :disabled="submitting || cards.length === 0 || !title.trim()"
                            class="w-full rounded-xl border border-emerald-500/30 bg-[var(--gl-surface)] py-2.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 transition-all hover:bg-emerald-500/10 active:scale-95 disabled:opacity-50"
                        >
                            Save to Library (Host Later)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft, Zap, Sparkles, Plus, Trash2,
    Play, Trophy, Gamepad2, LoaderCircle, BookOpen, FileText,
    Check, Search, Layers, X, Save
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    gradeLevels: any[];
    availableTypes: { value: string; label: string; desc: string }[];
    materials?: any[];
    initialMaterialId?: number | null;
    subjects?: any[];
}>();

const title = ref('');
const subject = ref('Science');
const subjectId = ref<number | null>(null);
const gradeLevelId = ref<number | ''>('');
const gameType = ref('speedquiz');
const xpReward = ref(50);

const materialsList = computed(() => props.materials || []);
const aiSource = ref<'material' | 'prompt'>('material');
const selectedMaterialIds = ref<number[]>([]);
const materialSearchQuery = ref('');
const materialInstructions = ref('');
const appendMode = ref(false);

const filteredMaterials = computed(() => {
    const q = materialSearchQuery.value.trim().toLowerCase();

    if (!q) {
return materialsList.value;
}

    return materialsList.value.filter((m: any) =>
        (m.title || '').toLowerCase().includes(q) ||
        (m.subject || '').toLowerCase().includes(q) ||
        (m.file_name || '').toLowerCase().includes(q)
    );
});

const selectedMaterials = computed(() => {
    return materialsList.value.filter((m: any) => selectedMaterialIds.value.includes(m.id));
});

function isMaterialSelected(id: number): boolean {
    return selectedMaterialIds.value.includes(id);
}

function toggleMaterial(id: number) {
    const idx = selectedMaterialIds.value.indexOf(id);

    if (idx >= 0) {
        selectedMaterialIds.value.splice(idx, 1);
    } else {
        selectedMaterialIds.value.push(id);
    }

    syncMaterialFormDefaults();
}

function selectAllMaterials() {
    selectedMaterialIds.value = materialsList.value.map((m: any) => m.id);
    syncMaterialFormDefaults();
}

function clearSelectedMaterials() {
    selectedMaterialIds.value = [];
}

function syncMaterialFormDefaults() {
    if (selectedMaterials.value.length === 0) {
return;
}

    const first = selectedMaterials.value[0];

    if (selectedMaterials.value.length === 1) {
        if (!title.value.trim() || title.value.endsWith('- Live Arena') || title.value.startsWith('Live Arena:')) {
            title.value = `${first.title} - Live Arena`;
        }

        if (first.subject_id) {
subjectId.value = first.subject_id;
}

        if (first.subject && (subject.value === 'Science' || !subject.value.trim())) {
            subject.value = first.subject;
        }

        if (first.grade_level_id) {
            gradeLevelId.value = first.grade_level_id;
        }
    } else {
        const titles = selectedMaterials.value.slice(0, 2).map((m: any) => m.title).join(' + ');
        const suffix = selectedMaterials.value.length > 2 ? ` (+${selectedMaterials.value.length - 2} more)` : '';

        if (!title.value.trim() || title.value.endsWith('- Live Arena') || title.value.startsWith('Live Arena:')) {
            title.value = `Live Arena: ${titles}${suffix}`;
        }

        const commonSubject = selectedMaterials.value.find((m: any) => m.subject)?.subject;

        if (commonSubject && (subject.value === 'Science' || !subject.value.trim())) {
            subject.value = commonSubject;
        }
    }
}

onMounted(() => {
    if (props.initialMaterialId) {
        selectedMaterialIds.value = [Number(props.initialMaterialId)];
        aiSource.value = 'material';
        syncMaterialFormDefaults();
    } else if (materialsList.value.length > 0) {
        selectedMaterialIds.value = [materialsList.value[0].id];
        aiSource.value = 'material';
        syncMaterialFormDefaults();
    } else {
        aiSource.value = 'prompt';
    }
});

const aiPrompt = ref('');
const aiCount = ref(5);
const aiGenerating = ref(false);
const aiError = ref('');
const aiSuccess = ref('');

const submitting = ref(false);

interface CardItem {
    question: string;
    answer: string;
    options?: string[];
    color: string;
    order: number;
}

const cards = ref<CardItem[]>([
    {
        question: 'What organelle is known as the powerhouse of the cell?',
        answer: 'Mitochondria',
        options: ['Nucleus', 'Ribosome', 'Mitochondria', 'Golgi Apparatus'],
        color: '#10b981',
        order: 0,
    },
    {
        question: 'Which component stores genetic DNA in eukaryotic cells?',
        answer: 'Nucleus',
        options: ['Cytoplasm', 'Nucleus', 'Vacuole', 'Lysosome'],
        color: '#3b82f6',
        order: 1,
    },
]);

function addCard() {
    const palette = ['#10b981', '#3b82f6', '#8b5cf6', '#f59e0b', '#ec4899'];
    cards.value.push({
        question: '',
        answer: '',
        options: ['', '', '', ''],
        color: palette[cards.value.length % palette.length],
        order: cards.value.length,
    });
}

function removeCard(idx: number) {
    cards.value.splice(idx, 1);
}

function updateCardOption(card: CardItem, optIdx: number, val: string) {
    if (!card.options) {
card.options = ['', '', '', ''];
}

    card.options[optIdx] = val;
}

async function generateWithAi() {
    if (aiGenerating.value) {
return;
}

    if (aiSource.value === 'material') {
        if (selectedMaterialIds.value.length === 0) {
            aiError.value = 'Please select at least one teaching material to compile.';

            return;
        }
    } else {
        if (!aiPrompt.value.trim()) {
            aiError.value = 'Please enter a topic prompt or lesson notes.';

            return;
        }
    }

    aiGenerating.value = true;
    aiError.value = '';
    aiSuccess.value = '';

    try {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const payload: Record<string, any> = {
            count: aiCount.value,
            game_type: gameType.value,
        };

        if (aiSource.value === 'material') {
            payload.material_ids = selectedMaterialIds.value;
            payload.material_id = selectedMaterialIds.value[0] || null;

            if (materialInstructions.value.trim()) {
                payload.prompt = materialInstructions.value.trim();
            }
        } else {
            payload.prompt = aiPrompt.value.trim();
        }

        const res = await fetch('/teacher/games/live/generate-ai', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                Accept: 'application/json',
            },
            body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (!res.ok || !data.ok) {
            aiError.value = data.message || 'Failed to generate questions with AI.';

            return;
        }

        if (Array.isArray(data.cards) && data.cards.length > 0) {
            if (appendMode.value && cards.value.length > 0) {
                const currentLen = cards.value.length;
                const newCards = data.cards.map((c: CardItem, idx: number) => ({
                    ...c,
                    order: currentLen + idx,
                }));
                cards.value = [...cards.value, ...newCards];
            } else {
                cards.value = data.cards;
            }

            const countCompiled = selectedMaterials.value.length;
            const sourceName = countCompiled > 1
                ? `${countCompiled} compiled materials`
                : (selectedMaterials.value[0]?.title ? `"${selectedMaterials.value[0].title}"` : (data.material?.title ? `"${data.material.title}"` : 'selected material'));

            aiSuccess.value = `Successfully generated ${data.cards.length} match questions compiled from ${sourceName} via ${data.provider || 'AI'}!`;

            syncMaterialFormDefaults();

            if (!title.value.trim() && aiPrompt.value.trim()) {
                const words = aiPrompt.value.trim().split(/\s+/).slice(0, 4).join(' ');
                title.value = `Live Arena: ${words}`;
            }
        }
    } catch {
        aiError.value = 'Network error while contacting AI generator.';
    } finally {
        aiGenerating.value = false;
    }
}

function submitCreateLive(launchNow: boolean = true) {
    if (cards.value.length === 0 || !title.value.trim() || submitting.value) {
return;
}

    submitting.value = true;
    router.post(
        '/teacher/games/live/create',
        {
            title: title.value.trim(),
            subject: subject.value.trim() || 'General',
            subject_id: subjectId.value,
            grade_level_id: gradeLevelId.value || null,
            type: gameType.value,
            xp_reward: xpReward.value,
            launch: launchNow,
            cards: cards.value.map((c, i) => ({
                question: c.question.trim(),
                answer: c.answer.trim(),
                options: c.options && c.options.filter(o => o.trim()).length ? c.options : null,
                color: c.color,
                order: i,
            })),
        },
        {
            onError: () => {
                submitting.value = false;
            },
        }
    );
}
</script>
