<template>
    <Head title="Student Dashboard" />

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
    <div
        v-if="flash?.info"
        class="gl-fade-in mb-6 rounded-xl px-4 py-3 text-sm"
        style="
            background: var(--gl-warning-bg);
            color: var(--gl-accent);
            border: 1px solid rgba(251, 191, 36, 0.2);
        "
    >
        {{ flash.info }}
    </div>

    <!-- Greeting -->
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p
                class="text-xs font-semibold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Dashboard
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Hello {{ firstName }}!
            </h1>
        </div>
        <!-- Interactive Daily Streak & Multiplier Shield Widget -->
        <button
            type="button"
            @click="showStreakModal = true"
            class="group flex flex-wrap items-center gap-3 rounded-2xl p-2.5 sm:px-4 sm:py-2.5 transition-all hover:scale-[1.02] hover:shadow-lg text-left"
            style="
                background: linear-gradient(
                    135deg,
                    rgba(251, 191, 36, 0.16),
                    rgba(249, 115, 22, 0.1)
                );
                border: 1px solid rgba(251, 191, 36, 0.35);
                box-shadow: 0 2px 12px rgba(251, 191, 36, 0.08);
            "
            title="Click to view streak multiplier & manage shields"
        >
            <div class="flex items-center gap-2">
                <div
                    class="flex h-8 w-8 items-center justify-center rounded-xl transition-transform group-hover:scale-110"
                    style="background: linear-gradient(135deg, var(--gl-accent), #f59e0b); color: #fff; box-shadow: 0 0 10px var(--gl-accent-glow);"
                >
                    <Flame class="h-4.5 w-4.5" :stroke-width="2.5" />
                </div>
                <div>
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-extrabold" style="color: var(--gl-text-primary)">
                            {{ currentStreak }}-Day Streak
                        </span>
                        <!-- Multiplier Tag -->
                        <span
                            class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-black uppercase tracking-wider"
                            style="
                                background: linear-gradient(135deg, rgba(245, 158, 11, 0.25), rgba(217, 70, 239, 0.25));
                                color: #f59e0b;
                                border: 1px solid rgba(245, 158, 11, 0.4);
                            "
                        >
                            {{ streakMultiplierLabel }} XP Boost
                        </span>
                    </div>
                    <p class="text-[10px]" style="color: var(--gl-text-muted)">
                        Tap to manage streak &amp; shields
                    </p>
                </div>
            </div>

            <!-- Shield Indicator Pill -->
            <div
                class="flex items-center gap-1.5 rounded-xl px-2.5 py-1 text-xs font-bold border transition-colors"
                :style="
                    isStreakFreezeActive
                        ? 'background: rgba(16, 185, 129, 0.15); border-color: rgba(16, 185, 129, 0.35); color: var(--gl-success);'
                        : 'background: var(--gl-surface); border-color: var(--gl-border); color: var(--gl-text-secondary);'
                "
            >
                <ShieldCheck v-if="isStreakFreezeActive" class="h-3.5 w-3.5 text-emerald-400" :stroke-width="2.5" />
                <ShieldAlert v-else class="h-3.5 w-3.5 text-amber-400" :stroke-width="2.5" />
                <span class="text-[11px]">
                    {{ isStreakFreezeActive ? 'Shield Active' : `${freezeShieldsCount} Shield${freezeShieldsCount === 1 ? '' : 's'}` }}
                </span>
            </div>
        </button>
    </div>

    <!-- Active Live Arena Match Banner for Students -->
    <div
        v-if="(activeLiveRooms?.length ?? 0) > 0"
        class="mb-6 space-y-3"
    >
        <div
            v-for="room in activeLiveRooms"
            :key="room.id"
            class="gl-fade-in relative overflow-hidden rounded-3xl p-5 sm:p-6 transition-all"
            style="
                background: linear-gradient(135deg, rgba(16, 185, 129, 0.14), rgba(6, 182, 212, 0.12));
                border: 1px solid rgba(16, 185, 129, 0.4);
                box-shadow: 0 4px 24px rgba(16, 185, 129, 0.18);
            "
        >
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3.5">
                    <div
                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-white shadow-md"
                        style="background: linear-gradient(135deg, #10b981, #059669);"
                    >
                        <Zap class="h-6 w-6 fill-current animate-bounce" />
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-500/15 px-2.5 py-0.5 text-[11px] font-black text-red-500 dark:text-red-400">
                                <span class="h-2 w-2 rounded-full bg-red-500 animate-ping"></span>
                                LIVE MATCH ARENA NOW
                            </span>
                            <span
                                v-if="room.status === 'waiting'"
                                class="rounded-full bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-600"
                            >
                                Lobby Waiting
                            </span>
                            <span
                                v-else
                                class="rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-600"
                            >
                                Questions In Progress
                            </span>
                        </div>
                        <h2 class="mt-1 text-base font-extrabold text-[var(--gl-text-primary)] sm:text-lg">
                            {{ room.game?.title || 'Live Match Arena' }}
                        </h2>
                        <p class="text-xs text-[var(--gl-text-secondary)]">
                            Hosted by <strong class="text-[var(--gl-text-primary)]">{{ room.teacher?.name || 'Teacher' }}</strong>
                            <template v-if="room.game?.subject"> · {{ room.game.subject }}</template>
                            <template v-if="room.game?.grade"> · {{ room.game.grade }}</template>
                            <template v-if="room.participants_count"> · {{ room.participants_count }} joined</template>
                        </p>
                    </div>
                </div>

                <!-- Big PIN & Join Button -->
                <div class="flex flex-wrap items-center gap-3">
                    <div
                        class="flex items-center gap-2.5 rounded-2xl border px-4 py-2 sm:px-5 sm:py-2.5"
                        style="background: var(--gl-surface); border-color: rgba(16, 185, 129, 0.4);"
                    >
                        <div>
                            <p class="text-[10px] font-extrabold tracking-wider uppercase text-emerald-600">
                                Game Code / PIN
                            </p>
                            <p class="font-mono text-xl font-black tracking-widest text-[var(--gl-text-primary)] sm:text-2xl">
                                {{ room.pin }}
                            </p>
                        </div>
                        <button
                            type="button"
                            @click="copyLiveRoomPin(room.pin)"
                            class="flex h-8 w-8 items-center justify-center rounded-xl transition-all hover:bg-emerald-500/20 active:scale-95 text-emerald-600"
                            title="Copy PIN"
                        >
                            <Check v-if="copiedLivePin === room.pin" class="h-4 w-4" />
                            <Copy v-else class="h-4 w-4" />
                        </button>
                    </div>

                    <a
                        :href="`/student/games/rooms/${encodeURIComponent(room.pin)}`"
                        class="flex items-center gap-2 rounded-2xl px-5 py-3 text-sm font-extrabold text-white shadow-lg transition-all hover:scale-105 active:scale-95"
                        style="background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);"
                    >
                        <Play class="h-4 w-4 fill-current" />
                        <span>Join Live Arena</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <!-- LEFT (2/3) -->
        <div class="space-y-5 xl:col-span-2">
            <!-- Stat cards -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div
                    class="rounded-2xl p-4 transition-all hover:-translate-y-0.5"
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
                            class="h-4.5 w-4.5"
                            style="color: var(--gl-accent)"
                            :stroke-width="2"
                        />
                    </div>
                    <p
                        class="text-xl font-extrabold tabular-nums"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ totalPoints.toLocaleString() }}
                        <span class="text-xs font-semibold">XP</span>
                    </p>
                    <p class="text-xs" style="color: var(--gl-text-secondary)">
                        Total earned
                    </p>
                </div>
                <div
                    class="rounded-2xl p-4 transition-all hover:-translate-y-0.5"
                    style="
                        background: rgba(59, 130, 246, 0.08);
                        border: 1px solid rgba(59, 130, 246, 0.18);
                    "
                >
                    <div
                        class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                        style="background: rgba(59, 130, 246, 0.16)"
                    >
                        <Award
                            class="h-4.5 w-4.5"
                            style="color: var(--gl-primary)"
                            :stroke-width="2"
                        />
                    </div>
                    <p
                        class="text-xl font-extrabold"
                        style="color: var(--gl-text-primary)"
                    >
                        Lv. {{ userLevel }}
                    </p>
                    <p class="text-xs" style="color: var(--gl-text-secondary)">
                        {{ levelTitle }}
                    </p>
                </div>
                <div
                    class="rounded-2xl p-4 transition-all hover:-translate-y-0.5"
                    style="
                        background: rgba(16, 185, 129, 0.08);
                        border: 1px solid rgba(16, 185, 129, 0.18);
                    "
                >
                    <div
                        class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                        style="background: rgba(16, 185, 129, 0.16)"
                    >
                        <Target
                            class="h-4.5 w-4.5"
                            style="color: var(--gl-success)"
                            :stroke-width="2"
                        />
                    </div>
                    <p
                        class="text-xl font-extrabold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ overallAvg
                        }}<span class="text-xs font-semibold">%</span>
                    </p>
                    <p class="text-xs" style="color: var(--gl-text-secondary)">
                        Average score
                    </p>
                </div>
                <div
                    class="rounded-2xl p-4 transition-all hover:-translate-y-0.5"
                    style="
                        background: rgba(239, 68, 68, 0.08);
                        border: 1px solid rgba(239, 68, 68, 0.18);
                    "
                >
                    <div
                        class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                        style="background: rgba(239, 68, 68, 0.16)"
                    >
                        <Trophy
                            class="h-4.5 w-4.5"
                            style="color: var(--gl-danger)"
                            :stroke-width="2"
                        />
                    </div>
                    <p
                        class="text-xl font-extrabold"
                        style="color: var(--gl-text-primary)"
                    >
                        #{{ leaderboardMini.rank }}
                    </p>
                    <p class="text-xs" style="color: var(--gl-text-secondary)">
                        of {{ leaderboardMini.of }} in section
                    </p>
                </div>
            </div>

            <!-- Upcoming Exam & Assessment Countdown -->
            <div v-if="assessmentsCountdown && assessmentsCountdown.length > 0" class="gl-glow-card overflow-hidden p-5 sm:p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            style="
                                background: linear-gradient(135deg, rgba(239, 68, 68, 0.18), rgba(245, 158, 11, 0.15));
                                border: 1px solid rgba(239, 68, 68, 0.3);
                            "
                        >
                            <Timer class="h-5 w-5 text-rose-500" :stroke-width="2" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold" style="color: var(--gl-text-primary)">
                                    Upcoming Exam &amp; Assessment Countdown
                                </h3>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    style="background: rgba(239, 68, 68, 0.15); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.3);"
                                >
                                    {{ assessmentsCountdown.length }} Imminent
                                </span>
                            </div>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                Time-sensitive deadlines to prioritize and complete
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Countdown Cards Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                    <div
                        v-for="item in assessmentsCountdown"
                        :key="item.id"
                        class="group relative rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5 flex flex-col justify-between"
                        :style="
                            item.urgency === 'urgent'
                                ? {
                                      background: 'linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(239, 68, 68, 0.04))',
                                      border: '1px solid rgba(239, 68, 68, 0.35)',
                                      boxShadow: '0 0 15px rgba(239, 68, 68, 0.08)',
                                  }
                                : {
                                      background: 'var(--gl-surface-2)',
                                      border: '1px solid var(--gl-border)',
                                  }
                        "
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span
                                    class="rounded-lg px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                    :style="item.urgency === 'urgent' ? 'background: #ef4444; color: #fff;' : 'background: rgba(59, 130, 246, 0.15); color: var(--gl-primary);'"
                                >
                                    {{ item.type }}
                                </span>
                                <span
                                    class="flex items-center gap-1 text-[11px] font-bold"
                                    :style="item.urgency === 'urgent' ? 'color: #ef4444;' : 'color: var(--gl-accent);'"
                                >
                                    <Clock class="h-3 w-3" />
                                    {{ item.days_left > 0 ? `${item.days_left}d ${item.hours_left}h left` : `${item.hours_left}h ${item.minutes_left}m left` }}
                                </span>
                            </div>

                            <h4 class="font-bold text-sm line-clamp-1" style="color: var(--gl-text-primary)">
                                {{ item.title }}
                            </h4>
                            <p class="text-xs mt-1" style="color: var(--gl-text-muted)">
                                Due: {{ item.due_date }} <span v-if="item.time_limit">· {{ item.time_limit }} mins limit</span>
                            </p>
                        </div>

                        <div class="mt-4 pt-3 border-t flex items-center justify-between" style="border-color: var(--gl-border);">
                            <span class="text-[11px] font-medium text-slate-400">
                                {{ item.urgency === 'urgent' ? '🔥 Final call' : 'Prepare early' }}
                            </span>
                            <Link
                                :href="item.href"
                                class="flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold text-white transition-all hover:scale-105"
                                :style="
                                    item.urgency === 'urgent'
                                        ? 'background: linear-gradient(135deg, #ef4444, #dc2626); box-shadow: 0 0 10px rgba(239, 68, 68, 0.4);'
                                        : 'background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary)); box-shadow: 0 0 8px var(--gl-primary-glow);'
                                "
                            >
                                Start Now
                                <ArrowRight class="h-3 w-3" :stroke-width="2.5" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Announcements & Notices -->
            <div class="gl-glow-card overflow-hidden p-5 sm:p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    rgba(59, 130, 246, 0.18),
                                    rgba(139, 92, 246, 0.12)
                                );
                                border: 1px solid rgba(59, 130, 246, 0.3);
                            "
                        >
                            <Megaphone
                                class="h-5 w-5"
                                style="color: var(--gl-primary)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3
                                    class="text-base font-bold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    Announcements & Notices
                                </h3>
                                <span
                                    v-if="(announcements?.length ?? 0) > 0"
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    style="
                                        background: rgba(59, 130, 246, 0.15);
                                        color: var(--gl-primary);
                                        border: 1px solid rgba(59, 130, 246, 0.25);
                                    "
                                >
                                    {{ announcements?.length }} active
                                </span>
                            </div>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                Important updates from teachers and school administration
                            </p>
                        </div>
                    </div>

                    <!-- Category filter pills -->
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button
                            v-for="pill in [
                                { id: 'all', label: 'All' },
                                { id: 'section', label: 'My Section' },
                                { id: 'pinned', label: 'Pinned' },
                                { id: 'academic', label: 'Academic' },
                                { id: 'reminder', label: 'Reminders' },
                            ]"
                            :key="pill.id"
                            type="button"
                            @click="announcementFilter = (pill.id as any)"
                            class="rounded-lg px-2.5 py-1 text-xs font-semibold transition-all"
                            :style="
                                announcementFilter === pill.id
                                    ? {
                                          background: 'var(--gl-primary)',
                                          color: '#fff',
                                          boxShadow: '0 0 10px var(--gl-primary-glow)',
                                      }
                                    : {
                                          background: 'var(--gl-surface-2)',
                                          color: 'var(--gl-text-muted)',
                                          border: '1px solid var(--gl-border)',
                                      }
                            "
                        >
                            {{ pill.label }}
                        </button>

                        <Link
                            href="/student/announcements"
                            class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-semibold transition-all hover:underline"
                            style="color: var(--gl-primary)"
                        >
                            View All
                            <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                </div>

                <!-- Announcements List -->
                <div v-if="filteredAnnouncements.length > 0" class="space-y-3">
                    <div
                        v-for="item in filteredAnnouncements"
                        :key="item.id"
                        class="group relative rounded-2xl p-4 transition-all duration-200 hover:-translate-y-0.5"
                        :style="
                            item.is_pinned
                                ? {
                                      background:
                                          'linear-gradient(135deg, rgba(251, 191, 36, 0.08), rgba(245, 158, 11, 0.03))',
                                      border: '1px solid rgba(251, 191, 36, 0.35)',
                                      boxShadow: '0 2px 12px rgba(251, 191, 36, 0.06)',
                                  }
                                : {
                                      background: 'var(--gl-surface-2)',
                                      border: '1px solid var(--gl-border)',
                                  }
                        "
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="mb-1.5 flex flex-wrap items-center gap-2">
                                    <span
                                        v-if="item.is_pinned"
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-extrabold uppercase tracking-wide"
                                        style="
                                            background: rgba(251, 191, 36, 0.18);
                                            color: var(--gl-accent);
                                            border: 1px solid rgba(251, 191, 36, 0.35);
                                        "
                                    >
                                        <Pin class="h-3 w-3" :stroke-width="2.5" />
                                        Pinned
                                    </span>

                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                        :style="{
                                            background: getCategoryBadge(item.category).bg,
                                            color: getCategoryBadge(item.category).color,
                                            border: `1px solid ${getCategoryBadge(item.category).border}`,
                                        }"
                                    >
                                        <component
                                            :is="getCategoryBadge(item.category).icon"
                                            class="h-3 w-3"
                                            :stroke-width="2"
                                        />
                                        {{ getCategoryBadge(item.category).label }}
                                    </span>

                                    <span
                                        v-if="item.section"
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                        style="
                                            background: rgba(139, 92, 246, 0.15);
                                            color: #8B5CF6;
                                            border: 1px solid rgba(139, 92, 246, 0.35);
                                        "
                                        title="Targeted to your section"
                                    >
                                        <Users class="h-3 w-3" :stroke-width="2" />
                                        Section {{ item.section.name }}
                                    </span>

                                    <span
                                        v-else-if="item.grade_level"
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold"
                                        style="
                                            background: rgba(59, 130, 246, 0.15);
                                            color: #3B82F6;
                                            border: 1px solid rgba(59, 130, 246, 0.35);
                                        "
                                    >
                                        <Users class="h-3 w-3" :stroke-width="2" />
                                        {{ item.grade_level.name }}
                                    </span>

                                    <span class="text-[11px]" style="color: var(--gl-text-muted)">
                                        {{ item.time_ago || item.date_formatted }}
                                    </span>
                                </div>

                                <h4
                                    class="text-sm font-bold tracking-tight line-clamp-1"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ item.title }}
                                </h4>

                                <p
                                    class="mt-1 text-xs leading-relaxed line-clamp-2"
                                    style="color: var(--gl-text-secondary)"
                                >
                                    {{ item.content }}
                                </p>

                                <div class="mt-2.5 flex items-center justify-between gap-2">
                                    <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                        Posted by:
                                        <span class="font-medium" style="color: var(--gl-text-secondary)">
                                            {{ item.author_name }}
                                        </span>
                                    </p>

                                    <button
                                        type="button"
                                        @click="openAnnouncementModal(item)"
                                        class="inline-flex items-center gap-1 text-xs font-semibold transition-colors hover:underline"
                                        style="color: var(--gl-primary)"
                                    >
                                        Read full notice
                                        <ArrowRight class="h-3 w-3" :stroke-width="2" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-2xl py-8 text-center"
                    style="background: var(--gl-surface-2); border: 1px dashed var(--gl-border)"
                >
                    <Megaphone class="h-8 w-8 mb-2 opacity-40" style="color: var(--gl-text-muted)" />
                    <p class="text-xs font-semibold" style="color: var(--gl-text-secondary)">
                        No announcements found in this category
                    </p>
                    <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                        Check back later for school updates and announcements.
                    </p>
                </div>
            </div>

            <!-- Course progress -->
            <div class="gl-glow-card p-5 sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3
                            class="text-base font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            Course Progress
                        </h3>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            Tap a subject to keep going
                        </p>
                    </div>
                    <Link
                        href="/student/results"
                        class="flex items-center gap-1 text-xs font-semibold transition-colors hover:opacity-80"
                        style="color: var(--gl-primary)"
                    >
                        Results
                        <ArrowRight class="h-3.5 w-3.5" :stroke-width="2" />
                    </Link>
                </div>
                <div class="space-y-4">
                    <Link
                        v-for="s in subjectRows"
                        :key="s.key"
                        :href="s.href"
                        class="group block"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex min-w-0 items-center gap-3">
                                <div
                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                    :style="{ background: s.bg }"
                                >
                                    <component
                                        :is="s.icon"
                                        class="h-4.5 w-4.5"
                                        :style="{ color: s.color }"
                                        :stroke-width="2"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="truncate text-sm font-semibold"
                                        style="color: var(--gl-text-primary)"
                                    >
                                        {{ s.label }}
                                    </p>
                                    <p
                                        class="text-[11px]"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        {{ s.done }}/{{ s.total }} done ·
                                        {{ s.avg }}% avg
                                    </p>
                                </div>
                            </div>
                            <span
                                class="shrink-0 text-xs font-bold tabular-nums"
                                style="color: var(--gl-primary)"
                                >{{ s.pct }}%</span
                            >
                        </div>
                        <div
                            class="mt-1.5 h-2 overflow-hidden rounded-full"
                            style="background: var(--gl-surface-2)"
                        >
                            <div
                                class="h-full rounded-full transition-all"
                                :style="{
                                    width: s.pct + '%',
                                    background: s.bar,
                                }"
                            ></div>
                        </div>
                    </Link>
                </div>
            </div>

            <!-- Challenge + efficiency -->
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                <div
                    class="relative overflow-hidden rounded-2xl p-5"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-primary-glow),
                            rgba(124, 58, 237, 0.08)
                        );
                        border: 1px solid var(--gl-border);
                    "
                >
                    <div class="relative z-10 flex items-center gap-4">
                        <img
                            src="/logo.png"
                            alt=""
                            class="h-14 w-14 shrink-0 rounded-full object-cover"
                            style="box-shadow: 0 0 20px var(--gl-primary-glow)"
                        />
                        <div class="min-w-0 flex-1">
                            <p
                                class="text-[11px] font-bold tracking-widest uppercase"
                                style="color: var(--gl-primary)"
                            >
                                Today's Challenge
                            </p>
                            <p
                                class="mt-0.5 truncate text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ challenge.title }}
                            </p>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-secondary)"
                            >
                                {{ challenge.sub }}
                            </p>
                        </div>
                        <Link
                            :href="challenge.href"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-white transition-all hover:scale-105"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-accent),
                                    #f59e0b
                                );
                                box-shadow: 0 0 16px var(--gl-accent-glow);
                            "
                            :title="challenge.title"
                        >
                            <Play class="ml-0.5 h-5 w-5" :stroke-width="2.5" />
                        </Link>
                    </div>
                </div>
                <div class="gl-glow-card flex items-center gap-4 p-5">
                    <div class="relative h-20 w-20 shrink-0">
                        <svg
                            viewBox="0 0 80 80"
                            class="h-full w-full -rotate-90"
                        >
                            <circle
                                cx="40"
                                cy="40"
                                r="34"
                                fill="none"
                                stroke-width="9"
                                style="stroke: var(--gl-surface-2)"
                            />
                            <circle
                                cx="40"
                                cy="40"
                                r="34"
                                fill="none"
                                stroke-width="9"
                                stroke-linecap="round"
                                :stroke-dasharray="RING"
                                :stroke-dashoffset="ringOff"
                                style="
                                    stroke: var(--gl-accent);
                                    transition: stroke-dashoffset 0.6s ease;
                                "
                            />
                        </svg>
                        <span
                            class="absolute inset-0 flex items-center justify-center text-sm font-extrabold tabular-nums"
                            style="color: var(--gl-text-primary)"
                            >{{ overallAvg }}%</span
                        >
                    </div>
                    <div>
                        <p
                            class="text-sm font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            Learning Efficiency
                        </p>
                        <p
                            class="mt-0.5 text-xs leading-relaxed"
                            style="color: var(--gl-text-secondary)"
                        >
                            Your overall mastery across all graded work.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Achievement Badges Showcase -->
            <div class="gl-glow-card overflow-hidden p-5 sm:p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                            style="
                                background: linear-gradient(135deg, rgba(245, 158, 11, 0.18), rgba(217, 70, 239, 0.15));
                                border: 1px solid rgba(245, 158, 11, 0.3);
                            "
                        >
                            <Award class="h-5 w-5 text-amber-400" :stroke-width="2" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-bold" style="color: var(--gl-text-primary)">
                                    Achievement Badges
                                </h3>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-bold"
                                    style="background: rgba(16, 185, 129, 0.15); color: var(--gl-success); border: 1px solid rgba(16, 185, 129, 0.3);"
                                >
                                    {{ unlockedBadgesCount }} / {{ badgesList.length }} Unlocked
                                </span>
                            </div>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                Collectible honors earned through dedication, speed, and accuracy
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Badge Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <button
                        v-for="b in badgesList"
                        :key="b.id"
                        type="button"
                        @click="openBadgeModal(b)"
                        class="group relative flex flex-col items-center text-center p-3.5 rounded-2xl border transition-all duration-200 hover:-translate-y-1 hover:shadow-lg"
                        :style="
                            b.unlocked
                                ? {
                                      background: 'linear-gradient(135deg, rgba(251, 191, 36, 0.08), rgba(124, 58, 237, 0.05))',
                                      borderColor: 'rgba(251, 191, 36, 0.35)',
                                      boxShadow: '0 2px 10px rgba(251, 191, 36, 0.05)',
                                  }
                                : {
                                      background: 'var(--gl-surface-2)',
                                      borderColor: 'var(--gl-border)',
                                      opacity: '0.7',
                                  }
                        "
                    >
                        <div
                            class="relative mb-2.5 flex h-12 w-12 items-center justify-center rounded-2xl transition-transform group-hover:scale-110"
                            :style="
                                b.unlocked
                                    ? {
                                          background: 'linear-gradient(135deg, #F59E0B, #EC4899)',
                                          boxShadow: '0 0 16px rgba(245, 158, 11, 0.35)',
                                          color: '#fff',
                                      }
                                    : {
                                          background: 'var(--gl-surface)',
                                          border: '1px solid var(--gl-border)',
                                          color: 'var(--gl-text-muted)',
                                      }
                            "
                        >
                            <component :is="getBadgeIcon(b.icon)" class="h-6 w-6" :stroke-width="2.2" />
                            <span
                                v-if="!b.unlocked"
                                class="absolute -top-1 -right-1 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-slate-800 border border-slate-700 text-slate-400"
                            >
                                <Lock class="h-2.5 w-2.5" />
                            </span>
                        </div>

                        <p class="font-bold text-xs truncate max-w-full" style="color: var(--gl-text-primary)">
                            {{ b.name }}
                        </p>
                        <p class="text-[10px] line-clamp-1 mt-0.5" style="color: var(--gl-text-muted)">
                            {{ b.desc }}
                        </p>

                        <span
                            class="mt-2.5 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wider"
                            :style="
                                b.unlocked
                                    ? 'background: rgba(16, 185, 129, 0.15); color: var(--gl-success); border: 1px solid rgba(16, 185, 129, 0.3);'
                                    : 'background: var(--gl-surface); color: var(--gl-text-muted); border: 1px solid var(--gl-border);'
                            "
                        >
                            {{ b.unlocked ? `Unlocked (+${b.reward_xp} XP)` : 'Locked' }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Recent results -->
            <div class="gl-glow-card overflow-hidden">
                <div
                    class="border-b px-5 py-4"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center justify-between gap-3">
                        <h3
                            class="text-sm font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            Recent Results
                        </h3>
                        <Link
                            v-if="recentResults.length"
                            href="/student/results"
                            class="flex items-center gap-1 text-xs font-semibold transition-colors hover:opacity-80"
                            style="color: var(--gl-primary)"
                        >
                            View all
                            <ArrowRight class="h-3.5 w-3.5" :stroke-width="2" />
                        </Link>
                    </div>
                </div>
                <div
                    v-if="!recentResults.length"
                    class="flex flex-col items-center justify-center px-6 py-10 text-center"
                >
                    <p
                        class="text-sm font-medium"
                        style="color: var(--gl-text-secondary)"
                    >
                        No results yet
                    </p>
                    <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                        Complete an activity to see it here.
                    </p>
                </div>
                <ul
                    v-else
                    class="divide-y"
                    style="border-color: var(--gl-border)"
                >
                    <li
                        v-for="r in recentResults"
                        :key="r.submitted_at + r.title"
                        class="flex items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-[rgba(59,130,246,0.03)]"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <PctBadge :pct="r.pct" />
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-medium"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ r.title }}
                                </p>
                                <span
                                    class="mt-0.5 inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                                    :class="badgeClass(r.type)"
                                    >{{ r.type }}</span
                                >
                            </div>
                        </div>
                        <span
                            class="shrink-0 text-sm font-bold tabular-nums"
                            :style="{
                                color:
                                    r.pct >= 60
                                        ? 'var(--gl-success)'
                                        : 'var(--gl-danger)',
                            }"
                            >{{ r.score }}/{{ r.total }}</span
                        >
                    </li>
                </ul>
            </div>
        </div>

        <!-- RIGHT (1/3) -->
        <div class="space-y-5">
            <!-- Quick Class Polls Widget -->
            <div v-if="activePoll" class="gl-glow-card overflow-hidden p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="
                                background: linear-gradient(135deg, rgba(59, 130, 246, 0.15), rgba(139, 92, 246, 0.15));
                                border: 1px solid rgba(59, 130, 246, 0.3);
                            "
                        >
                            <HelpCircle class="h-4 w-4 text-blue-500" :stroke-width="2" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold" style="color: var(--gl-text-primary)">
                                Classroom Quick Poll
                            </h3>
                            <p class="text-[10px]" style="color: var(--gl-text-muted)">
                                {{ activePoll.total_votes }} total vote{{ activePoll.total_votes === 1 ? '' : 's' }} recorded
                            </p>
                        </div>
                    </div>

                    <button
                        v-if="activePoll.user_voted && !showChangeVote"
                        type="button"
                        @click="showChangeVote = true"
                        class="text-[10px] text-blue-400 hover:underline"
                    >
                        Change vote
                    </button>
                </div>

                <!-- Question -->
                <p class="text-xs font-semibold mb-3" style="color: var(--gl-text-primary)">
                    {{ activePoll.question }}
                </p>

                <!-- UNVOTED or CHANGE VOTE mode: 1-click option buttons -->
                <div v-if="!activePoll.user_voted || showChangeVote" class="space-y-2">
                    <button
                        v-for="opt in activePoll.options"
                        :key="opt.index"
                        type="button"
                        :disabled="votingPoll"
                        @click="submitPollVote(opt.index)"
                        class="w-full text-left rounded-xl p-2.5 text-xs font-medium border transition-all hover:scale-[1.01] hover:border-blue-500 disabled:opacity-50 flex items-center justify-between"
                        :style="
                            opt.is_selected
                                ? 'background: rgba(59, 130, 246, 0.15); border-color: var(--gl-primary); color: var(--gl-text-primary);'
                                : 'background: var(--gl-surface-2); border-color: var(--gl-border); color: var(--gl-text-secondary);'
                        "
                    >
                        <span>{{ opt.text }}</span>
                        <CheckCircle2 v-if="opt.is_selected" class="h-3.5 w-3.5 text-blue-500 shrink-0" />
                    </button>
                </div>

                <!-- VOTED mode: Real-time results progress bars -->
                <div v-else class="space-y-2.5">
                    <div
                        v-for="opt in activePoll.options"
                        :key="opt.index"
                        class="rounded-xl p-2.5 border transition-all"
                        :style="
                            opt.is_selected
                                ? 'background: rgba(59, 130, 246, 0.08); border-color: rgba(59, 130, 246, 0.35);'
                                : 'background: var(--gl-surface-2); border-color: var(--gl-border);'
                        "
                    >
                        <div class="flex items-center justify-between text-xs mb-1.5 gap-2">
                            <span class="font-medium truncate flex items-center gap-1.5" :style="opt.is_selected ? 'color: var(--gl-primary); font-weight: bold;' : 'color: var(--gl-text-primary);'">
                                <CheckCircle2 v-if="opt.is_selected" class="h-3.5 w-3.5 text-blue-500 shrink-0" />
                                {{ opt.text }}
                            </span>
                            <span class="font-bold tabular-nums shrink-0" style="color: var(--gl-text-secondary)">
                                {{ opt.pct }}% ({{ opt.votes }})
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-slate-800/40">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :style="{
                                    width: `${opt.pct}%`,
                                    background: opt.is_selected
                                        ? 'linear-gradient(90deg, var(--gl-primary), var(--gl-secondary))'
                                        : 'linear-gradient(90deg, #64748B, #94A3B8)',
                                }"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Weekly activity -->
            <div class="gl-glow-card p-5">
                <div class="mb-1 flex items-center justify-between">
                    <h3
                        class="text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Weekly Activity
                    </h3>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                        >{{ weekTotal }} done</span
                    >
                </div>
                <p class="mb-3 text-[11px]" style="color: var(--gl-text-muted)">
                    Last 7 days
                </p>
                <div class="grid grid-cols-7 gap-1.5">
                    <div
                        v-for="(d, i) in weekDots"
                        :key="i"
                        class="flex flex-col items-center gap-1"
                    >
                        <span
                            class="flex h-8 w-full items-center justify-center rounded-lg text-[10px] font-bold"
                            :style="
                                d.count > 0
                                    ? {
                                          background:
                                              'linear-gradient(135deg, var(--gl-accent), #F59E0B)',
                                          color: '#fff',
                                          boxShadow:
                                              '0 0 10px var(--gl-accent-glow)',
                                      }
                                    : {
                                          background: 'var(--gl-surface-2)',
                                          color: 'var(--gl-text-muted)',
                                      }
                            "
                            :title="`${d.label}: ${d.count} submitted`"
                        >
                            {{ d.count > 0 ? d.count : d.dow }}
                        </span>
                        <span
                            class="text-[9px]"
                            style="color: var(--gl-text-muted)"
                            >{{ d.dow }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- Student To-Do List -->
            <div class="gl-glow-card overflow-hidden p-5">
                <div class="mb-3 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="
                                background: rgba(16, 185, 129, 0.14);
                                border: 1px solid rgba(16, 185, 129, 0.25);
                            "
                        >
                            <CheckSquare
                                class="h-4 w-4"
                                style="color: var(--gl-success)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                To-Do & Tasks
                            </h3>
                            <p class="text-[10px]" style="color: var(--gl-text-muted)">
                                {{ totalOpenTodosCount }} item{{ totalOpenTodosCount === 1 ? '' : 's' }} to complete
                            </p>
                        </div>
                    </div>

                    <!-- Tabs switcher -->
                    <div
                        class="flex rounded-xl p-0.5"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <button
                            type="button"
                            @click="todoTab = 'assignments'"
                            class="rounded-lg px-2.5 py-1 text-[11px] font-semibold transition-all"
                            :style="
                                todoTab === 'assignments'
                                    ? {
                                          background: 'var(--gl-surface)',
                                          color: 'var(--gl-primary)',
                                          boxShadow: '0 1px 4px rgba(0,0,0,0.1)',
                                      }
                                    : { color: 'var(--gl-text-muted)' }
                            "
                        >
                            Due ({{ assignmentTodos.length }})
                        </button>
                        <button
                            type="button"
                            @click="todoTab = 'checklist'"
                            class="rounded-lg px-2.5 py-1 text-[11px] font-semibold transition-all"
                            :style="
                                todoTab === 'checklist'
                                    ? {
                                          background: 'var(--gl-surface)',
                                          color: 'var(--gl-primary)',
                                          boxShadow: '0 1px 4px rgba(0,0,0,0.1)',
                                      }
                                    : { color: 'var(--gl-text-muted)' }
                            "
                        >
                            My Tasks ({{ personalTasks.length }})
                        </button>
                    </div>
                </div>

                <!-- ASSIGNMENTS TAB -->
                <div v-if="todoTab === 'assignments'">
                    <div v-if="assignmentTodos.length > 0" class="space-y-2.5">
                        <div
                            v-for="item in assignmentTodos"
                            :key="item.id"
                            class="rounded-xl p-3 transition-all hover:-translate-y-0.5"
                            :style="
                                item.is_urgent
                                    ? {
                                          background: 'rgba(239, 68, 68, 0.06)',
                                          border: '1px solid rgba(239, 68, 68, 0.25)',
                                      }
                                    : {
                                          background: 'var(--gl-surface-2)',
                                          border: '1px solid var(--gl-border)',
                                      }
                            "
                        >
                            <div class="flex items-start justify-between gap-2.5">
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <div
                                        class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-lg"
                                        :style="{ background: getTodoTypeBadge(item.type).bg }"
                                    >
                                        <component
                                            :is="getTodoTypeBadge(item.type).icon"
                                            class="h-3.5 w-3.5"
                                            :style="{ color: getTodoTypeBadge(item.type).color }"
                                            :stroke-width="2"
                                        />
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="rounded px-1.5 py-0.5 text-[9px] font-bold uppercase tracking-wider"
                                                :style="{
                                                    background: getTodoTypeBadge(item.type).bg,
                                                    color: getTodoTypeBadge(item.type).color,
                                                }"
                                            >
                                                {{ item.type }}
                                            </span>
                                            <span
                                                v-if="item.is_urgent"
                                                class="inline-flex items-center gap-0.5 text-[10px] font-bold"
                                                style="color: var(--gl-danger)"
                                            >
                                                <Clock class="h-2.5 w-2.5" />
                                                Due Soon
                                            </span>
                                        </div>
                                        <p
                                            class="mt-1 truncate text-xs font-bold"
                                            style="color: var(--gl-text-primary)"
                                            :title="item.title"
                                        >
                                            {{ item.title }}
                                        </p>
                                        <p class="mt-0.5 text-[10px]" style="color: var(--gl-text-muted)">
                                            {{ item.due_text }}
                                            <span v-if="item.time_limit"> &bull; {{ item.time_limit }} mins</span>
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button
                                        v-if="item.toggle_url"
                                        type="button"
                                        @click="toggleAssignmentItem(item)"
                                        class="rounded-lg p-1.5 text-[var(--gl-text-muted)] hover:bg-emerald-500/15 hover:text-emerald-500 transition-colors"
                                        :title="item.type === 'Material' ? 'Mark material as read' : 'Mark task as complete'"
                                    >
                                        <CheckCircle2 class="h-4 w-4" />
                                    </button>

                                    <Link
                                        v-if="item.href && item.type === 'Material'"
                                        :href="item.href"
                                        class="shrink-0 inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white transition-all hover:scale-105"
                                        style="
                                            background: linear-gradient(
                                                135deg,
                                                var(--gl-primary),
                                                var(--gl-secondary)
                                            );
                                            box-shadow: 0 0 8px var(--gl-primary-glow);
                                        "
                                    >
                                        Read
                                        <BookOpen class="h-3 w-3" :stroke-width="2" />
                                    </Link>
                                    <Link
                                        v-else
                                        :href="item.href"
                                        class="shrink-0 inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-white transition-all hover:scale-105"
                                        style="
                                            background: linear-gradient(
                                                135deg,
                                                var(--gl-primary),
                                                var(--gl-secondary)
                                            );
                                            box-shadow: 0 0 8px var(--gl-primary-glow);
                                        "
                                    >
                                        {{ item.type === 'Task' ? 'View' : 'Start' }}
                                        <ArrowRight class="h-3 w-3" :stroke-width="2" />
                                    </Link>

                                    <button
                                        type="button"
                                        @click="dismissAssignmentItem(item)"
                                        title="Dismiss / hide from to-do list"
                                        class="rounded-lg p-1.5 text-[var(--gl-text-muted)] hover:bg-[rgba(239,68,68,0.12)] hover:text-[var(--gl-danger)] transition-colors"
                                    >
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- All caught up -->
                    <div
                        v-else
                        class="flex flex-col items-center justify-center rounded-xl p-5 text-center"
                        style="
                            background: var(--gl-surface-2);
                            border: 1px dashed var(--gl-border);
                        "
                    >
                        <CheckCircle2
                            class="mb-1.5 h-7 w-7"
                            style="color: var(--gl-success)"
                            :stroke-width="2"
                        />
                        <p class="text-xs font-bold" style="color: var(--gl-text-primary)">
                            All caught up!
                        </p>
                        <p class="mt-0.5 text-[11px]" style="color: var(--gl-text-muted)">
                            You have no pending tests or activities right now.
                        </p>
                    </div>
                </div>

                <!-- PERSONAL CHECKLIST TAB -->
                <div v-else>
                    <!-- Input box -->
                    <form @submit.prevent="addPersonalTask" class="mb-3 flex items-center gap-1.5">
                        <input
                            v-model="newTaskText"
                            type="text"
                            placeholder="+ Add a study task..."
                            maxlength="100"
                            class="w-full rounded-xl px-3 py-1.5 text-xs focus:outline-none"
                            style="
                                background: var(--gl-surface-2);
                                border: 1px solid var(--gl-border);
                                color: var(--gl-text-primary);
                            "
                        />
                        <button
                            type="submit"
                            :disabled="!newTaskText.trim()"
                            class="shrink-0 rounded-xl px-3 py-1.5 text-xs font-semibold text-white transition-all disabled:opacity-40"
                            style="background: var(--gl-primary)"
                        >
                            Add
                        </button>
                    </form>

                    <!-- Filter pills -->
                    <div class="mb-2 flex items-center justify-between text-[11px]">
                        <div class="flex items-center gap-1">
                            <button
                                v-for="f in ['all', 'pending', 'completed']"
                                :key="f"
                                type="button"
                                @click="checklistFilter = (f as any)"
                                class="rounded px-2 py-0.5 capitalize transition-colors"
                                :style="
                                    checklistFilter === f
                                        ? {
                                              background: 'var(--gl-surface-2)',
                                              color: 'var(--gl-text-primary)',
                                              fontWeight: 'bold',
                                          }
                                        : { color: 'var(--gl-text-muted)' }
                                "
                            >
                                {{ f }}
                            </button>
                        </div>
                        <button
                            v-if="personalCompletedCount > 0"
                            type="button"
                            @click="clearCompletedTasks"
                            class="text-[10px] text-red-400 hover:underline"
                        >
                            Clear done
                        </button>
                    </div>

                    <!-- Checklist items -->
                    <div
                        v-if="filteredPersonalTasks.length > 0"
                        class="max-h-56 space-y-2 overflow-y-auto pr-1"
                    >
                        <div
                            v-for="task in filteredPersonalTasks"
                            :key="task.id"
                            class="group flex items-center justify-between gap-2 rounded-xl p-2.5 transition-colors"
                            style="
                                background: var(--gl-surface-2);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            <button
                                type="button"
                                @click="togglePersonalTask(task)"
                                class="flex flex-1 items-center gap-2.5 min-w-0 text-left"
                            >
                                <span
                                    class="flex h-4.5 w-4.5 shrink-0 items-center justify-center rounded-md transition-colors"
                                    :style="
                                        task.completed
                                            ? {
                                                  background: 'var(--gl-success)',
                                                  color: '#fff',
                                              }
                                            : {
                                                  border:
                                                      '1.5px solid var(--gl-border)',
                                              }
                                    "
                                >
                                    <CheckCircle2
                                        v-if="task.completed"
                                        class="h-3 w-3"
                                        :stroke-width="2.5"
                                    />
                                </span>
                                <span
                                    class="truncate text-xs transition-all"
                                    :style="
                                        task.completed
                                            ? {
                                                  textDecoration: 'line-through',
                                                  color: 'var(--gl-text-muted)',
                                              }
                                            : { color: 'var(--gl-text-primary)' }
                                    "
                                >
                                    {{ task.text }}
                                </span>
                            </button>

                            <button
                                type="button"
                                @click="deletePersonalTask(task.id)"
                                class="shrink-0 p-1 text-red-400 opacity-0 transition-opacity group-hover:opacity-100 hover:text-red-300"
                                title="Delete task"
                            >
                                <Trash2 class="h-3 w-3" />
                            </button>
                        </div>
                    </div>

                    <div
                        v-else
                        class="rounded-xl p-4 text-center text-xs"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                    >
                        No tasks in this list. Add a study goal above!
                    </div>
                </div>
            </div>

            <!-- Leaderboard mini -->
            <div
                class="overflow-hidden rounded-2xl"
                style="
                    background:
                        linear-gradient(
                            180deg,
                            var(--gl-accent-glow),
                            transparent 55%
                        ),
                        var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div class="p-5 pb-3 text-center">
                    <div
                        class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-xl"
                        style="background: rgba(251, 191, 36, 0.16)"
                    >
                        <Trophy
                            class="h-5 w-5"
                            style="color: var(--gl-accent)"
                            :stroke-width="2"
                        />
                    </div>
                    <h3
                        class="text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Leaderboard
                    </h3>
                    <p class="text-[11px]" style="color: var(--gl-text-muted)">
                        You're #{{ leaderboardMini.rank }} of
                        {{ leaderboardMini.of }}
                    </p>
                </div>
                <div class="flex items-end justify-center gap-2 px-5 pt-1">
                    <div
                        v-for="e in podium"
                        :key="e.rank"
                        class="flex w-16 flex-col items-center"
                    >
                        <div
                            class="relative flex items-center justify-center overflow-hidden rounded-full font-bold text-white"
                            :class="
                                e.rank === 1
                                    ? 'h-11 w-11 text-sm'
                                    : 'h-9 w-9 text-xs'
                            "
                            :style="{
                                background: e.avatar
                                    ? 'transparent'
                                    : e.rank === 1
                                      ? 'linear-gradient(135deg, var(--gl-accent), #F59E0B)'
                                      : e.rank === 2
                                        ? 'linear-gradient(135deg, #94A3B8, #64748B)'
                                        : 'linear-gradient(135deg, #F97316, #EA580C)',
                                boxShadow:
                                    e.rank === 1
                                        ? '0 0 16px var(--gl-accent-glow)'
                                        : 'none',
                            }"
                        >
                            <img
                                v-if="e.avatar"
                                :src="e.avatar"
                                :alt="e.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else>
                                {{
                                    e.me
                                        ? (displayName || '?')
                                              .charAt(0)
                                              .toUpperCase()
                                        : e.name.charAt(0).toUpperCase()
                                }}
                            </span>
                        </div>
                        <p
                            class="mt-1 max-w-full truncate text-[10px] font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ e.me ? 'You' : e.name.split(' ')[0] }}
                        </p>
                        <p
                            class="text-[10px] tabular-nums"
                            style="color: var(--gl-text-muted)"
                        >
                            {{ e.total_points }} XP
                        </p>
                        <div
                            class="mt-1 w-full rounded-t-lg"
                            :style="{
                                height:
                                    (e.rank === 1
                                        ? 44
                                        : e.rank === 2
                                          ? 30
                                          : 20) + 'px',
                                background:
                                    e.rank === 1
                                        ? 'linear-gradient(180deg, var(--gl-accent), #F59E0B)'
                                        : 'var(--gl-surface-2)',
                                border: '1px solid var(--gl-border)',
                                borderBottom: 'none',
                            }"
                        ></div>
                    </div>
                </div>
                <div class="p-3" style="border-top: 1px solid var(--gl-border)">
                    <Link
                        href="/student/leaderboard"
                        class="flex items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-semibold transition-all hover:scale-[1.02]"
                        style="
                            background: linear-gradient(
                                135deg,
                                var(--gl-primary),
                                var(--gl-secondary)
                            );
                            color: #fff;
                            box-shadow: 0 0 12px var(--gl-primary-glow);
                        "
                    >
                        View full leaderboard
                        <ArrowRight class="h-3.5 w-3.5" :stroke-width="2" />
                    </Link>
                </div>
            </div>

            <!-- Pending nudge -->
            <div
                v-if="pendingCount > 0"
                class="flex items-center gap-3 rounded-2xl p-4"
                style="
                    background: rgba(59, 130, 246, 0.07);
                    border: 1px solid rgba(59, 130, 246, 0.18);
                "
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                    style="background: rgba(59, 130, 246, 0.14)"
                >
                    <Clock
                        class="h-4.5 w-4.5"
                        style="color: var(--gl-primary)"
                        :stroke-width="2"
                    />
                </div>
                <p
                    class="text-xs leading-relaxed"
                    style="color: var(--gl-text-secondary)"
                >
                    <span
                        class="font-bold"
                        style="color: var(--gl-text-primary)"
                        >{{ pendingCount }} activit{{
                            pendingCount === 1 ? 'y' : 'ies'
                        }}
                        pending.</span
                    >
                    Finish {{ pendingCount === 1 ? 'it' : 'them' }} to earn more
                    XP!
                </p>
            </div>
        </div>
    </div>

    <!-- Announcement Details Modal -->
    <div
        v-if="activeAnnouncement"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background: rgba(0, 0, 0, 0.65); backdrop-filter: blur(4px)"
        @click.self="closeAnnouncementModal"
    >
        <div
            class="gl-fade-in relative w-full max-w-lg overflow-hidden rounded-2xl p-6"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
                box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
            "
        >
            <div class="mb-4 flex items-start justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        v-if="activeAnnouncement.is_pinned"
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider"
                        style="
                            background: rgba(251, 191, 36, 0.18);
                            color: var(--gl-accent);
                            border: 1px solid rgba(251, 191, 36, 0.35);
                        "
                    >
                        <Pin class="h-3 w-3" :stroke-width="2.5" />
                        Pinned
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                        :style="{
                            background: getCategoryBadge(activeAnnouncement.category).bg,
                            color: getCategoryBadge(activeAnnouncement.category).color,
                            border: `1px solid ${getCategoryBadge(activeAnnouncement.category).border}`,
                        }"
                    >
                        <component
                            :is="getCategoryBadge(activeAnnouncement.category).icon"
                            class="h-3 w-3"
                            :stroke-width="2"
                        />
                        {{ getCategoryBadge(activeAnnouncement.category).label }}
                    </span>
                    <span
                        v-if="activeAnnouncement.section"
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                        style="
                            background: rgba(139, 92, 246, 0.15);
                            color: #8B5CF6;
                            border: 1px solid rgba(139, 92, 246, 0.35);
                        "
                    >
                        <Users class="h-3 w-3" :stroke-width="2" />
                        Section {{ activeAnnouncement.section.name }}
                    </span>
                    <span
                        v-else-if="activeAnnouncement.grade_level"
                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                        style="
                            background: rgba(59, 130, 246, 0.15);
                            color: #3B82F6;
                            border: 1px solid rgba(59, 130, 246, 0.35);
                        "
                    >
                        <Users class="h-3 w-3" :stroke-width="2" />
                        {{ activeAnnouncement.grade_level.name }}
                    </span>
                </div>
                <button
                    type="button"
                    @click="closeAnnouncementModal"
                    class="rounded-lg p-1.5 transition-colors hover:bg-white/10"
                    style="color: var(--gl-text-muted)"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <h3 class="text-lg font-bold" style="color: var(--gl-text-primary)">
                {{ activeAnnouncement.title }}
            </h3>

            <div
                class="mt-1.5 flex items-center gap-2 text-xs"
                style="color: var(--gl-text-muted)"
            >
                <span>By {{ activeAnnouncement.author_name }}</span>
                <span>&bull;</span>
                <span>{{ activeAnnouncement.date_formatted }}</span>
            </div>

            <div
                class="mt-4 max-h-72 overflow-y-auto whitespace-pre-line text-sm leading-relaxed"
                style="
                    color: var(--gl-text-secondary);
                    border-top: 1px solid var(--gl-border);
                    padding-top: 1rem;
                "
            >
                {{ activeAnnouncement.content }}
            </div>

            <div class="mt-6 flex justify-end">
                <button
                    type="button"
                    @click="closeAnnouncementModal"
                    class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-opacity hover:opacity-90"
                    style="background: var(--gl-primary)"
                >
                    Close Notice
                </button>
            </div>
        </div>
    </div>

    <!-- Daily Streak Freeze & Multiplier Modal -->
    <div
        v-if="showStreakModal"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px)"
        @click.self="showStreakModal = false"
    >
        <div
            class="gl-fade-in relative w-full max-w-md overflow-hidden rounded-3xl p-6 border shadow-2xl"
            style="background: var(--gl-surface); border-color: var(--gl-border);"
        >
            <div class="mb-4 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div
                        class="flex h-9 w-9 items-center justify-center rounded-xl"
                        style="background: linear-gradient(135deg, var(--gl-accent), #f59e0b); color: #fff;"
                    >
                        <Flame class="h-5 w-5" :stroke-width="2.5" />
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold" style="color: var(--gl-text-primary)">
                            Daily Streak &amp; Multipliers
                        </h3>
                        <p class="text-[11px]" style="color: var(--gl-text-muted)">
                            Build your streak to unlock higher XP rewards
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="showStreakModal = false"
                    class="rounded-lg p-1.5 transition-colors hover:bg-white/10 text-slate-400"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <!-- Current Streak Status Card -->
            <div
                class="mb-4 rounded-2xl p-4 border flex items-center justify-between"
                style="background: linear-gradient(135deg, rgba(251, 191, 36, 0.12), rgba(245, 158, 11, 0.05)); border-color: rgba(251, 191, 36, 0.3);"
            >
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-amber-500">Active Streak</span>
                    <p class="text-2xl font-black" style="color: var(--gl-text-primary)">
                        {{ currentStreak }} Days
                    </p>
                    <p class="text-xs text-amber-400/90 font-medium mt-0.5">
                        Current Boost: {{ streakMultiplierLabel }} XP
                    </p>
                </div>
                <div
                    class="flex h-12 w-12 items-center justify-center rounded-2xl"
                    style="background: rgba(245, 158, 11, 0.2); color: var(--gl-accent);"
                >
                    <Zap class="h-6 w-6" :stroke-width="2.5" />
                </div>
            </div>

            <!-- Multiplier Tiers Table -->
            <div class="mb-4 rounded-2xl border p-3" style="background: var(--gl-surface-2); border-color: var(--gl-border);">
                <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 block mb-2">
                    XP Multiplier Tiers
                </span>
                <div class="space-y-1.5 text-xs">
                    <div
                        v-for="tier in [
                            { days: '1 - 2 Days', mult: '1.0x XP', active: currentStreak >= 1 && currentStreak < 3 },
                            { days: '3 - 4 Days', mult: '1.2x XP', active: currentStreak >= 3 && currentStreak < 5 },
                            { days: '5 - 6 Days', mult: '1.5x XP', active: currentStreak >= 5 && currentStreak < 7 },
                            { days: '7+ Days', mult: '2.0x XP Boost', active: currentStreak >= 7 },
                        ]"
                        :key="tier.days"
                        class="flex items-center justify-between p-2 rounded-xl border transition-all"
                        :style="
                            tier.active
                                ? 'background: rgba(245, 158, 11, 0.15); border-color: rgba(245, 158, 11, 0.4); color: var(--gl-text-primary); font-weight: bold;'
                                : 'background: transparent; border-color: transparent; color: var(--gl-text-secondary);'
                        "
                    >
                        <span>{{ tier.days }}</span>
                        <span class="flex items-center gap-1.5 font-bold" :class="tier.active ? 'text-amber-400' : 'text-slate-400'">
                            {{ tier.mult }}
                            <CheckCircle2 v-if="tier.active" class="h-3.5 w-3.5 text-amber-400" />
                        </span>
                    </div>
                </div>
            </div>

            <!-- Streak Freeze Shield Control Card -->
            <div
                class="rounded-2xl border p-4 mb-5"
                :style="
                    isStreakFreezeActive
                        ? 'background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.35);'
                        : 'background: var(--gl-surface-2); border-color: var(--gl-border);'
                "
            >
                <div class="flex items-start justify-between gap-3 mb-2">
                    <div class="flex items-center gap-2">
                        <ShieldCheck v-if="isStreakFreezeActive" class="h-5 w-5 text-emerald-400 shrink-0" />
                        <ShieldAlert v-else class="h-5 w-5 text-amber-400 shrink-0" />
                        <div>
                            <h4 class="font-bold text-xs" style="color: var(--gl-text-primary)">
                                Streak Freeze Shield
                            </h4>
                            <p class="text-[11px]" style="color: var(--gl-text-muted)">
                                {{ isStreakFreezeActive ? 'Your streak is shielded from resetting today.' : 'Equip a shield to prevent streak loss if you miss a day.' }}
                            </p>
                        </div>
                    </div>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-extrabold uppercase shrink-0"
                        :style="isStreakFreezeActive ? 'background: rgba(16, 185, 129, 0.2); color: #34d399;' : 'background: rgba(245, 158, 11, 0.15); color: #f59e0b;'"
                    >
                        {{ freezeShieldsCount }} in inventory
                    </span>
                </div>

                <div class="mt-3 pt-3 border-t flex justify-end" style="border-color: var(--gl-border);">
                    <button
                        type="button"
                        :disabled="freezingStreak || (!isStreakFreezeActive && freezeShieldsCount <= 0)"
                        @click="toggleStreakFreeze"
                        class="flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-bold text-white transition-all disabled:opacity-40"
                        :style="
                            isStreakFreezeActive
                                ? 'background: linear-gradient(135deg, #64748b, #475569);'
                                : 'background: linear-gradient(135deg, #10b981, #059669); box-shadow: 0 0 10px rgba(16, 185, 129, 0.3);'
                        "
                    >
                        <ShieldCheck class="h-3.5 w-3.5" />
                        {{ isStreakFreezeActive ? 'Deactivate Shield' : 'Activate Streak Freeze' }}
                    </button>
                </div>
            </div>

            <div class="flex justify-end">
                <button
                    type="button"
                    @click="showStreakModal = false"
                    class="rounded-xl px-4 py-2 text-xs font-semibold text-white transition-opacity hover:opacity-90"
                    style="background: var(--gl-primary);"
                >
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Badge Detail Modal -->
    <div
        v-if="activeBadge"
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="background: rgba(0, 0, 0, 0.7); backdrop-filter: blur(4px)"
        @click.self="closeBadgeModal"
    >
        <div
            class="gl-fade-in relative w-full max-w-sm overflow-hidden rounded-3xl p-6 border shadow-2xl text-center"
            style="background: var(--gl-surface); border-color: var(--gl-border);"
        >
            <button
                type="button"
                @click="closeBadgeModal"
                class="absolute top-4 right-4 rounded-lg p-1.5 transition-colors hover:bg-white/10 text-slate-400"
            >
                <X class="h-4 w-4" />
            </button>

            <!-- Large Badge Icon -->
            <div
                class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-3xl shadow-xl transition-transform"
                :style="
                    activeBadge.unlocked
                        ? {
                              background: 'linear-gradient(135deg, #F59E0B, #EC4899)',
                              boxShadow: '0 0 25px rgba(245, 158, 11, 0.4)',
                              color: '#fff',
                          }
                        : {
                              background: 'var(--gl-surface-2)',
                              border: '1px solid var(--gl-border)',
                              color: 'var(--gl-text-muted)',
                          }
                "
            >
                <component :is="getBadgeIcon(activeBadge.icon)" class="h-10 w-10" :stroke-width="2.2" />
            </div>

            <span
                class="inline-block rounded-full px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider mb-2"
                :style="
                    activeBadge.unlocked
                        ? 'background: rgba(16, 185, 129, 0.15); color: var(--gl-success); border: 1px solid rgba(16, 185, 129, 0.3);'
                        : 'background: var(--gl-surface-2); color: var(--gl-text-muted); border: 1px solid var(--gl-border);'
                "
            >
                {{ activeBadge.unlocked ? 'Unlocked' : 'Locked' }} · {{ activeBadge.category }}
            </span>

            <h3 class="text-lg font-black" style="color: var(--gl-text-primary)">
                {{ activeBadge.name }}
            </h3>
            <p class="mt-2 text-xs leading-relaxed" style="color: var(--gl-text-secondary)">
                {{ activeBadge.desc }}
            </p>

            <div
                class="mt-4 rounded-2xl border p-3 flex items-center justify-between text-xs"
                style="background: var(--gl-surface-2); border-color: var(--gl-border);"
            >
                <span class="text-slate-400">Reward</span>
                <span class="font-extrabold text-amber-400">+{{ activeBadge.reward_xp }} XP</span>
            </div>

            <button
                type="button"
                @click="closeBadgeModal"
                class="mt-5 w-full rounded-xl py-2.5 text-xs font-bold text-white transition-opacity hover:opacity-90"
                style="background: var(--gl-primary);"
            >
                Done
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import {
    FileQuestion,
    FileText,
    ClipboardList,
    FlaskConical,
    Zap,
    Award,
    Trophy,
    ArrowRight,
    Clock,
    Flame,
    Target,
    Play,
    Megaphone,
    Pin,
    CheckSquare,
    AlertTriangle,
    BookOpen,
    BellRing,
    Calendar,
    Trash2,
    CheckCircle2,
    X,
    ShieldCheck,
    ShieldAlert,
    Timer,
    Lock,
    Sparkles,
    Gamepad2,
    HelpCircle,
    Copy,
    Check,
    Users,
} from '@lucide/vue';
import { ref, computed, onMounted } from 'vue';
import { toast } from 'vue-sonner';
import PctBadge from '@/components/PctBadge.vue';
import { levelForPoints, titleForLevel } from '@/lib/levels';

