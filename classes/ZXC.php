<?php

class ZXC {
    // ------------------------------------------- //
    // ------------- ZXC Properties -------------- //
    // ------------------------------------------- //
    
    // The Query itself
    public $_query;
    
    // The initial statement
    public $_action;

    // These should all be self-explanatory
    public $_from;
    public $_where = Array();
    public $_order;
    public $_group;
    public $_having;
    public $_offset;
    public $_limit;
    
    protected $_offlim;

    // Update/Insert Properties
    public $_set = Array();
    public $_vals;
    public $_no_result = false;
    public $_return_id = false;
    
    // Properties useful for joins
    public $_fields;
    public $_join;
    
    // Properties for the alt() method
    public $_alt = Array();
    public $_key = Array();
    
    // Properties for SQL Result sets
    public $_print;
    public $_autofix = false; 
    public $_end_type = '';
    public $_return_blank_array = false;
    
    // Static properties for ZXC itself
    static $OBJ;

    // MySQLi Resource
    static $LINK;
    
    // List of queries (if log_queries is true)
    static $QUERY;
    
    // Default Configuration settings
    static $CONF = array (
        
        'db_host' => '',
        'db_user' => '',
        'db_pass' => '',
        'use_db' => '',
        
        'result_type' => 'ASSOC',

        'allow_debug_all' => true,
        'allow_how' => true,
        
        'debug_all' => false,
        'log_queries' => false,
        'debug_backticks' => false,
        'allow_debug' => true,
        
        'old_mode' => false
    );
            
    // Parser for various modular functions
    static $parser = Array(                  
        'field' => Array(
            '=' => 'COUNT($)',
            '+' => 'SUM($)',
            '>' => 'MAX($)',
            '<' => 'MIN($)'
        ),
        'update' => Array(
            '+=' => '$F + $V',
            '-=' => '$F - $V',
            '*=' => '$F * $V',
            '/=' => '$F / $V',
            '%=' => '$F % $V',
            '.=' => 'CONCAT($F,$V)',
            '~=' => 'CONCAT($V,$F)',
            '++' => '$F + 1',
            '--' => '$F - 1'
        ),
        'query' => Array(
            'from' => 'FROM $',
            'vals' => 'VALUES (,)',
            'set' => 'SET (,)',
            'where' => 'WHERE (&)',
            'order' => '$',
            'having' => 'HAVING (&)',
            'offlim' => 'LIMIT $',
            'alt' => 'ON DUPLICATE KEY UPDATE (,)'
        )
    );
              
    // ----
    
    static $user_functions = Array();
    static $user_static_functions = Array();
         
    // A fix for functions with the same names as PHP functions
    public function __call($func,$args)
    {
        $user_func = ZXC::$user_functions[$func];
        if ($user_func)
        {
            array_unshift($args,$this);
            return call_user_func_array($user_func,$args);
        }
        
        if (strpos($func,'do_') === false)
        {
            $func = 'do_'.$func;
            return call_user_func_array(Array('ZXC',$func),$args);
        }
    }

    public static function __callstatic($func,$args)              
    {
        $user_func = ZXC::$user_static_functions[$func];
        if ($user_func)
        {
            return call_user_func_array($user_func,$args);
        }
    }
              
    // -------------------------------------------------- //
    // ------------------External Methods---------------- //
    // -------------------------------------------------- //
          
    // ----------- Mysql Query Modes ---------- //

    private $skip_compute = false;
    
    // Runs a raw Query
    static function raw($str) {
        $ZXC = new self();
        $ZXC->filter_query($str);
        $ZXC->_query = $str;
        $ZXC->_skip_compute = true;
        return $ZXC;
    }
    
    // Initializes a SELECT ... FROM Query
    
    static function sel($str) 
    {
        if (!$this) { $ZXC = new self(); } else { $ZXC = $this; }

        $ZXC->filter_query($str);        
        $ZXC->_mode = 'sel';
        
        if ($ZXC->prefix($str,'#')) { $ZXC->_query .= ' DISTINCT '; }
        $str = explode('/',$str);
        $sel = $str[0];
        $from = $str[1];
        $db = $str[2];
        
        if ($db)
        {
            ZXC::SWITCH_DB($db);
            $ZXC->revert_db = 1;
        }
        
        if ($from)
        {
            $ZXC->parse_from($from);
        }
        
        $ZXC->parse_sel($sel);
        
        return $ZXC;
    }
    
