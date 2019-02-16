<?php
Class Ajax
{
    public $db;
    public function get_results($data)
    {
        $data = urldecode($data);
        parse_str($data,$data);
        
        $fields = Array('name','id');
        
        if ($data['rank'] != 'ratio' && $data['rank'])
        {
            $fields[] = $data['rank']; 
        }
        
        $this->db->data = $data;
        $this->db->select($fields);
        
        if ($data['rank'] == 'ratio')
        {
            $this->db->add_fields($this->db->to_ratio($data['ratio_num'],$data['ratio_denom']));
        }
        if ($data['fields'])
        {
            $this->db->add_fields($data['fields']);
        }
        
        $this->db->filter();
        $results = $this->db->results();
        $count = $results['count'];
        $results = $results['results'];
        
        if (!$results) { return Array('error' => 'no_results'); }
        
        $keys = array_keys($results[0]);
        
        return Array('results' => $results, 'count' => $count, 'keys' => $keys);
    }
    
    public function get_nutrition($data)
    {
        $id = $data['id'];
        
        $this->db->select();
        $this->db->by_id($id);    
        $row = $this->db->ZXC->row();
        
        $read = new Weight();
        $weights = $read->by_id($id);
        return Array('data' => $row, 'weights' => $weights, 'id' => $id);
    }
    
    public function compare_foods($data)
    {
        $ids = array_keys($data);
        $this->db->select();
        $this->db->by_ids($ids);
        $data = $this->db->ZXC->go();
        $fields = array_keys($data[0]);
        
        $names = Array();
        foreach ($data?:Array() AS $row)
        {
            $names[] = $row['name'];
        }
        
        $data = pair($data,'id');
        
        return Array('data' => $data, 'names' => $names, 'fields' => $fields, 'ids' => $ids);
    }
}