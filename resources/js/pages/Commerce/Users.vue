<template>
    <Head title="ELYX Accounts" />

    <div class="commerce-users-workspace px-4 py-5 sm:px-6 sm:py-7">
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
        v-if="isImpersonating"
        class="gl-fade-in mb-6 rounded-xl px-4 py-3 text-sm"
        style="
            background: var(--gl-warning-bg);
            color: var(--gl-accent);
            border: 1px solid rgba(251, 191, 36, 0.2);
        "
    >
        <span class="font-medium"
            >Impersonating {{ page.props.auth.user.name }}</span
        >
        <button
            @click="leaveImpersonation"
            class="ml-auto rounded-lg px-3 py-1 text-xs font-semibold"
            style="background: var(--gl-accent); color: #0f172a"
        >
            Stop impersonating
        </button>
    </div>

    <!-- Header -->
    <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
        <div>
            <p
                class="text-xs font-semibold tracking-widest uppercase"
                style="color: var(--gl-text-muted)"
            >
                Account directory
            </p>
            <h1
                class="mt-1 text-2xl font-extrabold tracking-tight sm:text-3xl"
                style="color: var(--gl-text-primary)"
            >
                Accounts & sellers
            </h1>
            <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                Manage buyers, sellers, and platform access across ELYX Studio.
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
                    v-model="search"
                    type="text"
                    placeholder="Search by name or email..."
                    class="rounded-xl border py-2.5 pr-3 pl-9 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                    style="
                        background: var(--gl-surface);
                        color: var(--gl-text-primary);
                        border-color: var(--gl-border);
                        width: 210px;
                    "
                />
            </div>
            <select
                v-model="roleFilter"
                class="rounded-xl border px-3 py-2.5 text-sm outline-none"
                style="
                    background: var(--gl-surface);
                    color: var(--gl-text-primary);
                    border-color: var(--gl-border);
                "
            >
                <option value="">All account types</option>
                <option value="buyer">Buyers</option>
                <option value="seller">Sellers</option>
                <option value="superadmin">Administrators</option>
                <option value="student">Students</option>
                <option value="teacher">Legacy staff</option>
            </select>
            <button
                v-if="isSuper"
                @click="openMerge()"
                class="flex h-10 items-center gap-1.5 rounded-xl border px-3 text-xs font-semibold transition-all hover:scale-105"
                style="
                    background: var(--gl-surface);
                    border-color: rgba(245, 158, 11, 0.35);
                    color: #f59e0b;
                "
                title="Merge duplicate user accounts"
            >
                <GitMerge class="h-4 w-4" :stroke-width="2.5" />
                <span class="hidden sm:inline">Merge</span>
            </button>
            <button
                @click="openAddUser"
                class="flex h-10 w-10 items-center justify-center rounded-xl text-white transition-all hover:scale-105"
                style="
                    background: linear-gradient(135deg, #27a9e8, #0f6ea8);
                    box-shadow: 0 4px 14px rgba(15, 110, 168, 0.28);
                "
                title="Add user"
            >
                <Plus class="h-5 w-5" :stroke-width="2.5" />
            </button>
        </div>
    </div>

    <section class="commerce-users-summary" aria-label="Account summary">
        <div class="commerce-users-summary-card commerce-users-summary-card--total">
            <span class="commerce-users-summary-icon"><Users class="h-4 w-4" /></span>
            <span><strong>{{ localUsers.length }}</strong><small>Total accounts</small></span>
        </div>
        <div class="commerce-users-summary-card">
            <span class="commerce-users-summary-icon"><ShoppingBag class="h-4 w-4" /></span>
            <span><strong>{{ roleCounts.buyers }}</strong><small>Buyers</small></span>
        </div>
        <div class="commerce-users-summary-card">
            <span class="commerce-users-summary-icon"><Store class="h-4 w-4" /></span>
            <span><strong>{{ roleCounts.sellers }}</strong><small>Sellers</small></span>
        </div>
        <div class="commerce-users-summary-card">
            <span class="commerce-users-summary-icon"><ShieldCheck class="h-4 w-4" /></span>
            <span><strong>{{ roleCounts.admins }}</strong><small>Administrators</small></span>
        </div>
    </section>

    <!-- Bulk bar -->
    <div
        v-if="selected.length"
        class="mb-4 flex flex-wrap items-center gap-2 rounded-2xl px-4 py-3"
        style="
            background: var(--gl-surface);
            border: 1px solid var(--gl-border);
        "
    >
        <span
            class="text-xs font-semibold"
            style="color: var(--gl-text-primary)"
            >{{ selected.length }} selected</span
        >
        <button
            @click="bulkDelete"
            class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
            style="
                background: var(--gl-danger-bg);
                color: var(--gl-danger);
                border: 1px solid rgba(239, 68, 68, 0.25);
            "
        >
            <Trash2 class="h-3.5 w-3.5" :stroke-width="2" /> Delete
        </button>
        <button
            @click="selected = []"
            class="ml-auto text-xs font-medium transition-colors hover:opacity-80"
            style="color: var(--gl-text-muted)"
        >
            Clear
        </button>
    </div>
    <label
        v-else
        class="mb-4 flex cursor-pointer items-center gap-2 text-xs font-medium"
        style="color: var(--gl-text-muted)"
    >
        <input
            type="checkbox"
            :checked="allChecked"
            @change="toggleAll"
            class="gl-check"
        />
        Select all {{ filteredUsers.length }} accounts
    </label>

    <div class="grid grid-cols-1 items-start gap-5 xl:grid-cols-3">
        <!-- LIST (2/3) -->
        <div class="xl:col-span-2">
            <div
                v-if="!filteredUsers.length"
                class="gl-glow-card flex flex-col items-center justify-center px-6 py-16 text-center"
            >
                <div
                    class="mb-4 flex h-12 w-12 items-center justify-center rounded-full"
                    style="background: var(--gl-surface-2)"
                >
                    <Users
                        class="h-6 w-6"
                        :stroke-width="1.75"
                        style="color: var(--gl-text-muted)"
                    />
                </div>
                <p
                    class="text-sm font-medium"
                    style="color: var(--gl-text-secondary)"
                >
                    No users found
                </p>
                <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                    Try a different search term.
                </p>
            </div>

            <template v-else>
                <div
                    class="mb-1 hidden grid-cols-[1fr_130px_110px_110px_40px] items-center gap-3 px-4 pb-2 text-[11px] font-semibold tracking-wider uppercase sm:grid"
                    style="color: var(--gl-text-muted)"
                >
                    <span>Account</span><span>Type</span><span>Profile</span
                    ><span>Joined</span><span></span>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="u in paged"
                        :key="u.id"
                        @click="selectedId = u.id"
                        class="grid cursor-pointer grid-cols-[auto_1fr_40px] items-center gap-3 rounded-2xl px-4 py-3 transition-all sm:grid-cols-[1fr_130px_110px_110px_40px]"
                        :style="
                            u.id === selectedId
                                ? {
                                      background:
                                          'linear-gradient(135deg, #0f6ea8, #27a9e8)',
                                      boxShadow:
                                          '0 4px 18px rgba(15,110,168,0.24)',
                                      border: '1px solid transparent',
                                  }
                                : {
                                      background: 'var(--gl-surface)',
                                      border: '1px solid var(--gl-border)',
                                  }
                        "
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <input
                                type="checkbox"
                                :value="u.id"
                                v-model="selected"
                                :disabled="
                                    u.id === (page.props.auth as any)?.user?.id
                                "
                                @click.stop
                                class="gl-check"
                                :title="'Select ' + u.name"
                            />
                            <div
                                class="relative flex h-9 w-9 shrink-0 items-center justify-center overflow-hidden rounded-full text-[11px] font-bold text-white"
                                :style="{
                                    background: u.avatar
                                        ? 'transparent'
                                        : 'linear-gradient(135deg, ' +
                                          avatarColor(u.name) +
                                          ', ' +
                                          avatarColor2(u.name) +
                                          ')',
                                }"
                            >
                                <img
                                    v-if="u.avatar"
                                    :src="u.avatar"
                                    :alt="u.name"
                                    class="h-full w-full object-cover"
                                />
                                <span v-else>{{ initials(u.name) }}</span>
                            </div>
                            <div class="min-w-0">
                                <p
                                    class="truncate text-sm font-semibold"
                                    :style="{
                                        color:
                                            u.id === selectedId
                                                ? '#fff'
                                                : 'var(--gl-text-primary)',
                                    }"
                                >
                                    {{ u.name }}
                                </p>
                                <p
                                    class="truncate text-[11px]"
                                    :style="{
                                        color:
                                            u.id === selectedId
                                                ? 'rgba(255,255,255,0.75)'
                                                : 'var(--gl-text-muted)',
                                    }"
                                >
                                    {{ u.email }}
                                </p>
                                <div
                                    v-if="
                                        u.role === 'teacher' &&
                                        u.subjects?.length
                                    "
                                    class="mt-1 flex flex-wrap gap-1"
                                >
                                    <span
                                        v-for="sub in u.subjects"
                                        :key="sub.id"
                                        class="rounded-md px-1.5 py-0.5 text-[10px] font-semibold"
                                        :style="{
                                            background:
                                                u.id === selectedId
                                                    ? 'rgba(255,255,255,0.22)'
                                                    : 'rgba(59,130,246,0.12)',
                                            color:
                                                u.id === selectedId
                                                    ? '#fff'
                                                    : 'var(--gl-primary)',
                                        }"
                                    >
                                        {{ sub.name }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <span class="hidden sm:block"
                            ><StatusBadge :status="u.role"
                        /></span>
                        <span
                            class="hidden truncate text-xs sm:block"
                            :style="{
                                color:
                                    u.id === selectedId
                                        ? 'rgba(255,255,255,0.9)'
                                        : 'var(--gl-text-secondary)',
                            }"
                            >{{ u.role === 'seller' ? 'Seller profile' : u.role === 'buyer' ? 'Customer profile' : u.gradeLevel?.name ?? u.grade ?? 'Platform' }}</span
                        >
                        <span
                            class="hidden truncate text-xs sm:block"
                            :style="{
                                color:
                                    u.id === selectedId
                                        ? 'rgba(255,255,255,0.9)'
                                        : 'var(--gl-text-secondary)',
                            }"
                            >{{ formatDate(u.created_at) }}</span
                        >
                        <div class="relative flex justify-end">
                            <button
                                @click.stop="
                                    openMenu = openMenu === u.id ? null : u.id
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
                                v-if="openMenu === u.id"
                                class="absolute top-9 right-0 z-20 w-44 overflow-hidden rounded-xl py-1"
                                style="
                                    background: var(--gl-surface);
                                    border: 1px solid var(--gl-border);
                                    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.18);
                                "
                            >
                                <button
                                    @click="
                                        openMenu = null;
                                        openEdit(u);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(124,58,237,0.08)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <Pencil
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-secondary)"
                                        :stroke-width="2"
                                    />
                                    Edit
                                </button>
                                <button
                                    v-if="canImpersonate(u)"
                                    @click="
                                        openMenu = null;
                                        impersonate(u);
                                    "
                                    :disabled="impersonatingId === u.id"
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)] disabled:opacity-50"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <Loader2
                                        v-if="impersonatingId === u.id"
                                        class="h-3.5 w-3.5 animate-spin"
                                        style="color: var(--gl-primary)"
                                    />
                                    <UserCheck
                                        v-else
                                        class="h-3.5 w-3.5"
                                        style="color: var(--gl-primary)"
                                        :stroke-width="2"
                                    />
                                    {{
                                        impersonatingId === u.id
                                            ? 'Impersonating...'
                                            : 'Impersonate'
                                    }}
                                </button>
                                <button
                                    v-if="isSuper && u.id !== myId"
                                    @click="
                                        openMenu = null;
                                        openMerge(u);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[rgba(245,158,11,0.08)]"
                                    style="color: var(--gl-text-primary)"
                                >
                                    <GitMerge
                                        class="h-3.5 w-3.5 text-amber-500"
                                        :stroke-width="2"
                                    />
                                    Merge into...
                                </button>
                                <button
                                    v-if="canDelete(u)"
                                    @click="
                                        openMenu = null;
                                        confirmDelete(u);
                                    "
                                    class="flex w-full items-center gap-2 px-3.5 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-danger-bg)]"
                                    style="color: var(--gl-danger)"
                                >
                                    <Trash2
                                        class="h-3.5 w-3.5"
                                        :stroke-width="2"
                                    />
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="mt-4 flex flex-wrap items-center justify-between gap-3"
                >
                    <p class="text-xs" style="color: var(--gl-text-muted)">
                        {{ rangeLabel }}
                    </p>
                    <div v-if="totalPages > 1" class="flex items-center gap-1">
                        <button
                            @click="pageNum = Math.max(1, pageNum - 1)"
                            :disabled="pageNum === 1"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-bold disabled:opacity-40"
                            style="
                                background: var(--gl-surface);
                                border: 1px solid var(--gl-border);
                                color: var(--gl-text-secondary);
                            "
                        >
                            ‹
                        </button>
                        <button
                            v-for="p in pageNums"
                            :key="p"
                            @click="pageNum = p"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-bold tabular-nums"
                            :style="
                                p === pageNum
                                    ? {
                                          background:
                                              'linear-gradient(135deg, #0f6ea8, #27a9e8)',
                                          color: '#fff',
                                      }
                                    : {
                                          background: 'var(--gl-surface)',
                                          border: '1px solid var(--gl-border)',
                                          color: 'var(--gl-text-secondary)',
                                      }
                            "
                        >
                            {{ p }}
                        </button>
                        <button
                            @click="pageNum = Math.min(totalPages, pageNum + 1)"
                            :disabled="pageNum === totalPages"
                            class="rounded-lg px-2.5 py-1.5 text-xs font-bold disabled:opacity-40"
                            style="
                                background: var(--gl-surface);
                                border: 1px solid var(--gl-border);
                                color: var(--gl-text-secondary);
                            "
                        >
                            ›
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <!-- DETAIL (1/3) -->
        <div
            v-if="selectedUser"
            class="rounded-2xl p-5 xl:sticky xl:top-6"
            style="
                background: var(--gl-surface);
                border: 1px solid var(--gl-border);
            "
        >
            <div class="flex items-start gap-3">
                <div
                    class="relative flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full text-sm font-bold text-white"
                    :style="{
                        background: selectedUser.avatar
                            ? 'transparent'
                            : 'linear-gradient(135deg, ' +
                              avatarColor(selectedUser.name) +
                              ', ' +
                              avatarColor2(selectedUser.name) +
                              ')',
                    }"
                >
                    <img
                        v-if="selectedUser.avatar"
                        :src="selectedUser.avatar"
                        :alt="selectedUser.name"
                        class="h-full w-full object-cover"
                    />
                    <span v-else>{{ initials(selectedUser.name) }}</span>
                </div>
                <div class="min-w-0 flex-1">
                    <h2
                        class="truncate text-lg font-extrabold tracking-tight"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ selectedUser.name }}
                    </h2>
                    <p
                        class="truncate text-xs"
                        style="color: var(--gl-text-muted)"
                    >
                        {{ selectedUser.email }}
                    </p>
                </div>
            </div>
            <div class="mt-3"><StatusBadge :status="selectedUser.role" /></div>

            <div
                class="mt-4 space-y-2.5 border-t pt-4"
                style="border-color: var(--gl-border)"
            >
                <div>
                    <p class="text-[11px]" style="color: var(--gl-text-muted)">
                        Email
                    </p>
                    <p
                        class="truncate text-xs font-semibold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ selectedUser.email }}
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-2.5">
                    <div>
                        <p
                            class="text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Role
                        </p>
                        <p
                            class="text-xs font-semibold capitalize"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ selectedUser.role }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Gender
                        </p>
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ selectedUser.gender || '—' }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Grade
                        </p>
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{
                                selectedUser.gradeLevel?.name ??
                                selectedUser.grade ??
                                '—'
                            }}
                        </p>
                    </div>
                    <div>
                        <p
                            class="text-[11px]"
                            style="color: var(--gl-text-muted)"
                        >
                            Section
                        </p>
                        <p
                            class="text-xs font-semibold"
                            style="color: var(--gl-text-primary)"
                        >
                            {{ selectedUser.section?.name ?? '—' }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-1.5 pt-1">
                    <button
                        @click="openEdit(selectedUser)"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors hover:bg-[rgba(124,58,237,0.08)]"
                        style="
                            color: var(--gl-text-secondary);
                            border: 1px solid var(--gl-border);
                        "
                        title="Edit"
                    >
                        <Pencil class="h-3.5 w-3.5" :stroke-width="2" /> Edit
                    </button>
                    <button
                        v-if="canImpersonate(selectedUser)"
                        @click="impersonate(selectedUser)"
                        :disabled="impersonatingId === selectedUser.id"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors hover:bg-[rgba(59,130,246,0.08)] disabled:opacity-50"
                        style="
                            color: var(--gl-primary);
                            border: 1px solid var(--gl-border);
                        "
                        title="Impersonate"
                    >
                        <Loader2
                            v-if="impersonatingId === selectedUser.id"
                            class="h-3.5 w-3.5 animate-spin"
                        />
                        <UserCheck
                            v-else
                            class="h-3.5 w-3.5"
                            :stroke-width="2"
                        />
                    </button>
                    <button
                        v-if="isSuper && selectedUser.id !== myId"
                        @click="openMerge(selectedUser)"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors hover:bg-[rgba(245,158,11,0.08)]"
                        style="
                            color: #f59e0b;
                            border: 1px solid rgba(245, 158, 11, 0.3);
                        "
                        title="Merge account"
                    >
                        <GitMerge class="h-3.5 w-3.5" :stroke-width="2" />
                        <span>Merge</span>
                    </button>
                    <button
                        v-if="canDelete(selectedUser)"
                        @click="confirmDelete(selectedUser)"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium transition-colors hover:bg-[var(--gl-danger-bg)]"
                        style="
                            color: var(--gl-danger);
                            border: 1px solid rgba(239, 68, 68, 0.2);
                        "
                        title="Delete"
                    >
                        <Trash2 class="h-3.5 w-3.5" :stroke-width="2" />
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- click-away for row menu -->
    <div
        v-if="openMenu !== null"
        class="fixed inset-0 z-10"
        style="background: rgba(0, 0, 0, 0.25)"
        @click="openMenu = null"
    ></div>

    <Teleport to="body">
        <div
            v-if="showAddUser"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="showAddUser = false"
        >
            <div
                class="no-scrollbar max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <h3
                    class="mb-4 text-base font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Add user
                </h3>
                <form @submit.prevent="submitAddUser" class="space-y-4">
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Name</label
                        >
                        <input
                            v-model="addForm.name"
                            type="text"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                        <p
                            v-if="addError.name"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ addError.name }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Email</label
                        >
                        <input
                            v-model="addForm.email"
                            type="email"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                        <p
                            v-if="addError.email"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ addError.email }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Gender</label
                        >
                        <select
                            v-model="addForm.gender"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
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
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Role</label
                        >
                        <select
                            v-model="addForm.role"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        >
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="superadmin">Superadmin</option>
                            <option value="buyer">Buyer</option>
                            <option value="seller">Seller</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Grade Level</label
                        >
                        <select
                            v-model="addForm.grade_level_id"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
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
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Section</label
                        >
                        <select
                            v-model="addForm.section_id"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        >
                            <option value="">—</option>
                            <option
                                v-for="s in addSections"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                    </div>
                    <div v-if="addForm.role === 'teacher'">
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Assigned Subjects</label
                        >
                        <div
                            v-if="props.subjects?.length"
                            class="flex flex-wrap gap-1.5 rounded-xl border p-2.5"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                            "
                        >
                            <button
                                type="button"
                                v-for="sub in props.subjects"
                                :key="sub.id"
                                @click="toggleAddSubject(sub.id)"
                                class="rounded-lg px-2.5 py-1 text-xs font-semibold transition-all"
                                :style="
                                    addForm.subject_ids.includes(sub.id)
                                        ? {
                                              background: 'var(--gl-primary)',
                                              color: '#fff',
                                          }
                                        : {
                                              background: 'var(--gl-surface)',
                                              color: 'var(--gl-text-secondary)',
                                              border: '1px solid var(--gl-border)',
                                          }
                                "
                            >
                                {{ sub.name }}
                            </button>
                        </div>
                        <p
                            v-else
                            class="text-xs italic"
                            style="color: var(--gl-text-muted)"
                        >
                            No active subjects available to assign.
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Password</label
                        >
                        <input
                            v-model="addForm.password"
                            type="password"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                        <p
                            v-if="addError.password"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ addError.password }}
                        </p>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="showAddUser = false"
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
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    #27a9e8,
                                    #0f6ea8
                                );
                            "
                        >
                            Create
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <Teleport to="body">
        <div
            v-if="editing"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="editing = null"
        >
            <div
                class="no-scrollbar max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
                "
            >
                <h3
                    class="mb-4 text-base font-semibold"
                    style="color: var(--gl-text-primary)"
                >
                    Edit user
                </h3>
                <form @submit.prevent="saveEdit" class="space-y-4">
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Name</label
                        >
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                        <p
                            v-if="editError.name"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ editError.name }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Email</label
                        >
                        <input
                            v-model="editForm.email"
                            type="email"
                            required
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                        <p
                            v-if="editError.email"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ editError.email }}
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Gender</label
                        >
                        <select
                            v-model="editForm.gender"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
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
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Role</label
                        >
                        <select
                            v-model="editForm.role"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        >
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="superadmin">Superadmin</option>
                            <option value="buyer">Buyer</option>
                            <option value="seller">Seller</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Grade Level</label
                        >
                        <select
                            v-model="editForm.grade_level_id"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
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
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Section</label
                        >
                        <select
                            v-model="editForm.section_id"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        >
                            <option value="">—</option>
                            <option
                                v-for="s in editSections"
                                :key="s.id"
                                :value="s.id"
                            >
                                {{ s.name }}
                            </option>
                        </select>
                    </div>
                    <div v-if="editForm.role === 'teacher'">
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Assigned Subjects</label
                        >
                        <div
                            v-if="props.subjects?.length"
                            class="flex flex-wrap gap-1.5 rounded-xl border p-2.5"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                            "
                        >
                            <button
                                type="button"
                                v-for="sub in props.subjects"
                                :key="sub.id"
                                @click="toggleEditSubject(sub.id)"
                                class="rounded-lg px-2.5 py-1 text-xs font-semibold transition-all"
                                :style="
                                    editForm.subject_ids.includes(sub.id)
                                        ? {
                                              background: 'var(--gl-primary)',
                                              color: '#fff',
                                          }
                                        : {
                                              background: 'var(--gl-surface)',
                                              color: 'var(--gl-text-secondary)',
                                              border: '1px solid var(--gl-border)',
                                          }
                                "
                            >
                                {{ sub.name }}
                            </button>
                        </div>
                        <p
                            v-else
                            class="text-xs italic"
                            style="color: var(--gl-text-muted)"
                        >
                            No active subjects available to assign.
                        </p>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >New Password
                            <span
                                class="text-xs"
                                style="color: var(--gl-text-muted)"
                                >(leave blank to keep current)</span
                            ></label
                        >
                        <input
                            v-model="editForm.password"
                            type="password"
                            autocomplete="new-password"
                            class="w-full rounded-xl border px-3.5 py-2.5 text-sm outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                        <p
                            v-if="editError.password"
                            class="mt-1 text-xs"
                            style="color: var(--gl-danger)"
                        >
                            {{ editError.password }}
                        </p>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="editing = null"
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
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                            "
                        >
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <UserMergeModal
        v-model:is-open="showMergeModal"
        :users="localUsers"
        :initial-source-id="mergeSourceId"
        :initial-target-id="mergeTargetId"
        :base-path="basePath"
    />
    </div>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Pencil,
    Trash2,
    Plus,
    UserCheck,
    Search,
    Users,
    ShoppingBag,
    ShieldCheck,
    Store,
    Ellipsis,
    Loader2,
    GitMerge,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import StatusBadge from '@/components/StatusBadge.vue';
