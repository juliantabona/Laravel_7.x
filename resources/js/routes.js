//  Import Vue Router
import VueRouter from 'vue-router';

//  Set routes
let routes = [

    //  Login
    {
        alias: '/',
        path: '/login', name: 'login',
        meta: { layout: 'Basic', middlewareGuest: true },
        component: require('./views/auth/login/main.vue').default
    },

    //  Register
    {
        alias: '/signup',
        path: '/register', name: 'register',
        meta: { layout: 'Basic', middlewareGuest: true },
        component: require('./views/auth/register/main.vue').default
    },

    //  Register
    {
        path: '/forgot-password', name: 'forgot-password',
        meta: { layout: 'Basic', middlewareGuest: true },
        component: require('./views/auth/forgot-password/main.vue').default
    },

    //  Register
    {
        path: '/reset-password', name: 'reset-password',
        meta: { layout: 'Basic', middlewareGuest: true },
        component: require('./views/auth/reset-password/main.vue').default
    },

    //  Projects
    {
        path: '/projects', name: 'show-projects',
        meta: { layout: 'Dashboard', middlewareAuth: true },
        component: require('./views/projects/list/main.vue').default
    },

   //  Create Project
   {
        path: '/projects/create', name: 'create-project',
        meta: { layout: 'Dashboard', middlewareAuth: true },
        component: require('./views/projects/create/main.vue').default
    },

    //  Single Project Overview
    {
        path: '/projects/:project_url/overview',
        meta: { layout: 'Dashboard', middlewareAuth: true },
        component: require('./views/projects/show/main.vue').default,
        children: [
            {
                path: '/', name: 'show-project-overview',
                meta: { layout: 'Dashboard', middlewareAuth: true },
                component: require('./views/projects/show/builder/versions/list/main.vue').default
            }
        ]
    },

    //  Single Project Builder
    {
        path: '/projects/:project_url/builder',
        meta: { layout: 'Dashboard', middlewareAuth: true },
        component: require('./views/projects/show/main.vue').default,
        children: [
            {
                path: '/', name: 'show-project-builder',
                meta: { layout: 'Dashboard', middlewareAuth: true },
                component: require('./views/projects/show/builder/overview/editProject.vue').default
            },
            {
                path: 'sessions', name: 'show-project-sessions',
                meta: { layout: 'Dashboard', middlewareAuth: true },
                component: require('./views/projects/show/builder/sessions/list/main.vue').default
            },
            {
                path: 'analytics', name: 'show-project-analytics',
                meta: { layout: 'Dashboard', middlewareAuth: true },
                component: require('./views/projects/show/builder/analytics/list/main.vue').default
            },
            {
                path: 'versions', name: 'show-project-versions',
                meta: { layout: 'Dashboard', middlewareAuth: true },
                component: require('./views/projects/show/builder/versions/list/main.vue').default
            },
            {
                path: 'versions/:version_url', name: 'show-project-version',
                meta: { layout: 'Dashboard', middlewareAuth: true },
                component: require('./views/projects/show/builder/versions/show/main.vue').default
            },
        ]
    }
];

//  Initialise the router
const router = new VueRouter({
    routes
});

/** We can use the beforeEach() method to perform authentication. This means
 *  before accessing a given route, we can check if the route requires an
 *  authenticated user, if it does, then we just check if the user is
 *  authenticated. If the are not, we can redirect them back to the
 *  login page.
 */
router.beforeEach((to, from, next) => {

    console.log('From URL: '+ from.fullPath);
    console.log('To URL: '+ to.fullPath);

    /** Retrieve the matched route and check if it has meta.middlewareAuth set to true or set at all.
     *  If it's set to true it means we require the user to be authenticated to access the route and
     *  if they're not we're redirecting them to the login page.
     */      
    if (to.matched.some(record => record.meta.middlewareAuth)) {     
        //  Check if user is authenticated
        if (!auth.isAuthenticated()) {
            
            console.log('The user is not authenticated');
            
            console.log('We must go to the login page');

            /** Redirect to the login page. Save the current url so that 
             *  we can redirect back after we login
             */ 
            next({
                name: 'login',
                query: { redirect: to.fullPath }
            });

            return;
        }
    }

    console.log('beforeEach: Check if the user is authenticated');
    console.log('auth.isAuthenticated()');
    console.log(auth.isAuthenticated());

    /** Retrieve the matched route and check if it has meta.middlewareGuest set to true or set at all.
     *  If it's set to true it means the authenticated user cannot access the route and
     *  if they are we're redirecting them to the dashboard overview page
     */     
    if (to.matched.some(record => record.meta.middlewareGuest)) {     
        //  Check if user is authenticated
        if (auth.isAuthenticated()) {

            
            console.log('The user is authenticated');
            
            console.log('We must go to the projects page');

            /** Redirect to the projects page
             */ 
            next({
                name: 'show-projects',
            });

            return;
        }
    }

    next();
})

export default router;