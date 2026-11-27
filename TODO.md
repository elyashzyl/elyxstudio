# EduPulse To-Do & Roadmap

## Completed Milestones

### Messenger & Communication
- [x] **Unread Indicator Badges**: Display unread count badge, bold text, tinted background, and left accent border on conversation items in sidebar.
- [x] **Unread Message Separator**: Visual divider with pulse badge positioned above the first incoming unread message.
- [x] **Dynamic Unread Computation**: Calculate unread state using message `created_at` against participant pivot `last_read_at`.
- [x] **Real-Time Polling & Instant Clearing**: Background interval polling every 3s for conversations, optimistic read-state clearing when opening chat.
- [x] **Classmate Messaging Policy**: Teacher and superadmin toggles to restrict student messaging exclusively to teachers.
- [x] **Group Chat Support**: Multi-user group chat creation with teacher inclusion and classmate permission checks.
- [x] **Messenger File & Media Attachments**: Support sending image, PDF, and document attachments with preview chips, inline thumbnails, file cards, and secure downloads with base64 persistence fallback.
- [x] **Messenger Audio & Voice Notes**: In-browser audio recording via MediaRecorder with recording toolbar, waveform indicator, audio format fallback, custom inline `VoicePlayer` with scrub bar and speed toggles, inline audio streaming, and database base64 persistence.

### Gradebook & Academic Reporting
- [x] **Gradebook CSV Score Export**: Stream filtered student assessment scores (quizzes, seatworks, exams, practicals) to CSV with Excel UTF-8 BOM compatibility across entire classes or individual students.

### Automation & System Maintenance
- [x] **Automated Backup Scheduling**: Daily database backup (02:00) and weekly full system backup (Sun 03:00) scheduled in `routes/console.php` with overlap prevention.
- [x] **Autonomous AI System Manager**: Centralized autonomous supervisor daemon with master toggle (ON/OFF), configurable operating modes (advisory/approval/autonomous), strict deletion guardrails, background auto-grading queue, storage self-healing from database base64 backups, anomaly watchdog, scheduled artisan ticks, audit log dashboard, and 1-click rollback support.

### Routing, Settings & Authentication
- [x] **Settings Routes**: Define `profile.edit` and `security.edit` routes in `routes/settings.php` under auth middleware.
- [x] **Password Confirmation Protection**: Enforce password confirmation redirect on `security.edit` when two-factor authentication or passkey confirmation is active.
- [x] **Profile Redirect Resolution**: Return users to `/teacher/profile` or `/student/profile` upon updating settings.
- [x] **Settings Vue Fallbacks**: Add `resources/js/pages/settings/Profile.vue` and `resources/js/pages/settings/Security.vue` Inertia components to eliminate 404 errors.
- [x] **Registration Decoupling**: Remove fragile dependency on `@/routes/register` in `Register.vue` and replace with standard form submission attributes.

### Build & Deployment Pipelines
- [x] **Docker Build Stabilization**: Eliminate brittle `test -f resources/js/routes/register/index.ts` check from `Dockerfile`.
- [x] **Asset Compilation**: Resolve Wayfinder and Vite build discrepancies, verify clean `npm run build` compilation.
- [x] **Automated Test Suite**: Pass all 94 Pest feature and unit tests with zero failures.

### Materials & Storage Persistence
- [x] **External Storage Adapter (AWS S3 & Cloudflare R2)**: Multi-tier dynamic object storage with runtime driver switching (`local`, `r2`, `s3`), probe connection testing, automated local-to-cloud upload sync, missing file self-healing pull, deletion sync, signed direct streaming, and Superadmin settings control UI.
- [x] **Teaching Material Editing**: Implement edit modal for title, subject, grade level, and file replacements in `MaterialController` and `Materials.vue`.
- [x] **Database File Persistence**: Store base64 file payloads directly in `teaching_materials` table to prevent missing files across redeployments.
- [x] **Automatic Migrations**: Safe, non-destructive migration runner on application boot with concurrency locking (no `migrate:fresh`).
- [x] **Admin Material Assignment**: Allow superadmins to assign materials directly to specific teachers.

### Activities, Impersonation & Real-Time Sync
- [x] **Instant Impersonation**: Inertia 409 location responses for instant teacher/student impersonation without page reload.
- [x] **Activity Deletion & Creation**: Real-time optimistic deletion and index redirects for quizzes, games, seatworks, and practicals.
- [x] **Grade Level Validation**: Support both singular and array grade level parameter formats during activity creation.

### Universal Multi-School Adaptation & Dynamic Configuration
- [x] **School Profile Removal**: Removed institution-specific school profile tab and hardcoded school codes (`Baguio Patriotic High School`, `BPHS`, `406219`) to make EduPulse universally agnostic for any institution.
- [x] **Dynamic Contact & Payment Emails**: Stored `support_email` and `payment_email` in database `settings` table with `ely.ashzyl@gmail.com` defaults; dynamically bound in Inertia sharing, `Welcome.vue`, `PrivacyPolicy.vue`, and `TermsOfService.vue`.
- [x] **Database-Driven Gamification XP**: Migrated `PointsHelper::calculate` from hardcoded config array to dynamic database `Setting` thresholds (`xp_mastery_percentage`, `xp_passing_percentage`, `xp_award_mastery`, `xp_award_pass`, `xp_award_attempt`).
- [x] **Activity & Material Restrictions**: Hardened grade-level authorization checks across `PracticalController`, `ExamController`, `SeatworkController`, and `MaterialController`; enabled superadmin-created activity visibility for assigned student grade levels.
- [x] **Duplicate User Account Merging**: Implemented transactional user account consolidation in `UserMergeService`, `UserController`, `UserMergeModal.vue`, `Users.vue`, and `Students.vue` with duplicate assessment attempt resolution, non-destructive XP/points deduplication, messenger history transfer, passkey reassignment, and source cleanup tested in `UserMergeTest.php`.

---

## Pending / Future Tasks

### High Priority
- [x] **Production Migration Lock Test**: Confirmed `AutoMigrationService` handles existing production tables, missing repository, connection failure, and concurrency lock contention without caching failed states.
- [x] **Docker Production Deployment Verification**: Container build scripts, supervisord supervisor configs, persistent volume mounts (`edupulse_storage` and `edupulse_database`), backup directory initialization, and entrypoint auto-healing verified.

### Medium Priority
- [x] **External Storage Adapter (S3 / Cloudflare R2)**:
  - Add support for S3/R2 storage driver alongside existing database self-healing file backup.
  - Implement streaming preview for large PDF and presentation files.
- [x] **WebSockets Integration (Laravel Reverb)**:
  - Replace 1.5s/3s HTTP polling in Messenger with Laravel Reverb or Pusher WebSockets for real-time delivery.
  - Implement active typing indicators (`User is typing...`) and online presence indicators.

### Low Priority / Optimizations
- [x] **Gradebook & Exporting Enhancements**:
  - Add PDF gradebook report card generation with school branding.
  - Add student performance visual analytics graphs in Teacher Dashboard.
- [x] **Backup Remote Cloud Sync**:
  - Add option to sync backup archives to remote S3 or Cloudflare R2 bucket.
- [x] **Dependency & Vulnerability Audit**:
  - Run `composer audit` and `npm audit --omit=dev` in CI pipeline.
  - Tune Vite vendor chunk splitting for optimal cache utilization.
