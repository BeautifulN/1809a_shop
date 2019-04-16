<?php

namespace App\Http\Controllers\Weixin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Redis;

use GuzzleHttp\Client;

class WxController extends Controller
{

    /*
     * 首次接入GET请求
     * */
    public function index(){
        echo $_GET['echostr'];
    }

    /*
     * 接收微信时间推送
     * */
    public function wxEvent(){
        //接收微信服务器推送
        $content = file_get_contents("php://input");

        $time = date('Y-m-d H:i:s');

        $srt = $time . $content . "\n";

        file_put_contents("logs/wx_event.log",$srt,FILE_APPEND);

        $obj = simplexml_load_string($content); //把xml转换成对象
//        print_r($obj);
//        获取相应的字段 (对象格式)
//        $openid = $obj['FromUserName'];  //用户openid
        $openid = $obj->FromUserName;  //用户openid
        $wxid = $obj->ToUserName;   //微信号ID
//                print_r($wxid);

        $msgtype = $obj->MsgType;
        $content = $obj->Content;
        $media_id = $obj->MediaId;

//        print_r($msgtype);
//        echo 'ToUserName:'.$obj->ToUserName;echo"</br>";//微信号
//        echo 'FromUserName:'.$obj->FromUserName;echo"</br>";//用户openid
//        echo 'CreateTime:'.$obj->CreateTime;echo"</br>";//推送时间
//        echo 'Event:'.$obj->Event;echo"</br>";//消息类型
//die;

//        事件类型
        $event = $obj->Event;

//        扫码关注事件
        if($event=='subscribe') {
            //根据openid判断用户是否已存在
            $user = DB::table('wx_address')->where(['openid' => $openid])->first();
//            print_r($user);die;

            //如果用户之前关注过
            if ($user) {
                echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$wxid.']]></FromUserName><CreateTime>' . time() . '</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.'来了，老弟儿~' . $user->nickname . ']]></Content></xml>';
            }else{
///               获取用户的信息
                $userinfo = $this->getuser($openid);
///                       print_r($userinfo);die;
//                用户信息
                $info = [
//                'id' => $userinfo['subscribe'],
                    'openid' => $userinfo['openid'],
                    'nickname' => $userinfo['nickname'],
                    'sex' => $userinfo['sex'],
                    'country' => $userinfo['country'],
                    'headimgurl' => $userinfo['headimgurl'],
                    'subscribe_time' => $userinfo['subscribe_time'],
                ];

                $sql = DB::table('wx_address')->i53nsertGetId($info);
                echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$wxid.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.'千万人中，关注我；你真牛逼' . $info['nickname'] .']]></Content></xml>';

            }
        }

        //获取消息素材
        if ($msgtype=='text'){   //文本素材

            if(strpos($obj->Content,'+天气')){
                $city = explode('+',$obj->Content)[0];
                $url  = 'https://free-api.heweather.net/s6/weather/now?key=HE1904161044341977&location='.$city;  //天气接口
                $response = file_get_contents($url);
                $arr = json_decode($response,true);
//                print_r($arr);


                if ($arr['HeWeather6'][0]['status'] != 'ok'){   //状态码status

                    echo '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName><FromUserName><![CDATA['.$wxid.']]></FromUserName><CreateTime>'.time().'</CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA['.'请输入正确的天气：如（北京+天气）'.']]></Content></xml>';

                }else{
                    $fl = $arr['HeWeather6'][0]['now']['tmp'];   //温度
                    $cond_txt = $arr['HeWeather6'][0]['now']['cond_txt'];   //天气状况
                    $hum = $arr['HeWeather6'][0]['now']['hum'];   //相对湿度
                    $wind_sc = $arr['HeWeather6'][0]['now']['wind_sc'];   //风力
                    $wind_dir = $arr['HeWeather6'][0]['now']['wind_dir'];   //风向

                    $str = '温度:'.$fl."\n".'天气状况:'.$cond_txt."\n".'相对湿度:'.$hum."\n".'风力:'.$wind_sc."\n".'风向:'.$wind_dir."\n";
                    echo $xml = '<xml><ToUserName><![CDATA['.$openid.']]></ToUserName>
                                    <FromUserName><![CDATA['.$wxid.']]></FromUserName>
                                    <CreateTime>'.time().'</CreateTime>
                                    <MsgType><![CDATA[text]]></MsgType>
                                    <Content><![CDATA['.$str.']]></Content>
                                </xml>';
                }

            }

//            $userinfo = $this->getuser($openid);
            $info = [
                'openid' => $openid,
//                'nickname' => $userinfo['nickname'],
                'content' => $content,
//                'headimgurl' => $userinfo['headimgurl'],
//                'subscribe_time' => $userinfo['subscribe_time'],
            ];

            $sql = DB::table('wx_text')->insertGetId($info);

        }else if($msgtype=='image'){   //图片素材
            $access=$this->token();
            $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=$access&media_id=$media_id";
            $time = time();
            $str = file_get_contents($url);
            file_put_contents("/wwwroot/1809ashop/image/$time.jpg",$str,FILE_APPEND);

            $arr = [];
//            $sql = DB::table('wx_text')->insertGetId($arr);
        }else if($msgtype=='voice'){   //语音素材
            $access=$this->token();
            $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token=$access&media_id=$media_id";
            $time = time();
            $str = file_get_contents($url);
            file_put_contents("/wwwroot/1809ashop/voice/$time.mp3",$str,FILE_APPEND);

        }

    }

    /*
     * 获取微信AccessToken
     * */
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

    public function text(){
        $access_token = $this->token();
        echo $access_token;
    }

    /*
     * 获取用户基本信息
     * */
    public function getuser($openid){
        $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$this->token().'&openid='.$openid.'&lang=zh_CN';
//        echo $url;die;
        $data = file_get_contents($url);
//        var_dump($data);die;
        $arr = json_decode($data,true);
        return $arr;
    }

    //自定义菜单
    public function menu(){
//        接口
        $url = 'https://api.weixin.qq.com/cgi-bin/menu/create?access_token='.$this->token();

//        菜单数据内容
        $arr = [
            'button' => [

                [
                    "type" => "view",
                    "name" => "百度一下",
                    "url" => "http://www.baidu.com/"
                ],

                [ "name" => "点我，嘿嘿嘿",
                    "sub_button"=>[
                        [
                            "type"=>"view",
                            "name"=>"网站",
                            "url"=>"http://cpc.people.com.cn/"
                        ],
                        [
                            "type"=>"miniprogram",
                            "name"=>"网警举报",
                            "url"=>"http://mp.weixin.qq.com",
                            "appid"=>"wx286b93c14bbf93aa",
                            "pagepath"=>"pages/lunar/index"
                        ],
                        [
                            "type"=>"click",
                            "name"=>"赞一下我们",
                            "key"=>"wx1"
                        ]
                    ]
                ]
            ]
        ];
        $str = json_encode($arr,JSON_UNESCAPED_UNICODE);   //处理中文乱码
        $clinet = new Client();  //发送请求
        $response = $clinet->request('POST',$url,[
            'body' => $str
        ]);

        //处理响应回来
        $res = $response->getBody();
        //
        $arr = json_decode($res,true);
//            print_r($arr);
        //判断错误信息
        if($arr['errcode']>0){
            echo "菜单创建失败";
        }else{
            echo "菜单创建成功";
        }

    }

}