    // Initializes an INSERT Query
    
    static function ins($str)
    {
        if (!$this) { $ZXC = new self(); } else { $ZXC = $this; }
        $ZXC->filter_query($str);
        $ZXC->_mode = 'ins';
        $ZXC->_action = 'INSERT INTO '.$str.' ';
        $ZXC->_return_id = true;
        return $ZXC;
    }
    
    // Initializes an UPDATE query
    
    static function up($str)
    {
        if (!$this) { $ZXC = new self(); } else { $ZXC = $this; }
        $ZXC->filter_query($str);
        $ZXC->_mode = 'up';
        $ZXC->_action = 'UPDATE '.$str.' ';
        $ZXC->_no_result = true;
        return $ZXC;
    }
    
    // Initializes an INSERT Query that turns into an UPDATE query if the key already exists.
    
    static function alt($str)
    {
        if (!$this) { $ZXC = new self(); } else { $ZXC = $this; }
        $ZXC->filter_query($str);
        $ZXC->_mode = 'alt';
        $ZXC->_action = 'INSERT INTO '.$ZXC->prep($str).' ';
        $ZXC->_return_id = true;
        return $ZXC;
    }
    
    // Initializes a DELETE Query
    
    static function del($str)
    {
        if (!$this) { $ZXC = new self(); } else { $ZXC = $this; }
        $ZXC->filter_query($str);
        $ZXC->_mode = 'del'; 
        $ZXC->_action = 'DELETE ';
        $ZXC->_no_result = true;
        $ZXC->parse_from($str).' ';
        return $ZXC;
    }

    //------------------------- FROM and JOIN clauses ----------------------- //
    
    // Adds a FROM or JOIN statement
    public function from($str)
    {
        $this->parse_from($str);
        return $this;
    }
    
    // -------- WHERE Clauses ------------------ //
    
    // Adds a WHERE Clause.
    public function where()
    {
        $args = func_get_args();
        $this->_where = array_merge($this->_where,$this->parse_where($args)); 
        
        return $this;
    }
    
    // Adds a WHERE ... OR Clause
    public function any()
    {
        $args = func_get_args();
        $field = $this->prep($args[0]);
        for ($i = 1; $i < count($args); $i++)
        {
            $or[] = $field.' = '.$this->prep_val($args[$i]);
        }
        $this->_where[] = '('.implode(' OR ',$or).')';
        return $this;
    }
    
    // Adds a WHERE clause where the field has the same name as a variable
    public function via($str,$arr='')
    {
        if ($this->auto_via($str,'where'))
        {
            return $this;    
        }
        if ($arr) { $this->set_via($arr); }
        $str = explode(',',$str);
        for ($i = 0; $i < count($str); $i++)
        {
            $x = $str[$i];
            $y = $this->parse_via($x);
            $y = $this->prep_val($y);
            $this->_where[] = $x.' = '.$y.' ';
        }
        
        return $this;
    }
    
    // Adds a BETWEEN clause
    public function twix($str,$x,$y)
    {
        $str = $this->prep($str); 
        $x = $this->prep_val($x);
        $y = $this->prep_val($y);
        $this->_where[] = $str.' BETWEEN '.$x.' AND '.$y.' ';
        return $this;
    }
    
    // Adds a LIKE clause
    public function like()
    {
        $args = func_get_args();
        for ($i = 0; $i < count($args); $i+=2)
        {
            $not = '';
            if ($this->suffix($args[$i],'!='))
            {
                $not = ' NOT ';
            } 
            $this->_where[] = $this->prep($args[$i]).$not.' LIKE '.$this->prep_val($args[$i+1]); 
        }
        return $this;
    }
    
    // Iterates through an array, adding WHERE .. OR clauses as it goes.
    public function iter($tab,$arr)
    {
        for ($i = 0; $i < count($arr); $i++)
        {
            $where[] = $this->prep($tab).' = '.$this->prep_val($arr[$i]);
        }
        $this->_where[] = ' ('.implode(' OR ',$where).') ';
        return $this;
    }
    
