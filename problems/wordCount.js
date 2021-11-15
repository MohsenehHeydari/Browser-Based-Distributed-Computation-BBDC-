let fs = require("fs");
let text = fs.readFileSync("./mytext.txt").toString('utf-8');
let words = text.split(" ");

console.log(words.length);
//print words and count of them!!