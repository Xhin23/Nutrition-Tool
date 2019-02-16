<?php

function pair($arr,$id,$name='')
{
    if (!$arr) { return Array(); }
    $new_arr = Array();
    foreach ($arr?:Array() AS $row)    
    {
        $row['id'] = $row[$id]; 
        $new_arr[$row[$id]] = $row;
        // For the CP's select functionality
        if ($name)
        {
            $new_arr[$row[$id]]['name'] = $row[$name];
        }
    }
    return $new_arr;
}

function slug($var)
{
    $var = strtolower(str_replace(' ','_',$var));
    $var = preg_replace("/[^A-Za-z0-9 ]/", '', $var);
    return $var;
}

function deslug($var)
{
    return ucwords(str_replace('_',' ',$var));
}

function deslug_all($arr)
{
    $new_arr = Array();
    foreach ($arr?:Array() AS $row)
    {
        $new_arr[$row] = deslug($row);
    }
    return $new_arr;
}

function fill_select($name,$options,$value,$blank=false,$class='',$style='',$attrs=Array())
{
    if (!$options) { $options = Array(); }
    if ($blank) 
    {
         $blank_opt = Array('' => '');
         if (is_array($blank)) { $blank_opt = Array('' => '--- '.$blank[0].' ---'); }
         $options = $blank_opt + $options; 
    } 

    foreach ($attrs?:Array() AS $key => $val)
    {
        $attml = $key.'="'.$val.'" ';
    }
    
    $html .= '<select name="'.$name.'" class="'.$class.'" style="'.$style.'" '.$attml.'>';

    foreach ($options?:Array() AS $key => $val)
    {
        if (is_array($val)) { $val = $val['name']; } 
        $select = '';
        if ($value == $key) { $select = 'selected="selected"'; }
        $html .= '<option value="'.$key.'" '.$select.'>'.$val.'</option>';
    }
    $html .= '</select>';
    return $html;
}

function has($haystack,$needle,$i=false)
{
    // if the needle is an array
    if (is_array($needle))
    {
        foreach ($needle?:Array() AS $unit)
        {
            if (has($haystack,$unit,$i))    
            {
                return true;    
            }
        }
        return false;
    }
    
    // if the haystack is an array
    if (is_array($haystack))
    {
        return in_array($needle,$haystack);
    }
    
    // if the haystack is a string
    else
    {
        // case-insensitivity
        if ($i)
        {
            if (stripos($haystack,$needle) !== false)
            {
                return true;
            }
        }
        // case-sensitive (default)
        else
        {   
            if (strpos($haystack,$needle) !== false)
            {
                return true;
            }
        }
    }
}