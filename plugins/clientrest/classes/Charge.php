<?php
class Charge {
	//充值号码
	public	$mobile;
	//充值面额
	public	$amount;
	//curl
	public	$handle;
	//config
	public $config;
	//result
	public	$result;
	//company
	public	$company;
	//username
	public	$username;
	//orderid
	public	$orderid;
	//datetime
	public	$datetime;
	//充值接口
	const API = '007KA_KM';

	public	function __construct($company, $username, $mobile, $amount) {
		$this->datetime = date('YmdHis');
		$this->config = kohana::$config->load('common.charge');
		$this->handle = curl_init($this->config['url']);
		curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->handle, CURLOPT_POST, 1);
		$this->company = $company;
		$this->username = $username;
		$this->mobile =  trim($mobile);
		$this->amount = $amount*100;
		$this->orderid = 'HT'.$this->datetime.rand(100,999);
	}
	//charge
	public	function execute() {
		$param ="{$this->config['id']}|{$this->config['account']}|{$this->orderid}||{$this->amount}|120|0|xml|9|".self::API."|1.0.1.2|||{$this->mobile}|{$this->datetime}|||";
		try {
			curl_setopt($this->handle, CURLOPT_POSTFIELDS, array('Orderinfo'=>$param,'Sign'=>strtoupper(md5($param."|{$this->config['key']}"))));
			curl_setopt($this->handle, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($this->handle, CURLOPT_SSL_VERIFYHOST, FALSE);
			$this->result = $this->xmlToArray(curl_exec($this->handle));
			curl_close($this->handle);
			//$path = 'cache'.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR.'charge'.DIRECTORY_SEPARATOR.date('Y').DIRECTORY_SEPARATOR.date('m');
			$path = Kohana::$config->load('site.log_dir').'/change-'.date('Y-m-d').'.log';
			is_dir($path) OR mkdir($path, 0777, true);
			$context = $this->company.'的用户'.$this->username.'在'.date('Y-m-d H:i:s').'给手机'.$this->mobile.'充值'.($this->amount/100).'元'."\t".$this->orderid."\t".$this->traninfo($this->result['TranStat']);
			file_put_contents($path.DIRECTORY_SEPARATOR.date('d').'.txt', date('H:i:s')."\t".$context."\r\n", FILE_APPEND);
			return ($this->result['TranStat'] == 1 || $this->result['TranStat'] == 3);
		} catch (Exception $e) {
			return FALSE;
		}
	}
	//xmltoarray
	private	function xmlToArray($xml) {
		$result = array();
		$simpleXml = new simpleXmlElement($xml);
		foreach ($simpleXml->children() as $key => $node) {
			$result[$key] = (String)$node;
		}
		return $result;
	}
	//traninfo
	private	function traninfo($index) {
		$TranInfo = array(
			1 => '成功',
			2 => '重复订单，与原交易不一致',
			3 => '单号重复，交易已经接受',
			4 => '交易正在处理中',
			5 => '错误的交易指令',
			6 => '接口版本错',
			7 => '代理商校验错',
			8 => '不存在的代理商',
			9 => '其他错误',
			10 => '未定义(保留)',
			13 => '面值不正确',
			14 => '交易已经过期',
			17 => '超过约定交易限额',
			18 => '交易结果不能确定',
			20 => '校验失败',
			21 => '代理商已经暂停交易',
			22 => '交易品种没有定义',
			23 => '暂不支持指定号码充值',
			24 => '不能为该用户充值',
			25 => '指定充值号码与指定类别不一致',
			26 => '该代理商未开通该品种',
			28 => '成功金额小于申报金',
			29 => '成功金额大于申报金额',
			30 => '充值号码错误',
			31 => '交易信息不存在',
			32 => '代理商错误率太高，暂停',
			33 => '代理商余额不足',
			34 => '扣代理商款项失败',
			36 => '充值金额与交易金额不符',
			50 => '退款中',
			51 => '退款成功',
		);
		return isset($TranInfo[$index]) ? $TranInfo[$index] : '未定义异常';
	}
}
?>