# EduPulse Mobile App Plan

## 1. Recommended approach

Build the first mobile app with **Capacitor + Vue**, reusing the existing EduPulse frontend and Laravel backend.

This is the lowest-risk path because EduPulse already uses:

- Laravel 13
- Vue 3
- Inertia.js
- Vite
- Fortify authentication
- Laravel Reverb and Echo for realtime features
- Existing teacher and student dashboards
- Existing quizzes, seatwork, practical tasks, materials, games, messaging, and live arena features

### Why Capacitor first?

- Reuses much of the existing Vue UI and game logic.
- Produces Android and iOS applications from the same codebase.
- Allows native features such as push notifications, camera, file picker, status bar, and app lifecycle handling.
- Avoids rewriting the entire application in Flutter, React Native, or Swift/Kotlin before the product requirements are stable.
- Keeps the current website available while the mobile app is developed.

### Important limitation

The current application is primarily an Inertia web application. A mobile app should not depend entirely on browser navigation and server-rendered Inertia responses. The long-term mobile architecture should expose authenticated JSON endpoints for mobile screens and use the existing web routes only for the website.

The migration should therefore be incremental:

1. Make the existing web app mobile-friendly.
2. Add a PWA baseline.
3. Add mobile-specific JSON/API endpoints.
4. Wrap the Vue mobile experience with Capacitor.
5. Add native features and publish the applications.

---

## 2. Target architecture

```text
                         +----------------------+
                         |   Laravel Backend    |
                         |                      |
                         | Auth, roles, games,  |
                         | quizzes, files,      |
                         | Reverb, notifications|
                         +----------+-----------+
                                    |
                 +------------------+------------------+
                 |                                     |
        +--------v---------+                  +--------v---------+
        | EduPulse Website |                  | EduPulse Mobile  |
        | Inertia + Vue    |                  | Vue + Capacitor  |
        +------------------+                  +--------+---------+
                                                        |
                                     +------------------+------------------+
                                     |                  |                   |
                              Push notifications   Camera/files       App lifecycle
```

### Suggested code organization

Keep the existing web pages, but extract reusable business UI where possible:

```text
resources/js/
├── components/
├── composables/
│   ├── useAuth.ts
│   ├── useGames.ts
│   ├── useQuizzes.ts
│   ├── useOfflineQueue.ts
│   └── usePushNotifications.ts
├── pages/
│   ├── Teacher/
│   ├── Student/
│   └── Mobile/
├── services/
│   ├── api.ts
│   ├── auth.ts
│   ├── games.ts
│   ├── quizzes.ts
│   └── uploads.ts
└── types/
```

Do not duplicate game rules, grading rules, XP calculations, or permission checks in the mobile app. The server must remain authoritative.

---

## 3. Phase 0: define the first release

Before coding, decide what the first mobile release must support.

### Recommended MVP

#### Student

- Login, logout, and password reset.
- Student dashboard.
- View subjects and assigned materials.
- View and submit homework/seatwork.
- Take quizzes.
- View quiz results and grades.
- Play games, including Crossword, Word Search, and Memory Match.
- View XP, badges, and leaderboard.
- Receive assignment, result, and announcement notifications.
- Upload files from the phone.

#### Teacher

- Login.
- Teacher dashboard.
- View students and subjects.
- Create or edit quizzes and activities.
- Review and grade submissions.
- Create and manage games.
- View results.
- Send announcements.

### Defer from MVP unless required

- Full admin management.
- Backup administration.
- Advanced AI creator workflows.
- PDF exports and large report generation.
- Full live arena host controls.
- Complex drag-and-drop editors.
- Offline quiz submission with high-stakes timed exams.

The MVP should focus on reliable student activity completion and teacher review rather than replicating every desktop feature immediately.

---

## 4. Phase 1: audit the existing application

Create an inventory before introducing mobile-specific code.

### Audit routes and permissions

The current app has role-protected web route groups for teachers and students. Document each feature with:

- Route name.
- HTTP method.
- Required role.
- Required verification state.
- Request fields.
- Response data.
- File upload behavior.
- Error states.
- Realtime behavior.

