<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    GraduationCap,
    Rocket,
    Trophy,
    Star,
    Zap,
    BookOpen,
    Flame,
    MessageCircle,


    CheckCircle2,
    Sparkles,
    Shield,
    Award,
    Medal,
    Gem,
    Users,

    BarChart3,
    Globe,


    ClipboardCheck,
    TrendingUp,
    Gift,
    Palette,
    Menu,
    X,

} from '@lucide/vue';
import { computed, ref } from 'vue';
import CookieBanner from '@/components/CookieBanner.vue';
import ThemePicker from '@/components/ThemePicker.vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import { login } from '@/routes';

const mobileMenuOpen = ref(false);

const page = usePage();
const supportEmail = computed(() => (page.props as any).support_email || 'ely.ashzyl@gmail.com');
const paymentEmail = computed(() => (page.props as any).payment_email || 'ely.ashzyl@gmail.com');

const stats = [
    { value: '10,000+', label: 'Students', icon: Users },
    { value: '500+', label: 'Teachers', icon: GraduationCap },
    { value: '150+', label: 'Schools', icon: Globe },
    { value: '1M+', label: 'XP Earned', icon: Zap },
    { value: '250K+', label: 'Activities', icon: ClipboardCheck },
    { value: '50K+', label: 'Achievements', icon: Trophy },
];

const features = [
    {
        icon: Star,
        title: 'XP & Level System',
        desc: 'Earn XP for every activity. Watch your level rise as you master new skills.',
        bg: 'rgba(251,191,36,0.12)',
        color: '#FBBF24',
    },
    {
        icon: Award,
        title: 'Achievement Badges',
        desc: 'Unlock collectible badges for milestones, perfect scores, and learning streaks.',
        bg: 'rgba(59,130,246,0.12)',
        color: '#3B82F6',
    },
    {
        icon: Rocket,
        title: 'Interactive Quizzes',
        desc: 'Engaging quizzes with instant feedback keep students motivated to learn more.',
        bg: 'rgba(16,185,129,0.12)',
        color: '#10B981',
    },
    {
        icon: Flame,
        title: 'Daily Streaks',
        desc: 'Build consistent study habits and earn bonus rewards for maintaining your streak.',
        bg: 'rgba(239,68,68,0.12)',
        color: '#EF4444',
    },
    {
        icon: Trophy,
        title: 'Leaderboards',
        desc: 'Compete with classmates, track your rank, and climb to the top of your section.',
        bg: 'rgba(124,58,237,0.12)',
        color: '#7C3AED',
    },
    {
        icon: MessageCircle,
        title: 'Built-in Messenger',
        desc: 'Stay connected with teachers and classmates through real-time messaging.',
        bg: 'rgba(6,182,212,0.12)',
        color: '#06B6D4',
    },
    {
        icon: BarChart3,
        title: 'Analytics Dashboard',
        desc: 'Comprehensive analytics for teachers to track student progress at a glance.',
        bg: 'rgba(249,115,22,0.12)',
        color: '#F97316',
    },
    {
        icon: Shield,
        title: 'Teacher Tools',
        desc: 'Create, publish, grade, and manage all your learning activities from one place.',
        bg: 'rgba(139,92,246,0.12)',
        color: '#8B5CF6',
    },
];

const steps = [
    {
        icon: GraduationCap,
        title: 'Create Account',
        desc: 'Join EduPulse and set up your student or teacher profile.',
        bg: 'rgba(59,130,246,0.12)',
        color: '#3B82F6',
    },
    {
        icon: BookOpen,
        title: 'Complete Missions',
        desc: 'Take quizzes, practicals, and exams designed by your teachers.',
        bg: 'rgba(124,58,237,0.12)',
        color: '#7C3AED',
    },
    {
        icon: Zap,
        title: 'Earn XP & Rewards',
        desc: 'Get experience points for every completed activity and submission.',
        bg: 'rgba(251,191,36,0.12)',
        color: '#FBBF24',
    },
    {
        icon: Trophy,
        title: 'Unlock Achievements',
        desc: 'Collect badges for perfect scores, streaks, and special milestones.',
        bg: 'rgba(16,185,129,0.12)',
        color: '#10B981',
    },
    {
        icon: TrendingUp,
        title: 'Become a Champion',
        desc: 'Climb the leaderboard and become the top learner in your section.',
        bg: 'rgba(239,68,68,0.12)',
        color: '#EF4444',
    },
];

