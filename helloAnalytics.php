<?php
ini_set('max_execution_time', 0);
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-Transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

    <!-- head -->
    <head>
        <title>Statistiques Google Analytics</title>
        
        <!-- Meta -->
        <meta http-equiv="content-type" 
              content="text/html;charset=utf-8" />
        
        <!-- CSS -->
        <link rel="stylesheet" type="text/css" href="css/helloAnalytics.css?<?php echo filemtime('css/helloAnalytics.css'); ?>" />

        <script type='text/javascript' 
                src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js">
        </script>

    </head>
        
    <!-- body -->
    <body>
        
        <h1></h1>
        <div id="graphs"></div>

        <!-- GIF de chargement -->
        <div id="loading" style="display:none;">
            <p><img src="images/loading.gif" alt="chargement en cours"/></p>
        </div>
        
        <!-- Script -->
        <script type="text/javascript">

            // a appeler une 1ere fois avec le nombre d'items
            // a rappeler ensuite avec "-1" pour decrementer le compteur
            function showLoadingImg(itemsNb)
            {
                // creation du compteur statique
                if(typeof showLoadingImg.counter == 'undefined')
                    {
                        showLoadingImg.total = itemsNb;
                        showLoadingImg.counter = itemsNb;
                        $('#loading').attr('style', 'display:block;');
                    }
                    
                    if (itemsNb == -1)
                    {
                        showLoadingImg.counter -= 1;
                        // quand le dernier item a appelé cete fonction, 
                        // on n'affiche plus l'image de chargement'
                        if (showLoadingImg.counter == 0)
                            $('#loading').attr('style', 'display:none;');
                        
                        return (showLoadingImg.total - showLoadingImg.counter);
                    }
            }

            function doAjax(items, i, async_mode)
            {
                $.ajax
                ({
                    url : 'actions/list_graph.php',
                    dataType : "html",
                    async : async_mode,
                    data : {type : 'recupGraph',
                            id : items[i].id,
                            name : items[i].name},

                    success : function (data2)
                    {
                        divId = showLoadingImg(-1);
                        d = document.createElement('div');
                        d.id = divId;
                        $(d).html(data2);

                        $('#graphs').append(d);
                        
                        doAjax(items, ++i, async_mode);
                        
                    }
                })
            }
            
            function showAccountName(username)
            {
                $("h1").html("Compte : " + username);
            }
            
            window.onload = function ()
            {
                // attention, si var = true, pauvre serveur Apache souffre
                // mais si false, tout s'affiche en 1 fois apres que tout soit chargé
                async_mode = true;
                
                $.ajax({
                    url : 'actions/list_graph.php',
                    dataType : "json",
                    async : async_mode,
                    data : {type : 'recupItems'},
                    success : function (data)
                    {
                        var items = data.items;

                        showAccountName(data.username);
                        showLoadingImg(items.length);
                        
                        // doAjax s'appellera recursivement pour traites tous les sites
                        doAjax(items, 0, async_mode);

                    }
                })
            }

    </script>

    </body>
</html>