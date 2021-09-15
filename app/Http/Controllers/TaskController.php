<?php

    namespace App\Http\Controllers;

    use Illuminate\Http\Request;
    use Illuminate\Support\Facades\DB;
    use App\Traits\DataTrait;

    // use Illuminate\Support\Facades\Storage;

    use App\Models\Task;
    use App\Models\Data;

    class TaskController extends Controller{

        use DataTrait;

        function getTask($job_id){
            // check data table if there is any data for this job_id to process
            $data = $this->getData($job_id);
            
            return ['data'=>$data];

        }


        // function putDataUrl(){
        //     $url = Storage::url('01.text');
        // }


        
    }
