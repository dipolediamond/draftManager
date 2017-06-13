<?php
$G_MAIN_MENU            = 'processmaker';
$G_ID_MENU_SELECTED     = 'ID_DRAFTMANAGER_MNU_01';
$G_PUBLISH = new Publisher;
$G_PUBLISH->AddContent('view', 'draftManager/mainLoad');
G::RenderPage('publish');
?>