<template>
    <div>
        <div>
            <button class="btn btn-success" @click.prevent="startComputation"> start </button>
            <button class="btn btn-danger" v-if = "!stopComputationStatus" @click.prevent="stopComputation"> stop </button>
             <!-- <button class = "btn btn-danger" @click = "testGetSize">test get size</button>  -->
             <!-- <button class = "btn btn-info" @click="testPostSize">test post size</button> -->
        </div>
        <br>
    </div>
    
</template>

<script>
import io from "socket.io-client";
import axios from "axios";
    export default{
        data(){
            return{
                stopComputationStatus: true, //if it is true it means computation has been stopped
                workingStatus: false, //if it is true it means worker is working on a data an task
                taskId: null,
                currentData: null,
                currentDataFile: null,
                currentTaskFile: null,
                tasks:[],
                socket: null,
                failedCount: 0,

            }
        },
        inject:['user','getDeviceId'],
        watch:{
            tasks(newValue) {
                localStorage.setItem("tasks", JSON.stringify(newValue));
            },
            workingStatus(newValue){
                //check if worker is available to do task but there is no task and data!!!
                if(!this.stopComputationStatus){ // the process is running but there's no task and data 
                // console.log('working_status in watcher: '+newValue);
                    this.socket.emit('updateWorkingStatus',{user_id:this.user.id,working_status:newValue});
                }
            }
        },
        methods:{
            // testGetSize(){
            //     axios.get('/test')
            //     .then((response)=>{
            //         console.log(response);
            //     });
            //     console.log('test get  request');
            // },
            // testPostSize(){
            //     axios.post('/test')
            //     .then((response)=>{
            //         console.log(response);
            //     });
            //     console.log('test get  request');
            // },
            startComputation() {
                this.stopComputationStatus = false;
                this.setTaskAndData();
                //start socket connection
                this.startSocketConnection();
            },
            stopComputation(){
                 //stop socket connection
                this.stopSocketConnection();
                this.stopComputationStatus = true;
                // //set status to not ready for work
                this.workingStatus = false;
            },
            startSocketConnection() {
                // this.socket = io.connect("http://192.168.1.105:400", {
                    this.socket = io.connect("localhost:400", {
                    transports: ["websocket"],
                });
                let user_data = {
                    user_id: this.user.id,
                    user_name: this.user.name,
                    device_id: this.getDeviceId(),
                    job_id: this.$route.params.id,
                    working_status: this.workingStatus
                }
                console.log(user_data);
                this.socket.emit("connected", user_data);

                this.socket.on("greeting", function (data) {
                    console.log(data);
                });

                this.socket.on("newJob", (data) =>{
                    console.log(data);
                    this.setTaskAndData();
                });
                },

            stopSocketConnection() {
                this.socket.disconnect();
            },

            // request task and data information
            async getProcessData(){
                 try{
                        let response = await axios.get("/worker/taskRequest/" + this.$route.params.id);
                        return response.data.data;
                    }catch(error){
                        if(this.failedCount<=20){
                            this.failedCount+=1;
                            return await getProcessData();
                        }else{
                            throw new Error('failed count is more than 10!');
                        }
                    }
            },

            async setTaskAndData(currentData) {
                // console.log("setTaskAndData method started");
                
                if (currentData === undefined) {

                   currentData = await this.getProcessData();
                    
                }
                if (![null, undefined, false, 0].includes(currentData)) {
                    this.currentData = currentData;
                    this.taskId = currentData.task_id;

                    this.currentDataFile = await this.getDataFile();
                    if(![null, undefined, false, 0].includes(this.currentDataFile)){
                        // console.log("data file has gotten, lets go to get task file");
                        this.currentTaskFile = await this.getTaskFile();
                    }
                   
                } else {
                    console.log("no data to process!!");
                    this.workingStatus = false;
                    return;
                }
                
                if (
                    ![null, undefined, false, 0].includes(this.currentDataFile) &&
                    ![null, undefined, false, 0].includes(this.currentTaskFile)
                ) {
                    // console.log("doTask");
                    this.doTask();
                } else {
                    console.log("can not do task!");

                }
            },
            async getDataFile() {
               
               try {
                    this.currentDataFile = null;
                    
                    if(this.currentData.value == undefined || this.currentData.value == null || this.currentData.value == ''){
                         let response = await axios.get(this.currentData.url);
                        // console.log(response.data);
                        return response.data;
                    }

                    return this.currentData;
                   
                } catch (error) {
                    console.log('data file is not valid!')
                    return null;
                }

            },
            async getTaskFile() {
                this.currentTaskFile = null;
                // console.log("getTaskFile");
                //check if task has downloaded before
                if (this.tasks[this.taskId]) {
                    // console.log("task exist");
                    return this.tasks[this.taskId];
                } else {
                    console.log("task doesnt exsit");
                    let url = "/taskContents/" + this.taskId + ".txt";
                    // console.log("task url is created");
                    let response = await axios.get(url);
                    // this.tasks[this.currentData.task_id] = response.data;
                    // let tasks=JSON.parse(JSON.stringify(this.tasks));

                    this.$set(this.tasks, this.taskId, response.data); // Vue.set(object have changed, which key have changed, value of key)

                    // console.log(this.tasks,'tasks after changed')

                    // console.log(tasks,'clone tasks')
                    // this.tasks = tasks;

                    // console.log(this.tasks, "this.tasks");
                    // console.log(localStorage.getItem("tasks"), "localStorage");
                    // throw new Error("this.tasks");
                    return response.data;
                }
            },
            doTask() {
                this.workingStatus = true;
                // create a function using 'data' as input name and currentTaskFile as funcion body
                let task = new Function("data", this.currentTaskFile); 
                let result = task(this.currentDataFile);
                console.log(result);
                axios
                    .post("/worker/sendResult", {
                    result: result,
                    data: this.currentData,
                    job_id: this.$route.params.id,
                    })
                    .then((response) => {
                    // console.log("hasNewtTask ", response.data.hasNewTask);
                    if (response.data.hasNewTask === 1 && this.stopComputationStatus !== true) {
                        this.setTaskAndData(response.data.nextData);
                        // this.workingStatus = false;
                    } else {
                        console.log("process is finished!", response.data);
                        this.workingStatus = false;
                    }
                    })
                    .catch((error)=>{

                        this.failedCount++;
                        
                        if(this.failedCount <= 20){
                            this.setTaskAndData();
                        }
                    }
                    );    
            },
        },
        mounted(){
            
        }
    }
</script>
