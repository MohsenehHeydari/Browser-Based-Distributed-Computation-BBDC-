const fs = require('fs')

function randomUserInfo(rows=10,cols=10){
    
    let str = '';
    let id = 0;
    let userIDs = [];
    for( let i=0; i<rows; i++){
        userIDs.push(i);
    }
    for(let x = 0; x < rows; x++){
        str += x+':';
        let line = [];
        for(let y = 0; y < cols; y++){

            id = userIDs[Math.floor(Math.random()*userIDs.length)];
            
            if(line.includes(id)){
                if(y == cols-1){
                    str += '\n';
                    }
                continue;
            }
            else{
                line.push(id);
                if(y == cols-1){
                str += id+'\n';
                }
                else{
                    str += id+',';
                }
            }
        }
    }
    // console.log('str: ',str);
    try {
        fs.writeFileSync('randomUserInfo.txt', str)
    } 
    catch (err) {
        console.error(err)
    }

}
console.time('random user info file');
randomUserInfo(700,700);
console.timeEnd('random user info file');