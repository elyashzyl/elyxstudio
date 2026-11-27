<template>
    <Head title="Messenger" />

    <div
        v-if="!messengerEnabled"
        class="flex min-h-0 flex-1 items-center justify-center"
        style="background: var(--gl-bg)"
    >
        <div class="text-center">
            <div
                class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl"
                style="background: var(--gl-surface-2)"
            >
                <MessageCircle
                    class="h-8 w-8"
                    style="color: var(--gl-text-muted)"
                    :stroke-width="1.5"
                />
            </div>
            <p
                class="text-lg font-semibold"
                style="color: var(--gl-text-primary)"
            >
                Messenger Unavailable
            </p>
            <p class="mt-2 text-sm" style="color: var(--gl-text-secondary)">
                The messenger has been disabled by the administrator.
            </p>
        </div>
    </div>

    <div
        v-else
        class="relative flex min-h-0 flex-1 overflow-hidden rounded-2xl"
        style="
            background: var(--gl-surface);
            border: 1px solid var(--gl-border);
        "
    >
        <!-- Conversation List -->
        <div
            class="w-full shrink-0 flex-col md:w-72"
            :class="activeConversation ? 'hidden md:flex' : 'flex'"
            style="border-right: 1px solid var(--gl-border)"
        >
            <div class="shrink-0 px-4 pt-4 pb-3">
                <div class="mb-3 flex items-center justify-between">
                    <p
                        class="text-base font-extrabold tracking-tight"
                        style="color: var(--gl-text-primary)"
                    >
                        Messages
                    </p>
                    <button
                        v-if="isTeacher || isSuperadmin"
                        @click="toggleClassmateMessaging"
                        type="button"
                        class="flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[11px] font-semibold transition-all hover:opacity-90"
                        :style="
                            teacherClassmatesAllowed
                                ? 'background: rgba(16, 185, 129, 0.12); color: var(--gl-success); border: 1px solid rgba(16, 185, 129, 0.25);'
                                : 'background: rgba(239, 68, 68, 0.12); color: var(--gl-danger); border: 1px solid rgba(239, 68, 68, 0.25);'
                        "
                        :title="
                            teacherClassmatesAllowed
                                ? 'Students can message classmates. Click to turn off.'
                                : 'Classmate messaging is disabled. Click to turn on.'
                        "
                    >
                        <span
                            class="h-2 w-2 rounded-full"
                            :style="{
                                background: teacherClassmatesAllowed
                                    ? 'var(--gl-success)'
                                    : 'var(--gl-danger)',
                            }"
                        ></span>
                        Classmates: {{ teacherClassmatesAllowed ? 'ON' : 'OFF' }}
                    </button>
                </div>

                <div
                    v-if="isStudent && !canMessageClassmates"
                    class="mb-3 flex items-start gap-2 rounded-xl p-2.5 text-xs"
                    style="
                        background: rgba(239, 68, 68, 0.08);
                        border: 1px solid rgba(239, 68, 68, 0.2);
                        color: var(--gl-danger);
                    "
                >
                    <Info class="mt-0.5 h-4 w-4 shrink-0" :stroke-width="2" />
                    <span>
                        Classmate messaging is disabled by your teacher. You can only message teachers.
                    </span>
                </div>

                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2"
                        :stroke-width="2"
                        style="color: var(--gl-text-muted)"
                    />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search"
                        class="w-full rounded-full border px-4 py-2 pl-9 text-sm transition-all outline-none focus:shadow-[0_0_0_2px_var(--gl-primary-glow)]"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border-color: var(--gl-border);
                        "
                    />
                </div>
            </div>

            <div class="messenger-scroll flex-1 overflow-y-auto px-2 pb-2">
                <button
                    v-for="c in filteredConversations"
                    :key="c.id"
                    @click="openConversation(c.id)"
                    class="mb-1 flex w-full items-center gap-3 rounded-2xl px-3 py-2.5 text-left transition-all duration-150"
                    :class="[
                        c.unread && activeId !== c.id
                            ? 'border-l-4 border-l-[var(--gl-primary,#3b82f6)] font-medium shadow-sm'
                            : 'border-l-4 border-l-transparent',
                    ]"
                    :style="
                        activeId === c.id
                            ? {
                                  background:
                                      'linear-gradient(135deg, var(--gl-primary), var(--gl-secondary))',
                                  boxShadow:
                                      '0 4px 16px var(--gl-primary-glow)',
                              }
                            : c.unread
                              ? {
                                    background: 'rgba(59,130,246,0.08)',
                                    borderColor: 'rgba(59,130,246,0.25)',
                                }
                              : { background: 'transparent' }
                    "
                >
                    <div class="relative shrink-0">
                        <div
                            v-if="c.is_group"
                            class="flex h-11 w-11 items-center justify-center rounded-full text-xs font-bold text-white"
                            style="
                                background: linear-gradient(135deg, #8b5cf6, #6366f1);
                                box-shadow: 0 2px 8px rgba(139, 92, 246, 0.3);
                            "
                        >
                            <Users class="h-5 w-5 text-white" :stroke-width="2" />
                        </div>
                        <div
                            v-else
                            class="flex h-11 w-11 items-center justify-center rounded-full text-xs font-bold text-white"
                            :style="{
                                background:
                                    'linear-gradient(135deg, ' +
                                    avatarBg(c.other?.name ?? '') +
                                    ', ' +
                                    avatarBg2(c.other?.name ?? '') +
                                    ')',
                            }"
                        >
                            <img
                                v-if="c.other?.avatar"
                                :src="c.other.avatar"
                                alt=""
                                class="h-full w-full rounded-full object-cover"
                            />
                            <template v-else>{{ initials(c.other?.name ?? '?') }}</template>
                        </div>
                        <span
                            v-if="c.unread"
                            class="gl-pulse-glow absolute -right-0.5 -bottom-0.5 flex h-3.5 w-3.5 items-center justify-center rounded-full border-2"
                            :style="{
                                background: 'var(--gl-primary, #3b82f6)',
                                borderColor:
                                    activeId === c.id
                                        ? 'var(--gl-primary)'
                                        : 'var(--gl-surface)',
                            }"
                        ></span>
                        <span
                            v-else-if="!c.is_group && isUserOnline(c.other?.id)"
                            class="absolute -right-0.5 -bottom-0.5 flex h-3 w-3 items-center justify-center rounded-full border-2 bg-emerald-500 shadow-[0_0_6px_rgba(16,185,129,0.8)]"
                            :style="{
                                borderColor:
                                    activeId === c.id
                                        ? 'var(--gl-primary)'
                                        : 'var(--gl-surface)',
                            }"
                            title="Active now"
                        ></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-2">
                            <p
                                class="truncate text-sm"
                                :class="c.unread && activeId !== c.id ? 'font-bold' : 'font-semibold'"
                                :style="{
                                    color:
                                        activeId === c.id
                                            ? '#fff'
                                            : c.unread
                                              ? 'var(--gl-text-primary)'
                                              : 'var(--gl-text-secondary)',
                                }"
                            >
                                {{ c.is_group ? (c.title || 'Group Chat') : (c.other?.name ?? 'Unknown') }}
                            </p>
                            <span
                                v-if="c.last_message_at"
                                class="shrink-0 text-[10px]"
                                :class="c.unread && activeId !== c.id ? 'font-bold' : ''"
                                :style="{
                                    color:
                                        activeId === c.id
                                            ? 'rgba(255,255,255,0.75)'
                                            : c.unread
                                              ? 'var(--gl-primary, #3b82f6)'
                                              : 'var(--gl-text-muted)',
                                }"
                            >
                                {{ formatTime(c.last_message_at) }}
                            </span>
                        </div>
                        <div
                            class="mt-0.5 flex items-center justify-between gap-2"
                        >
                            <p
                                class="truncate text-xs"
                                :style="{
                                    color:
                                        activeId === c.id
                                            ? 'rgba(255,255,255,0.85)'
                                            : c.unread
                                              ? 'var(--gl-text-primary)'
                                              : 'var(--gl-text-muted)',
                                    fontWeight: c.unread && activeId !== c.id ? 600 : 400,
                                }"
                            >
                                <span v-if="activeId === c.id && typingIndicatorText" class="text-emerald-400 font-semibold italic">
                                    typing...
                                </span>
                                <template v-else>
                                    <span v-if="c.last_mine">You: </span
                                    >{{ c.last_message || 'No messages yet' }}
                                </template>
                            </p>
                            <span
                                v-if="c.unread"
                                class="flex h-5 min-w-[20px] shrink-0 items-center justify-center rounded-full px-1.5 text-[10px] font-extrabold leading-none shadow-sm"
                                :style="{
                                    background:
                                        activeId === c.id
                                            ? 'rgba(255, 255, 255, 0.3)'
                                            : 'var(--gl-primary, #3b82f6)',
                                    color: '#fff',
                                }"
                            >
                                {{ (c.unread_count && c.unread_count > 99) ? '99+' : (c.unread_count || 1) }}
                            </span>
                        </div>
                    </div>
                </button>
                <div
                    v-if="!filteredConversations.length"
                    class="flex flex-col items-center px-4 py-16 text-center"
                >
                    <MessageCircle
                        class="mb-3 h-8 w-8"
                        style="color: var(--gl-text-muted)"
                        :stroke-width="1.5"
                    />
                    <p
                        class="text-sm font-medium"
                        style="color: var(--gl-text-secondary)"
                    >
                        No conversations yet
                    </p>
                    <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                        Click + to start messaging.
                    </p>
                </div>
            </div>

            <div
                class="shrink-0 p-3"
                style="border-top: 1px solid var(--gl-border)"
            >
                <button
                    @click="showNewModal = true"
                    class="flex w-full items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-semibold text-white transition-all hover:scale-[1.02]"
                    style="
                        background: linear-gradient(
                            135deg,
                            var(--gl-primary),
                            var(--gl-secondary)
                        );
                        box-shadow: 0 0 12px var(--gl-primary-glow);
                    "
                >
                    <Plus class="h-4 w-4" :stroke-width="2.5" /> New message
                </button>
            </div>
        </div>

        <!-- Chat Area -->
        <div
            class="min-w-0 flex-1 flex-col"
            :class="activeConversation ? 'flex' : 'hidden md:flex'"
            style="background: var(--gl-bg)"
        >
            <template v-if="activeConversation">
                <!-- Chat Header -->
                <div
                    class="flex shrink-0 items-center gap-3 px-4 py-3"
                    style="
                        background: var(--gl-surface);
                        border-bottom: 1px solid var(--gl-border);
                    "
                >
                    <button
                        @click="closeConversation"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-surface-2)] md:hidden"
                        style="color: var(--gl-text-muted)"
                        title="Back to messages"
                    >
                        <ChevronLeft class="h-5 w-5" :stroke-width="2" />
                    </button>
                    <button
                        @click="showProfile = !showProfile"
                        class="flex min-w-0 flex-1 items-center gap-3 text-left"
                        :title="activeConversation.is_group ? 'View group info' : 'View contact'"
                    >
                        <div
                            v-if="activeConversation.is_group"
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                            style="
                                background: linear-gradient(135deg, #8b5cf6, #6366f1);
                                box-shadow: 0 2px 8px rgba(139, 92, 246, 0.35);
                            "
                        >
                            <Users class="h-5 w-5 text-white" :stroke-width="2" />
                        </div>
                        <div
                            v-else
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-sm font-bold text-white"
                            :style="{
                                background:
                                    'linear-gradient(135deg, ' +
                                    avatarBg(
                                        activeConversation.other?.name ?? '',
                                    ) +
                                    ', ' +
                                    avatarBg2(
                                        activeConversation.other?.name ?? '',
                                    ) +
                                    ')',
                            }"
                        >
                            <img
                                v-if="activeConversation.other?.avatar"
                                :src="activeConversation.other.avatar"
                                alt=""
                                class="h-full w-full rounded-full object-cover"
                            />
                            <template v-else>{{
                                initials(activeConversation.other?.name ?? '?')
                            }}</template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ activeConversation.is_group ? (activeConversation.title || activeConversation.subject || 'Group Chat') : activeConversation.other?.name }}
                            </p>
                            <div v-if="activeConversation.is_group" class="mt-0.5 flex items-center gap-1.5 flex-wrap">
                                <span
                                    class="inline-block rounded px-1.5 py-px text-[9px] font-extrabold tracking-widest uppercase"
                                    style="
                                        background: rgba(139, 92, 246, 0.14);
                                        color: #8b5cf6;
                                    "
                                >
                                    {{ activeConversation.participant_count ?? activeConversation.participants?.length ?? 0 }} members
                                </span>
                                <span
                                    v-if="onlineMemberCount > 0"
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-500"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ onlineMemberCount }} online
                                </span>
                            </div>
                            <div v-else class="mt-0.5 flex items-center gap-1.5 flex-wrap">
                                <span
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold"
                                    :style="{ color: isOtherOnline ? 'var(--gl-success, #10b981)' : 'var(--gl-text-muted)' }"
                                >
                                    <span
                                        class="h-2 w-2 rounded-full"
                                        :class="isOtherOnline ? 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.7)] animate-pulse' : 'bg-gray-400 opacity-60'"
                                    ></span>
                                    {{ isOtherOnline ? 'Active now' : 'Offline' }}
                                </span>
                                <span
                                    class="inline-block rounded px-1.5 py-px text-[9px] font-extrabold tracking-widest"
                                    style="
                                        background: rgba(251, 191, 36, 0.14);
                                        color: var(--gl-accent);
                                    "
                                >
                                    {{ roleLabel(activeConversation.other?.role) }}
                                </span>
                            </div>
                        </div>
                    </button>
                    <button
                        @click="deleteConversation"
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-danger-bg)]"
                        style="color: var(--gl-text-muted)"
                        title="Delete conversation"
                    >
                        <Trash2 class="h-4 w-4" :stroke-width="2" />
                    </button>
                </div>

                <!-- Messages Area -->
                <div
                    ref="messagesRef"
                    class="messenger-scroll flex-1 overflow-y-auto px-4 py-4"
                    style="min-height: 0"
                >
                    <div
                        v-for="(item, i) in chatItems"
                        :key="
                            item.type === 'day'
                                ? 'day-' + i + '-' + item.label
                                : item.type === 'unread_separator'
                                  ? 'unread-sep-' + i
                                  : item.m.id
                        "
                    >
                        <!-- Day separator -->
                        <div
                            v-if="item.type === 'day'"
                            class="my-3 flex justify-center"
                        >
                            <span
                                class="rounded-full px-3 py-1 text-[10px] font-bold tracking-widest uppercase"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-muted);
                                "
                            >
                                {{ item.label }}
                            </span>
                        </div>
                        <!-- Unread separator -->
                        <div
                            v-else-if="item.type === 'unread_separator'"
                            class="my-4 flex items-center gap-3 px-2"
                        >
                            <div
                                class="h-px flex-1"
                                style="
                                    background: linear-gradient(
                                        to right,
                                        transparent,
                                        var(--gl-primary, #3b82f6),
                                        transparent
                                    );
                                "
                            ></div>
                            <span
                                class="flex items-center gap-1.5 rounded-full px-3 py-1 text-[10px] font-extrabold tracking-wider uppercase shadow-sm"
                                style="
                                    background: rgba(59, 130, 246, 0.12);
                                    color: var(--gl-primary, #3b82f6);
                                    border: 1px solid rgba(59, 130, 246, 0.28);
                                "
                            >
                                <span
                                    class="h-1.5 w-1.5 rounded-full animate-pulse"
                                    style="background: var(--gl-primary, #3b82f6)"
                                ></span>
                                {{ item.label || 'Unread Messages' }}
                            </span>
                            <div
                                class="h-px flex-1"
                                style="
                                    background: linear-gradient(
                                        to right,
                                        transparent,
                                        var(--gl-primary, #3b82f6),
                                        transparent
                                    );
                                "
                            ></div>
                        </div>
                        <!-- Message -->
                        <div
                            v-else
                            class="gl-fade-in group mb-3 flex items-end gap-2"
                            :class="
                                item.m.sender_id === userId
                                    ? 'justify-end'
                                    : 'justify-start'
                            "
                        >
                            <div
                                v-if="item.m.sender_id !== userId"
                                class="w-7 shrink-0"
                            >
                                <div
                                    v-if="item.showAvatar"
                                    class="flex h-7 w-7 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                    :style="{
                                        background: avatarBg(
                                            item.m.sender_name ??
                                                activeConversation.other?.name ??
                                                '',
                                        ),
                                    }"
                                >
                                    <img
                                        v-if="liveAvatar(item.m.sender_id)"
                                        :src="liveAvatar(item.m.sender_id)!"
                                        alt=""
                                        class="h-full w-full rounded-full object-cover"
                                    />
                                    <template v-else>{{
                                        initials(
                                            liveName(
                                                item.m.sender_id,
                                                item.m.sender_name ??
                                                    activeConversation.other?.name ??
                                                    '?',
                                            ),
                                        )
                                    }}</template>
                                </div>
                            </div>
                            <!-- Own message actions (edit / remove) -->
                            <div
                                v-if="
                                    item.m.sender_id === userId &&
                                    !item.m.deleted_at
                                "
                                class="flex shrink-0 flex-col gap-1 opacity-0 transition-opacity duration-150 group-hover:opacity-100"
                            >
                                <button
                                    @click="startEdit(item.m)"
                                    class="flex h-6 w-6 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-surface-2)]"
                                    style="color: var(--gl-text-muted)"
                                    title="Edit message"
                                >
                                    <Pencil class="h-3 w-3" :stroke-width="2" />
                                </button>
                                <button
                                    @click="askDeleteMessage(item.m)"
                                    class="flex h-6 w-6 items-center justify-center rounded-full transition-colors hover:bg-[var(--gl-danger-bg)]"
                                    style="color: var(--gl-text-muted)"
                                    title="Remove message"
                                >
                                    <Trash2 class="h-3 w-3" :stroke-width="2" />
                                </button>
                            </div>
                            <div class="flex max-w-[72%] flex-col">
                                <!-- Inline edit -->
                                <form
                                    v-if="editingId === item.m.id"
                                    @submit.prevent="submitEdit(item.m)"
                                    class="flex items-center gap-1.5"
                                >
                                    <input
                                        ref="editInputRef"
                                        v-model="editDraft"
                                        type="text"
                                        class="w-full rounded-2xl border px-3.5 py-2 text-sm outline-none"
                                        style="
                                            background: var(--gl-surface-2);
                                            color: var(--gl-text-primary);
                                            border-color: var(--gl-primary);
                                            box-shadow: 0 0 0 2px
                                                var(--gl-primary-glow);
                                        "
                                    />
                                    <button
                                        type="submit"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-white transition-all hover:scale-105"
                                        style="
                                            background: linear-gradient(
                                                135deg,
                                                var(--gl-primary),
                                                var(--gl-secondary)
                                            );
                                        "
                                        title="Save"
                                    >
                                        <Check
                                            class="h-3.5 w-3.5"
                                            :stroke-width="2.5"
                                        />
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelEdit"
                                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full transition-colors"
                                        style="
                                            background: var(--gl-surface-2);
                                            color: var(--gl-text-muted);
                                            border: 1px solid var(--gl-border);
                                        "
                                        title="Cancel"
                                    >
                                        <X
                                            class="h-3.5 w-3.5"
                                            :stroke-width="2.5"
                                        />
                                    </button>
                                </form>
                                <!-- Removed message placeholder -->
                                <div
                                    v-else-if="item.m.deleted_at"
                                    class="rounded-2xl border border-dashed px-3.5 py-2 text-sm italic"
                                    :class="
                                        item.m.sender_id === userId
                                            ? 'text-right'
                                            : ''
                                    "
                                    style="
                                        background: var(--gl-surface-2);
                                        color: var(--gl-text-muted);
                                        border-color: var(--gl-border);
                                    "
                                >
                                    {{
                                        item.m.sender_id === userId
                                            ? 'You removed this message'
                                            : 'This message was removed'
                                    }}
                                </div>
                                <!-- Bubble -->
                                <template v-else>
                                    <p
                                        v-if="item.m.sender_id !== userId"
                                        class="mb-1 flex items-center gap-1.5 px-1.5 text-[11px]"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        <span>{{ liveName(item.m.sender_id, item.m.sender_name ?? activeConversation.other?.name ?? 'Unknown') }}</span>
                                        <span class="opacity-70">·</span>
                                        <span>{{ formatTime(item.m.created_at) }}</span>
                                        <span
                                            v-if="item.isUnread"
                                            class="rounded-full px-1.5 py-0.2 text-[9px] font-extrabold tracking-wider uppercase shadow-xs"
                                            style="
                                                background: rgba(59, 130, 246, 0.15);
                                                color: var(--gl-primary, #3b82f6);
                                                border: 1px solid rgba(59, 130, 246, 0.28);
                                            "
                                        >
                                            New
                                        </span>
                                    </p>
                                    <p
                                        v-else
                                        class="mb-1 px-1.5 text-right text-[11px]"
                                        style="color: var(--gl-text-muted)"
                                    >
                                        You
                                        <span class="opacity-70">·</span>
                                        {{ formatTime(item.m.created_at) }}
                                    </p>
                                    <div
                                        class="px-3.5 py-2.5 text-sm leading-relaxed break-words whitespace-pre-wrap"
                                        :style="
                                            item.m.sender_id === userId
                                                ? {
                                                      background:
                                                          'linear-gradient(135deg, #3B82F6, #2563EB)',
                                                      color: '#fff',
                                                      borderRadius:
                                                          '18px 18px 6px 18px',
                                                      boxShadow:
                                                          '0 2px 10px rgba(59,130,246,0.35)',
                                                  }
                                                : {
                                                      background: item.isUnread
                                                          ? 'rgba(59, 130, 246, 0.07)'
                                                          : 'var(--gl-surface)',
                                                      color: 'var(--gl-text-primary)',
                                                      borderRadius:
                                                          '18px 18px 18px 6px',
                                                      border: item.isUnread
                                                          ? '1px solid rgba(59, 130, 246, 0.32)'
                                                          : '1px solid var(--gl-border)',
                                                  }
                                        "
                                    >
                                        <!-- Attachment Rendering -->
                                        <div v-if="item.m.attachment_url" class="mb-2">
                                            <template v-if="item.m.attachment_type?.startsWith('image/')">
                                                <a
                                                    :href="item.m.attachment_url"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="block overflow-hidden rounded-xl border border-white/10 max-w-[280px]"
                                                >
                                                    <img
                                                        :src="item.m.attachment_url"
                                                        :alt="item.m.attachment_name || 'Attachment'"
                                                        class="w-full max-h-60 object-cover hover:opacity-90 transition-opacity"
                                                        loading="lazy"
                                                    />
                                                </a>
                                            </template>
                                            <template v-else-if="isAudioAttachment(item.m)">
                                                <VoicePlayer
                                                    :src="item.m.attachment_url"
                                                    :filename="item.m.attachment_name"
                                                    :is-sender="item.m.sender_id === userId"
                                                />
                                            </template>
                                            <template v-else>
                                                <a
                                                    :href="item.m.attachment_url"
                                                    target="_blank"
                                                    rel="noopener"
                                                    download
                                                    class="flex items-center gap-2.5 rounded-xl p-2.5 transition-all text-xs border"
                                                    :class="
                                                        item.m.sender_id === userId
                                                            ? 'bg-white/15 hover:bg-white/25 border-white/25 text-white'
                                                            : 'bg-[var(--gl-surface-2)] hover:bg-[var(--gl-surface)] border-[var(--gl-border)] text-[var(--gl-text-primary)]'
                                                    "
                                                >
                                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500/20 text-blue-400">
                                                        <FileText class="h-4 w-4" />
                                                    </div>
                                                    <div class="min-w-0 flex-1 text-left">
                                                        <p class="truncate font-semibold">{{ item.m.attachment_name || 'File' }}</p>
                                                        <p class="text-[10px] opacity-75">{{ formatBytes(item.m.attachment_size) }}</p>
                                                    </div>
                                                    <Download class="h-4 w-4 shrink-0 opacity-70" />
                                                </a>
                                            </template>
                                        </div>

                                        <div v-if="item.m.body">{{ item.m.body }}</div>
                                    </div>
                                    <div
                                        class="mt-0.5 flex items-center gap-1.5 px-1.5"
                                        :class="
                                            item.m.sender_id === userId
                                                ? 'justify-end'
                                                : 'justify-start'
                                        "
                                    >
                                        <span
                                            v-if="item.m.edited_at"
                                            class="text-[10px]"
                                            style="color: var(--gl-text-muted)"
                                            >(edited)</span
                                        >
                                        <span
                                            v-if="
                                                item.m.sender_id === userId &&
                                                isRead(item.m)
                                            "
                                            class="text-[10px] font-semibold"
                                            style="color: var(--gl-primary)"
                                            >✓✓ Seen</span
                                        >
                                        <span
                                            v-else-if="
                                                item.m.sender_id === userId
                                            "
                                            class="text-[10px]"
                                            style="color: var(--gl-text-muted)"
                                            >✓ Sent</span
                                        >
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Active Typing Indicator -->
                <div
                    v-if="typingIndicatorText"
                    class="flex items-center gap-2 px-4 py-1.5 transition-all"
                    style="
                        background: var(--gl-surface);
                        border-top: 1px dashed var(--gl-border);
                    "
                >
                    <div class="flex items-center gap-1">
                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--gl-primary)] animate-bounce" style="animation-delay: 0ms"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--gl-primary)] animate-bounce" style="animation-delay: 150ms"></span>
                        <span class="h-1.5 w-1.5 rounded-full bg-[var(--gl-primary)] animate-bounce" style="animation-delay: 300ms"></span>
                    </div>
                    <span class="text-xs italic font-medium" style="color: var(--gl-text-muted)">
                        {{ typingIndicatorText }}
                    </span>
                </div>

                <!-- Chat Input -->
                <div
                    class="shrink-0 px-4 py-3"
                    style="
                        background: var(--gl-surface);
                        border-top: 1px solid var(--gl-border);
                    "
                >
                    <div
                        v-if="activeConversation.can_send === false"
                        class="flex items-center justify-center gap-2.5 rounded-2xl border px-4 py-3 text-center text-xs font-medium"
                        style="
                            background: rgba(239, 68, 68, 0.08);
                            border-color: rgba(239, 68, 68, 0.2);
                            color: var(--gl-danger);
                        "
                    >
                        <Lock class="h-4 w-4 shrink-0" :stroke-width="2" />
                        <span>{{ activeConversation.disabled_reason || 'Messaging is currently disabled in this conversation.' }}</span>
                    </div>
                    <div v-else class="flex flex-col gap-2">
                        <!-- Attachment preview chip -->
                        <div
                            v-if="selectedFile"
                            class="flex items-center justify-between gap-2 self-start rounded-xl border px-3 py-1.5 text-xs font-medium"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                                color: var(--gl-text-primary);
                            "
                        >
                            <div class="flex items-center gap-2 max-w-[260px] truncate">
                                <img
                                    v-if="selectedFilePreview"
                                    :src="selectedFilePreview"
                                    class="h-6 w-6 rounded object-cover"
                                    alt="Attachment preview"
                                />
                                <Paperclip v-else class="h-4 w-4 shrink-0 text-blue-500" />
                                <span class="truncate">{{ selectedFile.name }}</span>
                                <span class="text-[10px] opacity-70">({{ formatBytes(selectedFile.size) }})</span>
                            </div>
                            <button
                                type="button"
                                @click="clearSelectedFile"
                                class="rounded-full p-0.5 text-gray-400 hover:text-red-500 transition-colors"
                                title="Remove attachment"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <!-- Voice Recording Toolbar -->
                        <div
                            v-if="isRecording"
                            class="flex items-center justify-between gap-3 rounded-2xl border px-3 py-2"
                            style="
                                background: var(--gl-surface-2);
                                border-color: rgba(239, 68, 68, 0.4);
                            "
                        >
                            <div class="flex items-center gap-2.5">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                                </span>
                                <span class="text-xs font-semibold text-red-500 tracking-wide uppercase">
                                    Recording
                                </span>
                                <span class="text-xs font-mono font-bold text-[var(--gl-text-primary)]">
                                    {{ formatRecordingTime(recordingSeconds) }}
                                </span>
                            </div>

                            <!-- Animated Audio Waveform Mock Bars -->
                            <div class="flex items-center gap-0.5 h-4">
                                <span class="w-1 bg-red-500 rounded-full animate-pulse h-2"></span>
                                <span class="w-1 bg-red-500 rounded-full animate-pulse h-4 delay-75"></span>
                                <span class="w-1 bg-red-500 rounded-full animate-pulse h-3 delay-150"></span>
                                <span class="w-1 bg-red-500 rounded-full animate-pulse h-4 delay-100"></span>
                                <span class="w-1 bg-red-500 rounded-full animate-pulse h-2 delay-200"></span>
                            </div>

                            <div class="flex items-center gap-1.5">
                                <button
                                    type="button"
                                    @click="cancelRecording"
                                    class="flex h-8 w-8 items-center justify-center rounded-xl text-gray-400 hover:text-red-500 hover:bg-red-500/10 transition-colors"
                                    title="Cancel recording"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="stopAndSendRecording"
                                    class="flex h-8 items-center gap-1.5 px-3 rounded-full text-xs font-semibold text-white bg-red-500 hover:bg-red-600 transition-colors shadow-sm"
                                    title="Send voice message"
                                >
                                    <Send class="h-3.5 w-3.5" />
                                    <span>Send</span>
                                </button>
                            </div>
                        </div>

                        <!-- Standard Text / File / Mic Input Form -->
                        <form
                            v-else
                            @submit.prevent="sendMessage"
                            class="flex items-center gap-2 rounded-2xl border px-2 py-2 pl-3"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                            "
                        >
                            <input
                                ref="fileInputRef"
                                type="file"
                                class="hidden"
                                accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.zip"
                                @change="onFileSelected"
                            />
                            <button
                                type="button"
                                @click="fileInputRef?.click()"
                                :disabled="sending"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-all duration-200 hover:scale-105 hover:bg-black/5 dark:hover:bg-white/5 disabled:opacity-40"
                                style="color: var(--gl-text-secondary)"
                                title="Attach file or image"
                            >
                                <Paperclip class="h-4.5 w-4.5" :stroke-width="2" />
                            </button>

                            <button
                                type="button"
                                @click="startRecording"
                                :disabled="sending"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl transition-all duration-200 hover:scale-105 hover:bg-black/5 dark:hover:bg-white/5 disabled:opacity-40 text-blue-500 hover:text-blue-600"
                                title="Record voice note"
                            >
                                <Mic class="h-4.5 w-4.5" :stroke-width="2" />
                            </button>

                            <textarea
                                ref="sendInputRef"
                                v-model="newMessage"
                                rows="1"
                                placeholder="Type something..."
                                class="max-h-[120px] flex-1 resize-none bg-transparent text-sm outline-none"
                                style="color: var(--gl-text-primary)"
                                :disabled="sending"
                                @input="onUserTyping"
                                @keydown.enter.exact.prevent="sendMessage"
                                @keydown.enter.shift.prevent="newMessage += '\n'"
                            ></textarea>
                            <button
                                type="submit"
                                :disabled="(!newMessage.trim() && !selectedFile) || sending"
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-white transition-all duration-200 hover:scale-105 disabled:opacity-40 disabled:hover:scale-100"
                                style="
                                    background: linear-gradient(
                                        135deg,
                                        #3b82f6,
                                        #2563eb
                                    );
                                    box-shadow: 0 0 12px rgba(59, 130, 246, 0.45);
                                "
                                title="Send"
                            >
                                <span
                                    v-if="sending"
                                    class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                                ></span>
                                <Plus
                                    v-else
                                    class="h-4.5 w-4.5"
                                    :stroke-width="2.5"
                                />
                            </button>
                        </form>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <div
                v-else
                class="flex flex-1 items-center justify-center"
                style="min-height: 0"
            >
                <div class="px-8 text-center">
                    <div
                        class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-3xl"
                        style="
                            background: linear-gradient(
                                135deg,
                                rgba(59, 130, 246, 0.12),
                                rgba(124, 58, 237, 0.08)
                            );
                        "
                    >
                        <MessageCircle
                            class="h-10 w-10"
                            :stroke-width="1.5"
                            style="color: var(--gl-primary)"
                        />
                    </div>
                    <p
                        class="text-lg font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        Your Messages
                    </p>
                    <p
                        class="mt-2 text-sm"
                        style="color: var(--gl-text-secondary)"
                    >
                        Select a conversation to start chatting.
                    </p>
                    <p class="mt-1 text-xs" style="color: var(--gl-text-muted)">
                        Stay connected with your classmates and teachers.
                    </p>
                </div>
            </div>
        </div>

        <!-- Backdrop for profile drawer on small screens -->
        <div
            v-if="showProfile && activeConversation"
            class="absolute inset-0 z-10 xl:hidden"
            style="background: rgba(0, 0, 0, 0.45)"
            @click="showProfile = false"
        ></div>

        <!-- Contact / Group Profile Panel -->
        <div
            v-if="showProfile && activeConversation"
            class="absolute inset-y-0 right-0 z-20 flex w-72 shrink-0 flex-col overflow-y-auto xl:static xl:z-auto"
            style="
                background: var(--gl-surface);
                border-left: 1px solid var(--gl-border);
            "
        >
            <div class="flex justify-end p-3">
                <button
                    @click="showProfile = false"
                    class="flex h-7 w-7 items-center justify-center rounded-lg transition-colors hover:bg-[var(--gl-surface-2)]"
                    style="color: var(--gl-text-muted)"
                    title="Close panel"
                >
                    <X class="h-4 w-4" :stroke-width="2" />
                </button>
            </div>

            <!-- Group Profile -->
            <template v-if="activeConversation.is_group">
                <div class="flex flex-col items-center px-5 text-center">
                    <div
                        class="flex h-20 w-20 items-center justify-center rounded-full text-xl font-bold text-white"
                        style="
                            background: linear-gradient(135deg, #8b5cf6, #6366f1);
                            box-shadow: 0 0 0 3px var(--gl-surface), 0 0 0 5px rgba(139, 92, 246, 0.4);
                        "
                    >
                        <Users class="h-10 w-10 text-white" :stroke-width="1.75" />
                    </div>
                    <p
                        class="mt-3 text-base font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        {{ activeConversation.title || activeConversation.subject || 'Group Chat' }}
                    </p>
                    <span
                        class="mt-1.5 inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold"
                        style="
                            background: rgba(139, 92, 246, 0.12);
                            color: #8b5cf6;
                        "
                    >
                        <Users class="h-3 w-3" :stroke-width="2" />
                        {{ activeConversation.participant_count ?? activeConversation.participants?.length ?? 0 }} members
                    </span>
                </div>

                <div class="mt-5 flex items-center justify-center gap-5 px-5">
                    <button
                        @click="focusSend"
                        class="flex flex-col items-center gap-1.5"
                        title="Send a message"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-full"
                            style="background: var(--gl-surface-2)"
                        >
                            <MessageCircle
                                class="h-4.5 w-4.5"
                                style="color: var(--gl-text-secondary)"
                                :stroke-width="2"
                            />
                        </span>
                        <span
                            class="text-[10px] font-semibold"
                            style="color: var(--gl-text-secondary)"
                            >Messages</span
                        >
                        <span
                            class="rounded-full px-1.5 py-px text-[9px] font-bold tabular-nums"
                            style="
                                background: rgba(59, 130, 246, 0.12);
                                color: var(--gl-primary);
                            "
                            >{{ msgStats.total }}</span
                        >
                    </button>
                    <div class="flex flex-col items-center gap-1.5">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-full"
                            style="background: var(--gl-surface-2)"
                        >
                            <Send
                                class="h-4.5 w-4.5"
                                style="color: var(--gl-text-secondary)"
                                :stroke-width="2"
                            />
                        </span>
                        <span
                            class="text-[10px] font-semibold"
                            style="color: var(--gl-text-secondary)"
                            >Sent</span
                        >
                        <span
                            class="rounded-full px-1.5 py-px text-[9px] font-bold tabular-nums"
                            style="
                                background: rgba(16, 185, 129, 0.12);
                                color: var(--gl-success);
                            "
                            >{{ msgStats.sent }}</span
                        >
                    </div>
                </div>

                <!-- Group Members List -->
                <div class="mt-5 px-5">
                    <p
                        class="mb-2 text-[11px] font-bold tracking-wide uppercase"
                        style="color: var(--gl-text-primary)"
                    >
                        Members ({{ activeConversation.participants?.length ?? 0 }})
                    </p>
                    <div class="messenger-scroll max-h-56 space-y-1.5 overflow-y-auto pr-1">
                        <div
                            v-for="p in activeConversation.participants"
                            :key="p.id"
                            class="flex items-center justify-between rounded-xl px-2.5 py-2"
                            style="background: var(--gl-surface-2)"
                        >
                            <div class="flex min-w-0 items-center gap-2">
                                <div
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                    :style="{
                                        background:
                                            'linear-gradient(135deg, ' +
                                            avatarBg(p.name) +
                                            ', ' +
                                            avatarBg2(p.name) +
                                            ')',
                                    }"
                                >
                                    <img
                                        v-if="p.avatar"
                                        :src="p.avatar"
                                        alt=""
                                        class="h-full w-full rounded-full object-cover"
                                    />
                                    <template v-else>{{ initials(p.name) }}</template>
                                </div>
                                <span
                                    class="truncate text-xs font-medium"
                                    style="color: var(--gl-text-primary)"
                                >
                                    {{ p.name }}
                                    <span
                                        v-if="p.id === userId"
                                        class="text-[10px] font-bold"
                                        style="color: var(--gl-primary)"
                                    >(You)</span>
                                </span>
                            </div>
                            <span
                                class="shrink-0 rounded px-1.5 py-0.5 text-[8px] font-extrabold tracking-wider"
                                :style="
                                    p.role === 'teacher' || p.role === 'superadmin'
                                        ? 'background: rgba(59, 130, 246, 0.14); color: var(--gl-primary);'
                                        : 'background: rgba(251, 191, 36, 0.14); color: var(--gl-accent);'
                                "
                            >
                                {{ roleLabel(p.role) }}
                            </span>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Direct Contact Profile -->
            <template v-else>
                <div class="flex flex-col items-center px-5 text-center">
                    <div
                        class="flex h-20 w-20 items-center justify-center rounded-full text-xl font-bold text-white"
                        :style="{
                            background:
                                'linear-gradient(135deg, ' +
                                avatarBg(activeConversation.other?.name ?? '') +
                                ', ' +
                                avatarBg2(activeConversation.other?.name ?? '') +
                                ')',
                            boxShadow:
                                '0 0 0 3px var(--gl-surface), 0 0 0 5px var(--gl-primary-glow)',
                        }"
                    >
                        <img
                            v-if="activeConversation.other?.avatar"
                            :src="activeConversation.other.avatar"
                            alt=""
                            class="h-full w-full rounded-full object-cover"
                        />
                        <template v-else>{{ initials(activeConversation.other?.name ?? '?') }}</template>
                    </div>
                    <p
                        class="mt-3 flex items-center gap-1.5 text-sm font-bold"
                        style="color: var(--gl-text-primary)"
                    >
                        @{{ handle(activeConversation.other?.name ?? '') }}
                        <span
                            class="flex h-4 w-4 items-center justify-center rounded-full text-[9px] text-white"
                            style="background: var(--gl-primary)"
                            title="Verified contact"
                            >✓</span
                        >
                    </p>
                    <p class="mt-0.5 text-xs" style="color: var(--gl-text-muted)">
                        {{ activeConversation.other?.name }}
                    </p>
                    <p
                        v-if="activeConversation.other?.bio"
                        class="mt-2 max-w-full text-xs leading-relaxed break-words whitespace-pre-line"
                        style="color: var(--gl-text-secondary)"
                    >
                        {{ activeConversation.other.bio }}
                    </p>
                    <span
                        class="mt-2 rounded px-2 py-0.5 text-[9px] font-extrabold tracking-widest"
                        style="
                            background: rgba(251, 191, 36, 0.14);
                            color: var(--gl-accent);
                        "
                    >
                        {{ roleLabel(activeConversation.other?.role) }}
                    </span>
                </div>

                <div class="mt-5 flex items-center justify-center gap-5 px-5">
                    <button
                        @click="focusSend"
                        class="flex flex-col items-center gap-1.5"
                        title="Send a message"
                    >
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-full"
                            style="background: var(--gl-surface-2)"
                        >
                            <MessageCircle
                                class="h-4.5 w-4.5"
                                style="color: var(--gl-text-secondary)"
                                :stroke-width="2"
                            />
                        </span>
                        <span
                            class="text-[10px] font-semibold"
                            style="color: var(--gl-text-secondary)"
                            >Message</span
                        >
                        <span
                            class="rounded-full px-1.5 py-px text-[9px] font-bold tabular-nums"
                            style="
                                background: rgba(59, 130, 246, 0.12);
                                color: var(--gl-primary);
                            "
                            >{{ msgStats.total }}</span
                        >
                    </button>
                    <div class="flex flex-col items-center gap-1.5">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-full"
                            style="background: var(--gl-surface-2)"
                        >
                            <Send
                                class="h-4.5 w-4.5"
                                style="color: var(--gl-text-secondary)"
                                :stroke-width="2"
                            />
                        </span>
                        <span
                            class="text-[10px] font-semibold"
                            style="color: var(--gl-text-secondary)"
                            >Sent</span
                        >
                        <span
                            class="rounded-full px-1.5 py-px text-[9px] font-bold tabular-nums"
                            style="
                                background: rgba(16, 185, 129, 0.12);
                                color: var(--gl-success);
                            "
                            >{{ msgStats.sent }}</span
                        >
                    </div>
                    <div class="flex flex-col items-center gap-1.5">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-full"
                            style="background: var(--gl-surface-2)"
                        >
                            <Inbox
                                class="h-4.5 w-4.5"
                                style="color: var(--gl-text-secondary)"
                                :stroke-width="2"
                            />
                        </span>
                        <span
                            class="text-[10px] font-semibold"
                            style="color: var(--gl-text-secondary)"
                            >Received</span
                        >
                        <span
                            class="rounded-full px-1.5 py-px text-[9px] font-bold tabular-nums"
                            style="
                                background: rgba(251, 191, 36, 0.14);
                                color: var(--gl-accent);
                            "
                            >{{ msgStats.received }}</span
                        >
                    </div>
                </div>
            </template>

            <div class="mt-5 px-5">
                <p
                    class="mb-1.5 text-[11px] font-bold"
                    style="color: var(--gl-text-primary)"
                >
                    About
                </p>
                <p
                    class="text-xs leading-relaxed"
                    style="color: var(--gl-text-secondary)"
                >
                    {{
                        activeConversation.is_group
                            ? (activeConversation.subject || 'Group conversation with ' + (activeConversation.participant_count ?? activeConversation.participants?.length ?? 0) + ' members.')
                            : (activeConversation.subject ||
                                'Direct conversation with ' +
                                    (activeConversation.other?.name ?? 'this contact') +
                                    '.')
                    }}
                </p>
            </div>

            <div class="mt-auto p-5">
                <button
                    @click="
                        showProfile = false;
                        deleteConversation();
                    "
                    class="flex w-full items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-xs font-semibold transition-all hover:scale-[1.02]"
                    style="
                        background: var(--gl-danger-bg);
                        color: var(--gl-danger);
                    "
                >
                    <Trash2 class="h-3.5 w-3.5" :stroke-width="2" /> Delete
                    conversation
                </button>
            </div>
        </div>
    </div>

    <!-- New Conversation / Group Modal -->
    <Teleport to="body">
        <div
            v-if="showNewModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            style="background: rgba(0, 0, 0, 0.4); backdrop-filter: blur(4px)"
            @click.self="showNewModal = false"
        >
            <div
                class="w-full max-w-md rounded-2xl p-6"
                style="
                    background: var(--gl-surface);
                    border: 1px solid var(--gl-border);
                    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.45);
                "
            >
                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                            "
                        >
                            <MessageCircle
                                v-if="modalTab === 'direct'"
                                class="h-4.5 w-4.5 text-white"
                                :stroke-width="2"
                            />
                            <Users
                                v-else
                                class="h-4.5 w-4.5 text-white"
                                :stroke-width="2"
                            />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold"
                                style="color: var(--gl-text-primary)"
                            >
                                {{ modalTab === 'direct' ? 'New Message' : 'New Group Chat' }}
                            </h3>
                            <p class="text-xs" style="color: var(--gl-text-muted)">
                                {{ modalTab === 'direct' ? 'Start a direct chat with someone' : 'Create a group conversation' }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showNewModal = false"
                        class="flex h-7 w-7 items-center justify-center rounded-lg transition-colors hover:bg-[var(--gl-surface-2)]"
                        style="color: var(--gl-text-muted)"
                    >
                        <X class="h-4 w-4" :stroke-width="2" />
                    </button>
                </div>

                <!-- Tabs -->
                <div
                    class="mb-4 flex rounded-xl p-1"
                    style="background: var(--gl-surface-2); border: 1px solid var(--gl-border)"
                >
                    <button
                        type="button"
                        @click="modalTab = 'direct'"
                        class="flex-1 rounded-lg py-1.5 text-xs font-semibold transition-all"
                        :style="
                            modalTab === 'direct'
                                ? 'background: var(--gl-surface); color: var(--gl-text-primary); box-shadow: 0 2px 8px rgba(0,0,0,0.15);'
                                : 'color: var(--gl-text-muted);'
                        "
                    >
                        Direct Message
                    </button>
                    <button
                        type="button"
                        @click="modalTab = 'group'"
                        class="flex-1 rounded-lg py-1.5 text-xs font-semibold transition-all"
                        :style="
                            modalTab === 'group'
                                ? 'background: var(--gl-surface); color: var(--gl-text-primary); box-shadow: 0 2px 8px rgba(0,0,0,0.15);'
                                : 'color: var(--gl-text-muted);'
                        "
                    >
                        Group Chat
                    </button>
                </div>

                <!-- Direct Message Form -->
                <form
                    v-if="modalTab === 'direct'"
                    @submit.prevent="startConversation"
                    class="space-y-4"
                >
                    <div
                        v-if="isStudent && !canMessageClassmates"
                        class="flex items-start gap-2 rounded-xl p-2.5 text-xs"
                        style="
                            background: rgba(239, 68, 68, 0.08);
                            border: 1px solid rgba(239, 68, 68, 0.2);
                            color: var(--gl-danger);
                        "
                    >
                        <Info class="mt-0.5 h-4 w-4 shrink-0" :stroke-width="2" />
                        <span>Classmate messaging is disabled. You can only message your teachers.</span>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Contact</label
                        >
                        <select
                            v-model="newRecipient"
                            required
                            class="w-full rounded-xl border px-4 py-2.5 text-sm transition-all outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        >
                            <option value="">Select a contact...</option>
                            <optgroup v-if="teachers.length" label="Teachers">
                                <option
                                    v-for="t in teachers"
                                    :key="t.id"
                                    :value="t.id"
                                >
                                    {{ t.name }}
                                </option>
                            </optgroup>
                            <optgroup
                                v-if="students.length && (!isStudent || canMessageClassmates)"
                                label="Students"
                            >
                                <option
                                    v-for="s in students"
                                    :key="s.id"
                                    :value="s.id"
                                >
                                    {{ s.name }}
                                </option>
                            </optgroup>
                        </select>
                    </div>
                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Message</label
                        >
                        <textarea
                            v-model="newMessageBody"
                            rows="3"
                            placeholder="Write your message..."
                            class="w-full resize-none rounded-xl border px-4 py-2.5 text-sm transition-all outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                            required
                        ></textarea>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="showNewModal = false"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium transition-all"
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
                            :disabled="!newRecipient || !newMessageBody.trim()"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-40 disabled:hover:scale-100"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 0 12px var(--gl-primary-glow);
                            "
                        >
                            Send
                        </button>
                    </div>
                </form>

                <!-- Group Chat Form -->
                <form
                    v-else
                    @submit.prevent="startGroupChat"
                    class="space-y-4"
                >
                    <div
                        v-if="isStudent && !canMessageClassmates"
                        class="flex items-start gap-2 rounded-xl p-2.5 text-xs"
                        style="
                            background: rgba(239, 68, 68, 0.08);
                            border: 1px solid rgba(239, 68, 68, 0.2);
                            color: var(--gl-danger);
                        "
                    >
                        <Info class="mt-0.5 h-4 w-4 shrink-0" :stroke-width="2" />
                        <span>Classmate messaging is disabled. You can only create groups with teachers.</span>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Group Name</label
                        >
                        <input
                            v-model="groupName"
                            type="text"
                            required
                            maxlength="100"
                            placeholder="e.g. Project Discussion"
                            class="w-full rounded-xl border px-4 py-2.5 text-sm transition-all outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        />
                    </div>

                    <div>
                        <div class="mb-1.5 flex items-center justify-between">
                            <label
                                class="block text-xs font-medium"
                                style="color: var(--gl-text-secondary)"
                            >
                                Members ({{ selectedGroupMembers.length }} selected)
                            </label>
                            <span
                                v-if="selectedGroupMembers.length > 0"
                                @click="selectedGroupMembers = []"
                                class="cursor-pointer text-[11px] font-semibold transition-opacity hover:opacity-80"
                                style="color: var(--gl-primary)"
                            >
                                Clear all
                            </span>
                        </div>

                        <!-- Search input for members -->
                        <div class="relative mb-2">
                            <Search
                                class="pointer-events-none absolute top-1/2 left-3 h-3.5 w-3.5 -translate-y-1/2"
                                :stroke-width="2"
                                style="color: var(--gl-text-muted)"
                            />
                            <input
                                v-model="groupMemberSearch"
                                type="text"
                                placeholder="Filter contacts..."
                                class="w-full rounded-xl border px-3.5 py-1.5 pl-8 text-xs transition-all outline-none"
                                style="
                                    background: var(--gl-surface-2);
                                    color: var(--gl-text-primary);
                                    border-color: var(--gl-border);
                                "
                            />
                        </div>

                        <!-- Member checklist scroll area -->
                        <div
                            class="messenger-scroll max-h-44 space-y-1 overflow-y-auto rounded-xl border p-1"
                            style="
                                background: var(--gl-surface-2);
                                border-color: var(--gl-border);
                            "
                        >
                            <div
                                v-for="c in availableGroupContacts"
                                :key="c.id"
                                @click="toggleMemberSelection(c.id)"
                                class="flex cursor-pointer items-center justify-between rounded-lg px-2.5 py-2 transition-colors hover:bg-[var(--gl-surface)]"
                                :style="
                                    selectedGroupMembers.includes(c.id)
                                        ? 'background: rgba(59, 130, 246, 0.1);'
                                        : ''
                                "
                            >
                                <div class="flex min-w-0 items-center gap-2.5">
                                    <div
                                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                                        :style="{
                                            background:
                                                'linear-gradient(135deg, ' +
                                                avatarBg(c.name) +
                                                ', ' +
                                                avatarBg2(c.name) +
                                                ')',
                                        }"
                                    >
                                        <img
                                            v-if="c.avatar"
                                            :src="c.avatar"
                                            alt=""
                                            class="h-full w-full rounded-full object-cover"
                                        />
                                        <template v-else>{{ initials(c.name) }}</template>
                                    </div>
                                    <div class="min-w-0 truncate">
                                        <p
                                            class="truncate text-xs font-semibold"
                                            style="color: var(--gl-text-primary)"
                                        >
                                            {{ c.name }}
                                        </p>
                                        <span
                                            class="text-[9px] font-extrabold tracking-widest uppercase"
                                            style="color: var(--gl-text-muted)"
                                        >
                                            {{ roleLabel(c.role) }}
                                        </span>
                                    </div>
                                </div>
                                <div
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded border transition-colors"
                                    :style="
                                        selectedGroupMembers.includes(c.id)
                                            ? 'background: var(--gl-primary); border-color: var(--gl-primary); color: #fff;'
                                            : 'border-color: var(--gl-border); background: transparent;'
                                    "
                                >
                                    <Check
                                        v-if="selectedGroupMembers.includes(c.id)"
                                        class="h-3 w-3"
                                        :stroke-width="3"
                                    />
                                </div>
                            </div>

                            <div
                                v-if="!availableGroupContacts.length"
                                class="py-6 text-center text-xs"
                                style="color: var(--gl-text-muted)"
                            >
                                No contacts available.
                            </div>
                        </div>
                    </div>

                    <div>
                        <label
                            class="mb-1.5 block text-xs font-medium"
                            style="color: var(--gl-text-secondary)"
                            >Initial Message <span class="font-normal opacity-70">(optional)</span></label
                        >
                        <textarea
                            v-model="groupInitialMessage"
                            rows="2"
                            placeholder="Introduce this group..."
                            class="w-full resize-none rounded-xl border px-4 py-2 text-sm transition-all outline-none"
                            style="
                                background: var(--gl-surface-2);
                                color: var(--gl-text-primary);
                                border-color: var(--gl-border);
                            "
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button
                            type="button"
                            @click="showNewModal = false"
                            class="rounded-xl px-4 py-2.5 text-sm font-medium transition-all"
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
                            :disabled="!groupName.trim() || selectedGroupMembers.length === 0 || creatingGroup"
                            class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02] disabled:opacity-40 disabled:hover:scale-100"
                            style="
                                background: linear-gradient(
                                    135deg,
                                    var(--gl-primary),
                                    var(--gl-secondary)
                                );
                                box-shadow: 0 0 12px var(--gl-primary-glow);
                            "
                        >
                            <span v-if="creatingGroup">Creating...</span>
                            <span v-else>Create Group</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Delete Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="showDeleteModal = false"
        >
            <div
                class="mx-4 w-full max-w-sm rounded-2xl p-6"
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
                    Delete conversation?
                </h3>
                <p
                    class="text-center text-sm"
                    style="color: var(--gl-text-secondary)"
                >
                    This will hide the conversation from your inbox. The other
                    participant can still see it.
                </p>
                <div class="mt-5 flex items-center justify-center gap-3">
                    <button
                        @click="showDeleteModal = false"
                        class="rounded-xl px-4 py-2.5 text-sm font-medium transition-all"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmDelete"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
                        style="background: var(--gl-danger)"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <!-- Remove Message Modal -->
        <div
            v-if="deleteTarget"
            class="fixed inset-0 z-50 flex items-center justify-center"
            style="background: rgba(0, 0, 0, 0.25); backdrop-filter: blur(2px)"
            @click.self="deleteTarget = null"
        >
            <div
                class="mx-4 w-full max-w-sm rounded-2xl p-6"
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
                    Remove message?
                </h3>
                <p
                    class="text-center text-sm"
                    style="color: var(--gl-text-secondary)"
                >
                    This message will be replaced with "removed" for both of
                    you.
                </p>
                <div class="mt-5 flex items-center justify-center gap-3">
                    <button
                        @click="deleteTarget = null"
                        class="rounded-xl px-4 py-2.5 text-sm font-medium transition-all"
                        style="
                            background: var(--gl-surface-2);
                            color: var(--gl-text-primary);
                            border: 1px solid var(--gl-border);
                        "
                    >
                        Cancel
                    </button>
                    <button
                        @click="confirmDeleteMessage"
                        class="rounded-xl px-4 py-2.5 text-sm font-semibold text-white transition-all hover:scale-[1.02]"
                        style="background: var(--gl-danger)"
                    >
                        Remove
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import {
    Search,
    Plus,
    Send,
    MessageCircle,
    Trash2,
    Pencil,
    Check,
    X,
    Inbox,
    ChevronLeft,
    Users,
    Lock,
    Info,
    Paperclip,
    FileText,
    Download,
    Mic,
} from '@lucide/vue';
import { computed, nextTick, ref, watch, onMounted, onUnmounted } from 'vue';
import VoicePlayer from '@/components/VoicePlayer.vue';
import { getEcho, isWsConnected, disconnectEcho } from '@/lib/echo';

