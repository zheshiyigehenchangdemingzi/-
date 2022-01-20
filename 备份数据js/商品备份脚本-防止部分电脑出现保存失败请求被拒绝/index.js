// 拿取表格中填写的规格值
function attr_getAttrList(){
	var attrListTbleData = {
		
	};
	// thead 数据获取
	var trhs = $("#tableAttr thead th");
	var heads = [];
	for(var i = 0; i < trhs.length;i++){
		heads.push($(trhs[i]).text().trim());
	}
	attrListTbleData.heads = heads;
	// tbody 数据获取
	var trs = $("#tableAttr tbody tr")
	var tbodys = [];
	for(var i = 0;i < trs.length;i++){
		var tds =  $(trs[i]).find("td")
		var list = [];
		for(var j = 0; j < tds.length;j++){
			var inputs =  $(tds[j]).find('input')
			if(j == tds.length - 2){
				list.push($(inputs[1]).val())
				break;
			}else{
				var val = $(inputs).val()
				list.push(val ? val : 0);
			}
		}
		tbodys.push(list);
	}
	attrListTbleData.tbodys = tbodys;
	return attrListTbleData;
}



// 生成表格规格值
function attr_createTbody(attrList){
	var input_keys = [
		'cost_price[]',
		'price[]',
		'discount_price[]',
		'live_price[]',
		'stock[]',
		'integral[]'
	];
	
	// 表头 生成 规格值
	var thedhtml = `<tr>`;
	for(var i = 0; i < attrList.heads.length;i++)thedhtml += `<th>${attrList.heads[i]}</th>`;
	$("#tableAttr thead").html(thedhtml+'</tr>');
	
	// 表体  生成数据
	var bodyhtml = ``;
	for(var i = 0; i < attrList.tbodys.length;i++){
		bodyhtml += `<tr>`;
		// 算出 是从第几个开始 统一的渲染变量
		// 最低也得有 8个 
		var stratLen = attrList.tbodys[i].length - 7;
		for(var j = 0; j < attrList.tbodys[i].length;j++){
			if(j < stratLen){
				bodyhtml += `<td><input data-fname="${attrList.tbodys[i][j]}" disabled="disabled" type="text" name="guiGeTableName[]" class="layui-input" value="${attrList.tbodys[i][j]}"></td>`;
			}else if(j == attrList.tbodys[i].length - 1){
				bodyhtml += `
				<td>
									<div class="layui-upload">
										<button type="button" class="layui-btn" id="uploadsImgs0">上传图片</button><input class="layui-upload-file" type="file" accept="" name="file">
										<div class="layui-upload-list">
											<img class="layui-upload-img" src="${attrList.tbodys[i][j]}">
											<p></p>
											<input type="hidden" name="img[]" value="${attrList.tbodys[i][j]}">
										</div>
									</div>
								</td>
								<td style="text-align: center;"><a href="javascript:;" onclick="attr_del(this)"><i class="iconfont"></i>删除</a></td></tr>`;
								break;
			}else{
				bodyhtml += `<td><input type="text" name="${input_keys[ j - stratLen]}" class="layui-input" value=${attrList.tbodys[i][j]}></td>`;
			}
		}
	}
	$("#tableAttr tbody").html(bodyhtml);
}
	
createTbody(attrList);


