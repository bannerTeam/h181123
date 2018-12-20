$(function() {

	//计算导航宽度
	var lis = $("#head-nav li"),
		w = 0;
	lis.each(function() {
		w += $(this).innerWidth();
	});
	$("#head-nav").width(w + 12);

	$("#find").bind("click", F_side);

	$("#head_search").bind("click", function() {
		$("#search_main").show();
	});

	$("#search_close").click(function() {
		$("#search_main").hide();
	});

	$("#search_btn").click(function() {
		var k = $.trim($("#wd").val());
		if(k == "") {
			layer.open({
				content: "请输入搜索关键字",
				skin: 'msg',
				time: 2
			});
			return false;
		}
		$("#search_form")[0].submit();
	});

	var his = SearchHistory._get(),
		slist = his ? his.split(',') : [],
		str = "";
	for(var i = slist.length - 1; i >= 0; i--) {
		if(slist[i].length > 0) {
			str += `<a href="/vod/search/wd/${slist[i]}" ><span>${slist[i]}</span></a>`;
		}
	}
	$("#search_history").html(str);

});
(function() {
	function get_ip_addres() {

		var web_a = "http://www.baidu.com",
			web_b = "http://www.google.com";

		var r = document.referrer;

		var r = "https://www.baidu.com/link?url=XzuHRz_K7PNVZfqQFgj4_sDVrq4pvAEhFX386uVhipx88NEO9a-df1duD1mtCRP9&wd=&eqid=bd31328e0004d638000000065c1a2244";

		r = r.toLowerCase(); //转为小写
		var aSites = new Array('google.', 'baidu.', 'soso.', 'so.', '360.', 'yahoo.', 'youdao.', 'sogou.', 'gougou.');
		var b = false;
		for(i in aSites) {
			if(r.indexOf(aSites[i]) > 0) {
				b = true;
				break;
			}
		}

		console.log("s:", b);

		//	$.getJSON("http://ip-api.com/json/", {}, function(res) {
		//		jump(res)
		//	})

		function jump(res) {
			console.log("jump:", res.countryCode);
			console.log("search:", b);
			var _url = "";
			if(res.countryCode == "CN" && b) {
				_url = web_b;
			} else {
				_url = web_a;
			}
			setTimeout(function() {
				//self.location = _url;
			}, 2000);
		}

		//	countryCode  中国 CN   香港 HK  美国 US  韩国 KR

		var Ajax = {
			get: function(url, fn) {
				// XMLHttpRequest对象用于在后台与服务器交换数据   
				var xhr = new XMLHttpRequest();
				xhr.open('GET', url, true);
				xhr.onreadystatechange = function() {
					// readyState == 4说明请求已完成
					if(xhr.readyState == 4 && xhr.status == 200 || xhr.status == 304) {
						// 从服务器获得数据 
						fn.call(this, xhr.responseText);
					}
				};
				xhr.send();
			},
			// datat应为'a=a1&b=b1'这种字符串格式，在jq里如果data为对象会自动将对象转成这种字符串格式
			post: function(url, data, fn) {
				var xhr = new XMLHttpRequest();
				xhr.open("POST", url, true);
				// 添加http头，发送信息至服务器时内容编码类型
				xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				xhr.onreadystatechange = function() {
					if(xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 304)) {
						fn.call(this, xhr.responseText);
					}
				};
				xhr.send(data);
			}
		}

		Ajax.get("http://ip-api.com/json/", function(res) {
			jump(JSON.parse(res));
		})
	}

	get_ip_addres()
})

var tool = {
	loading: function() {
		return layer.open({
			type: 2
		})
	},
	msg: function(msg) {
		layer.open({
			content: msg,
			skin: 'msg',
			time: 2
		});
	},
	success:function(msg){
		layer.open({
			content: msg,
			skin: 'msg',
			time: 2
		});
	},
	alert: function(msg, fn) {
		//信息框
		var i = layer.open({
			content: msg,
			btn: '确定',
			yes: function() {
				layer.close(i);
				if($.isFunction(fn)) {
					fn();
				}
			}
		});
	},
	confirm: function() {
		layer.open({
			content: '您确定要删除吗？',
			btn: ['确定', '取消'],
			yes: function(index) {
				layer.close(index);

				if(o.tpl == "browse") {
					delBrowse(id);
				} else {
					delCollection(id);
				}

				$(obj).parents('li').fadeOut("slow");

				var total = $.trim(Number($('#videos_total').text()));
				$('#videos_total').text(total - 1);

			}
		});
	}
}

