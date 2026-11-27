<?php

return [

    /*
    |--------------------------------------------------------------------------
    | XP Tiers (PointsHelper::calculate)
    |--------------------------------------------------------------------------
    | Minimum percentage thresholds for each tier.
    */
    'xp_tiers' => [
        ['min_pct' => 80, 'points' => 10],
        ['min_pct' => 60, 'points' => 5],
        ['min_pct' => 1,  'points' => 1],
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Points
    |--------------------------------------------------------------------------
    */
    'login_bonus'          => 5,
    'correct_answer_xp'   => 1,
    'message_milestone'   => 1000,
    'message_milestone_xp' => 10,
    'message_min_chars'   => 10,

    /*
    |--------------------------------------------------------------------------
    | XP Reward Limits
    |--------------------------------------------------------------------------
    */
    'xp_reward_max'       => 100,
    'manual_xp_max'       => 1000,
    'manual_xp_min'       => -1000,

    /*
    |--------------------------------------------------------------------------
    | Activity Types (used with PointsHelper::award)
    |--------------------------------------------------------------------------
    */
    'activity_types' => [
        'login'    => 'Login',
        'quiz'     => 'Quiz',
        'seatwork' => 'Seatwork',
        'practical' => 'Practical',
        'exam'     => 'Exam',
        'game'     => 'Game',
        'message'  => 'Message',
        'manual'   => 'Manual',
        'deduction' => 'Deduction',
        'reward'   => 'Reward',
    ],

    /*
    |--------------------------------------------------------------------------
    | Game Types
    |--------------------------------------------------------------------------
    */
    'game_types' => [
        'flashcard', 'quiz', 'fillblank', 'truefalse', 'wordjumble',
        'colorharmony', 'memorymatch', 'hangman', 'speedquiz',
        'dragdrop', 'ordering',
    ],

    /*
    |--------------------------------------------------------------------------
    | Game Defaults
    |--------------------------------------------------------------------------
    */
    'hangman_max_mistakes' => 6,
    'speed_quiz_seconds'   => 15,

    /*
    |--------------------------------------------------------------------------
    | Practical Defaults
    |--------------------------------------------------------------------------
    */
    'practical_max_score'   => 100,
    'practical_xp_reward'  => 10,
    'practical_max_attempts' => 3,

    /*
    |--------------------------------------------------------------------------
    | Exam Defaults
    |--------------------------------------------------------------------------
    */
    'exam_max_score' => 100,

    /*
    |--------------------------------------------------------------------------
    | Attempt Closing Duration
    |--------------------------------------------------------------------------
    | How long after starting an attempt before it auto-closes.
    */
    'attempt_close_after_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Notification Limits
    |--------------------------------------------------------------------------
    */
    'notification_limit_teacher' => 30,
    'notification_limit_student_practical' => 20,
    'notification_limit_student_other' => 5,

    /*
    |--------------------------------------------------------------------------
    | Dashboard Activity Limits
    |--------------------------------------------------------------------------
    */
    'dashboard_recent_student' => 5,
    'dashboard_recent_teacher' => 10,

    /*
    |--------------------------------------------------------------------------
    | Messenger Setting Key
    |--------------------------------------------------------------------------
    */
    'messenger_setting_key' => 'messenger_system_enabled',

    /*
    |--------------------------------------------------------------------------
    | Level System
    |--------------------------------------------------------------------------
    */
    'xp_per_level' => 100,

    /*
    |--------------------------------------------------------------------------
    | Level Titles (indexed by level, starting at 1)
    |--------------------------------------------------------------------------
    */
    'level_titles' => [
        'Novice', 'Beginner', 'Explorer', 'Scholar', 'Achiever',
        'Rising Star', 'Skilled', 'Expert', 'Master',
        'Grandmaster', 'Legend', 'Mythic', 'Immortal',
    ],
    'level_title_fallback' => 'Champion',

    /*
    |--------------------------------------------------------------------------
    | Secret Level Rewards (indexed by level)
    |--------------------------------------------------------------------------
    | Every reward has a `type` that RewardService knows how to apply:
    |   booster        multiplier, [chance, fallback_multiplier] — next graded activity
    |   badge          badge_key, xp_bonus_pct — passive XP bonus while equipped
    |   homework_pass  complete one quiz / seatwork / task with full marks
    |   shield         protect — wrong answers forgiven on next quiz
    |   time_warp      seconds — extra time on a timed quiz
    |   compass        eliminate — wrong choices removed on one question
    |   xp_grant       xp — instant XP
    |   streak_freeze  count — streak freeze shields added
    */
    'level_rewards' => [
        1  => ['name' => 'XP Booster Token',     'type' => 'booster',       'multiplier' => 2,   'description' => 'Earn 2× XP on your next graded activity. Use it wisely.'],
        2  => ['name' => 'Golden Pencil',         'type' => 'badge',         'badge_key' => 'golden_pencil',      'xp_bonus_pct' => 5,  'description' => 'A legendary writing instrument. Equip it for +5% XP on every graded activity.'],
        3  => ['name' => 'Homework Pass',         'type' => 'homework_pass', 'description' => 'Skip one quiz, seatwork, or task and get full marks.'],
        4  => ['name' => 'Brain Power Shield',    'type' => 'shield',        'protect' => 1,      'description' => 'Arm it to forgive one wrong answer on your next quiz.'],
        5  => ['name' => 'Time Warp Hourglass',   'type' => 'time_warp',     'seconds' => 30,     'description' => 'Add 30 extra seconds to a timed quiz.'],
        6  => ['name' => 'Star Compass',          'type' => 'compass',       'eliminate' => 2,    'description' => 'Points you toward the truth: removes 2 wrong choices on one quiz question.'],
        7  => ['name' => "Scholar's Scroll",      'type' => 'xp_grant',      'xp' => 50,          'description' => 'Unroll it to absorb 50 bonus XP of hidden wisdom.'],
        8  => ['name' => 'Focus Potion',          'type' => 'booster',       'multiplier' => 1.5, 'description' => 'Sharpens your mind: 1.5× XP on your next graded activity.'],
        9  => ['name' => 'Quiz Snapshot',         'type' => 'time_warp',     'seconds' => 60,     'description' => 'Freeze the clock: add 60 extra seconds to a timed quiz.'],
        10 => ['name' => 'Double Down Die',       'type' => 'booster',       'multiplier' => 3,   'chance' => 50, 'fallback_multiplier' => 1.5, 'description' => 'Roll it on your next graded activity: 50% chance of 3× XP, otherwise 1.5×.'],
        11 => ['name' => "Master's Seal",         'type' => 'badge',         'badge_key' => 'masters_seal',       'xp_bonus_pct' => 10, 'description' => 'A mark of true mastery. Equip it for +10% XP on every graded activity.'],
        12 => ['name' => 'Crown of Knowledge',    'type' => 'badge',         'badge_key' => 'crown_of_knowledge', 'xp_bonus_pct' => 15, 'description' => 'Wear it and the whole school will know your power. +15% XP while equipped.'],
        13 => ['name' => 'Immortal Flame',        'type' => 'badge',         'badge_key' => 'immortal_flame',     'xp_bonus_pct' => 20, 'description' => 'The rarest flame. Equip it for +20% XP on every graded activity.'],
        14 => ['name' => 'Midnight Lantern',      'type' => 'time_warp',     'seconds' => 90,     'description' => 'Lights the way through a tough quiz: add 90 extra seconds.'],
        15 => ['name' => 'Silver Quill',          'type' => 'xp_grant',      'xp' => 75,          'description' => 'Every stroke carries wisdom. Claim 75 bonus XP.'],
        16 => ['name' => 'Echo Shell',            'type' => 'shield',        'protect' => 1,      'description' => 'Hear the right answer echo back: forgives one wrong answer on your next quiz.'],
        17 => ['name' => 'Puzzle Key',            'type' => 'homework_pass', 'description' => 'Unlocks any one quiz, seatwork, or task with full marks.'],
        18 => ['name' => 'Owl Feather',           'type' => 'streak_freeze', 'count' => 1,        'description' => 'Calm and wise: adds 1 streak freeze to protect your daily streak.'],
        19 => ['name' => 'Thunder Chalk',         'type' => 'booster',       'multiplier' => 2,   'description' => 'Strike boldly: 2× XP on your next graded activity.'],
        20 => ['name' => 'Emerald Bookmark',      'type' => 'badge',         'badge_key' => 'emerald_bookmark',   'xp_bonus_pct' => 8,  'description' => 'Twenty levels conquered! Equip it for +8% XP on every graded activity.'],
        21 => ['name' => 'Comet Charm',           'type' => 'booster',       'multiplier' => 1.5, 'description' => 'Streak across your next challenge: 1.5× XP on your next graded activity.'],
        22 => ['name' => 'Library Pass',          'type' => 'homework_pass', 'description' => 'Check out one quiz, seatwork, or task as completed with full marks.'],
        23 => ['name' => 'Mirror of Insight',     'type' => 'compass',       'eliminate' => 2,    'description' => 'Reflect the truth: removes 2 wrong choices on one quiz question.'],
        24 => ['name' => 'Golden Ruler',          'type' => 'xp_grant',      'xp' => 100,         'description' => 'Measures progress in miles. Claim 100 bonus XP.'],
        25 => ['name' => 'Phoenix Feather',       'type' => 'streak_freeze', 'count' => 2,        'description' => 'Rise from any setback: adds 2 streak freezes. A quarter of the way to fifty!'],
        26 => ['name' => 'Starlight Ink',         'type' => 'booster',       'multiplier' => 2,   'description' => 'Your work glows brighter: 2× XP on your next graded activity.'],
        27 => ['name' => 'Gear of Genius',        'type' => 'shield',        'protect' => 2,      'description' => 'Keeps your brain running: forgives up to 2 wrong answers on your next quiz.'],
        28 => ['name' => 'Atlas Fragment',        'type' => 'xp_grant',      'xp' => 120,         'description' => 'A piece of the map of knowledge. Claim 120 bonus XP.'],
        29 => ['name' => 'Crystal Calculator',    'type' => 'compass',       'eliminate' => 2,    'description' => 'Wrong answers crack under pressure: removes 2 wrong choices on one question.'],
        30 => ['name' => 'Sapphire Medal',        'type' => 'badge',         'badge_key' => 'sapphire_medal',     'xp_bonus_pct' => 12, 'description' => 'Thirty levels deep. Equip it for +12% XP on every graded activity.'],
        31 => ['name' => 'Wind Runner Boots',     'type' => 'homework_pass', 'description' => 'Breeze past one quiz, seatwork, or task with full marks.'],
        32 => ['name' => 'Rune of Recall',        'type' => 'time_warp',     'seconds' => 90,     'description' => 'Remember everything in time: add 90 extra seconds to a timed quiz.'],
        33 => ['name' => 'Aurora Cloak',          'type' => 'booster',       'multiplier' => 2.5, 'description' => 'Shine bright: 2.5× XP on your next graded activity.'],
        34 => ['name' => 'Titan Backpack',        'type' => 'xp_grant',      'xp' => 150,         'description' => 'Carries a thousand lessons. Claim 150 bonus XP.'],
        35 => ['name' => 'Dragon Scale',          'type' => 'shield',        'protect' => 2,      'description' => 'Tough as armor: forgives up to 2 wrong answers on your next quiz.'],
        36 => ['name' => 'Celestial Globe',       'type' => 'compass',       'eliminate' => 2,    'description' => 'Spin it to reveal the way: removes 2 wrong choices on one quiz question.'],
        37 => ['name' => 'Oracle Stone',          'type' => 'booster',       'multiplier' => 3,   'chance' => 50, 'fallback_multiplier' => 2, 'description' => 'Consult the oracle on your next graded activity: 50% chance of 3× XP, otherwise 2×.'],
        38 => ['name' => 'Ruby Hourglass',        'type' => 'time_warp',     'seconds' => 120,    'description' => 'Time slows down: add 2 extra minutes to a timed quiz.'],
        39 => ['name' => 'Sage Spectacles',       'type' => 'homework_pass', 'description' => 'See straight through one quiz, seatwork, or task: full marks.'],
        40 => ['name' => 'Platinum Trophy',       'type' => 'badge',         'badge_key' => 'platinum_trophy',    'xp_bonus_pct' => 15, 'description' => 'Forty levels! Equip it for +15% XP on every graded activity.'],
        41 => ['name' => 'Nebula Notebook',       'type' => 'booster',       'multiplier' => 2,   'description' => 'Infinite ideas: 2× XP on your next graded activity.'],
        42 => ['name' => 'Answer to Everything',  'type' => 'xp_grant',      'xp' => 42,          'description' => 'The universe has spoken. Claim exactly 42 bonus XP.'],
        43 => ['name' => 'Griffin Wing',          'type' => 'shield',        'protect' => 3,      'description' => 'Soar over mistakes: forgives up to 3 wrong answers on your next quiz.'],
        44 => ['name' => 'Chronicle Tome',        'type' => 'xp_grant',      'xp' => 200,         'description' => 'Every achievement written inside. Claim 200 bonus XP.'],
        45 => ['name' => 'Diamond Mind',          'type' => 'booster',       'multiplier' => 3,   'description' => 'Unbreakable focus: 3× XP on your next graded activity.'],
        46 => ['name' => 'Eclipse Pendant',       'type' => 'compass',       'eliminate' => 3,    'description' => 'Darkness falls on wrong answers: removes up to 3 wrong choices on one question.'],
        47 => ['name' => 'Scepter of Scholars',   'type' => 'homework_pass', 'description' => 'Command one quiz, seatwork, or task to be completed with full marks.'],
        48 => ['name' => 'Galaxy Map',            'type' => 'streak_freeze', 'count' => 3,        'description' => 'Charts a safe course: adds 3 streak freezes.'],
        49 => ['name' => 'Eternal Torch',         'type' => 'xp_grant',      'xp' => 250,         'description' => 'The light of learning burns on. Claim 250 bonus XP.'],
        50 => ['name' => 'Legendary Laurel',      'type' => 'badge',         'badge_key' => 'legendary_laurel',   'xp_bonus_pct' => 25, 'description' => 'The summit of EduPulse. Equip it for +25% XP on every graded activity.'],
    ],
];
