<?php
header('Content-Type: text/html;charset=utf8');
class ClientController extends Controller{
	public function actionIndex(){
		$this->render('index');
	}
    public function actions() {
        return array(
            // captcha action renders the CAPTCHA image displayed on the contact page
            'captcha'=>array(
                'class'=>'CCaptchaAction',
                'maxLength'=>6,
                'minLength'=>'6',
            ),
          );
    }

    //>>>>>>>>>>>>>>>>>>>>>>>>>>>>接收参数并验证开始<<<<<<<<<<<<<<<<<<<<<<<<<<<<
    public function actionCheckMobile(){
        $client = new Client();
        $openid = $_POST['openid'];
        $openid = $this->arr2str($openid);
        $mobile = $_POST['mobile'];
        if(!empty($_POST)){
            if(preg_match("/^13[0-9]{1}[0-9]{8}$|15[0189]{1}[0-9]{8}$|18[0-9]{9}$/",$mobile)){
                $criteria=new CDbCriteria;
                $criteria->select='openid,mobile';
                $criteria->addcondition(array('openid=:openId','mobile=:Mobile'),'or');
                $criteria->params=array(':openId'=>$openid,':Mobile'=>$mobile); //复制给  select mobile,openid from tbl_client where openid=openid or mobile=mobile
                $weChatUser=Client::model()->find($criteria);
                    if(isset($weChatUser['mobile'])){
                        $client->updateAll(array('mobile'=>$mobile),'openid=:openId',array(':openId'=>$openid));
                    }else{
                        $client->mobile = $mobile;
                        $client->openid = $openid;
                        $client->time = time();
                        $client->save();
                    }
                $content = "您的手机号码为：".$mobile.",确认请回复【1】，如果有误，请重新点击【会员专区】->【手机绑定】";
            }else{
                $content = "对不起，您输入的手机号码格式不正确。如需绑定请重新点击【会员专区】->【手机绑定】";
            }
            echo $content;
        }
    }
    //>>>>>>>>>>>>>>>>>>>>>>>>>>>验证用户是否已经存在开始<<<<<<<<<<<<<<<<<<<<<<<<<
        public function actionBind(){
            if(!empty($_POST)){
                    $openid = $_POST['openid'];
                    $criteria=new CDbCriteria;
                    $criteria->select='openid,flag';   //select code from tbl_client
                    $criteria->addCondition(array('openid=:openId','flag=1'));
                    $criteria->params=array(':openId'=>$openid[0]); //复制给  select openid,flag from tbl_client where openid=openid and flag=flag
                    $weChatUser=Client::model()->find($criteria); // $params is not needed
                    if($weChatUser) {
                        $result = '您已经是会员了!!!';
                    }else{
                        $result = '尊敬的用户，您好！欢迎加入【昊祥科技有限公司】会员俱乐部，请回复您的手机号码，完成身份绑定。';
                    }
                    echo $result;
            }
        }
    //>>>>>>>>>>>>>>>>>>>>>>>>>>>验证用户是否已经存在结束<<<<<<<<<<<<<<<<<<<<<<<<<



    //>>>>>>>>>>>>>>>>>>>>>>>>>>>>接收参数并验证开始<<<<<<<<<<<<<<<<<<<<<<<<<<<<
    public function actionVerifyCode() {
        if(!empty($_POST)){
            $code = $_POST['code'];   //接收用户请求参数->验证码
            $openid = $_POST['openid'];    //接收用户请求参数->id
            $criteria=new CDbCriteria;      //实例化
            $criteria->select='code,openid,time';   //select code,openid from tbl_client
            $criteria->addCondition(array('openid=:openId','code=:code','time>:Time'));   //where openid=id and code=验证码
            $criteria->params=array(':openId'=>$openid[0], 'code'=>$code,'Time'=>time()); //复制给
            $weChatUser=Client::model()->find($criteria);
            if(isset($weChatUser)) {
                $client =  new Client();
                $client->updateAll(array('flag'=>1),'openid=:openId',array(':openId'=>$openid[0]));
                $result = '恭喜您已绑定成功!';
            }else{
                $result = '验证已超时,请重新输入验证码，或者您输入不正确,请重新点击【会员专区】->【手机绑定】';
            }
            echo $result;
        }
    }
    //>>>>>>>>>>>>>>>>>>>>>>>>>>>>接收参数并验证结束<<<<<<<<<<<<<<<<<<<<<<<<<<<<

    //>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>验证码开始<<<<<<<<<<<<<<<<<<<<<<<<<<<<
    //>>>>>>>>>>>>>>>>>>>>生成并发送验证码开始<<<<<<<<<<<<<<<<<<<<<<<<<
    public function actionSendCode(){
        $authnum = '';
        $strarr = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz';
        $openid = $_POST['openid'];
        $code = $_POST['code'];
        $openid = $this->arr2str($openid);
        $len = strlen($strarr); //得到字串的长度;
        //循环随机抽取六位前面定义的字母和数字;
        for($i=1;$i<=6;$i++){           //每次随机抽取一位数字;从第一个字到该字串最大长度,
            $num=rand(0,$len-1);            //减1是因为截取字符是从0开始起算;这样34字符任意都有可能排在其中;
            $authnum .= $strarr[$num];    //将通过数字得来的字符连起来一共是六位;
        }
        if($code==1){
                $criteria=new CDbCriteria;      //实例化
                $criteria->select='openid';   //select code,openid from tbl_client
                $criteria->addCondition(array('openid=:openId'));   //where openid=id and code=验证码
                $criteria->params=array(':openId'=>$openid); //复制给
                $weChatUser=Client::model()->find($criteria);
                if($weChatUser){
                    if($authnum){
                        $client = new Client();
                        $client->updateAll(array('code'=>$authnum,'time'=>time()+300),'openid=:openId',array(':openId'=>$openid));
                        $content = '尊敬的用户，您已确认需绑定的手机号码，我们将发送验证码【'.$authnum.'】至该手机号码，请您于5分钟内微信回复您收到的验证码，以确认绑定。';
                }
            }else{
                    $content = '请您重新点击【会员专区】->【手机绑定】';
                }
        }
        echo $content;
    }
    //>>>>>>>>>>>>>>>>>>>>生成并发送验证码结束<<<<<<<<<<<<<<<<<<<<<<<<<

    //>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>验证码结束<<<<<<<<<<<<<<<<<<<<<<<<<<<<
    /**
     * 字符串到数组
     */
    function str2arr($str,$delimiter=","){
        return explode($delimiter,$str);
    }
    /**
     * 数组到字符串
     */
    function arr2str($arr,$delimiter=","){
        return implode($delimiter,$arr);
    }







}