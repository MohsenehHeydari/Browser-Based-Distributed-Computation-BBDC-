import VueRouter  from 'vue-router';
// import DefaultView from './components/dashboard/DashboardDefaultView.vue';

import ListDevices from './components/worker/ListDevices.vue';
import DeviceForm from './components/worker/DeviceForm.vue';

import JobList from './components/jobs/JobList.vue';
import JobProcess from './components/jobs/Process.vue'


export default new VueRouter({
    // mode:'history',  it dosen't define any # to aplit server side address and client side address
    routes:[
        {
            path: '/home',
            component: {template:`<div> <h1> report everything needed</h1></div>`},
            name: 'home',
            meta:{
                resource:'home',
                title: 'home',
                visible: true,
                icon: "fas fa-book",
            }
        },
        {
            path:'/devices',
            component: {template:`<div><router-view></router-view></div>`},
            meta:{
                resource:'devices',
                title: 'devices',
                visible: true,
                icon: "fas fa-book"
            },
            children:[
                {
                    path: '/',
                    component: ListDevices,
                    name: 'devices-list',
                    meta:{
                        resource:'devices',
                        title: 'list devices',
                        visible: true,
                        icon: "fas fa-list"
                    }
                },
                {
                    path:'add',
                    component:DeviceForm,
                    name:'devices-add',
                    meta:{
                        resource:'devices',
                        title: 'add a new device',
                        visible: true,
                        icon: "fas fa-plus"
                    }
                },
                {
                    path:'edit/:id',
                    component:DeviceForm,
                    name:'devices-edit',
                    meta:{
                        resource:'devices',
                        title: 'edit',
                        visible: false,
                        icon: "fas fa-pencil"
                    }
                },
            ],
           
        },
       {
           path: '/jobs',
           component: {template: '<router-view></router-view>'},
           meta:{
            resource:'jobs',
            title: 'jobs',
            visible: false,
            icon: "fas fa-book"
            },
           children:[
            {
                path: 'jobList',
                component:JobList,
                name: 'jobList',
                meta:{
                    resource:'jobList',
                    title: 'list of jobs',
                    visible: true,
                    icon: "fas fa-list",
                }
            },
            {
                path: 'process/:id',
                component: JobProcess,
                name: 'process',
                meta:{
                    resource:'process',
                    title: 'process',
                    visible: false,
                    // icon: "fas fa-book",
                }
            },
           ]
        }
    ]
})