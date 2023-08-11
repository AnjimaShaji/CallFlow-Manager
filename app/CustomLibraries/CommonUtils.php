<?php

namespace App\CustomLibraries;

use App\Http\Controllers\Controller;
use App\Http\Controllers\BangaloreCallOriginateController;
use Illuminate\Http\Request;
use App\CustomLibraries\Constants;
use Hidehalo\Nanoid\Client;
use DB;
use Log;
class CommonUtils {

    public function validateAuthoriztaionToken($token)
    {
        $user = DB::table('user')
                    ->select('id')
                    ->where('token', '=', $token)
                    ->value('id');;
        return $user;
    }

    public static function triggerCurl($request, $url, $apiKey, $timeout = 10)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json", $apiKey));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $data = curl_exec($ch);
        print_r($data);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errorCode = curl_error($ch);

        curl_close($ch);

        // Response Array
        return [
            'response' => $data,
            'httpcode' => $statusCode,
            'error' => $errorCode
        ];
    }


}
?>