<?php

class WDIControllerLicensing_wdi {

  public function __construct() {
  }

  public function execute() {
    $task = WDILibrary::get('task', '');
    if ( $task != '' ) {
      if(!WDWLibrary::verify_nonce('licensing_bwg')){
        die('Sorry, your nonce did not verify.');
      }
    }
    if (method_exists($this, $task)) {
      $this->$task($id);
    }
    else {
      $this->display();
    }
  }

  public function display() {
    require_once WDI_DIR . "/admin/models/WDIModelLicensing_wdi.php";
    $model = new WDIModelLicensing_wdi();

    require_once WDI_DIR . "/admin/views/WDIViewLicensing_wdi.php";
    $view = new WDIViewLicensing_wdi($model);
    $view->display();
  }
}