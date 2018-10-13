<?php

/**
 * 控制器公共
 * @author EcaiYing
 * @version v1.0
 */
class Controller_Common extends Controller_Base {

    /**
     * 结果集
     * @var type 
     */
    private $result;

    public function before() {
        $action = $this->request->action();
        if (strcasecmp($action, 'upload') != 0) {
            parent::before();
        }
    }
    
    // 上传文件
    public function action_upload() {
        $userid = Filter::int('userid',2);

        if (!$userid && $this->user['companyid']){
            $userid = $this->user['companyid'];
        }

        $userpath = Kohana::$config->load('site.params.upload') . sprintf('%02d', $userid % 100) . '#' . $userid;
        $filename = Filter::txt('upfile');
        $uploadtype = Filter::int('type');
        $width = Filter::int('w');
        $height = Filter::int('h');
        $url = Common::upload($filename, $userpath, $uploadtype);
        $width > 0 && $url = Common::showPic($url, $width, $height);
        echo $url;
        exit();
    }
    
    /**
     * [消息回调]
     * @author yhf
     * @version v1.0
     */
    public function action_newMsg() {
        //ID是否存在
        $companyid = Filter::int('companyid');
        if (!$companyid){
            exit();
        }

        $msg = $this->jrequest('stat', self::GET, array('companyid' => $companyid), 'message')->result();
        if($msg){
            $this->cache->set("msg" . $companyid, json_encode($msg), 3600);
        }
        exit (json_encode(['result'=> TRUE]));
    }
    
     // 获取扫描二维码
    public function action_getQRcode() {
        $url = Filter::str('url');
        $aurl = md5($url);
        $aurl = dirname(DOCROOT) . '/cache/qrcode/' . pack("a2", $aurl) . '/' . $aurl . '.png';
        ob_clean();
        header('Content-Type: image/png');
        
        if (is_file($aurl)) {
            exit(file_get_contents($aurl));
        } else {// 保存
            $path = dirname($aurl);
            is_dir($path) || mkdir($path, 0777, true);
            Common::getQRcode($url, $aurl, 6, 'M');
            file_put_contents('php://output', file_get_contents($aurl));
        }
        exit;
    }
    
    /**
     * [ueditor文件上传]
     * @return [type] [description]
     * @author
     * @version v1.0
     */
    public function action_ueditor() {
        $userid = Filter::int('uid');
        $width = Filter::int('w', 300);
        $height = Filter::int('h', 300);
        $type = Filter::int('type', 1);
        $action = Filter::str('action', '');
        $basedir = Kohana::$config->load('site.params.upload') . sprintf('%02d', $userid % 100) . '/a/' . $userid;

        if ($action == 'tmpImg') {  // 涂鸦临时图片
            $type = 1;
            $basedir = str_replace('/a/', '/tmp/', $basedir);
        }

        $code = Kohana::$config->load('ueditor.code');

        if ($type == 2) {           // 远程图片
            $picurl = Filter::str("upfile");
            $uri = str_replace("&amp;", "&", htmlspecialchars($picurl));
            $imgurl = explode("ue_separate_ue", $uri);
            $tmpnames = array();
            
            foreach ($imgurl as $k => $v) {
                $ret = Common::showRemotePic($v, $width, $height, $userid, 4);
                $ret || array_push($tmpnames, 'error');
                array_push($tmpnames, $ret);
            }
            
            die("{'url': '" . implode("ue_separate_ue", $tmpnames) . "','tip': '远程图片抓取成功！', 'srcUrl': '{$picurl}'}");
        } else if ($type == 3) {    // 涂鸭
            $content = base64_decode(Filter::str('content'));
            
            if (!isset($content{0}))
                die("{'state': '{$code[5]}'}");
                
            try {
                is_dir($basedir) || mkdir($basedir, 0777, true);
                $filename = $basedir . '/' . time() . rand(1, 1000) . '.png';
                file_put_contents($filename, $content, FILE_USE_INCLUDE_PATH);
                die("{'url':'" . str_replace(Kohana::$config->load('site.params.upload'), '', $filename) . "','state':'{$code['0']}'}");
            } catch (Kohana_Exception $e) {
                die("{'state': '{$code['MOVE']}'}");
            }
        }

        $filename = 'upfile';
        $imgurl = Common::upload($filename, $basedir, $type);

        $width > 0 && $imgurl = Common::showPic($imgurl, $width, $height);
        $action && die("<script>parent.ue_callback('{$imgurl}','{$code['0']}')</script>");
        die("{'url':'{$imgurl}','state':'SUCCESS'}");
    }
    
}