const props = defineProps<{
    overallAvg: number;
    typeAverages: Record<string, number>;
    pendingCount: number;
    recentResults: {
        type: string;
        title: string;
        score: number;
        total: number;
        pct: number;
        submitted_at: string;
    }[];
    stats: {
        quizzes: { total: number; submitted: number };
        exams: { total: number; submitted: number };
        seatworks: { total: number; submitted: number };
        practicals: { total: number; submitted: number };
    };
    leaderboardMini: {
        rank: number;
        of: number;
        top: {
            id: number;
            name: string;
            avatar?: string | null;
            total_points: number;
            rank: number;
            me: boolean;
        }[];
    };
    announcements?: {
        id: number;
        title: string;
        content: string;
        category: string;
        is_pinned: boolean;
        author_name: string;
        author_role: string;
        created_at: string;
        date_formatted: string;
        time_ago: string;
        grade_level?: { id: number; name: string } | null;
        section?: { id: number; name: string } | null;
    }[];
    todos?: {
        id: string;
        activity_id: number;
        title: string;
        type: string;
        href: string;
        closes_at: string | null;
        due_text: string;
        due_date: string | null;
        time_limit: number | null;
        is_urgent: boolean;
    }[];
    streakInfo?: {
        streak: number;
        multiplier: number;
        multiplier_label: string;
        freeze_count: number;
        freeze_active: boolean;
    };
    assessmentsCountdown?: {
        id: string;
        title: string;
        type: string;
        href: string;
        closes_at: string;
        due_date: string;
        seconds_left: number;
        days_left: number;
        hours_left: number;
        minutes_left: number;
        urgency: 'urgent' | 'warning' | 'normal';
        time_limit: number | null;
    }[];
    badges?: {
        id: string;
        name: string;
        desc: string;
        category: string;
        unlocked: boolean;
        reward_xp: number;
        icon: string;
    }[];
    activeLiveRooms?: {
        id: number;
        game_id: number;
        teacher_id: number;
        pin: string;
        status: string;
        participants_count?: number;
        game?: {
            id: number;
            title: string;
            type: string;
            subject: string;
            grade?: string;
        };
        teacher?: {
            id: number;
            name: string;
        };
    }[];
    activePoll?: {
        id: number;
        question: string;
        total_votes: number;
        user_voted: boolean;
        user_vote_index: number | null;
        options: {
            index: number;
            text: string;
            votes: number;
            pct: number;
            is_selected: boolean;
        }[];
    } | null;
}>();