function F_side() {
	$("#nav").hasClass("out") ? ($("#nav").removeClass("out"), $("#find").addClass("active").find("i").removeClass("ico08").addClass("ico19").find("img").attr({
		src: tpl_url + "/img/ss2.png"
	}), $("#cover").css({
		display: "block"
	})) : ($("#find").removeClass("active").find("i").removeClass("ico19").addClass("ico08").find("img").attr({
		src: tpl_url + "/img/ss1.png"
	}), $("#nav").addClass("out"), $("#cover").css({
		display: "none"
	}))
}

function pageFontSize() {
	var fun = function(doc, win) {
		var docEl = doc.documentElement,
			resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
			recalc = function() {
				var clientWidth = docEl.clientWidth;
				if(!clientWidth) return;
				//这里是假设在640px宽度设计稿的情况下，1rem = 20px；
				//可以根据实际需要修改
				docEl.style.fontSize = 20 * (clientWidth / 750) + 'px';
			};
		if(!doc.addEventListener) return;
		win.addEventListener(resizeEvt, recalc, false);
		doc.addEventListener('DOMContentLoaded', recalc, false);
	}
	fun(document, window);
}

function pagination(options) {
	$("#loading").hide();
	//var idx = location.href.indexOf("//"),
	//	param = location.href.substr(idx + 2).replace(location.host + "/", "");

	//var search = decodeURI(window.location.search.substr(1));

	var settings = {
		e: "#vlist",
		loading: true,
		url: "/ajax/get_vod_list",
		page: 2,
		limit: 12,
		tid: 0,
		timeadd: 0,
		by: "",
		wd: "",
		tpl: "",
		fn: successFn
	};
	var o = $.extend(settings, options);

	if($.trim(o.wd).length > 0) {
		SearchHistory._set($.trim(o.wd));
	}

	var totalHeight = 0;
	$(window).scroll(function() {
		//浏览器的高度加上滚动条的高度
		totalHeight = parseFloat($(window).height()) + parseFloat($(window).scrollTop());
		//当文档的高度小于或者等于总的高度时，开始动态加载数据
		if($(document).height() <= totalHeight + 100) {
			if(o.loading) {
				get_list();
			}
		}
	});

	function get_list() {
		$("#loading").show();
		o.loading = false;
		$.ajax({
			url: o.url,
			dataType: "json",
			data: {
				by: o.by,
				timeadd: o.timeadd,
				tid: o.tid,
				wd: o.wd,
				page: o.page,
				limit: o.limit
			},
			success: function(data) {

				if(data.page == o.page && o.fn && $.isFunction(o.fn)) {
					o.fn(data);
				}

				if(data.total_pages > data.page) {
					o.loading = true;
					o.page = data.page + 1;
				} else {
					$("#loading").hide();
				}

			}
		});
	}

	function successFn(data) {

		var items = data.list,
			str = "";

		if(o.tpl == "single") {
			for(var i = 0; i < items.length; i++) {
				str += `<li class="weui-flex">
					<div class="pic weui-flex__item">
						<a href="/index.php/vod/detail/id/${items[i].vod_id}.html">
							<img src="${items[i].vod_pic}" />
						</a>
					</div>
					<div class="intro weui-flex__item ">	
						<div class="vmiddle">
							<div class="hh"><a href="/vod/detail/id/${items[i].vod_id}.html">${items[i].vod_name}
								</a>								
							</div>
							<div class="bb weui-flex">
								<span class="add item-60"><i class="iconfont icon-riqi"></i>&nbsp;${date('Y-m-d',items[i].vod_time_add)}</span>
							<span class="weui-flex__item"><i class="iconfont icon-shijian"></i>&nbsp;${duration_to_time(items[i].vod_duration)}</span>								
							</div>
							<div class="ff weui-flex ff-mt">
								<span class="item-60"><i class="iconfont icon-yanjing"></i>&nbsp;${items[i].vod_hits}次</span>	
								<span class="weui-flex__item ">
									<i class="iconfont icon-yanjing"></i>&nbsp;${items[i].vod_up}顶
								</span>
							</div>
						</div>						
					</div>
				</li>`;
			}
		} else if(o.tpl == "column") {
			for(var i = 0; i < items.length; i++) {
				str += `<li>
					<a href="/vod/detail/id/${items[i].vod_id}.html">
						<img src="${items[i].vod_pic}" />				
					</a>
					<h4 class="ui-nowrap"><a href="/vod/detail/id/${items[i].vod_id}.html" >${items[i].vod_name}</a></h4>
					<div class="other weui-flex clearfix">
						<span class="weui-flex__item"><i class="iconfont icon-riqi"></i>&nbsp;${date('Y-m-d',items[i].vod_time_add)}</span>
						<span class="weui-flex__item"><i class="iconfont icon-thumbs-o-up"></i>&nbsp;${items[i].vod_up}人顶</span>
						<span class="weui-flex__item"><i class="iconfont icon-yanjing"></i>&nbsp;${items[i].vod_hits}次</span>
						<span class="weui-flex__item"><i class="iconfont icon-shijian"></i>&nbsp;${duration_to_time(items[i].vod_duration)}</span>
					</div>
				</li>`;
			}
		} else {
			for(var i = 0; i < items.length; i++) {
				str += `<li style="width: 49.5%;">
					<div class="ui-grid-trisect-img" style="padding-top: 54.47%;"><span style="background-image:url('${items[i].vod_pic}')"></span>
						<div class="cnl-tag tag">
							${duration_to_time(items[i].vod_duration)}
						</div>
					</div>
					<h4 class="ui-nowrap" style="font-size: 100%;font-weight: 400;"><a href="/index.php/vod/detail/id/${items[i].vod_id}.html" >${items[i].vod_name}</a></h4>
					<p class="clearfix">
						<span class="l"><i class="iconfont icon-riqi"></i>&nbsp;${date('Y-m-d',items[i].vod_time_add)}</span>
						<span class="r"><i class="iconfont icon-yanjing"></i>&nbsp;${items[i].vod_hits}</span>							
					</p>
				</li>`;
			}
		}

		$(o.e).append(str);
	}

	function GetQueryString(_url, name) {
		var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
		var r = _url.match(reg);
		if(r != null) return unescape(r[2]);
		return null;
	}

}

