<?php

function recupItems()
{
    $client = new apiClient();
    $client->setApplicationName('Hello Analytics API Sample');
    $client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));
    $client->setUseObjects(true);
    $client->setAccessToken($_SESSION['token']);

    $analytics = new apiAnalyticsService($client);
    $profiles = $analytics->management_profiles->listManagementProfiles("~all", "~all");
    $items = $profiles->getItems();
    $itemsTab = array();
    $i = 0;
    foreach ($items as $item)
    {
        $itemsTab[$i]["id"] = $item->getId();
        $itemsTab[$i]["name"] = $item->getName();
        $i++;
    }
    
    echo json_encode(array("username" => $profiles->getUsername(),
                           "items"    => $itemsTab));
}

function recupGraph()
{
    if (isset($_GET['id']) && isset($_GET['name']))
    {
        $results = getResults($_GET['id'], $_GET['name']);
        printResults($results);    
    }
}


// [ne fonctionne que pour les mois actuellement] 
// transforme, par ex, 05 2012 en 'May+12', pour l'url google chart
function getDatesLabel(&$rows)
{
    $monthsCorresp = array('01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
        '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec');
    $datesLabel = '';

    foreach ($rows as $row)
        $datesLabel.= '|' .
                $monthsCorresp[$row[0]] .
                '+' .
                preg_replace('"^20"', '', $row[1], 1);

    return $datesLabel;
}

// fonctionne pour seulement 2 series de valeurs
// (ex : visites totales et visites naturelles)
function getMinMaxValue(&$rows, &$rows2)
{
    $values = array();
    $values['min'] = 0;
    $values['max'] = 0;

    $values['min'] = $rows[0][2];
    // boucle pour visites totales et recherches naturelles dans 8 derniers mois
    foreach ($rows as $row)
    {
        $values['min'] = ($row[2] < $values['min']) ? $row[2] : $values['min'];
        $values['min'] = ($row[3] < $values['min']) ? $row[3] : $values['min'];
        $values['max'] = ($row[2] > $values['max']) ? $row[2] : $values['max'];
        $values['max'] = ($row[3] > $values['max']) ? $row[3] : $values['max'];
    }

    // boucle pour recherches naturelles dans annee n-1 sur meme periode
    foreach ($rows2 as $row)
    {
        $values['min'] = ($row[2] < $values['min']) ? $row[2] : $values['min'];
        $values['max'] = ($row[2] > $values['max']) ? $row[2] : $values['max'];
    }

    //convertit ces valeurs au palier du dessus/dessous 
    //ex : 15698 devient 16000 pour max ; 359 devient 350 pour min etc.
    // 1) Valeur Min
    $len = (strlen($values['min']) - 1);
    $div = 1;
    for ($i = 0; $i < $len; $i++)
        $div *= 10;
    $toSubstract = ($values['min'] % $div);
    $values['min'] -= $toSubstract;

    // 2) Valeur Max   [A FAIRE]
//    $len = (strlen($values['max']) - 2);
//    $div = 1;
//    for ($i = 0; $i < $len; $i++)
//        $add *= 10;
//    $values['max'] + $add;
//    if ($values['max'])
//    
//    
//    $part_to_increase = ($values['max'] % $div);
//    $add = $div / 10;
//
//    
//    $toSubstract = ($values['min'] % $div);
//    $values['min'] -= $toSubstract;



    return $values;
}

// recupere le range pour l'url google chart 
function getRange(&$rows, $attr, $minValue, $maxValue)
{
    $range = '';

    if ($attr == 'xr')
    {
        // axe du bas
        $range = '0,0,' . (count($rows) - 1);
        // axes gauche et droite
        $range.= '|1,' . $minValue . ',' . $maxValue;
        $range.= '|2,' . $minValue . ',' . $maxValue;
    }
    elseif ($attr == 'ds')
    {
        $range .= $minValue . ',' . $maxValue;
        $range .= ',' . $minValue . ',' . $maxValue;
    }

    return $range;
}

