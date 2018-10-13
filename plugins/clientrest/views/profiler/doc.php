<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Doc</title>
    <link href="http://libs.baidu.com/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet">
    <script src="http://libs.baidu.com/jquery/1.10.2/jquery.min.js"></script>
    <script src="http://libs.baidu.com/bootstrap/3.0.3/js/bootstrap.min.js"></script>
</head>
<body>
<form action="log.ajaxDoc.html" method="post">
<div class="container">
    <h2>生成当前调用服务的接口文档注释 <a href="javascript:$('.faq').toggle();void(0);" class="btn btn-info">使用说明</a></h2>
    <div class="faq">
        <hr>
        <ol>
            <li>在本次服务调用日志倒推出来的接口参数等信息的基础上，录入并完善接口文档，然后点击“生成注释”按钮来生成接口文档注释（注：录入过程中避免使用特殊字符）；</li>
            <li>生成好注释以后，可以点击“写入文件”按钮将注释内容写入到php源码文件的对应的function的上方；</li>
            <li>部分服务接口如：<code>action_index</code> 按type参数拆成了多个，每个type对应一个方法调用，程序会试图将注释写入到实际调用的方法里；</li>
            <li>对于按type拆分的接口，程序会尝试推测出对应的实际调用的方法，如果推测不出来，需要手动指定，php代码里必须存在这个function。</li>
            <li>按type拆分的接口需要统一规范一下：使用<code>switch ... case</code>语句，针对每个type单独写一个私有方法，<code>default</code> 返回“无效类型”的错误，
                <a href="http://git.we2tu.com/php/service/blob/eae04c6acde5cdeb12d0f82c5a8e1c592eacd84a/app/classes/Controller/Line/File.php#L29"
                   target="_blank">示例代码</a>；
            </li>
            <li>“写入文件”操作的是本地文件，完成后记得提交到仓库；</li>
            <li>“写入文件”可能有未知的BUG，提交到仓库的时候请确认修改历史；</li>
            <li>“写入文件”操作会覆盖原有的注释；</li>
            <li>如果服务写在Controller的根目录，可能需要重构；</li>
            <li>如果参数是数组，会自动放入body；</li>
        </ol>
    </div>
    <hr>
    <div class="form-group">
        调用服务：[<?=$data['method']?>] <a href="/log.json.html?file=<?=$key?>"><?=$data['url']?></a>
    </div>
    <div class="form-group">
        分组名称（如调用的是订单服务，可以填 order，默认为 service）
    </div>
    <div class="form-group">
        <input type="text" class="form-control input-sm" value="<?=$api['section']?>" name="section">
    </div>

    <div class="form-group">
        接口描述
    </div>
    <div class="form-group">
        <input type="text" class="form-control input-sm" value="<?=$api['description']?>" name="description">
    </div>

    <div class="form-group">
        参数
    </div>
    <div class="form-group">
        <table class="table">
            <thead>
            <tr>
                <td>参数名称</td>
                <td>参数类型</td>
                <td>是否必填</td>
                <td>参数描述</td>
                <td></td>
            </tr>
            </thead>
            <?php $count=0;foreach($api['params'] as $k => $v) : ?>
            <tr>
                <td><input class="form-control" type="text" value="<?php echo $k;?>" name="params[<?php echo $count;?>][name]" readonly/></td>
                <td><input class="form-control" type="text" value="<?php echo $v['type'];?>" name="params[<?php echo $count;?>][type]"/></td>
                <td><input class="form-control" type='checkbox' value="1" <?php if(!$v['nullable']) echo 'checked';?> name='params[<?php echo $count;?>][nullable]'/></td>
                <td><input class="form-control" type="text" value="<?php echo $v['description'];?>" name="params[<?php echo $count;?>][description]" size='80'/></td>
                <td><input type="button" class="delTr" value="删除"/></td>
            </tr>
            <?php $count++; endforeach; ?>
            <tr class="add">
                <td colspan="5"><input type="button" class="addTr" value="添加"></td>
            </tr>
        </table>
    </div>
    <div class="form-group">
        body
    </div>
    <div class="form-group">
        <textarea cols='180' rows='3' class='form-control' name='body'><?php echo $api['body']?></textarea>
    </div>

    <button type="submit" class="btn btn-success" rel="0">生成注释</button>

    <hr>
    <div class="form-group">
        生成的注释：
    </div>
    <div class="form-group">
        <textarea class='form-control' rows="10" id="output"></textarea>
    </div>
    <div class="hide">
        <div class="form-group">
            路由
        </div>
        <div class="form-group">
            <input type="text" class="form-control input-sm" value="<?=$api['route']?>" name="route" readonly>
        </div>

        <div class="form-group">
            方法
        </div>
        <div class="form-group">
            <input type="text" class="form-control input-sm" value="<?=$api['method']?>" name="method" readonly>
        </div>

        <div class="form-group">
            返回结果
        </div>
        <div class="form-group">
            <textarea cols='180' rows='3' class='form-control' name='sample' readonly><?php echo $api['sample']?></textarea>
        </div>
    </div>