import UserMergeModal from '@/components/UserMergeModal.vue';

const page = usePage();
const flash = computed(() => (page.props as any).flash);

const props = defineProps<{
    users: any[];
    gradeLevels: { id: number; name: string }[];
    sections: { id: number; name: string; grade_level_id: number }[];
    subjects?: { id: number; name: string }[];
    basePath?: string;
}>();

const basePath = computed(() => props.basePath || '/admin/users');

const localUsers = ref<any[]>([...(props.users || [])]);

const roleCounts = computed(() => ({
    buyers: localUsers.value.filter((user) => user.role === 'buyer').length,
    sellers: localUsers.value.filter((user) => user.role === 'seller').length,
    admins: localUsers.value.filter((user) => user.role === 'superadmin').length,
}));

const showMergeModal = ref(false);
const mergeSourceId = ref<number | null>(null);
const mergeTargetId = ref<number | null>(null);

function openMerge(sourceUser?: any) {
    mergeSourceId.value = sourceUser ? sourceUser.id : null;
    mergeTargetId.value = null;
    showMergeModal.value = true;
}

watch(
    () => props.users,
    (val) => {
        localUsers.value = [...(val || [])];
    },
    { deep: true },
);

const editing = ref<any>(null);
const editForm = ref({
    name: '',
    email: '',
    gender: '',
    role: '',
    grade_level_id: '',
    section_id: '',
    password: '',
    subject_ids: [] as number[],
});
const editError = ref<any>({});

