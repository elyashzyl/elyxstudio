# EduPulse Autonomous AI System Manager — Architecture & Implementation Plan

## 1. Concept & Feasibility

**Yes, completely possible.**
You can add a centralized **AI System Manager ("EduPulse Copilot / Autonomous Supervisor")** that you toggle ON or OFF from the Superadmin / System Settings dashboard.

When active, it runs as a scheduled background daemon or on-demand trigger that inspects pending work, executes routine tasks within strict guardrails, and surfaces an audit log for human review.

---

## 2. Operating Modes

| Mode | Behavior |
| :--- | :--- |
| **OFF** | No autonomous operations run. All tasks remain purely manual. |
| **Advisory / Watchdog** | AI inspects system state, detects issues, and posts action alerts to Superadmin without altering database state. |
| **Approval-Required** | AI generates action proposals (e.g. "Grade 14 pending essays", "Self-heal 3 missing material files"); Superadmin clicks **Apply All** or **Dismiss**. |
| **Autonomous (Full Guardrails)** | AI automatically performs whitelisted routines on schedule (e.g. every 10–30 minutes) and writes every change to an immutable audit log. |

---

## 3. Strict Safety Guardrails & Role Boundaries

### Allowed Actions (Whitelisted)
- **Auto-Grading Submissions**:
  - Evaluate pending ungraded identification, essay, and practical attempts using configured AI provider with confidence scoring.
  - Automatically approve submissions only if AI confidence >= 85%; otherwise mark `Needs Teacher Review`.
- **Self-Healing Storage**:
  - Scan `teaching_materials` and message attachments for missing disk files; restore them from database base64 storage.
- **System Health & Anomaly Audits**:
  - Detect orphan records (e.g. attempts with deleted users).
  - Flag suspiciously fast student submissions (< 10 seconds with high score).
  - Test upstream AI provider connectivity (Gemini / OmniRoute / 9Router) and warn admin on high latency.
- **Scheduled Housekeeping**:
  - Trigger database safety snapshots before batch tasks.
  - Clean temporary export artifacts older than 48 hours.
  - Mark abandoned open quiz sessions past their time limit as auto-submitted.

### Strict Forbidden Actions (Hard-Coded Blacklist)
- **NO DELETIONS**: The AI Manager can **NEVER** execute `DELETE` queries on users, classes, grades, materials, quizzes, or messages.
- **NO DATABASE DESTRUCTION**: Absolute ban on `migrate:fresh`, `db:wipe`, or schema drops.
- **NO CREDENTIAL / PERMISSION ALTERATION**: Cannot change user passwords, emails, roles, or impersonation links.
- **NO FINANCIAL / CONTACT MODIFICATIONS**: Protected configuration keys (e.g. `ely.ashzyl@gmail.com`, payment channels) are read-only.
- **NO UNAPPROVED CONTENT PUBLISHING**: Draft activities created by teachers cannot be published to students without explicit teacher action.

---

## 4. Architecture & Database Design

### New Database Table: `ai_manager_logs`
Tracks every inspection and action taken by the AI:
- `id`: Primary key
- `event_type`: `inspection`, `auto_grade`, `storage_heal`, `anomaly_flag`, `backup_trigger`
- `status`: `success`, `skipped`, `flagged_for_review`, `failed`
- `target_type`: Model class (e.g. `App\Models\QuizAttempt`)
- `target_id`: ID of the affected record
- `confidence`: Decimal (0.00 – 1.00)
- `summary`: Short caveman-clear text summary of what was inspected or changed
- `payload`: JSON details (diff of scores, criteria applied, or diagnosis)
- `reverted_at`: Nullable timestamp (allows Superadmin 1-click undo)
- `created_at`, `updated_at`

### Settings Keys (`settings` table)
- `ai_manager_enabled`: `true` / `false` (master toggle)
- `ai_manager_mode`: `advisory` / `approval` / `autonomous`
- `ai_manager_interval_minutes`: Default `15`
- `ai_manager_auto_grade`: `true` / `false`
- `ai_manager_self_heal_files`: `true` / `false`
- `ai_manager_anomaly_detection`: `true` / `false`
- `ai_manager_confidence_threshold`: Default `0.85`

---

## 5. Implementation To-Do Checklist

### Phase 1: Database & Configuration
- [x] Create migration for `ai_manager_logs` table with rollback support.
- [x] Add AI Manager configuration defaults in `config/ai.php` and `AiConfigService.php`.
- [x] Add Superadmin control UI in `resources/js/pages/Teacher/Settings.vue`:
  - Master switch (ON/OFF)
  - Mode selector (Advisory / Approval / Autonomous)
  - Granular task toggles (Auto-Grade, Storage Healer, Anomaly Watchdog)
  - Confidence threshold slider (70% - 95%)

### Phase 2: Core Manager Service & Task Pipeline
- [x] Create `app/Services/AiManager/AiSystemManagerService.php`:
  - `runInspection()`: Master execution loop checking enabled sub-tasks.
  - Task 1: `AutoGraderTask.php` (processes pending quiz, seatwork, practical attempts).
  - Task 2: `StorageHealerTask.php` (detects missing disk files and heals from DB).
  - Task 3: `AnomalyDetectorTask.php` (flags zero-time submissions or stalled sessions).
  - Task 4: `HealthCheckTask.php` (checks DB lock, AI provider latency, disk space).
- [x] Create strict guardrail validator `app/Services/AiManager/GuardrailPolicy.php`:
  - Hard checks preventing deletion of any core tables.
  - Enforce confidence floor before writing score updates.

### Phase 3: Background Scheduler & Artisan Commands
- [x] Create artisan command: `php artisan edupulse:ai-manager-tick`.
- [x] Register schedule in `routes/console.php` with `withoutOverlapping()`.
- [x] Add manual "Run Inspection Now" button in System Settings.

### Phase 4: Superadmin Dashboard & Audit Log UI
- [x] Create `resources/js/pages/Teacher/AiManagerLogs.vue`:
  - Table of recent actions with status chips and confidence indicators.
  - "1-Click Revert" button for any auto-graded attempt.
  - Quick summary cards: Actions today, Submissions graded, Anomalies flagged.
- [x] Add status summary metrics and quick trigger button on Teacher/Admin navigation.

### Phase 5: Verification & Safety Testing
- [x] Feature tests verifying:
  - Toggle OFF halts all autonomous runs.
  - Guardrail prevents write actions on confidence < threshold.
  - Guardrail strictly blocks prohibited models/actions.
  - 1-click rollback restores previous score state cleanly.
  - File self-healing restores missing material without data loss.