    public function whin($field,$mixed)
    {
        $field = $this->prep($field);
        
        $subquery = $this->subquery($mixed);
        if ($subquery) { $mixed  = $subquery; }
        elseif (is_array($mixed))
        {
            $mixed = "'".implode("', '",$mixed)."'";
        }
        
        
        $this->_where[] = $field.' IN ('.$mixed.')';
        return $this;
    }
    
    // ------------ Sorting and Aggregate Clauses ----------------- //
    
    // Runs SORT and GROUP BY Clauses
    public function do_sort($args)
    {
        $args = explode(',',$args);
        for ($i = 0; $i < count($args); $i++)
        {            
            if ($this->prefix($args[$i],'#'))    { $group[] = $this->prep($args[$i]); }
            elseif ($this->suffix($args[$i],'++')) { $ord[] = $this->prep($args[$i]).' ASC'; }
            else if ($this->suffix($args[$i],'--')){ $ord[] = $this->prep($args[$i]).' DESC'; }
        }
        if ($group) { $this->_order = ' GROUP BY '.implode(', ',$group).' '; }
        if ($ord) { $this->_order .= ' ORDER BY '.implode(', ',$ord).' '; }
        return $this;
    }
    
    // Runs a HAVING clause
    public function hav()
    {
        $args = func_get_args();
        $this->_having = $this->parse_where($args);
        return $this;
    }
    
    // -------------- LIMIT and OFFSET Clauses ------------------- //
    
    // Sets a Query OFFSET
    public function do_off($x)
    {
        $this->_offset = $x;
        return $this;
    }
    
    // Sets a Query LIMIT
    public function lim($x)
    {
        $this->_limit = $x;
        return $this;
    }
    
    // -------------- Update and Insert friendly Clauses ---------- //
    
    // Insert these Values.
    public function val()
    {
        $args = func_get_args();
        for ($i = 0; $i < count($args); $i++)
        {
            $this->_vals[] = $this->prep_val($args[$i]);
        }
        return $this;
    }
    
    // Defines a key for use in the alt() method
    public function do_key()
    {
        $args = func_get_args();
        for ($i = 0; $i < count($args); $i+=2)
        {
            $this->_key[] = $this->prep($args[$i]).' = '.$this->prep_val($args[($i+1)]); 
        }
        return $this;
    }
    
    public function vkey($str,$arr='')
    {
        if ($this->auto_via($str,'key'))
        {
            return $this;    
        }
        if ($arr) { $this->set_via($arr); }
        $str = explode(',',$str);
        for ($i = 0; $i < count($str); $i++)
        {
            $this->_key[] = $this->prep($str[$i]).' = '.$this->prep_val($this->parse_via($str[$i]));
        }
        return $this;
    }
    
    // Insert/Update these values by field name and value.
    public function set()
    {
        $args = func_get_args();
       
        for ($i = 0; $i < count($args); $i+=2)
        {
            $d = 0;
            
            $format = '$F = $V';
            
            foreach (ZXC::$parser['update'] as $key => $func)
            {
                if ($this->suffix($args[$i],$key))
                {
                    if (strpos($format,'$V') === false)
                    {
                        $d++;
                    }
                    
                    $format = str_replace('$V',$func,$format);
                    break;
                }
            }
           
            $format = str_replace('$F',$this->prep($args[$i]),$format);
            $format = str_replace('$V',$this->prep_val($args[($i+1)]),$format);
            
            $this->_set[] = $format;       
            $i -= $d;
        }
        return $this;
    }

    // SET Clause where field name and variable name are the same.
    public function vset($str,$arr='')
    {
        if ($this->auto_via($str,'set'))
        {
            return $this;    
        }
        if ($arr) { $this->set_via($arr); }
        $str = explode(',',$str);
        for ($i = 0; $i < count($str); $i++)
        {
            $get = $this->parse_via($str[$i]);
            $this->_set[] = $this->prep($str[$i]).' = '.$this->prep_val($get);
        }
        return $this;
    }
    
    // ------------ End Clauses ----------------------- //
    
    // Runs insert/update/delete.
    // On insert, returns the insert id.
    // Can also fetch a 2-d array or null if there's no result set.
    public function go($return_blank_array=false)
    {
        $this->_return_blank_array = $return_blank_array;
        $this->compute_query();
        return $this->SQL($this);
    }
    
