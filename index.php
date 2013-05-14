<?php
ini_set('max_execution_time', 0);
require_once('init.php');

// Important : instructions
/*
 * Pour changer les identifians de connexion Google utilisé dans cette appli,
 * allez sur le site Api Console et connectez-vous avec votre compte Google Analytics.
 * Activez le service si ce n'est pas deja fait 
 * Recuperer les infos (voir plus bas) et inscrivez-les dans le fichier :
 * "google-api-php-client/src/config.php"
 * Voici les elements a renseigner : 
 * -> oauth2_client_id
 * -> oauth2_client_secret
 * -> oauth2_redirect_uri (chemin absolu vers cette page)   
 * -> developer_key
 */

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-Transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title>Statistiques Google Analytics</title>
	<meta http-equiv="content-type" 
		content="text/html;charset=utf-8" />
        <link rel="stylesheet" type="text/css" href="css/index.css" />

</head>

<body>
    
    <div id="corps">
        
        <h1>Statistiques Google Analytics</h1>
        <h3>Tous vos sites sur une page !</h3>
        
        <br />
        Pour chaque site, vous trouverez un graphique présentant :
        <ul>
            <li>Le nombre total de visites des 12 derniers mois</li>
            <li>Le nombre de visites provenant de <b>recherches naturelles</b>, les 12 derniers mois</li>
            <li>Le nombre de visites provenant de <b>recherches naturelles</b>, sur la même période l'année précédente</li>
        </ul>

        <br />
              
        <p>
            Pour récupérer vos graphiques, cliquez sur le lien ci-dessous.<br />
            Il vous faudra vous connecter avec votre compte Google Analytics
            (en haut à droite), puis cliquer sur "Autoriser l'accès".<br />
        </p>
        
        <span style="color:red;text-decoration:underline"> NOUVEAU : les graphiques sont chargés au fur et à mesure ==> plus d'attente !</span>
        
        <br />
        
        <div id="connexion">

            <p>
                <?php echo $print ?>
            </p>
            <br />

            <?php if ($connected === false) { ?>
                <a href ="<?php echo $buttonUrl ?>">Se connecter</a>
                
            <?php } elseif ($connected === true) { ?>
                <form name="input" action="helloAnalytics.php">
                    <input type="submit" id="button" value="Lancer l'application" />
                </form>
            <?php } ?>


        </div>
         
    </div>
    </body>
</html>