Important feature groups to audit:

- Authentication and profile.
- Subjects.
- Materials.
- Homework and seatwork.
- Quizzes and results.
- Practical tasks.
- Games.
- Live arena/game rooms.
- Messaging.
- Announcements.
- Notifications.
- Leaderboards and XP.

### Audit frontend assumptions

Search the Vue code for:

- Direct `window` and `document` usage.
- `requestFullscreen()` usage.
- Browser-only drag-and-drop.
- File input assumptions.
- Clipboard APIs.
- Keyboard shortcuts.
- Desktop hover-only interactions.
- Inertia `router` calls that expect HTML page responses.
- Long-running AI requests.
- Reverb/Echo initialization.

Each browser-only feature needs either a mobile equivalent or a graceful fallback.

### Deliverable

Create an internal feature matrix:

| Feature | Web route | Mobile API | Student | Teacher | Offline | Native dependency |
|---|---|---|---|---|---|---|
| Login | Fortify routes | `/api/v1/auth/login` | Yes | Yes | No | Secure storage |
| Materials | Existing material routes | `/api/v1/materials` | Yes | Yes | Read-only | File picker |
| Quizzes | Existing quiz routes | `/api/v1/quizzes` | Yes | Yes | Limited | Notifications |
| Games | Existing game routes | `/api/v1/games` | Yes | Yes | Selected games | Optional |

---

## 5. Phase 2: improve mobile web support first

Before adding Capacitor, make the website usable on phone-sized screens.

### Checklist

- Test layouts at 320px, 375px, 390px, 412px, and tablet widths.
- Remove horizontal page overflow.
- Ensure every modal has a mobile close action.
- Ensure buttons have at least a comfortable touch target.
- Replace hover-only actions with touch-friendly actions.
- Make tables responsive or provide card views.
- Make quiz and game controls keyboard-free where possible.
- Ensure uploads use the system file picker.
- Confirm all fullscreen games have a non-fullscreen fallback.
- Test device rotation for games and live activities.
- Test safe-area padding around notches and home indicators.

### Word Search and Crossword

For grid games:

- Keep the board inside a bounded container.
- Allow internal scrolling when a generated board cannot fit.
- Do not hide letters or clues with `overflow: hidden` unless the content has another access path.
- Add zoom or compact-cell behavior if very large boards are supported.
- Test touch selection, diagonal selection, and accidental page scrolling.

---

## 6. Phase 3: add the API foundation

The repository currently does not have a dedicated `routes/api.php`, so add a versioned API layer instead of exposing web controllers directly.

### Suggested API routes

```text
routes/api.php

/api/v1/auth/login
/api/v1/auth/logout
/api/v1/auth/me
/api/v1/auth/refresh

/api/v1/dashboard
/api/v1/profile
/api/v1/subjects
/api/v1/materials
/api/v1/materials/{material}
/api/v1/homework
/api/v1/homework/{homework}
/api/v1/submissions
/api/v1/submissions/{submission}

/api/v1/quizzes
/api/v1/quizzes/{quiz}
/api/v1/quizzes/{quiz}/attempts
/api/v1/quiz-attempts/{attempt}
/api/v1/quiz-attempts/{attempt}/submit
/api/v1/quiz-attempts/{attempt}/ai-check

/api/v1/practicals
/api/v1/practicals/{practical}
/api/v1/practical-attempts/{attempt}

/api/v1/games
/api/v1/games/{game}
/api/v1/games/{game}/attempts
/api/v1/game-attempts/{attempt}
/api/v1/game-attempts/{attempt}/submit

/api/v1/announcements
/api/v1/notifications
/api/v1/notifications/{notification}/read
/api/v1/leaderboard
/api/v1/push-tokens
```

Use `/api/v1` from the beginning so future breaking changes can be introduced through `/api/v2` rather than breaking installed apps.

### API response format

Use a consistent response format:

```json
{
  "data": {},
  "meta": {},
  "message": null,
  "errors": {}
}
```

