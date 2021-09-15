<template>
    <div class="card">
              <div class="card-header">
                <h3 class="card-title">List of jobs you have created</h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body p-0">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th style="width: 10px">#</th>
                      <th>name</th>
                      <th>Job category</th>
                      <th>Description</th>
                      <th>Expire date</th>
                      <th>Map task file</th>
                      <th>Reduce task file</th>
                      <th>Status</th>
                      <th>Final result file</th>
                      <th style="width: 150px">Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(ownerJob, index) in ownerJobList" :key="ownerJob.id" >
                      <td>{{index+1}}.</td>
                      <td>{{ownerJob.name}}</td>
                      <td>{{ownerJob.category}}</td>
                      <td>{{ownerJob.description}}</td>
                      <td>{{ownerJob.expire_date}}</td>
                      <td>{{ownerJob.mapTaskUrl}}</td>
                      <td>{{ownerJob.reduceTaskUrl}}</td>
                      <td>{{ownerJob.status}}</td>
                      <td>{{ownerJob.final_result}}</td>
                      <td>
                        <span @click ="$router.push({name: 'owner-jobs-edit', params: {id: ownerJob.id}})" title ="Edit" style="padding-right: 10px; cursor:pointer;" class="text-success">
                            <i class="fas fa-edit"></i> 
                        </span>
                        <span @click="deleteOwnerJob(ownerJob)" title ="delete" style="padding-right: 5px; cursor:pointer;" class="text-danger">
                            <i class="fas fa-trash"></i> 
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
    return {
      ownerJobList : [],
      }
  },
  methods:{
    getOwnerJobs(){
      axios.get("/owner/owner_jobs/list")
      .then((response) => {
        this.ownerJobList = response.data.ownerJobList;
        // console.log(response.data.ownerJobList,' owner job list');
      })
      .catch((error) => {
        console.log(error);
      });
    },
    deleteOwnerJob(ownerJob){
            axios.post('/owner/owner_jobs/delete/'+ownerJob.id, { _method:'delete'})
            .then((response)=>{
                console.log(response.data.message);
                this.getOwnerJobs();
            })
        },

  },
  created(){
        this.getOwnerJobs();
  }
    
}
</script>
