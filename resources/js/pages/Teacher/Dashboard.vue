<template>
    <Head title="Teacher Dashboard" />

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
                class="mt-1 flex flex-wrap items-center gap-2 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Hello {{ firstName }}!
                <span
                    v-if="isSuperadmin"
                    class="rounded-full px-2.5 py-1 text-[11px] font-bold"
                    style="
                        background: rgba(251, 191, 36, 0.14);
                        color: var(--gl-accent);
                        border: 1px solid rgba(251, 191, 36, 0.3);
                    "
                >
                    Superadmin
                </span>
                <span
                    v-else
                    class="rounded-full px-2.5 py-1 text-[11px] font-bold"
                    style="
                        background: rgba(59, 130, 246, 0.1);
                        color: var(--gl-primary);
                        border: 1px solid rgba(59, 130, 246, 0.25);
                    "
                >
                    Teacher
                </span>
            </h1>
        </div>
        <div
            class="flex items-center gap-2.5 rounded-2xl px-4 py-2.5"
            style="
                background: linear-gradient(
                    135deg,
                    rgba(16, 185, 129, 0.12),
                    rgba(59, 130, 246, 0.08)
                );
                border: 1px solid rgba(16, 185, 129, 0.25);
            "
        >
            <TrendingUp
                class="h-5 w-5 shrink-0"
                style="color: var(--gl-success)"
                :stroke-width="2"
            />
            <p
                class="text-xs font-medium"
                style="color: var(--gl-text-secondary)"
            >
                <span class="font-bold" style="color: var(--gl-text-primary)"
                    >{{ thisWeek }} submissions this week</span
                >
                — keep the momentum!
            </p>
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
                        background: rgba(59, 130, 246, 0.08);
                        border: 1px solid rgba(59, 130, 246, 0.18);
                    "
                >
                    <div
                        class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                        style="background: rgba(59, 130, 246, 0.16)"
                    >
                        <ClipboardList
                            class="h-[18px] w-[18px]"
                            style="color: var(--gl-primary)"
                            :stroke-width="2"
                        />
                    </div>
                    <p
                        class="text-xl font-extrabold tabular-nums"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ totalSubmissions.toLocaleString() }}
                    </p>
                    <p class="text-xs" style="color: var(--gl-text-secondary)">
                        Submissions
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
                        <Users
                            class="h-[18px] w-[18px]"
                            style="color: var(--gl-success)"
                            :stroke-width="2"
                        />
                    </div>
                    <p
                        class="text-xl font-extrabold tabular-nums"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ studentCount }}
                    </p>
                    <p class="text-xs" style="color: var(--gl-text-secondary)">
                        Students
                    </p>
                </div>
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
                        <Target
                            class="h-[18px] w-[18px]"
                            style="color: var(--gl-accent)"
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
                        Overall average
                    </p>
                </div>
                <div
                    class="rounded-2xl p-4 transition-all hover:-translate-y-0.5"
                    style="
                        background: rgba(124, 58, 237, 0.08);
                        border: 1px solid rgba(124, 58, 237, 0.18);
                    "
                >
                    <div
                        class="mb-2 flex h-9 w-9 items-center justify-center rounded-xl"
                        style="background: rgba(124, 58, 237, 0.16)"
                    >
                        <Layers
                            class="h-[18px] w-[18px]"
                            style="color: var(--gl-secondary)"
                            :stroke-width="2"
                        />
                    </div>
                    <p
                        class="text-xl font-extrabold tabular-nums"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ totalActivities }}
                    </p>
                    <p class="text-xs" style="color: var(--gl-text-secondary)">
                        Activities
                    </p>
                </div>
            </div>

            <!-- Student Pass Rates & Mastery Distribution -->
            <div class="gl-glow-card p-5 sm:p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-bold" style="color: var(--gl-text-primary)">
                                Student Pass Rates & Mastery
                            </h3>
                            <span
                                class="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider"
                                :style="{
                                    background: (passRateMetrics?.passRate ?? 0) >= 75 ? 'rgba(16, 185, 129, 0.15)' : 'rgba(245, 158, 11, 0.15)',
                                    color: (passRateMetrics?.passRate ?? 0) >= 75 ? 'var(--gl-success)' : 'var(--gl-accent)',
                                    border: (passRateMetrics?.passRate ?? 0) >= 75 ? '1px solid rgba(16, 185, 129, 0.3)' : '1px solid rgba(245, 158, 11, 0.3)'
                                }"
                            >
                                {{ (passRateMetrics?.passRate ?? 0) >= 75 ? 'Strong Performance' : 'Remediation Alert' }}
                            </span>
                        </div>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            Passing threshold: {{ passRateMetrics?.passingThreshold ?? 60 }}% minimum &bull; {{ passRateMetrics?.totalGraded ?? 0 }} total graded submissions
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold" style="background: rgba(16, 185, 129, 0.1); color: var(--gl-success)">
                            <CircleCheck class="h-3.5 w-3.5" :stroke-width="2.5" />
                            <span>{{ passRateMetrics?.passedCount ?? 0 }} Passed</span>
                        </div>
                        <div class="flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold" style="background: rgba(239, 68, 68, 0.1); color: var(--gl-danger)">
                            <CircleAlert class="h-3.5 w-3.5" :stroke-width="2.5" />
                            <span>{{ passRateMetrics?.failedCount ?? 0 }} Need Review</span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-12 items-center">
                    <!-- Circular Pass Rate Gauge -->
                    <div class="flex flex-col items-center justify-center md:col-span-4 p-3 rounded-2xl" style="background: var(--gl-surface-2); border: 1px solid var(--gl-border)">
                        <div class="relative h-28 w-28">
                            <svg viewBox="0 0 100 100" class="h-full w-full -rotate-90">
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="40"
                                    fill="none"
                                    stroke-width="10"
                                    style="stroke: rgba(148, 163, 184, 0.2)"
                                />
                                <circle
                                    cx="50"
                                    cy="50"
                                    r="40"
                                    fill="none"
                                    stroke-width="10"
                                    stroke-linecap="round"
                                    :stroke-dasharray="2 * Math.PI * 40"
                                    :stroke-dashoffset="(2 * Math.PI * 40) - (((passRateMetrics?.passRate ?? 0) / 100) * (2 * Math.PI * 40))"
                                    :style="{
                                        stroke: (passRateMetrics?.passRate ?? 0) >= 75 ? 'var(--gl-success)' : ((passRateMetrics?.passRate ?? 0) >= 60 ? '#f59e0b' : '#ef4444'),
                                        transition: 'stroke-dashoffset 0.8s ease'
                                    }"
                                />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="text-xl font-extrabold tabular-nums" style="color: var(--gl-text-primary)">
                                    {{ passRateMetrics?.passRate ?? 0 }}%
                                </span>
                                <span class="text-[10px] font-bold uppercase tracking-wider" style="color: var(--gl-text-muted)">
                                    Pass Rate
                                </span>
                            </div>
                        </div>
                        <p class="mt-2 text-center text-[11px] font-medium" style="color: var(--gl-text-secondary)">
                            {{ passRateMetrics?.passedCount ?? 0 }} of {{ passRateMetrics?.totalGraded ?? 0 }} submissions meet standard
                        </p>
                    </div>

                    <!-- Tier Breakdown Bars -->
                    <div class="space-y-2.5 md:col-span-8">
                        <div v-for="tier in (passRateMetrics?.tiers ?? [])" :key="tier.label" class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-semibold" style="color: var(--gl-text-primary)">{{ tier.label }}</span>
                                <span class="font-bold tabular-nums" :style="{ color: tier.color }">
                                    {{ tier.count }} ({{ tier.pct }}%)
                                </span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full" style="background: var(--gl-surface-2)">
                                <div
                                    class="h-full rounded-full transition-all duration-500"
                                    :style="{
                                        width: tier.pct + '%',
                                        background: tier.color
                                    }"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Activity mix -->
            <div class="gl-glow-card p-5 sm:p-6">
                <div class="mb-4 flex items-center justify-between gap-3">
                    <div>
                        <h3
                            class="text-base font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            Activity Mix
                        </h3>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            Submissions and averages per type
                        </p>
                    </div>
                    <Link
                        href="/teacher/results"
                        class="flex items-center gap-1 text-xs font-semibold transition-colors hover:opacity-80"
                        style="color: var(--gl-primary)"
                    >
                        Results
                        <ArrowRight class="h-3.5 w-3.5" :stroke-width="2" />
                    </Link>
                </div>
                <div class="space-y-4">
                    <Link
                        v-for="s in typeRows"
                        :key="s.key"
                        href="/teacher/results"
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
                                        class="h-[18px] w-[18px]"
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
                                        {{ s.count }} submissions · {{ s.avg }}%
                                        avg
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

            <!-- Quick create + efficiency -->
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
                                Quick Create
                            </p>
                            <p
                                class="mt-0.5 truncate text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                New quiz or activity
                            </p>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-secondary)"
                            >
                                Build it in seconds
                            </p>
                        </div>
                        <Link
                            href="/teacher/quizzes/create"
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-white transition-all hover:scale-105"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 0 16px var(--gl-primary-glow);
                            "
                            title="Create a quiz"
                        >
                            <Plus class="h-5 w-5" :stroke-width="2.5" />
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
                                    stroke: var(--gl-success);
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
                            Class Mastery
                        </p>
                        <p
                            class="mt-0.5 text-xs leading-relaxed"
                            style="color: var(--gl-text-secondary)"
                        >
                            Overall average across all graded submissions.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Score trend -->
            <div class="gl-glow-card p-5 sm:p-6">
                <div class="mb-1 flex items-center justify-between gap-3">
                    <div>
                        <h3
                            class="text-base font-bold"
                            style="color: var(--gl-text-primary)"
                        >
                            Score Trend
                        </h3>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            8-week average: {{ trendAvg }}%
                        </p>
                    </div>
                    <span
                        v-if="bestWeek"
                        class="shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold"
                        style="
                            background: rgba(251, 191, 36, 0.12);
                            color: var(--gl-accent);
                        "
                    >
                        Peak: {{ bestWeek.label }}
                    </span>
                </div>
                <svg
                    viewBox="0 0 300 140"
                    class="mt-3 w-full"
                    style="height: 10rem"
                    preserveAspectRatio="none"
                >
                    <defs>
                        <linearGradient
                            id="trendFill"
                            x1="0"
                            y1="0"
                            x2="0"
                            y2="1"
                        >
                            <stop
                                offset="0%"
                                style="
                                    stop-color: var(--gl-success);
                                    stop-opacity: 0.35;
                                "
                            />
                            <stop
                                offset="100%"
                                style="
                                    stop-color: var(--gl-success);
                                    stop-opacity: 0;
                                "
                            />
                        </linearGradient>
                    </defs>
                    <line
                        x1="0"
                        y1="35"
                        x2="300"
                        y2="35"
                        style="stroke: var(--gl-border); stroke-dasharray: 3 4"
                    />
                    <line
                        x1="0"
                        y1="70"
                        x2="300"
                        y2="70"
                        style="stroke: var(--gl-border); stroke-dasharray: 3 4"
                    />
                    <line
                        x1="0"
                        y1="105"
                        x2="300"
                        y2="105"
                        style="stroke: var(--gl-border); stroke-dasharray: 3 4"
                    />
                    <polygon :points="trendArea" fill="url(#trendFill)" />
                    <polyline
                        :points="trendLine"
                        fill="none"
                        stroke-width="2.5"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        style="stroke: var(--gl-success)"
                    />
                    <circle
                        v-for="(p, i) in trendDots"
                        :key="i"
                        :cx="p.x"
                        :cy="p.y"
                        r="3.5"
                        style="
                            fill: var(--gl-surface);
                            stroke: var(--gl-success);
                            stroke-width: 2.5;
                        "
                    >
                        <title>
                            {{ weeklyTrends[i].label }}:
                            {{ weeklyTrends[i].average }}%
                        </title>
                    </circle>
                </svg>
                <div
                    class="mt-1 flex justify-between text-[10px]"
                    style="color: var(--gl-text-muted)"
                >
                    <span>{{ weeklyTrends[0]?.label }}</span>
                    <span>{{
                        weeklyTrends[weeklyTrends.length - 1]?.label
                    }}</span>
                </div>
            </div>

            <!-- Recent activity -->
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
                            Recent Activity
                        </h3>
                        <Link
                            v-if="recentActivity.length"
                            href="/teacher/results"
                            class="flex items-center gap-1 text-xs font-semibold transition-colors hover:opacity-80"
                            style="color: var(--gl-primary)"
                        >
                            View all
                            <ArrowRight class="h-3.5 w-3.5" :stroke-width="2" />
                        </Link>
                    </div>
                </div>
                <div
                    v-if="!recentActivity.length"
                    class="flex flex-col items-center justify-center px-6 py-10 text-center"
                >
                    <p
                        class="text-sm font-medium"
                        style="color: var(--gl-text-secondary)"
                    >
                        No activity yet
                    </p>
                    <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                        Submissions will appear here once students complete
                        activities.
                    </p>
                    <Link
                        href="/teacher/quizzes/create"
                        class="mt-4 flex items-center gap-1.5 rounded-xl px-4 py-2 text-xs font-semibold text-white transition-all hover:scale-[1.03]"
                        style="
                            background: linear-gradient(
                                135deg,
                                var(--gl-primary),
                                var(--gl-secondary)
                            );
                            box-shadow: 0 0 12px var(--gl-primary-glow);
                        "
                    >
                        <Plus class="h-3.5 w-3.5" :stroke-width="2" /> Create
                        your first quiz
                    </Link>
                </div>
                <ul
                    v-else
                    class="divide-y"
                    style="border-color: var(--gl-border)"
                >
                    <li
                        v-for="r in recentActivity"
                        :key="r.submitted_at + r.title + r.student"
                        class="flex items-center justify-between gap-3 px-5 py-3 transition-colors hover:bg-[rgba(59,130,246,0.03)]"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <PctBadge :value="r.pct" />
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-medium"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ r.student }}
                                </p>
                                <div
                                    class="mt-0.5 flex flex-wrap items-center gap-2"
                                >
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                                        :class="badgeClass(r.type)"
                                        >{{ r.type }}</span
                                    >
                                    <span
                                        class="truncate text-[11px]"
                                        style="color: var(--gl-text-muted)"
                                        >{{ r.title }} · {{ r.score }}/{{
                                            r.total
                                        }}</span
                                    >
                                </div>
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
                            >{{ r.pct }}%</span
                        >
                    </li>
                </ul>
            </div>
        </div>

        <!-- RIGHT (1/3) -->
        <div class="space-y-5">
            <!-- Activity Completion Trends -->
            <div class="gl-glow-card p-5">
                <div class="mb-2 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold" style="color: var(--gl-text-primary)">
                            Activity Completion
                        </h3>
                        <p class="text-[11px]" style="color: var(--gl-text-muted)">
                            Expected vs completed submissions
                        </p>
                    </div>
                    <span
                        class="rounded-full px-2.5 py-0.5 text-[11px] font-bold tabular-nums"
                        :style="{
                            background: (completionMetrics?.overallCompletionRate ?? 0) >= 70 ? 'rgba(16, 185, 129, 0.15)' : 'rgba(59, 130, 246, 0.15)',
                            color: (completionMetrics?.overallCompletionRate ?? 0) >= 70 ? 'var(--gl-success)' : 'var(--gl-primary)',
                            border: '1px solid rgba(59, 130, 246, 0.25)'
                        }"
                    >
                        {{ completionMetrics?.overallCompletionRate ?? 0 }}% completion
                    </span>
                </div>

                <div class="mt-3 space-y-3">
                    <div
                        v-for="item in (completionMetrics?.byType ?? [])"
                        :key="item.type"
                        class="rounded-xl p-2.5"
                        style="background: var(--gl-surface-2); border: 1px solid var(--gl-border)"
                    >
                        <div class="flex items-center justify-between text-xs mb-1.5">
                            <span class="font-bold" style="color: var(--gl-text-primary)">
                                {{ item.type }}
                            </span>
                            <span class="font-bold tabular-nums" :style="{ color: item.color }">
                                {{ item.rate }}%
                            </span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full" style="background: rgba(148, 163, 184, 0.2)">
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :style="{
                                    width: item.rate + '%',
                                    background: item.color
                                }"
                            ></div>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-[10px]" style="color: var(--gl-text-muted)">
                            <span>{{ item.submissions }} submissions</span>
                            <span>{{ item.activities }} active &bull; {{ item.students }} students</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submission activity -->
            <div class="gl-glow-card p-5">
                <div class="mb-1 flex items-center justify-between">
                    <h3
                        class="text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Submission Activity
                    </h3>
                    <span
                        class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-muted);
                        "
                        >{{ periodTotal }} total</span
                    >
                </div>
                <p class="mb-3 text-[11px]" style="color: var(--gl-text-muted)">
                    Last 8 weeks
                </p>
                <div class="flex h-24 items-end gap-1.5">
                    <div
                        v-for="(w, i) in weeklyTrends"
                        :key="i"
                        class="flex h-full flex-1 flex-col items-center justify-end gap-1"
                        :title="`${w.label}: ${w.submissions} submissions`"
                    >
                        <div
                            class="w-full rounded-t-md transition-all"
                            :style="{
                                height:
                                    maxWeekly > 0
                                        ? Math.max(
                                              6,
                                              (w.submissions / maxWeekly) * 100,
                                          ) + '%'
                                        : '6px',
                                background:
                                    w.submissions === maxWeekly && maxWeekly > 0
                                        ? 'linear-gradient(180deg, var(--gl-accent), #F59E0B)'
                                        : 'linear-gradient(180deg, var(--gl-primary), var(--gl-secondary))',
                                opacity: w.submissions > 0 ? 1 : 0.25,
                                boxShadow:
                                    w.submissions === maxWeekly && maxWeekly > 0
                                        ? '0 0 10px var(--gl-accent-glow)'
                                        : 'none',
                            }"
                        ></div>
                        <span
                            class="text-[8px]"
                            style="color: var(--gl-text-muted)"
                            >{{ shortLabel(w.label) }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- Top students -->
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
                        Top Students
                    </h3>
                    <p class="text-[11px]" style="color: var(--gl-text-muted)">
                        Highest XP earners
                    </p>
                </div>
                <div
                    v-if="!podium.length"
                    class="px-5 pb-4 text-center text-xs"
                    style="color: var(--gl-text-muted)"
                >
                    No students yet.
                </div>
                <div
                    v-else
                    class="flex items-end justify-center gap-2 px-5 pt-1"
                >
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
                            <span v-else>{{ (e.name || '?').charAt(0).toUpperCase() }}</span>
                        </div>
                        <p
                            class="mt-1 max-w-full truncate text-[10px] font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ e.name.split(' ')[0] }}
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
                        href="/teacher/students"
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
                        View all students
                        <ArrowRight class="h-3.5 w-3.5" :stroke-width="2" />
                    </Link>
                </div>
            </div>

            <!-- Manage nudge -->
            <div
                class="flex items-center gap-3 rounded-2xl p-4"
                style="
                    background: rgba(124, 58, 237, 0.07);
                    border: 1px solid rgba(124, 58, 237, 0.18);
                "
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                    style="background: rgba(124, 58, 237, 0.14)"
                >
                    <Users
                        class="h-[18px] w-[18px]"
                        style="color: var(--gl-secondary)"
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
                        >{{ studentCount }} students ·
                        {{ totalActivities }} activities.</span
                    >
                    Publish new work to keep them engaged!
                </p>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    FileQuestion,
    FileText,
    ClipboardList,
    FlaskConical,
    TrendingUp,
    Users,
    Layers,
    Trophy,
    ArrowRight,
    Target,
    Plus,
    CircleCheck,
    CircleAlert,
} from '@lucide/vue';
import { computed } from 'vue';
import PctBadge from '@/components/PctBadge.vue';

