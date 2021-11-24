
const fs = require('fs')

// function multiply(a, b) {
    
//     console.time('multiplyFunction');
//     let aNumRows = a.length, aNumCols = a[0].length,
//         bNumRows = b.length, bNumCols = b[0].length;
//     let m = new Array(aNumRows);  // initialize array of rows
    
//     if(aNumCols !== bNumRows){
//         // XxZ & ZxY => XxY
//         console.log('number of columns in the first matrix should be the same as the number of rows in the second');
//     }
//     else{
//         let r = 0;
//         for (r; r < aNumRows; ++r) {
//             m[r] = new Array(bNumCols); // initialize the current row
//             let c = 0;
//             for (c; c < bNumCols; ++c) {
//                  m[r][c] = 0;             // initialize the current cell
//                 for (var i = 0; i < aNumCols; ++i) {
//                     m[r][c] += a[r][i] * b[i][c];
//                 }
//             }
//         }
//         // console.log(r,' * ',c);
//     }
    
//     console.timeEnd('multiplyFunction');
//     return m;
// }

function multiplyFromFiles(aURL,bURL){

    let first = fs.readFileSync(aURL).toString().trim();
    let second = fs.readFileSync(bURL).toString().trim();
    // console.log(first);
    // console.log(second);
    let aLines = first.split('\n'),
        bLines = second.split('\n'),
        aNumRows = aLines.length,
        bNumRows = bLines.length,
        aNumCols = aLines[0].split(',').length,
        bNumCols = bLines[0].split(',').length;
    let a = [];
    let b = [];
    console.log('aNumCols',aNumCols);
    console.log('bNumCols',bNumRows);
    // initiate a matrix
    for(let i = 0; i < aNumRows; i++){
        a.push(aLines[i].split(','));
    }
    // initiate b matrix
    for(let j = 0; j < bNumRows; j++){
        b.push(bLines[j].split(','));
    }
    let str = '';
    let m = new Array(aNumRows);

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
                    for (let k = 0; k < aNumCols; ++k) {
                        m[r][c] += a[r][k] * b[k][c];
                        // if(k == aNumCols-1){
                        //     str += m[r][c]+'\n';
                        // }
                        // else{
                        //     str += m[r][c]+',';
                        // }
                    }
                }
            }
        }
        str = m.toString();
        // str = str.split(',');
        // // console.log(str);
        // for(let index = bNumCols-1; index < str.length; index+bNumCols){
        //     str.splice(index,0,'\n');
        // }
        try {
            fs.writeFileSync('./Hezar/multiplicationResult.txt', str)
        } 
        catch (err) {
            console.error(err)
        }
        return m;
}


console.log('a * b =');
console.time('multiply out of func');
// console.table(multiplyFromFiles('./SadInSisad/Firstrandom.txt','./SadInSisad/Secondrandom.txt'));
multiplyFromFiles('./Hezar/Firstrandom.txt','./Hezar/Secondrandom.txt');
console.timeEnd('multiply out of func');



