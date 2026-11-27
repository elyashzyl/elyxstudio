import { ref } from 'vue';

export interface ThemePreview {
    bg: string;
    surface: string;
    primary: string;
    text: string;
}

export interface ThemeOption {
    id: string;
    label: string;
    dark: boolean;
    swatches: [string, string, string];
    preview: ThemePreview;
}

export const THEMES: ThemeOption[] = [
    {
        id: 'default',
        label: 'Default',
        dark: true,
        swatches: ['#3B82F6', '#7C3AED', '#F59E0B'],
        preview: {
            bg: '#0F172A',
            surface: '#1E293B',
            primary: '#3B82F6',
            text: '#F8FAFC',
        },
    },
    {
        id: 'midnight-bloom',
        label: 'Midnight Bloom',
        dark: true,
        swatches: ['#8B5CF6', '#38BDF8', '#F0ABFC'],
        preview: {
            bg: '#0B0620',
            surface: '#171233',
            primary: '#8B5CF6',
            text: '#F1EDFF',
        },
    },
    {
        id: 'ocean-breeze',
        label: 'Ocean Breeze',
        dark: true,
        swatches: ['#22D3EE', '#2DD4BF', '#FBBF24'],
        preview: {
            bg: '#04202B',
            surface: '#07303E',
            primary: '#22D3EE',
            text: '#EAFBFF',
        },
    },
    {
        id: 'sunset-horizon',
        label: 'Sunset Horizon',
        dark: true,
        swatches: ['#FB923C', '#F43F5E', '#FBBF24'],
        preview: {
            bg: '#1C0E04',
            surface: '#2C1508',
            primary: '#FB923C',
            text: '#FFF4E8',
        },
    },
    {
        id: 'cyberpunk',
        label: 'Cyberpunk',
        dark: true,
        swatches: ['#FF2D78', '#00E5FF', '#FACC15'],
        preview: {
            bg: '#0A0A12',
            surface: '#12121E',
            primary: '#FF2D78',
            text: '#F4F2FF',
        },
    },
    {
        id: 'kodama-grove',
        label: 'Kodama Grove',
        dark: true,
        swatches: ['#4ADE80', '#2DD4BF', '#FBBF24'],
        preview: {
            bg: '#0A140E',
            surface: '#102015',
            primary: '#4ADE80',
            text: '#EDF7EF',
        },
    },
    {
        id: 'quantum-rose',
        label: 'Quantum Rose',
        dark: true,
        swatches: ['#FB7185', '#E879F9', '#FBBF24'],
        preview: {
            bg: '#1D0A14',
            surface: '#2C1020',
            primary: '#FB7185',
            text: '#FFF0F4',
        },
    },
    {
        id: 'mono',
        label: 'Mono',
        dark: true,
        swatches: ['#E4E4E7', '#A1A1AA', '#FACC15'],
        preview: {
            bg: '#101012',
            surface: '#18181B',
            primary: '#E4E4E7',
            text: '#FAFAFA',
        },
    },
    {
        id: 'vintage-paper',
        label: 'Vintage Paper',
        dark: false,
        swatches: ['#C2410C', '#5F6C5D', '#D97706'],
        preview: {
            bg: '#F4EFE4',
            surface: '#FBF8F0',
            primary: '#C2410C',
            text: '#3E3428',
        },
    },
    {
        id: 'candyland',
        label: 'Candyland',
        dark: false,
        swatches: ['#EC4899', '#8B5CF6', '#F59E0B'],
        preview: {
            bg: '#FFF0F6',
            surface: '#FFFFFF',
            primary: '#EC4899',
            text: '#4A1030',
        },
    },
    {
        id: 'claude',
        label: 'Claude',
        dark: false,
        swatches: ['#D97757', '#5F6C5D', '#EAB308'],
        preview: {
            bg: '#F5F0E8',
            surface: '#FFFFFF',
            primary: '#D97757',
            text: '#2E2620',
        },
    },
    {
        id: 'emerald-night',
        label: 'Emerald Night',
        dark: true,
        swatches: ['#10B981', '#14B8A6', '#FBBF24'],
        preview: {
            bg: '#04120C',
            surface: '#0A1F14',
            primary: '#10B981',
            text: '#E9FBF0',
        },
    },
    {
        id: 'royal-grape',
        label: 'Royal Grape',
        dark: true,
        swatches: ['#A855F7', '#EC4899', '#FBBF24'],
        preview: {
            bg: '#150A24',
            surface: '#211238',
            primary: '#A855F7',
            text: '#F5EDFF',
        },
    },
    {
        id: 'crimson-dusk',
        label: 'Crimson Dusk',
        dark: true,
        swatches: ['#EF4444', '#F97316', '#FBBF24'],
        preview: {
            bg: '#1A080A',
            surface: '#270D10',
            primary: '#EF4444',
            text: '#FDECEC',
        },
    },
    {
        id: 'arctic-frost',
        label: 'Arctic Frost',
        dark: true,
        swatches: ['#7DD3FC', '#60A5FA', '#FDE68A'],
        preview: {
            bg: '#0A1626',
            surface: '#10233A',
            primary: '#7DD3FC',
            text: '#F0F9FF',
        },
    },
    {
        id: 'golden-hour',
        label: 'Golden Hour',
        dark: true,
        swatches: ['#F59E0B', '#FB923C', '#FDE047'],
        preview: {
            bg: '#191004',
            surface: '#241804',
            primary: '#F59E0B',
            text: '#FFFAEB',
        },
    },
    {
        id: 'lavender-mist',
        label: 'Lavender Mist',
        dark: true,
        swatches: ['#A78BFA', '#F0ABFC', '#FDE68A'],
        preview: {
            bg: '#12101F',
            surface: '#1B1830',
            primary: '#A78BFA',
            text: '#F2EFFC',
        },
    },
    {
        id: 'teal-abyss',
        label: 'Teal Abyss',
        dark: true,
        swatches: ['#14B8A6', '#22D3EE', '#FCD34D'],
        preview: {
            bg: '#02181C',
            surface: '#062A32',
            primary: '#14B8A6',
            text: '#E6FFFB',
        },
    },
    {
        id: 'neon-lime',
        label: 'Neon Lime',
        dark: true,
        swatches: ['#A3E635', '#4ADE80', '#FDE047'],
        preview: {
            bg: '#0C1304',
            surface: '#141F08',
            primary: '#A3E635',
            text: '#F7FEE7',
        },
    },
    {
        id: 'copper-rust',
        label: 'Copper Rust',
        dark: true,
        swatches: ['#EA580C', '#F59E0B', '#FCD34D'],
        preview: {
            bg: '#170C06',
            surface: '#241209',
            primary: '#EA580C',
            text: '#FFF1E8',
        },
    },
    {
        id: 'ice-violet',
        label: 'Ice Violet',
        dark: true,
        swatches: ['#818CF8', '#C084FC', '#FDE68A'],
        preview: {
            bg: '#0E0D1A',
            surface: '#171628',
            primary: '#818CF8',
            text: '#EEEFFF',
        },
    },
    {
        id: 'blood-moon',
        label: 'Blood Moon',
        dark: true,
        swatches: ['#F43F5E', '#FB923C', '#FDE047'],
        preview: {
            bg: '#120607',
            surface: '#1D0A0C',
            primary: '#F43F5E',
            text: '#FDEBEB',
        },
    },
    {
        id: 'deep-space',
        label: 'Deep Space',
        dark: true,
        swatches: ['#3B82F6', '#6366F1', '#22D3EE'],
        preview: {
            bg: '#050914',
            surface: '#0A1224',
            primary: '#3B82F6',
            text: '#EAF1FF',
        },
    },
    {
        id: 'matcha',
        label: 'Matcha',
        dark: true,
        swatches: ['#84CC16', '#4ADE80', '#FDE047'],
        preview: {
            bg: '#0D120A',
            surface: '#151D10',
            primary: '#84CC16',
            text: '#F2F7E8',
        },
    },
    {
        id: 'plum-wine',
        label: 'Plum Wine',
        dark: true,
        swatches: ['#C026D3', '#E879F9', '#FBBF24'],
        preview: {
            bg: '#160A17',
            surface: '#231026',
            primary: '#C026D3',
            text: '#FAEDFC',
        },
    },
    {
        id: 'steel-blue',
        label: 'Steel Blue',
        dark: true,
        swatches: ['#CBD5E1', '#64748B', '#F59E0B'],
        preview: {
            bg: '#0B1220',
            surface: '#111C30',
            primary: '#CBD5E1',
            text: '#F1F5F9',
        },
    },
    {
        id: 'ultraviolet',
        label: 'Ultraviolet',
        dark: true,
        swatches: ['#7C3AED', '#D946EF', '#F0ABFC'],
        preview: {
            bg: '#0D0518',
            surface: '#180A2E',
            primary: '#7C3AED',
            text: '#F3EDFF',
        },
    },
    {
        id: 'ember-glow',
        label: 'Ember Glow',
        dark: true,
        swatches: ['#F97316', '#EF4444', '#FDE047'],
        preview: {
            bg: '#170703',
            surface: '#240B05',
            primary: '#F97316',
            text: '#FFF3E8',
        },
    },
    {
        id: 'pine-forest',
        label: 'Pine Forest',
        dark: true,
        swatches: ['#2DD4BF', '#34D399', '#FDE68A'],
        preview: {
            bg: '#071210',
            surface: '#0C1F1B',
            primary: '#2DD4BF',
            text: '#E9F7F2',
        },
    },
    {
        id: 'galaxy-pink',
        label: 'Galaxy Pink',
        dark: true,
        swatches: ['#D946EF', '#8B5CF6', '#FDE047'],
        preview: {
            bg: '#130818',
            surface: '#1F0D28',
            primary: '#D946EF',
            text: '#FBEFFD',
        },
    },
    {
        id: 'slate-storm',
        label: 'Slate Storm',
        dark: true,
        swatches: ['#60A5FA', '#94A3B8', '#FBBF24'],
        preview: {
            bg: '#0C0F16',
            surface: '#141922',
            primary: '#60A5FA',
            text: '#F1F5F9',
        },
    },
    {
        id: 'turquoise-lagoon',
        label: 'Turquoise Lagoon',
        dark: true,
        swatches: ['#2DD4BF', '#22D3EE', '#FDE047'],
        preview: {
            bg: '#03211F',
            surface: '#07332F',
            primary: '#2DD4BF',
            text: '#E6FFFA',
        },
    },
    {
        id: 'magenta-pulse',
        label: 'Magenta Pulse',
        dark: true,
        swatches: ['#EC4899', '#F0ABFC', '#FDE047'],
        preview: {
            bg: '#190414',
            surface: '#270820',
            primary: '#EC4899',
            text: '#FDEBF6',
        },
    },
    {
        id: 'bronze-age',
        label: 'Bronze Age',
        dark: true,
        swatches: ['#D97706', '#A16207', '#FBBF24'],
        preview: {
            bg: '#120C05',
            surface: '#1D130A',
            primary: '#D97706',
            text: '#F7EFE2',
        },
    },
    {
        id: 'night-owl',
        label: 'Night Owl',
        dark: true,
        swatches: ['#58A6FF', '#BC8CFF', '#D29922'],
        preview: {
            bg: '#0D1117',
            surface: '#161B22',
            primary: '#58A6FF',
            text: '#E6EDF3',
        },
    },
    {
        id: 'sakura-night',
        label: 'Sakura Night',
        dark: true,
        swatches: ['#F472B6', '#C084FC', '#FDE68A'],
        preview: {
            bg: '#170D16',
            surface: '#241222',
            primary: '#F472B6',
            text: '#FCEBF4',
        },
    },
    {
        id: 'mint-fresh',
        label: 'Mint Fresh',
        dark: false,
        swatches: ['#059669', '#0D9488', '#D97706'],
        preview: {
            bg: '#EFFAF3',
            surface: '#FFFFFF',
            primary: '#059669',
            text: '#0B3020',
        },
    },
    {
        id: 'sky-morning',
        label: 'Sky Morning',
        dark: false,
        swatches: ['#2563EB', '#0284C7', '#D97706'],
        preview: {
            bg: '#EFF6FF',
            surface: '#FFFFFF',
            primary: '#2563EB',
            text: '#172554',
        },
    },
    {
        id: 'lemon-cream',
        label: 'Lemon Cream',
        dark: false,
        swatches: ['#D97706', '#CA8A04', '#EA580C'],
        preview: {
            bg: '#FFFBEB',
            surface: '#FFFFFF',
            primary: '#D97706',
            text: '#451A03',
        },
    },
    {
        id: 'peach-sorbet',
        label: 'Peach Sorbet',
        dark: false,
        swatches: ['#EA580C', '#DB2777', '#CA8A04'],
        preview: {
            bg: '#FFF1E8',
            surface: '#FFFFFF',
            primary: '#EA580C',
            text: '#431407',
        },
    },
    {
        id: 'lilac-field',
        label: 'Lilac Field',
        dark: false,
        swatches: ['#7C3AED', '#DB2777', '#D97706'],
        preview: {
            bg: '#F5F0FF',
            surface: '#FFFFFF',
            primary: '#7C3AED',
            text: '#2E1065',
        },
    },
    {
        id: 'rose-garden',
        label: 'Rose Garden',
        dark: false,
        swatches: ['#E11D48', '#DB2777', '#D97706'],
        preview: {
            bg: '#FFF1F2',
            surface: '#FFFFFF',
            primary: '#E11D48',
            text: '#4C0519',
        },
    },
    {
        id: 'oatmeal',
        label: 'Oatmeal',
        dark: false,
        swatches: ['#A16207', '#6B7C5E', '#C2410C'],
        preview: {
            bg: '#F3EFE6',
            surface: '#FBF9F3',
            primary: '#A16207',
            text: '#3F382C',
        },
    },
    {
        id: 'seafoam',
        label: 'Seafoam',
        dark: false,
        swatches: ['#0D9488', '#0284C7', '#D97706'],
        preview: {
            bg: '#ECFDF5',
            surface: '#FFFFFF',
            primary: '#0D9488',
            text: '#042F2E',
        },
    },
    {
        id: 'buttercream',
        label: 'Buttercream',
        dark: false,
        swatches: ['#CA8A04', '#A16207', '#EA580C'],
        preview: {
            bg: '#FFFDF0',
            surface: '#FFFFFF',
            primary: '#CA8A04',
            text: '#422006',
        },
    },
    {
        id: 'periwinkle',
        label: 'Periwinkle',
        dark: false,
        swatches: ['#4F46E5', '#0EA5E9', '#D97706'],
        preview: {
            bg: '#EEF2FF',
            surface: '#FFFFFF',
            primary: '#4F46E5',
            text: '#1E1B4B',
        },
    },
    {
        id: 'terracotta-sun',
        label: 'Terracotta Sun',
        dark: false,
        swatches: ['#C2410C', '#B45309', '#CA8A04'],
        preview: {
            bg: '#FFF4EC',
            surface: '#FFFFFF',
            primary: '#C2410C',
            text: '#431407',
        },
    },
    {
        id: 'sage-meadow',
        label: 'Sage Meadow',
        dark: false,
        swatches: ['#65A30D', '#0D9488', '#CA8A04'],
        preview: {
            bg: '#F0F5EC',
            surface: '#FFFFFF',
            primary: '#65A30D',
            text: '#1A2E05',
        },
    },
    {
        id: 'powder-blue',
        label: 'Powder Blue',
        dark: false,
        swatches: ['#0284C7', '#4F46E5', '#D97706'],
        preview: {
            bg: '#F0F9FF',
            surface: '#FFFFFF',
            primary: '#0284C7',
            text: '#0C4A6E',
        },
    },
    {
        id: 'blush',
        label: 'Blush',
        dark: false,
        swatches: ['#B4436B', '#8B5CF6', '#D97706'],
        preview: {
            bg: '#FDF2F4',
            surface: '#FFFFFF',
            primary: '#B4436B',
            text: '#471C28',
        },
    },
];

