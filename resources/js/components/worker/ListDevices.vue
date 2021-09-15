<template>
    <div class="card">
              <div class="card-header">
                <h3 class="card-title">List of Devices</h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body p-0">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th style="width: 10px">#</th>
                      <th>name</th>
                      <th>CPU usage</th>
                      <th style=""></th>
                      <th style="width: 150px">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(device, index) in devices" :key="device.id" >
                      <td>{{index+1}}.</td>
                      <td>{{device.name}}</td>
                      <td>
                        <div :title="device.CPU+'%'" class="progress progress-xs">
                          <div class="progress-bar progress-bar-danger" :style="'width:'+device.CPU+'%'"></div>
                        </div>
                      </td>
                      <td>
                          <span class="badge bg-info" >{{device.CPU}}%</span></td>
                      <td>
                        <span @click ="$router.push({name: 'devices-edit', params: {id: device.id}})" title ="Edit" style="padding-right: 10px; cursor:pointer;" class="text-success">
                            <i class="fas fa-edit"></i>
                        </span>
                        <span @click="deleteDevice(device)" title ="delete" style="padding-right: 5px; cursor:pointer;" class="text-danger">
                            <i class="fas fa-trash"></i>
                        </span>
                        <span @click="setCurrentDevice(device)" title ="select" style="padding-right: 5px; cursor:pointer;" class="text-info">
                            <i class="fas fa-check"></i>
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
</template>

<script>
import axios from 'axios'; 

export default{
    props:[],
    inject:['user','setDeviceId'],
    data(){
        return {
            devices:[]
        }

    },

    methods:{
        getDevices(){
            axios.get('/worker/devices/list')
            .then(
                (response)=>{
                    this.devices = response.data.devices;
                    console.log(this.devices);
            })
            .catch(console.log);
        },
        setCurrentDevice(device){
            console.log('set current device method');
            this.setCookie('device-id',device.id,1);
            this.setDeviceId(device.id);
            this.$router.push({name:'jobList'});
        },
        deleteDevice(device){
            axios.post('/worker/devices/delete/'+device.id, { _method:'delete'})
            .then((response)=>{
                console.log(response.data.message);
                this.getDevices();
            })
        },
       setCookie(name, value, expire_days=1) {

            let data={
                name,value,expire_days // name:name (key,value are has the same name), value:value, expire_days: expire_days
            };
           axios.post('/set-cookie',data)
               .then(
                   (response)=>{
                      console.log(response.data.message)
                   })
               .catch(console.log);

        },
        
    },
    created(){
        this.getDevices();
    }
     
}
</script>
<style>
    .fa-custom-check:before {
    content: "\f2611";
}
</style>
