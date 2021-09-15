require('./bootstrap');

import Vue from 'vue';

Vue.component('auth', require('./components/auth/Main.vue').default);

const app = new Vue({
    el: '#app',
});


