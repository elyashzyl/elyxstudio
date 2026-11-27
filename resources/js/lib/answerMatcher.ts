/**
 * Client-side Answer Matcher
 * Mirrors backend AnswerMatcherService for real-time game checks & rechecks:
 * - Case-insensitive
 * - Punctuation stripping
 * - Articles ('a', 'an', 'the') stripping
 * - Filler stripping ('tool', 'feature', 'button', etc.)
 * - Singular / plural normalization
 * - Conversational wrapper stripping
 * - Morphological stemming and concept root multiset analysis
 * - Exclusive pair conflict rejection
 * - Levenshtein edit distance & token overlap
 */

const NUMBER_MAP: Record<string, string> = {
    zero: '0',
    one: '1',
    two: '2',
    three: '3',
    four: '4',
    five: '5',
    six: '6',
    seven: '7',
    eight: '8',
    nine: '9',
    ten: '10',
    first: '1',
    second: '2',
    third: '3',
    fourth: '4',
    fifth: '5',
};

const FILLERS = new Set([
    'tool',
    'tools',
    'feature',
    'features',
    'property',
    'properties',
    'button',
    'buttons',
    'panel',
    'panels',
    'option',
    'options',
    'menu',
    'menus',
    'command',
    'commands',
    'element',
    'elements',
    'mode',
    'modes',
    'key',
    'keys',
    'shortcut',
    'shortcuts',
    'bar',
    'bars',
    'box',
    'boxes',
    'icon',
    'icons',
    'field',
    'fields',
    'window',
    'windows',
    'type',
    'types',
    'method',
    'methods',
    'item',
    'items',
]);

const SYMBOL_MAP: Record<string, string> = {
    '&': 'and',
    '+': 'plus',
    '%': 'percent',
    '#': 'number',
    '@': 'at',
    '=': 'equals',
    '/': 'slash',
    '*': 'asterisk',
    '<': 'less than',
    '>': 'greater than',
};

const SYNONYM_GROUPS: string[][] = [
    ['selection tool', 'black arrow', 'selection', 'pointer', 'arrow pointer', 'v'],
    ['direct selection tool', 'white arrow', 'direct selection', 'anchor point tool', 'a'],
    ['type tool', 'text tool', 'text', 'type', 't'],
    ['pen tool', 'bezier pen', 'path tool', 'pen', 'p'],
    ['pencil tool', 'pencil', 'n'],
    ['rectangle tool', 'box tool', 'rectangle', 'rect', 'm'],
    ['ellipse tool', 'circle tool', 'oval tool', 'ellipse', 'l'],
    ['polygon tool', 'polygon'],
    ['line tool', 'line', 'backslash'],
    ['hand tool', 'pan tool', 'pan', 'hand', 'h', 'spacebar'],
    ['zoom tool', 'magnifier', 'magnifying glass', 'zoom', 'z'],
    ['eyedropper tool', 'eyedropper', 'color picker', 'i'],
    ['gradient tool', 'gradient', 'g'],
    ['scissors tool', 'scissors', 'c'],
    ['eraser tool', 'eraser', 'shift e'],
    ['rotate tool', 'rotate', 'r'],
    ['scale tool', 'scale', 's'],
    ['free transform tool', 'free transform', 'e'],
    ['undo', 'ctrl z', 'control z', 'cmd z', 'command z'],
    ['redo', 'ctrl y', 'control y', 'cmd y', 'command y', 'ctrl shift z', 'cmd shift z'],
    ['copy', 'ctrl c', 'control c', 'cmd c', 'command c'],
    ['paste', 'ctrl v', 'control v', 'cmd v', 'command v'],
    ['cut', 'ctrl x', 'control x', 'cmd x', 'command x'],
    ['save', 'ctrl s', 'control s', 'cmd s', 'command s'],
    ['save as', 'ctrl shift s', 'cmd shift s'],
    ['select all', 'ctrl a', 'control a', 'cmd a', 'command a'],
    ['deselect', 'ctrl shift a', 'cmd shift a', 'ctrl d', 'cmd d'],
    ['group', 'ctrl g', 'control g', 'cmd g', 'command g'],
    ['ungroup', 'ctrl shift g', 'cmd shift g'],
    ['print', 'ctrl p', 'control p', 'cmd p', 'command p'],
    ['new document', 'ctrl n', 'cmd n'],
    ['open document', 'ctrl o', 'cmd o'],
    ['zoom in', 'ctrl plus', 'cmd plus', 'plus', '+', 'ctrl +'],
    ['zoom out', 'ctrl minus', 'cmd minus', 'minus', '-', 'ctrl -'],
    ['stroke', 'outline', 'border', 'line weight'],
    ['fill', 'interior color', 'inside color', 'fill color', 'swatch'],
    ['cmyk', 'cyan magenta yellow black', 'cyan magenta yellow key'],
    ['rgb', 'red green blue'],
    ['dpi', 'dots per inch', 'ppi', 'pixels per inch'],
    ['jpeg', 'jpg'],
    ['png', 'portable network graphics'],
    ['pdf', 'portable document format'],
    ['gif', 'graphics interchange format'],
    ['transparency', 'opacity', 'transparent', 'see through', 'partly see through', 'semi transparent', 'translucent'],
    ['readability', 'legibility', 'readable', 'legible', 'reading ease'],
    ['consistency', 'uniformity', 'visual consistency', 'consistent', 'harmony'],
];

