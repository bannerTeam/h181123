function pageInit(options) {

	var settings = {
		e: "#rlist",
		loading: true,
		url: "/ajax/get_vod_relevant",
		page: 1,
		limit: 12,
		id: 0,
		tid: 0,
		tpid: 0,
		timeadd: 0,
		by: "",
		wd: "",
		fn: successFn
	};
	var o = $.extend(settings, options);

	/**
	 * 获取列表
	 */
	function get_list() {
		var lindex = layer.load();
		o.loading = false;
		$.ajax({
			url: o.url,
			dataType: "json",
			data: {
				by: o.by,
				timeadd: o.timeadd,
				tid: o.tid,
				tpid: o.tpid,
				wd: o.wd,
				page: o.page,
				limit: o.limit
			},
			success: function(data) {
				layer.close(lindex);
				if(o.fn && $.isFunction(o.fn)) {
					o.fn(data);
				}
				if(o.page == data.pagecount){
					o.page = 1;
				}else{
					o.page++;
				}
				
			}
		});
	}


	function successFn(data) {

		var items = data.list,
			str = "";

		for(var i = 0; i < items.length; i++) {
			
			str +=`<div class="item">
						<a target="_blank" href="/vod/detail/id/${items[i].vod_id}">
							<div class="thumb">
								<img alt="${items[i].vod_name}" src="${items[i].vod_pic}" />
							</div>
							<div class="title ui-nowrap">
								${items[i].vod_name}
							</div>
							<div class="date clearfix">
								<span class="l">
								<i class="iconfont icon-riqi"></i>&nbsp;${date('Y-m-d',items[i].vod_time_add)}
							</span>
								<span class="r">
								<i class="iconfont icon-shijian"></i>&nbsp;${items[i].vod_duration}
							</span>
							</div>
							<div class="ft clearfix">
								<span class="l">
								<i class="iconfont icon-huida"></i>&nbsp;${items[i].vod_up}
							</span>
								<span class="r">
								<i class="iconfont icon-yanjing"></i>&nbsp;已被观看：${items[i].vod_hits}次
							</span>
							</div>
						</a>
					</div>`;
			

		}

		$(o.e).html(str);
	}

	/**
	 * 换一批
	 */
	$("#rchange").click(function(){
		get_list();
	})
	
	/**
	 * 格式化日期
	 * @param {Object} sj
	 */
	function Rmd(sj) {
		var now = new Date(sj * 1000);
		var year = now.getFullYear();
		var month = now.getMonth() + 1;
		var date = now.getDate();
		return month + " - " + date;
	}

	$("#collection").click(function() {
		if(h18.login(true)) {
			Collection(this);
		}
	})
	/**
	 * 收藏/取消
	 */
	function Collection(obj) {
		if($(obj).data('loading')) {
			return false;
		}
		$(obj).data('loading', true);
		$.ajax({
			url: "/index.php/user/ajax_collection",
			dataType: "json",
			data: {
				ac: $(obj).hasClass('active') ? "delr" : "set",
				id: o.id
			},
			success: function(data) {
				if(data.code == 1) {
					if($(obj).hasClass('active')) {
						$("#collection").text("收藏").removeClass('active');
						tips("已取消");
					} else {
						$("#collection").text("已收藏").addClass('active');
						tips("收藏成功");
					}
				} else {
					tips(data.msg);
				}
				$(obj).data('loading', false);
			}
		});
	}
	/**
	 * 获取收藏状态
	 */
	function get_collection() {
		h18.login() && $.ajax({
			url: "/index.php/user/ajax_collection",
			dataType: "json",
			data: {
				ac: "get",
				id: o.id
			},
			success: function(data) {
				if(data.code == 1) {
					is_collection = true;
					$("#collection").text("已收藏").addClass('active');
				}
			}
		});
	}
	get_collection();

	var is_collection = false,
		is_up = false,
		is_down = false;

	$(".j-up").click(function() {
		h18.login(true);
		if(!is_up) {
			up_down(this, 3);
			is_up = true;
		} else {
			tips("已顶");
		}
	})
	$(".j-down").click(function() {
		h18.login(true);
		if(!is_down) {
			up_down(this, 6);
			is_down = true;
		} else {
			tips("已踩");
		}
	});

	/**
	 * 获取状态
	 */
	function get_up_down() {
		!h18.login() && $.ajax({
			url: "/user/ajax_up_down",
			dataType: "json",
			data: {
				ac: "get",
				id: o.id
			},
			success: function(data) {
				if(data.up > 0) {
					is_up = true;
				}
				if(data.down > 0) {
					is_down = true;
				}
			}
		});
	}
	get_up_down();

	/**
	 * 顶数/顶数
	 * 3想看（顶）; 6.不想看（踩）
	 */
	function up_down(obj, type) {
		if($(obj).data('loading')) {
			return false;
		}
		$(obj).data('loading', true);
		$.ajax({
			url: "/user/ajax_up_down",
			dataType: "json",
			data: {
				ac: "set",
				id: o.id,
				type: type
			},
			success: function(data) {
				if(data.code == 1) {
					if(type == 3) {
						$("#up").text(Number($.trim($("#up").text())) + 1);
					} else {
						$("#down").text(Number($.trim($("#down").text())) - 1);
					}
					tips("成功");
				}

			}
		});
	}

	function tips(msg) {
		layer.msg(msg);
	}

	get_list();

	var browsevod = Number($.cookie('h18_browsevod'));
	if($("#video").length == 0) {
		vod_disable();
	} else if((h18.userid) == 0 && browsevod > 0) {
		vod_disable();
	} else {
		setTimeout(function() {
			$.ajax({
				type: "post",
				url: "/vod/ajax_browse",
				dataType: "json",
				data: {
					id: o.id
				},
				success: function(data) {
					if(data.code < 1) {
						vod_disable(data.code);
					}
				}
			});
		}, 1000 * 30);
	}

	function vod_disable(status) {
		$("#video-container").html("");

		layer.open({
			content: (h18.userid) == 0 ? '今日的看片次数已经用完，请明日再来<br/>注册会员享更多看片次数<br/>推广给朋友可获得永久VIP' : '今日的看片次数已经用完，请明日再来<br/>购买VIP会员享无限次看片权益<br/>推广给朋友可获得永久VIP',
			btn: [(h18.userid) == 0 ? '注册' : '升级', (h18.userid) == 0 ? '登录' : '不要'],
			yes: function(index, layero) {
				if(h18.userid > 0) {
					location.href = "/user/vip";
				} else {
					location.href = "/user/reg";
				}
			},
			btn2: function(index, layero) {
				if((h18.userid) == 0) {
					location.href = "/user/login";
				} else {
					location.href = "/about/vip";
				}
			},
			cancel: function() {
				location.href = "/about/vip";
			}
		});

	}

}

