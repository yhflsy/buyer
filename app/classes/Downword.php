<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Downword {

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

    // 下载文件
    public function downfile($title = '',$url='') {
        $title = str_replace(',', '', $title);
        $title = str_replace(' ', '', $title);
        $title = preg_replace("#([^0-9-a-z-\x{4e00}-\x{9fa5}])+#ui", '', $title);
        $filename = "{$title}.doc";
        $url = 'http://'.$_SERVER['SERVER_NAME'].'/'.$url;
        $html = file_get_contents($url, 5);
        $html = preg_replace('#class="line_print_box"#', 'style="display:none"', $html);
        $html = preg_replace('#class="send_email_box"#', 'style="display:none"', $html);
        $html = preg_replace('#邮箱地址：|发送邮件|打印本页|导出word|关闭窗口#', '', $html);
        $filename = iconv("UTF-8", "GBK", $filename);
        $this->down($filename, $html);
        exit();
    }

    // 下载word文件
    public function down($filename, $html) {
        ob_start();
        header("Content-Type:application/octet-stream; charset=UTF-8");
        // header("Content-Type:application/msword; charset=UTF-8");
        header("Content-Disposition: attachment; filename=$filename");
        // header("Content-Disposition: inline; filename=$filename");
        echo $html;
        ob_end_flush();
        exit();
    }
    
    /**
     * dengquan
     * $title 标题
     * $html  内容
     * pdf下载
     * @return 
     */
    public static function downPdf($title,$html) {
        include_once(DOCROOT . '../plugins/tcpdf/tcpdf.php');
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        // 设置文档信息   
        $pdf->SetCreator('tripb2b.com');
        $pdf->SetAuthor('tripb2b.com');
        $pdf->SetTitle($title);
        $pdf->SetSubject('TCPDF Tutorial');
        $pdf->SetKeywords('TCPDF, PDF, PHP');
        // 设置页眉和页脚信息   
        $pdf->SetHeaderData('tripb2b.png', 30, '', '全国旅游批发商联盟', array(0, 64, 255), array(0, 64, 128));
        $pdf->setFooterData(array(0, 64, 0), array(0, 64, 128));
        // 设置页眉和页脚字体   
        $pdf->setHeaderFont(Array('stsongstdlight', '', '10'));
        $pdf->setFooterFont(Array('helvetica', '', '8'));
        // 设置默认等宽字体   
        $pdf->SetDefaultMonospacedFont('courier');
        // 设置间距   
        $pdf->SetMargins(15, 27, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        // 设置分页   
        $pdf->SetAutoPageBreak(TRUE, 25);
        // set image scale factor   
        $pdf->setImageScale(1.25);
        // set default font subsetting mode   
        $pdf->setFontSubsetting(true);
        //设置字体   
        $pdf->SetFont('stsongstdlight', '', 14);
        $pdf->AddPage();
        $pdf->setPageMark();
//        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true); //使用writeHTMLCell打印文本
        //输出PDF  i  预览   d下载
        $pdf->Output('t.pdf', 'I');
        exit;
    }
    
    /**
     * dengquan
     * $title 标题
     * $html  内容
     * pdf下载
     * @return 
     */
    public static function downMpdf($title,$content){
        $content = self::setFilter($content);
        $header =  
        //页眉设置  
         '<pageheader name="myHeader" content-left="" content-center='.$title.' content-right="{DATE Y-m-d}" '  
                . 'header-style="font-family:sans-serif; font-size:8pt;color:#880000;" '  
                . 'header-style-right="font-size:8pt; font-weight:normal; font-style:italic;color:#880000;" line="on" />'.  
        //页脚设置  
                '<pagefooter name="myFooter" content-left="" content-center="馨途平台" content-right="{PAGENO}" '  
                . 'footer-style="font-family:sans-serif; font-size:8pt; font-weight:normal; color:#880000;"'  
                . ' footer-style-left="" line="on" />'.  
        //封面内容  
                '<h1 align="center"></h1><h2 align="right"></h2>  
                    <br><br><br><br><br>  
                 <h2 align="right">创建者：tripb2b.com</h2>'.'<h2 align="right">创建日期：'.date("Y-m-d H:i:s")  
        //关键代码，关联上面的<pageheader>代码，使页眉，页脚和目录生成，具体的功能，其实看字段也能猜测出，或者尝试该值看效果。这里奇偶也的页眉页脚相同，根据需求可以设置为不同的格式。       
         . '<tocpagebreak  font="mono" font-size="16"  paging="on" links="on"   
        resetpagenum="1" pagenumstyle="1"  
        odd-header-name="myHeader" odd-header-value="on" even-header-name="myHeader" even-header-value="on"   
        odd-footer-name="myFooter" odd-footer-value="on" even-footer-name="myFooter" even-footer-value="on"    
        toc-odd-header-name="myHeader" toc-odd-header-value="on" toc-even-header-name="myHeader"   
        toc-even-header-value="on" toc-odd-footer-name="" toc-odd-footer-value="on" toc-even-footer-name="" toc-even-footer-value="on"  
        toc-preHTML="<h1 align="center">目 录</h1>" />';//使用转义符号<==<    >==> ,即写入的html代码要用转义符号   
        $html = $header.$content;  
        include_once(DOCROOT . '../plugins/mpdf/mpdf.php');
        $mpdf = new MPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        //设置字体，解决中文乱码  
        $mpdf -> useAdobeCJK = TRUE;  
        $mpdf ->autoScriptToLang = true;  
        $mpdf -> autoLangToFont = true;  
//        $mpdf->shrink_tables_to_fit = 0;
        //$mpdf-> showImageErrors = true; //显示图片无法加载的原因，用于调试，注意的是,我的机子上gif格式的图片无法加载出来。  
        //设置pdf显示方式  
        $mpdf->SetDisplayMode('fullpage');  
        //目录相关设置：  
        //Remember bookmark levels start at 0(does not work inside tables)H1 - H6 must be uppercase  
        //$this->h2bookmarks = array('H1'=>0, 'H2'=>1, 'H3'=>2);  
        $mpdf->h2toc = array('H3'=>0,'H4'=>1,'H5'=>2);  
        $mpdf->h2bookmarks = array('H3'=>0,'H4'=>1,'H5'=>2);  
        $mpdf->mirrorMargins = 1;  
        //是否缩进列表的第一级  
        $mpdf->list_indent_first_level = 0;  
        $mpdf->WriteHTML($html);  //$html中的内容即为变成pdf格式的html内容。  
        //输出pdf文件  
        $mpdf->Output(urlencode($title).'.pdf','D'); //'I'表示在线展示 'D'则显示下载窗口
        exit;  
    }

    //过滤全角半角
    public static function setFilter($html){
        $arr=array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
            '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
            'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
            'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
            'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
            'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
            'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
            '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
            '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
            '》' => '>',
            '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
            '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
            '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
            '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
            '　' => ' ');
        return  strtr($html,$arr);
    }
}
