<template>
    <component :is="current_component" :type="type" :data="data" @change_component="changeComponent"></component>
</template>

<script>

    import login from './Login';
    import register from './Register';



    export  default {

        components:{login,register},
        props:['component','data','type'],

        data(){
            return {
                current_component:'login'
            }
        },
        provide:{
            baseUrl:window.location.protocol + "//" + window.location.host,
        },
        computed:{
            baseUrl() {
                let getUrl = window.location;
                return getUrl.protocol + "//" + getUrl.host;
            },
        },
        methods:{
            changeComponent(component_name){
                this.current_component=component_name;
            }
        },
        mounted() {
            this.current_component=this.component;
        }
    }
</script>

<style>
    .invalid-feedback{
        display: inline-block !important;
    }
</style>
