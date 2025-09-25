import Vue from 'vue'
import VueRouter from 'vue-router'
import Login from '../views/auth/Login.vue'
import Main from '../components/layouts/Main'
import Dashboard from '../views/dashboard/Index.vue'
import Sessions from '../views/sessions/Index'

import StudentList from "../views/students/Index"
import CategoryList from "../views/category/Index"
import UserList from '../views/users/Index'
import MenuList from '../views/menu/List'
import MenuPermission from '../views/users/MenuPermission'

//New
import TeacherList from "../views/teacher/Index"
import StatementList from "../views/statement/Index"
import DepartmentList from "../views/department/Index"
import DesignationList from "../views/designation/Index"
import YearList from "../views/year/Index"
//Report
import TeacherWiseAverageRating from "../views/report/TeacherWiseAverageRating"
import StudentWiseParticipationReport from "../views/report/StudentWiseParticipationReport"
import QuestionWiseAnalysis from "../views/report/QuestionWiseAnalysis"
import SurveyList from "../views/report/SurveyList"
import TeacherEvaluationPrint from "../views/report/TeacherEvaluationPrint"


//settings
import OneTimePasswordChange from "../views/settings/OneTimeChangePassword"

import NotFound from '../views/404/Index';
// import Profile from '../views/profile/Index';
import {baseurl} from '../base_url'

Vue.use(VueRouter);

const config = () => {
    let token = localStorage.getItem('token');
    return {
        headers: {Authorization: `Bearer ${token}`}
    };
}
const checkToken = (to, from, next) => {
    let token = localStorage.getItem('token');
    if (token === 'undefined' || token === null || token === '') {
        next(baseurl + 'login');
    } else {
        next();
    }
};

const activeToken = (to, from, next) => {
    let token = localStorage.getItem('token');
    if (token === 'undefined' || token === null || token === '') {
        next();
    } else {
        next(baseurl);
    }
};

const routes = [
    {
        path: baseurl,
        component: Main,
        redirect: {name: 'Dashboard'},
        children: [
            //DASHBAORD
            {
                path: baseurl + 'dashboard',
                name: 'Dashboard',
                component: Dashboard
            },
            //SESSION SETTINGS
            {path: baseurl + 'sessions-list', name: 'Sessions', component: Sessions},
            {path: baseurl + 'student-list', name: 'StudentList', component: StudentList},
            {path: baseurl + 'category-list', name: 'CategoryList', component: CategoryList},
            {path: baseurl + 'user-list', name: 'UserList', component: UserList},

            //menu vue route
            {path: baseurl + 'menu-list', name: 'MenuList', component: MenuList},
            {path: baseurl + 'user-menu-permission', name: 'UserMenuPermission', component: MenuPermission},

           //teacher
            {path: baseurl + 'teacher-list', name: 'TeacherList', component: TeacherList},
            {path: baseurl + 'statement-list', name: 'StatementList', component: StatementList},
            {path: baseurl + 'department-list', name: 'DepartmentList', component: DepartmentList},
            {path: baseurl + 'designation-list', name: 'DesignationList', component: DesignationList},
            {path: baseurl + 'year-list', name: 'YearList', component: YearList},
            //report
            {path: baseurl + 'teacher-wise-average-rating', name: 'TeacherWiseAverageRating', component: TeacherWiseAverageRating},
            {path: baseurl + 'student-wise-participation-report', name: 'StudentWiseParticipationReport', component: StudentWiseParticipationReport},
            {path: baseurl + 'question-wise-analysis', name: 'QuestionWiseAnalysis', component: QuestionWiseAnalysis},
            {path: baseurl + 'survey-list', name: 'SurveyList', component: SurveyList},
            {path: baseurl + 'get-single-teachers-print/:teacher_id', name: 'TeacherEvaluationPrint', component: TeacherEvaluationPrint},

            {path: baseurl + 'one-time-password-change', name: 'OneTimePasswordChange', component: OneTimePasswordChange},

        ],
        beforeEnter(to, from, next) {
            checkToken(to, from, next);
        }
    },
    {
        path: baseurl + 'login',
        name: 'Login',
        component: Login,
        beforeEnter(to, from, next) {
            activeToken(to, from, next);
        }
    },
    {
        path: baseurl + '*',
        name: 'NotFound',
        component: NotFound,
    },
]

const router = new VueRouter({
    mode: 'history',
    base: process.env.baseurl,
    routes
});

router.afterEach(() => {
    $('#preloader').hide();
});

router.beforeEach((to, from, next) => {
    let token = localStorage.getItem('token');
    let is_change_password = localStorage.getItem("is_change_password");
    if (token && is_change_password) {
        if (is_change_password === "N" && to.name !== "OneTimePasswordChange") {
            return next({ name: "OneTimePasswordChange" })
        }
        if (is_change_password === "Y" && to.name === "OneTimePasswordChange") {
            return next({ name: "Dashboard" })
        }
    }
    next()
});

export default router
