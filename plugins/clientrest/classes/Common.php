<?php

/**
 * 系统公共静态类
 * @author TanJiajun
 * @date 2013年12月18日
 */
final class Common {
    
    /**
     * 将二维数组处理
     * @param array $array 被处理的数组 //不能为空数组
     * @param string $valuename $array的一个value(一维或二维数组)中的某个key($valuename)对应的值作为$arr的值【$valuename对应的值可以为任意类型】
     * @param string $keyname $array的一个value(一维或二维数组)中的某个key($keyname)对应的值作为$arr的键【$keyname对应的值只能为整型、字符串型】
     * @return array $arr
     */
    public static function arr2ToArr($array, $valuename ='', $keyname ='') {
        if(!$valuename && !$keyname){
            return $array;
        }

        $arr = array();
        $assigned = $keyname ? 'keyname_' : 'key';
        $assign = $valuename ? 'valuename_' : 'value';
        foreach ($array as $key => $value) {
            $keyname_ = $value[$keyname];
            $valuename_ = $value[$valuename];
            $arr[${$assigned}] = ${$assign};
        }

        return $arr;
    }

    //在数组中指定位置插入键值固定的数组
    public static function array_insert (&$array, $position, $insert_array) {
        $first_array = array_splice ($array, 0, $position);
        $array = array_merge ($first_array, $insert_array, $array);
    }

