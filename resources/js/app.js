
/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');


import Vue from 'vue';

// import route js file
import router from './ownerRoutes';

Vue.component('app', require('./components/App.vue').default);
Vue.component('dashboard', require('./components/dashboard/Dashboard.vue').default);

const app = new Vue({
    data:{
        meun:[{}]
    },
    el: '#app',
    router // router:router
});
