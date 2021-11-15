<template>
  <div class="card card-info">
    <div class="card-header">
      <h3 class="card-title">Creat a job</h3>
    </div>
    <!-- /.card-header -->
    <!-- form start -->
    <form class="form-horizontal" method="Post" action="test/test/test" @submit.prevent="sendFormData">
      <!-- /.card-body -->
      <div class="card-body" >

        <!-- job id field -->
        <div class="form-group row">
          <div class="col-sm-2"></div>
          <label for="job_id" class="col-sm-2 col-form-label">job Category</label>
          <div class="col-sm-5">
            <select v-model="model.job_id" class="form-control" id="job_id" name="job_id">
              <option v-for="job in jobs" :key="'job-'+job.id" :value='job.id'>
                {{job.name}}
              </option>
            </select>
            <template v-if="errors['job_id']!== undefined">
              <span class="error invalid-feedback" v-for="(error_message,index) in errors['job_id']" :key="'job_id'+index">{{error_message}}</span>
            </template>
          </div>
        </div>

        <!-- name filed -->
        <div class="form-group row">
          <div class="col-sm-2"></div>
          <label for="name" class="col-sm-2 col-form-label">Name</label>
          <div class="col-sm-5">
            <input v-model="model.name" type="text" class="form-control"
              :class= "[errors['name'] !== undefined  && validName === false  ? 'is-invalid' : '',  validName ? 'is-valid' : '']"
              id="name" name="name" placeholder="Enter your job name"/>
            
            <template v-if="errors['name']!== undefined">
              <span class="error invalid-feedback" v-for="(error_message,index) in errors['name']" :key="'name'+index">{{error_message}}</span>
            </template>
            </div>
        </div>

        <!-- select type of data -->
        <div class="form-group row">
          <div class="col-sm-2"></div>
          <label for="data_type" class="col-sm-2 col-form-label">Input data type </label>
          <div class="col-sm-5">
            <select v-model="data_type" class="form-control" id="data_type" name="data_type">
              <option value='file'>
                File
              </option>
              <option value='link'>
                Link
              </option>
              <option value='link_file'>
                File of links
              </option>
              <option value='data_value'>
                Value
              </option>
            </select>
          </div>
        </div>

        <!-- if data type is a file upload a file -->
        <div class="form-group row" v-if="data_type == 'file' || data_type == 'link_file'">
          <div class="col-sm-2"></div>
          <label for="dataFile" class="col-sm-2 col-form-label">Data File</label>
          <div class="col-sm-5">
            <div class="custom-file">
              <input type="file" name="data_file" class="custom-file-input" id="dataFile"  
              :class= "errors['data_file'] !== undefined  ? 'is-invalid' : ''"
              @change="$el.querySelector('#dataFile-label').textContent = $event.target.value"/>
              <label class="custom-file-label" id="dataFile-label" for="dataFile">Choose file</label>
              <template v-if="errors['data_file']!== undefined">
                <span class="error invalid-feedback" v-for="(error_message,index) in errors['data_file']" :key="'data_file'+index">{{error_message}}</span>
              </template>
            </div>
          </div>
        </div>
        <!-- if data type is link -->
        <div class="form-group row" v-else-if="data_type == 'link'">
          <div class="col-sm-2"></div>
          <label for="data_link" class="col-sm-2 col-form-label">Data File Link</label>
          <div class="col-sm-5">
            <input v-model="model.data_link" type="text" class="form-control"
              :class= "[errors['data_link'] !== undefined  && validName === false  ? 'is-invalid' : '']"
              id="data_link" name="data_link" placeholder="Enter your job file link"/>
            <template v-if="errors['data_link']!== undefined">
              <span class="error invalid-feedback" v-for="(error_message,index) in errors['data_link']" :key="'data_link'+index">{{error_message}}</span>
            </template>
            </div>
        </div>
        <!-- if data type is value -->
        <div class="form-group row" v-else>
          <div class="col-sm-2"></div>
          <label for="data_value" class="col-sm-2 col-form-label">Value</label>
          <div class="col-sm-5">
            <input v-model="model.data_value" type="text" class="form-control"
              :class= "[errors['data_value'] !== undefined  && validName === false  ? 'is-invalid' : '']"
              id="data_value" name="data_value" placeholder="Enter your job input data value"/>
            <template v-if="errors['data_value']!== undefined">
              <span class="error invalid-feedback" v-for="(error_message,index) in errors['data_value']" :key="'data_value'+index">{{error_message}}</span>
            </template>
            </div>
        </div>
        <!-- expire date field -->
        <div class="form-group row">
          <div class="col-sm-2"></div>
          <label for="expire_date" class="col-sm-2 col-form-label">Expire date</label>
          <div class="col-sm-5 " >
            <date-picker locale="en" name="expire_date" id="expire_date" 
              :class= "[errors['expire_date'] !== undefined  && validDate === false  ? 'is-invalid' : '',  validDate ? 'is-valid' : '']"
              v-model="model.expire_date"></date-picker>
            <template v-if="errors['expire_date']!== undefined">
              <span class="error invalid-feedback" v-for="(error_message,index) in errors['expire_date']" :key="'expire_date'+index">{{error_message}}</span>
            </template>
          </div>
        </div>
        
      </div>
     
      <!-- /.card-footer -->
      <div class="card-footer">
        <button type="submit" class="btn btn-info">Save</button>
        <button type="button" class="btn btn-default float-right">Cancel</button>
      </div>
      
    </form>
  </div>
</template>

<script>
import axios from "axios";
import VuePersianDatetimePicker from "vue-persian-datetime-picker";
export default {
  components: { "date-picker": VuePersianDatetimePicker },
  data() {
    return {
      model: {job_id:1, name:null, expire_date:null},
      data_type:'file',
      errors: {},
      validName:false,
      validDate:false,
      jobs:[],
      // input_fields:[
      //   {
      //     name:'id',
      //     required:true,
      //     type:'hidden'
      //   },
      //    {
      //     name:'id',
      //     required:true,
      //     type:'text'
      //   }
      // ]
    };
  },
  watch:{
    'model.name'(newValue){
      if(newValue !== ''){
        this.validName = true;
      }
      else{
        this.validName = false;
      }
      
    },
    'model.expire_date'(newValue){
       if(newValue !== ''){
        this.validDate = true;
      }
      else{
        this.validDate = false;
      }
    },
    
  },
  methods: {
    
    sendFormData(event) {
      //validation
     
      let errors={};
      let url = "/owner/owner_jobs/create";
      let data = new FormData(event.target);
      axios.post(url, data)
      .then((response)=>{

        // show a message to client that owner job has created
        
        // this.$router.push({name:'owner-jobs-list'});
      })
      .catch((error)=>{
        if(error.response.data !== undefined){
          Object.entries(error.response.data).forEach(([key,value])=>{
          errors[key]=value;
          });
        }
          this.errors = errors;
          console.log(error.response.data,'error in sending form!!');
      });

      // console.log(event.target);
    },
  },
  computed:{
    
  },
  mounted(){
    axios.get('/owner/owner_jobs/getJobs')
    .then((response) =>{
      this.jobs = response.data.jobs;
    })
  }
};
</script>

<style>
.vpd-input-group input {
  border-right: 1px solid #ced4da !important;
  border-left: none !important;

  border-top-right-radius: 0.25rem !important;
  border-bottom-right-radius: 0.25rem !important;
  border-top-left-radius: 0 !important;
  border-bottom-left-radius: 0 !important;
}
</style>