const props = defineProps<{
    totalSubmissions: number;
    totalActivities: number;
    studentCount: number;
    overallAvg: number;
    typeAverages: Record<string, number>;
    typeSubmissions: Record<string, number>;
    recentActivity: {
        type: string;
        title: string;
        student: string;
        score: number;
        total: number;
        pct: number;
        submitted_at: string;
    }[];
    weeklyTrends: { label: string; submissions: number; average: number }[];
    topStudents: {
        id: number;
        name: string;
        avatar?: string | null;
        total_points: number;
        rank: number;
    }[];
    passRateMetrics?: {
        passRate: number;
        passedCount: number;
        failedCount: number;
        totalGraded: number;
        passingThreshold: number;
        masteryCount: number;
        proficientCount: number;
        developingCount: number;
        beginningCount: number;
        tiers: { label: string; count: number; pct: number; color: string }[];
    };
    completionMetrics?: {
        overallCompletionRate: number;
        expectedSubmissions: number;
        actualSubmissions: number;
        byType: {
            type: string;
            activities: number;
            submissions: number;
            students: number;
            rate: number;
            color: string;
        }[];
    };
    isSuperadmin: boolean;
}>();

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const displayName = computed(
    () => (page.props as any).auth?.user?.name ?? 'Teacher',
);
const firstName = computed(() => displayName.value.split(' ')[0]);

