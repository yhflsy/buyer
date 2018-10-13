<?php defined('SYSPATH') OR die('No direct script access.');

echo str_repeat(PHP_EOL, 100);
?>

<style type="text/css">
<?php include Kohana::find_file('views', 'profiler/style', 'css') ?>
	.kohana-hidden{display:none;}
</style>

<?php
$group_stats      = Profiler::group_stats();
$group_cols       = array('min', 'max', 'average', 'total');
$application_cols = array('min', 'max', 'average', 'current');
?>

<div class="kohana kohana-hidden">
    <div class="debug-info">
	<?php foreach (Profiler::groups() as $group => $benchmarks): ?>
    <?php if (in_array($group, ['kohana', 'requests'])) continue;?>
	<table class="profiler">
		<tr class="group">
			<th class="name" rowspan="1"><?php echo __(ucfirst($group)) ?></th>
			<td class="time" colspan="3"><?php echo number_format($group_stats[$group]['total']['time'], 6) ?> <abbr title="seconds">s</abbr></td>
			<td class="memory" colspan="1"><?php echo number_format($group_stats[$group]['total']['memory'] / 1024, 4) ?> <abbr title="kilobyte">kB</abbr></td>
		</tr>
		<tr class="headers">
			<th class="name"><?php echo __('Benchmark') ?></th>
			<?php foreach ($group_cols as $key): ?>
			<th class="<?php echo $key ?>"><?php echo __(ucfirst($key)) ?></th>
			<?php endforeach ?>
		</tr>
		<?php foreach ($benchmarks as $name => $tokens): ?>
		<tr class="mark time">
			<?php $stats = Profiler::stats($tokens) ?>
            <?php if(strpos($name, '/log/')):
                $names = explode('::', $name);
            ?>
            <th class="name rowgroup" rowspan="2" scope="rowgroup">
                <?php echo HTML::chars(current($names)) ?> [<u><a target="_blank" href="/log.json.html?file=<?php echo $names[1];?>">log</a></u>]
            </th>
            <?php else: ?>
			<th class="name rowgroup" rowspan="2" scope="rowgroup"><?php echo HTML::chars($name), ' (', count($tokens), ')' ?></th>
            <?php endif; ?>
			<?php foreach ($group_cols as $key): ?>
			<td class="<?php echo $key ?>">
				<div>
					<div class="value"><?php echo number_format($stats[$key]['time'], 6) ?> <abbr title="seconds">s</abbr></div>
					<?php if ($key === 'total' && $group_stats[$group]['max']['time']>0): ?>
						<div class="graph" style="left: <?php echo max(0, 100 - $stats[$key]['time'] / $group_stats[$group]['max']['time'] * 100) ?>%"></div>
					<?php endif ?>
				</div>
			</td>
			<?php endforeach ?>
		</tr>
		<tr class="mark memory">
			<?php foreach ($group_cols as $key): ?>
			<td class="<?php echo $key ?>">
				<div>
					<div class="value"><?php echo number_format($stats[$key]['memory'] / 1024, 4) ?> <abbr title="kilobyte">kB</abbr></div>
					<?php if ($key === 'total'): ?>
						<div class="graph" style="left: <?php echo max(0, 100 - $stats[$key]['memory'] / $group_stats[$group]['max']['memory'] * 100) ?>%"></div>
					<?php endif ?>
				</div>
			</td>
			<?php endforeach ?>
		</tr>
		<?php endforeach ?>
	</table>
	<?php endforeach ?>
<!--
	<table class="profiler">
		<?php $stats = Profiler::application() ?>
		<tr class="final mark time">
			<th class="name" rowspan="2" scope="rowgroup"><?php echo __('Application Execution').' ('.$stats['count'].')' ?></th>
			<?php foreach ($application_cols as $key): ?>
			<td class="<?php echo $key ?>"><?php echo number_format($stats[$key]['time'], 6) ?> <abbr title="seconds">s</abbr></td>
			<?php endforeach ?>
		</tr>
		<tr class="final mark memory">
			<?php foreach ($application_cols as $key): ?>
			<td class="<?php echo $key ?>"><?php echo number_format($stats[$key]['memory'] / 1024, 4) ?> <abbr title="kilobyte">kB</abbr></td>
			<?php endforeach ?>
		</tr>
	</table>
-->
    </div>
    <div class="kohana-footer" style="text-align: center; background: #ffc; line-height: 2em; opacity: .7"  onclick="toggledebuginfo()">
	    [<?php echo Kohana::$environment; ?>]
	    [<a href="/?_printuser=1" target="_blank">$this->user</a>]
	    [<a href="/log.latest.html" target="_blank" onclick="setTimeout(function(){$('.debug-info').hide()}, 100)">服务日志</a>]
	    [<a onclick="$(this).parent().remove(); toggledebuginfo();">关闭</a>]
    </div>
</div>

<script>
if(typeof $ === "undefined"){
    var fileref=document.createElement('script');
    fileref.setAttribute("src", "http://libs.baidu.com/jquery/1.8.3/jquery.min.js");
    document.getElementsByTagName("head")[0].appendChild(fileref);
}

function toggledebuginfo(){
    $(".debug-info").toggle();
}

setTimeout(function(undef){
    if ($ === undef) return ;
	$(".kohana-hidden").removeClass("kohana-hidden");
    $(".debug-info").toggle();
    $(document).keyup(function(e){
        if (e.keyCode == 18) toggledebuginfo();
    });
	$(document).on("mouseup", ".rowgroup", function () {
		var text = "";
		if (window.getSelection)
			text = $.trim(window.getSelection().toString());
		else if (document._text && document._text.type != "Control")
			text = $.trim(document._text.createRange().text);
		if (/[0-9a-f]{32}$/.test(text))
			window.open("/log.redis.html?key=php:apiResult:"+text.substr(-32));
	});

	var cacheEl = false;
	$(".name", $(".profiler .group")).each(function(){
		if($(this).text() == "缓存服务") cacheEl = $(this).closest("table");
	});
	if (!cacheEl) return;
	var reidsCachaCount = 0;
	function clearingRedisCache(){
		reidsCachaCount --;
		$("#kohana-redis-cache").text("[还剩 "+ reidsCachaCount + " 条缓存待清除]");
	}
	$(document).on("click", ".redis-clear", function (e) {
		e.stopPropagation();
		if (! $("#kohana-redis-cache").hasClass("running")) {
			$("#kohana-redis-cache").addClass("running");
            $(".rowgroup",cacheE1).each(function(index,e){
				$.get("/log.redis.html?clear=1&key="+$(e).text().substr(0,46), clearingRedisCache)
			});
		}
	});
	if ($(".profiler").size()>2) {
		reidsCachaCount = $(".rowgroup", cacheEl).size();
		$(".kohana-footer").prepend($("<a id='kohana-redis-cache'>").attr("href","###").addClass("redis-clear").text("[清除本页"+reidsCachaCount+"个服务的缓存]"));
	}
}, 200);
</script>