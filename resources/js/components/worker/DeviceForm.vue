<template>
   <div class="card card-info">
    <div class="card-header">
      <h3 class="card-title">Horizontal Form</h3>
    </div>
    <!-- /.card-header -->
    <!-- form start -->
    <form
      class="form-horizontal"
      method="Post"
      action="test/test/test"
      @submit.prevent="sendFormData"
    >
      <div class="card-body">

        <div class="form-group row">
          <div class="col-sm-2"></div>
          <label for="name" class="col-sm-2 col-form-label">Name</label>
          <div class="col-sm-5">
            <input
              v-model="model.name"
              type="text"
              class="form-control"
              id="name"
              name="name"
              placeholder="Enter your name"
            />
          </div>
        </div>

        <div class="form-group row">
          <div class="col-sm-2"></div>
          <label for="cpu" class="col-sm-2 col-form-label">CPU</label>
          <div class="col-sm-5">
            <input
              v-model="model.CPU"
              type="Number"
              class="form-control"
              id="cpu"
              name="CPU"
              placeholder="Enter amount of cpu you want to share"
            />
          </div>
        </div>

        <div class="form-group row">
          <div class="col-sm-2"></div>
          <label for="gpu" class="col-sm-2 col-form-label">GPU</label>
          <div class="col-sm-5">
            <input
              v-model="model.GPU"
              type="Number"
              class="form-control"
              id="gpu"
              name="GPU"
              placeholder="Enter amount of gpu you want to share"
            />
          </div>
        </div>

         <div class="form-group row">
          <div class="col-sm-2"></div>
          <label for="battery" class="col-sm-2 col-form-label">Battery</label>
          <div class="col-sm-5">
            <input
              v-model="model.battery"
              type="Number"
              class="form-control"
              id="battery"
              name="battery"
              placeholder="Enter amount of battery you want to share"
            />
          </div>
        </div>

        <div class="form-group row">
          <div class="col-sm-2"></div>
          <label for="ram" class="col-sm-2 col-form-label">RAM</label>
          <div class="col-sm-5">
            <input
              v-model="model.RAM"
              type="Number"
              class="form-control"
              id="ram"
              name="RAM"
              placeholder="Enter amount of RAM you want to share"
            />
          </div>
        </div>


        <div class="form-group row">
          <div class="col-sm-2"></div>
          <label for="availability" class="col-sm-2 col-form-label">availability</label>
          <div class="col-sm-5">
            <input
              v-model="model.availability"
              type="Number"
              class="form-control"
              id="availability"
              name="availability"
              placeholder="Enter how much you're device is available in minutes"
            />
          </div>
        </div>


      </div>
      <!-- /.card-body -->
      <div class="card-footer">
        <button type="submit" class="btn btn-info">Save</button>
        <button @click="$router.push({name: 'devices-list'})" type="button" class="btn btn-default float-right">
          Cancel
        </button>
      </div>
      <!-- /.card-footer -->
    </form>
  </div>
</template>

<script>
import axios from 'axios'
// import VuePersianDatetimePicker from "vue-persian-datetime-picker";
export default{
    // components:{ "date-picker": VuePersianDatetimePicker },
    data(){
        return{
            model: {},
        }
    },
    watch:{
      '$route'(){
        this.init();
      }
    },
    methods:{
        sendFormData(){
            let url = '/worker/devices/add';
            let data = this.model;
            if(this.$route.name === 'devices-edit'){
              url = '/worker/devices/edit/'+this.$route.params.id;
              data._method = 'put';
            }
            axios.post(url,data)
            .then((response)=>{
            console.log(response.data.message);
            this.$router.push({name: 'devices-list'});

            })

        },
        getFormData(){
          axios.get('/worker/devices/edit/'+this.$route.params.id) //$route is current route information
          .then((response)=>{
            this.model = response.data.device;
          })
        },
        init(){
          if(this.$route.name === 'devices-edit'){
          this.getFormData();
          }else {
            this.model = {};
          }
        }
    },
    mounted(){
        this.init();
    }
}
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