const showAddUser = ref(false);
const addForm = ref({
    name: '',
    email: '',
    gender: '',
    role: 'student',
    grade_level_id: '',
    section_id: '',
    password: '',
    subject_ids: [] as number[],
});
const addError = ref<any>({});

function toggleEditSubject(id: number) {
    const idx = editForm.value.subject_ids.indexOf(id);

    if (idx > -1) {
        editForm.value.subject_ids.splice(idx, 1);
    } else {
        editForm.value.subject_ids.push(id);
    }
}

function toggleAddSubject(id: number) {
    const idx = addForm.value.subject_ids.indexOf(id);

    if (idx > -1) {
        addForm.value.subject_ids.splice(idx, 1);
    } else {
        addForm.value.subject_ids.push(id);
    }
}

const addSections = computed(() =>
    addForm.value.grade_level_id
        ? props.sections.filter(
              (s) =>
                  String(s.grade_level_id) ===
                  String(addForm.value.grade_level_id),
          )
        : props.sections,
);

const editSections = computed(() =>
    editForm.value.grade_level_id
        ? props.sections.filter(
              (s) =>
                  String(s.grade_level_id) ===
                  String(editForm.value.grade_level_id),
          )
        : props.sections,
);

const search = ref('');
const roleFilter = ref('');
const openMenu = ref<number | null>(null);

