/****************************************

 @Date：2015-09-21
 @Copyright:Getaj
       
 */

;!function(window, undefined){        
"use strict";
var path = '', //组件存放目录，为空表示自动获取(不用填写host，相对站点的根目录即可)。
$, win, ready = {
    getPath: function(){
        var js = document.scripts, jsPath = js[js.length - 1].src;
        return path ? path : jsPath.substring(0, jsPath.lastIndexOf("/") + 1);
    },
    //五种原始层模式
    type: ['dialog','tips']
};

//默认内置方法。
window.info = {
    v: '1.0.0',
    ie6: !!window.ActiveXObject && !window.XMLHttpRequest,
    index: 0,
    path: ready.getPath(),
    
    //载入模块
    use: function(module, callback){
        var i = 0, head = $('head')[0];
        var module = module.replace(/\s/g, '');
        var iscss = /\.css$/.test(module);
        var node = document.createElement(iscss ? 'link' : 'script');
        var id = module.replace(/\.|\//g, '');
        if(iscss){
            node.type = 'text/css';
            node.rel = 'stylesheet';
        }
        node[iscss ? 'href' : 'src'] = /^http:\/\//.test(module) ? module : info.path + module;
        node.id = id;
        if(!$('#'+ id)[0]){
            head.appendChild(node);
        }
        if(callback){
            if(document.all){
                $(node).ready(callback);
            } else {
                $(node).load(callback);
            }
        }
    },
    //tips层快捷引用
    tips: function(html, follow, parme, maxWidth, guide, style){
        var conf = {
            type: 4, shade: false, 
            tips: {msg: html, follow: follow}    
        };
        conf.time = typeof parme === 'object' ? parme.time : (parme|0);
        parme = parme || {};
        conf.maxWidth = parme.maxWidth || maxWidth;
        conf.tips.guide = parme.guide || guide;
        conf.tips.style = parme.style || style;
        conf.tips.more = parme.more;
        return $.info(conf);
    }
};

//缓存常用字符
var doms = ['zpbox_info','.zpbox_main'];

var Class = function(setings){    
    var that = this, config = that.config;
    info.index++;
    that.index = info.index;
    that.config = $.extend({} , config , setings);
    that.config.dialog = $.extend({}, config.dialog , setings.dialog);
    that.config.tips = $.extend({}, config.tips , setings.tips);
    that.creat();
};

Class.pt = Class.prototype;

//默认配置
Class.pt.config = {
    type: 0,
    icon: 1,
    msg: '',
	href:'#',
    fix: true,
    title: '信息提示框',
    area: ['530px', 'auto'],
    time: 0,
    zIndex: 19880602, 
    classs:"zpbox_yes",
    success: function(info){}, //创建成功后的回调
    close: function(index){ info.close(index);}, //右上角关闭回调
    end: function(){} //终极销毁回调
};

//容器
Class.pt.space = function(html){
    var that = this, html = html || '', times = that.index, config = that.config, dialog = config.dialog;
	var zIndex = config.zIndex + times;
	return ['<div times="'+ times +'" id="zpbox_shade' + times + '" class="zpbox_shade" style="z-index:'+ zIndex +'"></div>','<div class="zpbox_info" id="zpbox_info'+times+'" style="z-index:'+ zIndex +'">'    
	+ '<div class="zpbox_title"><em>'+config.title+'</em></div>'
	+ '<div class="zpbox_body"><div class="zpbox_bicon zpbox_bicon_'+config.icon+'"></div><h3>'+config.msg+'</h3></div>'
	+ '<div class="zpbox_footer"><a href="'+config.href+'" class="'+config.classs+'">确定</a></div>'
	+ '</div>'
	];
};

//创建骨架
Class.pt.creat = function(){
    var that = this , space = '', config = that.config, dialog = config.dialog, times = that.index;
    var page = config.page, body = $("body"), setSpace = function(html){
        var html = html || '';
        space = that.space(html);
        body.append($(space[0]));
    };
	setSpace();
	body.append($(space[1]));
    var infoE = that.infoE = $('#'+ doms[0] + times);
    infoE.css({width: config.area[0], height: config.area[1]});
	that.set(times);
    that.callback();
};
//初始化骨架
Class.pt.set = function(times){
    var that = this;
    var config = that.config;
    var infoE = that.infoE;
    that.autoArea(times);
    infoE.attr({'type' :  ready.type[config.type]});
    //坐标自适应浏览器窗口尺寸
	win.on('resize', function(){
		infoE.css({top: (win.height() - infoE.outerHeight())/2});
	});
};
//自适应宽高
Class.pt.autoArea = function(times){
    var that = this, times = times || that.index, config = that.config, page = config.page;
    var infoE = $('#'+ doms[0] + times);
    (info.ie6 && config.area[0] !== 'auto') && infoMian.css({width : infoE.outerWidth()});
   	infoE.css({marginLeft : -infoE.outerWidth()/2});
	infoE.css({top: (win.height() - infoE.outerHeight())/2});
};

//自动关闭info
Class.pt.autoclose = function(){
    var that = this, time = that.config.time, maxLoad = function(){
        time--;
        if(time === 0){
            info.close(that.index);
            clearInterval(that.autotime);
        }
    };
    that.autotime = setInterval(maxLoad , 1000);
};
ready.config = {
    end: {}
};
Class.pt.callback = function(){
    var that = this, infoE = that.infoE, config = that.config, dialog = config.dialog;
    that.openinfo();
    that.config.success(infoE);
    info.ie6 && that.IE6(infoE);
     infoE.find('.zpbox_yes').on('click', function(){
        config.close(that.index);
        info.close(that.index);
    });
    infoE.find('.closepage').on('click', function(){
        window.close();
    });
    ready.config.end[that.index] = config.end;
};

//恢复select
ready.reselect = function(){
    $.each($('select'), function(index , value){
        var sthis = $(this);
        if(!sthis.parents('.'+doms[0])[0]){
            (sthis.attr('info') == 1 && $('.'+doms[0]).length < 1) && sthis.removeAttr('info').show(); 
        }
        sthis = null;
    });
}; 
Class.pt.IE6 = function(infoE){
    var that = this;
    var _ieTop = infoE.offset().top;    
    //ie6的固定与相对定位
    if(that.config.fix){
        var ie6Fix = function(){
            infoE.css({top : win.scrollTop() + _ieTop});
        };    
    }else{
        var ie6Fix = function(){
            infoE.css({top : _ieTop});    
        };
    }
    ie6Fix();
    win.scroll(ie6Fix);
    //隐藏select
    $.each($('select'), function(index , value){
        var sthis = $(this);
        if(!sthis.parents('.'+doms[0])[0]){
            sthis.css('display') == 'none' || sthis.attr({'info' : '1'}).hide();
        }
        sthis = null;
    });
};
//给info对象拓展方法
Class.pt.openinfo = function(){
    var that = this, infoE = that.infoE;

    //自适应宽高
    info.autoArea = function(index){
        return that.autoArea(index);
    };
    //置顶当前窗口
    info.zIndex = that.config.zIndex;
    info.setTop = function(infoNow){
        var setZindex = function(){
            info.zIndex++;
            infoNow.css('z-index', info.zIndex + 1);
        };
        info.zIndex = parseInt(infoNow[0].style.zIndex);
        infoNow.on('mousedown', setZindex);
        return info.zIndex;
    };
};

ready.isauto = function(infoo, options, offset){
    options.area[0] === 'auto' && (options.area[0] = infoo.outerWidth());
    options.area[1] === 'auto' && (options.area[1]  = infoo.outerHeight());
    infoo.attr({area: options.area + ',' + offset});
    infoo.find('.zpbox_max').addClass('zpbox_maxmin');
};

ready.rescollbar = function(index){
    if(doms.html.attr('info-full') == index){
        if(doms.html[0].style.removeProperty){
            doms.html[0].style.removeProperty('overflow');
        } else {
            doms.html[0].style.removeAttribute('overflow');
        }
        doms.html.removeAttr('info-full');
    }
};

//获取page层所在索引
info.getIndex = function(selector){
    return $(selector).parents('.'+doms[0]).attr('times');    
};
//关闭info总方法
info.close = function(index){
    var infoo = $('#'+ doms[0] + index), type = infoo.attr('type'), shadeNow = $('#zpbox_moves, #zpbox_shade' + index);
    if(!infoo[0]){
        return;
    }
	infoo[0].innerHTML = '';
	infoo.remove();
    shadeNow.remove();
    info.ie6 && ready.reselect();
    ready.rescollbar(index);
    typeof ready.config.end[index] === 'function' && ready.config.end[index]();
    delete ready.config.end[index]; 
};
//关闭所有层
info.closeAll = function(type){
    $.each($('.'+doms[0]), function(){
        var othis = $(this);
        var is = type ? (othis.attr('type') === type) : 1;
        if(is){
            info.close(othis.attr('times'));
        }
        is = null;
    });
};

//主入口
ready.run = function(){
    $ = jQuery; 
    win = $(window);
    doms.html = $('html');
    info.use('info.css');
    $.info = function(deliver){
        var o = new Class(deliver);
        return o.index;
    };
    (new Image()).src = info.path + 'bg.jpg';
};

ready.run();//启动

}(window);