const page = usePage();
const messengerEnabled = ref(true);
onMounted(async () => {
    try {
        const r = await fetch('/api/messenger-status');

        if (r.ok) {
            const d = await r.json();
            messengerEnabled.value = d.enabled;
        }
    } catch {}

    pollInterval = window.setInterval(() => {
        if (!isWsConnected.value) {
            pollMessages();
        }
    }, 3000);

    // Always refresh conversations/contacts (even when the WebSocket is
    // connected) so profile changes (name, photo, bio) reach other users.
    convPollInterval = window.setInterval(() => {
        if (!document.hidden) {
            pollConversations();
        }
    }, 5000);

    typingCleanupInterval = window.setInterval(() => {
        const now = Date.now();
        typingUsers.value = typingUsers.value.filter((u) => u.expiresAt > now);
    }, 1000);

    setupWebSockets();
});

const props = defineProps<{
    conversations: any[];
    contacts: any[];
    conversation?: any;
    can_message_classmates?: boolean;
    allow_classmate_messaging?: boolean;
    is_teacher?: boolean;
    is_superadmin?: boolean;
}>();

const isTeacher = computed(() => {
    if (props.is_teacher !== undefined) {
return props.is_teacher;
}

    return (page.props.auth as any).user?.role === 'teacher';
});

