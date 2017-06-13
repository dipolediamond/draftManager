<?php
/**
 * class.draftManager.php
 *  
 */

  class draftManagerClass extends PMPlugin {
    function __construct() {
      set_include_path(
        PATH_PLUGINS . 'draftManager' . PATH_SEPARATOR .
        get_include_path()
      );
    }

    function setup()
    {
    }

    function getFieldsForPageSetup()
    {
    }

    function updateFieldsForPageSetup()
    {
    }

  }
?>