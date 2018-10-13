<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Debug</title>
    <script src="//libs.baidu.com/jquery/1.9.0/jquery.js"></script>
    <style>
        .wide{width:320px;}
        .short{width:160px;}
        .mini{width:80px;}
        .request{ top:10px; left:10px; height:120px; width:98%}
        textarea{display: block; width: 98%; height:100px}
        #output{ margin-top:15px; font-size:12px; border:1px solid #ddd; padding: 10px; word-break: break-all; width:98%; overflow-x: scroll}
    </style>
</head>
<body>
<div class="request">
    <form method="POST">
        <div>
            <input type="text" name="url" class="wide" value="<?php echo $data['url'];?>"/>
            <input type="text" name="method" class="mini" value="<?php echo $data['method'];?>"/>
            <input type="button" value="Request" id="button-request"/>
        </div>
        <div>
            <textarea rows="2" name="data"><?php if (is_array($data['data'])): echo rawurldecode(http_build_query($data['data'], null, '&amp;')); else: echo rawurldecode($data['data']); endif; ?></textarea>
        </div>
    </form>
</div>
<pre id="output"></pre>
<script>
    $(function(){
        $("#button-request").click(function(){
            $("#output").empty();
            $.post('/log.dorequest.html', $("form").serialize(), function(r){
                $("#output").html(r);
            });
        });
    });
</script>
</body>
</html>