const STORAGE_KEY = 'edupulse-theme';

export const activeTheme = ref<string>('default');
export const isDark = ref<boolean>(true);

function syncDomAndStorage(themeId: string, dark: boolean): void {
    isDark.value = dark;
    activeTheme.value = themeId;

    if (typeof window === 'undefined') {
        return;
    }

    if (dark) {
        document.documentElement.classList.add('dark', 'dark-mode');
    } else {
        document.documentElement.classList.remove('dark', 'dark-mode');
    }

    if (themeId === 'default') {
        document.documentElement.removeAttribute('data-theme');
    } else {
        document.documentElement.setAttribute('data-theme', themeId);
    }

    try {
        localStorage.setItem(STORAGE_KEY, themeId);
        localStorage.setItem('dark-mode', dark ? 'dark' : 'light');
        localStorage.setItem('appearance', dark ? 'dark' : 'light');
    } catch {
        /* storage unavailable */
    }
}

export function getTheme(): string {
    if (typeof window === 'undefined') {
        return 'default';
    }

    const stored = localStorage.getItem(STORAGE_KEY);

    return stored && THEMES.some((t) => t.id === stored) ? stored : 'default';
}

export function applyTheme(id: string): void {
    const theme = THEMES.find((t) => t.id === id);
    const dark = theme ? theme.dark : isDark.value;

    syncDomAndStorage(id, dark);
}

export function toggleThemeMode(): void {
    const nextDark = !isDark.value;
    let nextTheme = activeTheme.value;
    const currentThemeOption = THEMES.find((t) => t.id === nextTheme);

    if (nextDark) {
        // Switching to Dark mode
        if (currentThemeOption && !currentThemeOption.dark) {
            nextTheme = 'default';
        }
    } else {
        // Switching to Light mode
        if (
            currentThemeOption &&
            currentThemeOption.dark &&
            nextTheme !== 'default'
        ) {
            nextTheme = 'default';
        }
    }

    syncDomAndStorage(nextTheme, nextDark);
}

export function initTheme(): void {
    if (typeof window === 'undefined') {
        return;
    }

    const storedTheme = getTheme();
    const storedDark = localStorage.getItem('dark-mode');
    const theme = THEMES.find((t) => t.id === storedTheme);

    let initialDark = true;

    if (storedTheme !== 'default' && theme) {
        initialDark = theme.dark;
    } else if (storedDark) {
        initialDark = storedDark !== 'light';
    }

    syncDomAndStorage(storedTheme, initialDark);

    window.addEventListener('storage', (e) => {
        if (e.key === STORAGE_KEY || e.key === 'dark-mode') {
            initTheme();
        }
    });
}
