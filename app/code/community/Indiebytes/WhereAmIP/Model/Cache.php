<?php
class Indiebytes_WhereAmIP_Model_Cache extends Enterprise_PageCache_Model_Container_Abstract
{
    protected function _getCacheId()
    {
        return 'CONTAINER_' . md5(
            $this->_placeholder->getAttribute('cache_id') .
            $this->_getCookieValue(Enterprise_PageCache_Model_Cookie::COOKIE_CUSTOMER, ''));
    }

    protected function _renderBlock()
    {
        $block = $this->_placeholder->getAttribute('block');
        $template = $this->_placeholder->getAttribute('template');

        $block = new $block;
        $block->setTemplate($template);
        $block->setLayout(Mage::app()->getLayout());

        return $block->toHtml();
    }
}