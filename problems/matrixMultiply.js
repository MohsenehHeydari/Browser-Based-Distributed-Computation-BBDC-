function randomMatrix(rows=10,cols=10){
       
    let front = new Array(rows)// .fill(new Array(rows));

    // Loop through Initial array to randomly place cells
    for(var x = 0; x < rows; x++){
        front[x] = [];  // ***** Added this line *****
        for(var y = 0; y < cols; y++){
            front[x][y] = Math.floor(Math.random()*1000);
        }
    }
    return front;
}

function multiply(a, b) {
    let aNumRows = a.length, aNumCols = a[0].length,
        bNumRows = b.length, bNumCols = b[0].length;
    let m = new Array(aNumRows);  // initialize array of rows
    // console.log('aNumCols: '+aNumCols);
    // console.log('bNumRows: '+bNumRows);
    if(aNumCols !== bNumRows){
        // XxZ & ZxY => XxY
        console.log('number of columns in the first matrix should be the same as the number of rows in the second');
    }
    else{
        let r = 0;
        for (r; r < aNumRows; ++r) {
            m[r] = new Array(bNumCols); // initialize the current row
            let c = 0;
            for (c; c < bNumCols; ++c) {
                 m[r][c] = 0;             // initialize the current cell
                for (var i = 0; i < aNumCols; ++i) {
                    m[r][c] += a[r][i] * b[i][c];
                }
            }
        }
        console.log(r,' * ',c);
    }
  return m;
}

let a = randomMatrix(1000,1000);
console.table(a);
console.log('--------------------------------------------------------------------------------------------------------------------------------------------------');
let b = randomMatrix(1000,3000);
console.table(b);
console.log('a * b =');
console.table(multiply(a, b));
