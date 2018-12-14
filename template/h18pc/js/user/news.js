function pageInit(options) {

	var settings = {
		e: "#nlist",
		loading: true,
		url: "/user/news",
		page: 1,
		limit: 20,
		pagecount: 1,
		id: 0,
		tid: 0,
		fn: successFn
	};
	var o = $.extend(settings, options);

	/**
	 * 获取列表
	 */
	function get_list() {
		layer.load();
		$.ajax({
			url: o.url,
			dataType: "json",
			data: {
				page: o.page,
				limit: o.limit
			},
			success: function(data) {

				o.pagecount = data.pagecount;
				if(o.fn && $.isFunction(o.fn)) {
					o.fn(data);
					paging();
					o.page++;
				}

				layer.closeAll();

			}
		});
	}
	get_list();

	function successFn(data) {

		var items = data.list,
			str = "";

		for(var i = 0; i < items.length; i++) {
			str += `
				<li>
					<div data-id="${items[i].id}" class="tt ${items[i].status > 0 ? 'already' : ''}">
						<span class="l"><b>${items[i].status > 0 ? '已读' : '未读'}</b>${items[i].title}</span>
						<span class="r">${date('Y-m-d',items[i].send_time)}</span>
					</div>
					<div class="con hide">
						<p>
							${items[i].introduce}
						</p>
						<p class="name">
							${items[i].send_name}
							<br/>
							${date('Y-m-d H:m',items[i].send_time)}
						</p>
						<span class="up"></span>
					</div>								
				</li>`;
		}

		$(o.e).html(str);

	}

	function paging() {
		
		if(o.pagecount == 1){
			$("#paging").html("");
			return false;
		}
		
		var str = "",maxcount = (o.pagecount > 10 ? 10 : o.pagecount);
		for(var i = 1; i <= maxcount; i++) {
			if(o.page == i) {
				str += '<a class="page_link page_current">' + i + '</a>';
			} else {
				str += '<a class="page_link" href="javascript:;" data-id="' + i + '">' + i + '</a>';
			}
		}

		var sss = `<div class="mac_pages">
		     <div class="page_info">${str}<span>&nbsp;&nbsp;第${o.page}页/共${maxcount}页</span></div>
		</div>`;

		$("#paging").html(sss);

	}

	//点击分页
	$("#paging").on("click", ".page_link", function() {
		if($(this).data('id')){
			o.page = $(this).data('id');
			get_list();
		}
	})

	//展开
	$("#nlist").on("click", ".tt", function() {
		$(this).next().toggleClass('hide');
		if(!$(this).hasClass('already')){
			$(this).addClass('already').find('b').text('已读');
			
			already($(this).data('id'));			
		}
	})



	/**
	 * 收藏
	 */
	function already(id) {
		$.ajax({
			url: "/user/news",
			dataType: "json",
			data: {
				ac: "already",
				id: id
			}
		});
	}

}