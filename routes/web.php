<?php

use App\Helpers\LevelHelper;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AiActivityCreatorController;
use App\Http\Controllers\AiCommunicationController;
use App\Http\Controllers\AiManagerController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CommerceAccountController;
use App\Http\Controllers\CommerceAdminController;
use App\Http\Controllers\CommerceController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GameRoomController;
use App\Http\Controllers\Grade10ProfileController;
use App\Http\Controllers\GradeLevelController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MessengerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PollController;
use App\Http\Controllers\PracticalController;
use App\Http\Controllers\PracticalFileController;
use App\Http\Controllers\QuarterController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\SchoolYearController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\SeatworkController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\UserController;
use App\Models\GradeLevel;
use App\Models\RewardRedemption;
use App\Models\Section;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', [CommerceController::class, 'home'])->name('home');

Route::get('/shop', [CommerceController::class, 'index'])->name('shop');
Route::get('/shop/{product}', [CommerceController::class, 'show'])->name('shop.product');
Route::get('/cart', [CommerceController::class, 'cart'])->name('cart');
Route::post('/cart/{product}', [CommerceController::class, 'addToCart'])
    ->middleware('throttle:commerce-cart')
    ->block(5, 5)
    ->name('cart.add');
Route::patch('/cart/{product}', [CommerceController::class, 'updateCart'])
    ->middleware('throttle:commerce-cart')
    ->block(5, 5)
    ->name('cart.update');
Route::delete('/cart/{product}', [CommerceController::class, 'removeFromCart'])
    ->middleware('throttle:commerce-cart')
    ->block(5, 5)
    ->name('cart.remove');
Route::get('/checkout', [CommerceController::class, 'checkout'])->name('checkout');
Route::post('/checkout', [CommerceController::class, 'placeOrder'])
    ->middleware('throttle:commerce-checkout')
    ->block(10, 10)
    ->name('checkout.store');
Route::get('/orders/{order}', [CommerceController::class, 'confirmation'])
    ->middleware('signed')
    ->name('order.confirmation');

Route::middleware(['auth', 'verified', 'role.commerce:buyer,seller'])->group(function () {
    Route::get('/account', [CommerceAccountController::class, 'index'])->name('account');
    Route::get('/account/orders/{order:id}', [CommerceAccountController::class, 'show'])->name('account.orders.show');
});

Route::middleware(['auth', 'verified', 'role.commerce:seller'])->prefix('seller')->name('seller.')->group(function () {
    Route::get('/dashboard', [SellerController::class, 'index'])->name('dashboard');
    Route::post('/products', [SellerController::class, 'storeProduct'])->name('products.store');
    Route::patch('/products/{product}', [SellerController::class, 'updateProduct'])->name('products.update');
    Route::patch('/products/{product}/submit', [SellerController::class, 'submitProduct'])->name('products.submit');
});

Route::middleware(['auth', 'verified', 'role.commerce'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/commerce', [CommerceAdminController::class, 'index'])->name('commerce.index');
    Route::post('/commerce/products', [CommerceAdminController::class, 'storeProduct'])->name('commerce.products.store');
    Route::patch('/commerce/products/{product}', [CommerceAdminController::class, 'updateProduct'])->name('commerce.products.update');
    Route::patch('/commerce/products/{product}/approve', [CommerceAdminController::class, 'approveProduct'])->name('commerce.products.approve');
    Route::patch('/commerce/products/{product}/reject', [CommerceAdminController::class, 'rejectProduct'])->name('commerce.products.reject');
    Route::patch('/commerce/products/{product}/archive', [CommerceAdminController::class, 'archiveProduct'])->name('commerce.products.archive');
    Route::patch('/commerce/orders/{order:id}/status', [CommerceAdminController::class, 'updateOrderStatus'])->name('commerce.orders.status');
    Route::patch('/commerce/orders/{order:id}/payment-status', [CommerceAdminController::class, 'updateOrderPaymentStatus'])->name('commerce.orders.payment-status');
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/bulk', [UserController::class, 'bulkDestroy'])->name('users.bulk-destroy');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/merge', [UserController::class, 'merge'])->name('users.merge');
    Route::match(['get', 'post'], '/users/{user}/impersonate', [UserController::class, 'impersonate'])->name('users.impersonate');
    Route::patch('/users/toggle-system-messenger', [UserController::class, 'toggleSystemMessenger'])->name('users.toggle-system-messenger');
    Route::patch('/users/toggle-classmate-messaging', [UserController::class, 'toggleSystemClassmateMessaging'])->name('users.toggle-classmate-messaging');
});

/* The inherited EduPulse portal is still available to authenticated users. */
Route::get('/portal', function () {
    if (Auth::check()) {
        /** @var User $user */
        $user = Auth::user();
        if ($user->isSuperadmin()) {
            return redirect()->route('teacher.dashboard');
        }

        return redirect($user->isTeacher() || $user->isSuperadmin()
            ? route('teacher.dashboard')
            : ($user->isSeller() ? route('seller.dashboard') : ($user->isBuyer() ? route('account') : route('student.dashboard')))
        );
    }

    return inertia('Welcome');
})->name('portal');

Route::get('/dashboard', function () {
    /** @var User|null $user */
    $user = Auth::user();
    if (! $user) {
        return redirect()->route('login');
    }

    return redirect($user->isTeacher() || $user->isSuperadmin()
        ? route('teacher.dashboard')
        : ($user->isSeller() ? route('seller.dashboard') : ($user->isBuyer() ? route('account') : route('student.dashboard')))
    );
})->middleware(['auth'])->name('dashboard');

