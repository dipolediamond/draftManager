<?php
G::LoadClass("plugin");

class draftManagerPlugin extends PMPlugin
{
  
  
  public function draftManagerPlugin($sNamespace, $sFilename = null)
  {
    $res = parent::PMPlugin($sNamespace, $sFilename);
    $this->sFriendlyName = "draftManager Plugin";
    $this->sDescription  = "Autogenerated plugin for class draftManager";
    $this->sPluginFolder = "draftManager";
    $this->sSetupPage    = "setup";
    $this->iVersion      = 1;
    //$this->iPMVersion    = 2425;
    $this->aWorkspaces   = null;
    //$this->aWorkspaces = array("os");
    
    
    
    return $res;
  }

  public function setup()
  {
    $this->registerMenu("processmaker", "menudraftManager.php");
    
    
  }

  public function install()
  {
    $RBAC = RBAC::getSingleton() ;
    $RBAC->initRBAC();
    $RBAC->createPermision("PM_DRAFTMANAGER");
  }
  
  public function enable()
  {
    
  }

  public function disable()
  {
    
  }
  
}

$oPluginRegistry = &PMPluginRegistry::getSingleton();
$oPluginRegistry->registerPlugin("draftManager", __FILE__);