const filteredUsers = computed(() => {
    return localUsers.value.filter((u) => {
        if (search.value) {
            const q = search.value.toLowerCase();

            if (
                !u.name.toLowerCase().includes(q) &&
                !u.email.toLowerCase().includes(q)
            ) {
                return false;
            }
        }

        if (roleFilter.value && u.role !== roleFilter.value) {
            return false;
        }

        return true;
    });
});

// Selection + pagination
const selectedId = ref<number | null>(null);
const pageNum = ref(1);
const PAGE_SIZE = 10;

const selectedUser = computed(() => {
    const list = filteredUsers.value;

    if (!list.length) {
        return null;
    }

    return list.find((u) => u.id === selectedId.value) ?? list[0];
});

watch(
    selectedUser,
    (u) => {
        if (u) {
            selectedId.value = u.id;
        }
    },
    { immediate: true },
);

const totalPages = computed(() =>
    Math.max(1, Math.ceil(filteredUsers.value.length / PAGE_SIZE)),
);
const paged = computed(() =>
    filteredUsers.value.slice(
        (pageNum.value - 1) * PAGE_SIZE,
        pageNum.value * PAGE_SIZE,
    ),
);
const rangeLabel = computed(() => {
    const total = filteredUsers.value.length;

    if (!total) {
        return '0 results';
    }

    const from = (pageNum.value - 1) * PAGE_SIZE + 1;

    return `${from}–${Math.min(pageNum.value * PAGE_SIZE, total)} of ${total} results`;
});
const pageNums = computed(() => {
    const t = totalPages.value,
        c = pageNum.value;
    const nums = new Set<number>([1, t, c - 1, c, c + 1]);

    return [...nums].filter((n) => n >= 1 && n <= t).sort((a, b) => a - b);
});

