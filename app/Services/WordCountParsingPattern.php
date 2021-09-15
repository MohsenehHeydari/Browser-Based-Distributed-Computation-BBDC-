<?php

namespace App\Services;
use Illuminate\Support\Facades\Storage;

class WordCountParsingPattern {
    
    public function createFiles($request, $ownerJob){
        //get file content
        // decomposition pattern for wordCount
        $contents = file_get_contents($request->file('data_file')->getRealPath());
        $lines = preg_split('/\n|\r\n?/', $contents);
        $lines = array_filter($lines, function ($value) {
            return strlen($value) > 0;
        });

        $index = 1;
        foreach ($lines as $line) {
            //store lines to file
            $url = 'data/' . $request->input('name') . $ownerJob->id . '/' . $index . '.txt';
            Storage::disk('public')->put($url, $line);

            $index++;
        }

        return $lines;
    }
}