</div>
</form>
  <div class="container">
    <div class="form-group">
      项目：
    </div>
    <div class="form-group">
      <input type="text" class="form-control input-sm postvalue" value="<?= $project['dir'] ?>" name="dir" readonly="">
    </div>
    <div class="form-group">
      类名：
    </div>
    <div class="form-group">
      <input type="text" class="form-control input-sm postvalue" value="<?= $project['class_name'] ?>" name="calssname" readonly="">
    </div>
    <div class="form-group">
      方法名：
    </div>
    <div class="form-group">
      <input type="text" class="form-control input-sm postvalue" value="<?= $project['method_name'] ?>" name="methodname" readonly="">
    </div>
    <?php if($type) :?>
    <div class="form-group">
      <code>type='<?=$type?>'</code> 对应调用的方法名：
    </div>
    <div class="form-group">
      <input type="text" class="form-control input-sm postvalue" value="<?= $project['type_method_name'] ?>" name="typemethodname">
    </div>
    <?php endif;?>
    <div class="form-group">
    </div>
    <div class="form-group">
        <?php if (is_file($path)): ?>
        <button class="auto_sub btn btn-danger">写入文件</button>
        <?php else : ?>
        不能将注释自动写入PHP文件，因为找不到对应的源文件：
        <?php endif; ?>
        <a href="log.checkfile.html?path=<?=$path?>" target="_blank"><?=$path?></a>
    </div>

      <hr>
      <br><br>

  </div>

<script>
    $(function(){
        $('.faq').toggle();
        $("form").submit(function (e) {
            $.post('/log.ajaxDoc.html', $(this).serialize(), function (r) {
                $("#output").val(r.result);
            }, 'json');
            e.preventDefault();
            return false;
        });

        $('.auto_sub').click(function () {
            var data = {};
            var check = 1;
            $(".postvalue").each(function () {
                var k = $(this).attr("name");
                var v = $(this).val();
                if ($.trim(v) == "") {
                    alert(k + '不能为空');
                    check = 0;
                }
                data[k] = v;
            });
            if (check) {
                data['doc'] = $.trim($('#output').val());
                if (! /^\/\*\*((.*)\n\s*)+\*+\/$/.test(data['doc'])){
                    alert("不是文档注释，骗鬼呢！");
                    return false;
                }
                $.post('/log.ajaxDocSave.html', data, function (r) {
                    if (r.error)alert(r.error);
                    if (r.file) {
                        alert('已写入文件' + r.file);
                    }
                }, 'json');
            }
        });
        $(document).on("click", '.delTr', function(){
            $(this).closest('tr').remove();
        });
        $('.addTr').click(function(){
            var a = $(".table tr").length-2;
            $(".add").before(
                "<tr>" +
                    "<td><input class='form-control' type='text' value='' name='params["+a+"][name]'/></td>"+
                    "<td><input class='form-control' type='text' value='' name='params["+a+"][type]'/></td>"+
                    "<td><input class='form-control' type='checkbox' value='1'  name='params["+a+"][nullable]'/></td>"+
                    "<td><input class='form-control' type='text' value='' name='params["+a+"][description]' size='80'/></td>"+
                    "<td><input type='button' class='delTr' value='删除'/></td>"+
                "</tr>"
            );
        });
    });
</script>
</body>
</html>