const testimonials = [
    {
        name: 'Sarah Chen',
        role: 'Student, Grade 10',
        text: 'EduPulse made learning fun again. I love checking my XP after every quiz and seeing my level go up!',
    },
    {
        name: 'James Rodriguez',
        role: 'Teacher',
        text: 'My students are more engaged than ever. The leaderboard has created healthy competition in my classroom.',
    },
    {
        name: 'Aiko Tanaka',
        role: 'Student, Grade 8',
        text: 'The achievement badges are so satisfying to collect. I studied extra just to get the Perfect Score badge!',
    },
];
</script>

<template>
    <Head title="EduPulse — Level Up Your Learning" />
    <div
        class="welcome-page"
        style="
            background: var(--gl-bg);
            color: var(--gl-text-primary);
            overflow-x: hidden;
            scroll-behavior: smooth;
        "
    >
        <!-- ════════════ NAV ════════════ -->
        <nav
            class="sticky top-0 z-30 flex items-center justify-between px-4 py-3 transition-all sm:px-6"
            style="
                background: var(--gl-header-bg);
                backdrop-filter: blur(20px);
                border-bottom: 1px solid var(--gl-border);
            "
        >
            <div class="flex items-center gap-2.5">
                <img
                    src="/logo.png"
                    alt="EduPulse logo"
                    class="h-9 w-9 rounded-full object-cover"
                    style="box-shadow: 0 0 12px var(--gl-primary-glow)"
                />
                <div>
                    <span class="text-lg font-bold tracking-tight">
                        <span style="color: var(--gl-primary)">Edu</span
                        ><span style="color: var(--gl-text-primary)"
                            >Pulse</span
                        >
                    </span>
                    <span
                        class="ml-2 hidden rounded px-1.5 py-0.5 text-[10px] sm:inline"
                        style="
                            color: var(--gl-text-muted);
                            background: var(--gl-surface-2);
                        "
                        >Learn · Play · Achieve</span
                    >
                </div>
            </div>
            <div
                class="hidden items-center gap-5 text-sm md:flex"
                style="color: var(--gl-text-secondary)"
            >
                <a
                    href="#features"
                    class="transition-colors hover:opacity-80"
                    style="color: inherit"
                    >Features</a
                >
                <a
                    href="#themes"
                    class="transition-colors hover:opacity-80"
                    style="color: inherit"
                    >Themes</a
                >
                <a
                    href="#how"
                    class="transition-colors hover:opacity-80"
                    style="color: inherit"
                    >How It Works</a
                >

                <ThemeToggle />
                <Link
                    :href="login()"
                    class="rounded-xl px-5 py-2 text-sm font-semibold text-white transition-all hover:scale-105"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-primary),
                            var(--gl-secondary)
                        );
                        box-shadow: 0 0 16px var(--gl-primary-glow);
                    "
                >
                    Sign In
                </Link>

            </div>
            <div class="flex items-center gap-2 md:hidden">
                <ThemeToggle />
                <button
                    type="button"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    class="rounded-xl p-2 transition-colors"
                    style="
                        background: var(--gl-surface-2);
                        border: 1px solid var(--gl-border);
                        color: var(--gl-text-primary);
                    "
                    aria-label="Toggle menu"
                >
                    <Menu v-if="!mobileMenuOpen" class="h-5 w-5" />
                    <X v-else class="h-5 w-5" />
                </button>
            </div>
        </nav>

        <!-- Mobile menu dropdown -->
        <div
            v-if="mobileMenuOpen"
            class="sticky top-[61px] z-20 space-y-3 border-b px-5 py-4 transition-all md:hidden"
            style="
                background: var(--gl-surface);
                border-color: var(--gl-border);
                backdrop-filter: blur(20px);
            "
        >
            <a
                href="#features"
                @click="mobileMenuOpen = false"
                class="block py-1 text-sm font-medium"
                style="color: var(--gl-text-primary)"
                >Features</a
            >
            <a
                href="#themes"
                @click="mobileMenuOpen = false"
                class="block py-1 text-sm font-medium"
                style="color: var(--gl-text-primary)"
                >Themes</a
            >
            <a
                href="#how"
                @click="mobileMenuOpen = false"
                class="block py-1 text-sm font-medium"
                style="color: var(--gl-text-primary)"
                >How It Works</a
            >

            <div
                class="flex flex-col gap-2 border-t pt-2"
                style="border-color: var(--gl-border)"
            >
                <Link
                    :href="login()"
                    @click="mobileMenuOpen = false"
                    class="rounded-xl py-2 text-center text-sm font-semibold text-white"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-primary),
                            var(--gl-secondary)
                        );
                    "
                    >Sign In</Link
                >
            </div>
        </div>

        <!-- ════════════ HERO ════════════ -->
        <section
            class="relative overflow-hidden px-6 pt-16 pb-24 md:pt-24 md:pb-32"
        >
            <div
                class="pointer-events-none absolute -top-60 -right-60 h-[700px] w-[700px] rounded-full opacity-10"
                style="
                    background: radial-gradient(
                        circle,
                        var(--gl-primary),
                        transparent 70%
                    );
                "
            ></div>
            <div
                class="pointer-events-none absolute -bottom-60 -left-60 h-[600px] w-[600px] rounded-full opacity-10"
                style="
                    background: radial-gradient(
                        circle,
                        var(--gl-secondary),
                        transparent 70%
                    );
                "
            ></div>
            <div
                class="pointer-events-none absolute top-1/3 left-1/2 h-[420px] w-[420px] -translate-x-1/2 rounded-full opacity-[0.07]"
                style="
                    background: radial-gradient(
                        circle,
                        var(--gl-accent),
                        transparent 70%
                    );
                "
            ></div>

            <div
                class="relative z-10 mx-auto grid max-w-6xl items-center gap-12 md:grid-cols-2"
            >
                <!-- Left -->
                <div>
                    <div
                        class="mb-6 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-medium"
                        style="
                            background: var(--gl-primary-glow);
                            color: var(--gl-primary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <Sparkles class="h-3.5 w-3.5" :stroke-width="2" />
                        Gamified Learning Platform
                    </div>
                    <h1
                        class="mb-6 text-5xl leading-[1.05] font-extrabold tracking-tight md:text-7xl"
                    >
                        <span>Learn.</span><br />
                        <span class="grad-text">Play. Achieve.</span>
                    </h1>
                    <p
                        class="mb-8 max-w-lg text-base md:text-lg"
                        style="
                            color: var(--gl-text-secondary);
                            line-height: 1.7;
                        "
                    >
                        Turn every lesson into an exciting adventure where
                        students earn XP, unlock achievements, complete
                        challenges, and climb the leaderboard.
                    </p>
                    <div class="mb-8 flex flex-wrap items-center gap-3">

                        <Link
                            :href="login()"
                            class="inline-flex items-center gap-2 rounded-xl px-7 py-3.5 text-base font-semibold text-white transition-all hover:scale-105"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 0 28px var(--gl-primary-glow);
                            "
                        >
                            <Rocket class="h-5 w-5" :stroke-width="2" /> Enter
                            Portal
                        </Link>
                    </div>
                    <div
                        class="grid grid-cols-2 gap-x-6 gap-y-2.5 text-xs"
                        style="color: var(--gl-text-muted)"
                    >
                        <div class="flex items-center gap-1.5">
                            <CheckCircle2
                                class="h-3.5 w-3.5"
                                style="color: var(--gl-success)"
                                :stroke-width="2"
                            />
                            Gamified Learning
                        </div>
                        <div class="flex items-center gap-1.5">
                            <CheckCircle2
                                class="h-3.5 w-3.5"
                                style="color: var(--gl-success)"
                                :stroke-width="2"
                            />
                            Teacher Friendly
                        </div>
                        <div class="flex items-center gap-1.5">
                            <CheckCircle2
                                class="h-3.5 w-3.5"
                                style="color: var(--gl-success)"
                                :stroke-width="2"
                            />
                            Student Focused
                        </div>
                        <div class="flex items-center gap-1.5">
                            <CheckCircle2
                                class="h-3.5 w-3.5"
                                style="color: var(--gl-success)"
                                :stroke-width="2"
                            />
                            50 Beautiful Themes
                        </div>
                    </div>
                </div>
                <!-- Right: Dashboard Mockup -->
                <div class="relative hidden md:block">
                    <div
                        class="relative rounded-2xl p-5"
                        style="
                            background: var(--gl-surface);
                            border: 1px solid var(--gl-border);
                            transform: perspective(900px) rotateY(-5deg)
                                rotateX(2deg);
                            box-shadow:
                                0 24px 48px rgba(0, 0, 0, 0.25),
                                0 0 0 1px var(--gl-primary-glow);
                        "
                    >
                        <!-- Mock header -->
                        <div
                            class="mb-4 flex items-center gap-2 pb-3"
                            style="border-bottom: 1px solid var(--gl-border)"
                        >
                            <img
                                src="/logo.png"
                                alt=""
                                class="h-5 w-5 rounded-full object-cover"
                            />
                            <span
                                class="ml-1 text-[10px] font-semibold"
                                style="color: var(--gl-text-muted)"
                                >EduPulse Dashboard</span
                            >
                            <div class="ml-auto flex gap-1.5">
                                <span
                                    class="h-2.5 w-2.5 rounded-full"
                                    style="background: var(--gl-danger)"
                                ></span
                                ><span
                                    class="h-2.5 w-2.5 rounded-full"
                                    style="background: var(--gl-accent)"
                                ></span
                                ><span
                                    class="h-2.5 w-2.5 rounded-full"
                                    style="background: var(--gl-success)"
                                ></span>
                            </div>
                        </div>
                        <!-- Mock cards -->
                        <div class="mb-3 grid grid-cols-2 gap-3">
                            <div
                                class="rounded-lg p-3"
                                style="background: var(--gl-surface-2)"
                            >
                                <div class="mb-2 flex items-center gap-1.5">
                                    <Zap
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-accent)"
                                        :stroke-width="2"
                                    />
                                    <div
                                        class="h-1.5 w-10 rounded-full"
                                        style="background: var(--gl-accent)"
                                    ></div>
                                </div>
                                <div
                                    class="text-lg font-bold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    2,450
                                    <span
                                        class="text-[10px] font-medium"
                                        style="color: var(--gl-text-muted)"
                                        >XP</span
                                    >
                                </div>
                            </div>
                            <div
                                class="rounded-lg p-3"
                                style="background: var(--gl-surface-2)"
                            >
                                <div class="mb-2 flex items-center gap-1.5">
                                    <Trophy
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-accent)"
                                        :stroke-width="2"
                                    />
                                    <div
                                        class="h-1.5 w-10 rounded-full"
                                        style="background: var(--gl-primary)"
                                    ></div>
                                </div>
                                <div
                                    class="text-lg font-bold"
                                    style="color: var(--gl-text-primary)"
                                >
                                    #3
                                    <span
                                        class="text-[10px] font-medium"
                                        style="color: var(--gl-text-muted)"
                                        >Rank</span
                                    >
                                </div>
                            </div>
                        </div>
                        <div
                            class="mb-3 rounded-lg p-4"
                            style="background: var(--gl-surface-2)"
                        >
                            <div class="mb-2 flex items-center justify-between">
                                <span
                                    class="text-[10px] font-semibold"
                                    style="color: var(--gl-text-secondary)"
                                    >Level 12 Progress</span
                                ><span
                                    class="text-[10px] font-bold"
                                    style="color: var(--gl-primary)"
                                    >68%</span
                                >
                            </div>
                            <div
                                class="h-2 w-full rounded-full"
                                style="background: var(--gl-surface-3)"
                            >
                                <div
                                    class="h-2 rounded-full"
                                    style="
                                        width: 68%;
                                        background: linear-gradient(
                                            90deg,
                                            var(--gl-primary),
                                            var(--gl-secondary)
                                        );
                                    "
                                ></div>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div
                                class="flex flex-1 items-center gap-2 rounded-lg p-2.5"
                                style="background: var(--gl-surface-2)"
                            >
                                <Medal
                                    class="h-4 w-4"
                                    style="color: var(--gl-accent)"
                                    :stroke-width="2"
                                />
                                <div
                                    class="h-2 w-14 rounded-full"
                                    style="background: var(--gl-surface-3)"
                                ></div>
                            </div>
                            <div
                                class="flex flex-1 items-center gap-2 rounded-lg p-2.5"
                                style="background: var(--gl-surface-2)"
                            >
                                <Gem
                                    class="h-4 w-4"
                                    style="color: var(--gl-secondary)"
                                    :stroke-width="2"
                                />
                                <div
                                    class="h-2 w-14 rounded-full"
                                    style="background: var(--gl-surface-3)"
                                ></div>
                            </div>
                        </div>
                    </div>
                    <!-- Floating badges -->
                    <div
                        class="animate-float absolute -right-3 -bottom-4 flex items-center gap-2 rounded-xl px-4 py-2.5 text-xs font-bold"
                        style="
                            background: linear-gradient(
                                135deg,
                                var(--gl-accent),
                                var(--gl-primary)
                            );
                            color: #fff;
                            box-shadow: 0 8px 20px var(--gl-accent-glow);
                        "
                    >
                        <Trophy class="h-4 w-4" :stroke-width="2.5" /> Level 24
                        Reached!
                    </div>
                    <div
                        class="animate-float-slow absolute -top-4 -left-4 flex items-center gap-1.5 rounded-xl px-3 py-2 text-xs font-bold"
                        style="
                            background: var(--gl-surface);
                            border: 1px solid var(--gl-border);
                            color: var(--gl-success);
                            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
                        "
                    >
                        <Zap class="h-4 w-4" :stroke-width="2.5" /> +50 XP
                    </div>
                </div>
            </div>
        </section>

        <!-- ════════════ STATS ════════════ -->
        <section
            class="px-6 py-14"
            style="
                border-top: 1px solid var(--gl-border);
                border-bottom: 1px solid var(--gl-border);
                background: var(--gl-surface);
            "
        >
            <div
                class="mx-auto grid max-w-6xl grid-cols-2 gap-6 sm:grid-cols-3 md:grid-cols-6"
            >
                <div
                    v-for="s in stats"
                    :key="s.label"
                    class="group text-center"
                >
                    <div
                        class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-xl transition-all group-hover:scale-110"
                        style="background: var(--gl-primary-glow)"
                    >
                        <component
                            :is="s.icon"
                            class="h-5 w-5"
                            style="color: var(--gl-primary)"
                            :stroke-width="2"
                        />
                    </div>
                    <p
                        class="text-2xl font-bold transition-all md:text-3xl"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ s.value }}
                    </p>
                    <p
                        class="mt-0.5 text-xs"
                        style="color: var(--gl-text-muted)"
                    >
                        {{ s.label }}
                    </p>
                </div>
            </div>
        </section>

        <!-- ════════════ THEMES ════════════ -->
        <section id="themes" class="relative overflow-hidden px-6 py-24">
            <div
                class="pointer-events-none absolute top-0 left-1/4 h-[300px] w-[300px] rounded-full opacity-10"
                style="
                    background: radial-gradient(
                        circle,
                        var(--gl-secondary),
                        transparent 70%
                    );
                "
            ></div>
            <div class="relative z-10 mx-auto mb-12 max-w-6xl text-center">
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-medium"
                    style="
                        background: var(--gl-accent-glow);
                        color: var(--gl-accent);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <Palette class="h-3.5 w-3.5" :stroke-width="2" /> 50
                    Handcrafted Themes
                </div>
                <h2
                    class="mb-3 text-3xl font-extrabold tracking-tight md:text-5xl"
                >
                    One Platform, <span class="grad-text">Fifty Moods</span>
                </h2>
                <p
                    class="mx-auto max-w-xl text-sm md:text-base"
                    style="color: var(--gl-text-secondary)"
                >
                    Pick a theme and watch the entire page — and the whole app —
                    transform instantly. Your choice is saved automatically.
                </p>
            </div>
            <div class="relative z-10 mx-auto max-w-5xl">
                <ThemePicker />
            </div>
        </section>

        <!-- ════════════ FEATURES ════════════ -->
        <section
            id="features"
            class="px-6 py-24"
            style="
                background: var(--gl-surface);
                border-top: 1px solid var(--gl-border);
                border-bottom: 1px solid var(--gl-border);
            "
        >
            <div class="mx-auto mb-16 max-w-6xl text-center">
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-medium"
                    style="
                        background: var(--gl-secondary-glow);
                        color: var(--gl-secondary);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <Sparkles class="h-3.5 w-3.5" :stroke-width="2" /> Built for
                    Success
                </div>
                <h2
                    class="mb-3 text-3xl font-extrabold tracking-tight md:text-5xl"
                >
                    Everything You Need<br /><span class="grad-text"
                        >to Succeed</span
                    >
                </h2>
                <p
                    class="mx-auto max-w-xl text-sm md:text-base"
                    style="color: var(--gl-text-secondary)"
                >
                    A complete gamified learning platform with all the tools
                    teachers and students need.
                </p>
            </div>
            <div
                class="mx-auto grid max-w-6xl grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4"
            >
                <div
                    v-for="f in features"
                    :key="f.title"
                    class="group rounded-2xl p-6 transition-all duration-300 hover:-translate-y-1.5"
                    style="
                        background: var(--gl-bg);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <div
                        class="mb-4 flex h-11 w-11 items-center justify-center rounded-xl transition-all group-hover:scale-110"
                        :style="{ background: f.bg }"
                    >
                        <component
                            :is="f.icon"
                            class="h-5 w-5"
                            :style="{ color: f.color }"
                            :stroke-width="2"
                        />
                    </div>
                    <h3
                        class="mb-1.5 text-sm font-semibold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ f.title }}
                    </h3>
                    <p
                        class="text-xs leading-relaxed"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ f.desc }}
                    </p>
                </div>
            </div>
        </section>

        <!-- ════════════ JOURNEY ════════════ -->
        <section id="how" class="px-6 py-24">
            <div class="mx-auto mb-16 max-w-6xl text-center">
                <div
                    class="mb-4 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-medium"
                    style="
                        background: var(--gl-success-bg);
                        color: var(--gl-success);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <Zap class="h-3.5 w-3.5" :stroke-width="2" /> Your Learning
                    Adventure
                </div>
                <h2
                    class="mb-3 text-3xl font-extrabold tracking-tight md:text-5xl"
                >
                    How It <span class="grad-text">Works</span>
                </h2>
                <p
                    class="mx-auto max-w-xl text-sm md:text-base"
                    style="color: var(--gl-text-secondary)"
                >
                    Five simple steps to transform your educational experience.
                </p>
            </div>
            <div class="relative mx-auto max-w-5xl">
                <!-- Connecting line -->
                <div
                    class="absolute top-8 right-[10%] left-[10%] hidden h-0.5 opacity-40 md:block"
                    style="
                        background: linear-gradient(
                            90deg,
                            var(--gl-primary),
                            var(--gl-secondary),
                            var(--gl-accent),
                            var(--gl-success),
                            var(--gl-danger)
                        );
                    "
                ></div>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-5">
                    <div
                        v-for="(h, i) in steps"
                        :key="h.title"
                        class="group relative text-center"
                    >
                        <div
                            class="relative z-10 mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl text-lg font-bold transition-all group-hover:scale-110"
                            :style="{
                                background: 'var(--gl-surface)',
                                border: '1px solid var(--gl-border)',
                                boxShadow: '0 0 20px var(--gl-primary-glow)',
                            }"
                        >
                            <component
                                :is="h.icon"
                                class="h-7 w-7"
                                :style="{ color: h.color }"
                                :stroke-width="2"
                            />
                        </div>
                        <span
                            class="mb-1.5 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-muted);
                            "
                            >STEP {{ i + 1 }}</span
                        >
                        <h4
                            class="mb-1 text-sm font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ h.title }}
                        </h4>
                        <p
                            class="text-xs leading-relaxed"
                            style="color: var(--gl-text-secondary)"
                        >
                            {{ h.desc }}
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ════════════ TESTIMONIALS ════════════ -->
        <section
            class="px-6 py-24"
            style="
                background: var(--gl-surface);
                border-top: 1px solid var(--gl-border);
                border-bottom: 1px solid var(--gl-border);
            "
        >
            <div class="mx-auto mb-16 max-w-6xl text-center">
                <h2
                    class="mb-3 text-3xl font-extrabold tracking-tight md:text-5xl"
                >
                    Loved by <span class="grad-text">Students & Teachers</span>
                </h2>
                <p
                    class="mx-auto max-w-xl text-sm md:text-base"
                    style="color: var(--gl-text-secondary)"
                >
                    See what our community has to say about EduPulse.
                </p>
            </div>
            <div
                class="mx-auto grid max-w-4xl grid-cols-1 gap-5 md:grid-cols-3"
            >
                <div
                    v-for="t in testimonials"
                    :key="t.name"
                    class="rounded-2xl p-6 text-center transition-all hover:-translate-y-1"
                    style="
                        background: var(--gl-bg);
                        border: 1px solid var(--gl-border);
                    "
                >
                    <div class="mb-3 flex justify-center gap-0.5">
                        <Star
                            v-for="i in 5"
                            :key="i"
                            class="h-3.5 w-3.5 fill-current"
                            style="color: var(--gl-accent)"
                            :stroke-width="1"
                        />
                    </div>
                    <p
                        class="mb-4 text-sm leading-relaxed italic"
                        style="color: var(--gl-text-secondary)"
                    >
                        "{{ t.text }}"
                    </p>
                    <div
                        class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold text-white"
                        style="
                            background: linear-gradient(
                                135deg,
                                var(--gl-primary),
                                var(--gl-secondary)
                            );
                        "
                    >
                        {{ t.name.charAt(0) }}
                    </div>
                    <p
                        class="text-sm font-semibold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ t.name }}
                    </p>
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        {{ t.role }}
                    </p>
                </div>
            </div>
        </section>

        <!-- ════════════ CTA ════════════ -->
        <section class="relative overflow-hidden px-6 py-28">
            <div
                class="relative mx-auto max-w-4xl overflow-hidden rounded-3xl px-6 py-16 text-center"
                style="
                    background: linear-gradient(
                        135deg,
                        var(--gl-primary-glow),
                        var(--gl-secondary-glow)
                    );
                    border: 1px solid var(--gl-border);
                "
            >
                <div
                    class="pointer-events-none absolute -top-16 -right-16 h-64 w-64 rounded-full opacity-20"
                    style="
                        background: radial-gradient(
                            circle,
                            var(--gl-accent),
                            transparent 70%
                        );
                    "
                ></div>
                <div
                    class="pointer-events-none absolute -bottom-16 -left-16 h-64 w-64 rounded-full opacity-20"
                    style="
                        background: radial-gradient(
                            circle,
                            var(--gl-primary),
                            transparent 70%
                        );
                    "
                ></div>
                <div class="relative z-10">
                    <img
                        src="/logo.png"
                        alt="EduPulse logo"
                        class="mx-auto mb-6 h-20 w-20 rounded-full object-cover"
                        style="box-shadow: 0 0 32px var(--gl-primary-glow)"
                    />
                    <div
                        class="mb-6 inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-xs font-medium"
                        style="
                            background: var(--gl-surface);
                            color: var(--gl-accent);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        <Gift class="h-3.5 w-3.5" :stroke-width="2" /> Start
                        Your Journey Today
                    </div>
                    <h2
                        class="mb-4 text-3xl font-extrabold tracking-tight md:text-5xl"
                    >
                        Ready to Begin Your<br />Learning Adventure?
                    </h2>
                    <p
                        class="mx-auto mb-8 max-w-lg text-sm md:text-base"
                        style="color: var(--gl-text-secondary)"
                    >
                        Join thousands of students who are learning, playing,
                        and achieving every single day.
                    </p>
                    <div
                        class="flex flex-wrap items-center justify-center gap-4"
                    >
                        <Link
                            :href="login()"
                            class="inline-flex items-center gap-2 rounded-xl px-8 py-3.5 text-base font-bold text-white transition-all hover:scale-105"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 0 32px var(--gl-primary-glow);
                            "
                        >
                            <Rocket class="h-5 w-5" :stroke-width="2" /> Enter
                            Portal
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ════════════ FOOTER ════════════ -->
        <footer
            class="px-6 py-14"
            style="
                border-top: 1px solid var(--gl-border);
                background: var(--gl-surface);
            "
        >
            <div class="mx-auto max-w-6xl">
                <div class="mb-10 grid grid-cols-2 gap-8 md:grid-cols-4">
                    <div>
                        <div class="mb-3 flex items-center gap-2">
                            <img
                                src="/logo.png"
                                alt="EduPulse logo"
                                class="h-8 w-8 rounded-full object-cover"
                            />
                            <span class="font-bold"
                                ><span style="color: var(--gl-primary)"
                                    >Edu</span
                                ><span style="color: var(--gl-text-primary)"
                                    >Pulse</span
                                ></span
                            >
                        </div>
                        <p
                            class="text-xs leading-relaxed"
                            style="color: var(--gl-text-muted)"
                        >
                            The gamified learning management system that makes
                            education fun.
                        </p>
                    </div>
                    <div>
                        <p
                            class="mb-3 text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            Platform
                        </p>
                        <div
                            class="space-y-2 text-xs"
                            style="color: var(--gl-text-muted)"
                        >
                            <p>Quizzes</p>
                            <p>Exams</p>
                            <p>Practicals</p>
                            <p>Leaderboard</p>
                        </div>
                    </div>
                    <div>
                        <p
                            class="mb-3 text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            Legal &amp; Privacy
                        </p>
                        <div
                            class="space-y-2 text-xs"
                            style="color: var(--gl-text-muted)"
                        >
                            <p>
                                <Link
                                    href="/privacy-policy"
                                    class="transition-colors hover:text-white"
                                    >Privacy Policy</Link
                                >
                            </p>
                            <p>
                                <Link
                                    href="/terms-of-service"
                                    class="transition-colors hover:text-white"
                                    >Terms of Service</Link
                                >
                            </p>
                            <p>
                                <Link
                                    href="/cookie-policy"
                                    class="transition-colors hover:text-white"
                                    >Cookie Policy</Link
                                >
                            </p>
                        </div>
                    </div>
                    <div>
                        <p
                            class="mb-3 text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            Connect
                        </p>
                        <div
                            class="space-y-2 text-xs"
                            style="color: var(--gl-text-muted)"
                        >
                            <p>Twitter</p>
                            <p>Facebook</p>
                            <p>Discord</p>
                            <p>
                                <a
                                    :href="`mailto:${supportEmail}`"
                                    class="transition-colors hover:text-white"
                                    >Email: {{ supportEmail }}</a
                                >
                            </p>
                            <p class="text-[11px] opacity-80">
                                Inquiries &amp; payments welcome via <a :href="`mailto:${paymentEmail}`" class="underline transition-colors hover:text-white">{{ paymentEmail }}</a>.
                            </p>
                        </div>
                    </div>
                </div>
                <div
                    class="flex flex-wrap items-center justify-between gap-4 pt-8 text-xs"
                    style="
                        color: var(--gl-text-muted);
                        border-top: 1px solid var(--gl-border);
                    "
                >
                    <p>&copy; 2026 EduPulse. All rights reserved.</p>
                    <div class="flex items-center gap-4">
                        <Link
                            href="/privacy-policy"
                            class="transition-colors hover:underline"
                            >Privacy</Link
                        >
                        <span>&bull;</span>
                        <Link
                            href="/terms-of-service"
                            class="transition-colors hover:underline"
                            >Terms</Link
                        >
                        <span>&bull;</span>
                        <Link
                            href="/cookie-policy"
                            class="transition-colors hover:underline"
                            >Cookies</Link
                        >
                    </div>
                    <p style="color: var(--gl-primary)">
                        Learn &bull; Play &bull; Achieve
                    </p>
                </div>
            </div>
        </footer>
        <CookieBanner />
    </div>
</template>

<style scoped>
.grad-text {
    background: linear-gradient(
        135deg,
        var(--gl-primary),
        var(--gl-secondary),
        var(--gl-accent)
    );
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
@keyframes float-y {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}
.animate-float {
    animation: float-y 3.5s ease-in-out infinite;
}
.animate-float-slow {
    animation: float-y 5s ease-in-out infinite;
}
</style>
