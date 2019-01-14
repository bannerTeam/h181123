function pageInit(options) {

	var settings = {
		e: "#vlist",
		loading: true,
		url: "",
		page: 1,
		limit: 10,
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
		
		if(items.length == 0){
			str = '<tr><td colspan="5" class="empty">没有符合条件数据</td></tr>';
			$(o.e).html(str);
			return true;
		}

		for(var i = 0; i < items.length; i++) {
			str += `
			<tr>
				<td>${date('Y-m-d H:i',items[i].add_time)}</td>
				<td>${items[i].type == 1 ? '-' : '+'}${items[i].amount}</td>
				<td>${items[i].after_amount}</td>
				<td>${items[i].type_format}</td>
				<td><span class="${format_status(items[i].status)}">${items[i].status_format}</span></td>
			</tr>`;
		}

		$(o.e).html(str);

	}

	function paging() {
		
		if(o.pagecount <= 1){
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

	


}