const thisWeek = computed(
    () => props.weeklyTrends?.[props.weeklyTrends.length - 1]?.submissions ?? 0,
);

const maxWeekly = computed(() => {
    const vals = (props.weeklyTrends ?? []).map((w) => w.submissions);

    return vals.length ? Math.max(...vals) : 0;
});

const periodTotal = computed(() =>
    (props.weeklyTrends ?? []).reduce((a, w) => a + w.submissions, 0),
);

const bestWeek = computed(() => {
    const weeks = props.weeklyTrends ?? [];

    if (!weeks.length || maxWeekly.value <= 0) {
return null;
}

    return weeks.find((w) => w.submissions === maxWeekly.value) ?? null;
});

const trendAvg = computed(() => {
    const weeks = (props.weeklyTrends ?? []).filter((w) => w.submissions > 0);

    if (!weeks.length) {
return 0;
}

    return Math.round(weeks.reduce((a, w) => a + w.average, 0) / weeks.length);
});

function trendPoint(i: number): { x: number; y: number } {
    const n = Math.max(1, (props.weeklyTrends ?? []).length);
    const avg = (props.weeklyTrends ?? [])[i]?.average ?? 0;

    return {
        x: n === 1 ? 150 : (i / (n - 1)) * 300,
        y: 130 - (Math.min(100, avg) / 100) * 120,
    };
}

