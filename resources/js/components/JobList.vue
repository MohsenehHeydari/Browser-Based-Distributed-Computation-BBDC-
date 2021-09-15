<template>
  <div class="container startTask">
    <div>
      <p>
        <button
          type="button"
          @click.prevent="$emit('changeComponent', 'Register')"
        >
          add new device
        </button>
      </p>
      <p>
        <button
          type="button"
          @click.prevent="$emit('changeComponent', 'SelectDevice')"
        >
          select another device
        </button>
      </p>
    </div>
    <h1>please choose a task to participate</h1>

    <template v-if="stopComputationStatus">
      <div v-for="job in jobList" :key="'job-' + job.id">
          <h2>{{ job.name }}</h2>
          <p>
            <b>{{ job.description }}</b>
          </p>
          <p>
            <button  type="button" @click.prevent="startComputation(job)">
              Start the computation
            </button>
          </p>
      </div>
    </template>

    <button
      v-if = "!stopComputationStatus"
      type="button"
      style="background-color: red"
      @click.prevent="stopComputation"
    >
      Stop the computation
    </button>
    
  </div>
</template>

<script>
import axios from "axios";
import Vue from "vue";
import io from "socket.io-client";

export default {
  props: ["user", "device_id"],
  data() {
    return {
      currentJob: null,
      currentData: null,
      currentDataFile: null,
      taskId: null,
      tasks: {},
      jobList: [
        { id: 1, name: "Word Count", description: "this is an example." },
      ],
      stopComputationStatus: true, //if it is true it means computation has been stopped
      socket: null,
      workingStatus: false, //if it is true it means worker is working on a data an task
    };
  },
  watch: {
    tasks(newValue) {
      localStorage.setItem("tasks", JSON.stringify(newValue));
    },
    workingStatus(newValue){
      //check if worker is available to do task but there is no task and data!!!
      if(!this.stopComputationStatus){ // the process is running but there's no task and data 
      console.log('working_status in watcher: '+newValue);
         this.socket.emit('updateWorkingStatus',{user_id:this.user.id,working_status:newValue});
      }
     
    }
    //another way to know about changes of tasks array
    // tasks: {
    //   handler(newValue, oldValue) {
    //     localStorage.setItem("tasks", JSON.stringify(newValue));
    //   },
    //   deep: true,
    // },
  },
  methods: {
    startComputation(job) {
      this.currentJob = job;
      this.stopComputationStatus = false;
      this.setTaskAndData();
      //start socket connection
      this.startSocketConnection();
    },

    stopComputation() {
      //stop socket connection
      this.stopSocketConnection();
      this.stopComputationStatus = true;
      //set status to not ready for work
      this.workingStatus = false;

    },

    startSocketConnection() {
      this.socket = io.connect("http://localhost:3000", {
        transports: ["websocket"],
      });
      this.socket.emit("connected", {
        user_id: this.user.id,
        user_name: this.user.name,
        device_id: this.device_id,
        job_id: this.currentJob.id,
        working_status: this.workingStatus
      });

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

    async setTaskAndData(currentData) {
      console.log("setTaskAndData method started");
      
      if (currentData === undefined) {
        let response = await axios.get("/taskRequest/" + this.currentJob.id);
        currentData = response.data.data;
      }
        if (![null, undefined, false, 0].includes(currentData)) {
        this.currentData = currentData;
        this.taskId = currentData.task_id;

        this.currentDataFile = await this.getDataFile();
        console.log("data file has gotten, lets go to get task file");
        this.currentTaskFile = await this.getTaskFile();
      } else {
        console.log("no data to process!!");
        this.workingStatus = false;
        // console.log('working status in no process ',this.workingStatus)
        return;
      }
      
      if (
        ![null, undefined, false, 0].includes(this.currentDataFile) &&
        ![null, undefined, false, 0].includes(this.currentTaskFile)
      ) {
        console.log("doTask");
        this.doTask();
      } else {
        console.log("can not do task!");

      }
    },

    async getDataFile() {
      try {
        this.currentDataFile = null;
        let response = await axios.get(this.currentData.url);
        console.log(response.data);
        return response.data;
      } catch (error) {}
    },
    async getTaskFile() {
      this.currentTaskFile = null;
      console.log("getTaskFile");
      if (this.tasks[this.taskId]) {
        console.log("task exist");
        return this.tasks[this.taskId];
      } else {
        console.log("task dont exsit");
        let url = "/taskContents/" + this.taskId + ".txt";
        console.log("task url is created");
        let response = await axios.get(url);
        // this.tasks[this.currentData.task_id] = response.data;
        // let tasks=JSON.parse(JSON.stringify(this.tasks));

        Vue.set(this.tasks, this.taskId, response.data); // Vue.set(object have changed, which key have changed, value of key)

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
      let task = new Function("data", this.currentTaskFile); // create a function using 'data' as input name and currentTaskFile as funcion body
      let result = task(this.currentDataFile);
      console.log(result);
      axios
        .post("/sendresult", {
          result: result,
          data_id: this.currentData.id,
          job_id: this.currentJob.id,
        })
        .then((response) => {
          console.log("hasNextTask ", response.data.hasNewTask);
          if (response.data.hasNewTask === 1 && this.stopComputationStatus !== true) {
            this.setTaskAndData(response.data.nextData);
          } else {
            console.log("process is finished!", response.data);
            this.workingStatus = false;
          }
        });
    },

    jobListRequest() {
      axios
        .get("/jobList")
        .then((response) => {
          this.jobList = response.data.jobList;
        })
        .catch((error) => {
          console.log(error);
        });
    },
  },
  mounted() {

    let tasks = localStorage.getItem("tasks");

    if ([null, undefined, false, 0].includes(tasks)) {
      this.tasks = {};
    } else {
      this.tasks = JSON.parse(tasks);
    }
  },
  created() {
    this.jobListRequest();
  },
};
</script>