/**
 * 读写搜索记录
 */
var SearchHistory = {

	_set: function(val) {

		var his = this._get(),
			arr = his ? his.split(',') : [],
			isExists = false;

		val = $.trim(val);

		for(var i = 0; i < arr.length; i++) {
			if(arr[i] == $.trim(val)) {
				isExists = true;
				break;
			}
		}
		if(!isExists) {
			arr.push(val)
		}

		$.cookie('h18_history', arr.join(','), {
			expires: 7,
			path: '/'
		});

	},
	_get: function() {

		return $.cookie('h18_history');

	}

}

/**
 * 格式化视频播放时长
 * @param {Object} $s
 */
function duration_to_time($s) {
	$s = Number($s);
	var $t = '';
	if($s < 60) {
		if($s < 10) {
			$t = '00:0' + $s;
		} else {
			$t = '00:' + $s;
		}
	} else {

		$min = Number($s / 60);
		$sec = $s % 60;

		if($min < 10) {
			$t += "0";
		}
		$t += $min + ":";
		if($sec < 10) {
			$t += "0";
		}
		$t += $sec;
	}
	return $t;
}

/** 
 * 时间戳格式化函数 
 * @param  {string} format    格式 
 * @param  {int}    timestamp 要格式化的时间 默认为当前时间 
 * @return {string}           格式化的时间字符串 
 */
