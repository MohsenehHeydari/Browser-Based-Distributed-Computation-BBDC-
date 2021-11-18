const fs = require('fs')
// const buf = require('buffer');

function randomMatrix(rows=10,cols=10){
    
    let front = new Array(rows);// .fill(new Array(rows));
    let row = new Array(cols);
    let str = '';
    // Loop through Initial array to randomly place cells
    for(var x = 0; x < rows; x++){
        row[x] = [];
        front[x] = [];  // ***** Added this line *****
        for(var y = 0; y < cols; y++){
            front[x][y] = y;
            row[x].push(front[x][y]);
            str += front[x][y]+',';
            if(y == cols-1){
                str += '\n';
            }
        }
    }
    console.log('str: ',str);
    try {
        fs.writeFileSync('random.txt', str)
        //file written successfully
    } 
    catch (err) {
        console.error(err)
    }
    return front;
}

console.table(randomMatrix(100,10)) ; 