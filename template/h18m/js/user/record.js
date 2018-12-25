function pageInit(options) {

	$("#loading").hide();
	var settings = {
		e: "#vlist",
		loading: true,
		url: "/proxy/brokerage_record",
		page: 1,
		limit: 20,
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
	get_list();
	function successFn(data) {

		var items = data.list,
			str = "";

		for(var i = 0; i < items.length; i++) {
			str +=`
			<tr>
				<td>${date('Y-m-d H:i',items[i].add_time)}</td>
				<td>${items[i].type == 1 ? '-' : '+'}${items[i].amount}</td>
				<td>${items[i].after_amount}</td>
				<td>${items[i].type_format}</td>
				<td><span class="${format_status(items[i].status)}">${items[i].status_format}</span></td>
			</tr>
			`;
		}

		$(o.e).append(str);
	}
	
	function format_status(status){
		
		switch (status){
			case 1:
				return 's1';
			case 2:
				return 's2';			
		}
		return 's0';
		
		
	}

	
	

}