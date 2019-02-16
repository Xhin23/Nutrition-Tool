<?php
Class DB
{
    public $field_names = Array(
        'calories' => Array('Calories',' kcal'),
        'protein' => Array('Protein','g'),
        'keto_amino' => Array('Ketogenic amino acids','g',0,'Ketogenic amino acids are amino acids that are metabolized directly into acetyl-coa (and eventually ketone bodies) instead of glucose.<br /><br />
        In this field I have included Leucine and Lysine (which are always ketogenic), but also Phenylalanine, Isoleucine, Tryptophan, and Tyrosine, which are both ketogenic and glucogenic. I have also included Threonine, which actually is ketogenic despite some textbooks claiming the contrary.'),
        'gluco_amino' => Array('Glucogenic amino acids','g',0,'Glucogenic amino acids are amino acids that are converted into glucose when broken down. <br /><br />
       The ones I\'ve charted here are the 13 exclusively glucogenic amino acids, not ones like tryptophan which are both glucogenic and ketogenic.<br /><br />
       Note also that data for Asparagine, Cysteine, and Glutamine was not available, so this field really measures the 10 remaining glucogenic amino acids.'),
        'fat' => Array('Fat','g'),
        'carbs' => Array('Total Carbohydrates','g'),
        'alcohol' => Array('Alcohol','g'),
        'starch' => Array('Starch','g'),
        'sugar' => Array('Sugar','g'),
        'fructose' => Array('Fructose','g'),
        'fiber' => Array('Fiber','g'),
        'net_carbs' => Array('Net Carbs','g',0,'Total carbohydrates minus fiber. Usually subtracting sugar alcohols as well, although that information was not available.<br /><br />
        In the UK, the "Carbohydrates" column on food labeling already has fiber subtracted.'),
        'trans_fat' => Array('Trans Fat','g'),
        'sat_fat' => Array('Saturated Fat','g'),
        'mono_fat' => Array('Monounsaturated Fat','g'),
        'poly_fat' => Array('Polyunsatured Fat','g'),
        'cholesterol' => Array('Cholesterol','mg'),
        
        'calcium' => Array('Calcium','mg',1000),
        'iron' => Array('Iron','mg',18),
        'magnesium' => Array('Magnesium','mg',400),
        'phosphorus' => Array('Phosphorus','mg',1000),
        'potassium' => Array('Potassium','mg',4700),
        'sodium' => Array('Sodium','mg'),
        'zinc' => Array('Zinc','mg',15),
        'copper' => Array('Copper','mg',2),
        'fluoride' => Array('Fluoride','mcg'),
        'manganese' => Array('Manganese','mg',2),
        'selenium' => Array('Selenium','mcg',70),
        'vit_a_RAE' => Array('Vitamin A','mcg',900,'Measured in RAE. Each μg here is equal to 1 μg retinol, 2 μg of β-carotene in oil, 12 μg of "dietary" beta-carotene, or 24 μg of the three other dietary provitamin-A carotenoids.'),
        'vit_e' => Array('Vitamin E','mg',15),
        'vit_d' => Array('Vitamin D',' IU',400),
        'vit_c' => Array('Vitamin C','mg',60),
        'thiamin' => Array('Thiamin','mg',1.5,'Also known as Vitamin B1'),
        'riboflavin' => Array('Riboflavin','mg',1.7,'Also known as Vitamin B2'),
        'niacin' => Array('Niacin','mg',20,'Niacin is a form of Vitamin B3'),
        'pantothenic_acid' => Array('Pantothenic Acid','mg',10,'Also known as Vitamin B5'),
        'vit_b6' => Array('Vitamin B6','mg',2),
        'folate' => Array('Folate','mcg',400,'Vitamin B9 is one form of Folate. Folic acid and folacin are also known forms.'),
        'vit_b12' => Array('Vitamin B12','mcg',6),
        'choline' => Array('Choline','mg',550),
        'vit_k1' => Array('Vitamin K1','mcg',80),
        'min_score' => Array('Mineral Score','',0,'This number is the sum of RDA percentages for Calcium, Iron, Magnesium, Phosphorus, Potassium, Zinc, Copper, Manganese and Selenium.<br /><br />
        I didn\'t include sodium because that would cause overly salty foods to have an inflated score. I also didn\'t include fluoride because there\'s no established RDA for it.'),
        'vit_score' => Array('Vitamin Score','',0,'This number is the sum of RDA percentages for Vitamin A, Thiamin, Riboflavin, Niacin, Pantothenic Acid, Vitamin B6, Folate, Vitamin B12, Vitamin C, Vitamin D, Vitamin E, Vitamin K1, Vitamin K2 and Choline.<br /><br />
        Note that while Choline technically isn\'t a vitamin, it has many similarities to B-complex vitamins.
        '),
        'micro_score' => Array('Micronutrient Score','',0,'This number is derived from summing together both the mineral score and the vitamin score. While definitely flawed, it\'s a good shorthand for measuring how nutritious a food is overall, at least as far as vitamins and minerals go.'),
        'vit_mk4' => Array('Vitamin K2','mcg',0,'*This field actually measures Menatetrenone (MK-4), one of the nine forms of vitamin K2. Other data was not available.'),
        'hydroxyproline' => Array('Collagen','g',0,'*This is actually a measurement of Hydroxyproline. <br /><br />
        Animal collagen is 13.5% hydroxyproline and hydroxyproline is found in few proteins other than collagen, so it\'s a good way to measure it. '),
        'phytosterols' => Array('Phytosterols','mg')
    );

    public function get_fields()
    {
        $arr = ZXC::sel('*/food')->lim(1)->go();
        $arr = $arr[0];
        $arr = array_keys($arr);
        
        $fields = Array();
        foreach ($arr?:Array() AS $unit)
        {
            if (!$this->field_names[$unit][0]) { continue; }
            $fields[] = Array('field' => $unit, 'name' => $this->field_names[$unit][0]);
        }
        return $fields;
    }

    public function get_cats()
    {
        $cats = ZXC::sel('groupid,name/food_groups')->go();
        return $cats;
    }
    
    // ---
    
    public $data, $ZXC;
    
    public function select($fields='*')
    {  
        if ($fields != '*')
        {
            $fields = implode(',',$fields);
        }
        
        $debug = '';
        if ($_POST['debug'] == 1) { $debug = '//'; } 
        ZXC::CONF('debug_backticks',true);
        $this->ZXC = ZXC::sel($debug.$fields.'/food');

    }
 
    public function add_fields($arr)
    {
        if (!is_array($arr))
        {
            $arr = Array($arr);
        }
        $this->ZXC->_action .= ','.implode(',',$arr);
    }

        private function ratio_not_zero($num,$denom)
        {
            $this->ZXC->where($num.'!=',0,$denom.'!=',0);
        }

    public function to_ratio($num,$denom)
    {
        $ZXC = new ZXC();
        return '('.$ZXC->prep($num).'/'.$ZXC->prep($denom).')';
    }
        
        private function where($field,$op,$val)
        {
            if (is_array($field))
            {
                $field = $this->to_ratio($field[0],$field[1]);
            }
            else
            {
                $field = $this->ZXC->prep($field);
            }
            
            if (!has(Array('=','>','<'),$op)) { die; }
            
            $where = $field.' '.$op.' '.$this->ZXC->prep_val($val);
            $this->ZXC->_where[] = $where;
        }

    public function filter()
    {
        $data = $this->data;
        
        $ratio_fields = Array();
        foreach ($data['ratio_field_num']?:Array() AS $key => $num)
        {
            $denom = $data['ratio_field_denom'][$key];
            if (!$num || !$denom) { continue; }
            
            $this->ratio_not_zero($num,$denom);
            $ratio_fields[] = $this->to_ratio($num,$denom);
        }
        
        if (count($ratio_fields))
        {
            $this->ZXC->_action .= ','.implode(',',$ratio_fields);
        }
        
        $cats = $data['cats'];
        
        if (count($cats))
        {
            $this->ZXC->whin('groupid',$cats);
        }
        
        $filter_fields = $data['filter_fields'];
        $filter_types = $data['filter_types'];
        $filter_amts = $data['filter_amts'];
        
        foreach ($filter_fields?:Array() AS $key => $val)
        {
            $is_ratio = false;
            if (!$val) { continue; }
            if (substr($val,0,7) == 'ratio__')
            {
                $is_ratio = true;
                $val = explode('__',$val);
                $val = Array($val[1],$val[2]);
            }
            else
            {
                $this->add_fields($val);
            }
            
            // RDI Filtering
            $amt = $filter_amts[$key];
            if (has($amt,'%') && !$is_ratio && $data['rdi'][$val])
            {
                $amt = str_replace('%','',$amt);
                $amt = ($amt/100)*$data['rdi'][$val];
            }
            
            $type = $filter_types[$key];
            if ($type == 'lt')
            {
                $this->where($val,'<',$amt);
            }
            elseif ($type == 'gt')
            {
                $this->where($val,'>',$amt);
            }
            elseif ($type == 'eq')
            {
                $this->where($val,'=',$amt);
            }
            elseif ($type == 'notzero')
            {
                $this->ZXC->where($val.'!=',0);
            }
        }
        
        if ($data['search'])
        {
            $search = explode(' ',$data['search']);
            foreach ($search?:Array() AS $entry) 
            {
                $this->ZXC->like('name','%'.$entry.'%');
            }
        }
        
        if ($data['filter'])
        {
            $search = explode(' ',$data['filter']);
            foreach ($search?:Array() AS $entry)
            {
                $this->ZXC->like('name!=','%'.$entry.'%');
            }
        }
    }

    public function by_id($id)
    {
        $this->ZXC->where('id',$id);
    }
    
    public function by_ids($arr)
    {
        $this->ZXC->iter('id',$arr);
    }
    
    public function results()
    {
        $sort_order = '--';
        if ($this->data['sort_order'] == 'asc')
        {
            $sort_order = '++';
        }
        
        $ratio_num = $this->data['ratio_num'];
        $ratio_denom = $this->data['ratio_denom'];
        
        if ($ratio_num && $ratio_denom)
        {
            $this->ZXC->_order = ' ORDER BY '.$this->to_ratio($ratio_num,$ratio_denom).' '.$this->data['sort_order'];
            $this->ratio_not_zero($ratio_num,$ratio_denom);
        }
        else
        {
            $rank = $this->data['rank'] ?: 'id';
            $this->ZXC->sort($rank.$sort_order);
        }
        
        $entries = $_POST['entries'];
        if ($entries < 0) { $entries = 0; }
        if ($entries > 100) { $entries = 100; }
        
        $offset = $_POST['offset'];
        if ($offset < 0) { $offset = 0; }
        
        $count = clone $this->ZXC;
        $count->_action = 'SELECT COUNT(`id`)';
        $count = $count->one();
        
        $results = $this->ZXC->lim(intval($entries))->do_off(intval($offset))->go();
        return Array( 'results' => $results, 'count' => $count);
    }

}