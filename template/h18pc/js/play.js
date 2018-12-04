function pageInit(options) {

	var settings = {
		e: "#rlist",
		loading: true,
		url: "/ajax/get_vod_relevant",
		page: 2,
		limit: 12,
		id: 0,
		tid: 0,
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

				if(o.fn && $.isFunction(o.fn)) {
					o.fn(data);
				}

				$("#loading").hide();

			}
		});
	}

	function successFn(data) {

		var items = data.list,
			str = "";

		for(var i = 0; i < items.length; i++) {
			str += `<li>
				<a href="/vod/detail/id/${items[i].vod_id}.html" >
				<div class="ui-grid-trisect-img" style="padding-top: 54.47%;"><span style="background-image:url('${items[i].vod_pic}')"></span>
					<div class="cnl-tag tag">
						${date('m-d',items[i].vod_time_add)}
					</div>
				</div>
				</a>
				<h4 class="ui-nowrap" style="font-size: 100%;font-weight: 400;text-align:center"><a href="/vod/detail/id/${items[i].vod_id}.html" >${items[i].vod_name}</a></h4>
			</li>`;
		}

		$(o.e).append(str);
	}

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
		h18.login(true);
		if(!is_collection) {
			Collection(this);
		}
		$("#collection").text("已收藏");
		tips("已收藏");
	})
	/**
	 * 收藏
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
				ac: "set",
				id: o.id
			},
			success: function(data) {
				is_collection = true;
				if(data.code == 1) {
					$("#collection span").text("已收藏");
				}
				tips(data.msg);
			}
		});
	}
	/**
	 * 获取收藏状态
	 */
	function get_collection() {
		!h18.login() && $.ajax({
			url: "/index.php/user/ajax_collection",
			dataType: "json",
			data: {
				ac: "get",
				id: o.id
			},
			success: function(data) {
				if(data.code == 1) {
					is_collection = true;
					$("#collection span").text("已收藏");
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