    // Deprecated, for backwards-compatibility.
    public function one()
    {
        if (ZXC::$CONF['old_mode'])
        {
            return $this->auto();
        }
        else
        {
            return $this->the();
        }
    }
    
    public function auto()
    {
        $this->_autofix = true;
        $this->compute_query();
        return $this->SQL($this);
    }
    
    // Fetches a string
    public function the()
    {
        $this->_end_type = 'the';
        $result = $this->go();
        while (is_array($result))
        {
            $result = $result[0];
        }
        return $result;
    }
    
    // Fetches a column of values, ex [1,2,3]
    public function col($return_blank_array=false)
    {
        $this->_return_blank_array = $return_blank_array;
        $this->_end_type = 'col';
        return $this->go();
    }
    
    // Fetches a row of values, ex {id: 1, username: 'Xhin'} 
    public function row($return_blank_array=false)
    {
        $this->_return_blank_array = $return_blank_array;
        $this->_end_type = 'row';
        return $this->go();
    }
    
    // Shows you the rendered query without running it.
    public function test()
    {
        $this->compute_query();
        $this->output();
    }
    
    // --------------------------------------------------------- //
    // --------------ZXC Internal Configuration ---------------- //
    // --------------------------------------------------------- //
    
    // This stores an object or array into ZXC's internal memory which will be called in v-prefixed functions.
    static function OBJ($obj='')
    {
        ZXC::$OBJ = Array();
        if (!$obj) { ZXC::$OBJ = ''; return; }
        foreach ($obj as $key => $val)
        {
            ZXC::$OBJ[$key] = $val;
        }
    }
    
    // This updates ZXC's internal configuration.
    static function CONF() 
    {
        $args = func_get_args();
        if (is_array($args[0]))
        {
            ZXC::$CONF = array_merge(ZXC::$CONF,$args[0]);
            return;
        }
        for ($i = 0; $i < count($args); $i+=2)
        {
            ZXC::$CONF[$args[$i]] = $args[$i+1]; 
        }
    }
    
    // This allows you to add custom user functions individually instead of extending ZXC.   
    static function FUNC($code,$callback='',$static=false)
    {
        if (!$callback) 
        {
            $callback = $code;
        }
        
        if ($static)
        {
            ZXC::$user_static_functions[$code] = $callback;
        }
        else
        {
            ZXC::$user_functions[$code] = $callback;
        }
    }
    
    // This adds something to ZXC's internal symbol parser
    static function PARSE($type,$code,$format)
    {
        ZXC::$parser[$type][$code] = $format;
    }
    
    static $DBS = Array();  
    
    // This initializes a MySQLi Link
    static function INIT($host,$user,$pass,$db,$alias='')
    {
        if (is_array($host))
        {
            $user = $host['user'];
            $pass = $host['pass'];
            $db = $host['db'];
            $alias = $host['alias'];
            $host = $host['host'];
        }
        
        ZXC::CONF('db_host',$host,'db_user',$user,'db_pass',$pass,'use_db',$db);
        
        try 
        {
            ZXC::$LINK = new PDO('mysql:host='.$host.';dbname='.$db, $user, $pass);
        } 
        catch (PDOException $e) 
        {
            die("Connect failed: <br />" . ZXC::$LINK->getMessage() . "<br/ >");
        }
        ZXC::$LINK->exec('SET NAMES utf8');

        // Stores the connection internally        
        if (!$alias) { $alias = ZXC::raw('SELECT DATABASE()')->one(); }
        ZXC::$DBS[$alias] = ZXC::$LINK;
    }
    
    static $last_link;  
    
    // This allows you to add another database to your mysql connection.
    static function ADD_DB($name,$alias='',$primary=false)
    {
        if (!$alias) { $alias = $name; }

        ZXC::$DBS[$alias] = new PDO('mysql:host='.ZXC::$CONF['host'].';dbname='.$name, ZXC::$CONF['db_user'], ZXC::$CONF['db_pass']);
        if ($primary)
        {
            ZXC::SWITCH_DB($alias);
        }
    }
       
    // This switches the database that ZXC works with.
    static function SWITCH_DB($alias)
    {
        ZXC::$last_link = ZXC::$LINK;
        ZXC::$LINK = ZXC::$DBS[$alias];
    }
    