Route::get('/materials', function () {
    /** @var User|null $user */
    $user = Auth::user();
    if (! $user) {
        return redirect()->route('login');
    }

    return redirect($user->isTeacher() || $user->isSuperadmin()
        ? route('teacher.materials.index')
        : ($user->isSeller() || $user->isBuyer() ? route('account') : route('student.materials'))
    );
})->middleware(['auth'])->name('materials.redirect');

Route::get('/privacy-policy', fn () => inertia('Legal/PrivacyPolicy'))->name('privacy');
Route::get('/terms-of-service', fn () => inertia('Legal/TermsOfService'))->name('terms');
Route::get('/cookie-policy', fn () => inertia('Legal/CookiePolicy'))->name('cookies');

if (! Features::enabled(Features::registration())) {
    Route::match(['get', 'post'], '/register', fn () => redirect()->route('login'))->name('register');
}

Route::middleware(['auth', 'verified', 'role.teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/dashboard', [TeacherController::class, 'dashboard'])->name('dashboard');
    Route::get('/students', [TeacherController::class, 'students'])->name('students');
    Route::get('/students/export-scores', [TeacherController::class, 'exportScores'])->name('students.export-scores');
    Route::get('/students/export-report-card-pdf', [TeacherController::class, 'exportReportCardPdf'])->name('students.export-report-card-pdf');
    Route::get('/students/{user}/scores', [TeacherController::class, 'studentScores'])->name('students.scores');
    Route::get('/students/{user}/points', [TeacherController::class, 'studentPoints'])->name('students.points');
    Route::get('/students/{user}/summary', [TeacherController::class, 'studentSummary'])->name('students.summary');
    Route::put('/students/{user}', [TeacherController::class, 'studentUpdate'])->name('students.update');
    Route::delete('/students/bulk-remove', [TeacherController::class, 'bulkStudentRemove'])->name('students.bulk-remove');
    Route::delete('/students/{user}/remove', [TeacherController::class, 'studentRemove'])->name('students.remove');
    Route::post('/students/merge', [UserController::class, 'merge'])->name('students.merge');
    Route::post('/students/{user}/add-xp', [TeacherController::class, 'addXp'])->name('students.addXp');
    Route::get('/teachers', [TeacherController::class, 'teachers'])->name('teachers.index');
    Route::get('/teachers/{user}', [TeacherController::class, 'teacherShow'])->name('teachers.show');
    Route::put('/teachers/{user}', [TeacherController::class, 'teacherUpdate'])->name('teachers.update');
    Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
    Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
    Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');
    Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy'])->name('subjects.destroy');
    Route::get('/results', [TeacherController::class, 'results'])->name('results');
    Route::post('/results/auto-recheck-bulk', [TeacherController::class, 'autoRecheckBulk'])->name('results.auto-recheck-bulk');
    Route::delete('/results/attempt', [TeacherController::class, 'destroyAttempt'])->name('results.destroy-attempt');
    Route::delete('/results/bulk-destroy', [TeacherController::class, 'destroyAttemptsBulk'])->name('results.destroy-attempts-bulk');
    Route::get('/leaderboard', [TeacherController::class, 'leaderboard'])->name('leaderboard');
    Route::get('/games', [GameController::class, 'index'])->name('games.index');
    Route::post('/games', [GameController::class, 'store'])->name('games.store');
    Route::patch('/games/bulk-visibility', [GameController::class, 'bulkVisibility'])->name('games.bulk-visibility');
    Route::post('/games/bulk-auto-reopen', [GameController::class, 'bulkAutoReopen'])->name('games.bulk-auto-reopen');
    Route::delete('/games/bulk', [GameController::class, 'bulkDestroy'])->name('games.bulk-destroy');
    Route::put('/games/{game}', [GameController::class, 'update'])->name('games.update');
    Route::patch('/games/{game}/toggle-hidden', [GameController::class, 'toggleHidden'])->name('games.toggleHidden');
    Route::patch('/games/{game}/reassign', [GameController::class, 'reassign'])->name('games.reassign');
    Route::delete('/games/{game}', [GameController::class, 'destroy'])->name('games.destroy');
    Route::get('/games/{game}/players', [GameController::class, 'players'])->name('games.players');
    Route::post('/games/{game}/reopen/{student}', [GameController::class, 'reopenForStudent'])->name('games.reopen');
    Route::post('/games/{game}/toggle-auto-reopen', [GameController::class, 'toggleAutoReopen'])->name('games.toggle-auto-reopen');
    Route::post('/games/{game}/rooms', [GameRoomController::class, 'teacherCreate'])->name('games.rooms.create');
    Route::get('/games/live/create', [GameRoomController::class, 'teacherLiveCreate'])->name('games.live.create');
    Route::post('/games/live/generate-ai', [GameRoomController::class, 'generateAiQuestions'])->name('games.live.generate-ai');
    Route::post('/games/live/create', [GameRoomController::class, 'teacherStoreLiveGame'])->name('games.live.store');
    Route::get('/games/rooms/{room:pin}/status', [GameRoomController::class, 'roomStatus'])->name('games.rooms.status');
    Route::get('/games/rooms/{room:pin}/host', [GameRoomController::class, 'teacherHost'])->name('games.rooms.host');
    Route::post('/games/rooms/{room:pin}/start', [GameRoomController::class, 'teacherStart'])->name('games.rooms.start');
    Route::post('/games/rooms/{room:pin}/next', [GameRoomController::class, 'teacherNext'])->name('games.rooms.next');
    Route::post('/games/rooms/{room:pin}/end', [GameRoomController::class, 'teacherEnd'])->name('games.rooms.end');
    Route::delete('/games/rooms/{room:pin}', [GameRoomController::class, 'destroy'])->name('games.rooms.destroy');
    Route::post('/games/{game}/reopen-requests/{reopenRequest}/reject', [GameController::class, 'rejectReopenRequest'])->name('games.reopen-requests.reject');

    Route::post('/ai-communications/draft', [AiCommunicationController::class, 'draft'])->middleware('throttle:10,1')->name('ai-communications.draft');

    Route::get('/ai-creator', [AiActivityCreatorController::class, 'create'])->name('ai-creator.create');
    Route::post('/ai-creator/parse', [AiActivityCreatorController::class, 'parse'])->name('ai-creator.parse');
    Route::post('/ai-creator', [AiActivityCreatorController::class, 'store'])->name('ai-creator.store');

    Route::get('/materials', [MaterialController::class, 'teacherIndex'])->name('materials.index');
    Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
    Route::match(['put', 'patch', 'post'], '/materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
    Route::patch('/materials/{material}/reassign', [MaterialController::class, 'reassign'])->name('materials.reassign');
    Route::post('/materials/{material}/generate', [MaterialController::class, 'generateQuiz'])->name('materials.generate');
    Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])->name('materials.destroy');

    Route::get('/announcements', [AnnouncementController::class, 'teacherIndex'])->name('announcements.index');
    Route::patch('/announcements/{announcement}/toggle-pin', [AnnouncementController::class, 'togglePin'])->name('announcements.toggle-pin');

    Route::get('/polls', [PollController::class, 'index'])->name('polls.index');
    Route::post('/polls', [PollController::class, 'store'])->name('polls.store');
    Route::patch('/polls/{poll}/toggle-active', [PollController::class, 'toggleActive'])->name('polls.toggle-active');
    Route::delete('/polls/{poll}', [PollController::class, 'destroy'])->name('polls.destroy');

    Route::get('/todos', [TodoController::class, 'teacherIndex'])->name('todos.index');
    Route::post('/todos', [TodoController::class, 'store'])->name('todos.store');
    Route::delete('/todos/{todo}', [TodoController::class, 'destroy'])->name('todos.destroy');
    Route::post('/todos/dismiss-grading-task', [TodoController::class, 'dismissGradingTask'])->name('todos.dismiss-grading-task');

    Route::get('/quizzes', [QuizController::class, 'index'])->name('quizzes.index');
    Route::get('/quizzes/create', [QuizController::class, 'create'])->name('quizzes.create');
    Route::post('/quizzes', [QuizController::class, 'store'])->name('quizzes.store');
    Route::get('/quizzes/{quiz}', [QuizController::class, 'show'])->name('quizzes.show');
    Route::get('/quizzes/{quiz}/edit', [QuizController::class, 'edit'])->name('quizzes.edit');
    Route::put('/quizzes/{quiz}', [QuizController::class, 'update'])->name('quizzes.update');
    Route::patch('/quizzes/{quiz}/publish', [QuizController::class, 'publish'])->name('quizzes.publish');
    Route::patch('/quizzes/{quiz}/reopen', [QuizController::class, 'reopen'])->name('quizzes.reopen');
    Route::patch('/quizzes/{quiz}/close-now', [QuizController::class, 'closeNow'])->name('quizzes.close-now');
    Route::get('/quizzes/{quiz}/recheck/{attempt}', [QuizController::class, 'recheck'])->name('quizzes.recheck');
    Route::put('/quizzes/{quiz}/recheck/{attempt}', [QuizController::class, 'recheckUpdate'])->name('quizzes.recheck-update');
    Route::put('/quizzes/{quiz}/auto-recheck/{attempt}', [QuizController::class, 'autoRecheck'])->name('quizzes.auto-recheck');
    Route::post('/quizzes/{quiz}/auto-recheck-all', [QuizController::class, 'autoRecheckAll'])->name('quizzes.auto-recheck-all');
    Route::post('/quizzes/{quiz}/ai-check/{attempt}', [QuizController::class, 'aiCheck'])->name('quizzes.ai-check');
    Route::patch('/quizzes/{quiz}/reassign', [QuizController::class, 'reassign'])->name('quizzes.reassign');
    Route::delete('/quizzes/{quiz}', [QuizController::class, 'destroy'])->name('quizzes.destroy');

    Route::get('/practicals', [PracticalController::class, 'index'])->name('practicals.index');
    Route::post('/practicals', [PracticalController::class, 'store'])->name('practicals.store');
    Route::get('/practicals/{practical}/grade/{attempt}', [PracticalController::class, 'grade'])->name('practicals.grade');
    Route::put('/practicals/{practical}/grade/{attempt}', [PracticalController::class, 'gradeUpdate'])->name('practicals.grade-update');
    Route::put('/practicals/{practical}/auto-recheck/{attempt}', [PracticalController::class, 'autoRecheck'])->name('practicals.auto-recheck');
    Route::post('/practicals/{practical}/ai-grade/{attempt}', [PracticalController::class, 'aiGrade'])->name('practicals.ai-grade');
    Route::post('/practicals/{practical}/ai-grade-all', [PracticalController::class, 'aiGradeAll'])->name('practicals.ai-grade-all');
    Route::get('/practicals/{practical}/recheck/{attempt}', [PracticalController::class, 'recheck'])->name('practicals.recheck');
    Route::put('/practicals/{practical}/recheck/{attempt}', [PracticalController::class, 'recheckUpdate'])->name('practicals.recheck-update');
    Route::get('/practicals/{practical}/edit', [PracticalController::class, 'edit'])->name('practicals.edit');
    Route::put('/practicals/{practical}', [PracticalController::class, 'update'])->name('practicals.update');
    Route::get('/practicals/{practical}', [PracticalController::class, 'show'])->name('practicals.show');
    Route::patch('/practicals/{practical}/publish', [PracticalController::class, 'publish'])->name('practicals.publish');
    Route::patch('/practicals/{practical}/reopen', [PracticalController::class, 'reopen'])->name('practicals.reopen');
    Route::patch('/practicals/{practical}/close-now', [PracticalController::class, 'closeNow'])->name('practicals.close-now');
    Route::patch('/practicals/{practical}/close-attempt/{attempt}', [PracticalController::class, 'closeAttempt'])->name('practicals.close-attempt');
    Route::patch('/practicals/{practical}/reassign', [PracticalController::class, 'reassign'])->name('practicals.reassign');
    Route::delete('/practicals/{practical}', [PracticalController::class, 'destroy'])->name('practicals.destroy');
    Route::delete('/practicals/{practical}/attempt/{attempt}', [PracticalController::class, 'destroyAttempt'])->name('practicals.destroy-attempt');

    Route::get('/exams', [ExamController::class, 'index'])->name('exams.index');
    Route::post('/exams', [ExamController::class, 'store'])->name('exams.store');
    Route::post('/exams/compile-activities', [ExamController::class, 'compileActivities'])->name('exams.compile-activities');
    Route::get('/exams/{exam}/edit', [ExamController::class, 'edit'])->name('exams.edit');
    Route::put('/exams/{exam}', [ExamController::class, 'update'])->name('exams.update');
    Route::get('/exams/{exam}', [ExamController::class, 'show'])->name('exams.show');
    Route::patch('/exams/{exam}/publish', [ExamController::class, 'publish'])->name('exams.publish');
    Route::patch('/exams/{exam}/reopen', [ExamController::class, 'reopen'])->name('exams.reopen');
    Route::patch('/exams/{exam}/close-now', [ExamController::class, 'closeNow'])->name('exams.close-now');
    Route::patch('/exams/{exam}/reassign', [ExamController::class, 'reassign'])->name('exams.reassign');
    Route::delete('/exams/{exam}', [ExamController::class, 'destroy'])->name('exams.destroy');
    Route::get('/exams/{exam}/attempts', [ExamController::class, 'attempts'])->name('exams.attempts');
    Route::post('/exams/attempts/{attempt}/grade', [ExamController::class, 'grade'])->name('exams.grade');
    Route::put('/exams/attempts/{attempt}/auto-recheck', [ExamController::class, 'autoRecheck'])->name('exams.auto-recheck');
    Route::post('/exams/attempts/{attempt}/ai-grade', [ExamController::class, 'aiGrade'])->name('exams.ai-grade');
    Route::post('/exams/{exam}/ai-grade-all', [ExamController::class, 'aiGradeAll'])->name('exams.ai-grade-all');

    Route::get('/seatworks', [SeatworkController::class, 'index'])->name('seatworks.index');
    Route::post('/seatworks', [SeatworkController::class, 'store'])->name('seatworks.store');
    Route::get('/seatworks/{seatwork}/edit', [SeatworkController::class, 'edit'])->name('seatworks.edit');
    Route::put('/seatworks/{seatwork}', [SeatworkController::class, 'update'])->name('seatworks.update');
    Route::get('/seatworks/{seatwork}', [SeatworkController::class, 'show'])->name('seatworks.show');
    Route::get('/seatworks/{seatwork}/recheck/{attempt}', [SeatworkController::class, 'recheck'])->name('seatworks.recheck');
    Route::put('/seatworks/{seatwork}/recheck/{attempt}', [SeatworkController::class, 'recheckUpdate'])->name('seatworks.recheck-update');
    Route::post('/seatworks/{seatwork}/attempts/{attempt}/record', [SeatworkController::class, 'setRecordedAttempt'])->name('seatworks.attempts.record');
    Route::put('/seatworks/{seatwork}/auto-recheck/{attempt}', [SeatworkController::class, 'autoRecheck'])->name('seatworks.auto-recheck');
    Route::post('/seatworks/{seatwork}/auto-recheck-all', [SeatworkController::class, 'autoRecheckAll'])->name('seatworks.auto-recheck-all');
    Route::post('/seatworks/{seatwork}/ai-check/{attempt}', [SeatworkController::class, 'aiCheck'])->name('seatworks.ai-check');
    Route::patch('/seatworks/{seatwork}/publish', [SeatworkController::class, 'publish'])->name('seatworks.publish');
    Route::patch('/seatworks/{seatwork}/reopen', [SeatworkController::class, 'reopen'])->name('seatworks.reopen');
    Route::patch('/seatworks/{seatwork}/close-now', [SeatworkController::class, 'closeNow'])->name('seatworks.close-now');
    Route::patch('/seatworks/{seatwork}/reassign', [SeatworkController::class, 'reassign'])->name('seatworks.reassign');
    Route::delete('/seatworks/{seatwork}', [SeatworkController::class, 'destroy'])->name('seatworks.destroy');

    Route::get('/grade-levels', [GradeLevelController::class, 'index'])->name('grade-levels.index');
    Route::get('/grade-levels/create', [GradeLevelController::class, 'create'])->name('grade-levels.create');
    Route::post('/grade-levels', [GradeLevelController::class, 'store'])->name('grade-levels.store');
    Route::get('/grade-levels/{gradeLevel}/edit', [GradeLevelController::class, 'edit'])->name('grade-levels.edit');
    Route::patch('/grade-levels/{gradeLevel}', [GradeLevelController::class, 'update'])->name('grade-levels.update');
    Route::delete('/grade-levels/{gradeLevel}', [GradeLevelController::class, 'destroy'])->name('grade-levels.destroy');

    Route::post('/grade-levels/{gradeLevel}/sections', [GradeLevelController::class, 'storeSection'])->name('grade-levels.sections.store');
    Route::delete('/grade-levels/{gradeLevel}/sections/{section}', [GradeLevelController::class, 'destroySection'])->name('grade-levels.sections.destroy');

    Route::get('/quarters', [QuarterController::class, 'index'])->name('quarters.index');
    Route::post('/quarters', [QuarterController::class, 'store'])->name('quarters.store');
    Route::put('/quarters/{quarter}', [QuarterController::class, 'update'])->name('quarters.update');
    Route::delete('/quarters/{quarter}', [QuarterController::class, 'destroy'])->name('quarters.destroy');

    Route::get('/school-years', [SchoolYearController::class, 'index'])->name('school-years.index');
    Route::post('/school-years', [SchoolYearController::class, 'store'])->name('school-years.store');
    Route::post('/school-years/switch', [SchoolYearController::class, 'switchView'])->name('school-years.switch');
    Route::post('/school-years/transfer', [SchoolYearController::class, 'transferContent'])->name('school-years.transfer');
    Route::put('/school-years/{schoolYear}', [SchoolYearController::class, 'update'])->name('school-years.update');
    Route::patch('/school-years/{schoolYear}/set-current', [SchoolYearController::class, 'setCurrent'])->name('school-years.set-current');
    Route::delete('/school-years/{schoolYear}', [SchoolYearController::class, 'destroy'])->name('school-years.destroy');

    Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/activity-logs/poll', [ActivityLogController::class, 'poll'])->name('activity-logs.poll');
    Route::post('/activity-logs/clear', [ActivityLogController::class, 'clear'])->name('activity-logs.clear');

    Route::get('/error-logs', [ErrorLogController::class, 'index'])->name('error-logs.index');
    Route::post('/error-logs/clear', [ErrorLogController::class, 'clear'])->name('error-logs.clear');
    Route::get('/error-logs/download', [ErrorLogController::class, 'download'])->name('error-logs.download');
    Route::post('/error-logs/test', [ErrorLogController::class, 'generateTest'])->name('error-logs.test');

    Route::get('/commerce', [CommerceAdminController::class, 'index'])->name('commerce.index');
    Route::post('/commerce/products', [CommerceAdminController::class, 'storeProduct'])->name('commerce.products.store');
    Route::patch('/commerce/products/{product}', [CommerceAdminController::class, 'updateProduct'])->name('commerce.products.update');
    Route::patch('/commerce/products/{product}/approve', [CommerceAdminController::class, 'approveProduct'])->name('commerce.products.approve');
    Route::patch('/commerce/products/{product}/reject', [CommerceAdminController::class, 'rejectProduct'])->name('commerce.products.reject');
    Route::patch('/commerce/products/{product}/archive', [CommerceAdminController::class, 'archiveProduct'])->name('commerce.products.archive');
    Route::patch('/commerce/orders/{order:id}/status', [CommerceAdminController::class, 'updateOrderStatus'])->name('commerce.orders.status');
    Route::patch('/commerce/orders/{order:id}/payment-status', [CommerceAdminController::class, 'updateOrderPaymentStatus'])->name('commerce.orders.payment-status');

    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('/backups/{filename}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/{filename}/sync', [BackupController::class, 'sync'])->name('backups.sync');
    Route::delete('/backups/{filename}', [BackupController::class, 'destroy'])->name('backups.destroy');
    Route::post('/backups/restore', [BackupController::class, 'restore'])->name('backups.restore');
    Route::post('/backups/upload', [BackupController::class, 'upload'])->name('backups.upload');

    Route::get('/system-settings', [SystemSettingController::class, 'index'])->name('system-settings.index');
    Route::put('/system-settings', [SystemSettingController::class, 'update'])->name('system-settings.update');
    Route::put('/system-settings/storage', [SystemSettingController::class, 'updateStorage'])->name('system-settings.update-storage');
    Route::put('/system-settings/ai', [SystemSettingController::class, 'updateAi'])->name('system-settings.update-ai');
    Route::post('/system-settings/reset', [SystemSettingController::class, 'reset'])->name('system-settings.reset');
    Route::post('/system-settings/test-ai', [SystemSettingController::class, 'testAi'])->name('system-settings.test-ai');
    Route::post('/system-settings/test-storage', [SystemSettingController::class, 'testStorage'])->name('system-settings.test-storage');

    Route::get('/ai-manager', [AiManagerController::class, 'index'])->name('ai-manager.index');
    Route::post('/ai-manager/run', [AiManagerController::class, 'runInspection'])->name('ai-manager.run');
    Route::post('/ai-manager/logs/{log}/revert', [AiManagerController::class, 'revert'])->name('ai-manager.revert');

    Route::get('/profile', function () {
        return Inertia::render('Teacher/Profile', [
            'gradeLevels' => GradeLevel::orderBy('name')->get(['id', 'name']),
            'sections' => Section::orderBy('name')->get(['id', 'name', 'grade_level_id']),
        ]);
    })->name('profile');
    Route::patch('/profile/toggle-messenger', [TeacherController::class, 'toggleMessenger'])->name('profile.toggleMessenger');
    Route::patch('/profile/toggle-classmate-messaging', [TeacherController::class, 'toggleClassmateMessaging'])->name('profile.toggleClassmateMessaging');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy'])->name('announcements.destroy');

    Route::get('/meetings', [MeetingController::class, 'teacherIndex'])->name('meetings.index');
    Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
    Route::post('/meetings/{meeting}/verify', [MeetingController::class, 'verify'])->name('meetings.verify');
    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

    Route::middleware(['messenger.enabled'])->group(function () {
        Route::get('/messenger', [MessengerController::class, 'index'])->name('messenger');
        Route::get('/messenger/unread-count', [MessengerController::class, 'unreadCount'])->name('messenger.unread');
        Route::get('/messenger/{conversation}', [MessengerController::class, 'show'])->name('messenger.show');
        Route::get('/messenger/{conversation}/meet', [MessengerController::class, 'meet'])->name('messenger.meet');
        Route::post('/messenger/start', [MessengerController::class, 'store'])->name('messenger.start');
        Route::post('/messenger/group', [MessengerController::class, 'storeGroup'])->name('messenger.group.store');
        Route::post('/messenger/{conversation}/send', [MessengerController::class, 'sendMessage'])->name('messenger.send');
        Route::post('/messenger/{conversation}/read', [MessengerController::class, 'markAsRead'])->name('messenger.read');
        Route::patch('/messenger/{conversation}/messages/{message}', [MessengerController::class, 'updateMessage'])->name('messenger.message.update');
        Route::delete('/messenger/{conversation}/messages/{message}', [MessengerController::class, 'destroyMessage'])->name('messenger.message.destroy');
        Route::get('/messenger/{conversation}/poll', [MessengerController::class, 'poll'])->name('messenger.poll');
        Route::delete('/messenger/{conversation}', [MessengerController::class, 'archive'])->name('messenger.archive');
    });

    // Attendance Module
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::get('/api/attendance', [AttendanceController::class, 'getDaily'])->name('attendance.get');
    Route::get('/api/attendance/all', [AttendanceController::class, 'allDaily'])->name('attendance.all');
    Route::post('/api/attendance', [AttendanceController::class, 'storeDaily'])->name('attendance.store');
    Route::put('/api/attendance/{recordId}', [AttendanceController::class, 'updateDaily'])->name('attendance.update');
    Route::put('/api/attendance/{recordId}/entry', [AttendanceController::class, 'updateDailyEntry'])->name('attendance.entry-update');
    Route::delete('/api/attendance/{recordId}', [AttendanceController::class, 'destroyDaily'])->name('attendance.destroy');

    Route::get('/api/monthly', [AttendanceController::class, 'getMonthly'])->name('attendance.monthly');
    Route::post('/api/monthly', [AttendanceController::class, 'storeMonthly'])->name('attendance.monthly-store');
    Route::put('/api/monthly/{recordId}/entry', [AttendanceController::class, 'updateMonthlyEntry'])->name('attendance.monthly-entry');
    Route::put('/api/monthly/{recordId}/remarks', [AttendanceController::class, 'updateMonthlyRemarks'])->name('attendance.monthly-remarks');
    Route::put('/api/monthly/{recordId}/summary', [AttendanceController::class, 'updateMonthlySummary'])->name('attendance.monthly-summary');
    Route::put('/api/monthly/{recordId}/excluded-dates', [AttendanceController::class, 'updateMonthlyExcludedDates'])->name('attendance.monthly-excluded');
    Route::delete('/api/monthly/{recordId}', [AttendanceController::class, 'destroyMonthly'])->name('attendance.monthly-destroy');
    Route::get('/api/monthly/{recordId}/export', [AttendanceController::class, 'exportMonthly'])->name('attendance.monthly-export');

    Route::get('/api/students', [AttendanceController::class, 'getStudents'])->name('attendance.students');

    Route::get('/api/schedule/{teacherId}', [AttendanceController::class, 'getSchedule'])->name('attendance.schedule');
    Route::post('/api/schedule/{teacherId}', [AttendanceController::class, 'storeSchedule'])->name('attendance.schedule-store');
    Route::put('/api/schedule/{teacherId}', [AttendanceController::class, 'updateSchedule'])->name('attendance.schedule-update');
    Route::put('/api/schedule/{teacherId}/{entryId}', [AttendanceController::class, 'updateScheduleEntry'])->name('attendance.schedule-entry-update');
    Route::delete('/api/schedule/{teacherId}/{entryId}', [AttendanceController::class, 'destroyScheduleEntry'])->name('attendance.schedule-entry-destroy');
    Route::delete('/api/schedule/{teacherId}/all', [AttendanceController::class, 'destroyScheduleAll'])->name('attendance.schedule-all-destroy');

    Route::get('/api/attendance-settings', [AttendanceController::class, 'getSettings'])->name('attendance.settings');
    Route::post('/api/attendance-settings', [AttendanceController::class, 'storeSettings'])->name('attendance.settings-store');

    Route::get('/api/attendance-events/calendar', [AttendanceController::class, 'getCalendarEvents'])->name('attendance.calendar');
    Route::post('/api/attendance-events/calendar', [AttendanceController::class, 'storeCalendarEvent'])->name('attendance.calendar-store');
    Route::put('/api/attendance-events/calendar/{id}', [AttendanceController::class, 'updateCalendarEvent'])->name('attendance.calendar-update');
    Route::delete('/api/attendance-events/calendar/{id}', [AttendanceController::class, 'destroyCalendarEvent'])->name('attendance.calendar-destroy');

    Route::get('/api/attendance-events/quarterly', [AttendanceController::class, 'getQuarterlyEvents'])->name('attendance.quarterly');
    Route::post('/api/attendance-events/quarterly', [AttendanceController::class, 'storeQuarterlyEvent'])->name('attendance.quarterly-store');
    Route::put('/api/attendance-events/quarterly/{id}', [AttendanceController::class, 'updateQuarterlyEvent'])->name('attendance.quarterly-update');
    Route::delete('/api/attendance-events/quarterly/{id}', [AttendanceController::class, 'destroyQuarterlyEvent'])->name('attendance.quarterly-destroy');

    Route::get('/grade10-profiles', [Grade10ProfileController::class, 'teacherIndex'])->name('grade10.profiles.index');
    Route::get('/grade10-profiles/export/{format}', [Grade10ProfileController::class, 'export'])->name('grade10.profiles.export');
});