const isSuperadmin = computed(() => {
    if (props.is_superadmin !== undefined) {
return props.is_superadmin;
}

    return (
        (page.props.auth as any).user?.is_superadmin ||
        (page.props.auth as any).user?.role === 'superadmin'
    );
});

const canMessageClassmates = computed(() => {
    if (props.can_message_classmates !== undefined) {
return props.can_message_classmates;
}

    return (page.props.auth as any).user?.can_message_classmates ?? true;
});

const teacherClassmatesAllowed = computed(() => {
    if (props.allow_classmate_messaging !== undefined) {
return props.allow_classmate_messaging;
}

    return (page.props.auth as any).user?.allow_classmate_messaging !== false;
});

function toggleClassmateMessaging() {
    if (isSuperadmin.value) {
        router.patch(
            '/admin/users/toggle-classmate-messaging',
            {},
            { preserveScroll: true },
        );
    } else if (isTeacher.value) {
        router.patch(
            '/teacher/profile/toggle-classmate-messaging',
            {},
            { preserveScroll: true },
        );
    }
}

const modalTab = ref<'direct' | 'group'>('direct');
const groupName = ref('');
const selectedGroupMembers = ref<number[]>([]);
const groupInitialMessage = ref('');
const groupMemberSearch = ref('');
const creatingGroup = ref(false);

