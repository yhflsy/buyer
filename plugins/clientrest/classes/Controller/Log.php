<?php

(APP_DEBUG === true) OR exit('<h1>Forbidden</h1>');
/**
 *
 */
class Controller_Log extends Controller_Base {

    public function before(){
        if (Kohana::$environment == Kohana::DEVELOPMENT) {
            parent::before();
        }
    }

    public function action_index(){
        
    }

    public function action_json() {
        $outputjson = Filter::int('outputjson', 0);
        if(($content = $this->_getCacheContent())){
            if ($outputjson) {
                header("Content-type: application/json; charset=utf-8");
                echo $content;
            } else {
                header("Content-type: text/html; charset=utf-8");
                $this->_outputHelpHtml();
                $data = json_decode($content, TRUE);
                $str = preg_replace("~=>\s*array~isx", '=> array', var_export($data, true));
                if (strpos($str, '#kohana_error') !== false) {
                    echo preg_replace('~^.*(?=<style)~s', '', $str);
                } else {
                    $str = highlight_string('<?php' . "\r\n" . $str, true);
                    echo str_replace('\\\'', '&apos;', $str);
                }
            }
        } else {
            echo '无内容';
        }
        exit;
    }
    
    private function _getCacheContent() {
        $file = Filter::str('file');
        if (is_file(DOCROOT . $file)){
            return file_get_contents(DOCROOT . $file);
        } else {
            $redis = Debug::getRedis();
            if(!$redis) {
                return '';
            }
            if(strpos($file, '/') !== FALSE) {
                $file = sprintf("apilog:%s:%s", Kohana::$environment, substr($file, -13, -5));
            }
            return $redis->get($file);
        }
    }

    public function action_latest() {
        $this->_isExistRedis();
        $logfile = DOCROOT . '/log/api/latest.json';
        if (is_file($logfile)){
            $logs = json_decode(file_get_contents($logfile), true);
        } else {
            $logs = array();
        }

        $logs = array_reverse((array) $logs);
        foreach((array) $logs as $log) {
            printf("[%s] <a href='/log.json.html?file=%s' target='_blank'>%s</a> [%s][file]<br>",
                    is_numeric($log['time'])?date("Y-m-d H:i:s", $log['time']):$log['time'],
                    $log['file'],
                    $log['url'],
                    $log['method']);
        }
        exit;
    }
    
    public function _isExistRedis() {
        $redis = Debug::getRedis();
        if(!$redis) {
            return ;
        }
        $key = sprintf("apiloglatest:%s", Kohana::$environment);
        $list = $redis->lRange($key, 0, -1);
        foreach($list as $log) {
            $log = json_decode($log, TRUE);
            printf("[%s] <a href='/log.json.html?file=%s' target='_blank'>%s</a> [%s]<br>",
                    is_numeric($log['time'])?date("Y-m-d H:i:s", $log['time']):$log['time'],
                    $log['file'],
                    $log['url'],
                    $log['method']);
        }
    }

    public function action_replay(){
        $data = (array)json_decode($this->_getCacheContent(), TRUE);
        if(!$data) {
            echo '无数据内容';exit;
        }
        echo View::factory('profiler/replay')->bind('data', $data);
        exit;
    }