const page = usePage();
const flash = page.props.flash as any;

const displayName = computed(
    () => (page.props as any).auth?.user?.name ?? 'Learner',
);
const firstName = computed(() => displayName.value.split(' ')[0]);

// === Live Arena Actions ===
const copiedLivePin = ref<string | null>(null);
function copyLiveRoomPin(pin: string) {
    navigator.clipboard.writeText(pin);
    copiedLivePin.value = pin;
    toast.success(`Game PIN ${pin} copied to clipboard!`);
    setTimeout(() => {
        if (copiedLivePin.value === pin) {
copiedLivePin.value = null;
}
    }, 2000);
}



// === Streak & Shield State & Logic ===
const currentStreak = computed(() => props.streakInfo?.streak ?? streakDots.value ?? 1);
const streakMultiplier = computed(() => props.streakInfo?.multiplier ?? 1.0);
const streakMultiplierLabel = computed(() => props.streakInfo?.multiplier_label ?? '1.0x');
const freezeShieldsCount = computed(() => props.streakInfo?.freeze_count ?? 2);
const isStreakFreezeActive = computed(() => props.streakInfo?.freeze_active ?? false);

const showStreakModal = ref(false);
const freezingStreak = ref(false);

function toggleStreakFreeze() {
    freezingStreak.value = true;
    router.post('/student/streak/freeze', {}, {
        preserveScroll: true,
        onFinish: () => {
            freezingStreak.value = false;
        },
    });
}