    // This reverts back to a database that was switched from using the above function
    static function REVERT_DB()
    {
        ZXC::$LINK = ZXC::$last_link;    
    }
    
    // --------------------------------------------------------- //
    // ---------------ZXC Internal Methods --------------------- //
    // --------------------------------------------------------- //
    
    // ------------ Query Parsers ------------- //
    
    // Parses the SELECT clause
    public function parse_sel($sel)
    {
        $sel = explode(',',$sel);
        for ($i = 0; $i < count($sel); $i++)
        {
            if ($sel[$i] != '*') { $sel[$i] = $this->prep($sel[$i]); }
        }
        $sel = implode(', ',$sel);
        $this->_action = 'SELECT '.$sel;
    }
    
    protected $_keys = array('<<' => 'LEFT JOIN ', '<>' => 'INNER JOIN ', '><' => 'OUTER JOIN ', '>>' => 'RIGHT JOIN ');
    
    // This function Parses FROM and JOIN clauses.
    public function parse_from($from)
    {
        $subquery = $this->subquery($from);
        if ($subquery) 
        {
            $this->_from = $subquery.' AS '.$this->eprep(md5(microtime()));; 
            return;
        }
        
        if (strpos($from,'<') === FALSE && strpos($from,'>') === FALSE) { $this->_from .= $this->prep($from); return; }
        if (substr($from,0,1) == '<' || substr($from,0,1) == '>') { $from = ' '.$from; }
        $max = strlen($from);
        unset($this->_fields);
        $keys = $this->_keys;
        for ($i = 0; $i < $max; $i++)
        {
            $sub = $from[$i];
            if ($sub === '>' || $sub === '<')
            {
                $split .= $sub;
                if ($q % 2 == 0) { $this->_fields[] = $buff; } 
                else 
                {
                    if (strlen($buff) == 0) { $buff = $last; }
                    $last = $cond[] = $buff;
                    $j[] = $keys[$split];
                    unset($split);
                } 
                unset($buff);
                $q++;
            }
            else { $buff .= $sub; }
        }
        if ($q % 2 == 0) { $this->_fields[] = $buff; } else { $cond[] = $buff; }
    
        $from = ' '.$this->_fields[0].' ';
        for ($i = 0; $i < (count($this->_fields)-1); $i++) 
        {
            $from .= ' '.$j[$i].' '.$this->prep($this->_fields[($i+1)]).' '.$this->j_parse($cond[$i]).' ';
        }
    
        $this->_from .= ' '.$from.' ';
    }

    private $where_operators = array('<=','>=','<','>','!=');

    // This function parses WHERE clauses.
    public function parse_where($args)
    {
        for ($i = 0; $i < count($args); $i+=2)
        {
            $op = '=';
            for ($a = 0; $a < count($this->where_operators); $a++)
            {
                $sym = $this->where_operators[$a];
                if ($this->suffix($args[$i],$sym)) { $op = $sym; }
            }
             
            $x = $this->prep($args[$i]);
            $y = $this->prep_val($args[($i+1)]);
            $where[] = $x.' '.$op.' '.$y.' ';
        }
        return $where;
    }
    
    private $via_obj = Array();
    
    private $via_superglobals = Array(
        'p' => '_POST',
        'g' => '_GET',
        'c' => '_COOKIE',
        's' => '_SESSION',
        'r' => '_REQUEST'
    );
    
    public function auto_via($arr,$field)
    {
        if (!is_array($arr)) { return; }
        $field = '_'.$field;
        foreach ($arr?:Array() AS $key => $value)
        {
            array_push($this->$field, $this->prep($key).' = '.$this->prep_val($value));
        }
        return true;
    }
    
    public function set_via($var)
    {
        if (is_array($var))
        {
            $set = $var;
        }
        else
        {
            $super = $this->via_superglobals[$var];
            if (!$super) { return; }
            $set = $GLOBALS[$super];
        }
        
        $this->via_obj = $set;
    }
    
    // This function returns a value from a v-prefixed function.
    public function parse_via($str)
    {
        if ($this->via_obj) { $str = $this->via_obj[$str]; }
        elseif (ZXC::$OBJ[$str]) { $str = ZXC::$OBJ[$str]; }
        else { $str = $GLOBALS[$str]; }
        
        return $str;
    }
    
