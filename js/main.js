_decimal_places = 1;

function bind_buttons() {
	$("input[type=button]").unbind().click(function() {
		if ($(this).hasClass('switch-button'))
		{
		    var switchto = $(this).attr('switchto');
		    var val = $(this).val();
		    $(this).attr('switchto',val);
		    $(this).attr('value',switchto);
		    $(this).toggleClass('sel');   
		    return; 
		}
		
		var func = $(this).attr('function');
		var var1 = $(this).attr('var1');
		var var2 = $(this).attr('var2');
		var var3 = $(this).attr('var3');
		var obj = $(this).attr('obj');

		if (var1 == 'this') 
		{
			var1 = $(this);
		}

		var object = 'buttons';
		if (obj) { object = obj; }

		window[object][func](var1, var2, var3);
	});
}

function debug()
{
	return $("#debug-button").hasClass('sel');
}

function round(num, places) 
{
	num = parseFloat(num);
	if (typeof places == 'undefined') 
	{
		places = _decimal_places;
	}
	if (num < 0.05) 
	{
		return num.toPrecision(2);
	}
	num = +(num).toFixed(places)
	return num;
}

function unit(field) 
{
	if (!field_names[field]) { return ''; }
	if (!field_names[field][1]) { return ''; }
	
	var field_name = field_names[field][1];
	if (field_name == 'mcg') 
	{
		field_name = 'μg';
	}
	return '<span class="unit">'+field_name+'</span>';
}

function rdi(field, value) 
{	
	if (field.indexOf('/') != -1) { return ''; }
	var rdi = parseInt($("#rdi-"+field).val());
	if (!rdi || isNaN(rdi)) { return ''; }
	
	return Math.round((value / rdi) * 100) + '%';
}

function ajax(func, data, obj) 
{
	data['ajax'] = func;
	data['debug'] = debug();
	$.ajax({
		type : "POST",
		url : "",
		data : data,
		success : function(e) {
			var message = '';
			if (debug()) 
			{
				$("#debug").html(e);
				$("#debug").show();
			}
			e = JSON.parse(e);

			message = window[obj][func](e);

			$("#message").html(message);
		}
	});
}

function bind_all(str) 
{
	str = str.split(',');
	for (var i in str) 
	{
		window[str[i]]['bind']();
	}
}