const STOP_WORDS = new Set([
    'a', 'an', 'the',
    'is', 'are', 'was', 'were', 'be', 'been', 'being',
    'to', 'of', 'in', 'for', 'on', 'with', 'at', 'by', 'from', 'up', 'about', 'into', 'over', 'after', 'under', 'above',
    'and', 'or', 'so', 'but', 'while', 'which', 'that', 'this', 'these', 'those',
    'it', 'its', 'they', 'them', 'their', 'we', 'us', 'our', 'you', 'your', 'he', 'him', 'his', 'she', 'her',
    'can', 'could', 'should', 'would', 'may', 'might', 'must', 'will', 'shall',
    'as', 'if', 'because', 'than', 'then', 'all', 'any', 'every', 'some', 'when', 'where', 'what', 'why', 'how',
    'i', 'my', 'me', 'think', 'answer',
]);

const EXCLUSIVE_PAIRS: [string, string][] = [
    ['stroke', 'fill'],
    ['selection', 'direct selection'],
    ['pen', 'pencil'],
    ['rectangle', 'ellipse'],
    ['rectangle', 'circle'],
    ['rectangle', 'polygon'],
    ['ellipse', 'polygon'],
    ['circle', 'polygon'],
    ['zoom in', 'zoom out'],
    ['undo', 'redo'],
    ['copy', 'paste'],
    ['cmyk', 'rgb'],
    ['vector', 'raster'],
    ['lossless', 'lossy'],
];