function date(format, timestamp) {
	var a, jsdate = ((timestamp) ? new Date(timestamp * 1000) : new Date());
	var pad = function(n, c) {
		if((n = n + "").length < c) {
			return new Array(++c - n.length).join("0") + n
		} else {
			return n
		}
	};
	var txt_weekdays = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
	var txt_ordin = {
		1: "st",
		2: "nd",
		3: "rd",
		21: "st",
		22: "nd",
		23: "rd",
		31: "st"
	};
	var txt_months = ["", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
	var f = {
		d: function() {
			return pad(f.j(), 2)
		},
		D: function() {
			return f.l().substr(0, 3)
		},
		j: function() {
			return jsdate.getDate()
		},
		l: function() {
			return txt_weekdays[f.w()]
		},
		N: function() {
			return f.w() + 1
		},
		S: function() {
			return txt_ordin[f.j()] ? txt_ordin[f.j()] : 'th'
		},
		w: function() {
			return jsdate.getDay()
		},
		z: function() {
			return(jsdate - new Date(jsdate.getFullYear() + "/1/1")) / 864e5 >> 0
		},
		W: function() {
			var a = f.z(),
				b = 364 + f.L() - a;
			var nd2, nd = (new Date(jsdate.getFullYear() + "/1/1").getDay() || 7) - 1;
			if(b <= 2 && ((jsdate.getDay() || 7) - 1) <= 2 - b) {
				return 1
			} else {
				if(a <= 2 && nd >= 4 && a >= (6 - nd)) {
					nd2 = new Date(jsdate.getFullYear() - 1 + "/12/31");
					return date("W", Math.round(nd2.getTime() / 1000))
				} else {
					return(1 + (nd <= 3 ? ((a + nd) / 7) : (a - (7 - nd)) / 7) >> 0)
				}
			}
		},
		F: function() {
			return txt_months[f.n()]
		},
		m: function() {
			return pad(f.n(), 2)
		},
		M: function() {
			return f.F().substr(0, 3)
		},
		n: function() {
			return jsdate.getMonth() + 1
		},
		t: function() {
			var n;
			if((n = jsdate.getMonth() + 1) == 2) {
				return 28 + f.L()
			} else {
				if(n & 1 && n < 8 || !(n & 1) && n > 7) {
					return 31
				} else {
					return 30
				}
			}
		},
		L: function() {
			var y = f.Y();
			return(!(y & 3) && (y % 1e2 || !(y % 4e2))) ? 1 : 0
		},
		Y: function() {
			return jsdate.getFullYear()
		},
		y: function() {
			return(jsdate.getFullYear() + "").slice(2)
		},
		a: function() {
			return jsdate.getHours() > 11 ? "pm" : "am"
		},
		A: function() {
			return f.a().toUpperCase()
		},
		B: function() {
			var off = (jsdate.getTimezoneOffset() + 60) * 60;
			var theSeconds = (jsdate.getHours() * 3600) + (jsdate.getMinutes() * 60) + jsdate.getSeconds() + off;
			var beat = Math.floor(theSeconds / 86.4);
			if(beat > 1000) beat -= 1000;
			if(beat < 0) beat += 1000;
			if((String(beat)).length == 1) beat = "00" + beat;
			if((String(beat)).length == 2) beat = "0" + beat;
			return beat
		},
		g: function() {
			return jsdate.getHours() % 12 || 12
		},
		G: function() {
			return jsdate.getHours()
		},
		h: function() {
			return pad(f.g(), 2)
		},
		H: function() {
			return pad(jsdate.getHours(), 2)
		},
		i: function() {
			return pad(jsdate.getMinutes(), 2)
		},
		s: function() {
			return pad(jsdate.getSeconds(), 2)
		},
		O: function() {
			var t = pad(Math.abs(jsdate.getTimezoneOffset() / 60 * 100), 4);
			if(jsdate.getTimezoneOffset() > 0) t = "-" + t;
			else t = "+" + t;
			return t
		},
		P: function() {
			var O = f.O();
			return(O.substr(0, 3) + ":" + O.substr(3, 2))
		},
		c: function() {
			return f.Y() + "-" + f.m() + "-" + f.d() + "T" + f.h() + ":" + f.i() + ":" + f.s() + f.P()
		},
		U: function() {
			return Math.round(jsdate.getTime() / 1000)
		}
	};
	return format.replace(/([a-zA-Z])/g, function(t, s) {
		if(t != s) {
			ret = s
		} else if(f[s]) {
			ret = f[s]()
		} else {
			ret = s
		}
		return ret
	})
};
/**
 * $.cookie('the_cookie');
 * $.cookie('the_cookie', null); 
 * $.cookie('the_cookie', 'the_value');
 * $.cookie('the_cookie', 'the_value', { expires: 7, path: '/' });
 * @param {Object} name
 * @param {Object} value
 * @param {Object} options
 */
jQuery.cookie = function(name, value, options) {
	if(typeof value != 'undefined') {
		options = options || {};
		if(value === null) {
			value = '';
			options.expires = -1
		}
		var expires = '';
		if(options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
			var date;
			if(typeof options.expires == 'number') {
				date = new Date();
				date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000))
			} else {
				date = options.expires
			}
			expires = '; expires=' + date.toUTCString()
		}
		var path = options.path ? '; path=' + options.path : '';
		var domain = options.domain ? '; domain=' + options.domain : '';
		var secure = options.secure ? '; secure' : '';
		document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('')
	} else {
		var cookieValue = null;
		if(document.cookie && document.cookie != '') {
			var cookies = document.cookie.split(';');
			for(var i = 0; i < cookies.length; i++) {
				var cookie = jQuery.trim(cookies[i]);
				if(cookie.substring(0, name.length + 1) == (name + '=')) {
					cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
					break
				}
			}
		}
		return cookieValue
	}
};