watch([search, roleFilter], () => {
    pageNum.value = 1;
    openMenu.value = null;
});
watch(filteredUsers, () => {
    if (pageNum.value > totalPages.value) {
        pageNum.value = totalPages.value;
    }
});

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
}

function initials(name: string) {
    return (name || '')
        .split(' ')
        .map((w: string) => w[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}
function avatarColor(name: string) {
    const c = [
        '#3B82F6',
        '#10B981',
        '#F59E0B',
        '#EF4444',
        '#7C3AED',
        '#EC4899',
        '#06B6D4',
    ];
    let h = 0;
    const n = name || '';

    for (let i = 0; i < n.length; i++) {
        h = n.charCodeAt(i) + ((h << 5) - h);
    }

    return c[Math.abs(h) % c.length];
}
function avatarColor2(name: string) {
    const c = [
        '#2563EB',
        '#059669',
        '#D97706',
        '#DC2626',
        '#6D28D9',
        '#DB2777',
        '#0891B2',
    ];
    let h = 0;
    const n = name || '';

    for (let i = 0; i < n.length; i++) {
        h = n.charCodeAt(i) + ((h << 7) - h);
    }

    return c[Math.abs(h) % c.length];
}

const myId = computed(() => (page.props.auth as any)?.user?.id);
const isSuper = computed(() => !!(page.props.auth as any)?.user?.is_superadmin);
const isImpersonating = computed(() => !!(page.props as any).impersonated_by);

function canDelete(u: any): boolean {
    return u.id !== myId.value && !isImpersonating.value;
}
function canImpersonate(u: any): boolean {
    return u.id !== myId.value && isSuper.value && !isImpersonating.value;
}

function openAddUser() {
    addForm.value = {
        name: '',
        email: '',
        gender: '',
        role: 'student',
        grade_level_id: '',
        section_id: '',
        password: '',
    };
    addError.value = {};
    showAddUser.value = true;
}

function submitAddUser() {
    addError.value = {};
    router.post(basePath.value, addForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            showAddUser.value = false;
        },
        onError: (errors) => {
            addError.value = errors;
        },
    });
}

