<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceReportController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\ClassScheduleReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EvaluationStatementController;
use App\Http\Controllers\ExamAttendanceController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\ExamScheduleReportController;
use App\Http\Controllers\ExamStatusController;
use App\Http\Controllers\ExamStudentController;
use App\Http\Controllers\ExamSupervisorController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuPermissionController;
use App\Http\Controllers\NoticeController;
use App\Http\Controllers\NoticeReportController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\StudentAttendanceController;
use App\Http\Controllers\StudentAttendanceReportController;
use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentResultReportController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherSubjectController;
use App\Http\Controllers\TeacherSurveyController;
use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\AuthController;
use \App\Http\Controllers\SettingController;
use \App\Http\Controllers\UserController;
use \App\Http\Controllers\CommonController;

Route::post('login', [AuthController::class, 'login']);
Route::post('student-login', [StudentAuthController::class, 'studentLogin']);

Route::group(['middleware' => 'jwt:api'], function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
    Route::get('app-supporting-data', [SettingController::class, 'appSupportingData']);
});

Route::group(['middleware' => ['jwt:api']], function () {
    // ADMIN USERS
    Route::apiResource('users',UserController::class);
    Route::get('search/users/{query}', [UserController::class,'search']);
    Route::get('get-all-users/', [UserController::class, 'getAllUser']);

    Route::apiResource('categories',CategoryController::class);
    Route::get('search/categories/{query}', [CategoryController::class,'search']);

    //SESSIONS MODULE
    Route::group(['prefix' => 'sessions'],function () {
        Route::post('list',[SessionsController::class,'index']);
        Route::post('create',[SessionsController::class,'store']);
        Route::post('update/{id}',[SessionsController::class,'update']);
        Route::get('by-id/{id}',[SessionsController::class,'byId']);
    });

    Route::apiResource('student',StudentController::class);
    Route::get('search/student/{query}', [StudentController::class,'search']);
    Route::get('get-schedule-data/{session_id}/{category_id}', [StudentController::class,'getScheduleData']);
    Route::get('get-student-wise-schedule-data/{student_id}', [StudentController::class,'getStudentWiseScheduleData']);
    Route::post('student-details', [StudentController::class,'getStudentDetails']);

    //teachers
    Route::apiResource('teachers',TeacherController::class);
    Route::get('search/teachers/{query}', [TeacherController::class,'search']);

    //evaluation statement
    Route::apiResource('statements',EvaluationStatementController::class);
    Route::get('search/statements/{query}', [EvaluationStatementController::class,'search']);

    //departments
    Route::apiResource('departments',DepartmentController::class);
    Route::get('search/departments/{query}', [DepartmentController::class,'search']);

    //designation
    Route::apiResource('designations',DesignationController::class);
    Route::get('search/designations/{query}', [DesignationController::class,'search']);


    //survey controller
    Route::post('survey-submit', [TeacherSurveyController::class,'surveySubmitted']);

    //report
    Route::get('survey-list', [ReportController::class,'getSurveyList']);
    Route::get('get-teachers-evaluation-details', [ReportController::class,'getTeacherDetails']);
    Route::get('get-single-teachers-evaluation', [ReportController::class,'getSingleTeacherEvaluation']);
    Route::get('get-single-teachers-print/{id}', [ReportController::class,'getSingleTeacherEvaluationPrint']);

    //report
    Route::get('/dashboard', [StudentDashboardController::class, 'dashboard']);

    //menu resource route
    Route::apiResource('menu', MenuController::class);
    Route::get('search/menu/{query}', [MenuController::class,'search']);
    Route::get('get-all-menu', [MenuController::class,'getAllMenu']);

    //menu permission route
    Route::get('get-user-menu-details/{UserID}', [MenuPermissionController::class, 'getUserMenuPermission']);
    Route::get('sidebar-get-all-user-menu', [MenuPermissionController::class,'getSidebarAllUserMenu']);
    Route::post('save-user-menu-permission', [MenuPermissionController::class,'saveUserMenuPermission']);

    Route::get('get-all-session', [CommonController::class,'getAllSession']);
    Route::get('get-all-category', [CommonController::class,'getAllCategory']);
    Route::get('get-all-role', [CommonController::class,'getAllRole']);
    Route::get('get-all-department', [CommonController::class,'getAllDepartment']);
    Route::get('get-all-designation', [CommonController::class,'getAllDesignation']);
    Route::get('get-all-subject', [CommonController::class,'getAllSubject']);

    //new route
    Route::get('get-all-statement', [CommonController::class,'getAllStatement']);
    Route::get('get-all-teacher', [CommonController::class,'getAllTeacher']);
    Route::get('get-all-year', [CommonController::class,'getAllYear']);

    //report
    Route::get('teacher-wise-average-rating', [ReportController::class,'teacherWiseAverageRating']);
    Route::get('student-wise-participation-report', [ReportController::class,'studentWiseParticipationReport']);
    Route::get('question-wise-analysis', [ReportController::class,'questionWiseAnalysis']);
    Route::get('dashboard-data', [DashboardController::class, 'dashboardData']);
    Route::get('top-five-teacher', [DashboardController::class,'topFiveTeacher']);
    Route::get('lowest-five-teacher', [DashboardController::class,'lowestFiveTeacher']);
    Route::get('get-chart-data', [DashboardController::class,'getChartData']);

    //settings
    Route::post('change-password', [SettingController::class, 'changePassword']);
    Route::post('student-change-password', [StudentAuthController::class, 'changePassword']);

    Route::group(['prefix' => 'report'],function () {
        //Route::get('student-payment',[ReportController::class,'studentPayment']);
    });

//    //admin module
//    // Menu routes
//    Route::get('get-user-menu', [MenuController::class, 'getUserMenus']);
//    Route::apiResource('menus', MenuController::class);
//    Route::get('/roles', [MenuController::class, 'getRoleMenus']);
//    Route::get('/permissions', [MenuPermissionController::class, 'getPermissions']);
//    Route::put('menus/roles/{role}', [MenuController::class, 'updateRoleMenus']);

});