// === Achievement Badges State & Logic ===
const badgesList = computed(() => props.badges ?? []);
const unlockedBadgesCount = computed(() => badgesList.value.filter((b) => b.unlocked).length);
const activeBadge = ref<any | null>(null);

function openBadgeModal(b: any) {
    activeBadge.value = b;
}

function closeBadgeModal() {
    activeBadge.value = null;
}

function getBadgeIcon(iconName: string) {
    switch (iconName) {
        case 'Rocket':
        case 'Sparkles':
            return Sparkles;
        case 'Zap':
            return Zap;
        case 'Award':
            return Award;
        case 'Trophy':
            return Trophy;
        case 'Flame':
            return Flame;
        case 'Gamepad2':
            return Gamepad2;
        default:
            return Award;
    }
}

// === Quick Class Poll State & Logic ===
const votingPoll = ref(false);
const showChangeVote = ref(false);

function submitPollVote(optionIndex: number) {
    if (!props.activePoll) {
return;
}

    votingPoll.value = true;
    router.post(`/student/polls/${props.activePoll.id}/vote`, {
        option_index: optionIndex,
    }, {
        preserveScroll: true,
        onFinish: () => {
            votingPoll.value = false;
            showChangeVote.value = false;
        },
    });
}

// === Announcements State & Logic ===
const announcementFilter = ref<'all' | 'pinned' | 'section' | 'academic' | 'reminder'>('all');
const activeAnnouncement = ref<any | null>(null);