Validation errors should be predictable:

```json
{
  "message": "The submitted data is invalid.",
  "errors": {
    "answer": ["The answer field is required."]
  }
}
```

### Reuse backend logic

Do not copy controller logic into new API controllers. Extract shared actions/services first:

```text
app/Actions/
├── Games/
├── Quizzes/
├── Submissions/
└── Materials/

app/Services/
├── GameScoringService.php
├── QuizGradingService.php
├── SubmissionService.php
└── NotificationService.php
```

Both web controllers and API controllers should call the same actions/services.

---

## 7. Phase 4: choose mobile authentication

### Recommended option: Laravel Sanctum token authentication

Use Sanctum for the mobile client with:

- Short-lived access tokens where practical.
- Token revocation on logout.
- Device name and platform metadata.
- Per-user token management.
- Server-side role checks on every protected endpoint.

Never store a long-lived token in plain browser storage.

### Mobile storage

Use Capacitor Preferences for non-sensitive settings and a secure storage plugin for tokens. Do not store access tokens in ordinary localStorage for a production app.

Store only what is needed:

- Access/refresh token.
- User identifier.
- Selected theme.
- Last successful sync time.
- Offline queue metadata.

### Authentication flows to support

- Login.
- Logout.
- Session restoration when opening the app.
- Expired token handling.
- Password reset.
- Email verification if required.
- Account disabled or deleted response.
- Optional biometric unlock after the first successful login.

---

## 8. Phase 5: build the mobile Vue shell

Add a mobile entry point without breaking the web application.

### Suggested app shell

- `MobileApp.vue` or a mobile route group.
- Bottom navigation for students:
  - Home
  - Activities
  - Games
  - Results
  - Profile
- Bottom navigation for teachers:
  - Dashboard
  - Activities
  - Students
  - Results
  - Profile
- A shared notification center.
- Global offline/online status indicator.
- Global loading and retry states.
- Global API error handling.

### Navigation rule

Use mobile routes and API data for the mobile app. Keep Inertia page navigation for the website. Avoid trying to load the full desktop dashboard inside a WebView and calling that the finished app.

### UI rules

- Use safe-area insets.
- Use large touch targets.
- Keep primary actions at the bottom on small screens.
- Avoid hover-dependent controls.
- Avoid dense desktop tables.
- Use cards, segmented controls, and bottom sheets.
- Keep destructive actions behind confirmation.

---

## 9. Phase 6: games and quizzes on mobile

### Shared rules

The backend must own:

- Correct answers.
- Attempt state.
- Score calculation.
- XP rewards.
- Completion state.
- Anti-duplication rules.
- Timer expiration.
- Reconnect behavior.

The mobile client can render the activity and provide optimistic UI, but it must not be trusted for grading or XP.

### Game types

Implement in this order:

1. Quiz.
2. True/False and multiple choice.
3. Word Search.
4. Crossword.
5. Memory Match.
6. Flashcards and ordering.
7. Drag-and-drop activities.
8. Live arena.

### Word Search and Crossword requirements

- Touch-friendly cell selection.
- No reliance on hover.
- Visible selected state.
- Accessible word/clue list.
- Internal scrolling for oversized boards.
- Device rotation support where useful.
- No loss of progress when the app is backgrounded.
- Server checkpointing for long activities.

### Timed activities

- Store the authoritative start and end timestamps on the server.
- Recalculate remaining time when the app resumes.
- Do not trust only the device clock.
- Submit automatically when the server considers the attempt expired.

---

## 10. Phase 7: offline support

Do not make high-stakes grading fully offline in the first release.

### Safe offline features

- View recently loaded materials.
- View previously loaded results.
- View downloaded instructions.
- Play practice games that do not affect grades or XP.
- Draft homework text.
- Queue non-critical profile changes.

### Online-required features initially

- Quiz final submission.
- Graded homework submission.
- Practical task submission.
- AI checking or AI grading.
- XP award.
- Live arena.
- Teacher grading.

### Offline queue design