    public function action_doc(){
        if (Kohana::$environment !== Kohana::DEVELOPMENT) {
            exit('dev only');
        }

        $key = Filter::str('file');
        $content = $this->_getCacheContent();
        if ($content){
            $data = json_decode($content, TRUE);
            $this->view['data'] = $data;
            $project = [];

            // url
            $urlInfo = parse_url($data['url']);
            if (strpos($urlInfo['host'], 'order')) {
                $section = 'order';
                $project['dir'] = 'order.service';
            } else if (strpos($urlInfo['host'], 'line')) {
                $section = 'line';
                $project['dir'] = 'line.service';
            } else {
                $project['dir'] = 'service';
                $section = 'service';
            }

            if ($data['data']['type']) {
                $route = trim($urlInfo['path'], '/') . '/' . $data['data']['type'];
            } else {
                $route = trim($urlInfo['path'], '/');
            }
            $route = substr($route, 0, 3) === 'api' ? '/' . $route : '/api/'.$route;

            // class
            $routeArr = explode('/', trim($route, '/'));
            $project['class_name'] = 'Controller_' . ucfirst($routeArr[1]) . '_' . ucfirst($routeArr[2]);

            $method = $data['method'];
            switch ($method){
                case 'POST':
                    $description = '新增';
                    $project['method_name'] = 'action_create';
                    break;
                case 'PUT':
                    $description = '更新';
                    $project['method_name'] = 'action_update';
                    break;
                case 'DELETE':
                    $description = '删除';
                    $project['method_name'] = 'action_delete';
                    break;
                default:
                    $description = '查询';
                    $project['method_name'] = 'action_index';
            }

            if ($data['data']['type']) {
                // 解析出method
                $project['type_method_name'] = $this->_getMethod($project['dir'], $project['class_name'], $project['method_name'], $data['data']['type']);
            }

            // params
            $params = [];
            $bodyArr = [];
            foreach($data['data'] as $param => $value) {
                if (is_array($value)) {
                    $bodyArr[$param] = $value;
                    continue;
                }
                // ignore type
                if (in_array($param, ['type'])) {
                    continue;
                }
                $tmp = [
                    'nullable' => true,
                    'type' => 'String',
                    'description' => '',
                ];
                if (in_array($param, ['id', 'lineid', 'dateid', 'orderid', 'memberid', 'companyid'])) {
                    $tmp['nullable'] = false;
                    $tmp['type'] = 'Int';
                } elseif (in_array($param, ['keywords', 'kw'])) {
                    $tmp['type'] = 'String';
                    $tmp['description'] = '关键字';
                } elseif(in_array($param, ['state', 'days', 'website', 'grade', 'linecategory'])) {
                    $tmp['type'] = 'Int';
                }

                if (substr($param, 0, 2) == 'is'){
                    $tmp['type'] = 'Int';
                }

                if ( (substr($param, 0, -2) == 'id' && strpos($value, ',')>0)
                    || substr($param, 0, -3) == 'ids'
                ) {
                    $tmp['type'] = 'Int|String';
                    $tmp['description'] = '多个id用,分隔';
                }
                if (substr($param, 0, -4) == "time"
                    || strpos($param, 'timebegin')!==false
                    || strpos($param, 'timeend')!==false
                ) {
                    $tmp['type'] = 'DateTime';
                    $tmp['description'] = '';
                }
                if (substr($param, 0, -5) == "price"
                    || strpos($param, 'pricebegin')!==false
                    || strpos($param, 'priceend')!==false
                ) {
                    $tmp['type'] = 'Int';
                    $tmp['description'] = '';
                }

                if ($tmp['type'] == 'String') {
                    switch (gettype($value)) {
                        case "integer":
                            $tmp['type'] = "Int";
                            break;
                    }
                }

                $params[$param] = $tmp;
            }

            $body = rawurldecode(http_build_query($bodyArr));

            $result = $data['response']['result'];
            if (is_array($result) && is_array($result['list'])) {
                $result['list'] = array_slice($result['list'], 0, 1);
            }
            // sample output
            $sampleArr = [
                'code' => 200,
                'message' => '操作成功！',
                'result' => $result,
                'serkey' => $data['response']['serkey'],
            ];

            $sample = (json_encode($sampleArr, JSON_UNESCAPED_UNICODE));

            $api = compact('section', 'description', 'method', 'route', 'params', 'body', 'sample');
            $path = dirname(DOCROOT) . sprintf("/%s/app/classes/%s.php", $project['dir'], str_replace('_', '/', $project['class_name']));
            echo View::factory('profiler/doc')
                ->bind('project', $project)
                ->bind('type', $data['data']['type'])
                ->bind('path', $path)
                ->bind('data', $data)
                ->bind('key', $key)
                ->bind('api', $api);
        } else {
            exit('no content.');
        }
        exit;
    }