const availableGroupContacts = computed(() => {
    let list = localContacts.value;

    if (isStudent && !canMessageClassmates.value) {
        list = list.filter(
            (c: any) => c.role === 'teacher' || c.role === 'superadmin',
        );
    }

    if (!groupMemberSearch.value) {
return list;
}

    const q = groupMemberSearch.value.toLowerCase();

    return list.filter((c: any) => c.name.toLowerCase().includes(q));
});

function toggleMemberSelection(id: number) {
    const idx = selectedGroupMembers.value.indexOf(id);

    if (idx >= 0) {
        selectedGroupMembers.value.splice(idx, 1);
    } else {
        selectedGroupMembers.value.push(id);
    }
}

function startGroupChat() {
    if (
        !groupName.value.trim() ||
        selectedGroupMembers.value.length === 0 ||
        creatingGroup.value
    ) {
        return;
    }

    creatingGroup.value = true;
    router.post(
        `${prefix}/messenger/group`,
        {
            name: groupName.value.trim(),
            participant_ids: selectedGroupMembers.value,
            message: groupInitialMessage.value.trim() || undefined,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showNewModal.value = false;
                groupName.value = '';
                selectedGroupMembers.value = [];
                groupInitialMessage.value = '';
                groupMemberSearch.value = '';
                creatingGroup.value = false;
            },
            onError: () => {
                creatingGroup.value = false;
            },
            onFinish: () => {
                creatingGroup.value = false;
            },
        },
    );
}