function loadCss(src) {
	var cssTag = document.getElementById('loadCss');
	var head = document.getElementsByTagName('head').item(0);
	if(cssTag) head.removeChild(cssTag);
	css = document.createElement('link');
	css.href = src;
	css.rel = 'stylesheet';
	css.type = 'text/css';
	css.id = 'loadCss';
	head.appendChild(css);
}
var VideoPlayer = {
	player: false,
	setting: {
		w: 920,
		h: 517
	},
	init: function() {

		var ww = $(window).width(),
			w = (ww < 620) ? $("body").width() : 920,
			h = w / 16 * 9;

		$("#video").css({
			height: h
		});

		VideoPlayer.setting.w = w;
		VideoPlayer.setting.h = h;

		if((/Safari/.test(navigator.userAgent) && !/Chrome/.test(navigator.userAgent))) {
			loadCss("/static/player/video/video.css");
			$("head").append('<script type="text/javascript" src="/static/player/video/video.min.js"></script>');
			$("head").append('<script type="text/javascript" src="/static/player/video/videojs-contrib-hls.js"></script>');
			VideoPlayer.safariVideo();
			return false;
		} else {
			$("#video_banner").remove();
			VideoPlayer.player = new ckplayer(videoObject);
		}
	},
	safariVideo: function() {

		VideoPlayer.getPlayerAdv(function() {
			var playUrl = videoObject.video;
			if(typeof(videoObject.video) == "object") {
				playUrl = videoObject.video[0][0];
			}

			$("#video").append(`<video id="roomVideo1" class="video-js vjs-big-play-centered" controls preload="none" ><source id="source" src="${playUrl}" type="application/x-mpegURL"></video>`);

			VideoPlayer.newVideo();

		});

	},
	newVideo: function() {
		if(typeof(videojs) == "undefined") {
			setTimeout(function() {
				VideoPlayer.newVideo();
			}, 2000);
		} else {
			$("#video_banner").remove();
			var myPlayer = videojs('roomVideo1', {
				autoplay: false,
				height: VideoPlayer.setting.h,
				width: VideoPlayer.setting.w
			});
		}
	},
	getPlayerAdv: function(fn) {
		if(videoObject.uvip == "1") {
			if(fn && $.isFunction(fn)) {
				fn();
			}
			$("#video").show();
			$("#video_banner").hide();
			return false;
		}

		var playbanner = {};
		$.ajax({
			type: "get",
			url: "/Ajax/get_playbanner",
			dataType: "json",
			success: function(ret) {
				playbanner.Adv = {
					front: ret.front[0],
					pause: ret.pause[0]
				};

				if(playbanner.Adv.front) {

					var ti = parseInt(playbanner.Adv.front.time);

					$("#video_banner").append(`<div id="ad_front" style="" class="banner_box">
						<div><span id="adv_count_down" class="adv_count_down"></span><a target="_blank" href="${playbanner.Adv.front.link}"><img src="${playbanner.Adv.front.file}" /></a></div>						
					</div>`);

					var aaa = setInterval(function() {
						$("#adv_count_down").text('(' + ti + 's)');
						ti--;
						if(ti < 0) {
							clearInterval(aaa);
							$("#video").show();
							$("#video_banner").hide();
							if(fn && $.isFunction(fn)) {
								fn();
							}
						}
					}, 1000);
				}

				if(playbanner.Adv.pause) {
					$("#video_banner").append(`<div id="ad_pause" style="display: none;" class="banner_box">
						<div><a target="_blank" href="${playbanner.Adv.pause.link}"><img src="${playbanner.Adv.pause.file}" /></a></div>
						<span href="javascript:;" class="ad_close"><span class="ad_close_s"></span></span>
					</div>`);
				}

				$("#video_banner img").css({
					"max-height": playbanner.h,
					"max-width": playbanner.w
				});

			}
		});
	}
}