function openEdit(u: any) {
    editForm.value = {
        name: u.name,
        email: u.email,
        gender: u.gender || '',
        role: u.role,
        grade_level_id: u.grade_level_id ?? '',
        section_id: u.section_id ?? '',
        password: '',
        subject_ids: (u.subjects || []).map((s: any) => s.id),
    };
    editError.value = {};
    editing.value = u;
}

function saveEdit() {
    editError.value = {};
    router.put(`${basePath.value}/${editing.value.id}`, editForm.value, {
        preserveScroll: true,
        onSuccess: () => {
            editing.value = null;
        },
        onError: (errors) => {
            editError.value = errors;
        },
    });
}

function confirmDelete(u: any) {
    if (
        !confirm(`Delete user "${u.name}" (${u.email})? This cannot be undone.`)
    ) {
        return;
    }

    openMenu.value = null;
    const backup = [...localUsers.value];
    localUsers.value = localUsers.value.filter((item: any) => item.id !== u.id);
    selected.value = selected.value.filter((id) => id !== u.id);
    router.delete(`${basePath.value}/${u.id}`, {
        preserveScroll: true,
        onError: () => {
            localUsers.value = backup;
        },
    });
}

const selected = ref<number[]>([]);
const allChecked = computed(() => {
    const selectable = filteredUsers.value.filter(
        (u: any) => u.id !== myId.value,
    );

    return (
        selectable.length > 0 &&
        selectable.every((u: any) => selected.value.includes(u.id))
    );
});

