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
		if(!o.loading){
			return false;
		}
		$("#rchange").addClass('ref360');
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
				if(o.page == data.pagecount){
					o.page = 1;
				}else{
					o.page++;
				}
				$("#loading").hide();
				$("#rchange").removeClass('ref360');
				o.loading = true;
			}
		});
	}

	function successFn(data) {

		var items = data.list,
			str = "";

		for(var i = 0; i < items.length; i++) {
			str += `
				<li style="width: 49.5%;">
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

		$(o.e).html(str);
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
					$("#collection").addClass('active').text("已收藏");		
				}
			}
		});
	}
	get_collection();

	/**
	 * 获取影片 的 上一部，下一部
	 */
	function get_front_after() {
		$.ajax({
			url: "/vod/ajax_front_after",
			dataType: "json",
			data: {
				id: o.id
			},
			success: function(data) {
				if(data.after != '') {
					$("#front_after").attr('href', "/vod/detail/id/" + data.after);
				} else if(data.front != '') {
					$("#front_after").attr('href', "/vod/detail/id/" + data.front).val("上一部");
				}
			}
		});
	}
	get_front_after();

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
		$(this).addClass('active');
	})
	$(".j-down").click(function() {
		h18.login(true);
		if(!is_down) {
			up_down(this, 6);
			is_down = true;
		} else {
			tips("已踩");
		}
		$(this).addClass('active');
	});

	/**
	 * 获取状态
	 */
	function get_up_down() {
		h18.login() && $.ajax({
			url: "/index.php/user/ajax_up_down",
			dataType: "json",
			data: {
				ac: "get",
				id: o.id
			},
			success: function(data) {
				if(data.up > 0) {
					is_up = true;
					$(".j-up").addClass('active');
				}
				if(data.down > 0) {
					is_down = true;
					$(".j-down").addClass('active');
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
			url: "/index.php/user/ajax_up_down",
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
		layer.open({
			content: msg,
			skin: 'msg',
			time: 2
		});
	}

	/**
	 * 换一批
	 */
	$("#rchange").click(function(){		
		get_list();
	})
	get_list();
	
	var browsevod = Number($.cookie('h18_browsevod'));
	if((h18.userid) ==0 && browsevod > 0){
		vod_disable();
	}
	else{
		setTimeout(function() {
			$.ajax({
				type:"post",
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
	
	
	function vod_disable(status){
		$("#video-container").html("");
		//询问框
		layer.open({
			content: '需要升级VIP才能继续观看',
			btn: ['升级', (h18.userid) ==0 ? '登录':'不要'],
			yes: function(index) {
				//升级
				if(h18.userid > 0){
					location.href = "/user/vip";
				}else{
					location.href = "/user/index";
				}
			},
			no: function() {
				if((h18.userid) == 0){
					location.href = "/user/login";	
				}else{
					location.href = "/user/index";
				}								
			}
		});
	}
	

	

}