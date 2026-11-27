export const XP_PER_LEVEL = 100;

export const LEVEL_TITLES = [
    'Novice',
    'Beginner',
    'Explorer',
    'Scholar',
    'Achiever',
    'Rising Star',
    'Skilled',
    'Expert',
    'Master',
    'Grandmaster',
    'Legend',
    'Mythic',
    'Immortal',
];

export function levelForPoints(points: number, xpPerLevel: number = XP_PER_LEVEL): number {
    const step = xpPerLevel > 0 ? xpPerLevel : XP_PER_LEVEL;

    return Math.floor(Math.max(0, Number(points) || 0) / step) + 1;
}

export function progressForLevel(points: number, xpPerLevel: number = XP_PER_LEVEL): number {
    const step = xpPerLevel > 0 ? xpPerLevel : XP_PER_LEVEL;

    return Math.max(0, Number(points) || 0) % step;
}

export function pointsForLevel(level: number, xpPerLevel: number = XP_PER_LEVEL): number {
    const step = xpPerLevel > 0 ? xpPerLevel : XP_PER_LEVEL;

    return Math.max(0, level - 1) * step;
}

export function nextLevelPoints(points: number, xpPerLevel: number = XP_PER_LEVEL): number {
    const step = xpPerLevel > 0 ? xpPerLevel : XP_PER_LEVEL;

    return levelForPoints(points, step) * step;
}

export function titleForLevel(level: number): string {
    return LEVEL_TITLES[level - 1] ?? 'Champion';
}

export function progressPct(points: number, xpPerLevel: number = XP_PER_LEVEL): number {
    const step = xpPerLevel > 0 ? xpPerLevel : XP_PER_LEVEL;
    const p = progressForLevel(points, step);

    return Math.min(100, Math.round((p / step) * 100));
}