// recupere les donnees pour l'url google chart 
// [ne fonctionne que pour les mois actuellement] 
function getGraphData(&$rows, &$rows2)
{
    $dataString = '';
    $organicSearch = '';
    $totalVisits = '';
    $organicSearchN_1 = '';

    $i = 0;
    $nbRows = (count($rows) - 1);
    // boucle pour visites totales et recherches naturelles dans 12 derniers mois
    foreach ($rows as $row)
    {
        $organicSearch .= $row[3];
        $totalVisits .= $row[2];

        // si dernier mois, pas de virgule 
        if ($i < $nbRows)
        {
            $organicSearch .= ',';
            $totalVisits .= ',';
        }
        $i++;
    }
    // boucle pour recherches naturelles dans annee n-1 sur meme periode
    $i = 0;
    foreach ($rows2 as $row)
    {
        $organicSearchN_1 .= $row[2];

        // si dernier mois, pas de virgule 
        if ($i < $nbRows)
            $organicSearchN_1 .= ',';
        $i++;
    }

    $dataString .= $organicSearch . '|' . $totalVisits . '|' . $organicSearchN_1;
    return $dataString;
}

// remplit l'array pour l'url google chart (12 derniers mois)
// voir : "https://developers.google.com/chart/image/docs/chart_params?hl=fr"
function fillGraphArray(&$rows, &$rows2)
{
    $minMaxValues = getMinMaxValue($rows, $rows2);
    $opts = array(
        // legende en bas (ex: 0:|Jan+12|Feb+12|Mar+12|Apr+12|May+12|Jun+12|Jul+12|Aug+12|Sep+12|Oct+12)
        'chxl' => '0:' . getDatesLabel($rows),
        // position labels axes (ex: 0,0,1,2,3,4,5,6,7,8,9)
        //'chxp' => '',
        // 'range' pour chaque axe (ex: 0,0,9|1,0,20|2,0,20)
        'chxr' => getRange($rows, 'xr', $minMaxValues['min'], $minMaxValues['max']),
        // style label axes (couleur etc.)
        'chxs' => '0,000000,11.5,0,l,676767',
        // axes visibles (ex: x,r,y)(<= abscisse, ordonnée à droite, ordonnée)
        'chxt' => 'x,r,y',
        // dimensions du graphique (ex: 800x200)
        'chs' => '800x200',
        // type de graphique (ex: lc)(<= courbes)
        'cht' => 'lc',
        // couleurs courbes (ex: 3072F3,008000)
        'chco' => '3072F3,008000,BA822D',
        // 'etendue' des donnees barre gauche , barre droite (ex: 0,20,0,20)
        'chds' => getRange($rows, 'ds', $minMaxValues['min'], $minMaxValues['max']),
        // donnees des courbes (ex: t:1,2,3,4,5,6,7,8,9,10|12,12,12,16,16,14,15,14,15,16)
        'chd' => 't:' . getGraphData($rows, $rows2),
        // nom chaque courbe (ex: Visites+recherches+naturels|Total+visites)
        'chdl' => 'Visites+recherches+naturelles|Total+visites|Recherches+naturelles+année+n-1',
        // position de la legende (ex: b)(signifie en bas)
        'chdlp' => 'b',
        // style grille : nb de verticales et d'horizontales (ex: 11,12,5,4)
        'chg' => (100 / (count($rows) - 1)) . ',10' . ',5,4', // [A CHANGER]
        // style courbes (pointillé ou solide) (ex: 1|1) 
        'chls' => '1|1',
        // titre du graphique
        'chtt' => 'Progression+des+visites+(12+derniers+mois)'
    );

    return $opts;
}


// fonctionne que pour les semaines
function getDates2monthsLabel(&$rows)
{
    $monthsCorresp = array('01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
        '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Aug',
        '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dec');
    $datesLabel = '';

    foreach ($rows as $row)
    {
        $dayNb = date('d', strtotime($row[1] . 'W' . $row[0]));
        $monthNb = date('m', strtotime($row[1] . 'W' . $row[0]));        
        
        $datesLabel.= '|' .
                $monthsCorresp[$monthNb] .
                '+' .
                $dayNb;
    }

    return $datesLabel;
}


