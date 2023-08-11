<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\CustomLibraries\Constants;
use DB;
use Log;

class FileUploadController extends Controller
{

    public function getFileDetails(Request $request)
    {
        $header = $request->header('Authorization');
        $response = [];
        $app_name = $request->get('appName');
        Log::info('Application Name');
        Log::info($app_name);
        $files = $request->file('file');
        if(empty($files)) {
            $response['error'] = "File empty";
        }
        if(empty($app_name)) {
            $response['error'] = "Application Name empty";
        } else if(empty(self::validateAppName($app_name))) {
            $response['error'] = "Application Name not exists";
        }
        if(!empty($files)) {
            $name = $files->getClientOriginalName();
            $file_size = $files->getSize();
            Log::info('File Name');
            Log::info($name);
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            if(!in_array($ext,['wav','mp3'])) {
                $response['error'] = "Please give audio files";
            }
            if($file_size >= 5000000) {
                $response['error'] = "Audio files size greater than 5 MB";
            }
        }
        if(empty($header)) {
            $response['error'] = "Authorization Token empty";
        } 
        if(!empty($response['error'])) {
            Log::info('Error: ', $response);
            return response()->json($response, 400);
        } else if(self::validateAuthoriztaionToken($header)) {
            $response['succes'] = self::uploadFile($app_name,$ext,$files);
            Log::info('Success: ', $response);
            return response()->json($response['succes'], 200);
        } else {
            $response['error'] = 'Invalid Authorization Token';
            Log::info('Error: ', $response);
            return response()->json($response, 401);
        }

    }

    public function validateAppName($appName)
    {
        $appName = DB::table('user')
                    ->select('user')
                    ->where('user', '=', $appName)
                    ->value('user');
                    
        return $appName;
    }


    public function validateAuthoriztaionToken($token)
    {
        $user = DB::table('user')
                    ->select('id')
                    ->where('token', '=', $token)
                    ->value('id');
                    
        return $user;
    }

    public function uploadFile($app_name,$ext,$files)
    {
        $promptFile = 'telecom_'.uniqid();
        $file_name = $promptFile . '.'.$ext;
        $promptFileUlaw = $promptFile.'.ulaw';
        $promptData = [
            'app_name' => $app_name,
            'prompt_file' => $promptFileUlaw,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $prompt = DB::table('prompt')->insertGetId($promptData);
        $designation_path = storage_path().'/app/promptFiles';
        $files->move($designation_path, $file_name);
        $response = [
            'app_name' => $app_name,
            'prompt_file' => $promptFile,
            'status' => 'Success'
        ];
        return $response;
    }

}