<template>
    <div class="container selectDevice">
        <p><button type="button" @click.prevent='$emit("changeComponent","Register")' >add new device</button></p>
    <h1>please choose a device to participate</h1>
      <div v-for="device in devices" :key="'device-'+device.id">
        <h2>{{device.name}}</h2>
        <p><button type="button" @click.prevent="setCurrentDevice(device)" >Select Device</button></p>
      </div>
  </div>
</template>
<div>
    <p><button type="button" @click.prevent='$emit("changeComponent","Register")' >add new device</button></p>
</div>

<script>
import axios from 'axios'; // import nickname(name i choose) from 'package name'

export default{
    props:['user','device_id'],
    data(){
        return {
            devices:[]
        }

    },

    methods:{
        getDevices(){
            axios.get('/device-list')
            .then(
                (response)=>{
                    this.devices = response.data.devices;
                    if(this.devices.length === 0){
                        this.$emit("changeComponent","Register");
                    }
                    console.log(this.devices);
            })
            .catch(console.log);
            //  .catch(error=>{console.log(error)});
        },
        setCurrentDevice(device){
            this.setCookie('device-id',device.id,1);
            this.$emit('setDeviceId',device)
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


            // var d = new Date();
            // d.setTime(d.getTime() + (exdays*24*60*60*1000));
            // var expires = "expires="+ d.toUTCString();
            // document.cookie = name + "=" + value + ";" + expires + ";path=/";


        },
        
    },
    created(){
        this.getDevices();
    }
     
}
</script>
