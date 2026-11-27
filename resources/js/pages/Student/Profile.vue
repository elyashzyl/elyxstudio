<template>
    <Head title="Profile" />

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
        v-if="flash?.error"
        class="gl-fade-in mb-6 rounded-xl px-4 py-3 text-sm"
        style="
            background: var(--gl-danger-bg);
            color: var(--gl-danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
        "
    >
        {{ flash.error }}
    </div>
    <div
        v-if="flash?.info"
        class="gl-fade-in mb-6 rounded-xl px-4 py-3 text-sm"
        style="
            background: rgba(59, 130, 246, 0.12);
            color: var(--gl-primary);
            border: 1px solid rgba(59, 130, 246, 0.2);
        "
    >
        {{ flash.info }}
    </div>

    <!-- Header -->
    <div class="mb-6">
        <p
            class="text-xs font-semibold tracking-widest uppercase"
            style="color: var(--gl-text-muted)"
        >
            Account
        </p>
        <h1
            class="mt-1 flex flex-wrap items-center gap-2 text-2xl font-extrabold tracking-tight sm:text-3xl"
            style="color: var(--gl-text-primary)"
        >
            {{ user.name }}
            <span
                class="rounded-full px-2.5 py-1 text-[11px] font-bold"
                style="
                    background: rgba(59, 130, 246, 0.1);
                    color: var(--gl-primary);
                    border: 1px solid rgba(59, 130, 246, 0.25);
                "
            >
                Student
            </span>
        </h1>
        <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
            {{ user.email }} · Level {{ userLevel }} {{ levelTitle }} ·
            {{ totalPoints }} XP
        </p>
    </div>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2">
            <!-- Secret Rewards -->
            <div class="gl-glow-card overflow-hidden p-0">
                <div
                    class="border-b px-6 py-4"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="background: rgba(251, 191, 36, 0.12)"
                        >
                            <Gift
                                class="h-4 w-4"
                                style="color: var(--gl-accent)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                Secret Rewards
                            </h3>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                Level up to unlock hidden rewards. What's behind
                                the next mystery?
                            </p>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <div
                        v-if="secretRewards.length"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div
                            v-for="r in secretRewards"
                            :key="r.level"
                            class="gl-reward-in relative overflow-hidden rounded-xl p-4"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    rgba(251, 191, 36, 0.1),
                                    rgba(59, 130, 246, 0.06)
                                );
                                border: 1px solid rgba(251, 191, 36, 0.2);
                            "
                        >
                            <div class="mb-2 flex items-center justify-between">
                                <div
                                    class="flex h-9 w-9 items-center justify-center rounded-lg"
                                    style="background: rgba(251, 191, 36, 0.18)"
                                >
                                    <Gift
                                        class="h-4.5 w-4.5"
                                        style="color: var(--gl-accent)"
                                        :stroke-width="2"
                                    />
                                </div>
                                <span
                                    class="rounded-full px-2 py-0.5 text-[10px] font-semibold"
                                    style="
                                        background: rgba(59, 130, 246, 0.12);
                                        color: var(--gl-primary);
                                    "
                                    >Level {{ r.level }}</span
                                >
                            </div>
                            <p
                                class="text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ r.title }}
                            </p>
                            <p
                                class="mt-1 text-xs leading-relaxed"
                                style="color: var(--gl-text-secondary)"
                            >
                                {{ r.description }}
                            </p>
                            <p
                                v-if="r.effect_label"
                                class="mt-1.5 text-[11px] font-semibold"
                                style="color: var(--gl-primary)"
                            >
                                {{ r.effect_label }}
                            </p>
                            <div class="mt-3">
                                <!-- Consumed state -->
                                <p
                                    v-if="r.used"
                                    class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[10px] font-semibold"
                                    style="background: var(--gl-surface-2); color: var(--gl-text-muted)"
                                >
                                    <Check class="h-3 w-3" :stroke-width="2.5" />
                                    Used
                                </p>

                                <!-- Booster toggle -->
                                <button
                                    v-else-if="r.type === 'booster'"
                                    type="button"
                                    @click="useReward(r.level)"
                                    :disabled="usingLevel === r.level"
                                    class="w-full rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-60 flex items-center justify-center gap-1.5"
                                    :style="{
                                        background: r.active
                                            ? 'linear-gradient(135deg, #10B981, #059669)'
                                            : 'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                                        boxShadow: r.active ? '0 0 10px rgba(16, 185, 129, 0.4)' : '0 0 10px var(--gl-primary-glow)'
                                    }"
                                >
                                    <Zap class="h-3.5 w-3.5" :stroke-width="2.5" />
                                    <span>{{
                                        r.active
                                            ? 'Active (click to deactivate)'
                                            : rewardsHub?.active_booster_level
                                              ? 'Another booster is active'
                                              : 'Activate Booster'
                                    }}</span>
                                </button>

                                <!-- Shield toggle -->
                                <button
                                    v-else-if="r.type === 'shield'"
                                    type="button"
                                    @click="useReward(r.level)"
                                    :disabled="usingLevel === r.level"
                                    class="w-full rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-60 flex items-center justify-center gap-1.5"
                                    :style="{
                                        background: r.active
                                            ? 'linear-gradient(135deg, #F59E0B, #D97706)'
                                            : 'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                                        boxShadow: r.active ? '0 0 10px rgba(245, 158, 11, 0.4)' : '0 0 10px var(--gl-primary-glow)'
                                    }"
                                >
                                    <Shield class="h-3.5 w-3.5" :stroke-width="2.5" />
                                    <span>{{ r.active ? 'Shield Armed (Ready for Quiz)' : 'Arm Shield' }}</span>
                                </button>

                                <!-- Badges -->
                                <button
                                    v-else-if="r.type === 'badge'"
                                    type="button"
                                    @click="useReward(r.level)"
                                    :disabled="usingLevel === r.level"
                                    class="w-full rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-60 flex items-center justify-center gap-1.5"
                                    :style="{
                                        background: r.equipped
                                            ? 'linear-gradient(135deg, #8B5CF6, #6D28D9)'
                                            : 'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                                        boxShadow: r.equipped ? '0 0 10px rgba(139, 92, 246, 0.4)' : '0 0 10px var(--gl-primary-glow)'
                                    }"
                                >
                                    <Sparkles class="h-3.5 w-3.5" :stroke-width="2.5" />
                                    <span>{{ r.equipped ? 'Equipped Badge' : `Equip Badge (+${r.xp_bonus_pct ?? 0}% XP)` }}</span>
                                </button>

                                <!-- Instant XP -->
                                <button
                                    v-else-if="r.type === 'xp_grant'"
                                    type="button"
                                    @click="useReward(r.level)"
                                    :disabled="usingLevel === r.level"
                                    class="w-full rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-60 flex items-center justify-center gap-1.5"
                                    style="background: linear-gradient(135deg, #10B981, #059669); box-shadow: 0 0 10px rgba(16, 185, 129, 0.35)"
                                >
                                    <Gift class="h-3.5 w-3.5" :stroke-width="2.5" />
                                    <span>{{ usingLevel === r.level ? 'Claiming…' : `Claim +${r.xp ?? 0} XP` }}</span>
                                </button>

                                <!-- Streak freeze -->
                                <button
                                    v-else-if="r.type === 'streak_freeze'"
                                    type="button"
                                    @click="useReward(r.level)"
                                    :disabled="usingLevel === r.level || (rewardsHub?.streak_freeze_count ?? 0) >= 5"
                                    class="w-full rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-60 flex items-center justify-center gap-1.5"
                                    style="background: linear-gradient(135deg, #06B6D4, #0E7490); box-shadow: 0 0 10px rgba(6, 182, 212, 0.35)"
                                >
                                    <Snowflake class="h-3.5 w-3.5" :stroke-width="2.5" />
                                    <span>{{
                                        (rewardsHub?.streak_freeze_count ?? 0) >= 5
                                            ? 'Freeze inventory full (5/5)'
                                            : `Add ${r.count ?? 1} Streak Freeze${(r.count ?? 1) === 1 ? '' : 's'}`
                                    }}</span>
                                </button>

                                <!-- Homework Pass -->
                                <div v-else-if="r.type === 'homework_pass'" class="space-y-1.5">
                                    <Link
                                        href="/student/todos"
                                        class="w-full rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-all hover:scale-[1.02] flex items-center justify-center gap-1.5"
                                        style="background: linear-gradient(135deg, #F59E0B, #B45309); box-shadow: 0 0 10px rgba(245, 158, 11, 0.3)"
                                    >
                                        <Ticket class="h-3.5 w-3.5" :stroke-width="2.5" />
                                        <span>Use on Quiz / Seatwork / Performance Task</span>
                                    </Link>
                                </div>

                                <!-- Time Warp & Compass -->
                                <div v-else-if="r.type === 'time_warp'" class="space-y-1">
                                    <p class="text-[11px] font-medium text-purple-600 dark:text-purple-300 flex items-center gap-1">
                                        <Hourglass class="h-3.5 w-3.5" :stroke-width="2.5" />
                                        <span>Click the hourglass during a timed quiz</span>
                                    </p>
                                </div>
                                <div v-else-if="r.type === 'compass'" class="space-y-1">
                                    <p class="text-[11px] font-medium text-cyan-600 dark:text-cyan-300 flex items-center gap-1">
                                        <Compass class="h-3.5 w-3.5" :stroke-width="2.5" />
                                        <span>Click the compass on a quiz question</span>
                                    </p>
                                </div>

                                <!-- Generic Fallback -->
                                <button
                                    v-else
                                    type="button"
                                    @click="useReward(r.level)"
                                    :disabled="usingLevel === r.level"
                                    class="w-full rounded-lg px-3 py-1.5 text-xs font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-60"
                                    style="background: linear-gradient(135deg, var(--gl-primary), var(--gl-secondary)); box-shadow: 0 0 10px var(--gl-primary-glow)"
                                >
                                    {{ usingLevel === r.level ? 'Claiming…' : 'Claim Reward' }}
                                </button>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="mb-3 rounded-xl p-4 text-center text-sm"
                        style="color: var(--gl-text-secondary)"
                    >
                        No rewards unlocked yet — keep earning XP to begin your
                        journey.
                    </div>
                    <div
                        class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5"
                    >
                        <div
                            v-for="s in lockedSlots"
                            :key="s.level"
                            class="flex flex-col items-center justify-center rounded-xl border border-dashed p-4 text-center"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                            "
                        >
                            <Lock
                                class="mb-1.5 h-5 w-5"
                                style="color: var(--gl-text-muted)"
                                :stroke-width="2"
                            />
                            <p
                                class="text-sm font-bold"
                                style="color: var(--gl-text-muted)"
                            >
                                ???
                            </p>
                            <p
                                class="mt-0.5 text-[10px]"
                                style="color: var(--gl-text-muted)"
                            >
                                Reach Level {{ s.level }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Profile Form -->
            <div class="gl-glow-card overflow-hidden p-0">
                <div
                    class="border-b px-6 py-4"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="background: rgba(59, 130, 246, 0.12)"
                        >
                            <User
                                class="h-4 w-4"
                                style="color: var(--gl-primary)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                Profile Information
                            </h3>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                Update your name and email address.
                            </p>
                        </div>
                    </div>
                </div>
                <form @submit.prevent="updateProfile" class="space-y-4 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="field-label"
                                style="color: var(--gl-text-secondary)"
                                >Name</label
                            >
                            <input
                                v-model="profileForm.name"
                                type="text"
                                required
                                class="w-full rounded-xl border px-4 py-2.5 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                            <p
                                v-if="profileForm.errors.name"
                                class="mt-1 text-xs"
                                style="color: var(--gl-danger)"
                            >
                                {{ profileForm.errors.name }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="field-label"
                                style="color: var(--gl-text-secondary)"
                                >Email</label
                            >
                            <input
                                v-model="profileForm.email"
                                type="email"
                                required
                                class="w-full rounded-xl border px-4 py-2.5 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                            <p
                                v-if="profileForm.errors.email"
                                class="mt-1 text-xs"
                                style="color: var(--gl-danger)"
                            >
                                {{ profileForm.errors.email }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="field-label"
                                style="color: var(--gl-text-secondary)"
                                >Gender</label
                            >
                            <select
                                v-model="profileForm.gender"
                                class="w-full rounded-xl border px-4 py-2.5 text-sm outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            >
                                <option value="">Select gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                            <p
                                v-if="profileForm.errors.gender"
                                class="mt-1 text-xs"
                                style="color: var(--gl-danger)"
                            >
                                {{ profileForm.errors.gender }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="field-label"
                                style="color: var(--gl-text-secondary)"
                                >Grade Level</label
                            >
                            <select
                                v-model="profileForm.grade_level_id"
                                class="w-full rounded-xl border px-4 py-2.5 text-sm outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            >
                                <option value="">—</option>
                                <option
                                    v-for="g in gradeLevels"
                                    :key="g.id"
                                    :value="g.id"
                                >
                                    {{ g.name }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label
                            class="field-label"
                            style="color: var(--gl-text-secondary)"
                            >Section</label
                        >
                        <select
                            v-model="profileForm.section_id"
                            class="w-full rounded-xl border px-4 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        >
                            <option value="">—</option>
                            <option
                                v-for="s in filteredSections"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                    </div>

                    <div
                        v-if="mustVerifyEmail && !user.email_verified_at"
                        class="rounded-xl px-4 py-3 text-sm"
                        style="
                            background: var(--gl-warning-bg);
                            color: var(--gl-accent);
                            border: 1px solid rgba(251, 191, 36, 0.2);
                        "
                    >
                        <p>
                            Your email is unverified.
                            <button
                                type="button"
                                @click="resendVerification"
                                class="font-medium underline"
                                style="color: var(--gl-accent)"
                            >
                                Click here to re-send the verification email.
                            </button>
                        </p>
                        <p
                            v-if="status === 'verification-link-sent'"
                            class="mt-1 font-medium"
                            style="color: var(--gl-success)"
                        >
                            A new verification link has been sent.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="profileForm.processing"
                            class="rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-50"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 0 12px var(--gl-primary-glow);
                            "
                        >
                            Save
                        </button>
                        <span
                            v-if="profileForm.recentlySuccessful"
                            class="text-sm"
                            style="color: var(--gl-success)"
                            >Saved.</span
                        >
                    </div>
                </form>
            </div>

            <ProfileCustomizationCard :user="user" />

            <!-- Appearance -->
            <div class="gl-glow-card overflow-hidden p-0">
                <div
                    class="border-b px-6 py-4"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="background: rgba(124, 58, 237, 0.12)"
                        >
                            <Palette
                                class="h-4 w-4"
                                style="color: var(--gl-secondary)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                Appearance
                            </h3>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                Pick a theme — it applies across the whole
                                system.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="p-4 sm:p-6">
                    <ThemePicker />
                </div>
            </div>

            <!-- Password Form -->
            <div class="gl-glow-card overflow-hidden p-0">
                <div
                    class="border-b px-6 py-4"
                    style="border-color: var(--gl-border)"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg"
                            style="background: rgba(124, 58, 237, 0.12)"
                        >
                            <Lock
                                class="h-4 w-4"
                                style="color: var(--gl-secondary)"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-sm font-semibold"
                                style="color: var(--gl-text-primary)"
                            >
                                Update Password
                            </h3>
                            <p
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                Ensure your account is using a strong password.
                            </p>
                        </div>
                    </div>
                </div>
                <form @submit.prevent="updatePassword" class="space-y-4 p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                class="field-label"
                                style="color: var(--gl-text-secondary)"
                                >Current password</label
                            >
                            <input
                                v-model="passwordForm.current_password"
                                type="password"
                                required
                                autocomplete="current-password"
                                class="w-full rounded-xl border px-4 py-2.5 text-sm outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                            <p
                                v-if="passwordForm.errors.current_password"
                                class="mt-1 text-xs"
                                style="color: var(--gl-danger)"
                            >
                                {{ passwordForm.errors.current_password }}
                            </p>
                        </div>
                        <div>
                            <label
                                class="field-label"
                                style="color: var(--gl-text-secondary)"
                                >New password</label
                            >
                            <input
                                v-model="passwordForm.password"
                                type="password"
                                required
                                autocomplete="new-password"
                                class="w-full rounded-xl border px-4 py-2.5 text-sm outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                            <p
                                v-if="passwordForm.errors.password"
                                class="mt-1 text-xs"
                                style="color: var(--gl-danger)"
                            >
                                {{ passwordForm.errors.password }}
                            </p>
                        </div>
                    </div>
                    <div>
                        <label
                            class="field-label"
                            style="color: var(--gl-text-secondary)"
                            >Confirm new password</label
                        >
                        <input
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                            class="w-full rounded-xl border px-4 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                    </div>
                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-50"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 0 12px var(--gl-primary-glow);
                            "
                        >
                            Save
                        </button>
                        <span
                            v-if="passwordForm.recentlySuccessful"
                            class="text-sm"
                            style="color: var(--gl-success)"
                            >Saved.</span
                        >
                    </div>
                </form>
            </div>

            <!-- Delete Account -->
            <div
                class="gl-glow-card p-6"
                style="border-color: rgba(239, 68, 68, 0.2)"
            >
                <div class="mb-4 flex items-center gap-3">
                    <div
                        class="flex h-8 w-8 items-center justify-center rounded-lg"
                        style="background: var(--gl-danger-bg)"
                    >
                        <Trash2
                            class="h-4 w-4"
                            style="color: var(--gl-danger)"
                            :stroke-width="2"
                        />
                    </div>
                    <div>
                        <h3
                            class="text-sm font-semibold"
                            style="color: var(--gl-danger)"
                        >
                            Delete Account
                        </h3>
                        <p class="text-xs" style="color: var(--gl-text-muted)">
                            Permanently delete your account. This cannot be
                            undone.
                        </p>
                    </div>
                </div>
                <button
                    @click="showDelete = true"
                    class="rounded-xl px-4 py-2.5 text-sm font-medium transition-all"
                    style="
                        background: var(--gl-danger-bg);
                        color: var(--gl-danger);
                        border: 1px solid rgba(239, 68, 68, 0.2);
                    "
                >
                    <Trash2 class="mr-1.5 inline h-4 w-4" :stroke-width="2" />
                    Delete account
                </button>
            </div>
        </div>
        <!-- /left column -->

        <!-- Identity card -->
        <aside class="space-y-5 xl:sticky xl:top-6">
            <div
                class="overflow-hidden rounded-2xl p-5 text-center"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                "
            >
                <div
                    class="mx-auto flex h-16 w-16 items-center justify-center overflow-hidden rounded-full text-xl font-bold text-white"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-secondary),
                            var(--gl-primary)
                        );
                        box-shadow: 0 0 18px var(--gl-secondary-glow);
                    "
                >
                    <img
                        v-if="user.avatar"
                        :src="user.avatar"
                        :alt="user.name"
                        class="h-full w-full object-cover"
                    />
                    <span v-else>{{ (user.name || '?').charAt(0) }}</span>
                </div>
                <p
                    class="mt-2 truncate text-sm font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    {{ user.name }}
                </p>
                <p
                    class="truncate text-[11px]"
                    style="color: var(--gl-text-muted)"
                >
                    {{ user.email }}
                </p>
                <span
                    class="mt-2 inline-block rounded-full px-2.5 py-1 text-[11px] font-bold"
                    style="
                        background: linear-gradient(
                            135deg,
                            rgba(59, 130, 246, 0.15),
                            rgba(124, 58, 237, 0.12)
                        );
                        color: var(--gl-primary);
                    "
                >
                    Level {{ userLevel }} · {{ levelTitle }}
                </span>
                <div class="relative mx-auto mt-3 h-20 w-20">
                    <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90">
                        <circle
                            cx="18"
                            cy="18"
                            r="15.915"
                            fill="none"
                            stroke-width="3.5"
                            style="stroke: var(--gl-surface-2)"
                        />
                        <circle
                            cx="18"
                            cy="18"
                            r="15.915"
                            fill="none"
                            stroke-width="3.5"
                            stroke-linecap="round"
                            pathLength="100"
                            :stroke-dasharray="`${xpProgressPct} 100`"
                            style="stroke: var(--gl-primary)"
                        />
                    </svg>
                    <span
                        class="absolute inset-0 flex items-center justify-center text-xs font-extrabold tabular-nums"
                        style="color: var(--gl-text-primary)"
                        >{{ totalPoints }}</span
                    >
                </div>
                <p class="mt-1 text-[11px]" style="color: var(--gl-text-muted)">
                    {{ xpNextLevel - xpProgress }} XP to Level
                    {{ userLevel + 1 }}
                </p>
            </div>
            <div
                class="flex items-center gap-3 rounded-2xl p-4"
                style="
                    background: rgba(251, 191, 36, 0.07);
                    border: 1px solid rgba(251, 191, 36, 0.18);
                "
            >
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                    style="background: rgba(251, 191, 36, 0.14)"
                >
                    <Gift
                        class="h-4 w-4"
                        style="color: var(--gl-accent)"
                        :stroke-width="2"
                    />
                </div>
                <p
                    class="text-xs leading-relaxed"
                    style="color: var(--gl-text-secondary)"
                >
                    Next reward:
                    <span
                        class="font-bold"
                        style="color: var(--gl-text-primary)"
                        >Level {{ userLevel + 1 }} Chest.</span
                    >
                    Keep earning XP!
                </p>
            </div>
        </aside>
    </div>
    <!-- /grid -->

    <!-- Delete Modal -->
    <Teleport to="body">
        <div
            v-if="showDelete"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="showDelete = false"
        >
            <div
                class="mx-4 w-full max-w-md rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <div
                    class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl"
                    style="background: var(--gl-danger-bg)"
                >
                    <Trash2
                        class="h-7 w-7"
                        style="color: var(--gl-danger)"
                        :stroke-width="2"
                    />
                </div>
                <h3
                    class="mb-2 text-center text-base font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Delete Account
                </h3>
                <p
                    class="text-center text-sm"
                    style="color: var(--gl-text-secondary)"
                >
                    Enter your password to confirm. This action cannot be
                    undone.
                </p>
                <form @submit.prevent="deleteAccount">
                    <div class="mt-4">
                        <input
                            v-model="deleteForm.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="Password"
                            class="w-full rounded-xl border px-4 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                        <p
                            v-if="deleteForm.errors.password"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ deleteForm.errors.password }}
                        </p>
                    </div>
                    <div class="mt-5 flex items-center justify-center gap-3">
                        <button
                            type="button"
                            @click="showDelete = false"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border: 1px solid var(--gl-border);
                            "
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="deleteForm.processing"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
                            style="background: var(--gl-danger)"
                        >
                            {{
                                deleteForm.processing ? 'Deleting...' : 'Delete'
                            }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Level-Up Secret Reward Reveal -->
    <Teleport to="body">
        <div
            v-if="showReveal && levelUpReward"
            class="fixed inset-0 z-[100] flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px)"
            @click.self="claimReward"
        >
            <div
                class="gl-reveal-pop gl-shine relative mx-4 w-full max-w-md overflow-hidden rounded-3xl p-8 text-center"
                style="
                    background: linear-gradient(
                        160deg,
                        var(--gl-surface),
                        var(--gl-surface-2)
                    );
                    border: 1px solid rgba(251, 191, 36, 0.3);
                    box-shadow: 0 8px 48px rgba(0, 0, 0, 0.25);
                "
            >
                <span
                    v-for="(s, i) in sparkles"
                    :key="i"
                    class="gl-sparkle absolute rounded-full"
                    :style="{
                        left: s.x + '%',
                        top: s.y + '%',
                        width: s.size + 'px',
                        height: s.size + 'px',
                        background: s.color,
                        animationDelay: s.delay + 's',
                    }"
                ></span>
                <div
                    class="gl-pulse-glow mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-accent),
                            #f59e0b
                        );
                        box-shadow: 0 0 24px var(--gl-accent-glow);
                    "
                >
                    <Gift class="h-8 w-8 text-white" :stroke-width="2" />
                </div>
                <p
                    class="text-xs font-bold tracking-[0.2em] uppercase"
                    style="color: var(--gl-accent)"
                >
                    Secret Reward Unlocked
                </p>
                <h2
                    class="mt-1 text-3xl font-extrabold"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-accent),
                            var(--gl-primary)
                        );
                        -webkit-background-clip: text;
                        background-clip: text;
                        color: transparent;
                    "
                >
                    LEVEL UP!
                </h2>
                <p
                    class="mt-2 text-sm font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    You reached
                    <span
                        class="rounded-full px-2 py-0.5"
                        style="
                            background: rgba(59, 130, 246, 0.12);
                            color: var(--gl-primary);
                        "
                        >Level {{ levelUpReward.level }} ·
                        {{ titleForLevel(levelUpReward.level) }}</span
                    >
                </p>
                <div
                    class="mx-auto mt-4 max-w-sm rounded-2xl p-5"
                    style="
                        background: linear-gradient(
                            135deg,
                            rgba(251, 191, 36, 0.12),
                            rgba(59, 130, 246, 0.06)
                        );
                        border: 1px solid rgba(251, 191, 36, 0.25);
                    "
                >
                    <p
                        class="text-base font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ levelUpReward.title }}
                    </p>
                    <p
                        class="mt-1 text-sm leading-relaxed"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ levelUpReward.description }}
                    </p>
                </div>
                <button
                    @click="useLevelUpReward"
                    :disabled="usingLevel === levelUpReward.level"
                    class="mt-6 w-full rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-60"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-primary),
                            var(--gl-secondary)
                        );
                        box-shadow: 0 0 12px var(--gl-primary-glow);
                    "
                >
                    {{
                        usingLevel === levelUpReward.level
                            ? 'Using…'
                            : 'Use Reward'
                    }}
                </button>
                <button
                    @click="claimReward"
                    class="mt-2 w-full rounded-xl px-5 py-2 text-xs font-medium transition-colors"
                    style="color: var(--gl-text-muted)"
                >
                    Save for later
                </button>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Trash2,
    User,
    Lock,
    Gift,
    Palette,
    Check,
    Zap,
    Shield,
    Sparkles,
    Ticket,
    Hourglass,
    Compass,
    Snowflake,
} from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import ProfileCustomizationCard from '@/components/ProfileCustomizationCard.vue';
import ThemePicker from '@/components/ThemePicker.vue';
import {
    levelForPoints,
    titleForLevel,
    progressForLevel,
    nextLevelPoints,
    progressPct,
} from '@/lib/levels';
import { send } from '@/routes/verification';

