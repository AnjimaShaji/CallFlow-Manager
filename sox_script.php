<?php

define('ROOTPATH', dirname(__FILE__));

$dir = ROOTPATH . '/storage/app/promptFiles';

$outputPath = ROOTPATH . '/storage/app/covertedFiles';

$copyFilePath = ROOTPATH . "/storage/app/promptFilesCopy";

foreach (glob("$dir/*") as $filename) {

        copy($filename, $copyFilePath.'/'.basename($filename));

        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if($ext == 'mp3') {
                $res = null;
                $wav_file = basename($filename,".".$ext). '.wav';
                $cmd = "lame --decode ".$filename." ".$dir."/".$wav_file;
                $resp = exec($cmd,$res);
                unlink($filename);
        }

        if($ext == 'wav') {
                $output=null;
                $file = basename($filename,".".$ext);
                $command = "sox -V $filename -r 8000 -c 1 -t ul $outputPath/$file.ulaw";
                $exportResp = exec($command,$output);
                $ulawFile = $outputPath.'/'.$file.'.ulaw';
                exec("scp $ulawFile root@114.143.197.210:/var/lib/asterisk/sounds/en/");
                //unlink($ulawFile);
        }
}