    /** 对二维数组按照 某个key的值进行分组，支持多key分组
     * @param $arr 二维数组
     * @param $key 要分组的key/arrayGroupBy($arr, $key1, $key2) 对arr先按key1分组，之后针对每个分组再按key2分组，构建出多维数组
     * @return array 分组后的多维数组
     */
    public static function arrayGroupBy($arr, $key){
        $grouped = [];
        foreach ($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }

        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array(array('Common','arrayGroupBy'), $parms);
            }
        }
        return $grouped;
    }

    public static function array_column($array, $column_key, $index_key = null){
        if (!function_exists('array_column')) {
            return array_reduce($array, function ($result, $item) use ($column_key, $index_key)
            {
                if (null === $index_key) {
                    $result[] = $item[$column_key];
                } else {
                    $result[$item[$index_key]] = $item[$column_key];
                }

                return $result;
            }, []);

        }else{
            return array_column($array, $column_key, $index_key = null);
        }
    }


    public static function array2list($array, $default = null){
        $keys = array_keys($array);
        $arr = array();
        $total = count($array[$keys[0]]);
        for($i =0; $i < $total; $i++){
            foreach ($keys as $v) {
                $arr[$i][$v] = Arr::get($array[$v], $i, $default);
            }
        }

        return $arr;
    }

    /**
     * [将数组生成select下拉选项]
     * @param array $data    [一维数组或二维数组]
     * @param int $chkval  [选中option的value]
     * @param boolean $all   [是否需要‘请选择’]
     * @param string $value   [二维数组中要作为option的value的key]
     * @param string $text    [二维数组中要作为option的text的key]
     * @return string
     */
    public static function arrayToSelect($data,$chkval = -1, $all = true,  $value='id', $text='name') {
        if(empty($data) || !is_array($data)){
            return '';
        }
        $str = $all ? "<option value='-1'>请选择</option>" : '';
        foreach ($data as $k => $v) {
            if(is_array($v)){
                $k = $v[$value];
                $v = $v[$text];
            }

            $chkflag = ($k == $chkval) ? ' selected="selected"' : '';
            $str .= "<option value='{$k}' {$chkflag}>{$v}</option>";
        }
        return $str;
    }

    static public function exportExcel($titileArray, $datas, $title = '') {
        header("Content-Type: text/csv; charset=UTF-8"); 
        Kohana::load(DOCROOT . '../plugins/PHPExcel/1.7.9/PHPExcel.php');
        Kohana::load(DOCROOT . '../plugins/PHPExcel/1.7.9/PHPExcel/Writer/IWriter.php');
        Kohana::load(DOCROOT . '../plugins/PHPExcel/1.7.9/PHPExcel/Writer/Excel2007.php');
        Kohana::load(DOCROOT . '../plugins/PHPExcel/1.7.9/PHPExcel/Writer/Excel5.php');
        Kohana::load(DOCROOT . '../plugins/PHPExcel/1.7.9/PHPExcel/IOFactory.php');

        $objExcel = new PHPExcel();

        //设置属性 (这段代码无关紧要，其中的内容可以替换为你需要的)
        $objExcel->getProperties()->setCreator("happytoo.cn technology team");
        $objExcel->getProperties()->setLastModifiedBy("happytoo.cn technology team");
        $objExcel->getProperties()->setTitle("happytoo.cn technology team");
        $objExcel->getProperties()->setSubject("happytoo.cn technology team");
        $objExcel->getProperties()->setDescription("happytoo.cn technology team");
        $objExcel->getProperties()->setKeywords("happytoo.cn technology team");
        $objExcel->getProperties()->setCategory("happytoo.cn technology team");
        $objExcel->setActiveSheetIndex(0);

        $ex = '2003';
        //表头
        for ($i = 0; $i < count($titileArray); $i++) {
            $objExcel->getActiveSheet()->setCellValue(chr($i + 97) . '1', $titileArray[$i]);
        }

        //获取键名
        $cols = array();
        if ($datas) {
            foreach ($datas[0] as $key => $value)
                $cols[] = $key;
        }
        $i = 0;
        foreach ((array)$datas as $k => $v) {
            $u1 = $i + 2;
            /* ----------写入内容------------- */
            foreach ($cols as $key => $c)
                $objExcel->getActiveSheet()->setCellValue(chr(97 + $key) . $u1, $v[$c]);
            $i++;
        }

        $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
        $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');

        // 设置页方向和规模
        $objExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $objExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $objExcel->setActiveSheetIndex(0);
        $timestamp = time();
        ob_end_clean();//清除缓存
        if (!empty($title))
            $timestamp = $title;
        if ($ex == '2007') { //导出excel2007文档
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $timestamp . '.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
            $objWriter->save('php://output');
            exit;
        } else {            //导出excel2003文档
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $timestamp . '.xls"');
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
        }
    }

    //线路导出 copy by mconline
     static public function exportExcelMc($titileArray, $datas, $title = '',$othterArray) {
        Kohana::load(DOCROOT . '../plugins/PHPExcel/1.7.9/PHPExcel.php');
        Kohana::load(DOCROOT . '../plugins/PHPExcel/1.7.9/PHPExcel/Writer/IWriter.php');
        Kohana::load(DOCROOT . '../plugins/PHPExcel/1.7.9/PHPExcel/Writer/Excel2007.php');
        Kohana::load(DOCROOT . '../plugins/PHPExcel/1.7.9/PHPExcel/Writer/Excel5.php');
        Kohana::load(DOCROOT . '../plugins/PHPExcel/1.7.9/PHPExcel/IOFactory.php');

        $objExcel = new PHPExcel();

        //设置属性 (这段代码无关紧要，其中的内容可以替换为你需要的)
        $objExcel->getProperties()->setCreator("happytoo.cn technology team");
        $objExcel->getProperties()->setLastModifiedBy("happytoo.cn technology team");
        $objExcel->getProperties()->setTitle("happytoo.cn technology team");
        $objExcel->getProperties()->setSubject("happytoo.cn technology team");
        $objExcel->getProperties()->setDescription("happytoo.cn technology team");
        $objExcel->getProperties()->setKeywords("happytoo.cn technology team");
        $objExcel->getProperties()->setCategory("happytoo.cn technology team");
        $objExcel->setActiveSheetIndex(0);
        
        $ex = '2007';
        $objExcel->getActiveSheet()->setCellValue(chr(97) . '1', $othterArray['title']."  ".$othterArray['columtitle']."  ".$othterArray['contents']['name']."  ".$othterArray['contents']['tel']."  ".$othterArray['contents']['address']);
        $objExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER); 
        $objExcel->getActiveSheet()->getStyle('A1')->getAlignment()->setWrapText(true); //换行
        //表头
        for ($i = 0; $i < count($titileArray); $i++) {
            $objExcel->getActiveSheet()->setCellValue(chr($i + 97) . '2', $titileArray[$i]);
            if ($i == 0){ //设置列宽
                $objExcel->getActiveSheet()->getColumnDimension(chr($i + 97))->setWidth(80);
            }else if($i == 3){
                 $objExcel->getActiveSheet()->getColumnDimension(chr($i + 97))->setWidth(80);
            }else{
                $objExcel->getActiveSheet()->getColumnDimension(chr($i + 97))->setWidth(15);
            }
        }
        
        $styleArray1 = array(
            'font' => array(
               'bold' => true,
               'color'=>array(
                   'argb' => '00000000',
               ),
            ),
            'alignment' => array(
               'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );
        
        $styleArray2 = array(
            'font' => array(
               'bold' => false,
               'color'=>array(
                   'argb' => '00000000',
               ),
            ),
            'alignment' => array(
               'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ),
        );
    
    
         // 将A1单元格设置为加粗，居中
        $objExcel->getActiveSheet()->getStyle('A1:G2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER); 
        $objExcel->getActiveSheet()->getStyle('A2:G2')->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        $objExcel->getActiveSheet()->getStyle('A2:G2')->applyFromArray($styleArray1);
        $objExcel->getActiveSheet()->getStyle('A2:G2')->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        
        //获取键名
        $cols = array();
        if ($datas) {
            foreach ($datas[0] as $key => $value)
                $cols[] = $key;
        }
        $i = 0;
        foreach ($datas as $k => $v) {
            $u1 = $i + 3;
            /* ----------写入内容------------- */
            foreach ($cols as $key => $c){
                if ($key == 2){
                    $objExcel->getActiveSheet()->getCell(chr(97 + $key) . $u1)->getHyperlink()->setUrl($v[$c][1]); //设置url
                    $objExcel->getActiveSheet()->getColumnDimension(chr(97 + $key))->setWidth(53); //设置宽度
                    $objExcel->getActiveSheet()->getCell(chr(97 + $key) . $u1)->getHyperlink()->setTooltip('Navigate to website');
                    $objExcel->getActiveSheet()->getStyle(chr(97 + $key) . $u1)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
                    $objExcel->getActiveSheet()->setCellValue(chr(97 + $key) . $u1, $v[$c][0]);
                    $objExcel->getActiveSheet()->getStyle(chr(97 + $key) . $u1)->getAlignment()->setWrapText(true); //换行
                    $objExcel->getActiveSheet()->getStyle(chr(97 + $key). $u1)->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER); //设置垂直居中
                }else if($key == 3){
                    $objExcel->getActiveSheet()->getStyle(chr(97 + $key))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        
                    $objExcel->getActiveSheet()->getColumnDimension(chr(97 + $key))->setWidth(88);
                    $objExcel->getActiveSheet()->getStyle(chr(97 + $key))->getFont()->setSize(9);
                    $objExcel->getActiveSheet()->getStyle(chr(97 + $key))->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_LEFT);//水平向左
                    //$objExcel->getActiveSheet()->getStyle(chr(97 + $key))->applyFromArray($styleArray2);   // 设置水平居中
                    $objExcel->getActiveSheet()->getStyle(chr(97 + $key))->getAlignment()->setWrapText(true); //换行
                    $objExcel->getActiveSheet()->setCellValue(chr(97 + $key) . $u1, $v[$c]);
                }else{
                    $objExcel->getActiveSheet()->getStyle(chr(97 + $key))->getAlignment()->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
                    $objExcel->getActiveSheet()->getColumnDimension(chr(97 + $key))->setWidth(8);
                    $objExcel->getActiveSheet()->getStyle(chr(97 + $key))->applyFromArray($styleArray2);   // 设置水平居中
                    $objExcel->getActiveSheet()->setCellValue(chr(97 + $key) . $u1, $v[$c]);
                }
                //设置单元格边框
                $objExcel->getActiveSheet()->getStyle(chr(97 + $key). $u1)->getBorders()->getAllBorders()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);  // 设置边框
            }
            $i++;
    
        }
        //专线
        $count = count($datas);
        $j = 0;
        $key = 0;
        foreach($othterArray['colum'] as $kk => $vv){
            $uu = $j + $count + 4;
            
            $objExcel->getActiveSheet()->getCell(chr(97 + $key) . $uu)->getHyperlink()->setUrl($vv['url']);  
            $objExcel->getActiveSheet()->getCell(chr(97 + $key) . $uu)->getHyperlink()->setTooltip('Navigate to website');
            $objExcel->getActiveSheet()->getStyle(chr(97 + $key) . $uu)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
//            $objExcel->getActiveSheet()->setCellValue(chr(97 + $key) . $uu, $vv['title']);
            
            if(($kk+1) % 4 == 0){
                $j++;
                $key = 0;  
            }else{
                $key++;
            }
        }

          //合并单元格
        $objExcel->getActiveSheet()->mergeCells('A1:G1');
        
//        $objExcel->getActiveSheet()->setCellValue('A'.($uu+4), '');
        //设置表头行高
        $objExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(30);
        $objExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(20);
        
         //设置字体样式
        $objExcel->getActiveSheet()->getStyle('A1')->getFont()->setName('黑体');
        $objExcel->getActiveSheet()->getStyle('A1')->getFont()->setSize(11);
        $objExcel->getActiveSheet()->getStyle('A1')->getFont()->setBold(true);

        $objExcel->getActiveSheet()->getHeaderFooter()->setOddHeader('&L&BPersonal cash register&RPrinted on &D');
        $objExcel->getActiveSheet()->getHeaderFooter()->setOddFooter('&L&B' . $objExcel->getProperties()->getTitle() . '&RPage &P of &N');

        // 设置页方向和规模
        $objExcel->getActiveSheet()->getPageSetup()->setOrientation(PHPExcel_Worksheet_PageSetup::ORIENTATION_PORTRAIT);
        $objExcel->getActiveSheet()->getPageSetup()->setPaperSize(PHPExcel_Worksheet_PageSetup::PAPERSIZE_A4);
        $objExcel->setActiveSheetIndex(0);
        $timestamp = time();
        ob_end_clean();//清除缓存
        if (!empty($title))
            $timestamp = $title;
        if ($ex == '2007') { //导出excel2007文档
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $timestamp . '.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel2007');
            $objWriter->save('php://output');
            exit;
        } else {            //导出excel2003文档
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $timestamp . '.xls"');
            header('Cache-Control: max-age=0');
            $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');
            $objWriter->save('php://output');
            exit;
        }
    }
    
    /**
     * 上传文件
     * @param type $upload_file
     * @param type $filename
     * @param type $type
     * @param type $file_type
     * @param type $size
     * @return type
     */
    public static function upload($upload_file, $filename, $type = 1, $file_type = null, $size = null) {
        $validate = new Validation($_FILES);

        $validate->rule($upload_file, 'Upload::not_empty');
        $validate->rule($upload_file, 'Upload::valid');
        $file_type && $validate->rule($upload_file, 'Upload::type', array(':value', $file_type)); // array(':value', array('jpg', 'png', 'gif'))
        $size && $validate->rule($upload_file, 'Upload::size', array(':value', $size)); // K, MiB, GB

        if ($type == 1) {
            if ($validate->rule($upload_file, 'Upload::type', array(':value', array('jpg', 'png', 'gif', 'bmp', 'jpeg')))) {
                $savepath = str_replace('#', '/o/', $filename);
            }
        } elseif ($type == 2) {
            if ($validate->rule($upload_file, 'Upload::type', array(':value', array('jpg', 'png', 'gif', 'bmp', 'jpeg', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'zip', 'txt', 'rar')))) {
                $savepath = str_replace('#', '/a/', $filename);
            }
        } elseif ($type) {
            if ($validate->rule($upload_file, 'Upload::type', array(':value', explode(",", $type)))) {
                $savepath = str_replace('#', '/a/', $filename);
            }
        } else {
            if ($validate->rule($upload_file, 'Upload::type', array(':value', array('doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'pdf', 'zip', 'txt', 'rar')))) {
                $savepath = str_replace('#', '/a/', $filename);
            }
        }
        !is_dir($savepath) && @mkdir($savepath, 0777,true) && @chmod($savepath, 0777);
        $basename = time() . rand(1, 1000) . '.' . pathinfo($_FILES[$upload_file]['name'], PATHINFO_EXTENSION);
        $validate->check() && Upload::save($_FILES[$upload_file], $basename, $savepath, FALSE) && $upload_status = true;
        $error = $validate->errors();

        if ($error) {
            switch ($error[$upload_file][0]) {
                case 'Upload::not_empty':
                    $error = '上传文件不能为空！';
                    break;
                case 'Upload::valid':
                    $error = '上传的文件无效！';
                    break;
                case 'Upload::type':
                    $error = '上传文件类型不匹配！';
                    break;
                case 'Upload::size':
                    $error = '上传文件尺寸不匹配！';
                    break;
                default:
                    $error = '操作失败';
            }
        } else {
            $error = str_replace('\\', '/', str_replace(Kohana::$config->load('site.params.upload'), '', $savepath . DS . $basename)); //strtolower 去掉名称强制小写
        }

        return $error;
    }
    
    /**
     * 显示本地图片
     * @param type $url 本地地址
     * @param type $width
     * @param type $height
     * @param type $ratio 默认等比
     */
    static function showPic($url, $width = 800, $height = 600, $ratio = true) {

        $basedir = Kohana::$config->load('site.params.upload');
        $picfile = $basedir . $url;
//        $thumbfile = str_replace(array('/o/', '/a/'), '/t/', $picfile);
        $thumbfile = str_replace(array('/t/', '/a/'), '/o/', $picfile);
        $thumbdir = pathinfo($thumbfile, PATHINFO_DIRNAME);
        is_dir($thumbdir) || mkdir($thumbdir, 0777, true);
        $thumbfile = preg_replace('/[\.png|\.gif|\.jpg|\.jpeg]+$/i', '\\0', $thumbfile);

        if (file_exists($thumbfile))
            return str_replace($basedir, '', $thumbfile);

        file_exists($picfile) || file_put_contents($picfile, Request::factory($url)->execute()->render());

        if ($ratio) {
            Kohana_Image::factory($picfile)->resize($width, $height)->save($thumbfile);
        } else {
            Kohana_Image::factory($picfile)->crop($width, $height, 0, 0)->save($thumbfile);
        }

        return str_replace($basedir, '', $thumbfile);
    }

    // 格式化日期
    static function formatDate($format, $datetime, $week = 0) {
        $datetime = $datetime > 3000 ? $datetime : strtotime($datetime);

        if ($week) {
            $weeknames = [
                '日',
                '一',
                '二',
                '三',
                '四',
                '五',
                '六'
            ];
            $format = str_replace('w', $weeknames [date('w', $datetime)], $format);
        }

        return date($format, $datetime);
    }
    
    public static function windowClose($message = null) {
        ob_clean();
        header("Content-type:text/html; charset=utf-8");
        exit(sprintf("<script type='text/javascript'>%s%s</script>", $message ? "alert('" . $message . "');" : '', "window.opener=null;window.open('','_self');window.close();"));
     	//echo View::factory('msg', array('status' => 1, 'message' => $message, 'location' => 'javascript:windows.close();', 'class' => 'closepage')); exit;
    }
    
    /**
     * 解密url for 前段使用
     * @param type $url
     */
    static function urldecode($url) {
        base64_encode(base64_decode($url)) == $url && ($url = base64_decode($url));
        rawurlencode(rawurldecode($url)) == $url && ($url == rawurldecode($url));
        strstr($url, '.html') == false && ($url = 'javascript:{history.back()}');
        return $url;
    }
    
     /**
     * 线路状态的统一/支持一维二维数组
     * @param type $data //数据中gotime 可传可不传
     * @param type $st 1停止 -1 不限
     * return state 0 正常 1停止 2 客满 3截止 4删除 5过期
     */
    public static function lineState(&$data = null, $st = -1) {
        if (empty($data)){
            return $data;
        }

        $fileds = array('endtime', 'isstop', 'surplus' , 'deletetime', 'gotime');
        if (array_intersect_key(array_flip($fileds),$data)) {//表示$data是一维数组
            $flag = TRUE;
            $data = array($data);
        }

        $datetime = strtotime(date("Y-m-d"));
        $curtime = time();
        foreach($data as $i => $d){
            if($d[$fileds[3]] > 0){   //删除
                $data[$i]['state'] = 4;
            }else if($d[$fileds[1]] == 1 || $st == 1 || $d['state'] == 1){ // 停止
                $data[$i]['state'] = 1;
            }else if($d[$fileds[4]] && $datetime > $d[$fileds[4]]){ // 过期
                $data[$i]['state'] = 5;
            }else if($curtime > $d[$fileds[0]]){ // 截止
                $data[$i]['state'] = 3;
            }else if($d[$fileds[2]] <= 0){  //客满
                $data[$i]['state'] = 2;
            }else{    //正常
                $data[$i]['state'] = 0;
            }
            $data[$i]['isstop'] = $data[$i]['state'];   //为兼容其他地方的代码，仍可用 isstop 来存储状态值，但仍建议使用 state 字段
            $data[$i]['isstop']==5 && $data[$i]['isstop']==3;   //兼容其他地方的代码/使用isstop的时候没有5的状态
        }
        $flag && $data = $data[0];
        return $data;
    }
    
    /**
     * 
     * @param type $date 表单提交的值‘2015-06-12至2015-06-26’，针对这种情况
     * @param type $istimestamp 是否需要时间戳
     * @return array
     */
    public static function explodeDate($date, $istimestamp = TRUE){
        $arr = array();
        if(!$date || !is_string($date)){
            return $arr;
        }
        $arr = explode('至', $date);
        if(!empty($arr) && $istimestamp){
            $arr[0] && $arr[0] = strtotime($arr[0]);
            $arr[1] && $arr[1] = strtotime($arr[1])+86400;
        }
        return $arr;
    }
    
    // 根据时间戳返回早中晚
    static function structroutetime($time) {

        $time = (int)date('H', $time);
        if(5<=$time&&$time<=9)
            return '早上';
        if(9<=$time&&$time<=11)
            return '上午';
        if(11<=$time&&$time<=13)
            return '中午';
        if(13<=$time&&$time<=17)
            return '下午';
        if(17<=$time&&$time<=23)
            return '晚上';
        if(23<=$time)
            return '夜';
    }
    // 格式化根据时间返回早中晚
    static function structroutetime2($time) {

        $time = (int)$time;
        if(0<=$time&&$time<=5)
            return '凌晨';
        if(5<=$time&&$time<=9)
            return '早上';
        if(9<=$time&&$time<=11)
            return '上午';
        if(11<=$time&&$time<=13)
            return '中午';
        if(13<=$time&&$time<=17)
            return '下午';
        if(17<=$time&&$time<=23)
            return '晚上';
        if(23<=$time)
            return '夜';
    }
    
    /**
     * 处理往返交通 
     * @param type $str
     * @return string
     */
    public static function dealSellerTract2($str) {
        if (empty($str)) {
            return '---';
        }

        $reg = '/([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*)/';

        //判断是否有中转经停地
        $arr = explode(',', $str);

        if (count($arr) > 8) {
            $reg = '/([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*)/';
        }

        if (preg_match($reg, $str)) {
            $str = preg_replace($reg, '\3', $str);

            if ($str) {
                return $str;
            } else {
                return '---';
            }
        } else {
            if (strpos($str, ',,,') != false) {
                return '---';
            } else {
                return UTF8::substr($str, 0, 4);
            }
        }

        return $str;
    }
    
    /**
     * 处理往返交通 
     * @param type $str
     * @return string
     */
    public static function dealSellerTract1($str) {
        if (empty($str)) {
            return '---';
        }

        $reg = '/([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*)/';

        //判断是否有中转经停地
        $arr = explode(',', $str);

        if (count($arr) > 8) {
            $reg = '/([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*),([^,]*)/';
        }

        if (preg_match($reg, $str)) {
            $str = preg_replace($reg, '\1\2\3-\4:\5-\6:\7 \8', $str);

            if ($str && $str!="  ") {
                return $str;
            } else {
                return '---';
            }
        } else {
            if (strpos($str, ',,,') != false) {
                return '---';
            } else {
                return UTF8::substr($str, 0, 4);
            }
        }

        return $str;
    }
    
    /**
     * 处理往返交通
     * @param type $str
     * @return string
     */
    public static function dealSellerTract($str) {
        if (strstr($str, ',,,,,,,') || strstr($str, ',,,,,,,,') || substr($str, 0, 4) == ',,,0' || empty($str)) { //若是空交通，就不用处理成00:00/……了
            return '---';
        }

        $arr = explode(',', $str);
        $strs = '';

        foreach ($arr as $k => $v) {
            switch ($k) {
                case 0: $strs .= empty($v) ? '--/' : $v . '/';
                    break;
                case 1: $strs .= empty($v) ? '-- ' : $v . ' ';
                    break;
                case 2: $strs .= $v . ' ';
                    break;
                case 3:
                case 5: $strs .= ($v > 9) ? $v . ':' : '0' . (int) $v . ':';
                    break;
                case 4: $strs .= ($v > 9) ? $v . '/' : '0' . (int) $v . '/';
                    break;
                case 6: $strs .= ($v > 9) ? $v : '0' . (int) $v;
                    break;
                case 7: $strs .= ' ' . $v;
                    break;
                case 8:
                    if ($arr[7] != '直飞') {
                        $strs .= ' ' . $v;
                    }
                    break;
            }
        }

        return $strs;
    }
    
    /**
     * B2C配置生成
     * @param type $companyid
     * @return type
     */
    public static function setB2cCache($companyid = "", $path = "") {
        if (!$companyid) {
            return false;
        }
        try {
            $data = file_get_contents(Kohana::$config->load("site.params.host.b2c").'/common.setcache.html?cid='.$companyid);
            $data = json_decode($data,true);
            return $data['result'];
        } catch (Exception $ex) {
            return false;
        }
        
        return false;
    }
    
    public static function showB2cWebClose() {
        exit('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html xmlns="http://www.w3.org/1999/xhtml">
        <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>域名未备案或到期，已关闭！</title>
        <style type="text/css">
        div { text-align:center; width:700px; margin:-100px 0 0 -350px; position:absolute; z-index:1; left:50%; top:35%; height:200px; color:#333; padding-top:40px; background:#f7f7f7; border:solid 5px #ccc; font-family:微软雅黑; }
        strong { color:#f30; font-family:Georgia, "Times New Roman", Times, serif; }
        </style>
        </head>

        <body>
        <div>
            <h1>域名未备案或到期，已关闭！</h1>
            <!--<h2>如有任何问题请拨打：<strong>021-61673600</strong>、<strong>61400301</strong></h2>-->
        </div>
        </body>
        </html>');
    }

    
    // 验证手机 由王攀提供
    public static function verifyPhone($phone) {
        //return preg_match("/^13[\d]{9}$|^14[5,7]{1}\d{8}$|^15[^4]{1}\d{8}$|^17[0,6,7,8]{1}\d{8}$|^18[\d]{9}$/",$phone) ? true : false;    //王攀提供
        //参考https://www.cnblogs.com/zengxiangzhan/p/phone.html
        return preg_match("/^13[\d]{9}$|^14[5-9]{1}\d{8}$|^15[^4]{1}\d{8}$|^166[\d]{8}|^17[0-8]{1}\d{8}$|^18[\d]{9}|^19[8,9]{1}\d{8}$/",$phone) ? true : false;
    }
    
    // 
    public static function hoteltoArr($json) {
        $arr = json_decode($json,true);
        $arr = (array)$arr;
        $res = [];
        foreach ($arr as $k=>$v){
            $onehotel = explode('/', $v);
            $res[$k]['title'] = $onehotel[0];
            $res[$k]['id'] = $onehotel[1];
            $res[$k]['value'] = $v;
        }
        return $res;
    }
    
    public static function hoteltoArrforid($json) {
        $arr = json_decode($json,true);
        $arr = (array)$arr;
        $res = [];
        foreach ($arr as $k=>$v){
            $onehotel = explode('/', $v);
            $res[$onehotel[1]] = $onehotel[0];
        }
        return $res;
    }
    
    /**
     * 发送短信
     * @param type $mobile 手机
     * @param type $content 发送内容
     * @param type $dsttime 定时
     * @return boolean
     */
    static function sendSms($mobile = null, $content = null, $dsttime = '', $signText = '【馨途旅游1】') {
//        if (empty($mobile) || empty($content)){
//            return FALSE;
//        }
//
//        $content = is_string($content) ? [$content] : (array)$content;
//        $url = count($content) > 1 ? 'http://www.ztsms.cn:8800/sendManySms.do' : 'http://www.ztsms.cn:8800/sendSms.do';
//        $sms = array( //发送短信的配置
//            'productid' => '48661',
//            'username' => 'jszc',
//            'password' => 'yu126com'
//        );
//        $params = $sms;    //site配置里已经无此配置项
//        //Common::DebugPrint($params);
//        $params['mobile'] = is_array($mobile) ? implode(',', $mobile) : (string)$mobile;
//        $params['content'] = implode("{$signText}※", $content) . $signText;
//        $params['dsttime'] = $dsttime;
//        $params['productid'] = '48661';
//        $params['xh'] = '';
//
//        $result = Request::factory($url)->method(Request::POST)->post($params)->execute()->body();
//        return $result < 0 ? FALSE : (bool) substr($result, 0, 1);
    }
    
    /**
     * 设置用户可以登录的平台 注意此方法与 passport.php 文件中的调用相对应，改动时请一并修改
     * @param int $registsource 1 2 4   //website
     * @param int $isseller 0 1
     */
    static function getPlatformAuth($registsource,$isseller, $cate = ''){
        if ($cate == 'wap') {
            return array('tripb2b','happytoo','mconline');
        }
        $arr = array();
        if(!isset($registsource) || !isset($isseller)){
            return $arr;
        }
        
        switch ($isseller) {
            case 0:
                switch ($registsource){
                    case 1:
                        $arr = array('tripb2b','happytoo','mconline');
                        break;
                    case 2:
                        $arr = array('tripb2b','happytoo');
                        break;
                    case 4:
                        $arr = array('tripb2b','mconline');
                        break;
                    default : break;
                }
                break;
            case 1:
                switch ($registsource){
                    case 1:
                        $arr = array('tripb2b');
                        break;
                    case 2:
                        $arr = array('happytoo');
                        break;
                    case 4:
                        $arr = array('mconline');
                        break;
                    default : break;
                }
                break;
            default:
                break;
        }
        return $arr;
    }
    
    /**
     * 根据传入的9个域名之一，得到平台标识字符串
     */
    static function getPlatformByDomain($sitedomain = array()){
        $platform = ''; // 默认字符串会造成
        $serverhost = $_SERVER['HTTP_HOST'];
        if(empty($sitedomain)){
            $sitedomain = Kohana::$config->load('platform.host');
        }
        foreach ($sitedomain as $value) {
            $key = array_search($serverhost, $value);
            if($key){
                $platform = $key;
                break;
            }
        }
        return $platform;
    }

    public static function getQRcode($url, $file = false, $ps = '4', $level = 'L') {
        Kohana::load("../../../plugins/phpqrcode/phpqrcode.php");
        QRcode::png($url, $file, $level, $ps, 4, true);
    }
    
    // 清除Html代码
    static function clearHtml($details, $length, $more = 1) {
        $str = strip_tags($details);
        $str = preg_replace('/\n/is', '', $str);
        $str = preg_replace('/ |　/is', '', $str);
        $str = preg_replace('/&nbsp;/is', '', $str);

        try {
            $str = trim(str_replace([
                '　',
                '&nbsp;',
                ' ',
                '"',
                '\'',
                '&quot;',
                '&rdquo;',
                '&ldquo;'
                            ], '', $str));
            $str = self::showTitle($str, $length, $more);
        } catch (Exception $ex) {
            $str = "";
        }
        return $str;
    }

    // 截取前N个字符，汉字计两个字符
    static function showTitle($title, $length, $haspoints = true, $start = 0) {
        $title = iconv('utf-8', 'gbk', $title);
        if (strlen($title) > $length) {
            $tmpstr = "";
            $strlen = $start + $length;
            for ($i = 0; $i < $strlen; $i ++) {
                if (ord(substr($title, $i, 1)) > 0xa0) {
                    $tmpstr .= substr($title, $i, 2);
                    $i ++;
                } else
                    $tmpstr .= substr($title, $i, 1);
            }
            $tmpstr .= $haspoints ? ".." : "";
        } else {
            $tmpstr = $title;
        }
        return iconv('gbk', 'utf-8', $tmpstr);
    }
	
	
	  public static function FilterHtml(&$data, $filterfields = array('L_NoItem', 'L_Children', 'L_Shop', 'L_TourContent', 'L_Mode', 'R_Car')) {
        if (empty($data))
            return;
        foreach ($filterfields as $k => $f) {
            if (isset($data [$f])) {
                $data [$f] = Filter::unHtml(Filter::reHtml($data [$f]));
            }
        }

        return $data;
    }
    
     public static function FilterHtmllist(&$datalist, $filterfields = array('L_YesItem', 'L_NoItem', 'L_Children', 'L_Shop', 'L_Reminder', 'L_Mode', 'L_TourContent', 'r_content')) {
        if (empty($datalist))
            return;
        foreach ($filterfields as $k) {
            if (isset($datalist [$k]) && $datalist [$k]) {
//				$datalist [$k] = Filter::unHtml ( Filter::reHtml ( $datalist [$k] ) );
                $datalist [$k] = Filter::unHtml(( $datalist [$k]));
            } else {
                foreach ($datalist as $key => $d) {
                    if (isset($d [$k]) && !empty($d [$k])) {
//						$datalist [$key] [$k] = Filter::unHtml ( Filter::reHtml ( $datalist [$key] [$k] ) );
                        $datalist [$key] [$k] = Filter::unHtml(( $datalist [$key] [$k]));
                    }
                }
            }
        }
        return $datalist;
    }
    
    public static function writelog($content,$fileName) {
        $saveFile = Kohana::$config->load('site.log_dir').'/'.$fileName.'-'.date('Y-m-d').'.log';
        @mkdir(dirname($saveFile), 0777, true);
        $content = "\r\n\r\n".date('Y-m-d H:i:s').$content;
        file_put_contents($saveFile, $content, FILE_APPEND);
    }
    
    /**
     * 检测时间是否结束
     */
    public static function checkTime($date, $type = true) {
        if (preg_match('/^[\d]+$/', $date)) {
            $timestamp = strtotime(date('Y-m-d') . ' ' . date('H:i', $date));
        } else {
            $timestamp = strtotime(date('Y-m-d') . ' ' . date('H:i', strtotime($date)));
        }
        if ($type) {
            return $timestamp > time();
        } else {
            return $timestamp - time();
        }
    }
    
    /**
     * 检测日期 从Filter中复制过来
     * @param type $str
     * @param type $format
     * @return boolean
     */
    public static function isdate($str, $format = "Y-m-d") {
        $format = $format ? $format : "Y-m-d";
        $strArr = explode("-", $str);

        if (empty($strArr)) {
            return false;
        }

        foreach ($strArr as $val) {
            if (strlen($val) < 2) {
                $val = "0" . $val;
            }
            $newArr [] = $val;
        }

        $str = implode("-", $newArr);
        $unixTime = strtotime($str);
        $checkDate = date($format, $unixTime);

        if ($checkDate == $str) {
            return true;
        } else {
            return false;
        }
    }
	/**
     * 检测是否是手机端过来 如果是返回true 否则false
     * @param type $str
     * @param type $format
     * @return boolean
     */
	public static function isFormMobile() {
    	$useragent = isset ( $_SERVER ['HTTP_USER_AGENT'] ) ? $_SERVER ['HTTP_USER_AGENT'] : '';
    	$useragent_commentsblock = preg_match ( '|\(.*?\)|', $useragent, $matches ) > 0 ? $matches [0] : '';
    	function CheckSubstrs($substrs, $text) {
    		foreach ( $substrs as $substr )
    		if (false !== strpos ( $text, $substr )) {
    			return true;
    		}
    		return false;
    	}
    	$mobile_os_list = array (
    			'Google Wireless Transcoder',
    			'Windows CE',
    			'WindowsCE',
    			'Symbian',
    			'Android',
    			'armv6l',
    			'armv5',
    			'Mobile',
    			'CentOS',
    			'mowser',
    			'AvantGo',
    			'Opera Mobi',
    			'J2ME/MIDP',
    			'Smartphone',
    			'Go.Web',
    			'Palm',
    			'iPAQ'
    	);
    	$mobile_token_list = array (
    			'Profile/MIDP',
    			'Configuration/CLDC-',
    			'160×160',
    			'176×220',
    			'240×240',
    			'240×320',
    			'320×240',
    			'UP.Browser',
    			'UP.Link',
    			'SymbianOS',
    			'PalmOS',
    			'PocketPC',
    			'SonyEricsson',
    			'Nokia',
    			'BlackBerry',
    			'Vodafone',
    			'BenQ',
    			'Novarra-Vision',
    			'Iris',
    			'NetFront',
    			'HTC_',
    			'Xda_',
    			'SAMSUNG-SGH',
    			'Wapaka',
    			'DoCoMo',
    			'iPhone',
    			'iPod'
    	);

    	$found_mobile = CheckSubstrs ( $mobile_os_list, $useragent_commentsblock ) || CheckSubstrs ( $mobile_token_list, $useragent );

    	if ($found_mobile) {
    		return true;
    	} else {
    		return false;
    	}
    }
    
    public static function dealWithCasUserInfo(&$userinfo){
        $sitedomain = Kohana::$config->load('platform.host');
        $userinfo['platformauth'] = Common::getPlatformAuth($userinfo['companyinfo']['registsource'], $userinfo['companyinfo']['isseller']);
        //处理站点
        $siteinfo = array();
        foreach ($userinfo['siteinfo'] as $value) {
            $siteinfo[$value['siteId']] = $value;
        }
        $userinfo['siteinfo'] = $siteinfo;

        //处理用户权限
        $userinfo['authorization']['values'] = $userinfo['authorization']['menus'] = array();
        if($userinfo['resources']){
            foreach ($userinfo['resources'] as $v) {
                $userinfo['authorization']['values'] = array_merge($userinfo['authorization']['values'], Arr::pluck($v['privs'], 'code'));
                $userinfo['authorization']['menus'][] = $v['url'];
            }
         }
        //处理用户的域名列表
        $identity = $userinfo['companyinfo']['isseller'] == 1 ? 'seller' : 'buyer';
        if(empty($siteinfo['sitedomain'])){
            foreach($userinfo['platformauth'] as $v) {
                $userinfo['sitedomain'][]=$sitedomain[$identity][$v];
                $userinfo['sitedomain'][]=$sitedomain['index'][$v];
            }
        }
    }
    
    public static function Location($url){
        header("Content-type: text/html; charset=utf-8");
        echo "<script type=\"text/javascript\">location.href='" . $url . "'</script>";
        exit;    
    }
    
    /**
     * 获取用户的可访问的域名标识字符串
     * @param type $isseller 1 / 0
     * @param type $platformauth Common::getPlatformAuth的结果
     * @return string str 示例 110110000
     */
    public static function getExitKeyWords($isseller,$platformauth){
        $str = $isseller ? '1' : '0';
        $key = 0;
        in_array('tripb2b', $platformauth) && $key += 1;
        in_array('happytoo', $platformauth) && $key += 2;
        in_array('mconline', $platformauth) && $key += 4;
        return $key.$str;
    }
    
    public static function getSitedomainByKeyWords($words,$sitedomain){
        $temp = array();
        $plat = (int)substr($words, 0, 1);
        $identity = substr($words, 1) ? 'seller' : 'buyer';
        
        if( ($plat & 1)){
            $temp[] = $sitedomain['index']['tripb2b'];
            $temp[] = $sitedomain[$identity]['tripb2b'];
            $temp[] = $sitedomain['shop']['tripb2b'];
        }
        if( ($plat & 2) ){
            $temp[] = $sitedomain['index']['happytoo'];
            $temp[] = $sitedomain[$identity]['happytoo'];
            $temp[] = $sitedomain['shop']['happytoo'];
        }
        if( ($plat & 4) ){
            $temp[] = $sitedomain['index']['mconline'];
            $temp[] = $sitedomain[$identity]['mconline'];
        }
        return $temp;
    }
    
    public static function getPlatformByDomainWords($domainwords){
        $platform = 'tripb2b';
        if(stristr($domainwords,'happytoo')){
            $platform = 'happytoo';
        }elseif(stristr($domainwords, 'mconline')){
            $platform = 'mconline';
        }
        return $platform;
    }
    
    
    public static function getPlat($style){
        $conf = array(
            1 =>  array('http://www.tripb2b.com', 'cy-logo.png', '馨·驰誉'),
            2 =>  array('http://www.happytoo.cn', 'ht-logo.png' , '馨·欢途'),
            4 =>  array('http://www.mconline.com.cn', 'mc-logo.png', '馨·美程'),
        );

        if(strpos($_SERVER['HTTP_HOST'], 'happytoo') !== false){
            return $conf[2][$style];
        }elseif( strpos($_SERVER['HTTP_HOST'], 'mconline') !== false){
            return $conf[4][$style];
        }else{
            return $conf[1][$style];
        }      
    }
    
    //返回当前环境所有域名,$
    public static function getPlatformHosts($currentEnvironment = 'hosts') {
        $configUrl = 'platform.'.$currentEnvironment;
        switch (Kohana::$environment) {
            case Kohana::DEVELOPMENT://开发环境
                return Kohana::$config->load($configUrl.'.dev');
                break;
            case Kohana::TESTING:
                return Kohana::$config->load($configUrl.'.test');
                break;
            case Kohana::STAGING:
                return Kohana::$config->load($configUrl.'.pre');
                break;
            default://线上环境
                return Kohana::$config->load($configUrl.'.product');
                break;
        }
    }

    // 设置平台环境
    public static function setupEnv(){
        if (Kohana::$environment = getenv("TB_ENV")) {
            return ;
        }
        // Kohana::$environment === Kohana::DEVELOPMENT // 开发
        // Kohana::$environment === Kohana::TESTING // 测试
        // Kohana::$environment === Kohana::STAGING // pre
        // Kohana::$environment === Kohana::PRODUCTION // 生产
        $hostname = getenv('HTTP_HOST');
        switch (substr($hostname, 0, strpos($hostname, '.'))) {
            case 'test':
                Kohana::$environment = Kohana::TESTING;
                break;

            case 'pre':
                Kohana::$environment = Kohana::STAGING;
                break;

            default:
                if (APP_DEBUG === true || strpos($hostname, 'd.etu6.org') !== FLASE) {
                    Kohana::$environment = Kohana::DEVELOPMENT;
                } else {
                    Kohana::$environment = Kohana::PRODUCTION;
                }
        }
    }

    // 设置ERP环境
    public static function setupERPEnv(){
        // Kohana::$environment === Kohana::DEVELOPMENT // 开发
        // Kohana::$environment === Kohana::TESTING // 测试
        // Kohana::$environment === Kohana::PRODUCTION // 生产
        $matches = [];
        $hostname = getenv('HTTP_HOST');
        preg_match('/(.*\.)?(\w+\.\w+\.\w+)$/', $hostname, $matches);
        switch ($matches[2]) {
            case 'erp.tripb2b.com':     //线上ERP
            case 'admin.jintutrip.com': //线上品质游ERP
                Kohana::$environment = Kohana::PRODUCTION;
                break;
            case 'jintutrip.etu6.org':  //测试环境品质游ERP
            case 'erp.we2tu.com':   //测试环境ERP
                Kohana::$environment = Kohana::TESTING;
                break;

            case 'tb.yake.net':
                Kohana::$environment = Kohana::DEVELOPMENT;
                break;

            case 'erp.etu6.org':
                Kohana::$environment = Kohana::STAGING; //ERP的另外一个测试环境，PHP专用
                break;

            default:
                if (APP_DEBUG === true) {
                    Kohana::$environment = Kohana::DEVELOPMENT;
                } else {
                    Kohana::$environment = Kohana::PRODUCTION;
                }
        }
    }
    
    public static function staticUrl () {
        switch(Kohana::$environment) {
            //测试环境
            case Kohana::TESTING:
                return 'http://static.etu6.org/t/common/';
                break;
            //预环境
            case Kohana::STAGING:
                return 'http://static.tripb2b.com/p/common/';
                break;
            //开发环境
            case Kohana::DEVELOPMENT:
                return 'http://'.DOMAIN_STATIC.'/common/';
                break;
            //线上环境
            case Kohana::PRODUCTION:
                return 'http://static.tripb2b.com/o/common/';
                break;
        }
    }

    // 根据平台展示百度统计代码
    public static function loadBaiduStat($platform = null){
        $platform = $platform ?: Common::getPlatformByDomain();
        $stats = Kohana::$config->load("platform.websitevalue");
        $code = $stats[$platform];
        echo <<<EOF
<script>
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement("script");
  hm.src = "//hm.baidu.com/hm.js?{$code}";
  var s = document.getElementsByTagName("script")[0];
  s.parentNode.insertBefore(hm, s);
})();
</script>
EOF;
    }
    
    public static function protect(){
        $aUser = Session::instance()->get('TRIPB2BCOM_USER');
        $param = strtoupper(md5((int)$aUser['memberinfo']['id']));
        return sprintf('http://%s/redirect.html?sign=%s&u=', $_SERVER['HTTP_HOST'], $param);        
    }
    
    public static function ValidRemoteUser(){
        return;
        if(Kohana::$environment !== Kohana::DEVELOPMENT){
            $unid = self::Unid();
            $user = Session::instance()->get('TRIPB2BCOM_USER');
            if($user){
                $isOn = Cache::instance('default')->get($unid);
                if(!$isOn){
                   setcookie('PHPSESSID', NULL,NULL,"/",$_SERVER['SERVER_NAME']); 
                   Session::instance()->delete('TRIPB2BCOM_USER');
                   @session_destroy();
                }
            }
        }        
    }
    
    public static function Unid($user = null){
        $user = $user ?: Session::instance()->get('TRIPB2BCOM_USER');
        if($user['memberinfo']['id']){
            return md5($user['memberinfo']['id'].$_SERVER['HTTP_USER_AGENT']);
        }
        return false;
    }

    public static function UnidState($user = null, $state = null) {
        if (! $redis = RedisDB::getRedis()) {
            return 6379;
        }
        $user = $user ?: Session::instance()->get('TRIPB2BCOM_USER');
        if ($state === null) {
            return $redis->get(self::Unid($user));
        } else {
            if ($state) {
                return $redis->set(self::Unid($user), time(), 86400);
            } else {
                $redis->del(self::Unid($user));
            }
        }
        return 9999;
    }
    
    /**
     * 生成图形验证码
     * @return string
     */
    public static function getValidateCode() {
        $width = 100;
        $height  = 30;
        $num = 4;   //界面上只能显示4个
        $image = imagecreatetruecolor($width,$height);//创建一个宽100，高度30的图片
        $bgcolor=imagecolorallocate($image,mt_rand(157,255), mt_rand(157,255), mt_rand(157,255));//图片背景颜色
        imagefill($image,0,0,$bgcolor);//图片填充背景颜色
        $data = 'abdefghjmnrtyABDEFGHKLMNPRTXYZ2345678';//随机因子
        $len = strlen($data)-1;
        $fontcontent = $validatecode = '';
        for($i = 0; $i < $num; $i++) {
            $fontsize=6;
            $fontcolor=imagecolorallocate($image,rand(0,120),rand(0,120),rand(0,120));
            $fontcontent = $data[rand(0,$len)];
            $validatecode .= $fontcontent;
            
            $x=($i*100/4)+ rand(5,10);
            $y=rand(5,10);
            imagestring($image,$fontsize,$x,$y,$fontcontent,$fontcolor);
        } 

        //随机点，生成干扰点
        for($i=0;$i<250;$i++){
          $pointcolor=imagecolorallocate($image,rand(50,120),rand(50,120),rand(50,120));
          imagesetpixel($image,rand(1,99),rand(1,99),$pointcolor);
        }
        //雪花
        for ($i=0;$i<6;$i++) {
         $color = imagecolorallocate($image,rand(200,255),rand(200,255),rand(200,255));
         imagestring($image,1,rand(0,$width),rand(0,$height),'*',$color);
        }
        //随机线，生成干扰线
        for($i=0;$i<3;$i++){
          $linecolor=imagecolorallocate($image,rand(80,220),rand(80,220),rand(80,220));
          imageline($image,rand(1,99),rand(1,29),rand(1,99),rand(1,29),$linecolor);
        }

        header("Content-type: image/PNG"); 
        imagepng($image);//输出图片 
        imagedestroy($image);//释放图片所占内存
        return $validatecode;
    }
    public static function DebugPrint($variable, $die = TRUE) {
        print_r('<pre>');
        print_r($variable);
        $die && die();
    }

    
    /**
    * 清理字符串中的部分不可见的ASCII码控制字符
    * @param string $string 待处理字符串
    * @return string 处理后的字符串
    */
    public static function clearInvisibleCharacter($string = '') {
        $do_not_searches = array(chr(9), chr(10), chr(13)); //排除 tab, \n, \r 三个字符
        /* 需清理的字符列表 */
        $searches = array();
        for ($i = 0; $i <= 31; $i++){
            if (!in_array(chr($i), $do_not_searches)){
                $searches[] = chr($i);
            }
        }
        $searches[] = chr(127);
        return str_replace($searches, '', $string);
    }

    // 过滤掉emoji表情
    public static function filterEmoji($str = ''){
        if($str){
            $str = preg_replace_callback(
                '/./u',
                function (array $match) {
                    return strlen($match[0]) >= 4 ? '' : $match[0];
                },
                $str);
        }
        return $str;
    }

    /**
     * 远程图片上传
     */
    public static function uploadLongRange($url,$save_dir='',$filename='',$type=0) {
        if(trim($url)==''){
            return array('file_name'=>'','save_path'=>'','error'=>1);
        }
        if(trim($save_dir)==''){
            $save_dir='./';
        }
        if(trim($filename)==''){//保存文件名
            $ext=strrchr($url,'.');
            if($ext!='.gif'&&$ext!='.jpg'){
                return array('file_name'=>'','save_path'=>'','error'=>3);
            }
            $filename=time().$ext;
        }
        if(0!==strrpos($save_dir,'/')){
            $save_dir.='/';
        }
        //创建保存目录
        if(!file_exists($save_dir)&&!mkdir($save_dir,0777,true)){
            return array('file_name'=>'','save_path'=>'','error'=>5);
        }
        //获取远程文件所采用的方法
        if($type){
            $ch=curl_init();
            $timeout=5;
            curl_setopt($ch,CURLOPT_URL,$url);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
            $img=curl_exec($ch);
            curl_close($ch);
        }else{
            ob_start();
            readfile($url);
            $img=ob_get_contents();
            ob_end_clean();
        }
        //$size=strlen($img);
        //文件大小
        $fp2=@fopen($save_dir.$filename,'a');
        fwrite($fp2,$img);
        fclose($fp2);
        unset($img,$url);
        return array('file_name'=>$filename,'save_path'=>$save_dir.$filename,'error'=>0);
    }

    /*
     * 图片转base64
     */
    public static function imgBase($url,$type=0,$timeout=30){
        $msg = ['code'=>2100,'status'=>'error','msg'=>'未知错误！'];
        $imgs= ['image/jpeg'=>'jpeg',
            'image/jpg'=>'jpg',
            'image/gif'=>'gif',
            'image/png'=>'png',
            'text/html'=>'html',
            'text/plain'=>'txt',
            'image/pjpeg'=>'jpg',
            'image/x-png'=>'png',
            'image/x-icon'=>'ico'
        ];
        if(!stristr($url,'http')){
            $msg['code']= 2101;
            $msg['msg'] = 'url地址不正确!';
            return $msg;
        }
        $dir= pathinfo($url);
        //var_dump($dir);
        $host = $dir['dirname'];
        $refer= $host.'/';
        $ch = curl_init($url);
        curl_setopt ($ch, CURLOPT_REFERER, $refer); //伪造来源地址
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//返回变量内容还是直接输出字符串,0输出,1返回内容
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);//在启用CURLOPT_RETURNTRANSFER的时候，返回原生的（Raw）输出
        curl_setopt($ch, CURLOPT_HEADER, 0); //是否输出HEADER头信息 0否1是
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout); //超时时间
        $data = curl_exec($ch);
        //$httpCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);
        //$httpContentType = curl_getinfo($ch,CURLINFO_CONTENT_TYPE);
        $info = curl_getinfo($ch);
        curl_close($ch);
        $httpCode = intval($info['http_code']);
        $httpContentType = $info['content_type'];
        $httpSizeDownload= intval($info['size_download']);

        if($httpCode!='200'){
            $msg['code']= 2102;
            $msg['msg'] = 'url返回内容不正确！';
            return $msg;
        }
        if($type>0 && !isset($imgs[$httpContentType])){
            $msg['code']= 2103;
            $msg['msg'] = 'url资源类型未知！';
            return $msg;
        }
        if($httpSizeDownload<1){
            $msg['code']= 2104;
            $msg['msg'] = '内容大小不正确！';
            return $msg;
        }
        $msg['code']  = 200;
        $msg['status']='success';
        $msg['msg']   = '资源获取成功';
        if($type==0 or $httpContentType=='text/html') $msg['data'] = $data;
        $base_64 = base64_encode($data);
        if($type==1) $msg['data'] = $base_64;
        elseif($type==2) $msg['data'] = "data:{$httpContentType};base64,{$base_64}";
        elseif($type==3) $msg['data'] = "<img src='data:{$httpContentType};base64,{$base_64}' />";
        else $msg['msg'] = '未知返回需求！';
        unset($info,$data,$base_64);
        return $msg;
    }
}