```text
offline_queue
├── id
├── action_type
├── endpoint
├── payload
├── created_at
├── retry_count
├── status
└── last_error
```

When the connection returns:

1. Replay actions in order.
2. Use idempotency keys.
3. Stop on authentication failure.
4. Show conflicts to the user.
5. Never silently discard a submission.

---

## 11. Phase 8: native capabilities

Add native integrations only after the main mobile flow works.

### Push notifications

Use Firebase Cloud Messaging for Android and Apple Push Notification service through a suitable Capacitor plugin.

Recommended notification events:

- New assignment.
- Assignment due soon.
- Quiz result available.
- Teacher feedback available.
- Announcement published.
- Live arena starting.
- Message received.

Backend work:

- Add a `push_tokens` table.
- Associate tokens with users and devices.
- Support token rotation and revocation.
- Respect notification preferences.
- Never include sensitive grades or private content in notification previews by default.

### File uploads

Support:

- Camera capture where appropriate.
- Photo library selection.
- Document picker.
- Upload progress.
- Retry after failure.
- File size and MIME validation on the server.
- Clear permission-denied messages.

### Other native features

Add only when required:

- Share sheet.
- Biometrics.
- Local notifications.
- Deep links.
- Haptics for game feedback.
- Status bar and splash screen configuration.

---

## 12. Phase 9: realtime features

EduPulse uses Laravel Reverb and Echo. Realtime behavior must be tested inside a Capacitor WebView.

### Test and configure

- Reverb host and port for Android emulator.
- Reverb host and port for iOS simulator.
- TLS certificates in production.
- Private channel authentication.
- Token/session restoration after app resume.
- Reconnection after network changes.
- Battery usage while subscribed.

### Live arena

Add explicit connection states:

- Connecting.
- Connected.
- Reconnecting.
- Offline.
- Session ended.

The server should reconcile room state after reconnecting. Do not assume that every realtime event was received.

Recommended recovery endpoint:

```text
GET /api/v1/game-rooms/{room}/state
```

---

## 13. Phase 10: testing strategy

### Backend tests

Add feature tests for:

- API authentication.
- Role authorization.
- Subject access.
- Quiz attempt creation.
- Quiz submission idempotency.
- Game scoring.
- XP calculation.
- File upload validation.
- Push token registration.
- Reconnect/state recovery.
- Teacher/student data isolation.

### Frontend tests

Test:

- API loading, success, empty, and error states.
- Expired authentication.
- Offline queue behavior.
- Retry behavior.
- Touch selection in Word Search.
- Crossword cell selection.
- Memory Match interaction.
- Timer resume behavior.
- Upload progress.

### Device matrix

At minimum test:

- Android low-end phone.
- Android current phone.
- iPhone small screen.
- iPhone current screen.
- Android tablet.
- iPad.
- Slow network.
- Network loss during submission.
- App background/resume.
- Portrait and landscape orientation.

### Accessibility

- Screen-reader labels for game cells.
- Keyboard fallback on web.
- Color contrast.
- Do not use color as the only correctness indicator.
- Large enough touch targets.
- Reduced-motion support.

---

## 14. Phase 11: security and privacy

Before publishing:

- Use HTTPS everywhere.
- Do not embed API secrets in the app.
- Keep AI provider keys on the Laravel server.
- Validate authorization server-side for every resource.
- Rate-limit login, AI, grading, and submission endpoints.
- Validate all uploaded files.
- Scan or restrict dangerous file types.
- Avoid logging tokens, passwords, submissions, or private messages.
- Add account deletion and data export procedures where required.
- Review privacy policy for mobile permissions and analytics.
- Add app transport security configuration for production.

The app package can be inspected by users. Anything shipped in the app should be considered public.

---

## 15. Phase 12: Capacitor setup

After the mobile Vue flow works in the browser:

```bash
npm install @capacitor/core @capacitor/cli
npx cap init EduPulse com.edupulse.app
npm install @capacitor/android @capacitor/ios
npx cap add android
npx cap add ios
```

Add a mobile build script to `package.json`:

