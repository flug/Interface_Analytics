<?php
require_once 'google-api-php-client/src/apiClient.php';
require_once 'google-api-php-client/src/contrib/apiAnalyticsService.php';

session_start();

$print = '';
$connected = null;
$client = new apiClient();
$client->setApplicationName('Hello Analytics API Sample');

// Visit https://code.google.com/apis/console?api=analytics to generate your
// client id, client secret, and to register your redirect uri.
//$client->setClientId('682399372086.apps.googleusercontent.com');
//$client->setClientSecret('_HhUyYCiYMyL_lfp41OdyJGL');
//$client->setRedirectUri('http://localhost/google-api-php-client/examples/analytics/helloAnalytics.php');
//$client->setDeveloperKey('AIzaSyCD9umZVs9oQYU7EaUtx8Pvo0leOPy1RFI');
$client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));

// Magic. Returns objects from the Analytics Service instead of associative arrays.
$client->setUseObjects(true);

// si cette page s'ouvre au retour de l'identification sur la page de Google
if (isset($_GET['code']))
{
    $client->authenticate();
    $_SESSION['token'] = $client->getAccessToken();
    $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_GET['errmsg']) && $_GET['errmsg'] == 'non_authorized')
{
    echo '<h4 style="color:red;">/!\ Ce compte n\'est pas authorisé à faire des requetes Google Analytics.<br />/!\ Veuillez changer de compte.</h4>';
}

// si un token a deja ete cree
if (isset($_SESSION['token']))
{
    if (isset($_GET['change']) && $_GET['change'] == 'yes')
        $client->revokeToken();
    else
        $client->setAccessToken($_SESSION['token']);
}

// si aucun token n'a ete trouve
if (!$client->getAccessToken())
{
    $print .= 'Vous n\'êtes <span style="font-weight:bold">pas connecté.</span><br />';
    $print .= 'Veuillez vous connecter sur votre <span style="text-decoration:underline">compte Google Analytics</span> avec ce bouton :<br />';
    
    $buttonUrl = $client->createAuthUrl();
    $connected = false;
}
else
{
    $analytics = new apiAnalyticsService($client);
    try 
    {
        $profiles = $analytics->management_profiles->listManagementProfiles("~all", "~all");
    }
    catch (apiServiceException $e)
    {
        $client->revokeToken();
        $_SESSION = array();
        session_destroy();
        header('Location: index.php?errmsg=non_authorized');
    }
    $username = $profiles->getUsername();
    $print .= 'Vous êtes connecté avec le compte :<br />';
    $print .= '--> <span style="font-weight:bold">' . $username . '</span><br />';
    $print .= '<a href="index.php?change=yes" style="font-size:0.8em;">Changer de compte ?</a>';

    $connected = true;
}


?>