const filteredAnnouncements = computed(() => {
    const list = props.announcements ?? [];

    if (announcementFilter.value === 'pinned') {
        return list.filter((a) => a.is_pinned);
    }

    if (announcementFilter.value === 'section') {
        return list.filter((a) => !!a.section);
    }

    if (announcementFilter.value === 'academic') {
        return list.filter((a) => a.category === 'academic');
    }

    if (announcementFilter.value === 'reminder') {
        return list.filter((a) => a.category === 'reminder' || a.category === 'urgent');
    }

    return list;
});

function openAnnouncementModal(item: any) {
    activeAnnouncement.value = item;
}

function closeAnnouncementModal() {
    activeAnnouncement.value = null;
}

function getCategoryBadge(category: string) {
    switch (category) {
        case 'urgent':
            return {
                label: 'Urgent',
                color: 'var(--gl-danger)',
                bg: 'rgba(239, 68, 68, 0.12)',
                border: 'rgba(239, 68, 68, 0.25)',
                icon: AlertTriangle,
            };
        case 'academic':
            return {
                label: 'Academic',
                color: 'var(--gl-primary)',
                bg: 'rgba(59, 130, 246, 0.12)',
                border: 'rgba(59, 130, 246, 0.25)',
                icon: BookOpen,
            };
        case 'reminder':
            return {
                label: 'Reminder',
                color: 'var(--gl-accent)',
                bg: 'rgba(251, 191, 36, 0.12)',
                border: 'rgba(251, 191, 36, 0.25)',
                icon: BellRing,
            };
        case 'event':
            return {
                label: 'Event',
                color: '#a855f7',
                bg: 'rgba(168, 85, 247, 0.12)',
                border: 'rgba(168, 85, 247, 0.25)',
                icon: Calendar,
            };
        default:
            return {
                label: 'General',
                color: 'var(--gl-success)',
                bg: 'rgba(16, 185, 129, 0.12)',
                border: 'rgba(16, 185, 129, 0.25)',
                icon: Megaphone,
            };
    }
}

