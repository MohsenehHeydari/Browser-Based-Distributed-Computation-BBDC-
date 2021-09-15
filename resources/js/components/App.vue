<template>
    <div>
        <!-- <template v-if="register_status === 'true'">
            <SelectDevice v-if="device_id === null" @setCookie="setDeviceId"></SelectDevice>
            <JobList v-else></JobList>
        </template>
         <Register v-else @requestSent="updateRegisterStatus"></Register> -->

         <component :device_id="device_id" :user="user" :is="current_component" @changeComponent="changeComponent" @setDeviceId="setDeviceId"></component>

    </div>
</template>

<script>
    import axios from 'axios';

    import Register from './Register';
    import JobList from './JobList';
    import SelectDevice from './SelectDevice';

    

    export default {
        components: {Register, JobList, SelectDevice},
        props:['default_device_id', 'user'],
        data() {
            return {
                register_status: "false",
                device_id: null,
                current_component:"JobList"
            }
        },
        methods: {
            updateRegisterStatus(status) {
                // console.log(status);
                this.register_status = status
                localStorage.setItem("register_status", "true");
            },
            setDeviceId(device) {
                this.device_id = device.id;
                this.current_component = "JobList"
            },
            getCookie(cname) {
                // var name = cname + "=";
                // var decodedCookie = decodeURIComponent(document.cookie);
                // console.log(document.cookie,'cookie');
                // var ca = decodedCookie.split(';');
                //   for(var i = 0; i <ca.length; i++) {
                //     var c = ca[i];
                //     while (c.charAt(0) == ' ') {
                //       c = c.substring(1);
                //       }
                //     if (c.indexOf(name) == 0) {
                //       return c.substring(name.length, c.length);
                //     }
                //       }
                //       return "";

                axios.get('get-cookie/device-id')
                    .then(response => {
                        this.device_id = response.data.value;
                    })
                    .catch(console.log)
            },

            changeComponent(componentName){
                this.current_component=componentName;
            }


        },
        created(){
             if (![null, undefined, '', 0, false].includes(this.default_device_id)) {
                this.device_id=this.default_device_id;
            }
             if(![null,undefined,"",0,false].includes(this.device_id)){
                this.current_component="JobList";
            }else{
                this.current_component="SelectDevice";
            }

        },
        mounted() {

            console.log(this.default_device_id);
            this.register_status = localStorage.getItem("register_status");


            // let device_id = this.getCookie('device-id');



            
        }
    }
</script>



