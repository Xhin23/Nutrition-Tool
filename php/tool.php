<?php

$fields = $db->get_fields();

function footnote($field) 
{
    if ($field['footnote'])
    {
        return '<a class="footnote" href="" field="'.$field['field'].'">[note]</a>';
    }
}

foreach ($fields?:Array() AS $key => $field)
{
    $footnote = $db->field_names[$field['field']][3];
    if (!$footnote)
    {
        continue;
    }
    $fields[$key]['footnote'] = true; 
    if (has($footnote,'*'))
    {
        $footnote = str_replace('*','',$footnote);
        $fields[$key]['label'] = '*';
    }
}

$cats = $db->get_cats();

$filter_fields = pair($fields,'field');

$filter_types = Array(
    'notzero' => '!= 0',
    'gt' => '>',
    'lt' => '<',
    'eq' => '='
);

$default_groups = Array(100,200,400,500,700,900,1000,1100,1200,1300,1500,1600,1700,2000);



?>

<head>
    <link rel="stylesheet" type="text/css" href="css/main.css" />
</head>
<body>
<h1>Nutrition Tool</h1>

<form action="" method="POST" id="menu-form">
<div class="menu-wrapper">

<a href="" id="toggle-fields">Fields <span>[ + ]</span></a>
<div id="menu-fields" class="menu" style="display: none;">
    <div class="buttons-row">
        <input type="button" function="select_all" var1="fields" value=" Select All " />
        <input type="button" function="unselect_all" var1="fields" value=" Unselect All " />
        <input type="button" function="select_custom_fields" var1="protein,fat,net_carbs,alcohol" value=" Select Macronutrients " />
        <input type="button" function="select_custom_fields" var1="calcium,iron,magnesium,phosphorus,potassium,sodium,zinc,copper,manganese,selenium,vit_a_RAE,vit_e,vit_d,vit_c,thiamin,riboflavin,niacin,pantothenic_acid,vit_b6,folate,vit_b12,choline,vit_k1" value=" Select Vitamins/Minerals " />
    </div>
    <ul id="fields">
        <?php foreach ($fields?:Array() AS $field) { ?>
        <li id="field-<?=$field['field']?>">
            <label><input type="checkbox" name="fields[]" value="<?=$field['field']?>" /><?=$field['name']?><?=$field['label']?></label>
            <?=footnote($field); ?>
        </li>
        <?php } ?>  
    </ul>
    
   <div class="ratio-select" id="field-ratios">
        <div id="ratio-field-template" class="ratio-wrapper" style="display: none;">
            <div class="ratio-num"><?=fill_select('ratio_field_num[]',$filter_fields,'',true)?></div>
            <div class="ratio-denom"><?=fill_select('ratio_field_denom[]',$filter_fields,'',true)?></div>
            <input type="button" obj="ratios" function="remove_field" var1="this" value="Remove Ratio" />
       </div>
   </div>
   <input type="button" obj="ratios" function="add_field" value=" Add Ratio " />
</div>

<a href="" id="toggle-filters">Filters <span>[ + ]</span></a>
<div id="menu-filters" class="menu" style="display: none;">
    <span class="bigger">Add Filter:</span> 
    <?=fill_select('',$filter_fields,'',true,'add-filter field-select')?>
    <input type="button" id="add-again" style="display: none;" obj="filter" function="add_again" value=" Add Again " />
    <table id="filters" style="display: none;">
        <tr id="filter-template" style="display: none;">
            <td class="filter-field"><?=fill_select('filter_fields[$]',$filter_fields,'',true,'field-select')?></td>
            <td class="filter-type"><?=fill_Select('filter_types[$]',$filter_types,'notzero')?></td>
            <td class="filter-amt"><input name="filter_amts[$]" style="display: none;" /></td>
            <td class="filter-remove"><input type="button" obj="filter" function="remove_row" var1="this" value=" Remove " /></td>
        </tr>
    </table>
</div>

<a href="" id="toggle-cats">Categories <span>[ + ]</span></a>
<div id="menu-cats" class="menu" style="display: none;">
    <div class="buttons-row">
    <input type="button" function="select_all" var1="cats" value=" Select All " />
    <input type="button" function="unselect_all" var1="cats" value=" Unselect All " />
    <input type="button" function="reset_checkboxes" var1="cats" value=" Reset Categories " />
    </div>
    <ul id="cats">
       <?php foreach ($cats?:Array() AS $cat) { ?>
            <li><label><input type="checkbox" name="cats[]" value="<?=$cat['groupid']?>" <?php if (in_array($cat['groupid'],$default_groups)) { ?>checked="checked"<?php } ?> /><?=$cat['name']?></label></li>
       <?php } ?>    
    </ul>
</div>

<a href="" id="toggle-search">Search <span>[ + ]</span></a>
<div id="menu-search" class="menu" style="display: none;">
    <table>
    <tr><td class="contains-keywords">Contains these keywords</td><td><input name="search" /></td></tr>
    <tr><td class="filter-keywords">Filter these keywords</td><td><input name="filter" /></td></tr>
    </table>
</div>

