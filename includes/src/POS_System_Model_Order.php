<?php

class POS_System_Model_Order extends Mage_Core_Model_Abstract
{


	public function _construct()
    {
        parent::_construct();
        $this->_init('retailexpress/order');
    }


}