// recupere les donnees pour l'url google chart 
// [ne fonctionne que pour les mois actuellement] 
function getGraph2monthsData(&$rows)
{
    $dataString = '';
    $organicSearch = '';
    $totalVisits = '';

    $i = 0;
    $nbRows = (count($rows) - 1);
    // boucle pour visites totales et recherches naturelles dans 12 derniers mois
    foreach ($rows as $row)
    {
        $organicSearch .= $row[3];
        $totalVisits .= $row[2];

        // si dernier mois, pas de virgule 
        if ($i < $nbRows)
        {
            $organicSearch .= ',';
            $totalVisits .= ',';
        }
        $i++;
    }

    $dataString .= $organicSearch . '|' . $totalVisits;
    return $dataString;
}


// fonctionne pour seulement 2 series de valeurs
// (ex : visites totales et visites naturelles)
function getMinMaxValue2months(&$rows)
{
    $values = array();
    $values['min'] = 0;
    $values['max'] = 0;

    $values['min'] = $rows[0][2];
    // boucle pour visites totales et recherches naturelles dans 8 derniers mois
    foreach ($rows as $row)
    {
        $values['min'] = ($row[2] < $values['min']) ? $row[2] : $values['min'];
        $values['min'] = ($row[3] < $values['min']) ? $row[3] : $values['min'];
        $values['max'] = ($row[2] > $values['max']) ? $row[2] : $values['max'];
        $values['max'] = ($row[3] > $values['max']) ? $row[3] : $values['max'];
    }

    //convertit ces valeurs au palier du dessus/dessous 
    //ex : 15698 devient 16000 pour max ; 359 devient 350 pour min etc.
    // 1) Valeur Min
    $len = (strlen($values['min']) - 1);
    $div = 1;
    for ($i = 0; $i < $len; $i++)
        $div *= 10;
    $toSubstract = ($values['min'] % $div);
    $values['min'] -= $toSubstract;

    return $values;
}




// remplit l'array pour l'url google chart (2 derniers mois)
// voir : "https://developers.google.com/chart/image/docs/chart_params?hl=fr"
function fillGraph2monthsArray(&$rows)
{
    $minMaxValues = getMinMaxValue2months($rows);
    $opts = array(
        // legende en bas (ex: 0:|Jan+12|Feb+12|Mar+12|Apr+12|May+12|Jun+12|Jul+12|Aug+12|Sep+12|Oct+12)
        'chxl' => '0:' . getDates2monthsLabel($rows),
        // position labels axes (ex: 0,0,1,2,3,4,5,6,7,8,9)
        //'chxp' => '',
        // 'range' pour chaque axe (ex: 0,0,9|1,0,20|2,0,20)
        'chxr' => getRange($rows, 'xr', $minMaxValues['min'], $minMaxValues['max']),
        // style label axes (couleur etc.)
        'chxs' => '0,000000,11.5,0,l,676767',
        // axes visibles (ex: x,r,y)(<= abscisse, ordonnée à droite, ordonnée)
        'chxt' => 'x,r,y',
        // dimensions du graphique (ex: 800x200)
        'chs' => '800x200',
        // type de graphique (ex: lc)(<= courbes)
        'cht' => 'lc',
        // couleurs courbes (ex: 3072F3,008000)
        'chco' => '3072F3,008000,BA822D',
        // 'etendue' des donnees barre gauche , barre droite (ex: 0,20,0,20)
        'chds' => getRange($rows, 'ds', $minMaxValues['min'], $minMaxValues['max']),
        // donnees des courbes (ex: t:1,2,3,4,5,6,7,8,9,10|12,12,12,16,16,14,15,14,15,16)
        'chd' => 't:' . getGraph2monthsData($rows),
        // nom chaque courbe (ex: Visites+recherches+naturels|Total+visites)
        'chdl' => 'Visites+recherches+naturelles|Total+visites',
        // position de la legende (ex: b)(signifie en bas)
        'chdlp' => 'b',
        // style grille : nb de verticales et d'horizontales (ex: 11,12,5,4)
        'chg' => (100 / (count($rows) - 1)) . ',10' . ',5,4', // [A CHANGER]
        // style courbes (pointillé ou solide) (ex: 1|1) 
        'chls' => '1|1',
        // titre du graphique
        'chtt' => 'Progression+des+visites+(2+derniers+mois)'
    );

    return $opts;
}


