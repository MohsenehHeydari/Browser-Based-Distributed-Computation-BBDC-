<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Device;

class DeviceController extends Controller{

    public function list(){
        $user=\Auth::user();
        $devices=Device::where('worker_id',$user->id)->select('id','name','CPU')->get();
        // Device::where('worker_id','=',$user->id)->get();
        return ['devices'=>$devices];
    }
    
    public function add(Request $request){
        $device = new Device();
        $user = \Auth::user();
        // throw new \Exception($user->id);
        $device->worker_id = $user->id;
        $this->saveModel($device, $request);
        return ['message' => 'device'. $device->id .' has added.'];
    }

    public function edit($id){
        $user = \Auth::user();
        $device = Device::where('worker_id',$user->id)->findOrFail($id);
        return ['device' => $device];
    }

    public function update(Request $request, $id){
        $user = \Auth::user();
        $device = Device::where('worker_id',$user->id)->findOrFail($id); // user can just edit his own devices
        $this->saveModel($device, $request);
        return ['message' => 'device'. $id .' has updated.'];
    }

    public function delete($id){
        $user = \Auth::user();
        $device = Device::where('worker_id',$user->id)->findOrFail($id); // user can just delete his own devices
        $device->delete();
        // Device::destroy($id);
        return ['message' => 'device'. $id .' has deleted.' ];
    }

    protected function saveModel($device, $request){
        $device->name = $request->input('name');
        $device->CPU = $request->input('CPU');
        $device->GPU = $request->input('GPU');
        $device->RAM = $request->input('RAM');
        $device->battery = $request->input('battery');
        $device->availability = $request->input('availability');
        $device->save();
    }
}