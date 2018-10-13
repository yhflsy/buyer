<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<title><?php echo Common::getPlat(2); ?>提醒您</title>
</head>
<body>
<script type="text/javascript" src="/skin/web.v1/js/jquery-1.8.2.min.js"></script>
<script type="text/javascript" src="/skin/web.v1/js/info/info.js"></script>
<script>
$(function(){
	$.info({
        title:'<?php echo Common::getPlat(2); ?>提醒您：',
		icon: <?php echo $status; ?>,//0：对号，1：感叹号,2:叉号
		msg:'<?php echo $message; ?>',
		href:'<?php echo $location; ?>',
        classs:'<?php echo isset($class) ? "$class" : "zpbox_yes" ?>'
	});

});

</script>
</body>
</html>