const userId = (page.props.auth as any).user.id;
const isStudent = (page.props.auth as any).user.role === 'student';
const prefix = isStudent ? '/student' : '/teacher';
const search = ref('');
const newMessage = ref('');
const sending = ref(false);
const selectedFile = ref<File | null>(null);
const selectedFilePreview = ref<string | null>(null);
const fileInputRef = ref<HTMLInputElement | null>(null);

function onFileSelected(event: Event) {
    const input = event.target as HTMLInputElement;

    if (input.files && input.files[0]) {
        const file = input.files[0];

        if (file.size > 10 * 1024 * 1024) {
            alert('File exceeds the 10MB limit.');
            input.value = '';

            return;
        }

        selectedFile.value = file;

        if (file.type.startsWith('image/')) {
            selectedFilePreview.value = URL.createObjectURL(file);
        } else {
            selectedFilePreview.value = null;
        }
    }
}

function clearSelectedFile() {
    selectedFile.value = null;

    if (selectedFilePreview.value) {
        URL.revokeObjectURL(selectedFilePreview.value);
        selectedFilePreview.value = null;
    }

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function isAudioAttachment(m: any): boolean {
    if (!m?.attachment_url) {
return false;
}

    if (m.attachment_type?.startsWith('audio/')) {
return true;
}

    const name = m.attachment_name || '';

    return /\.(webm|mp3|ogg|wav|m4a|aac)$/i.test(name);
}

// Voice Note Recording
const isRecording = ref(false);
const recordingSeconds = ref(0);
let mediaRecorder: MediaRecorder | null = null;
let audioChunks: Blob[] = [];
let recordingTimer: number | null = null;
let mediaStream: MediaStream | null = null;

async function startRecording() {
    try {
        if (!navigator.mediaDevices?.getUserMedia) {
            alert('Audio recording is not supported in this browser.');

            return;
        }

        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaStream = stream;

        const mimeType = (window as any).MediaRecorder?.isTypeSupported?.('audio/webm;codecs=opus')
            ? 'audio/webm;codecs=opus'
            : (window as any).MediaRecorder?.isTypeSupported?.('audio/webm')
            ? 'audio/webm'
            : (window as any).MediaRecorder?.isTypeSupported?.('audio/mp4')
            ? 'audio/mp4'
            : 'audio/ogg';

        mediaRecorder = new MediaRecorder(stream, { mimeType });
        audioChunks = [];

        mediaRecorder.ondataavailable = (e) => {
            if (e.data && e.data.size > 0) {
                audioChunks.push(e.data);
            }
        };

        mediaRecorder.start(250);
        isRecording.value = true;
        recordingSeconds.value = 0;

        recordingTimer = window.setInterval(() => {
            recordingSeconds.value++;

            if (recordingSeconds.value >= 300) {
                stopAndSendRecording();
            }
        }, 1000);
    } catch (err) {
        console.error('Microphone access error:', err);
        alert('Could not access microphone. Please allow microphone permissions in your browser.');
        cleanupRecording();
    }
}

function cancelRecording() {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        try {
            mediaRecorder.stop();
        } catch {}
    }

    cleanupRecording();
}

