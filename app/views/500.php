<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>500</title>
<style>
*{ padding:0; margin:0; font-family:"Microsoft Yahei";}
html{
	height:100%;
	background-image: radial-gradient(#6d9de5,#2f6cc8);
	background:#2f6cc8 \9;
	background-color:#2f6cc8\9\0;
}
.content{ width:980px; margin:0 auto;}
.header{ height:80px; padding:20px 30px;}
.error-cont{ padding:80px 0px; text-align:center;}
.error-prompt{ margin-top:30px;}
.error-prompt p{color: #fff; line-height:28px;}
.error-prompt .tprompt{ font-size:20px; margin-bottom:15px;}
.error-prompt .bprompt{ font-size:16px;}
.link{ clear:both; overflow:hidden; width:244px; margin:0px auto; margin-top:30px; }
.link a{ text-decoration:none; display:block; background-color:#f6ab00; float:left; height:36px; line-height:36px; text-align:center; width:106px; color:#fff; font-size:16px; box-shadow:3px #a87500; border-radius:4px;}
.link a:hover{ background-color:#f80;}
.link .homepage{ margin-right:26px;}
</style>
</head>
<body>
<div>
	<div class="content">
    	<div class="header"><img src="/skin/web.happytoo.v3/images/<?php echo Common::getPlat(1); ?>" /></div>
        <div class="error-cont">
    		<div class="errorimg"><img src="/skin/web.happytoo.v3/images/500.png" /></div>
            <div class="error-prompt">
            	<p class="tprompt">很抱歉，您访问的页面不在地球上...</p>
                <p class="bprompt">正在自动跳转到上一页，如没有跳转请点击此链接</p>
            </div>
            <div class="link">
            	<a class="homepage" href="<?php echo Common::getPlat(0); ?>">返回首页</a>
                <a href="javascript:history.go(-1);">返回上一页</a>
            </div>
    	</div>
    </div>
<div style="display:none;"><!--{$errorinfo}--></div>
</div>
</body>
</html>