const props = defineProps<{
    mustVerifyEmail?: boolean;
    status?: string;
    gradeLevels?: { id: number; name: string }[];
    sections?: { id: number; name: string; grade_level_id: number }[];
    secretRewards?: { level: number; title: string; description: string }[];
    usedRewards?: number[];
    rewardsHub?: {
        items: SecretReward[];
        active_xp_booster: boolean;
        active_brain_shield: boolean;
        active_booster_level?: number | null;
        equipped_badge: string | null;
        streak_freeze_count?: number;
        current_level: number;
    };
}>();

type SecretReward = {
    level: number;
    title: string;
    description: string;
    unlocked?: boolean;
    used?: boolean;
    active?: boolean;
    equipped?: boolean;
    type?: string;
    effect_label?: string;
    xp_bonus_pct?: number;
    xp?: number;
    count?: number;
};

type StudentSection = {
    id: number;
    name: string;
    grade_level_id: number;
};

const REVEAL_KEY = 'edupulse:last-revealed-level';
const sparkleColors = ['#FBBF24', '#3B82F6', '#7C3AED', '#10B981', '#F59E0B'];

const page = usePage();
const flash = computed(() => (page.props as any).flash);
const user = computed(() => (page.props.auth as any).user);
const totalPoints = computed(() => user.value?.total_points ?? 0);
const userLevel = computed(() => levelForPoints(totalPoints.value));
const levelTitle = computed(() => titleForLevel(userLevel.value));
const xpNextLevel = computed(() => nextLevelPoints(totalPoints.value));
const xpProgress = computed(() => progressForLevel(totalPoints.value));
const xpProgressPct = computed(() => progressPct(totalPoints.value));
const showDelete = ref(false);

