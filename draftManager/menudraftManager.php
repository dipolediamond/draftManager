<?php
global $G_TMP_MENU;
global $RBAC;

// HOME MODULE
if ($RBAC->userCanAccess('PM_DRAFTMANAGER') == 1) {
  $G_TMP_MENU->AddIdRawOption("ID_DRAFTMANAGER_MNU_01", "draftManager/main", "Draft Manager");
}

?>