<a href="" id="toggle-rank">Rank by <span>[ + ]</span></a>
<div id="menu-rank" class="menu" style="display: none;">
        <ul>
        <?php foreach ($fields?:Array() AS $field) { ?>
        <li>
            <label><input type="radio" name="rank" value="<?=$field['field']?>" /><?=$field['name']?><?=$field['label']?></label>
            <?=footnote($field); ?>
        </li>
        <?php } ?>  
    </ul>
   <ul class="no-cols">
   <li class="ratio-select">
       <label><input type="radio" name="rank" value="ratio" />Ratio</label>
       <div id="ratio-rank-wrapper" class="ratio-wrapper" style="display: none;">
           <div class="ratio-num"><?=fill_select('ratio_num',$filter_fields,'',true)?></div>
           <div class="ratio-denom"><?=fill_select('ratio_denom',$filter_fields,'',true)?></div>
       </div>
   </li>
   </ul>
   
    <div><select name="sort_order">
        <option value="desc" selected="selected">Sort: High to Low</option>
        <option value="asc">Sort: Low to High</option>
    </select></div>
</div>

<a href="" id="toggle-options">Options <span>[ + ]</span></a>
<div class="menu menu-wrapper" id="menu-options" style="display: none;">
    <div>
        <span class="bigger">Nutrient Format:</span> 
        <select id="nutrient-format">
            <option value="both" selected="selected">Both value and RDI</option>
            <option value="val">Only nutrient value</option>
            <option value="rdi">Only RDI</option>
        </select>
    </div>
    <a href="" id="toggle-rdi">Custom RDI Settings <span>[ + ]</span></a><br />
    <div id="menu-rdi" class="menu" style="display: none;">
        <table>
            <tr><th colspan="3" id="reset-rdi"><input type="button" function="reset_inputs" var1="menu-rdi" value=" Reset to Default "/></td></tr>
            <?php foreach ($fields?:Array() AS $field) { 
                $rdi = $db->field_names[$field['field']][2]?:'0';
                $unit = $db->field_names[$field['field']][1];
                if ($unit == 'mcg') { $unit = 'μg'; }
                if (!$unit) { continue; }
            ?>
                <tr><td><?=$field['name']?></td><td><input name="rdi[<?=$field['field']?>]" value="<?=$rdi?>" default-value="<?=$rdi?>" size="3" id="rdi-<?=$field['field']?>" /></td><td class="bigger"><?=$unit?></td></tr>
            <?php }?>   
        </table>
    </div>
</div>

<a href="" id="toggle-compare" style="display: none;">Compare <span>[ + ]</span></a>
<div class="menu" id="menu-compare" style="display: none;">
    <div id="compare-list">
        
    </div>
    <input type="button" obj="compare" function="pane" value=" Compare Foods " id="compare-compare" style="display: none;" />
    <input type="button" obj="compare" function="reset" value=" Remove All " />
</div>

<div id="form-controls">
<a id="help" href="http://gtx0.com/j/nutrition-tool" target="_blank">Help</a>
<input type="button" function="reset_all" value=" Reset all " />
<input type="submit" id="submit" value=" Submit " />
<input type="button" class="switch-button" id="debug-button" value=" Debug: OFF " switchto=" Debug: ON " style="display: none;" />
</div>

</div>
</form>

<div id="panes">

    <div id="results-pane">
    <h2 id="results-title" style="display: none;">Results</h2>
    <div class="pagination pagination-top" style="display: none;">
        <input type="button" obj="db" function="prev_page" value=" Previous Page " class="prev-page" />
        <input type="button" obj="db" function="next_page" value=" Next Page " class="next-page"  />
        <span class="pagination-showing bigger"></span>
    </div>
    <table id="results"></table>
    <div class="pagination pagination-bottom" style="display: none;">
        <input type="button" obj="db" function="prev_page" value=" Previous Page " class="prev-page" />
        <input type="button" obj="db" function="next_page" value=" Next Page " class="next-page"  />
        <span class="pagination-showing"></span>
    </div>
    </div>
    
    <div id="nutrition-pane" style="display: none;">
    <h3 id="nutrition-title"></h3>
    <input type="button" obj="results" function="refresh" value=" Back to Results " />
    <input type="button" obj="compare" function="init" var1="" var2="" value=" Compare Food " id="nut-compare-button" />
    <div class="comparing" id="nut-comparing" style="display: none;">Comparing</div><br />
    <div id="weights-wrapper">
        <span id="weights-title" class="bigger">Serving Size:</span>
        <span id="weight-custom" style="display: none;">
            <input id="weight-custom-input" size="3" /> 
            <select id="weight-custom-select"></select>
        </span>
        <select id="weights" class="spaced"></select>
        <input type="button" obj="nutrition" function="show_custom_weight" id="custom-weight-button" class="spaced" value=" Customize " />
        </span>
    </div>
    <table id="nutrition"></table>
    </div>
    
    <div id="compare-pane" style="display: none;">
    <h3>Comparing 100g of each:</h3>
    <input type="button" obj="compare" function="back" value=" Back to Results " />
    <input type="button" obj="graphs" function="pane" value=" View RDI Graph " class="spaced" /><br />
    
    <table id="compare-table">
        
    </table>
    
    </div>
    
    <div id="graph-pane" style="display: none;">
        <input type="button" obj="compare" function="pane" value=" Back to Chart " />
        <div id="graphs"></div>
    </div>

</div>

<div id="message"></div>

<div id="debug" style="position: fixed;top:0px;right:5px;width:800px;padding:5px;background-color: #fff;border:1px solid #000;word-wrap: break-word;display: none;height: 700px; overflow-y: scroll;"></div>

<script type="text/JavaScript">
    var field_names = <?=json_encode($db->field_names)?>;
</script>
<script type="text/JavaScript" src="js/jquery.js"></script>
<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
<script type="text/JavaScript" src="js/main.js"></script>
</body>