function cleanupRecording() {
    isRecording.value = false;
    recordingSeconds.value = 0;

    if (recordingTimer) {
        clearInterval(recordingTimer);
        recordingTimer = null;
    }

    if (mediaStream) {
        mediaStream.getTracks().forEach((t) => t.stop());
        mediaStream = null;
    }

    audioChunks = [];
    mediaRecorder = null;
}

async function stopAndSendRecording() {
    if (!mediaRecorder || mediaRecorder.state === 'inactive') {
        cleanupRecording();

        return;
    }

    mediaRecorder.onstop = async () => {
        const mime = mediaRecorder?.mimeType || 'audio/webm';
        const extension = mime.includes('mp4') ? 'mp4' : mime.includes('ogg') ? 'ogg' : 'webm';
        const blob = new Blob(audioChunks, { type: mime });
        const filename = `voice-note-${new Date().toISOString().replace(/[:.]/g, '-')}.${extension}`;
        const audioFile = new File([blob], filename, { type: mime });

        cleanupRecording();

        selectedFile.value = audioFile;
        selectedFilePreview.value = URL.createObjectURL(blob);
        await sendMessage();
    };

    try {
        mediaRecorder.stop();
    } catch {
        cleanupRecording();
    }
}

function formatRecordingTime(sec: number): string {
    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);

    return `${m}:${s.toString().padStart(2, '0')}`;
}

function formatBytes(bytes?: number): string {
    if (!bytes || bytes === 0) {
return '0 B';
}

    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));

    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// ---- Real-Time WebSockets & Presence State ----
const onlineUsers = ref<Array<{ id: number; name: string; role: string }>>([]);
const typingUsers = ref<Array<{ id: number; name: string; expiresAt: number }>>([]);
let activePresenceChannel: any = null;
let activePresenceChannelId: number | null = null;
let userChannel: any = null;
let typingCleanupInterval: number | null = null;
let typingTimeout: any = null;
let lastTypingEmit = 0;

function isUserOnline(contactId: number | undefined | null): boolean {
    if (!contactId) {
return false;
}

    return onlineUsers.value.some((u) => Number(u.id) === Number(contactId));
}

const isOtherOnline = computed(() => {
    if (!activeConversation.value || activeConversation.value.is_group) {
return false;
}

    const otherId = activeConversation.value.other?.id;

    return isUserOnline(otherId);
});

const onlineMemberCount = computed(() => {
    if (!activeConversation.value?.is_group) {
return 0;
}

    return onlineUsers.value.length;
});

