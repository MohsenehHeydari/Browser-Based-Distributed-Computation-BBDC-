<template>
  <div>
    <div class="wrapper">
      <!-- Navbar -->
      <dashboard-header></dashboard-header>

      <!-- Main Sidebar Container -->
      <dashborad-sidebar></dashborad-sidebar>

      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <dashborad-bread-crumbs></dashborad-bread-crumbs>
        <!-- /.content-header -->

        <!-- Main content -->
        <div class="content">
          <div class="container-fluid">
            <router-view></router-view> <!-- like <slot> it makes a place to put component according to routes -->
          </div>
        </div>
      </div>
      <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
      <!-- Control sidebar content goes here -->
    </aside>
    <!-- /.control-sidebar -->

    <!-- Main Footer -->
    <dashborad-footer></dashborad-footer>
    <!-- ./wrapper -->
  </div>
</template>

<script>
import DashboardHeader from "./DashboardHeader.vue";
// import DashboardMainContainer from "./job_owner/CreateOwnerJob.vue";
import DashboradBreadCrumbs from "./DashboradBreadCrumbs .vue";
import DashboradFooter from "./DashboradFooter.vue";
import DashboradSidebar from "./DashboradSidebar.vue";

import GlobalMethods from "../../helpers/GlobalMethods.vue";
export default {
  components: {
    DashboardHeader,
    DashboradFooter,
    DashboradSidebar,
    DashboradBreadCrumbs,
  
  },
  mixins: [GlobalMethods],
  props:{
    default_device_id:Number,
    user: Object,
    type: String
    },
  data() {
    return {
      device_id: null,
    };
  },
  computed:{
    
  },
  methods: {
    getDeviceId(){
      return this.device_id;
    },
    setDeviceId(device_id){
      this.device_id = device_id;
    }
  },
  provide(){
    return {
      getDeviceId: this.getDeviceId,
      user: this.user,
      type: this.type,
      setDeviceId: this.setDeviceId,
    }
  },
  created(){
    if (![null, undefined, '', 0, false].includes(this.default_device_id)) {
      this.device_id=this.default_device_id;
    }
  }
};
</script>