```json
{
  "scripts": {
    "build:mobile": "vite build",
    "cap:sync": "npm run build:mobile && npx cap sync",
    "cap:android": "npm run cap:sync && npx cap open android",
    "cap:ios": "npm run cap:sync && npx cap open ios"
  }
}
```

The exact Capacitor configuration should be added only after deciding whether the app loads:

1. A locally bundled Vue mobile shell that calls the Laravel API, or
2. The hosted EduPulse website inside a WebView.

Use the locally bundled mobile shell for the production architecture. A hosted WebView can be used temporarily for a prototype but should not be the final design.

### Development requirements

- Android Studio for Android builds.
- Xcode on macOS for iOS builds.
- Apple Developer account for iOS distribution.
- Google Play Console account for Android distribution.
- Firebase project for Android push notifications.
- Apple push credentials for iOS notifications.

An iOS app cannot be built or signed on Windows without using a macOS build service or CI runner.

---

## 16. Suggested project milestones

### Milestone 1: mobile web baseline

- Responsive student dashboard.
- Responsive teacher dashboard.
- Responsive quiz and game screens.
- No horizontal overflow.
- Mobile upload testing.

### Milestone 2: API foundation

- Add `/api/v1`.
- Add Sanctum authentication.
- Add API resources and validation.
- Add feature tests.
- Extract shared business services.

### Milestone 3: student mobile MVP

- Login.
- Dashboard.
- Subjects and materials.
- Homework submission.
- Quizzes and results.
- Core games.
- Profile and notifications.

### Milestone 4: teacher mobile MVP

- Teacher dashboard.
- Student list.
- Activities.
- Grading.
- Results.
- Announcements.

### Milestone 5: native features

- Push notifications.
- Native file picker/camera.
- Secure token storage.
- Offline cache.
- App lifecycle recovery.

### Milestone 6: realtime and advanced features

- Messaging.
- Live arena.
- Reverb reconnect behavior.
- Live host controls where necessary.

### Milestone 7: release hardening

- Device testing.
- Security review.
- Accessibility review.
- Performance profiling.
- Store assets and privacy declarations.
- Beta testing through TestFlight and Google Play testing tracks.

---

## 17. Definition of done for version 1

The mobile app is ready for beta when:

- A student can log in and restore a session.
- A student can view assigned activities and materials.
- A student can submit a homework file from a phone.
- A student can take and submit a quiz.
- Quiz submission remains correct after a network interruption.
- A student can play Word Search, Crossword, and Memory Match using touch.
- A student can see all letters, clues, and details without clipped content.
- A teacher can view results and grade supported activities.
- Push notifications work on at least one Android and one iOS test device.
- Expired tokens redirect to login without data loss.
- Unauthorized students cannot access teacher data.
- The backend passes API authorization and submission tests.
- The app can be built from a documented clean checkout.
- The website continues working independently of the mobile app.

---

## 18. First implementation checklist

Start with these tasks in order:

- [ ] Decide whether the first release is student-only or student plus teacher.
- [ ] Create the feature matrix for all current EduPulse screens.
- [ ] Identify the first five mobile API resources.
- [ ] Add `routes/api.php` with `/api/v1`.
- [ ] Configure Sanctum mobile authentication.
- [ ] Extract quiz/game/submission logic into shared services.
- [ ] Add API feature tests before building mobile screens.
- [ ] Create a Vue mobile shell and API client.
- [ ] Implement login and session restoration.
- [ ] Implement the student dashboard.
- [ ] Implement materials and homework submission.
- [ ] Implement quizzes and results.
- [ ] Implement the core games.
- [ ] Add Capacitor after the browser mobile flow is stable.
- [ ] Add secure storage, push notifications, and native uploads.
- [ ] Test on physical devices.
- [ ] Run a private beta before public store release.

---

## Final recommendation

Do not start by rewriting the entire application as a separate native project. Start by creating a clean mobile/API boundary around the existing Laravel and Vue codebase. Build the student MVP first, verify submissions and games on real devices, then add teacher workflows, notifications, offline support, and live arena features in separate milestones.