const typingIndicatorText = computed(() => {
    if (typingUsers.value.length === 0) {
return '';
}

    if (typingUsers.value.length === 1) {
        return `${typingUsers.value[0].name} is typing...`;
    }

    if (typingUsers.value.length === 2) {
        return `${typingUsers.value[0].name} and ${typingUsers.value[1].name} are typing...`;
    }

    return `${typingUsers.value.length} people are typing...`;
});

function handleIncomingTyping(e: { user_id: number; name: string; is_typing: boolean }) {
    if (Number(e.user_id) === Number(userId)) {
return;
}

    if (e.is_typing) {
        const existing = typingUsers.value.find((u) => Number(u.id) === Number(e.user_id));
        const expiresAt = Date.now() + 3500;

        if (existing) {
            existing.expiresAt = expiresAt;
        } else {
            typingUsers.value.push({ id: Number(e.user_id), name: e.name, expiresAt });
        }
    } else {
        typingUsers.value = typingUsers.value.filter((u) => Number(u.id) !== Number(e.user_id));
    }
}

function onUserTyping() {
    if (!activePresenceChannel) {
return;
}

    const now = Date.now();

    if (now - lastTypingEmit > 1500) {
        lastTypingEmit = now;

        try {
            activePresenceChannel.whisper('typing', {
                user_id: userId,
                name: (page.props.auth as any).user.name,
                is_typing: true,
            });
        } catch {}
    }

    if (typingTimeout) {
clearTimeout(typingTimeout);
}

    typingTimeout = setTimeout(() => {
        try {
            activePresenceChannel?.whisper('typing', {
                user_id: userId,
                name: (page.props.auth as any).user.name,
                is_typing: false,
            });
        } catch {}
    }, 2500);
}

function handleIncomingMessage(msg: any, convId: number) {
    if (activeId.value === convId) {
        const exists = messages.value.some((m: any) => m.id === msg.id);

        if (!exists) {
            messages.value = [...messages.value, msg];
            scrollToBottom(true);
            markConversationAsRead(convId);
        }
    }

    const conv = localConversations.value.find((c: any) => c.id === convId);

    if (conv) {
        conv.last_message = msg.body || (msg.attachment_name ? '📎 ' + msg.attachment_name : '');
        conv.last_message_at = msg.created_at;
        conv.last_mine = Number(msg.sender_id) === Number(userId);

        if (activeId.value !== convId) {
            conv.unread = true;
            conv.unread_count = (conv.unread_count || 0) + 1;
        } else {
            conv.unread = false;
            conv.unread_count = 0;
        }

        localConversations.value = sortConversations(localConversations.value);
    } else {
        pollConversations();
    }
}

function handleMessageUpdated(updatedMsg: any) {
    const idx = messages.value.findIndex((m: any) => m.id === updatedMsg.id);

    if (idx !== -1) {
        messages.value[idx] = { ...messages.value[idx], ...updatedMsg };
    }
}

function handleMessageDeleted(msgId: number) {
    const idx = messages.value.findIndex((m: any) => m.id === msgId);

    if (idx !== -1) {
        messages.value[idx] = {
            ...messages.value[idx],
            deleted_at: new Date().toISOString(),
            body: '',
            attachment_name: null,
            attachment_url: null,
        };
    }
}

function handleConversationRead(data: { user_id: number; read_at: string }) {
    if (Number(data.user_id) !== Number(userId)) {
        otherLastReadAt.value = data.read_at;
    }
}

async function markConversationAsRead(convId: number) {
    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';
        await fetch(`${prefix}/messenger/${convId}/read`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': token,
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
    } catch {}
}

function setupWebSockets() {
    const echo = getEcho((page.props as any).broadcasting?.reverb);

    if (!echo || !userId) {
return;
}

    if (!userChannel) {
        userChannel = echo.private(`chat.user.${userId}`);
        userChannel.listen('.message.sent', (e: any) => {
            handleIncomingMessage(e.message, e.conversation_id);
        });
    }

    joinActiveConversationChannel();
}

function joinActiveConversationChannel() {
    const echo = getEcho();

    if (!echo) {
return;
}

    if (activePresenceChannel && activePresenceChannelId) {
        echo.leave(`chat.conversation.${activePresenceChannelId}`);
        activePresenceChannel = null;
        activePresenceChannelId = null;
        onlineUsers.value = [];
        typingUsers.value = [];
    }

    if (!activeId.value) {
return;
}

    const convId = activeId.value;
    activePresenceChannelId = convId;

    activePresenceChannel = echo.join(`chat.conversation.${convId}`)
        .here((users: any[]) => {
            onlineUsers.value = users || [];
        })
        .joining((user: any) => {
            if (!onlineUsers.value.some((u: any) => Number(u.id) === Number(user.id))) {
                onlineUsers.value.push(user);
            }
        })
        .leaving((user: any) => {
            onlineUsers.value = onlineUsers.value.filter((u: any) => Number(u.id) !== Number(user.id));
            typingUsers.value = typingUsers.value.filter((u: any) => Number(u.id) !== Number(user.id));
        })
        .listen('.message.sent', (e: any) => {
            handleIncomingMessage(e.message, convId);
        })
        .listen('.message.updated', (e: any) => {
            handleMessageUpdated(e.message);
        })
        .listen('.message.deleted', (e: any) => {
            handleMessageDeleted(e.message_id);
        })
        .listen('.conversation.read', (e: any) => {
            handleConversationRead(e);
        })
        .listenForWhisper('typing', (e: any) => {
            handleIncomingTyping(e);
        });
}

function teardownWebSockets() {
    const echo = getEcho();

    if (echo) {
        if (activePresenceChannelId) {
            echo.leave(`chat.conversation.${activePresenceChannelId}`);
        }

        if (userId) {
            echo.leave(`chat.user.${userId}`);
        }
    }

    activePresenceChannel = null;
    activePresenceChannelId = null;
    userChannel = null;
    onlineUsers.value = [];
    typingUsers.value = [];
}
const showNewModal = ref(false);
const newRecipient = ref('');
const newMessageBody = ref('');
const showDeleteModal = ref(false);
const showProfile = ref(false);
const editingId = ref<number | null>(null);
const editDraft = ref('');
const editInputRef = ref<HTMLElement | null>(null);
const deleteTarget = ref<any | null>(null);
const messages = ref<any[]>(props.conversation?.messages ?? []);
const otherLastReadAt = ref<string | null>(
    props.conversation?.other_last_read_at ?? null,
);
const userLastReadAt = ref<string | null>(
    props.conversation?.user_last_read_at ?? null,
);
function sortConversations(list: any[]) {
    return [...list].sort((a: any, b: any) => {
        const dateA = a.last_message_at ? new Date(a.last_message_at).getTime() : 0;
        const dateB = b.last_message_at ? new Date(b.last_message_at).getTime() : 0;

        return dateB - dateA;
    });
}

const localConversations = ref<any[]>(sortConversations(props.conversations ?? []));
const localContacts = ref<any[]>(props.contacts ?? []);
watch(
    () => props.contacts,
    (val) => {
        if (val) {
            localContacts.value = val;
        }
    },
);
watch(
    () => props.conversations,
    (val) => {
        if (val) {
            localConversations.value = sortConversations(val);
        }
    },
    { deep: true },
);
const messagesRef = ref<HTMLElement | null>(null);
const sendInputRef = ref<HTMLTextAreaElement | null>(null);
let pollInterval: number | null = null;
let convPollInterval: number | null = null;

async function pollConversations() {
    try {
        const res = await fetch(`${prefix}/messenger`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (res.ok) {
            const data = await res.json();

            if (Array.isArray(data.contacts)) {
                localContacts.value = data.contacts;
            }

            if (Array.isArray(data.conversations)) {
                localConversations.value = sortConversations(
                    data.conversations.map((c: any) => {
                        if (activeId.value && c.id === activeId.value) {
                            return { ...c, unread: false, unread_count: 0 };
                        }

                        return c;
                    }),
                );
            }
        }
    } catch {}
}

watch(newMessage, () => {
    const el = sendInputRef.value;

    if (!el) {
return;
}

    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 120) + 'px';
});

function isRead(m: any) {
    if (m.sender_id !== userId) {
        return false;
    }

    if (!otherLastReadAt.value) {
        return false;
    }

    return new Date(m.created_at) <= new Date(otherLastReadAt.value);
}

function scrollToBottom(smooth = false) {
    // Wait for Vue to render the message list (the container may not exist
    // yet on first load) before measuring scrollHeight.
    nextTick(() => {
        requestAnimationFrame(() => {
            const el = messagesRef.value;

            if (!el) {
                return;
            }

            el.scrollTo({
                top: el.scrollHeight,
                behavior: smooth ? 'smooth' : 'instant',
            });
        });
    });
}

function isNearBottom() {
    if (!messagesRef.value) {
        return true;
    }

    const el = messagesRef.value;

    return el.scrollHeight - el.scrollTop - el.clientHeight < 100;
}

const teachers = computed(() =>
    localContacts.value.filter(
        (c: any) => c.role === 'teacher' || c.role === 'superadmin',
    ),
);
const students = computed(() =>
    localContacts.value.filter((c: any) => c.role === 'student'),
);

// The open conversation, overlaid with the latest participant profiles from
// polling so name/photo/bio edits show up without reopening the chat.
const activeConversation = computed(() => {
    const base = props.conversation;

    if (!base) {
return null;
}

    const live = localConversations.value.find((c: any) => c.id === base.id);
    const participants = live?.participants ?? base.participants;
    const otherId = base.other?.id;
    const other =
        participants?.find((p: any) => p.id === otherId) ?? base.other;

    return { ...base, participants, other };
});

function liveProfile(id: number | null | undefined) {
    if (id == null) {
return null;
}

    return (
        activeConversation.value?.participants?.find((p: any) => p.id === id) ??
        localContacts.value.find((c: any) => c.id === id) ??
        null
    );
}

function liveName(id: number | null | undefined, fallback: string) {
    return liveProfile(id)?.name ?? fallback;
}

function liveAvatar(id: number | null | undefined): string | null {
    return liveProfile(id)?.avatar ?? null;
}
const activeId = computed(() => activeConversation.value?.id ?? null);

const filteredConversations = computed(() => {
    const list = localConversations.value;

    if (!search.value) {
        return list;
    }

    const q = search.value.toLowerCase();

    return list.filter(
        (c: any) =>
            c.other?.name?.toLowerCase().includes(q) ||
            (c.title && c.title.toLowerCase().includes(q)) ||
            (c.subject && c.subject.toLowerCase().includes(q)),
    );
});

const msgStats = computed(() => {
    const list = messages.value.filter((m: any) => !m.deleted_at);
    const sent = list.filter((m: any) => m.sender_id === userId).length;

    return { total: list.length, sent, received: list.length - sent };
});

function roleLabel(role: string): string {
    if (role === 'superadmin') {
return 'ADMIN';
}

    if (role === 'teacher') {
return 'TEACHER';
}

    return 'STUDENT';
}

function handle(name: string): string {
    return (name || 'unknown').replace(/\s+/g, '');
}

