<template>
    <div
        class="flex flex-col gap-1.5 rounded-xl p-2.5 transition-all min-w-[240px] max-w-[320px] select-none"
        :class="
            isSender
                ? 'bg-white/15 text-white border border-white/20'
                : 'bg-[var(--gl-surface-2)] text-[var(--gl-text-primary)] border border-[var(--gl-border)]'
        "
    >
        <div class="flex items-center gap-2.5">
            <!-- Play / Pause Button -->
            <button
                type="button"
                @click="togglePlay"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full transition-transform active:scale-95 shadow-sm"
                :class="
                    isSender
                        ? 'bg-white text-blue-600 hover:bg-white/90'
                        : 'bg-blue-600 text-white hover:bg-blue-500'
                "
                :title="isPlaying ? 'Pause' : 'Play voice message'"
            >
                <Pause v-if="isPlaying" class="h-4 w-4 fill-current" />
                <Play v-else class="h-4 w-4 fill-current translate-x-0.5" />
            </button>

            <!-- Waveform / Progress Slider -->
            <div class="flex-1 flex flex-col justify-center gap-1 min-w-0">
                <div
                    class="relative h-2 w-full rounded-full cursor-pointer overflow-hidden"
                    :class="isSender ? 'bg-white/25' : 'bg-black/10 dark:bg-white/10'"
                    @click="onScrub"
                    ref="trackRef"
                >
                    <div
                        class="h-full rounded-full transition-[width] duration-75"
                        :class="isSender ? 'bg-white' : 'bg-blue-600'"
                        :style="{ width: `${progressPercent}%` }"
                    ></div>
                </div>

                <div class="flex items-center justify-between text-[11px] font-mono leading-none opacity-80">
                    <span>{{ formatTime(currentTime) }}</span>
                    <span>{{ formatTime(duration) }}</span>
                </div>
            </div>

            <!-- Playback Rate Toggle -->
            <button
                type="button"
                @click="cycleRate"
                class="rounded px-1.5 py-0.5 text-[10px] font-bold tracking-tight uppercase transition-colors shrink-0"
                :class="
                    isSender
                        ? 'bg-white/20 hover:bg-white/30 text-white'
                        : 'bg-black/5 dark:bg-white/10 hover:bg-black/10 text-[var(--gl-text-secondary)]'
                "
                title="Change playback speed"
            >
                {{ playbackRate }}x
            </button>

            <!-- Download Button -->
            <a
                :href="src"
                :download="filename || 'voice-note.webm'"
                class="p-1 rounded-md transition-opacity opacity-70 hover:opacity-100 shrink-0"
                :title="'Download ' + (filename || 'audio')"
            >
                <Download class="h-3.5 w-3.5" />
            </a>
        </div>

        <audio
            ref="audioRef"
            :src="src"
            preload="metadata"
            @timeupdate="onTimeUpdate"
            @loadedmetadata="onLoadedMetadata"
            @ended="onEnded"
            class="hidden"
        ></audio>
    </div>
</template>

<script setup lang="ts">
import { Play, Pause, Download } from '@lucide/vue';
import { ref, computed, onUnmounted } from 'vue';

const props = defineProps<{
    src: string;
    filename?: string;
    isSender?: boolean;
}>();

const audioRef = ref<HTMLAudioElement | null>(null);
const trackRef = ref<HTMLDivElement | null>(null);

const isPlaying = ref(false);
const currentTime = ref(0);
const duration = ref(0);
const playbackRate = ref(1);

const progressPercent = computed(() => {
    if (!duration.value) {
return 0;
}

    return Math.min(100, Math.max(0, (currentTime.value / duration.value) * 100));
});

function togglePlay() {
    if (!audioRef.value) {
return;
}

    if (isPlaying.value) {
        audioRef.value.pause();
        isPlaying.value = false;
    } else {
        audioRef.value.play().then(() => {
            isPlaying.value = true;
        }).catch(() => {
            isPlaying.value = false;
        });
    }
}

function onTimeUpdate() {
    if (audioRef.value) {
        currentTime.value = audioRef.value.currentTime;
    }
}

function onLoadedMetadata() {
    if (audioRef.value && !isNaN(audioRef.value.duration)) {
        duration.value = audioRef.value.duration;
    }
}

function onEnded() {
    isPlaying.value = false;
    currentTime.value = 0;
}

function onScrub(e: MouseEvent) {
    if (!trackRef.value || !audioRef.value || !duration.value) {
return;
}

    const rect = trackRef.value.getBoundingClientRect();
    const pos = (e.clientX - rect.left) / rect.width;
    const targetTime = Math.max(0, Math.min(duration.value, pos * duration.value));
    audioRef.value.currentTime = targetTime;
    currentTime.value = targetTime;
}

function cycleRate() {
    const rates = [1, 1.5, 2];
    const nextIdx = (rates.indexOf(playbackRate.value) + 1) % rates.length;
    playbackRate.value = rates[nextIdx];

    if (audioRef.value) {
        audioRef.value.playbackRate = playbackRate.value;
    }
}

function formatTime(sec: number): string {
    if (!sec || isNaN(sec)) {
return '0:00';
}

    const m = Math.floor(sec / 60);
    const s = Math.floor(sec % 60);

    return `${m}:${s.toString().padStart(2, '0')}`;
}

onUnmounted(() => {
    if (audioRef.value) {
        audioRef.value.pause();
    }
});
</script>
