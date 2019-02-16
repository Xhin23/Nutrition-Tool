<?php
Class Weight 
{
        private function w()
        {
            return ZXC::sel('id,pos,amt,name,weight/weights')->sort('pos++');
        }
    
    public function by_id($id)
    {
        return $this->w()->where('id',$id)->go();
    }
}