import VueRouter  from 'vue-router';

import DefaultView from './components/dashboard/DashboardDefaultView.vue';

import CreateOwnerJobForm from './components/job_owner/CreateOwnerJob.vue';
import ListOwnerJobs from './components/job_owner/ListOwnerJobs.vue';




export default new VueRouter({
    // mode:'history',  it dosen't define any # to aplit server side address and client side address
    routes:[
        {
            path: '/home',
            component: DefaultView,
            name: 'home',
            meta:{
                resource:'home',
                title: 'home',
                visible: true,
                icon: "fas fa-book"
            }
        },
        {
            path:'/owner-jobs',
            component: {template:`<div><h1>owner jobs</h1><router-view></router-view></div>`},
            // name: 'owner-jobs',
            children:[
                {
                    path: '/',
                    component: {template:`<div> job owner</div>`},
                    name: 'owner-jobs-home',
                    meta:{
                        resource:'owner-jobs',
                        title: 'owner jobs',
                        visible: false,
                        icon: "fas fa-book"
                    }
                },
                {
                    path:'create',
                    component:CreateOwnerJobForm,
                    name:'owner-jobs-create',
                    meta:{
                        resource:'owner-jobs',
                        title: 'create a new job',
                        visible: true,
                        icon: "fas fa-plus"
                    }
                },
                {
                    path:'list',
                    component:ListOwnerJobs,
                    name:'owner-jobs-list',
                    meta:{
                        resource:'owner-jobs',
                        title: 'list jobs',
                        visible: true,
                        icon: "fas fa-list"
                    }
                },
                
                {
                    path:'edit/{id}',
                    component:{template:`<div> job owner edit</div>`},
                    name:'owner-jobs-edit',
                    meta:{
                        resource:'owner-jobs',
                        title: 'edit',
                        visible: false,
                        icon: "fas fa-pencil"
                    }
                },
            ],
            meta:{
                resource:'owner-jobs',
                title: 'owner jobs',
                visible: true,
                icon: "fas fa-book"
            }
        },
        
    ]
})