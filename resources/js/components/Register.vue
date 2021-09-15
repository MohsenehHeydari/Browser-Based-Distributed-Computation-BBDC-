<template>
    <form action="/submit" method="POST">

        <div class="container">
          <h1>Registeration</h1>
          <p>Hi there and welcome to our distributed computing framework.</p>
          <p>Please fill in this form.</p>
          <hr>

          <p><label for="device-name"><b>Device Name</b></label></p>
          <p><input type="text" placeholder="Enter Device name" id="name" v-model="form.name" ></p>
          
          <p><label for="cpu"><b>How much cpu we can use?(in percent)</b></label></p>
          <p><input type="number" placeholder="CPU" id="cpu" required v-model="form.CPU"></p>
      
          <p><label for="GPU"><b>How much GPU we can use?(in percent)</b></label></p>
          <p><input type="number" placeholder="GPU" id="gpu" required v-model="form.GPU"></p>
      
          <p><label for="RAM"><b>How much RAM we can use?(MB)</b></label></p>
          <p><input type="number" placeholder="RAM" id="ram" required v-model="form.RAM"></p>

          <p><label for="Battery"><b>if you are using a mobile device(phone/tablet/laptop), set a treshold for battery usage.(in percent)</b></label></p>
          <p><input type="number" placeholder="Battery" id="battery"  v-model="form.battery"></p>

          <p><label for="availability"><b>Availability?(in minutes)</b></label></p>
          <p><input type="number" placeholder="Availability" id="availability" v-model="form.availability"></p>
          <hr>
          <p>By filling this form you agree to our <a href="#">Terms & Privacy</a>.</p>
          <button type="button" @click.prevent="sendRequest" class="registerbtn">Submit</button>
          <!-- <button type="button" @click.prevent='$emit("changeComponent","Register")'>add a new device</button> -->
        </div>
    </form>
</template>

<script>
import axios from 'axios';
export default {
    props:['user','device_id'],
  data(){
    return {
      form : {
        name:'lsidjfl',
        CPU:'20',
        GPU:'30',
        RAM:'3000',
        battery:'50',
        availability:'23',
        _token:''

      }
    }
  },
  methods:{
    sendRequest(){
  
      axios.post('submit',this.form)
      .then((response)=>{
          // this.updateRegisterStatus(true);
        // alert(response.data.message);
        // console.log(response);

        this.$emit('changeComponent',"SelectDevice");
      })
      .catch((error)=>{
        console.log(error);
      })
      // console.log('request is sending .... ');
    }
  },
    mounted(){ 
      
     }
}
</script>



