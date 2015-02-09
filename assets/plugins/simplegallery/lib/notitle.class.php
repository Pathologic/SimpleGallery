<?php namespace SimpleGallery;

require_once(MODX_BASE_PATH . 'assets/plugins/simplegallery/lib/controller.class.php');

class _sgData extends sgData
{
    public function save($fire_events = null, $clearCache = false)
    {
        if ($this->newDoc) $this->field['sg_title'] = " ";
        return parent::save($fire_events, $clearCache);
    }
}

class notitleController extends sgController
{
    public function __construct(\DocumentParser $modx)
    {
        parent::__construct($modx);
        $this->data = new \SimpleGallery\_sgData($this->modx);
    }
}