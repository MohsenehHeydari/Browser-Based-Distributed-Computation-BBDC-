require('./bootstrap');

import Vue from 'vue';
// import route js file
import router from './ownerRoutes';

Vue.component('dashboard', require('./components/dashboard/Dashboard.vue').default);

const app = new Vue({
    data:{
        menu:[{}]
    },
    el: '#app',
    router // router:router
});


