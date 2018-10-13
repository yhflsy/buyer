<?php

/*
 * 导出WORD 发送Email 
 */

class Wordemail {

    static $_instance;

    private function __construct() {
        
    }

    /**
     * 单例引用
     */
    public static function instance() {
        if (self::$_instance == NULL) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }
    
    // get html
    public function tohtml($url = '') {
        if(Kohana::$environment != Kohana::PRODUCTION){
            $url = str_replace('pre.ht.seller.tripb2b.com', '10.0.0.76:1002', $url);
            $url = str_replace('seller.happytoo.cn', '10.0.0.141:1002', $url);
        }
    
        try {
            $ch = curl_init();
            $timeout = 10;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $html = curl_exec($ch);
            curl_close($ch);
        } catch (Exception $e) {
            $html = file_get_contents($url, 5);
        }
        return $html;
    }

    // 下载文件
    public function downfile($title = '',$html='') {
        $title = str_replace(',', '', $title);
        $title = str_replace(',', '', $title);
        $title = str_replace('•', '', $title);
        $title = preg_replace("#([^0-9-a-z-\x{4e00}-\x{9fa5}])+#ui", '', $title);

        $filename = iconv("UTF-8", "GBK//IGNORE", "{$title}.doc");
        if(empty($filename)){
            $filename = iconv("UTF-8", "GBK", '线路名称.doc');
        }

        $this->down($filename, $html);
        exit();
    }

    // 下载word文件
    public function down($filename, $html) {
        ob_end_clean();
        ob_start();
        header("Content-Type:application/octet-stream; charset=UTF-8");
//        header("Content-Type:application/msword; charset=UTF-8");
        header("Content-Disposition: attachment; filename=$filename");
//         header("Content-Disposition: inline; filename=$filename");
         if(Kohana::$environment == Kohana::PRODUCTION){
//           $html = $this->getWordDocument($html);
         }

        echo $html;
        ob_end_flush();
        exit();
    }
    
    /**
     * 发送邮件
     * @param array $to array('name'=>'xxx@xx.com')邮件发送的对象
     * @param string $subject 邮件主题
     * @param string $content	邮件内容
     * @param array $attachment array('attachment name'=>'attachment path') 附件
     * @return boolean
     */
    public static function sendMail(array $to, $subject, $content, array $attachment = array()) {
        $mail_config = Kohana::$config->load("site.params.smtpserver");
        $mail = new PHPMailer ();
        $mail->IsSMTP(); // telling the class to use SMTP
        $mail->SMTPDebug = $mail_config["SMTPDebug"]; // enables SMTP debug information (for testing)  // 2 = messages only
        $mail->SMTPAuth = $mail_config["SMTPAuth"]; // enable SMTP authentication
        $mail->SMTPSecure = $mail_config["SMTPSecure"]; // sets the prefix to the servier
        $mail->Host = $mail_config['Host']; // sets GMAIL as the SMTP server
        $mail->Port = $mail_config["Port"]; // set the SMTP port for the GMAIL server
        $mail->Username = $mail_config["Username"]; // GMAIL username
        $mail->Password = $mail_config["Password"]; // GMAIL password
        $mail->SetFrom($mail_config["SetFrom"], $mail_config["SetFromName"]);
        $mail->AddReplyTo($mail_config["SetFrom"], $mail_config["SetFromName"]);
        $mail->Subject = "=?UTF-8?B?" . base64_encode($subject) . "?= ";
        if ($mail_config["IsHTML"])
            $mail->MsgHTML($content);
        else
            $mail->Body = $content;
        //$mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test

        $tonames = array_keys($to);
        $pathnames = array_keys($attachment);
        foreach ($tonames as $toname)
            $mail->AddAddress($to[$toname], is_int($toname) ? '' : $toname );

        foreach ($pathnames as $pathname) {
            $attachment[$pathname] = iconv('GBK', 'UTF-8', $attachment[$pathname]);
            $mail->AddAttachment($attachment[$pathname], is_int($pathname) ? '' : $pathname );
        }

        return $mail->Send();
    }
    
    
    public function getWordDocument( $content , $absolutePath = "" , $isEraseLink = true ) {
        $mht = new MhtFileMaker();
        if ($isEraseLink)
            $content = preg_replace('/<a\s*.*?\s*>(\s*.*?\s*)<\/a>/i' , '$1' , $content);   //去掉链接
     
        $images = array();
        $files = array();
        $matches = array();
        //这个算法要求src后的属性值必须使用引号括起来
        if ( preg_match_all('/<img[^>]*src\s*?=[\"\'](.*?)[\"\'][^>]*?>/',$content ,$matches ) )
        {
            $arrPath = $matches[1];
            for ( $i=0;$i<count($arrPath);$i++)
            {
                $path = $arrPath[$i];
                $imgPath = trim( $path );
                if ( $imgPath != "" )
                {
                    $files[] = $imgPath;
                    if( substr($imgPath,0,7) == 'http://')
                    {
                        //绝对链接，不加前缀
                    }
                    else
                    {
                        $imgPath = $absolutePath.$imgPath;
                    }
                    
                    $images[] = $imgPath;
                }
            }
         }
        $mht->AddContents("tmp.html",$mht->GetMimeType("tmp.html"),$content);

        for ( $i=0;$i<count($images);$i++)
        {
            $image = $images[$i];
            if ( @fopen($image , 'r') )
            {
                $imgcontent = @file_get_contents( $image );
                if ( $imgcontent )
                    $mht->AddContents($files[$i],$mht->GetMimeType($image),$imgcontent);
            }
            else
            {
                echo "file:".$image." not exist!<br />";
            }
         }
    
         return $mht->GetFile();
    }
	
	// 获取远程文件内容
	public static function html($url) {
	//	$servername = substr($_SERVER ['LOCAL_ADDR'], 0, 5) == "10.0." ? ($_SERVER ['LOCAL_ADDR'] . ":" . $_SERVER['SERVER_PORT']) : $_SERVER ['SERVER_NAME'];
        
	    if(Kohana::$environment == Kohana::PRODUCTION){
            $servername = 'buyer.tripb2b.com';
        }else{
            $servername = (substr($_SERVER ['LOCAL_ADDR'], 0, 9) == "10.0.0.76")  ? 'pre.ht.buyer.tripb2b.com': (substr($_SERVER ['LOCAL_ADDR'], 0, 5) == "10.0." ? 'buyer.tripb2b.com' : $_SERVER ['SERVER_NAME'] ) ;
            !$servername  &&  $servername = $_SERVER['HTTP_HOST'];
        }
        $url = 'http://' . $servername . '/' . $url;  

		try {
	            $ch = curl_init();
	            $timeout = 10;
	            curl_setopt($ch, CURLOPT_URL, $url);
	            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	            $html = curl_exec($ch);
	            curl_close($ch);
	        } catch (Exception $e) {
	            $html = file_get_contents($url, 5);
	        }
	        return $html;
	
	}
}