function format_field(str) 
{
	if (str == 'name') { return 'Name'; }
	else if (field_names[str]) 
	{
		return field_names[str][0];
	}
	else if (str.indexOf('/') != -1)
	{
		// Format ratios
		str = str.replace(/[()`]/g,'');
		str = str.split('/');
		str = '('+field_names[str[0]][0]+'/'+field_names[str[1]][0]+')';
		return str;
	}
}

var controls = 
{
	toggle_menu : function(name) {
		if ($("#menu-"+name).is(':hidden'))
		{
			this.show_menu(name);
		}
		else
		{
			this.hide_menu(name);
		}
	},
	show_menu: function(name) {
		$("#toggle-" + name).find('span').html('[ - ]');
		$("#menu-" + name).show();
	},
	hide_menu: function(name) {
		$("#toggle-" + name).find('span').html('[ + ]');
		$("#menu-" + name).hide();
	},
	hide_fields : function(fields) {
		$("#menu-fields li").show();
		for (var i in fields) 
		{
			$("#field-" + fields[i]).hide();
		}
	},
	
	pane_obj: '',
	pane_func: '',
	hide_all_panes: function() {
		$("#panes > div").hide();
	},
	switch_pane : function(pane,func) {
		func = func.split('.');
		this.pane_obj = func[0];
		this.pane_func = func[1];
		
		this.hide_all_panes();
		$("#" + pane + '-pane').show();
	},
	reload_pane: function() {
		if (!this.pane_obj || !this.pane_func) { return; }
		window[this.pane_obj][this.pane_func]();
	},
	render_pane: function(pane,func) {
		this.switch_pane(pane,func);
		this.reload_pane();
	}
};

var ratios = {
	ratio_sort: false,
	bind : function() {
		bind_buttons();
		$(".ratio-wrapper select").unbind().change(function() {
			ratios.render_filters();
		});
	},
	reset: function() {
		$("#field-ratios .ratio-wrapper").remove();
		this.hide_rank();
	},
	show_rank : function() {
		$("#ratio-rank-wrapper").show();
	},
	hide_rank : function() {
		$("#ratio-rank-wrapper").hide();
		$("#ratio-rank-wrapper select").val('');
		this.render_filters();
	},
	add_field : function() {
		var el = $("#ratio-field-template").clone();
		el.appendTo('#field-ratios');
		el.show();
		el.attr('id', '');
		this.bind();
		this.render_filters();
	},
	remove_field : function(el) {
		el.closest('.ratio-wrapper').remove();
		this.render_filters();
	},
	render_filters : function() {;
		$(".field-select option.ratio").each(function() {
			if ($(this).is(':selected')) { return; }
			$(this).remove();	
		});
		
		$(".ratio-wrapper").each(function() {
			var num = $(this).find('.ratio-num > select');
			var denom = $(this).find('.ratio-denom > select');

			num = {
				val : num.val(),
				name : num.find('option:selected').html()
			};
			denom = {
				val : denom.val(),
				name : denom.find('option:selected').html()
			};

			if (!num.val || !denom.val) { return; }

			var html = '<option class="ratio" value="ratio__' + num.val + '__' + denom.val + '">( ' + num.name + ' / ' + denom.name + ' )</option>';

			$(".field-select").append($(html));
		});
	}
};

var results = {
	bind : function() {
		$("#results a").unbind().click(function(e) {
			e.preventDefault();
			nutrition.get_info($(this).attr('foodid'));
		});
	},
	reset : function() {
		$("#results-title").hide();
		$("#results").html('');
	},
	refresh: function() {
		controls.switch_pane('results','db.send_form');
	},
	row: function(tag, arr, format) {
		var html, value, pdv, food_name;
		var foodid = -1;
		for (var i in arr) 
		{
			value = arr[i];
			if (i == 'id' || arr[i] == 'id') { continue; }
			if (i == 'name') 
			{
				value = '<a href="" foodid="' + arr['id'] + '">' + value + '</a>';
			}
			if (typeof format != 'undefined') 
			{
				value = format_field(value);
			}
			if (!isNaN(parseFloat(value))) 
			{
				value = nutrition.value(i,value);
			}
			html += '<' + tag + '>' + value + '</' + tag + '>';
		}
		
		if (arr['id'])
		{
			foodid = arr['id'];
		}
		
		if (arr['name'])
		{
			food_name = arr['name'];
		}
		
		$("#results").append('<tr foodid="'+foodid+'" food_name="'+food_name+'">' + html + '</tr>');
	},
	set_head : function(arr) {
		this.reset();
		this.row('th', arr, true);
	},
	populate : function(arr) {
		for (var a in arr) 
		{
			this.row('td', arr[a]);
		}
		this.bind();
	},
	paginate: function(amt) {
		if (amt == 0)
		{
			$(".pagination").hide();
			return;
		}
		
		$(".prev-page,.next-page").show();
		
		var min = db.offset;
		if (min == 0)
		{
			$(".prev-page").hide();
		}
		
		var max = db.offset + db.entries;
		if (max >= amt) 
		{
			$(".next-page").hide(); 
			max = amt; 
		}
		$(".pagination-showing").html(amt+' entries found. Showing '+min+'-'+max);
		$(".pagination").show();
		
	}
};

var compare = {
	on: false,
	data: {
	},
	pane: function() {
		controls.render_pane('compare','compare.compare_tool');
	},
	bind: function() {
		$("#compare-list > div > a").unbind().click(function(e) {
			e.preventDefault();
			compare.remove($(this).attr('id'));
			$(this).parent().fadeOut('fast');
			controls.reload_pane();
		});
	},
	init: function(id,name) {
		this.on = true;
		$("#toggle-compare").show();
		controls.show_menu('compare');
		results.refresh();
		controls.reload_pane();
		this.add(id,name);
	},
	reset: function() {
		this.on = false;
		this.data = {};
		$("#compare-list").html('');
		$("#toggle-compare").hide();
		controls.hide_menu('compare');
		if (!$("#compare-pane").is(':hidden') || !$("#graph-pane").is(':hidden'))
		{
			results.refresh();
			
		}
		controls.reload_pane();
	},
	
	// --
	locked: function() {
		return '<div class="comparing">Comparing</div>';
	},
	column: function() {
		if (!this.on) { return; }
		$("#results tr").each(function() {
			var foodid = $(this).attr('foodid');
			var name = $(this).attr('food_name');
			if (foodid == -1)
			{
				$(this).prepend('<th class="top-left">Compare</th>');
				return;
			}

			var html = '<td class="compare">';
			if (!compare.data[foodid])
			{
				html += '<input type="button" obj="compare" function="compare_button" var1="this" var2="'+foodid+'" var3="'+name+'" value="Compare" />';
			}
			else
			{
				html += compare.locked();
			}
			html += '</td>';
			
			$(this).prepend(html);
		});
		bind_buttons();
	},
	compare_button: function(el,foodid,name) {
		el.replaceWith(this.locked());
		this.add(foodid,name);
	},
	back: function() {
		controls.show_menu('compare');
		results.refresh();
	},
	compare_tool: function() {
		controls.hide_menu('compare');
		ajax('compare_foods', { data: this.data },'compare');
	},
	rdis: {
	},
	set_rdi: function(id,field,value) {
		var pdv = rdi(field,value);
		if (!pdv) { return; }
		pdv = pdv.replace('%','');
		
		var field_name = field_names[field][0];
		
		if (typeof (this.rdis[field_name]) == 'undefined')
		{
			this.rdis[field_name] = {};
		}
		
		this.rdis[field_name][id] = parseInt(pdv);
	},
	compare_foods: function(data) {
		this.rdis = {};
		
		names = data['names'];
		fields = data['fields'];
		ids = data['ids'];
		
		data = data['data'];
		
		var html = '<tr><th class="top-left"></th>';
		for (var i in names)
		{
			html += '<th>'+names[i]+'</th>';
		}
		html += '</tr>';
		var field, value;
		for (var a in fields)
		{
			field = fields[a];
			if (!field_names[field]) { continue; }
			html += '<tr><td class="compare">'+field_names[field][0]+'</td>';
			for (var id in ids) 
			{
				value = data[ids[id]][field];
				this.set_rdi(ids[id],field,value);
				html += '<td>'+nutrition.value(field,value)+'</td>';
			}
			html += '</tr>';
		}
		$("#compare-table").html($(html));
	},
	
	// -- 
	fix_menu_buttons: function() {
		var length = Object.keys(this.data).length;
		if (length > 1)
		{
			$("#compare-compare").show();
		}
		else
		{
			$("#compare-compare").hide();
			if (length == 0)
			{
				this.reset();
			}
		}
	},
	add: function(id,name) {
		this.data[id] = name;
		$("#compare-list").append($("<div><span>"+name+'</span><a href="" id="'+id+'">X</a></div>'));
		this.bind();
		this.fix_menu_buttons();
	},
	remove: function(id) {
		delete compare.data[id];
		this.fix_menu_buttons();
	}
};

var nutrition = {
	value: function(field,value) {
		pdv = rdi(field, value);
		value = round(value);
		if (unit(field)) 
		{ 
			value += unit(field);
		}
		
		if (!this.show('val') && pdv)
		{
			value = '';
		}
		
		if (pdv && this.show('rdi')) 
		{
			if (this.show('val'))
			{
				value += ' (';
			}
			value += pdv; 
			if (this.show('val'))
			{
				value += ')';
			}
		}
		return value;
	},
	get_info : function(id) {
		ajax('get_nutrition', { data : { id : id }}, 'nutrition');
	},
	get_nutrition : function(e) {
		this.hide_custom_weight();
		$("#nutrition").html('');
		$("#nutrition-title").html(e.data['name']);
		if (!compare.data[e.id])
		{
			$("#nut-compare-button").show();
			$("#nut-comparing").hide();
			$("#nut-compare-button").attr('var1',e.id);
			$("#nut-compare-button").attr('var2',e.data['name']);
		}
		else
		{
			$("#nut-compare-button").hide();
			$("#nut-comparing").show();
		}
		
		controls.switch_pane('nutrition','nutrition.change_weight');

		for (var i in e.data) 
		{
			if (!field_names[i]) { continue; }
			
			$("#nutrition").append('<tr>'+
			'<td>'+field_names[i][0]+'</td>'+ 
			'<td class="nut-val" base="'+e.data[i]+'" field="'+i+'"></td>'+
			'<td class="nut-rdi" base="'+e.data[i]+'" field="'+i+'"></td>'+
			'</tr>');
			this.scale_values(100);
		}

		var data, amt, weight, name, unit_base;
		$("#weights").html('<option value="100" amt="100" unit_base="1" selected="selected">100 grams</option>');
		$("#weight-custom-select").html('<option value="1">grams</option>')
		for (var i in e.weights) 
		{
			data = e.weights[i];
			amt = round(data['amt'], 0);
			name = data['name'];
			weight = Math.round(data['weight']);
			unit_base = weight / amt;

			$("#weights").append(
				'<option value="'+data['weight']+'"amt="'+amt+'"unit_base="'+unit_base+'">'+
				amt+' '+name+' ('+weight+'g)</option>'
			);
			$("#weight-custom-select").append('<option value="' + unit_base + '">' + name + '</option>');
		}
	},
	show_custom_weight : function() {
		$("#custom-weight-button").hide();
		var sel = $("#weights option:selected");

		$("#weights").hide();
		$("#weight-custom").show();
		$("#weight-custom-input").val(sel.attr('amt'));
		$("#weight-custom-select").val(sel.attr('unit_base'));
	},
	hide_custom_weight : function() {
		$("#weight-custom").hide();
		$("#custom-weight-button").show();
		$("#weights").show();
	},
	change_weight: function() {
		if ($("#weight-custom").is(':hidden'))
		{
			var factor = parseFloat($("#weights").val());
		}
		else
		{
			amt = $("#weight-custom-input").val();
			grams = $("#weight-custom-select").val();
			var factor = amt * grams;
		}
		nutrition.scale_values(factor);
	},
	scale_values : function(factor) {
		$("#nutrition td[base]").each(function() {
			$(this).show();
			var one_gram = parseFloat($(this).attr('base')) / 100;
			var amt = one_gram*factor;
			var field = $(this).attr('field');
			var html = '';
			if ($(this).hasClass('nut-rdi'))
			{
				html += rdi(field,amt);
			}
			else
			{
				html += round(amt) + unit(field);
			}
			$(this).html(html);
			
			// If RDI shouldn't show, hide the RDI column.
			if (!nutrition.show('rdi') && $(this).hasClass('nut-rdi'))
			{
				$(this).hide();
			}
			
			// If value shouldn't show, and the RDI exists, show only the RDI colum.
			if (!nutrition.show('val') && rdi(field,amt) && $(this).hasClass('nut-val'))
			{
				$(this).hide();	
			}
			
			// If value shouldn't show, but the RDI doesn't exist, hide the RDI field instead.
			if (!nutrition.show('val') && $(this).hasClass('nut-rdi') && !rdi(field,amt))
			{
				$(this).hide();
			}
		});
	},
	show: function(type) {
		var val = $("#nutrient-format").val();
		if (val == type || val == 'both')
		{
			return true;
		}
		return false;
	}
};

var db = {
	offset : 0,
	entries : 10,

	send_form : function(reset_offset) { 
		if (reset_offset) 
		{
			this.offset = 0;
		}
		var data = $("#menu-form").serialize();
		controls.switch_pane('results','db.send_form');

		ajax('get_results', {
			data : data,
			offset : db.offset,
			entries : db.entries
		}, 'db');
	},
	get_results : function(e) {
		if ( typeof e.results != 'undefined') 
		{
			results.paginate(e.count);
			results.set_head(e.keys);
			results.populate(e.results);
			compare.column();
		} 
		else if (e.error == 'no_results') 
		{
			results.paginate(0);
			message = 'No Results Found';
			results.reset();
		}
		$("#results-title").show();
		return message;
	},
	next_page : function() {
		this.offset += this.entries;
		this.send_form(false);
	},
	prev_page : function() {
		this.offset -= this.entries;
		if (this.offset < 0) { this.offset = 0; }
		
		this.send_form(false);
	}
};

var buttons = {
	select_all : function(name) {
		$("#" + name + " input").prop('checked', true);
	},
	unselect_all : function(name) {
		$("#" + name + " input").prop('checked', false);
	},
	reset_all : function() {
		$("#message").hide();
		$("#menu-form").trigger('reset');
		compare.reset();
		ratios.reset();
		controls.hide_all_panes();
		filter.remove_all_rows();
	},
	reset_checkboxes : function(id) {
		$("#"+id+" :checkbox").each(function(i,item){ 
        	this.checked = item.defaultChecked; 
		});
	},
	reset_inputs: function(id) {
		$("#"+id+" input").each(function() {
			if (!$(this).attr('default-value')) { return; }
			$(this).val($(this).attr('default-value'));
		});
	},
	select_custom_fields : function(fields) {
		fields = fields.split(',');
		for (var i in fields) 
		{
			$("#field-" + fields[i] + ' input').prop('checked', true);
		}
	}
};

var filter = {
	bind : function() {
		bind_buttons();
		$(".filter-type select").unbind().change(function() {
			var val = $(this).val();
			var row = $(this).closest('tr');
			if (val == 'notzero') 
			{
				filter.hide_amt(row);
			} 
			else {
				filter.show_amt(row);
			}
		});
	},
	hide_amt : function(row) {
		row.find('.filter-amt input').hide();
	},
	show_amt : function(row) {
		row.find('.filter-amt input').show();
	},
	fix_table: function() {
		var amt = $("#filters tr").length;
		if (amt <= 1)
		{
			$("#filters").hide();
		}
		else
		{
			$("#filters").show();
		}
		
	},
	remove_row : function(el) {
		el.closest('tr').remove();
		this.fix_table();
	},
	remove_all_rows : function() {
		$("#filters tr").each(function() {
			if ($(this).attr('id') == 'filter-template') 
			{ 
				return; 
			}
			$(this).remove();
		});
		this.fix_table();
	},
	add_again : function() {
		this.add_row($(".add-filter").val());
	},
	add_row : function(val) {
		if (!val) 
		{
			$("#add-again").hide();
			return;
		}
		$("#add-again").show();

		var i = $("#filters tr").length;
		var html = $("#filter-template").html();
		html = html.replace(/\$/g, i);
		$("#filters").append($('<tr id="filter-id-' + i + '">' + html + "</tr>"));
		$("#filter-id-" + i).find('.filter-field select').val(val);
		filter.bind();
		this.fix_table();
	}
};

var graphs = {
	pane: function() {
		controls.render_pane('graph','graphs.render');
	},
	render: function() {
		var titles = [''];
		var data = [];
		for (var id in compare.data)
		{
			titles.push(compare.data[id]);
		}
		
		var row;
		for (var field in compare.rdis)
		{
			row = [field];
			for (var id in compare.data)
			{
				row.push(compare.rdis[field][id]/100);	
			}
			data.push(row);
		}
		
		this.graph(titles,data,8);
	},
	graph: function(title,data,per_unit) 
	{
		var draw_data = {};
		
		for (var i = 0; i < data.length; i+=per_unit)
		{
			draw_data[(i/per_unit)] = data.slice(i,i+per_unit);
		}
		
		google.charts.load('current', {'packages':['bar']});
		google.charts.setOnLoadCallback(function() 
		{
			$("#graphs").html('');
			for (var i = 0; i < data.length; i++)
			{
				$("#graphs").append('<div id="graph'+i+'"></div>');
				graphs.draw('graph'+i,title,draw_data[i]);
			}
		});
	},
	draw: function(el,title,data) {   
		if (typeof data == 'undefined') { return; }
		
		var draw_data = data.slice(0);
		draw_data.unshift(title);
	    
	    data = google.visualization.arrayToDataTable(draw_data);
	
		var max = 'auto';
		
		// If there are some really high nutrient values, max out at 100% instead
		for (var a in draw_data)
		{
			for (var b in draw_data[a])
			{
				if (b == 0) { continue; }
				if (draw_data[a][b] > 1.5)
				{
					max = 1;
					break;
				}
			}
		}
	
		var options = {
			title: '',
			vAxis: {
			  	format: 'percent',
			  	viewWindow: { 
			  		max: max 
		  		},
		  		legend: {
		  			position: 'none'
		  		}
		  	}
		};
		
		var chart = new google.charts.Bar(document.getElementById(el));
			
		chart.draw(data, google.charts.Bar.convertOptions(options));
	}
};

$("#weights,#weight-custom > *").change(function() {
	nutrition.change_weight();
});

$("#nutrient-format").change(function() {
	controls.reload_pane();
});

$(".menu-wrapper > a").click(function(e) {
	e.preventDefault();
	controls.toggle_menu($(this).attr('id').replace('toggle-', ''));
});

$("#menu-form").submit(function(e) {
	e.preventDefault();
	db.send_form(true);
});

$("#menu-rank input[name=rank]").change(function(e) {
	console.log(e);
	var val = $("#menu-rank input[name=rank]:checked").val();
	if (val == 'ratio') 
	{
		ratios.ratio_sort = true;
		ratios.show_rank();
		return;
	} 
	else if (ratios.ratio_sort == true)
	{
		ratios.ratio_sort = false;
		ratios.hide_rank();
	}
	controls.hide_fields([val]);
});


$("#debug").click(function() {
	$(this).html('');
	$(this).hide();
});

$(".add-filter").change(function() {
	filter.add_row($(this).val());

});

$(".footnote").click(function(e) {
	e.preventDefault();
	var li = $(this).closest('li');
	if ($(this).html().indexOf('hide') != -1)
	{
		$(this).removeClass('hide-note');
		$(this).html('[note]');
		li.next().slideUp();
		return;
	}
	
	$(".hide-note").each(function() {
		$(this).removeClass('hide-note');
		$(this).html('[note]');
	});
	
	$(".footnote-note").remove();
	var field = $(this).attr('field');
	var el = $('<li class="footnote-note" style="display: none;"><div>'+field_names[field][3]+'</div></li>');
	li.after(el);
	el.slideDown('fast');
	$(this).html('[hide note]');
	$(this).addClass('hide-note');
});

$(".footnote-note").click(function() {
	$(this).slideUp('fast');
});

bind_all('results,ratios,filter'); 