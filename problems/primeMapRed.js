// const { concat } = require("core-js/core/array");

function map(data){

    data=data.split('-');
    let min=+data[0];
    let max=+data[1];
    let N=+data[2];
   
    let KnownPrimes = [2,3,5,7];
    let result =[];
    for(min; min<=max; min++){
        result.push([min,1]);
        if(KnownPrimes.includes(min) || isNotDividable(min,KnownPrimes)){
            
            for(let i=2; i<=N; i++){
                let f=i/min;
                if(Number.isInteger(f)){
                    let k = min*f;
                    if(k <= N && k!= min){
                        result.push([k,1]);
                    }
                }                
                
            }
            
        }
    }
    
    function isNotDividable(n,KP){ // check if n is divisable to any member of KnownPrims
        for(let i=0; i<KP.length; i++){
            if(n % KP[i] !== 0){
                return true;
            }
            return false;
        }
    }
    return result;
}


function concatArray(a,b){
    for(i=0; i<b.length; i++){
        a.push(b[i])
    }
    return a;
}

function reduce(data){
    let result = {};
    let finalResult = {};
    for(let i=0; i<data.length; i++){
    let key = data[i][0];
    let value = data[i][1];

        if(result[key] === undefined){
            result[key] = [value];
        }
        else{
            result[key].push(value);
        }
        
    }
    Object.keys(result).forEach((key)=>{
        if(result[key].length === 1){
            finalResult[key] = result[key][0];
        }
    })
    return finalResult;
}

console.log('---------------------');
let result = map('501-602-1000');
console.log(result);
console.log(reduce(result));


// result =[];
//  concatArray(result,map(2,20,100));
// // console.l/g('2 to 5:');
// concatArray(result,map(21,40,100));
// // console.log('6 to 10');
//  concatArray(result,map(41,60,100));
// // console.log('11 to 15');
//  concatArray(result,map(61,80,100));
// // console.log('16 to 20');
// concatArray(result,map(81,100,100));


// // console.log(rangeMap(10,11));
// console.log(reduce(result));