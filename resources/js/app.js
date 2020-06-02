/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Vue = require('vue');

//  Import Vue Router for custom routes and navigation
import VueRouter from 'vue-router';
import router from './routes.js';

Vue.use(VueRouter)

//  Import View UI for frontend UI design
import ViewUI from 'view-design';
import 'view-design/dist/styles/iview.css';
import locale from 'view-design/dist/locale/en-US';

Vue.use(ViewUI, { locale });

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

//  Basic App Scaffolding
import App from './App.vue';

//  Import Api For Api handling [A custom js file we created]
import Api from './api.js';

window.api = new Api();

//  Import Auth For Authentication handling [A custom js file we created]
import Auth from './auth.js';

window.auth = new Auth();
/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = new Vue({
    //  Place the app scaffolding in this element
    el: '#app',
    //  Render the app scaffolding
    render(h){
        return h(App);
    },
    //  Add our custom routes
    router
});

/** Save the current Vue Instance. We can use this instance inside other js files
 *  e.g api.js to call on iView methods e.g We can notify a warning message to
 *  the user in the following manner:  
 * 
 *  VueInstance.$Notice.warning({ title: 'Session expired!' });
 * 
 */
window.VueInstance = app;
