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
					
				if(data.pagecount > data.page){
					o.page++;
					o.loading = true;
				}					
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
			str += `
			<li data-id="${items[i].id}" class="item ${items[i].status > 0 ? 'already' : ''}">
				<p class="tit">${items[i].title}</p>
				<div class="more">
					<div class="hd">${items[i].title}</div>
					<div class="bd">${items[i].introduce}</div>
					<div class="ft">
						<p>${items[i].send_name}</p>
						<p>${date('Y-m-d H:m',items[i].send_time)}</p>
					</div>
				</div>
			</li>`;
		}

		$(o.e).append(str);

	}

	

	//展开
	$("#nlist").on("click", ".item", function() {
		$(this).toggleClass('open');
		if(!$(this).hasClass('already')) {
			$(this).addClass('already');

			already($(this).data('id'));
		}
	})
	
	/**
	 * 已读
	 * @param {Object} id
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