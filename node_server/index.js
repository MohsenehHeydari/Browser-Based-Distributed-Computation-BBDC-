let app = require('express')(); // an instance of express framework
let socket = require('socket.io');
let redis=require('redis');
let redisClient=redis.createClient({ detect_buffers: true });


let http = require('http'); //this is in node.js by default
let server = http.Server(app); // an instance of server 

const port = 400;

let users = {};
app.get('/', (req, resp) => {
    resp.send('hello world without firewall!')
});

// server.listen(port, () => {
//     console.log('server is listening to port : ' + port);
// });
// server.listen(port, '192.168.1.117');
server.listen(port, 'localhost');
// server.listen(port, '0.0.0.0');

console.log('server is listening  ' + port);

let io = socket(server); // a socket server is running


redisClient.subscribe('newJob');
redisClient.on('message',(channel,message)=>{
    console.log(channel,message);
    //notify idle workers with the same job_id to have new job
    if(channel === 'newJob'){
        let socket_ids = JSON.parse(message);
        if(socket_ids.length > 0){
            Object.keys(users).forEach( (key)=>{
                let user = users[key];
                user.socket.forEach((item)=>{
                    if(socket_ids.includes(item.socket_id)){
                        io.to(item.socket_id).emit('newJob', 'you have new job');
                        console.log('user.job_id:'+ user.job_id +' socket_id: '+ item.socket_id);
                    }
                })
            })
        }
    }
});

io.on('connection', (socket) => {
    
    io.to(socket.id).emit('greeting', 'hello dear user (:')

    socket.on('connected', (data) => {
        if(users[data.user_id]){
           users[data.user_id].socket.push({
            socket_id:socket.id,
            device_id:data.device_id,
            working_status:data.working_status
           });
        }else{
            data.socket=[{
                socket_id:socket.id,
                device_id:data.device_id,
                working_status:data.working_status
               }];
            users[data.user_id] = data;
            

        }
       
        updateOnlineUsers();
        
    });
    socket.on('updateWorkingStatus', (data) => {
       let user=users[data.user_id];
       user.socket.forEach((item)=>{
           if(item.socket_id === socket.id){
               item.working_status=data.working_status;
               console.log('user '+user.user_id + ' updated');
               console.log('working_status: ' + item.working_status);

        
           }
       })
        
    });

    socket.on('disconnect', function () {
       
        Object.keys(users).forEach(key=>{
            let user = users[key];
            console.log(`user ${user.user_id} disconnected`);
            user.socket.forEach((socket_object,index)=>{
                if(socket_object.socket_id === socket.id){
                user.socket.splice(index,1);

                if(user.socket.length === 0){
                    delete users[key];
                }
            }
            })
        });

        updateOnlineUsers();
       
    });
});

async function updateOnlineUsers(){
    client = redis.createClient();
    let online_users=[];
    Object.keys(users).forEach((key)=>{
        users[key].socket.forEach((socket)=>{
            online_users.push({
                user_id:users[key].user_id,
                socket_id:socket.socket_id,
                device_id:socket.device_id,
                working_status:socket.working_status,
                job_id:users[key].job_id,
            })
        })
        
    })
    console.log('online users: ', online_users);
    let string_online_users = await  JSON.stringify(online_users);
    client.set('online_users',string_online_users);
}