// cree l'url pour google chart
function createGraphURL(&$graphArray)
{
    $graphUrl = 'http://chart.apis.google.com/chart';

    $graphUrl .= '?chxl=' . $graphArray['chxl']     //0:|Jan+12|Feb+12|Mar+12|Apr+12|May+12|Jun+12|Jul+12|Aug+12|Sep+12|Oct+12|1:|0|2|4|6|8|10|12|14|16|18|2:|0|2|4|6|8|10|12|14|16|18'
            //.'&chxp=' . $graphArray['chxp']     //1,0,2,4,6,8,10,12,14,16,18|2,0,2,4,6,8,10,12,14,16,18'
            . '&chxr=' . $graphArray['chxr']     //0,0,10|1,0,20|2,0,20'
            . '&chxs=' . $graphArray['chxs']     //0,000000,11,0,l,676767'
            . '&chxt=' . $graphArray['chxt']     //x,r,y'
            . '&chs=' . $graphArray['chs']       //=704x190'
            . '&cht=' . $graphArray['cht']       //=lc'
            . '&chco=' . $graphArray['chco']     //=3366CC,008000'
            . '&chds=' . $graphArray['chds']     //=0,20,0,20'
            . '&chd=' . $graphArray['chd']       //=t:1,2,3,4,5,6,7,8,9,10|12,12,12,16,16,14,15,14,15,16'
            . '&chdl=' . $graphArray['chdl']     //=Google|Tous'
            . '&chdlp=' . $graphArray['chdlp']    //b'
            . '&chg=' . $graphArray['chg']       //20,25'
            . '&chls=' . $graphArray['chls']     //1|1'
            . '&chtt=' . $graphArray['chtt'];     //Progression+recherche+Google'

    return $graphUrl;
}


// pour donnees GA : genere les filters pour get les data
// style : pas de (not provided) ; [plus tard : pas de keywords contenus ds le nom du site]
function getFilters($siteName)
{            
    $filters = 'ga:keyword!=(not provided)';

    
//            // ex: http://www.news.cnn-fr.com/bonjour.php
//            // en cours !
//            
//            $cleanSiteName = parse_url($siteName);
//            $namesTab = preg_split('#^[][\s.-]#i', $CleanSiteName['host'], -1, PREG_SPLIT_NO_EMPTY);
//            foreach ($namesTab as $name)
//                $filters .= ';ga:keyword!@' . $name;
//            

    return $filters;
}


