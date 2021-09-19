<template>
    <div>
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index3.html" class="brand-link">
      <img :src="asset('dist/img/AdminLTELogo.png')" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">BBMR</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img :src="asset('dist/img/user2-160x160.jpg')" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">Worker 1</a>
        </div>
      </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" >


           <!-- <li class="nav-item" :class="$route.meta.resource === 'owner-jobs'?' menu-open':''"> -->
             <!-- like <a> create a link to a address that vue-router makes
             <router-link :to="{name:'home'}" class="nav-link" :active-class="'active'">
               <i class="nav-icon fas fa-book"></i>
              <p>Owner Job</p>
               <i class="right fas fa-angle-left"></i>
             </router-link>
              <ul class="nav nav-treeview">
                 <li class="nav-item">
                    <router-link :to="{name:'owner-jobs-create'}" class="nav-link" :active-class="'active'">
                      <i class="nav-icon fas fa-plus"></i>
                      <p>Create Owner Job</p>
                    </router-link>
                 </li>
                  <li class="nav-item">
                    <router-link :to="{name:'owner-jobs-list'}" class="nav-link" :active-class="'active'">
                      <i class="nav-icon fas fa-list"></i>
                      <p>List Owner Jobs</p>
                    </router-link>
                 </li>
              </ul> -->

          <!-- </li> -->
          <template v-for="(route, index) in routes">
              <menu-item v-if="route.meta.visible === true"  :route="route"  :key="'parent-'+index" :isMainMenu="true" :parentIndex="index"></menu-item>
              <template v-else-if="route.children !== undefined">
                <template v-for="(child, child_index) in route.children">
                   <menu-item   v-if="child.meta.visible === true"  :route="child"  :key="'child-'+child_index+'-'+index" :isMainMenu="true" :parentIndex="child_index"></menu-item>
                </template>
              </template>
          </template>
         
          
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->

<!--          <li class="nav-item">-->
<!--            <a href="pages/widgets.html" class="nav-link">-->
<!--              <i class="nav-icon fas fa-th"></i>-->
<!--              <p>-->
<!--                Widgets-->
<!--                <span class="right badge badge-danger">New</span>-->
<!--              </p>-->
<!--            </a>-->
<!--          </li>-->
<!--          <li class="nav-item">-->
<!--            <a href="#" class="nav-link">-->
<!--              <i class="nav-icon fas fa-copy"></i>-->
<!--              <p>-->
<!--                Layout Options-->
<!--                <i class="fas fa-angle-left right"></i>-->
<!--                <span class="badge badge-info right">6</span>-->
<!--              </p>-->
<!--            </a>-->
<!--            <ul class="nav nav-treeview">-->
<!--              <li class="nav-item">-->
<!--                <a href="pages/layout/top-nav.html" class="nav-link">-->
<!--                  <i class="far fa-circle nav-icon"></i>-->
<!--                  <p>Top Navigation</p>-->
<!--                </a>-->
<!--              </li>-->
<!--              <li class="nav-item">-->
<!--                <a href="pages/layout/top-nav-sidebar.html" class="nav-link">-->
<!--                  <i class="far fa-circle nav-icon"></i>-->
<!--                  <p>Top Navigation + Sidebar</p>-->
<!--                </a>-->
<!--              </li>-->
<!--              <li class="nav-item">-->
<!--                <a href="pages/layout/boxed.html" class="nav-link">-->
<!--                  <i class="far fa-circle nav-icon"></i>-->
<!--                  <p>Boxed</p>-->
<!--                </a>-->
<!--              </li>-->
<!--              <li class="nav-item">-->
<!--                <a href="pages/layout/fixed-sidebar.html" class="nav-link">-->
<!--                  <i class="far fa-circle nav-icon"></i>-->
<!--                  <p>Fixed Sidebar</p>-->
<!--                </a>-->
<!--              </li>-->
<!--              <li class="nav-item">-->
<!--                <a href="pages/layout/fixed-sidebar-custom.html" class="nav-link">-->
<!--                  <i class="far fa-circle nav-icon"></i>-->
<!--                  <p>Fixed Sidebar <small>+ Custom Area</small></p>-->
<!--                </a>-->
<!--              </li>-->
<!--              <li class="nav-item">-->
<!--                <a href="pages/layout/fixed-topnav.html" class="nav-link">-->
<!--                  <i class="far fa-circle nav-icon"></i>-->
<!--                  <p>Fixed Navbar</p>-->
<!--                </a>-->
<!--              </li>-->
<!--              <li class="nav-item">-->
<!--                <a href="pages/layout/fixed-footer.html" class="nav-link">-->
<!--                  <i class="far fa-circle nav-icon"></i>-->
<!--                  <p>Fixed Footer</p>-->
<!--                </a>-->
<!--              </li>-->
<!--              <li class="nav-item">-->
<!--                <a href="pages/layout/collapsed-sidebar.html" class="nav-link">-->
<!--                  <i class="far fa-circle nav-icon"></i>-->
<!--                  <p>Collapsed Sidebar</p>-->
<!--                </a>-->
<!--              </li>-->
<!--            </ul>-->
<!--          </li>-->

<!--          <li class="nav-header">MISCELLANEOUS</li>-->

            <li class="nav-item">
                <a @click.prevent="logout" class="nav-link">
                    <i class="nav-icon fas fa-arrow-circle-down"></i>
                    <p>
                        Logout
                    </p>
                </a>
            </li>

        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>
    </div>
</template>

<script>
import GlobalMethods from '../../helpers/GlobalMethods.vue'
import MenuItem from './MenuItem.vue';

import axios from 'axios';
export default{
  components:{MenuItem},
  mixins:[GlobalMethods],
  data(){
    return{}
  },

  inject:['type'],
  computed:{
    routes() {
      return this.$router.options.routes;
    },
      baseUrl() {
          let getUrl = window.location;
          return getUrl.protocol + "//" + getUrl.host;
      },
  },
    methods:{

        logout(){
            let logoutUrl='/'+this.type+'/logout';
            axios.post(logoutUrl).then(respone=>{
                window.location=this.baseUrl+'/'+this.type+'/login';
            })
        }
    }

}
</script>