function toggleAll() {
    const selectable = filteredUsers.value
        .filter((u: any) => u.id !== myId.value)
        .map((u: any) => u.id);
    selected.value = allChecked.value ? [] : selectable;
}

function bulkDelete() {
    if (!selected.value.length) {
        return;
    }

    if (
        !confirm(
            `Delete ${selected.value.length} user(s)? This cannot be undone.`,
        )
    ) {
        return;
    }

    const toDeleteIds = new Set(selected.value);
    const backup = [...localUsers.value];
    localUsers.value = localUsers.value.filter(
        (u: any) => !toDeleteIds.has(u.id),
    );
    const ids = [...selected.value];
    selected.value = [];
    router.delete(`${basePath.value}/bulk`, {
        data: { ids },
        preserveScroll: true,
        onError: () => {
            localUsers.value = backup;
        },
    });
}

const impersonatingId = ref<number | null>(null);

function impersonate(u: any) {
    if (impersonatingId.value) {
        return;
    }

    impersonatingId.value = u.id;
    router.post(
        `${basePath.value}/${u.id}/impersonate`,
        {},
        {
            onError: () => {
                window.location.href = `${basePath.value}/${u.id}/impersonate`;
            },
            onFinish: () => {
                impersonatingId.value = null;
            },
        },
    );
}

function leaveImpersonation() {
    router.post(
        '/leave-impersonation',
        {},
        {
            onError: () => {
                window.location.href = '/leave-impersonation';
            },
        },
    );
}
</script>