// === To-Do State & Logic ===
interface PersonalTask {
    id: string;
    text: string;
    completed: boolean;
    createdAt: string;
}

const todoTab = ref<'assignments' | 'checklist'>('assignments');
const personalTasks = ref<PersonalTask[]>([]);
const newTaskText = ref('');
const checklistFilter = ref<'all' | 'pending' | 'completed'>('all');

const currentUserId = computed(() => (page.props as any).auth?.user?.id ?? 'guest');

function loadPersonalTasks() {
    try {
        const key = `edupulse_student_todos_${currentUserId.value}`;
        const stored = localStorage.getItem(key);

        if (stored) {
            personalTasks.value = JSON.parse(stored);
        } else {
            personalTasks.value = [
                {
                    id: 'sample_1',
                    text: 'Review lecture notes on digital arts principles',
                    completed: false,
                    createdAt: new Date().toISOString(),
                },
                {
                    id: 'sample_2',
                    text: 'Check practical activity rubric requirements',
                    completed: true,
                    createdAt: new Date().toISOString(),
                },
            ];
            savePersonalTasks();
        }
    } catch {
        personalTasks.value = [];
    }
}

function savePersonalTasks() {
    try {
        const key = `edupulse_student_todos_${currentUserId.value}`;
        localStorage.setItem(key, JSON.stringify(personalTasks.value));
    } catch {
        // ignore storage errors
    }
}

