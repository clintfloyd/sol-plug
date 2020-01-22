<?php

class WDIModelSettings_wdi{

  public $active_tab = 'configure';

  public function __construct(){
    $this->activeTab = WDILibrary::get('form_action', 'configure');
  }
}