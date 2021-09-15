require('./bootstrap');

import Vue from 'vue';
// import route js file
import router from './workerRoutes';

Vue.component('dashboard', require('./components/dashboard/Dashboard.vue').default);

const app = new Vue({
    data:{
        menu:[{
            // icon, title, children, 
        }]
    },
    el: '#app',
    router // router:router
});


