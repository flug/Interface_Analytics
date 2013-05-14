<?php
//ini_set('max_execution_time', 0);
require_once '../google-api-php-client/src/apiClient.php';
require_once '../google-api-php-client/src/contrib/apiAnalyticsService.php';
require_once '../functions.php';

session_start();

if (isset($_GET['type']))
{
    switch ($_GET['type'])
    {
        case "recupItems" :
            recupItems();
            break;
        case "recupGraph" :
            recupGraph();
            break;
        default :
            break;
    }
}


?>