function addPersonalTask() {
    const text = newTaskText.value.trim();

    if (!text) {
return;
}

    personalTasks.value.unshift({
        id: 'task_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7),
        text,
        completed: false,
        createdAt: new Date().toISOString(),
    });
    newTaskText.value = '';
    savePersonalTasks();
}

function togglePersonalTask(task: PersonalTask) {
    task.completed = !task.completed;
    savePersonalTasks();
}

function deletePersonalTask(taskId: string) {
    personalTasks.value = personalTasks.value.filter((t) => t.id !== taskId);
    savePersonalTasks();
}

function clearCompletedTasks() {
    personalTasks.value = personalTasks.value.filter((t) => !t.completed);
    savePersonalTasks();
}

function dismissAssignmentItem(item: any) {
    if (!confirm(`Dismiss "${item.title}" from your to-do list?`)) {
return;
}

    router.post(
        '/student/todos/dismiss',
        { todo_key: item.dismiss_key || item.id },
        { preserveScroll: true }
    );
}

function toggleAssignmentItem(item: any) {
    if (!item.toggle_url) {
return;
}

    router.post(
        item.toggle_url,
        {},
        { preserveScroll: true }
    );
}

const filteredPersonalTasks = computed(() => {
    if (checklistFilter.value === 'pending') {
        return personalTasks.value.filter((t) => !t.completed);
    }

    if (checklistFilter.value === 'completed') {
        return personalTasks.value.filter((t) => t.completed);
    }

    return personalTasks.value;
});