    // This filters out parser instructions (such as debugging) from an initial function
    public function filter_query(&$str)
    {
        if ($this->prefix($str,'//')) 
        {
             $this->_print = 'one';
        }
    }
    
    // This function assembles all the pieces of a ZXC query into a raw MySQL Query.
    public function compute_query() 
    {
        $ZXC = $this;

        $ZXC->_set = array_merge($ZXC->_set,$ZXC->_key);

        // Should prevent potential glitches
        if ($ZXC->_skip_compute) { return; }
        
        // Force these functions into the parser['query'] syntax below
        if ($ZXC->_mode == 'alt') { $ZXC->_alt = $ZXC->_set; }
        
        if ($ZXC->_offset || $ZXC->_limit)
        {
            $offset = $ZXC->_offset?: 0;
            if ($ZXC->_limit) { $limit = ','.$ZXC->_limit; }
            $ZXC->_offlim = $offset.$limit;
        }
        
        if ($ZXC->_action) { $ZXC->_query = $ZXC->_action; }
        
        if ($ZXC->_mode == 'del' && $ZXC->_fields) 
        {
            foreach ($ZXC->_fields AS $field) { $x[] = $field.'.*'; }
            $ZXC->_query .= implode(', ',$x);
        }

        foreach (ZXC::$parser['query'] AS $key => $func)
        {
            $name = '_'.$key;
            $value = $ZXC->$name;
            if ($value)
            {
                $func = str_replace('$',$value,$func);
                if (is_array($value))
                {
                    $func = str_replace('(,)',implode(', ',$value),$func);
                    $func = str_replace('(&)',implode(' AND ',$value),$func);
                }
                
                $ZXC->_query .= ' '.$func;    
            }
        }
  
        $this->_query = $ZXC->_query;
    }

    // ------------- Statement Parsers ---------------- //
        
    // This allows you to place ZXC objects as arguments, generating subqueries.
    public function subquery($str)
    {   
        if (is_object($str))
        {
            if (get_class($str) == 'ZXC')
            {
                 $str->compute_query();
                 return '('.$str->_query.')';
            }
        }
    }
    
    // This turns numbers into field aliases.
    protected function rep_num($str)
    {
        if (is_numeric(substr($str,0,1)))
        {
            $field = $this->_fields[substr($str,0,1)-1];
            if (strpos($field,'|') !== false)
            {
                $field = explode('|',$field);
                $field = $field[1];
            }
            
            $str = substr_replace($str,$field.'.',0,1);
            
            $str = explode('.',$str);
            $str = $this->eprep($str[0]).'.'.$this->eprep($str[1]);         
        }
        else { $str = $this->eprep($str); }
        return $str;
    }
    
    
    
    // This parses out syntax related to field names
    public function prep($str)
    {
        foreach (ZXC::$parser['field'] AS $key => $func)
        {
            if ($this->prefix($str,$key))
            {
                $use_func = $func;
                break;
            }
        }
                 
        if (strstr($str,'|') != FALSE)
        {
            $str = explode('|',$str);
            $alias = ' AS '.$this->eprep($str[1]);
            $str = $str[0];
        }

        if ($str != '*') { $str = $this->rep_num($str); }
        
        if ($use_func)
        {
            $str = str_replace('$',$str,$use_func);
        }

        return $str.$alias;
    }
    
    // This turns a field name into a prepared field name
    public function eprep($str)
    {
        if (strpos($str,'`') == false) { $str =  '`'.$str.'`'; }
        return $str;
    }

    
    // This turns a value into a prepared value.
    public function prep_val($str)
    {
        $subquery = $this->subquery($str);
        if ($subquery) { return $subquery; }
        
        if (!is_array($str))
        {
             $str = ZXC::$LINK->quote($str); 
        }
         
        return $str;
    }
    
    // ---------------------- Syntax Functions -------------------- //
    
    // This checks a string to see if it begins with a pattern, and replaces it.
    public function prefix(&$str,$find)
    {
        $len = strlen($find);
        if (substr($str,0,$len) == $find)
        {
            $str = substr($str,$len);
            return true;
        }
    }

    // This checks a string to see if it ends with a pattern, and replaces it.
    public function suffix(&$str,$find)
    {
        $len = strlen($find);
        if (substr($str,-$len,$len) == $find)
        {
            $str = substr($str,0,-$len);
            return true;
        }
    }
    
