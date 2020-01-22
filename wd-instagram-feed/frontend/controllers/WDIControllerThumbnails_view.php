<?php

class WDIControllerThumbnails_view{

  public function __construct() {
  }

  public function execute($feed_row,$wdi_feed_counter){
    //including model
    require_once(WDI_DIR .'/frontend/models/WDIModelThumbnails_view.php');
    $model = new WDIModelThumbnails_view($feed_row,$wdi_feed_counter);

    //including view
    require_once(WDI_DIR .'/frontend/views/WDIViewThumbnails_view.php');
    $view = new WDIViewThumbnails_view($model);
    $view->display();
  }
}
?>