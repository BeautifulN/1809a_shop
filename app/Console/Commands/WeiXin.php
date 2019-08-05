<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Illuminate\Support\Str;

class WeiXin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'echo:weixin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '微信消息群发';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //
        $arr = DB::table('wx_address')->where(['sub_status'=>1])->get()->toArray(); //查询关注的用户
        $openid = array_column($arr,'openid');
//        print_r($openid);exit;
        $content = Str::random(8)."--嘿嘿嘿嘿--".Str::random(8);
        $response = $this->sendtext($openid,$content);
//        return $response;

    }

    public function sendtext($openid,$content){
        //消息群发
//        echo $content;
        $access = $this->token();
        $msg = "https://api.weixin.qq.com/cgi-bin/message/mass/send?access_token=$access";
        $arr = [
            "touser" => $openid,
            "text"=>[
                "content"=>$content,
            ],
            "msgtype"=>"text",
        ];
        $str = json_encode($arr,JSON_UNESCAPED_UNICODE);
        $client = new Client();  //发送请求
        $response = $client->request('POST',$msg,[
            'body' => $str
        ]);
        echo $response->getBody();

    }

    public function token(){

        $key = 'access_token';
        $tok = Redis::get($key);
//        var_dump($tok);die;
        if($tok){
            //echo '有缓存';
        }else{
            //echo '无缓存';
            $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.env('WX_APPID').'&secret='.env('WX_SECRET').'';

            $response = file_get_contents($url);
            $arr = json_decode($response,true);
//        var_dump($arr);exit;  输出得 ["access_token"]   ["expires_in"]

            //存缓存 access_token   (redis)
            Redis::set($key,$arr['access_token']);
            Redis::expire($key,3600);

            $tok = $arr['access_token'];
//            print_r($tok);
        }

        return $tok;
    }
}