// Protected routes
Route::middleware(['jwt:api'])->group(function () {
    // Menu routes
    Route::get('get-user-menu', [MenuController::class, 'getUserMenus']);
    Route::get('menus/roles', [MenuController::class, 'getRoleMenus']);
    Route::put('menus/roles/{roleId}', [MenuController::class, 'updateRoleMenus']);
    Route::post('menus/reorder', [MenuController::class, 'reorder']);
    Route::patch('menus/{id}/toggle-status', [MenuController::class, 'toggleStatus']);
    Route::apiResource('menus', MenuController::class);

    // Role routes
    Route::apiResource('roles', RoleController::class)->only(['index', 'show']);

    // Permission routes
    Route::get('permissions', [PermissionController::class, 'index']);
    Route::get('permissions/grouped', [PermissionController::class, 'grouped']);


    Route::prefix('users')->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{id}', [UserController::class, 'show']);
        Route::post('/{id}', [UserController::class, 'update']); // POST for file upload
        Route::post('/{id}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::delete('/{id}', [UserController::class, 'destroy']);
    });

    Route::get('/roles', [UserController::class, 'getRoles']);

    Route::prefix('teachers')->group(function () {
        Route::get('/', [TeacherController::class, 'index']);
        Route::post('/', [TeacherController::class, 'store']);
        Route::get('/{id}', [TeacherController::class, 'show']);
        Route::put('/{id}', [TeacherController::class, 'update']);
        Route::post('/{id}/toggle-head', [TeacherController::class, 'toggleHead']);
        Route::delete('/{id}', [TeacherController::class, 'destroy']);
    });

    Route::prefix('students')->group(function () {
        Route::get('/', [StudentController::class, 'index']);
        Route::post('/', [StudentController::class, 'store']);
        Route::get('/{id}', [StudentController::class, 'show']);
        Route::put('/{id}', [StudentController::class, 'update']);
        Route::delete('/{id}', [StudentController::class, 'destroy']);
    });

    Route::get('/student-users', [StudentController::class, 'getUsers']);

    Route::prefix('departments')->group(function () {
        Route::get('/', [DepartmentController::class, 'index']);
        Route::get('/{id}', [DepartmentController::class, 'show']);
        Route::post('/', [DepartmentController::class, 'store']);
        Route::put('/{id}', [DepartmentController::class, 'update']);
        Route::delete('/{id}', [DepartmentController::class, 'destroy']);
        Route::patch('/{id}/toggle-status', [DepartmentController::class, 'toggleStatus']);
        Route::get('/stats/all', [DepartmentController::class, 'statistics']);
        Route::post('/bulk-delete', [DepartmentController::class, 'bulkDelete']);
    });

    Route::prefix('subjects')->group(function () {
        Route::get('/', [SubjectController::class, 'index']);
        Route::post('/', [SubjectController::class, 'store']);
        Route::get('/{id}', [SubjectController::class, 'show']);
        Route::put('/{id}', [SubjectController::class, 'update']);
        Route::delete('/{id}', [SubjectController::class, 'destroy']);
    });

    Route::prefix('classrooms')->group(function () {
        Route::get('/', [ClassroomController::class, 'index']);
        Route::post('/', [ClassroomController::class, 'store']);
        Route::get('/stats', [ClassroomController::class, 'getStats']);
        Route::get('/{id}', [ClassroomController::class, 'show']);
        Route::put('/{id}', [ClassroomController::class, 'update']);
        Route::delete('/{id}', [ClassroomController::class, 'destroy']);
    });

    Route::prefix('schedules')->group(function () {
        Route::get('/', [ClassScheduleController::class, 'index']);
        Route::post('/', [ClassScheduleController::class, 'store']);
        Route::get('/form-data', [ClassScheduleController::class, 'getFormData']);
        Route::get('/weekly', [ClassScheduleController::class, 'getWeeklySchedule']);
        Route::get('/stats', [ClassScheduleController::class, 'getStats']);
        Route::get('/{id}', [ClassScheduleController::class, 'show']);
        Route::put('/{id}', [ClassScheduleController::class, 'update']);
        Route::delete('/{id}', [ClassScheduleController::class, 'destroy']);
    });

    Route::prefix('classes')->group(function () {
        Route::get('/', [ClassController::class, 'index']);
        Route::post('/', [ClassController::class, 'store']);
        Route::get('/statuses', [ClassController::class, 'getStatuses']);
        Route::get('/schedules', [ClassController::class, 'getSchedules']);
        Route::get('/daily-class-generate', [ClassController::class, 'dailyClassGenerate']);
        Route::get('/{id}', [ClassController::class, 'show']);
        Route::put('/{id}', [ClassController::class, 'update']);
        Route::delete('/{id}', [ClassController::class, 'destroy']);
    });

    // Enrollment Routes
    Route::prefix('enrollments')->group(function () {
        Route::get('/', [EnrollmentController::class, 'index']);
        Route::post('/', [EnrollmentController::class, 'store']);
        Route::get('/{id}', [EnrollmentController::class, 'show']);
        Route::put('/{id}', [EnrollmentController::class, 'update']);
        Route::delete('/{id}', [EnrollmentController::class, 'destroy']);

        // Additional routes
        Route::post('/bulk-enroll', [EnrollmentController::class, 'bulkEnroll']);
        Route::get('/statistics/all', [EnrollmentController::class, 'statistics']);
    });

    // Exam Management Routes
    Route::prefix('exams')->group(function () {
        Route::get('/', [ExamController::class, 'index']);
        Route::get('/upcoming', [ExamController::class, 'upcoming']);
        Route::post('/', [ExamController::class, 'store']);
        Route::get('/{id}', [ExamController::class, 'show']);
        Route::put('/{id}', [ExamController::class, 'update']);
        Route::delete('/{id}', [ExamController::class, 'destroy']);
        Route::post('/{id}/cancel', [ExamController::class, 'cancel']);

        Route::post('/{examId}/start', [ExamStatusController::class, 'startExam']);
        Route::post('/{examId}/complete', [ExamStatusController::class, 'completeExam']);
        Route::post('/{examId}/cancel', [ExamStatusController::class, 'cancelExam']);
        Route::post('/{examId}/reopen', [ExamStatusController::class, 'reopenExam']);
        Route::get('/{examId}/available-transitions', [ExamStatusController::class, 'availableTransitions']);

        // Auto-update (can be called manually or via cron)
        Route::get('/auto-update-status', [ExamStatusController::class, 'autoUpdateStatuses']);

        // Schedule routes
        Route::get('/schedule/department', [ExamController::class, 'schedule']);
        Route::get('/schedule/student/{studentId}', [ExamController::class, 'studentSchedule']);
        Route::get('/schedule/teacher/{teacherId}', [ExamController::class, 'teacherSchedule']);
    });

    Route::prefix('exams/{exam}/students')->group(function () {
        // Get all enrolled students
        Route::get('/', [ExamStudentController::class, 'index']);

        // Add single student
        Route::post('/', [ExamStudentController::class, 'store']);

        // Bulk add students by year/semester (MAIN FUNCTION)
        Route::post('/bulk/assign', [ExamStudentController::class, 'bulkAssign']);

        // Advanced bulk assign with filters
        Route::post('/bulk/assign/advanced', [ExamStudentController::class, 'bulkAssignAdvanced']);

        // Preview before bulk assign
        Route::get('/bulk/preview', [ExamStudentController::class, 'bulkAssignPreview']);

        // Auto assign seats
        Route::post('/assign-seats', [ExamStudentController::class, 'assignSeats']);

        // Get available students (not yet enrolled)
        Route::get('/available', [ExamStudentController::class, 'availableStudents']);

        // Update student (seat, eligibility)
        Route::put('/{student}', [ExamStudentController::class, 'update']);

        // Remove student from exam
        Route::delete('/{student}', [ExamStudentController::class, 'destroy']);
    });


    // Exam Results Routes
    Route::prefix('results')->group(function () {
        Route::post('/', [ExamResultController::class, 'store']);
        Route::post('/bulk', [ExamResultController::class, 'bulkStore']);
        //Route::get('/exam/{examId}', [ExamResultController::class, 'examResults']);
        Route::get('/student/{studentId}', [ExamResultController::class, 'studentResults']);
        Route::put('/{id}', [ExamResultController::class, 'update']);
    });

    // Attendance Routes
    Route::prefix('attendance')->group(function () {
        Route::post('/check-in', [ExamAttendanceController::class, 'checkIn']);
        Route::post('/{id}/check-out', [ExamAttendanceController::class, 'checkOut']);
//        Route::get('/exam/{examId}', [ExamAttendanceController::class, 'examAttendance']);
        Route::get('/exam/{examId}', [ExamAttendanceController::class, 'getExamAttendance']);
        Route::post('/mark-absent', [ExamAttendanceController::class, 'markAbsent']);
    });

    // Get dropdown data for form
    Route::get('exams-dropdown-data', [ExamController::class, 'getDropdownData']);

    Route::prefix('teacher-subjects')->group(function () {
        Route::get('/', [TeacherSubjectController::class, 'index']);
        Route::get('/create', [TeacherSubjectController::class, 'create']);
        Route::post('/', [TeacherSubjectController::class, 'store']);
        Route::post('/bulk-assign', [TeacherSubjectController::class, 'bulkAssign']);
        Route::post('/sync/{teacherId}', [TeacherSubjectController::class, 'syncSubjects']);
        Route::get('/{id}', [TeacherSubjectController::class, 'show']);
        Route::put('/{id}', [TeacherSubjectController::class, 'update']);
        Route::delete('/{id}', [TeacherSubjectController::class, 'destroy']);
        Route::get('/teacher/{teacherId}/subjects', [TeacherSubjectController::class, 'teacherSubjects']);
        Route::get('/subject/{subjectId}/teachers', [TeacherSubjectController::class, 'subjectTeachers']);
    });


    // Exam Supervisor Routes
    Route::prefix('exams/{examId}/supervisors')->group(function () {
        Route::get('/', [ExamSupervisorController::class, 'index']);
        Route::post('/', [ExamSupervisorController::class, 'store']);
        Route::put('/{supervisorId}', [ExamSupervisorController::class, 'update']);
        Route::delete('/{supervisorId}', [ExamSupervisorController::class, 'destroy']);
        Route::post('/bulk', [ExamSupervisorController::class, 'bulkStore']);
    });

    // Get available teachers for an exam
    Route::get('/exams/{examId}/available-teachers', [ExamSupervisorController::class, 'availableTeachers']);


    // Exam Results Routes
    Route::prefix('results')->group(function () {
        // Get results for an exam
        Route::get('/exam/{examId}', [ResultController::class, 'getExamResults']);

        // Bulk save results
        Route::post('/bulk', [ResultController::class, 'bulkStore']);

        // Single result operations
        Route::post('/', [ResultController::class, 'store']);
        Route::put('/{resultId}', [ResultController::class, 'update']);
        Route::delete('/{resultId}', [ResultController::class, 'destroy']);

        // Grade distribution
        Route::get('/exam/{examId}/grade-distribution', [ResultController::class, 'gradeDistribution']);

        // Student specific result
        Route::get('/student/{studentId}/exam/{examId}', [ResultController::class, 'studentResult']);

        // Publish/Unpublish results
        Route::post('/exam/{examId}/publish', [ResultController::class, 'publishResults']);
    });
    Route::get('/exams/{examId}/students', [ResultController::class, 'getExamStudents']);


//    Route::prefix('attendance')->group(function () {
//
//        // Get students list for marking attendance
//        Route::post('/students', [AttendanceController::class, 'getStudentsByClass']);
//
//        // Save bulk attendance
//        Route::post('/save-bulk', [AttendanceController::class, 'saveBulkAttendance']);
//
//        // Get attendance report with filters
//        Route::get('/report', [AttendanceController::class, 'getAttendanceReport']);
//
//        // Get attendance summary for a specific class
//        Route::get('/summary/{classId}', [AttendanceController::class, 'getClassSummary']);
//
//    });

    // ============================================
    // Teacher & Admin Routes - Attendance Management
    // ============================================
    Route::prefix('attendance')->group(function () {
        // Get students for a class
        Route::get('/class/{classId}/students', [AttendanceController::class, 'getStudentsForClass']);

        // Mark bulk attendance
        Route::post('/mark-bulk', [AttendanceController::class, 'markBulkAttendance']);

        // Mark all present
        Route::post('/class/{classId}/mark-all-present', [AttendanceController::class, 'markAllPresent']);

        // Get class attendance
        Route::get('/class/{classId}', [AttendanceController::class, 'getClassAttendance']);

        // Update single attendance
        Route::put('/{attendanceId}', [AttendanceController::class, 'updateAttendance']);

        // Delete attendance
        Route::delete('/{attendanceId}', [AttendanceController::class, 'deleteAttendance']);
    });

    // ============================================
    // Admin Routes - Reports & Analytics
    // ============================================
    Route::prefix('attendance/reports')->group(function () {
        // General attendance report with filters
        Route::get('/', [AttendanceReportController::class, 'getReport']);

        // Student-wise summary
        Route::get('/students', [AttendanceReportController::class, 'getStudentSummary']);

        // Subject-wise report
        Route::get('/subjects', [AttendanceReportController::class, 'getSubjectWiseReport']);

        // Department-wise report
        Route::get('/departments', [AttendanceReportController::class, 'getDepartmentWiseReport']);

        // Dashboard analytics
        Route::get('/dashboard', [AttendanceReportController::class, 'getDashboardAnalytics']);
    });

    // ============================================
    // Student Routes - View Own Attendance
    // ============================================
    Route::prefix('my-attendance')->group(function () {
        // Get overall attendance summary
        Route::get('/summary', [StudentAttendanceController::class, 'getMyAttendance']);

        // Get monthly attendance
        Route::get('/monthly', [StudentAttendanceController::class, 'getMonthlyAttendance']);

        // Get subject-wise attendance
        Route::get('/subjects', [StudentAttendanceController::class, 'getSubjectWiseAttendance']);

        // Get attendance history
        Route::get('/history', [StudentAttendanceController::class, 'getAttendanceHistory']);

        // Get attendance warnings
        Route::get('/warnings', [StudentAttendanceController::class, 'getAttendanceWarnings']);
    });

    // Get all departments
    //Route::get('/departments', [ClassController::class, 'getDepartments']);

    // Get subjects by department
    Route::get('/departments/{departmentId}/subjects', [ClassController::class, 'getSubjectsByDepartment']);

    // Get classes with filters
    Route::get('/classes', [ClassController::class, 'getClasses']);

    // Get single class details
    Route::get('/classes/{classId}', [ClassController::class, 'getClassDetails']);


    Route::prefix('notices')->group(function () {

        // Notice statistics
        Route::get('/statistics', [NoticeController::class, 'statistics']);

        // Toggle notice status
        Route::patch('/{id}/toggle-status', [NoticeController::class, 'toggleStatus']);

        // Standard CRUD operations
        Route::apiResource('/', NoticeController::class);
    });

// Public routes (for viewing active notices without authentication)
    Route::get('/public/notices', [NoticeController::class, 'index'])->name('notices.public');
    Route::get('/public/notices/{id}', [NoticeController::class, 'show'])->name('notices.public.show');

    //common route
    Route::get('/get-session', [CommonController::class, 'getAllSession']);
    Route::get('/get-category', [CommonController::class, 'getAllCategory']);
    Route::get('/get-departments', [CommonController::class, 'getDepartments']);
    Route::get('/designations', [CommonController::class, 'getDesignations']);
    Route::get('/teacher-users', [CommonController::class, 'getUsers']);
    Route::get('/get-program', [CommonController::class, 'getProgram']);
    Route::get('/get-students', [CommonController::class, 'getStudents']);
    Route::get('/get-subjects', [CommonController::class, 'getAllSubject']);
    Route::get('/get-exam-types', [CommonController::class, 'getAllExamType']);
    Route::get('/get-teacher', [CommonController::class, 'getAllTeacher']);
    Route::get('/get-classroom', [CommonController::class, 'getAllClassRoom']);
    Route::get('/teachers/{teacher}', [CommonController::class, 'show']);
    Route::get('/subjects/{subject}/teachers', [CommonController::class, 'getTeachersBySubject']);

});

Route::middleware(['middleware' => ['jwt:api']])->prefix('reports')->group(function () {

    // Student Result Reports
    Route::prefix('results')->group(function () {
        Route::get('/', [StudentResultReportController::class, 'index']);
        Route::get('/export', [StudentResultReportController::class, 'export']);
    });

    // Student Attendance Reports
    Route::prefix('attendance')->group(function () {
        Route::get('/', [StudentAttendanceReportController::class, 'index']);
        Route::get('/summary', [StudentAttendanceReportController::class, 'summary']);
    });

    // Class Schedule Reports
    Route::prefix('class-schedule')->group(function () {
        Route::get('/', [ClassScheduleReportController::class, 'index']);
        Route::get('/weekly', [ClassScheduleReportController::class, 'weekly']);
    });

    // Notice Reports
    Route::prefix('notices')->group(function () {
        Route::get('/', [NoticeReportController::class, 'index']);
        Route::get('/{id}', [NoticeReportController::class, 'show']);
    });

    // Exam Schedule Reports
    Route::prefix('exam-schedule')->group(function () {
        Route::get('/', [ExamScheduleReportController::class, 'index']);
        Route::get('/calendar', [ExamScheduleReportController::class, 'calendar']);
    });
});