function focusSend() {
    if (window.innerWidth < 1280) {
showProfile.value = false;
}

    requestAnimationFrame(() => sendInputRef.value?.focus());
}

function closeConversation() {
    router.get(`${prefix}/messenger`, {}, { preserveScroll: true });
}

function dayKey(date: string) {
    return new Date(date).toDateString();
}

function formatDay(date: string) {
    const d = new Date(date);
    const now = new Date();

    if (d.toDateString() === now.toDateString()) {
        return 'Today';
    }

    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);

    if (d.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }

    return d.toLocaleDateString(undefined, {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
}

const chatItems = computed(() => {
    const items: any[] = [];
    let lastDay = '';
    const msgs = messages.value;

    let firstUnreadMsgId: number | null = null;

    if (userLastReadAt.value) {
        const lastReadTime = new Date(userLastReadAt.value).getTime();
        const unreadMsg = msgs.find(
            (m: any) =>
                m.sender_id !== userId &&
                !m.deleted_at &&
                new Date(m.created_at).getTime() > lastReadTime,
        );

        if (unreadMsg) {
            firstUnreadMsgId = unreadMsg.id;
        }
    } else if (msgs.length > 0) {
        const unreadMsg = msgs.find(
            (m: any) => m.sender_id !== userId && !m.deleted_at,
        );

        if (unreadMsg) {
            firstUnreadMsgId = unreadMsg.id;
        }
    }

    let unreadSeparatorAdded = false;

    msgs.forEach((m: any, i: number) => {
        const dk = dayKey(m.created_at);

        if (dk !== lastDay) {
            items.push({ type: 'day', label: formatDay(m.created_at) });
            lastDay = dk;
        }

        if (
            firstUnreadMsgId &&
            m.id === firstUnreadMsgId &&
            !unreadSeparatorAdded
        ) {
            items.push({ type: 'unread_separator', label: 'Unread Messages' });
            unreadSeparatorAdded = true;
        }

        const isUnread =
            m.sender_id !== userId &&
            !m.deleted_at &&
            (!userLastReadAt.value ||
                new Date(m.created_at).getTime() >
                    new Date(userLastReadAt.value).getTime());

        const next = msgs[i + 1];
        const showAvatar =
            m.sender_id !== userId && !(next && next.sender_id === m.sender_id);
        items.push({ type: 'msg', m, showAvatar, isUnread });
    });

    return items;
});

function initials(name: string) {
    return name
        .split(' ')
        .map((w: string) => w[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
}

function avatarBg(name: string) {
    const colors = [
        '#3B82F6',
        '#10B981',
        '#F59E0B',
        '#EF4444',
        '#7C3AED',
        '#EC4899',
        '#06B6D4',
        '#F97316',
    ];
    let hash = 0;

    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 5) - hash);
    }

    return colors[Math.abs(hash) % colors.length];
}

function avatarBg2(name: string) {
    const colors = [
        '#2563EB',
        '#059669',
        '#D97706',
        '#DC2626',
        '#6D28D9',
        '#DB2777',
        '#0891B2',
        '#EA580C',
    ];
    let hash = 0;

    for (let i = 0; i < name.length; i++) {
        hash = name.charCodeAt(i) + ((hash << 7) - hash);
    }

    return colors[Math.abs(hash) % colors.length];
}

function formatTime(date: string) {
    const d = new Date(date);
    const now = new Date();
    const isToday = d.toDateString() === now.toDateString();

    if (isToday) {
        return d.toLocaleTimeString(undefined, {
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);

    if (d.toDateString() === yesterday.toDateString()) {
        return 'Yesterday';
    }

    return d.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

function openConversation(id: number) {
    const target = localConversations.value.find((c: any) => c.id === id);

    if (target) {
        target.unread = false;
        target.unread_count = 0;
    }

    router.get(
        `${prefix}/messenger/${id}`,
        {},
        { preserveScroll: true, preserveState: true },
    );
}

async function sendMessage() {
    if ((!newMessage.value.trim() && !selectedFile.value) || !activeConversation.value || sending.value) {
        return;
    }

    const text = newMessage.value.trim();
    const file = selectedFile.value;
    const previewUrl = selectedFilePreview.value;

    sending.value = true;
    newMessage.value = '';
    clearSelectedFile();

    // Optimistically create temporary local message
    const tempId = -Date.now();
    const optimisticMessage = {
        id: tempId,
        sender_id: userId,
        sender_name: (page.props.auth as any).user.name,
        body: text,
        attachment_name: file ? file.name : null,
        attachment_type: file ? file.type : null,
        attachment_size: file ? file.size : null,
        attachment_url: previewUrl || null,
        created_at: new Date().toISOString(),
        edited_at: null,
        deleted_at: null,
        sending: true,
    };

    messages.value = [...messages.value, optimisticMessage];
    const targetConv = localConversations.value.find(
        (c: any) => c.id === activeConversation.value?.id,
    );

    if (targetConv) {
        targetConv.last_message = text || (file ? '📎 ' + file.name : '');
        targetConv.last_message_at = new Date().toISOString();
        targetConv.last_mine = true;
        targetConv.unread = false;
        targetConv.unread_count = 0;
    }

    scrollToBottom(true);

    try {
        const token =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute('content') ?? '';

        const formData = new FormData();

        if (text) {
formData.append('body', text);
}

        if (file) {
formData.append('attachment', file);
}

        const res = await fetch(
            `${prefix}/messenger/${activeConversation.value.id}/send`,
            {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            },
        );

        if (res.ok) {
            const data = await res.json();

            if (data.message) {
                // Replace optimistic message with the saved server message
                messages.value = messages.value.map((m: any) =>
                    m.id === tempId ? data.message : m,
                );

                // Update conversation preview in sidebar
                const conv = props.conversations.find(
                    (c: any) => c.id === activeConversation.value?.id,
                );

                if (conv) {
                    conv.last_message = text || (file ? '📎 ' + file.name : '');
                    conv.last_message_at = data.message.created_at;
                    conv.last_mine = true;
                }
            }
        } else {
            // If request failed, fall back to standard Inertia post
            router.post(
                `${prefix}/messenger/${activeConversation.value.id}/send`,
                { body: text, attachment: file },
                {
                    preserveScroll: true,
                    preserveState: true,
                    forceFormData: true,
                    onError: () => {
                        messages.value = messages.value.filter(
                            (m: any) => m.id !== tempId,
                        );
                        newMessage.value = text;
                    },
                },
            );
        }
    } catch {
        // Network error fallback
        router.post(
            `${prefix}/messenger/${activeConversation.value.id}/send`,
            { body: text, attachment: file },
            {
                preserveScroll: true,
                preserveState: true,
                forceFormData: true,
                onError: () => {
                    messages.value = messages.value.filter(
                        (m: any) => m.id !== tempId,
                    );
                    newMessage.value = text;
                },
            },
        );
    } finally {
        sending.value = false;
        requestAnimationFrame(() => sendInputRef.value?.focus());
    }
}

function startConversation() {
    if (!newRecipient.value || !newMessageBody.value.trim()) {
        return;
    }

    router.post(
        `${prefix}/messenger/start`,
        { recipient_id: newRecipient.value, message: newMessageBody.value },
        {
            onSuccess: () => {
                showNewModal.value = false;
                newRecipient.value = '';
                newMessageBody.value = '';
            },
        },
    );
}

function deleteConversation() {
    if (!activeConversation.value) {
        return;
    }

    showDeleteModal.value = true;
}

function confirmDelete() {
    showDeleteModal.value = false;
    router.delete(`${prefix}/messenger/${activeConversation.value.id}`, {
        preserveScroll: true,
    });
}

function startEdit(m: any) {
    editingId.value = m.id;
    editDraft.value = m.body;
    requestAnimationFrame(() => editInputRef.value?.focus());
}

function cancelEdit() {
    editingId.value = null;
    editDraft.value = '';
}

function submitEdit(m: any) {
    const body = editDraft.value.trim();

    if (!body || !activeConversation.value) {
        return;
    }

    router.patch(
        `${prefix}/messenger/${activeConversation.value.id}/messages/${m.id}`,
        { body },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                cancelEdit();
            },
        },
    );
}

function askDeleteMessage(m: any) {
    deleteTarget.value = m;
}

function confirmDeleteMessage() {
    const m = deleteTarget.value;
    deleteTarget.value = null;

    if (!m || !activeConversation.value) {
        return;
    }

    router.delete(
        `${prefix}/messenger/${activeConversation.value.id}/messages/${m.id}`,
        { preserveScroll: true },
    );
}

async function pollMessages() {
    if (!activeConversation.value) {
        return;
    }

    const since = messages.value.length
        ? messages.value[messages.value.length - 1].created_at
        : null;

    try {
        const url = `${prefix}/messenger/${activeConversation.value.id}/poll${since ? `?since=${encodeURIComponent(since)}` : ''}`;
        const res = await fetch(url);
        const data = await res.json();

        if (data.other_last_read_at !== undefined) {
            otherLastReadAt.value = data.other_last_read_at;
        }

        if (data.messages?.length) {
            const existingIds = new Set(messages.value.map((m: any) => m.id));
            const newOnes = data.messages.filter(
                (m: any) => !existingIds.has(m.id),
            );

            if (newOnes.length) {
                messages.value = [...messages.value, ...newOnes];
                const lastM = newOnes[newOnes.length - 1];
                const targetConv = localConversations.value.find(
                    (c: any) => c.id === activeConversation.value?.id,
                );

                if (targetConv) {
                    targetConv.last_message = lastM.body;
                    targetConv.last_message_at = lastM.created_at;
                    targetConv.last_mine = lastM.sender_id === userId;
                    targetConv.unread = false;
                    targetConv.unread_count = 0;
                }
            }
        }
    } catch {}
}

// Watch the page prop, not the computed `activeConversation`, which gets a new
// object on every poll and would reset messages and scroll position.
watch(
    () => props.conversation,
    (val) => {
        messages.value = val?.messages ?? [];
        otherLastReadAt.value = val?.other_last_read_at ?? null;
        userLastReadAt.value = val?.user_last_read_at ?? null;
        showProfile.value = false;
        // Open on the most recent messages; repeat once after layout settles
        // (images/attachments can change the height).
        scrollToBottom(false);
        setTimeout(() => scrollToBottom(false), 150);
        joinActiveConversationChannel();
    },
    { immediate: true },
);

onMounted(() => scrollToBottom(false));

watch(messages, () => {
    if (isNearBottom()) {
        scrollToBottom(true);
    }
});

onUnmounted(() => {
    cleanupRecording();

    if (pollInterval) {
        clearInterval(pollInterval);
    }

    if (convPollInterval) {
        clearInterval(convPollInterval);
    }

    if (typingCleanupInterval) {
        clearInterval(typingCleanupInterval);
    }

    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }

    teardownWebSockets();
});
</script>

<style>
.messenger-scroll::-webkit-scrollbar {
    display: none;
}
.messenger-scroll {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.h-4\.5 {
    height: 1.125rem;
}
.w-4\.5 {
    width: 1.125rem;
}
</style>