// recupere les données de Googla Analytics
function getResults($profileId, $profileName)
{
    $client = new apiClient();
    $client->setApplicationName('Hello Analytics API Sample');
    $client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));
    $client->setUseObjects(true);
    $client->setAccessToken($_SESSION['token']);
    $analytics = new apiAnalyticsService($client);


    $results_tab = array();
    $periode_start = date('Y-m', strtotime('-12 months')) . '-01';
    $periode_end = date('Y-m-t', strtotime('last month'));

    // resultats visites totales et recherches naturelles
    // pour l'annee n sur les 12 derniers mois
    $results_tab['n'] = $analytics->data_ga->get(
                            'ga:' . $profileId,
                            $periode_start,
                            $periode_end, 
                            'ga:visits,ga:organicSearches',
                            array('dimensions' => 'ga:month,ga:year',
                                    'sort'       => 'ga:year')
                            );
    
    
    // resultats visites totales et recherches naturelles
    // pour l'annee n sur les 2 derniers mois
    $samedi_dernier = date('Y-m-d', strtotime('last saturday'));
    $dimanche_2_mois = date('Y-m-d', strtotime($samedi_dernier . ' -62 days'));
    $results_tab['last2months'] = $analytics->data_ga->get(
                                    'ga:' . $profileId,
                                    $dimanche_2_mois, 
                                    $samedi_dernier,
                                    'ga:visits,ga:organicSearches',
                                    array('dimensions' => 'ga:week,ga:year',
                                            'sort'       => 'ga:year')
                                    );
    
    

    // resultats recherches naturelles
    // pour les 12 mois precedents
    $results_tab['n-1'] = $analytics->data_ga->get(
                            'ga:' . $profileId,
                            date('Y-m-d', strtotime('-12 months', strtotime($periode_start))),
                            date('Y-m-d', strtotime('-12 months', strtotime($periode_end))),
                            //date('Y-m-t', strtotime('-13 months')), 
                            'ga:organicSearches',
                            array('dimensions' => 'ga:month,ga:year',
                                    'sort'       => 'ga:year')
                            );

    // resultats top15 keywords sur 12 derniers mois
    // pas de (not provided), pas de keywords contenus ds le nom du site
    $results_tab['keywords'] = $analytics->data_ga->get(
                            'ga:' . $profileId,
                            $periode_start,
                            $periode_end, 
                            'ga:organicSearches',
                            array('dimensions' => 'ga:keyword',
                                    'sort'       => '-ga:organicSearches',
                                    'filters'    => getFilters($profileName),
                                    'max-results' => '20')
                            );            

    return $results_tab;
}


// pour printResults : affiche une ligne du tableau de mots-clés
function print_keywords_table(&$kwRows)
{
    ?>
    <div class="droite">
        <table class ="table table-striped table-bordered table-condensed">
            <tbody>
                <tr>
                    <th colspan="10">
                        Top 15 Mots-cl&eacute;s (12 derniers mois)
                    </th>
                </tr>
                
                <?php
                if (count($kwRows))
                {
                    $i = 0;
                    foreach ($kwRows as $row)
                    {
                        if ($i == 0 || $i == 10)
                            echo '<tr>';

                        echo '<td>' .
                            ($i + 1) . '.' .
                            ' <span style="font-weight:bold">' . $row[0] . '</span>' .
                            ' (' . $row[1] . ')' .
                            '</td>';

                        if ($i == 9 || $i == 19)
                            echo '</tr>';

                        $i++;
                    }
                }
                ?>

            </tbody>
        </table>
    </div>

    <?php
}

// affiche resultats
function printResults(&$results)
{
    if (count($results['n']->getRows()) > 0)
    {
        $rows1 = $results['n']->getRows();
        $rows2 = $results['n-1']->getRows();
        $kwRows = $results['keywords']->getRows();
        
        
        $last2monthsRows = $results['last2months']->getRows();
        $graph2monthsArray = fillGraph2monthsArray($last2monthsRows);
        $graph2monthsUrl = createGraphURL($graph2monthsArray);
        
        
        $graphArray = fillGraphArray($rows1, $rows2);
        $graphUrl = createGraphURL($graphArray);
        $profileName = strtoupper($results['n']->getProfileInfo()->getProfileName());


        ?>

        <!-- affichage (nom / graphique / mots cles / barre) --> 

            <!-- nom du site -->
            <h4 style="margin-bottom:0px;">
                PROFILE NAME : <?php echo $profileName ?> 
            </h4>
                
            <!-- graphique 2 mois -->
            <div class="gauche">
                <img src="<?php echo $graph2monthsUrl ?>"
                            alt="graphique progression google"
                            style="width:100%;"/>
            </div>
            
            <!-- graphique 12 mois -->
            <div class="gauche">
                <img src="<?php echo $graphUrl ?>"
                            alt="graphique progression google"
                            style="width:100%;"/>
            </div>

            <!-- tableau de mots clés -->
            <?php print_keywords_table($kwRows); ?>

            <!-- barre -->
            <hr />
  

        <?php


    }
    else
        print '<p>No results found.</p>';
}


?>
