
const fs = require('fs');

function findMutualFriends(){

    let list = fs.readFileSync('randomUserInfo.txt').toString().trim();
    let lines = list.split('\n');
    let userCount = lines.length;
    let users = [];
    let friends = [];
    let mutual = {
        pairOfFriends:[],
        mutualFriends:[]
    };
    let str ='';
    
    lines.forEach((line)=>{
        let split = line.split(':');
        users.push(split[0]);
        friends.push(split[1].split(',').map(Number));
    });
    
    for(let i=0; i<userCount; i++){
        for(let j=0; j<friends.length; j++){
            if(i==j){
                continue;
            }
            else if(i<j){
                let tmpMutual =  friends[i].filter(item => friends[j].includes(item));
                if(tmpMutual !== []){
                    mutual.pairOfFriends.push([i,j]);
                    mutual.mutualFriends.push(tmpMutual);

                    str += '('+i+','+j+'): '+tmpMutual.toString()+'\n';
                }
            }
       }
    }

    // console.log(str);
    try {
        fs.writeFileSync('resultMutualFriends.txt', str);
    } 
    catch (err) {
        console.error(err)
    }
    
}
console.time('find mutual friend');
findMutualFriends();
console.timeEnd('find mutual friend');