const trendDots = computed(() =>
    (props.weeklyTrends ?? []).map((_, i) => trendPoint(i)),
);

const trendLine = computed(() =>
    trendDots.value.map((p) => `${p.x},${p.y}`).join(' '),
);

const trendArea = computed(() => {
    const pts = trendDots.value;

    if (!pts.length) {
return '';
}

    return `0,130 ${pts.map((p) => `${p.x},${p.y}`).join(' ')} 300,130`;
});

const maxTypeSubs = computed(() => {
    const vals = Object.values(props.typeSubmissions ?? {});

    return vals.length ? Math.max(...vals) : 0;
});

const typeRows = computed(() => {
    const defs = [
        {
            key: 'Quiz',
            label: 'Quizzes',
            icon: FileQuestion,
            bg: 'rgba(59,130,246,0.12)',
            color: '#3B82F6',
            bar: 'linear-gradient(90deg, var(--gl-primary), var(--gl-secondary))',
        },
        {
            key: 'Exam',
            label: 'Exams',
            icon: FileText,
            bg: 'rgba(124,58,237,0.12)',
            color: '#7C3AED',
            bar: 'linear-gradient(90deg, var(--gl-secondary), #A855F7)',
        },
        {
            key: 'Seatwork',
            label: 'Seatworks',
            icon: ClipboardList,
            bg: 'rgba(16,185,129,0.12)',
            color: '#10B981',
            bar: 'linear-gradient(90deg, var(--gl-success), #34D399)',
        },
        {
            key: 'Practical',
            label: 'Practicals',
            icon: FlaskConical,
            bg: 'rgba(251,191,36,0.12)',
            color: '#FBBF24',
            bar: 'linear-gradient(90deg, #F59E0B, #F97316)',
        },
    ];

    return defs.map((d) => ({
        ...d,
        count: props.typeSubmissions?.[d.key] ?? 0,
        avg: props.typeAverages?.[d.key] ?? 0,
        pct:
            maxTypeSubs.value > 0
                ? Math.round(
                      ((props.typeSubmissions?.[d.key] ?? 0) /
                          maxTypeSubs.value) *
                          100,
                  )
                : 0,
    }));
});

const podium = computed(() => {
    const top = [...(props.topStudents ?? [])];
    const get = (rank: number) => top.find((e) => e.rank === rank);

    return [get(2), get(1), get(3)].filter(
        (e): e is (typeof top)[number] => e != null,
    );
});

const RING = 2 * Math.PI * 34;

const ringOff = computed(
    () => RING - (Math.min(100, props.overallAvg) / 100) * RING,
);

function shortLabel(label: string): string {
    return label.replace(/(\w{3}) \d+/, '$1');
}

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