export function normalizeAnswer(text: string | null | undefined): string {
    if (!text) {
        return '';
    }

    const raw = text.trim();

    if (raw.length === 1 && !/^[a-z0-9]$/i.test(raw)) {
        return raw.toLowerCase();
    }

    let s = raw.toLowerCase();

    // Space out symbols so "Ctrl+Z" becomes "ctrl + z" and "Fill & Stroke" becomes "fill & stroke"
    s = s.replace(/([+&%#@=/*<>])/g, ' $1 ');

    // Strip trailing/leading punctuation
    s = s.replace(/[.,!?;:"'’[\]]/g, ' ');

    // Remove articles 'a', 'an', 'the'
    s = s.replace(/\b(a|an|the)\b/gi, ' ');

    // Collapse whitespace
    return s.replace(/\s+/g, ' ').trim();
}

function replaceSymbols(text: string): string {
    let s = text;

    for (const [sym, word] of Object.entries(SYMBOL_MAP)) {
        s = s.replaceAll(sym, ` ${word} `);
    }

    return s.replace(/\s+/g, ' ').trim();
}

function normalizeShortcuts(text: string): string {
    let s = text.toLowerCase().trim();

    s = s.replace(/\b(control|command|cmd)\b/g, 'ctrl');
    s = s.replace(/[+-]/g, ' ');

    return s.replace(/\s+/g, ' ').trim();
}

function areSynonyms(a: string, b: string): boolean {
    const normA = normalizeShortcuts(a);
    const normB = normalizeShortcuts(b);

    if (normA && normA === normB) {
        return true;
    }

    const stripA = stripFillers(a);
    const stripB = stripFillers(b);

    for (const group of SYNONYM_GROUPS) {
        const inA = group.includes(a) || group.includes(normA) || (stripA !== '' && group.includes(stripA));
        const inB = group.includes(b) || group.includes(normB) || (stripB !== '' && group.includes(stripB));

        if (inA && inB) {
            return true;
        }
    }

    return false;
}

function normalizeNumbers(text: string): string {
    const tokens = text.split(' ').map((tok) => NUMBER_MAP[tok] || tok);

    return tokens.join(' ');
}

function stripFillers(text: string): string {
    const tokens = text.split(' ').filter((tok) => !FILLERS.has(tok));

    return tokens.join(' ').trim();
}

function hasTerm(str: string, term: string): boolean {
    if (term === 'selection') {
        const withoutDirect = str.replace(/\bdirect\s+selection\b/gi, '');

        return /\bselection\b/i.test(withoutDirect);
    }

    const escaped = term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

    return new RegExp(`\\b${escaped}\\b`, 'i').test(str);
}

export function hasConflictingExclusivePair(a: string, b: string): boolean {
    for (const [x, y] of EXCLUSIVE_PAIRS) {
        const aHasX = hasTerm(a, x);
        const aHasY = hasTerm(a, y);
        const bHasX = hasTerm(b, x);
        const bHasY = hasTerm(b, y);

        if ((aHasX && !aHasY && bHasY && !bHasX) ||
            (aHasY && !aHasX && bHasX && !bHasY)) {
            return true;
        }
    }

    return false;
}

export function stripConversationalWrappers(str: string): string {
    let s = str.trim();

    if (!s) {
        return '';
    }

    const leadingPatterns = [
        /^(?:i\s+(?:think|believe)\s+(?:that\s+)?(?:it\s+is\s+)?)/i,
        /^(?:(?:the|my)\s+answer\s+is\s+)/i,
        /^(?:it\s+is\s+|it's\s+|its\s+)/i,
        /^(?:you\s+can\s+use\s+|you\s+should\s+use\s+|we\s+can\s+use\s+|we\s+should\s+use\s+|one\s+can\s+use\s+)/i,
        /^(?:by\s+using\s+|by\s+|using\s+|use\s+)/i,
        /^(?:it\s+should\s+have\s+|it\s+must\s+have\s+|it\s+has\s+)/i,
        /^(?:it\s+should\s+be\s+|it\s+must\s+be\s+|it\s+can\s+be\s+|should\s+be\s+)/i,
        /^(?:to\s+use\s+|to\s+make\s+|to\s+create\s+)/i,
        /^(?:the\s+|a\s+|an\s+)/i,
    ];

    let changed = true;

    while (changed) {
        changed = false;

        for (const pattern of leadingPatterns) {
            const replaced = s.replace(pattern, '').trim();

            if (replaced !== s) {
                s = replaced;
                changed = true;
            }
        }
    }

    const trailingPatterns = [
        /\s+(?:is\s+used|can\s+be\s+used|should\s+be\s+used|to\s+be\s+used)$/i,
        /\s+(?:is\s+the\s+answer|is\s+the\s+tool|is\s+needed)$/i,
        /\s+(?:tool\s+is\s+used|feature\s+is\s+used)$/i,
    ];

    for (const pattern of trailingPatterns) {
        s = s.replace(pattern, '').trim();
    }

    return s;
}

export function stemWord(w: string): string {
    const word = w.toLowerCase().trim();
    const len = word.length;

    if (len <= 3) {
        return word;
    }

    if (word.endsWith('ability') && len > 7) {
        return word.slice(0, -7);
    }

    if (word.endsWith('ibility') && len > 7) {
        return word.slice(0, -7);
    }

    if (word.endsWith('ency') && len > 5) {
        return word.slice(0, -4);
    }

    if (word.endsWith('ancy') && len > 5) {
        return word.slice(0, -4);
    }

    if (word.endsWith('ent') && len > 4) {
        return word.slice(0, -3);
    }

    if (word.endsWith('able') && len > 5) {
        return word.slice(0, -4);
    }

    if (word.endsWith('ible') && len > 5) {
        return word.slice(0, -4);
    }

    if (word.endsWith('tions') && len > 6) {
        return word.slice(0, -5);
    }

    if (word.endsWith('tion') && len > 5) {
        return word.slice(0, -4);
    }

    if (word.endsWith('ively') && len > 6) {
        return word.slice(0, -5);
    }

    if (word.endsWith('ive') && len > 5) {
        return word.slice(0, -3);
    }

    if (word.endsWith('ingly') && len > 6) {
        return word.slice(0, -5);
    }

    if (word.endsWith('ing') && len > 5) {
        let base = word.slice(0, -3);

        if (/([b-df-hj-np-tv-z])\1$/i.test(base)) {
            base = base.slice(0, -1);
        }

        return base;
    }

    if (word.endsWith('ed') && len > 4) {
        let base = word.slice(0, -2);

        if (/([b-df-hj-np-tv-z])\1$/i.test(base)) {
            base = base.slice(0, -1);
        }

        return base;
    }

    if (word.endsWith('ies') && len > 4) {
        return word.slice(0, -3) + 'y';
    }

    if (word.endsWith('sses') && len > 5) {
        return word.slice(0, -2);
    }

    if (word.endsWith('es') && len > 4) {
        return word.slice(0, -2);
    }

    if (word.endsWith('ly') && len > 4) {
        return word.slice(0, -2);
    }

    if (word.endsWith('s') && !word.endsWith('ss') && len > 3) {
        return word.slice(0, -1);
    }

    return word;
}

let cachedSynonymMap: [string, string][] | null = null;

function getSynonymMap(): [string, string][] {
    if (!cachedSynonymMap) {
        const map: [string, string][] = [];

        for (const group of SYNONYM_GROUPS) {
            const canonical = group[0];

            for (const syn of group) {
                if (syn !== canonical) {
                    map.push([syn, canonical]);
                }
            }
        }

        map.sort((a, b) => b[0].length - a[0].length);
        cachedSynonymMap = map;
    }

    return cachedSynonymMap;
}

export function getConceptRoots(str: string): string[] {
    let norm = normalizeAnswer(str);

    if (!norm) {
        return [];
    }

    norm = replaceSymbols(norm);

    const synonymMap = getSynonymMap();

    for (const [syn, canonical] of synonymMap) {
        if (syn.length > 1) {
            const escaped = syn.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

            norm = norm.replace(new RegExp(`\\b${escaped}\\b`, 'gi'), canonical);
        }
    }

    const words = norm.split(/\s+/).filter(Boolean);
    const roots: string[] = [];

    for (const w of words) {
        const clean = w.trim();

        if (!clean) {
            continue;
        }

        if (STOP_WORDS.has(clean)) {
            continue;
        }

        if (FILLERS.has(clean)) {
            continue;
        }

        if (clean.length <= 1 && !/^\d+$/.test(clean)) {
            continue;
        }

        const stemmed = stemWord(clean);

        if (stemmed && !roots.includes(stemmed)) {
            roots.push(stemmed);
        }
    }

    return roots;
}

export function levenshteinDistance(a: string, b: string): number {
    const m = a.length;
    const n = b.length;

    if (m === 0) {
        return n;
    }

    if (n === 0) {
        return m;
    }

    const row = Array.from({ length: n + 1 }, (_, i) => i);

    for (let i = 1; i <= m; i++) {
        let prev = i;

        for (let j = 1; j <= n; j++) {
            const cost = a[i - 1] === b[j - 1] ? 0 : 1;
            const val = Math.min(row[j] + 1, prev + 1, row[j - 1] + cost);

            row[j - 1] = prev;
            prev = val;
        }

        row[n] = prev;
    }

    return row[n];
}

/**
 * Determine if student answer is near or equivalent to expected answer.
 */
export function isNearMatch(
    expected: string | null | undefined,
    student: string | null | undefined,
): boolean {
    if (!expected || !student) {
        return false;
    }

    const normExp = normalizeAnswer(expected);
    const normStu = normalizeAnswer(student);

    if (!normExp || !normStu) {
        return false;
    }

    // 0. Conflicting exclusive term guard
    if (hasConflictingExclusivePair(normExp, normStu)) {
        return false;
    }

    // 1. Direct equality
    if (normExp === normStu) {
        return true;
    }

    // 1a. Conversational wrapper stripping equality ("use selection tool" == "selection tool")
    const cleanExp = stripConversationalWrappers(normExp);
    const cleanStu = stripConversationalWrappers(normStu);

    if (cleanExp !== '' && cleanExp === cleanStu) {
        return true;
    }

    // 1b. Symbol translation equivalence ("&" == "and", "+" == "plus")
    if (replaceSymbols(normExp) === replaceSymbols(normStu)) {
        return true;
    }

    if (cleanExp !== '' && replaceSymbols(cleanExp) === replaceSymbols(cleanStu)) {
        return true;
    }

    // 1c. Keyboard shortcut equivalence ("Ctrl+Z" == "Ctrl Z" == "Cmd Z")
    if (normalizeShortcuts(normExp) === normalizeShortcuts(normStu)) {
        return true;
    }

    if (cleanExp !== '' && normalizeShortcuts(cleanExp) === normalizeShortcuts(cleanStu)) {
        return true;
    }

    // 1d. Conceptual synonyms ("Selection Tool" == "Black Arrow" == "V")
    if (areSynonyms(normExp, normStu) || (cleanExp !== '' && areSynonyms(cleanExp, cleanStu))) {
        return true;
    }

    // 2. Boolean check
    if (['true', 't', 'false', 'f'].includes(normExp)) {
        const bExp = normExp.startsWith('t');
        const bStu = normStu.startsWith('t');

        if (normStu.startsWith('t') || normStu.startsWith('f')) {
            return bExp === bStu;
        }
    }

    // 3. Numbers check
    const numExp = normalizeNumbers(normExp);
    const numStu = normalizeNumbers(normStu);

    if (numExp === numStu) {
        return true;
    }

    // 4. Strip fillers ("tool", "feature")
    const stripExp = stripFillers(cleanExp !== '' ? cleanExp : normExp);
    const stripStu = stripFillers(cleanStu !== '' ? cleanStu : normStu);

    if (stripExp !== '' && stripExp === stripStu) {
        return true;
    }

    // 5. Concept roots & multiset comparison
    const expRoots = getConceptRoots(cleanExp !== '' ? cleanExp : normExp);
    const stuRoots = getConceptRoots(cleanStu !== '' ? cleanStu : normStu);

    if (expRoots.length > 0 && stuRoots.length > 0) {
        const sortedExp = [...expRoots].sort();
        const sortedStu = [...stuRoots].sort();

        // A. Exact root multiset equality ("consistency and readability" == "readability and consistency")
        if (sortedExp.length === sortedStu.length && sortedExp.every((val, idx) => val === sortedStu[idx])) {
            return true;
        }

        // B. Student contains ALL expected concepts
        const diff = expRoots.filter((r) => !stuRoots.includes(r));

        if (diff.length === 0) {
            return true;
        }

        // C. Multi-concept phrase overlap
        const common = expRoots.filter((r) => stuRoots.includes(r));
        const commonCount = common.length;
        const expCount = expRoots.length;
        const stuCount = stuRoots.length;

        if (expCount >= 2) {
            if (commonCount >= 2 && commonCount / expCount >= 0.50) {
                return true;
            }

            if (commonCount / Math.max(expCount, stuCount) >= 0.50) {
                return true;
            }

            if (stuCount >= 2 && commonCount / stuCount >= 0.66 && commonCount >= 2) {
                return true;
            }
        } else if (expCount === 1) {
            if (commonCount === 1 && stuCount <= 4) {
                return true;
            }
        }
    }

    // 6. Substring / whole phrase containment
    const targetExp = stripExp !== '' ? stripExp : (cleanExp !== '' ? cleanExp : normExp);
    const targetStu = stripStu !== '' ? stripStu : (cleanStu !== '' ? cleanStu : normStu);

    if (targetExp.length >= 3) {
        if (targetStu === targetExp) {
            return true;
        }

        const reExp = new RegExp(`\\b${targetExp.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'i');
        const reStu = new RegExp(`\\b${targetStu.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'i');

        if (reExp.test(targetStu) || (targetStu.length >= 4 && reStu.test(targetExp))) {
            return true;
        }
    }

    // 7. Token set / overlap
    const wordsExp = targetExp.split(' ').filter((w) => w.length >= 2);
    const wordsStu = targetStu.split(' ').filter((w) => w.length >= 2);

    if (wordsExp.length > 0 && wordsStu.length > 0) {
        const setStu = new Set(wordsStu);
        const inter = wordsExp.filter((w) => setStu.has(w));

        if (inter.length === wordsExp.length) {
            return true;
        }

        const jaccard = inter.length / Math.max(wordsExp.length, wordsStu.length);

        if (jaccard >= 0.50) {
            return true;
        }
    }

    // 8. Levenshtein edit distance
    const maxLen = Math.max(targetExp.length, targetStu.length);

    if (maxLen >= 4) {
        const lev = levenshteinDistance(targetExp, targetStu);
        const maxDist = maxLen >= 20 ? 4 : (maxLen >= 8 ? 2 : 1);

        if (lev <= maxDist) {
            return true;
        }
    }

    return false;
}
