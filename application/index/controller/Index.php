<?php
namespace app\index\controller;
use think\Controller;
use think\Request;
use app\Util\ReturnCode;
use app\Util\Tools;
use Yansongda\Pay\Pay;
use Yansongda\Pay\Log;
class Index extends Controller
{
    protected $param;
    protected function initialize(){
        $this->param = $this->request->param();
    }

    public function index()
    {
        // $form = '100000';
        $param  = $this->param;
        if(!isset($param['id'])){
            $form = '100000';
        }else{
            $form = $param['id'];
        }
        $this->assign([
            'form' => $form
        ]);
        if($this->request->isMobile()){
            return $this->fetch('mbindex');
        }else{
            return $this->fetch();
        }
    }


    // const APPID = 'wx86e3a1fe233f3075';
	// const MCHID = '1237428802';
	// const KEY = '566cb6a37d2e2234231352bc5b5660cd';
	// const APPSECRET = '3d6bc0273bf9876bb3293281041f2751';



    // const APPID = 'wxae63f5fac5d362bf';
	// const MCHID = '1284926301';
	// const KEY = '566cb6a37d2e2234231352bc5b5660cd';
	// const APPSECRET = 'e560a025cfc921b09a2a3995dba08cea';

    protected $config = [
        'app_id' => 'wxae63f5fac5d362bf', // 公众号 APPID
        'mch_id' => '1284926301',
        // 'key' => '566cb6a37d2e2234231352bc5b5660cd',
        'notify_url' => 'https://www.itarvin.info:8084/index/innex/notify.php',
        'APPSECRET' => 'e560a025cfc921b09a2a3995dba08cea',
        'log' => [ // optional
            'file' => './logs/wechat.log',
            'level' => 'info', // 建议生产环境等级调整为 info，开发环境为 debug
            'type' => 'single', // optional, 可选 daily.
            'max_file' => 30, // optional, 当 type 为 daily 时有效，默认 30 天
        ],
    ];

    public function pay()
    {
        // 获取openid
        $openid = $this->get_openid();
        var_dump($openid);die;

        $order = [
            'out_trade_no' => time(),
            'total_fee' => '1', // **单位：分**
            'body' => 'test body - 测试',
            // 'openid' => 'onkVf1FjWS5SBIixxxxxxx',
        ];
        // var_dump($this->config);die;
        $pay = Pay::wechat($this->config)->mp($order);
        // $pay->appId;
        // $pay->timeStamp;
        // $pay->nonceStr;
        // $pay->package
        // $pay->signType
    }

    public function notify()
    {
        $pay = Pay::wechat($this->config);
        try{
            $data = $pay->verify(); // 是的，验签就这么简单！

            Log::debug('Wechat notify', $data->all());
        } catch (\Exception $e) {
            // $e->getMessage();
        }
        return $pay->success()->send();// laravel 框架中请直接 `return $pay->success()`
    }


    public function get_openid($code = '')
    {
        if($code == ''){
            // 获取code
            $result_url = urlEncode("https://www.itarvin.info:8084/index/index/get_openid");
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->config['app_id']."&redirect_uri=".$result_url."&response_type=code&scope=snsapi_base &state=state#wechat_redirect";
            var_dump($url);die;
            $code = curl_post($url);
            var_dump($code);
        }else{
            $code = $_GET['code'];//获取code
            var_dump("====");die;
            $weixin =  file_get_contents("https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->config['app_id']."&secret=".$this->config['APPSECRET']."&code=".$code."&grant_type=authorization_code");//通过code换取网页授权access_token
            $jsondecode = json_decode($weixin); //对JSON格式的字符串进行编码
            $array = get_object_vars($jsondecode);//转换成数组
            $openid = $array['openid'];//输出openid
            echo $openid;
        }
    }
}
