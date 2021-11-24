const fs = require('fs')
// const buf = require('buffer');

function randomMatrix(rows=10,cols=10){
    
    let front = new Array(rows);// .fill(new Array(rows));
    let row = new Array(cols);
    let str = '';
    // Loop through Initial array to randomly place cells
    for(let x = 0; x < rows; x++){
        row[x] = [];
        front[x] = [];  // ***** Added this line *****
        for(let y = 0; y < cols; y++){
            front[x][y] = Math.floor(Math.random() * 1000);
            row[x].push(front[x][y]);
            if(y == cols-1){
                str += front[x][y]+'\n';
            }
            else{
                str += front[x][y]+',';
            }
        }
    }
    // console.log('str: ',str);
    try {
        fs.writeFileSync('random.txt', str)
        //file written successfully
    } 
    catch (err) {
        console.error(err)
    }
    
    return front;
}
console.time('random matrix');
// console.table(randomMatrix(1000,1000)) ; 
randomMatrix(1000,3000);
console.timeEnd('random matrix');