<?php
/**
 * Created by PhpStorm.
 * User: apple
 * Date: 07/05/17
 * Time: 12:01 PM
 */

namespace App\Http\Controllers;


use Illuminate\Http\Request;

class FileController
{
    public function uploadFile(Request $request){
        $file = $request->file('file');
        $destinationPath = 'uploads';
        $json = json_decode(file_get_contents($request->file));
        $file->move($destinationPath,$file->getClientOriginalName());
        return json_encode($json);
    }
}