const secretRewards = computed<SecretReward[]>(() => {
    if (props.rewardsHub?.items) {
        return props.rewardsHub.items.filter((item) => item.unlocked);
    }

    return (props.secretRewards || []).map((reward) => ({
        ...reward,
        used: usedLevels.value.has(reward.level),
        active: false,
        equipped: false,
        type: 'perk',
    }));
});
const usedLevels = computed(() => new Set(props.usedRewards || []));
const usingLevel = ref<number | null>(null);

function useReward(level: number) {
    if (usingLevel.value !== null) {
        return;
    }

    usingLevel.value = level;
    router.post(
        `/student/rewards/${level}/use`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                usingLevel.value = null;
            },
        },
    );
}
const lockedSlots = computed(() =>
    Array.from({ length: 5 }, (_, i) => ({
        level: userLevel.value + 1 + i,
    })),
);

const showReveal = ref(false);
const levelUpReward = ref<{
    level: number;
    title: string;
    description: string;
} | null>(null);

const sparkles = computed(() =>
    Array.from({ length: 12 }, (_, i) => ({
        x: 8 + ((i * 37) % 84),
        y: 4 + ((i * 53) % 82),
        size: 4 + ((i * 7) % 9),
        color: sparkleColors[i % sparkleColors.length],
        delay: (i % 6) * 0.15,
    })),
);

