function randomMatrix(rows=10,cols=10){
    
    let front = new Array(rows);// .fill(new Array(rows));
    let row = new Array(cols);
    // var fso = new CreateObject("Scripting.FileSystemObject");
    // var a = fso.CreateTextFile("c:\\testfile.txt", true);
    // a.WriteLine("This is a test.");
    // a.Close();

    // Loop through Initial array to randomly place cells
    for(var x = 0; x < rows; x++){
        row[x] = [];
        front[x] = [];  // ***** Added this line *****
        for(var y = 0; y < cols; y++){
            front[x][y] = Math.floor(Math.random()*1000);
            row[x].push(front[x][y]);
            if(y == cols-1){
                console.log(row[x]);
                // let blob = new Blob(row[x], { type: "text/plain;charset=utf-8" });
                // saveAs(blob, "testfile1.txt");   

            }
           
        }
    }
    return front;
}

console.table(randomMatrix(2,3)) ; // browser console only, not StackOverflow's