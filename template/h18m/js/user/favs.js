function pageInit(options) {

	$("#loading").hide();
	var settings = {
		e: "#vlist",
		loading: true,
		url: "/ajax/get_favs",
		page: 2,
		limit: 12,
		id: 0,
		tid: 0,
		tpl:"",
		fn: successFn
	};
	var o = $.extend(settings, options);
	
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
			str += `
			<li class="weui-flex">
				<div class="pic weui-flex__item">
					<a href="/vod/detail/id/${items[i].data.id}">
						<img src="${items[i].data.pic}" />
					</a>
				</div>
				<div class="intro weui-flex__item ">	
					<div class="vmiddle">
						<div class="hh"><a href="/vod/detail/id/${items[i].data.id}">${items[i].data.name}
							</a>								
						</div>
						<div class="bb">
							<span class="add weui-flex__item"><i class="iconfont icon-riqi"></i>&nbsp;${date('Y-m-d',items[i].vod_time_add)}</span>
						<span class="weui-flex__item"><i class="iconfont icon-shijian"></i>&nbsp;${duration_to_time(items[i].vod_duration)}</span>								
						</div>
						<div class="ff weui-flex ff-mt">
							<span class="weui-flex__item"><i class="iconfont icon-yanjing"></i>&nbsp;${items[i].data.hits}次</span>
							<span class="weui-flex__item btn">
								<a class="delete" data-id="${items[i].ulog_id}">
									${retTxt()}
								</a>
							</span>
						</div>
					</div>						
				</div>
			</li>
			`;
		}

		$(o.e).append(str);
	}

	function retTxt(){
		if(o.tpl == "browse"){
			return '删除记录';
		}else{
			return '取消收藏';
		}
	}

	$("#vlist").on("click", ".delete", function() {	
		var obj = this,id = $(this).data('id');
		layer.open({
			content: '您确定要删除吗？',
			btn: ['确定', '取消'],
			yes: function(index) {				
				layer.close(index);
				
				if(o.tpl == "browse"){
					delBrowse(id);
				}else{
					delCollection(id);
				}
				
				
				$(obj).parents('li').fadeOut("slow");
				
				var  total = $.trim(Number($('#videos_total').text()));
				$('#videos_total').text( total - 1);
				
			}
		});
	})

	
	/**
	 * 收藏
	 */
	function delCollection(id) {
		$.ajax({
			url: "/index.php/user/ajax_collection",
			dataType: "json",
			data: {
				ac: "del",
				id: id,
				type: 2
			}
		});
	}
	
	
	/**
	 * 删除浏览记录
	 */
	function delBrowse(id) {
		$.ajax({
			url: "/index.php/user/ajax_browse",
			dataType: "json",
			data: {
				ac: "del",
				id: id,
				type: 2
			}
		});
	}

}