function revealOnLevelUp() {
    const rewards = secretRewards.value;

    if (!rewards.length) {
        return;
    }

    const maxUnlockedLevel = Math.max(
        ...rewards.map((reward) => reward.level),
    );
    let lastSeen = 0;

    try {
        lastSeen = Number(localStorage.getItem(REVEAL_KEY) || 0);
    } catch {
        /* ignore */
    }

    if (maxUnlockedLevel <= lastSeen) {
        return;
    }

    const newest = rewards.find(
        (reward) => reward.level === maxUnlockedLevel,
    );

    if (!newest) {
        return;
    }

    levelUpReward.value = newest;
    showReveal.value = true;

    try {
        localStorage.setItem(REVEAL_KEY, String(maxUnlockedLevel));
    } catch {
        /* ignore */
    }
}

function claimReward() {
    showReveal.value = false;
    levelUpReward.value = null;
}

function useLevelUpReward() {
    if (!levelUpReward.value || usingLevel.value !== null) {
        return;
    }

    const level = levelUpReward.value.level;
    usingLevel.value = level;
    router.post(
        `/student/rewards/${level}/use`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                usingLevel.value = null;
                showReveal.value = false;
                levelUpReward.value = null;
            },
        },
    );
}

onMounted(() => {
    revealOnLevelUp();
});

const profileForm = useForm({
    name: user.value.name,
    email: user.value.email,
    gender: user.value.gender || '',
    grade_level_id: user.value.grade_level_id ?? '',
    section_id: user.value.section_id ?? '',
});

const filteredSections = computed(() =>
    profileForm.grade_level_id
        ? (props.sections || []).filter(
              (section: StudentSection) =>
                  String(section.grade_level_id) ===
                  String(profileForm.grade_level_id),
          )
        : props.sections || [],
);

function updateProfile() {
    profileForm.patch('/settings/profile', { preserveScroll: true });
}

function resendVerification() {
    router.post(send.url());
}

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function updatePassword() {
    passwordForm.put('/settings/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

const deleteForm = useForm({ password: '' });

function deleteAccount() {
    deleteForm.delete('/settings/profile', {
        preserveScroll: true,
        onSuccess: () => {
            showDelete.value = false;
        },
    });
}
</script>
