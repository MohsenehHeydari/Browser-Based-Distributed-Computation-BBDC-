const fs = require('fs');

function symmetric(N=5){

    let mat = new Array(N);
    let userIDs = [];
    let cell = 0;
    let str = '';
    
    for(let index=0; index<N; index++){
        mat[index]=[];
        userIDs.push(index);
    }
    
    for (let i = 0; i < N; i++){
        for (let j = 0; j <= i; j++){
            if(i == j){
                cell = 0;
                mat[i].push(cell);
                // str += cell+','
            }
            else{
                cell = [0,1][Math.floor(Math.random()*2)]
                mat[i].push(cell);
                   
            // str += cell[i][j]+','
            }
            mat[i][j] = cell;
            mat[j][i] = cell;
        }
    }
    for(let i = 0; i < N; i++){
        str += (i+1)+':';
        for(let j = 0; j < mat[i].length; j++){
            if(mat[i][j] == 1){
                str += (j+1)+',';
            }
        }
        // str = str.trim(',');
        str += '\n';
    }         
    console.log(str);
    try {
        fs.writeFileSync('randomSymmetric.txt', str)
    } 
    catch (err) {
        console.error(err)
    }

    return mat;
}
console.time('create symmetric matrix');
console.table(symmetric(20));
console.timeEnd('create symmetric matrix');