Route::middleware(['auth', 'verified', 'role.student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');
    Route::get('/quizzes', [StudentController::class, 'quizzes'])->name('quizzes.index');
    Route::get('/seatworks', [StudentController::class, 'seatworks'])->name('seatworks.index');
    Route::get('/practicals', [StudentController::class, 'practicals'])->name('practicals.index');
    Route::get('/exams', [StudentController::class, 'exams'])->name('exams.index');
    Route::get('/quizzes/{quiz}/take', [StudentController::class, 'take'])->name('quizzes.take');
    Route::post('/quizzes/{quiz}/submit', [StudentController::class, 'submit'])->name('quizzes.submit');
    Route::get('/quizzes/{attempt}/result', [StudentController::class, 'result'])->name('quizzes.result');
    Route::get('/results', [StudentController::class, 'results'])->name('results');
    Route::get('/practicals/{practical}/take', [PracticalController::class, 'take'])->name('practicals.take');
    Route::post('/practicals/{practical}/submit', [PracticalController::class, 'submit'])->name('practicals.submit');
    Route::get('/practicals/{attempt}/result', [PracticalController::class, 'result'])->name('practicals.result');
    Route::post('/practicals/{attempt}/close', [PracticalController::class, 'close'])->name('practicals.close');

    Route::get('/exams/{exam}/take', [ExamController::class, 'take'])->name('exams.take');
    Route::post('/exams/{exam}/submit', [ExamController::class, 'submit'])->name('exams.submit');
    Route::post('/exams/attempts/{attempt}/sync-timer', [ExamController::class, 'syncTimer'])->name('exams.sync-timer');
    Route::get('/exams/{attempt}/result', [ExamController::class, 'result'])->name('exams.result');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read', [NotificationController::class, 'read'])->name('notifications.read');

    Route::get('/leaderboard', [StudentController::class, 'leaderboard'])->name('leaderboard');

    Route::get('/games', [GameController::class, 'studentGames'])->name('games');
    Route::post('/games/rooms/join', [GameRoomController::class, 'studentJoin'])->name('games.rooms.join');
    Route::get('/games/rooms/{room:pin}', [GameRoomController::class, 'studentPlay'])->name('games.rooms.play');
    Route::post('/games/rooms/{room:pin}/answer', [GameRoomController::class, 'studentAnswer'])->name('games.rooms.answer');
    Route::get('/games/rooms/{room:pin}/status', [GameRoomController::class, 'roomStatus'])->name('games.rooms.status.student');
    Route::post('/games/{game}/request-reopen', [GameController::class, 'requestReopen'])->name('games.request-reopen');
    Route::post('/games/{game}/progress', [GameController::class, 'saveProgress'])->name('games.progress');
    Route::post('/games/{game}/answer', [GameController::class, 'correctAnswer'])->name('games.answer');
    Route::post('/games/{game}/hint', [GameController::class, 'useHint'])->name('games.hint');

    Route::post('/streak/freeze', [StudentController::class, 'freezeStreak'])->name('streak.freeze');
    Route::post('/polls/{poll}/vote', [StudentController::class, 'votePoll'])->name('polls.vote');

    Route::get('/materials', [MaterialController::class, 'studentIndex'])->name('materials');
    Route::post('/materials/{material}/toggle-read', [TodoController::class, 'toggleMaterialRead'])->name('materials.toggle-read');

    Route::get('/announcements', [AnnouncementController::class, 'studentIndex'])->name('announcements.index');
    Route::get('/todos', [TodoController::class, 'studentIndex'])->name('todos.index');
    Route::post('/todos/{todo}/toggle', [TodoController::class, 'toggleStudentTodo'])->name('todos.toggle');
    Route::post('/todos/dismiss', [TodoController::class, 'dismissStudentTodo'])->name('todos.dismiss');
    Route::post('/todos/restore', [TodoController::class, 'restoreStudentTodo'])->name('todos.restore');

    Route::get('/profile', function () {
        $user = Auth::user();

        return Inertia::render('Student/Profile', [
            'gradeLevels' => GradeLevel::orderBy('name')->get(['id', 'name']),
            'sections' => Section::orderBy('name')->get(['id', 'name', 'grade_level_id']),
            'secretRewards' => LevelHelper::unlockedRewards((int) $user->total_points),
            'usedRewards' => RewardRedemption::where('user_id', $user->id)->where('status', 'used')->pluck('level'),
            'rewardsHub' => RewardService::getStudentRewardsHub($user),
        ]);
    })->name('profile');

    Route::post('/rewards/{level}/use', [StudentController::class, 'useReward'])->name('rewards.use');
    Route::post('/rewards/use-homework-pass', [StudentController::class, 'useHomeworkPass'])->name('rewards.use-homework-pass');
    Route::post('/rewards/use-time-warp', [StudentController::class, 'useTimeWarp'])->name('rewards.use-time-warp');
    Route::post('/rewards/use-star-compass', [StudentController::class, 'useStarCompass'])->name('rewards.use-star-compass');
    Route::post('/rewards/equip-badge', [StudentController::class, 'equipBadge'])->name('rewards.equip-badge');

    Route::get('/meetings', [MeetingController::class, 'studentIndex'])->name('meetings.index');
    Route::post('/meetings/{meeting}/verify', [MeetingController::class, 'verify'])->name('meetings.verify');
    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy'])->name('meetings.destroy');

    Route::middleware(['messenger.enabled'])->group(function () {
        Route::get('/messenger', [MessengerController::class, 'index'])->name('messenger');
        Route::get('/messenger/unread-count', [MessengerController::class, 'unreadCount'])->name('messenger.unread');
        Route::get('/messenger/{conversation}', [MessengerController::class, 'show'])->name('messenger.show');
        Route::get('/messenger/{conversation}/meet', [MessengerController::class, 'meet'])->name('messenger.meet');
        Route::post('/messenger/start', [MessengerController::class, 'store'])->name('messenger.start');
        Route::post('/messenger/group', [MessengerController::class, 'storeGroup'])->name('messenger.group.store');
        Route::post('/messenger/{conversation}/send', [MessengerController::class, 'sendMessage'])->name('messenger.send');
        Route::post('/messenger/{conversation}/read', [MessengerController::class, 'markAsRead'])->name('messenger.read');
        Route::patch('/messenger/{conversation}/messages/{message}', [MessengerController::class, 'updateMessage'])->name('messenger.message.update');
        Route::delete('/messenger/{conversation}/messages/{message}', [MessengerController::class, 'destroyMessage'])->name('messenger.message.destroy');
        Route::get('/messenger/{conversation}/poll', [MessengerController::class, 'poll'])->name('messenger.poll');
        Route::delete('/messenger/{conversation}', [MessengerController::class, 'archive'])->name('messenger.archive');
    });

    Route::get('/seatworks/{seatwork}/take', [SeatworkController::class, 'take'])->name('seatworks.take');
    Route::post('/seatworks/{seatwork}/submit', [SeatworkController::class, 'submit'])->name('seatworks.submit');
    Route::get('/seatworks/{attempt}/result', [SeatworkController::class, 'result'])->name('seatworks.result');

    Route::get('/grade10-profile', [Grade10ProfileController::class, 'studentShow'])->name('grade10.profile');
    Route::post('/grade10-profile', [Grade10ProfileController::class, 'studentStore'])->name('grade10.profile.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/users', function () {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user?->isCommerceAdmin()) {
            return redirect()->route('admin.users.index');
        }

        if ($user?->isSeller()) {
            return redirect()->route('seller.dashboard');
        }

        if ($user?->isBuyer()) {
            return redirect()->route('account');
        }

        return redirect()->route($user?->isTeacher() ? 'teacher.dashboard' : 'student.dashboard');
    })->name('users');
    Route::match(['get', 'post'], '/leave-impersonation', [UserController::class, 'leaveImpersonation'])->name('users.leave-impersonation');
    Route::match(['get', 'post'], '/impersonate/{user}', [UserController::class, 'impersonate']);
    Route::match(['get', 'post'], '/users/{user}/impersonate', [UserController::class, 'impersonate']);
    Route::get('/api/messenger-status', [MessengerController::class, 'status']);
    Route::get('/messenger/attachment/{message}', [MessengerController::class, 'attachment'])->name('messenger.attachment');
    Route::get('/materials/{material}/preview', [MaterialController::class, 'preview'])->name('materials.preview');
    Route::get('/materials/{material}/download', [MaterialController::class, 'download'])->name('materials.download');
    Route::get('/practical-attempts/{attempt}/file', [PracticalFileController::class, 'show'])->name('practical-attempts.file');
});

require __DIR__.'/settings.php';