    private $_tokens;
    private function _getMethod($dir, $class_name, $method_name, $type){
        $file = dirname(DOCROOT) . sprintf("/%s/app/classes/%s.php", $dir, str_replace('_', '/', $class_name));
        if (! is_file($file)){
            return '';
        }
        $source = file_get_contents($file);
        $this->_tokens = token_get_all($source);
        $ignoreTokens = array(
            'T_COMMENT',
            'T_DOC_COMMENT',
            'T_WHITESPACE',
        );

        // 缩小范围
        $method_min_ptr = 0;
        $method_max_ptr = count($this->_tokens);
        foreach ($this->_tokens as $k => $token) {
            if(is_array($token)){
                if (token_name($token[0]) === 'T_FUNCTION') {
                    $ptr = $k;
                    while($ptr < $k+10) {
                        $ptr ++;
                        if (is_array($this->_tokens[$ptr])){
                            $_tn = token_name($this->_tokens[$ptr][0]);
                            if (! in_array($_tn, $ignoreTokens)) {
                                if ($_tn !== 'T_STRING') continue;
                                if ($method_min_ptr) {
                                    $method_max_ptr = min($ptr, $method_max_ptr);
                                    break 2;
                                } else {
                                    if ($this->_tokens[$ptr][1] === $method_name) {
                                        $method_min_ptr = $ptr;
                                    }
                                }
                            }
                        } else {
                            if ($this->_tokens[$ptr] === '('){
                                continue 2;
                            }
                        }
                    }
                }
            } else {
            }
        }
//        var_dump($this->_tokens[$method_min_ptr], $this->_tokens[$method_max_ptr]);
        // 搜索case分支
        $search = [
            ['type' => 'T_CASE'],
            ['type' => 'T_BREAK'],
        ];
        $tokens = $this->findTokens($search, $method_min_ptr, $method_max_ptr);
        if (empty($tokens)) return '';

        $pos_max = end($tokens)['pos'];
        reset($tokens);
        $pos_min = current($tokens)['pos'];
        foreach($tokens as $token) {
            $pos_min = max($pos_min, $token['pos']);
            if ($token['type'] != 'T_BREAK'){
                $pos = $this->findNext('T_CONSTANT_ENCAPSED_STRING', $pos_min, $pos_max,
                    [sprintf('"%s"', $type), sprintf("'%s'", $type)],
                    ['T_BREAK']);

                if (! $pos) continue;
                $pos_min = $pos;
                // find $this
                $this_pos = $this->findNext('T_VARIABLE', $pos_min, $pos_max, '$this', ['T_BREAK']);
                if (! $this_pos) continue;
                $pos_min = $this_pos;
                $call_pos = $this->findNext('T_STRING', $pos_min, $pos_min+5);
                if (! $call_pos) continue;
                return $this->_tokens[$call_pos][1];
            }
        }
        return '';
    }

    public function nextTokens($start, $len) {
        for($i = $start; $i < $start + $len; $i++){
            if (is_array($this->_tokens[$i])) {
                if (token_name($this->_tokens[$i][0]) == 'T_WHITESPACE') continue;
                printf("\n%s\n", token_name($this->_tokens[$i][0]));
                printf("%s\n", ($this->_tokens[$i][1]));
            } else {
                printf("\n%s\n", $this->_tokens[$i]);
            }
        }
    }

    public function findNext($type, $start=0, $end=null, $value=null, $breaks=array(), &$breakPos = null) {
        $start = min($start, count($this->_tokens));
        $end = $end ? min($end, count($this->_tokens)) : count($this->_tokens);
        for ($i = $start; $i < $end; $i++) {
            $tt = $this->_tokens[$i];
            if (is_array($tt)) {
                if (token_name($tt[0]) == $type) {
                    if ($value === null) {
                        return $i;
                    } else {
                        if (is_array($value) && in_array($tt[1], $value)) {
                            return $i;
                        } elseif ($value == $tt[1]) {
                            return $i;
                        }
                    }
                }
                if (! empty($breaks) && in_array(token_name($tt[0]), $breaks)) {
                    $breakPos = $i;
                    return false;
                }
            }
        }
        return false;
    }

    public function findTokens($tokens, $start=0, $end=null) {
        $start = min($start, count($this->_tokens));
        $end = $end ? min($end, count($this->_tokens)) : count($this->_tokens);
        $result = [];
        foreach ($tokens as $t) {
            for ($i = $start; $i < $end; $i++) {
                $tt = $this->_tokens[$i];
                if (is_array($tt)) {
                    if (token_name($tt[0]) == $t['type']) {
                        $result[] = [
                            'type' => $t['type'],
                            'value' => $tt[1],
                            'line' => $tt[2],
                            'pos' => $i,
                        ];
                    }
                } else {

                }
            }
        }
        usort($result, [$this, '_sortByLine']);
        return $result;
    }

    private function _sortByLine($a, $b){
        return $a['line'] - $b['line'];
    }

    public function action_ajaxDoc(){
        $result  = '/**' . PHP_EOL;
        $result .= sprintf('     * @ApiDescription(section="%s", description="%s")', $_POST['section'], $this->_clearTrim($_POST['description'])) . PHP_EOL;
        $result .= sprintf('     * @ApiMethod(type="%s")', $_POST['method']) . PHP_EOL;
        $result .= sprintf('     * @ApiRoute(name="%s")', $_POST['route']) . PHP_EOL;
        if ($_POST['params']) {
            foreach ($_POST['params'] as $k => $v) {
                $result .= sprintf('     * @ApiParams(name="%s", type="%s", nullable=%s, description="%s")', $v['name'], $v['type'], $v['nullable']?'false':'true', $this->_clearTrim($v['description'])) . PHP_EOL;
            }
        }
        $result .= sprintf('     * @ApiReturn(type="object", sample="%s")', $this->_clearTrim($_POST['sample'], '"')) . PHP_EOL;
        if ($_POST['body']) {
            $result .= sprintf('     * @ApiBody(sample="%s")', $this->_clearTrim($_POST['body'])) . PHP_EOL;
        }
        $result .= '     */';
        echo json_encode(['result' => $result]);
        exit;
    }
    
