<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassScheduleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\EvaluationStatementController;
use App\Http\Controllers\ExamAttendanceController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamResultController;
use App\Http\Controllers\ExamStudentController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuPermissionController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SessionsController;
use App\Http\Controllers\StudentAuthController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentDashboardController;
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

        // Schedule routes
        Route::get('/schedule/department', [ExamController::class, 'schedule']);
        Route::get('/schedule/student/{studentId}', [ExamController::class, 'studentSchedule']);
        Route::get('/schedule/teacher/{teacherId}', [ExamController::class, 'teacherSchedule']);
    });

    // Exam Results Routes
    Route::prefix('results')->group(function () {
        Route::post('/', [ExamResultController::class, 'store']);
        Route::post('/bulk', [ExamResultController::class, 'bulkStore']);
        Route::get('/exam/{examId}', [ExamResultController::class, 'examResults']);
        Route::get('/student/{studentId}', [ExamResultController::class, 'studentResults']);
        Route::put('/{id}', [ExamResultController::class, 'update']);
    });

    // Attendance Routes
    Route::prefix('attendance')->group(function () {
        Route::post('/check-in', [ExamAttendanceController::class, 'checkIn']);
        Route::post('/{id}/check-out', [ExamAttendanceController::class, 'checkOut']);
        Route::get('/exam/{examId}', [ExamAttendanceController::class, 'examAttendance']);
    });

    // Exam CRUD routes
    //Route::apiResource('exams', ExamController::class);

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

    Route::prefix('exams/{exam_id}/students')->group(function () {
        // Get all students for an exam
        Route::get('/', [ExamStudentController::class, 'index']);

        // Get available students (not assigned)
        Route::get('/available', [ExamStudentController::class, 'availableStudents']);

        // Get statistics
        Route::get('/statistics', [ExamStudentController::class, 'statistics']);

        // Assign students to exam (bulk)
        Route::post('/assign', [ExamStudentController::class, 'assignStudents']);

        // Auto-generate seat numbers
        Route::post('/generate-seats', [ExamStudentController::class, 'autoGenerateSeats']);

        // Bulk update attendance
        Route::post('/bulk-attendance', [ExamStudentController::class, 'bulkUpdateAttendance']);

        // Update single student
        Route::put('/{id}', [ExamStudentController::class, 'update']);

        // Remove student from exam
        Route::delete('/{id}', [ExamStudentController::class, 'destroy']);
    });


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


