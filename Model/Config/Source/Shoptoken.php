<?php
namespace Motive\Easymarketing\Model\Config\Source;

class Shoptoken
{
    protected function _getElementHtml(AbstractElement $element)
    {
        $element->setDisabled('disabled');
        return $element->getElementHtml();

    }
}