    private function _clearTrim($string = '') {
        //$str = str_replace(array('(', ')', ''), '', $string);
        return addcslashes($string, '"()');
    }
    
    public function action_checkFile() {
        header("content-type:text/html;charset=utf8");
        if(!file_exists($_GET['path'])) {
            echo $_GET['path'];
            echo '该文件不存在';exit;
        }
        $fileContent = highlight_file($_GET['path']);
        echo '<pre>';
        print_r($fileContent);exit;
    }

    public function action_ajaxDocSave(){
        $dir = $_POST['dir'];
        $class_name = $_POST['calssname'];
        $method = $_POST['typemethodname'];
        $new_doc = $_POST['doc'];

        if (! class_exists($class_name, false)) {
            $file = dirname(DOCROOT) . sprintf("/%s/app/classes/%s.php", $dir, str_replace('_', '/', $class_name));
            if (! file_exists($file)){
                echo json_encode(['error' => "file missing : $file"]); exit;
            }
            require $file;
            $source = file_get_contents($file);
        } else {
            echo json_encode(['error' => "class already loaded."]); exit;
        }

        $r = new ReflectionClass($class_name);
        if (! $r->hasMethod($method)) {
            echo json_encode(['error' => "method missing : $class_name:$method"]); exit;
        }

        $rMethod = $r->getMethod($method);
        $oldDoc = $rMethod->getDocComment();
        if (preg_match("~(\\s* (?:[a-z]+\\s+)* function \\s+ $method \\s* \\( )~ix", $source, $match)) {
            $define_line = $match[1];
        } else {
            echo json_encode(['error' => "match method definition failed"]); exit;
        }

        if (strlen($oldDoc)) {
            $source = str_replace($oldDoc, '', $source);
            $source = str_replace($define_line ,   trim($new_doc) . "\n    " . ltrim($define_line), $source);
        } else {
            $source = str_replace($define_line , "\n\n    " . trim($new_doc) . "\n    " . ltrim($define_line), $source);
        }

        if (is_writable($file) && file_put_contents($file, $source) !== false) {
            echo json_encode(['file' => $file]); exit;
        } else {
            echo json_encode(['error' => "failed to file:" . $file]); exit;
        }
    }

    public function action_dorequest(){
        $data = trim($_POST['data']);
        if (! empty($data)) {
            parse_str($data, $params);
        }
        if ($_COOKIE['XDEBUG_SESSION']){
            $params['XDEBUG_SESSION_START'] = $_COOKIE['XDEBUG_SESSION'];
        }

        $result = $this->request(Filter::str('url'), $_REQUEST['method'], $params, null)->get();
        print_r($result);
        exit;
    }

    private function _outputHelpHtml(){
        ?>
        <style>
            code{margin-top:20px; }
            .bar{position:fixed; text-align: center; top:5px; left:0; width:98%;}
        </style>
        <div class="bar">
            <a href="/log.replay.html?file=<?php echo Filter::str('file');?>">调试</a>
            <?php if(Kohana::$environment === Kohana::DEVELOPMENT): ?>
            <a href="/log.doc.html?file=<?php echo Filter::str('file');?>">生成文档</a>
            <?php endif; ?>
        </div>
<?php
    }

    public function action_redis() {
        header("Content-type: text/html; charset=utf-8");
        $key = $_REQUEST['key'];

        if (! $key) {
            exit ('empty key.');
        }

        $c = new RedisCache();
        $c or exit('redis gone.');

        if ($_REQUEST['clear']) {
            $c->delete($key);
            exit('deleted.');
        }

        $data = $c->get($key);
        $data or exit('no data.');

        if ($data['cached']) {
            $data['cached@'] = date("Y-m-d H:i:s", $data['cached']);
        }
        $str = preg_replace("~=>\s*array~isx", '=> array', var_export($data, true));
        if (strpos($str, '#kohana_error') !== false) {
            echo preg_replace('~^.*(?=<style)~s', '', $str);
        } else {
            $str = highlight_string('<?php' . "\r\n" . $str, true);
            echo str_replace('\\\'', '&apos;', $str);
        }

        echo <<<EOF
<div><br>[<a href="{$_SERVER['REQUEST_URI']}&clear=1" style="color:red">清除</a>]</div>
EOF;

        exit;
    }
}
