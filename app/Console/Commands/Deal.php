<?php
/**
 * Copyright © 2015—2016 erickdotphp@gmail.com All rights reserved.
 */
namespace App\Console\Commands;

use Illuminate\Console\Command;

class Deal extends Command
{

    protected $signature = 'deal';
    protected $description = 'Deal';

    
    //==========这三个参数需要配置==========================
    private $laravel_path = 'PathToLaravel\\public\\';
    private $appId = "";
    private $secret = "";
    //================================================
    
    private $userList = array(//需要推送的公众号open_id
        "123"
    );
    
    public function handle()
    {
        $path = storage_path()."/logs/run.log";
        $list = $this->generateData();
        $sendList = [];
        
        $cacheKey = "wx_send_list";
        if(\Cache::has($cacheKey))
        {
            $sendList = \Cache::get('wx_send_list');
        }

        if(!empty($list))
        {    
            foreach ($list as $one)
            {
                if(in_array($one['summary'], $sendList))
                {
                    continue;
                }
                $result = $this->sendMsg($one['author'], $one['summary'], $one['shareLink'], $one['avatar']);
                $content = date("Y-m-d H:i:s").' '.$one['author']."\r\n";
                file_put_contents($path, $content, FILE_APPEND);
                $sendList []= $one['summary'];
                \Cache::put($cacheKey, $sendList, "3600");
            }
        }
        file_put_contents($path, date("Y-m-d H:i:s").' '."执行完毕\r\n\r\n", FILE_APPEND);
        
    }
    
    private function generateData()
    {
        $result = $this->readData();
        
        $noticeList = [];
        if(isset($result['data']))
        {
            $data = $result['data'];
            if(!isset($data))
            {
                return [];
            }
            $threadList = $data['threadList'];
            foreach ($threadList as $thread)
            {
                if(!isset($thread['replyList']) || empty($thread['replyList']))
                {
                    $noticeList []=$thread;
                }
            }
        }  
        return $noticeList;
        
    }

    private function readData()
    {
        $filename = $this->laravel_path."data.json";
        if(!file_exists($filename))
        {
            return [];
        }
        $json_string = file_get_contents($filename);

        $obj = json_decode(trim($json_string,chr(239).chr(187).chr(191)),true);
        return $obj;
    }
        

    private function sendMsg($title, $description, $url, $picurl)
    {
        $api = "https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=".$this->getAccessToken();

        $picurl = substr($picurl, 0, strpos($picurl, "64?max-age"));
        $data = array(
            'touser' => "",
            "msgtype" => "news",
            "news"=> array("articles"=>[
                [
                    "title"=>$title, 
                    "description"=>$description,
                    "url"=>$url,
                    "picurl" => $picurl
                ]])
        );
        foreach ($this->userList as $toUser)
        {
            $data['touser'] = $toUser;
            
            $dataStr = json_encode($data, JSON_UNESCAPED_UNICODE);
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $dataStr);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                "Content-Type: application/json"
            ));
            $result = curl_exec($ch);
        }  
        return $result;
    }

    private function getAccessToken()
    {
        $appId = $this->appId;
        $secret = $this->secret;
        $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appId&secret=$secret";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, '120');
        $responseBody = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($responseBody, true);
        
        if (isset($result['access_token'])) {
            return $result['access_token'];
        } else {
            return false;
        }
    }
}
