export type BadgeInfo = { icon: string; label: string; level: number };

export const BADGES: Record<string, BadgeInfo> = {
    golden_pencil: { icon: '✏️', label: 'Golden Pencil', level: 2 },
    star_compass: { icon: '🧭', label: 'Star Compass', level: 6 },
    masters_seal: { icon: '🎖️', label: "Master's Seal", level: 11 },
    crown_of_knowledge: { icon: '👑', label: 'Crown of Knowledge', level: 12 },
    immortal_flame: { icon: '🔥', label: 'Immortal Flame', level: 13 },
    emerald_bookmark: { icon: '🔖', label: 'Emerald Bookmark', level: 20 },
    sapphire_medal: { icon: '🏅', label: 'Sapphire Medal', level: 30 },
    platinum_trophy: { icon: '🏆', label: 'Platinum Trophy', level: 40 },
    legendary_laurel: { icon: '🌿', label: 'Legendary Laurel', level: 50 },
};

export function badgeIcon(key?: string | null): string {
    return (key && BADGES[key]?.icon) || '✨';
}

export function badgeLabel(key?: string | null): string {
    const b = key ? BADGES[key] : undefined;

    return b ? `${b.label} (Level ${b.level})` : 'Badge';
}
