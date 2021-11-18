// import { writeFile } from 'fs/promises';
// import { Buffer } from 'buffer';

// let fs = require('fs/promises');
// let Buf = require('buffer');

// async function writeFileInNode(){
    
// try {
//     const controller = new AbortController();
//     const { signal } = controller;
//     // const data = new Uint8Array(Buffer.from('Hello Node.js','utf-8'));
//     const data = Buf.Buffer.from('Hello Node.js','utf-8');
//     const promise = fs.writeFile('message.txt', data, { signal });
  
//     // Abort the request before the promise settles.
//     controller.abort();
  
//     await promise;
//   } catch (err) {
//     // When a request is aborted - err is an AbortError
//     console.error(err);
//   }
// }

// writeFileInNode();
// console.log('file wrote.');
const fs = require('fs')

const content = 'Some content!'

try {
  fs.writeFileSync('test.txt', content)
  //file written successfully
} catch (err) {
  console.error(err)
}