<template>
    <div class="card">
              <div class="card-header">
                <h3 class="card-title">Select a job you interested to start processing</h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body p-0">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th style="width: 10px">#</th>
                      <th>name</th>
                      <th>Description</th>
                      <th style="width: 150px">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(job, index) in jobList" :key="job.id" >
                      <td>{{index+1}}.</td>
                      <td>{{job.name}}</td>
                      <td>{{job.description}}</td>
                      <td>
                        <span @click ="$router.push({name: 'process', params: {id: job.id}})" title ="start process" style="padding-right: 10px; cursor:pointer;" class="text-success">
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
    export default{
        data(){
           return{
               jobList: [],
           }
        },
        inject:['getDeviceId'],
        methods:{
            getJobs(){
                axios.get("/worker/jobs/list")
                .then((response) => {
                this.jobList = response.data.jobList;
                })
                .catch((error) => {
                console.log(error);
                });
            }
        },
        created(){
            console.log('jobList created');
            if([null,undefined,"",0,false].includes(this.getDeviceId())){
                // alert 'select device first'

                // console.log(this.getDeviceId(),'device_id');
                this.$router.push({name:'devices-list'});
            }
        },
        mounted(){
            this.getJobs();
        }
    }
</script>