const assignmentTodos = computed(() => props.todos ?? []);

const totalOpenTodosCount = computed(() => {
    const pendingAssignments = assignmentTodos.value.length;
    const pendingPersonal = personalTasks.value.filter((t) => !t.completed).length;

    return pendingAssignments + pendingPersonal;
});

const personalCompletedCount = computed(
    () => personalTasks.value.filter((t) => t.completed).length,
);

function getTodoTypeBadge(type: string) {
    switch (type) {
        case 'Quiz':
            return {
                icon: FileQuestion,
                color: '#3B82F6',
                bg: 'rgba(59, 130, 246, 0.12)',
            };
        case 'Exam':
            return {
                icon: FileText,
                color: '#8B5CF6',
                bg: 'rgba(139, 92, 246, 0.12)',
            };
        case 'Seatwork':
            return {
                icon: ClipboardList,
                color: '#10B981',
                bg: 'rgba(16, 185, 129, 0.12)',
            };
        case 'Practical':
            return {
                icon: FlaskConical,
                color: '#F59E0B',
                bg: 'rgba(245, 158, 11, 0.12)',
            };
        case 'Material':
            return {
                icon: BookOpen,
                color: '#EC4899',
                bg: 'rgba(236, 72, 153, 0.12)',
            };
        case 'Task':
            return {
                icon: CheckSquare,
                color: '#10B981',
                bg: 'rgba(16, 185, 129, 0.12)',
            };
        default:
            return {
                icon: FileText,
                color: 'var(--gl-primary)',
                bg: 'rgba(59, 130, 246, 0.12)',
            };
    }
}

onMounted(() => {
    loadPersonalTasks();
});

const totalPoints = computed(() => {
    const user = (page.props as any).auth?.user;

    return user?.total_points ?? 0;
});

const userLevel = computed(() => levelForPoints(totalPoints.value));
const levelTitle = computed(() => titleForLevel(userLevel.value));

const streakDots = computed(() => {
    const r = props.recentResults;
    const recent = r.slice(0, 7);

    return recent.filter(
        (x) => x.pct !== null && x.pct !== undefined && x.pct >= 60,
    ).length;
});

const subjectRows = computed(() => {
    const defs = [
        {
            key: 'quizzes',
            label: 'Quizzes',
            href: '/student/quizzes',
            icon: FileQuestion,
            bg: 'rgba(59,130,246,0.12)',
            color: '#3B82F6',
            bar: 'linear-gradient(90deg, var(--gl-primary), var(--gl-secondary))',
            done: props.stats.quizzes.submitted,
            total: props.stats.quizzes.total,
            avg: props.typeAverages.Quiz ?? 0,
        },
        {
            key: 'exams',
            label: 'Exams',
            href: '/student/exams',
            icon: FileText,
            bg: 'rgba(124,58,237,0.12)',
            color: '#7C3AED',
            bar: 'linear-gradient(90deg, var(--gl-secondary), #A855F7)',
            done: props.stats.exams.submitted,
            total: props.stats.exams.total,
            avg: props.typeAverages.Exam ?? 0,
        },
        {
            key: 'seatworks',
            label: 'Seatworks',
            href: '/student/seatworks',
            icon: ClipboardList,
            bg: 'rgba(16,185,129,0.12)',
            color: '#10B981',
            bar: 'linear-gradient(90deg, var(--gl-success), #34D399)',
            done: props.stats.seatworks.submitted,
            total: props.stats.seatworks.total,
            avg: props.typeAverages.Seatwork ?? 0,
        },
        {
            key: 'practicals',
            label: 'Practicals',
            href: '/student/practicals',
            icon: FlaskConical,
            bg: 'rgba(251,191,36,0.12)',
            color: '#FBBF24',
            bar: 'linear-gradient(90deg, #F59E0B, #F97316)',
            done: props.stats.practicals.submitted,
            total: props.stats.practicals.total,
            avg: props.typeAverages.Practical ?? 0,
        },
    ];

    return defs.map((d) => ({
        ...d,
        pct: d.total > 0 ? Math.round((d.done / d.total) * 100) : 0,
    }));
});

const challenge = computed(() => {
    const order = [
        { key: 'quizzes', label: 'Quizzes', href: '/student/quizzes' },
        { key: 'seatworks', label: 'Seatworks', href: '/student/seatworks' },
        { key: 'practicals', label: 'Practicals', href: '/student/practicals' },
        { key: 'exams', label: 'Exams', href: '/student/exams' },
    ];

    for (const o of order) {
        const s = (props.stats as any)[o.key];

        if (s && s.total > s.submitted) {
            const left = s.total - s.submitted;

            return {
                title: `${left} open ${o.label.toLowerCase()}`,
                sub: `Finish ${left === 1 ? 'it' : 'them'} to earn XP`,
                href: o.href,
            };
        }
    }

    return {
        title: 'All caught up!',
        sub: 'Review your results',
        href: '/student/results',
    };
});

const weekDots = computed(() => {
    const days: { label: string; dow: string; count: number }[] = [];
    const now = new Date();
    const dows = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    for (let i = 6; i >= 0; i--) {
        const d = new Date(
            now.getFullYear(),
            now.getMonth(),
            now.getDate() - i,
        );
        const key = `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`;
        const count = props.recentResults.filter((r) => {
            if (!r.submitted_at) {
return false;
}

            const s = new Date(r.submitted_at);

            return `${s.getFullYear()}-${s.getMonth()}-${s.getDate()}` === key;
        }).length;
        days.push({
            label: d.toLocaleDateString(undefined, {
                month: 'short',
                day: 'numeric',
            }),
            dow: dows[d.getDay()],
            count,
        });
    }

    return days;
});

const weekTotal = computed(() =>
    weekDots.value.reduce((a, d) => a + d.count, 0),
);

const podium = computed(() => {
    const top = [...(props.leaderboardMini?.top ?? [])];
    const get = (rank: number) => top.find((e) => e.rank === rank);

    return [get(2), get(1), get(3)].filter(
        (e): e is (typeof top)[number] => e != null,
    );
});

const RING = 2 * Math.PI * 34;

const ringOff = computed(
    () => RING - (Math.min(100, props.overallAvg) / 100) * RING,
);

function badgeClass(type: string): string {
    const map: Record<string, string> = {
        Quiz: 'bg-[rgba(59,130,246,0.12)] text-[#3B82F6]',
        Exam: 'bg-[rgba(251,191,36,0.12)] text-[#FBBF24]',
        Seatwork: 'bg-[rgba(16,185,129,0.12)] text-[#10B981]',
        Practical: 'bg-[rgba(239,68,68,0.12)] text-[#EF4444]',
    };

    return (
        map[type] ?? 'bg-[var(--gl-surface-2)] text-[var(--gl-text-secondary)]'
    );
}
</script>

<style scoped>
.card a {
    text-decoration: none;
}
.h-4\.5 {
    height: 1.125rem;
}
.w-4\.5 {
    width: 1.125rem;
}
</style>