    // --------------------- JOIN Functions ---------------------- //
    
    // This function turns a string into an array based on *multiple* explode conditions.
    protected function x_plode($chars,$haystack)
    {
        $arr[] = $tok = strtok($haystack,$chars);
        while ($tok !== false) 
        {
            $arr[count($arr)] = $tok = strtok($chars); 
        }
        array_pop($arr); 
        return $arr; 
    }
    
    // This function gets the positions of a substring inside a larger string.
    protected function all_pos($needle,$haystack)
    {
        for ($i = 0; $i < strlen($haystack); $i++)
        {
            $pos = strpos($haystack,$needle,$i);
            if ($pos !== FALSE) 
            {
                $i = $pos; 
                $result[$pos] = $needle; 
            }
        }
        return $result;
    } 

    // This function parses join conditions (USING, ON)
    protected function j_parse($j)
    {
        $out = 'USING';
        if (strpos($j,'=') != false) 
        {
            $out = 'ON'; 
        }
        
        $j = explode(',',$j);
        
        $clause = Array();
        foreach ($j AS $x) 
        {
            $x = explode('=',$x);
            $field = $this->prep($x[0]);
         
            if ($out == 'ON') 
            {
                $field .= ' = '.$this->prep($x[1]);
            } 
            
            $clause[] = $field;
        }
        return $out.' ('.implode(', ',$clause).')';
    }
    
    // ------------------- MySQL Functions --------------------- //
   
    // This parses a query before outputting it.
    public function query_parse($str)
    {
        if (ZXC::$CONF['debug_backticks'] == FALSE)
        {
            if (is_array($str) == TRUE)
            {
                foreach($str AS $key => $value) { $str[$key] = str_replace('`','',$value); }
            }
            else { $str = str_replace('`','',$str); }
        }
        return $str;
    }
    
    // This outputs a query for debugging purposes.
    public function output()
    {
        $x = $this->query_parse($this->_query);
        if (is_array($x) == TRUE) { echo '<div class="ZXC"><pre">'; print_r($x); echo '</pre></div>'; }
        else { echo '<div class="ZXC">'.$x.'</div><br /><br />'; }
    }
    
    // This runs the prepared MySQL Query.
    public function SQL($ZXC)
    {
        if (ZXC::$CONF['log_queries']) { ZXC::$QUERY[] = $ZXC->_query; }
        
        if (ZXC::$CONF['allow_debug'])
        {
            if ($ZXC->_print == 'one')
            {
                $this->output();
            }
        }
        
        if (ZXC::$CONF['debug_all'] == TRUE && ZXC::$CONF['allow_debug_all'] == TRUE) 
        {
            $this->output(); 
        }
        
        if ($ZXC->_mode && $ZXC->_mode != 'sel')
        {
            $result = ZXC::$LINK->exec($ZXC->_query);
        }
        
        // return if there is not supposed to be a result (update/delete)
        if ($ZXC->_no_result) { return; }
        
        // return insert id on inserts/alters.
        if ($ZXC->_return_id) { return ZXC::$LINK->lastInsertId(); }
        
        $type = constant('PDO::FETCH_'.ZXC::$CONF['result_type']);

        foreach (ZXC::$LINK->query($ZXC->_query,$type)?:Array() as $x)
        {
            
            // Autofix array if there's only one column
            if (count($x) == 1 && ($this->_autofix || $this->_end_type == 'col' || $this->_end_type == 'the')) 
            {
                 $keys = array_keys($x); 
                 $row[] = $x[$keys[0]]; 
            } 
            else 
            {
                 $row[] = $x; 
            }
            
        }

        // Autofix array if there's only one row
        if (count($row) == 1 && ($this->_autofix || $this->_end_type == 'row' || $this->_end_type == 'the')) 
        {
            $row = $row[0]; 
        }
        
        // Revert database if you need to
        if ($ZXC->revert_db)
        {
            ZXC::REVERT_DB();
        }
        
        // Return a blank array instead of null if go(true), col(true), row(true)
        if ($this->_return_blank_array && !$row)
        {
            return Array();
        }

        return $row;
    }
}

if ($_GET['debug'] == 1)
{
    ZXC::CONF('debug_all',true);
}