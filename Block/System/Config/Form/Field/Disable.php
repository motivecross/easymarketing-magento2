<?php
namespace Motive\Easymarketing\Block\System\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class Disable extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(AbstractElement $element)
    {
        //$element->setDisabled('disabled');
        $element->setData('readonly', 1);
        return $element->getElementHtml();

    }
}