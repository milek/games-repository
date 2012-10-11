<?php

    // includes
    include_once("config.php");
    include_once("includes/types.php");
    include_once("includes/functions.php");

    // lists
    $categories = array();
    $games = array();

    // load categories
    loadCategories($categories);

    // load games
    loadRetailGames($games);

    // parse achievements from Xbox.com, new format JSON
    parseAchievementsJson(file_get_contents("data/achievements.json"), $games);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <title><?php echo strip_tags($config['title']); ?></title>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="author" content="lukasz.milewski@gmail.com" />
  <link rel="stylesheet" type="text/css" media="screen" href="styles.css" />
  <script src="jquery.js"></script>
  <script src="jquery.tinysort.js"></script>
</head>
<body>

  <div class="top">
    <h1><?php echo $config['title']; ?></h1>
  </div>

  <tabs>
    <filter id="notreleased">not released</filter>
    <filter id="notowned">not owned</filter>
    <filter class="selected" id="notplayed">not played</filter>
    <filter class="selected" id="rented">rented</filter>
<?php

    $selectedCategory = false;

    foreach ($categories as $category)
    {
?>
    <tab id="<?= $category->id ?>"<?php if ($category->active) {?> class="active"<?php } ?>><?= $category->name ?></tab>
<?php

    }

?>
  </tabs>

  <div class="main">

<?php

    foreach ($games as $group)
    {
        $i = 1;

?>    <ul id="content<?= $group->name ?>" class="content<?= $group->name == $categories[0]->id ? " active" : "" ?>">
<?php

        foreach ($group->games as $game)
        {
            $addtnl = "";

            if ($game->renter != null)
            {
                $addtnl = " rented";
            }
            else if ($game->released != null && $game->released != "0")
            {
                $addtnl = " notreleased invisible";
            }
            else if ($game->released != null && $game->released == "0")
            {
                $addtnl = " notowned invisible";
            }
            else if ($game->achPoints->value == null || $game->achPoints->value == 0)
            {
                $addtnl = " notplayed";
            }
            else if ($game->achPoints->value == $game->achPoints->outOf)
            {
                $addtnl = " completed";
            }

            $achPointPercentage = 0;
            if ($game->achPoints->outOf > 0)
            {
                $achPointPercentage = floor($game->achPoints->value / $game->achPoints->outOf * 100);
            }

            $classColorPercentage = floor($achPointPercentage - ($achPointPercentage % 10));

            ob_start("newlines");

?>
      <tile class="game<?= $addtnl ?>">
        <cover>
          <id><?= $i++ ?></id>
          <name><?= $game->getGameName() ?><?php

            if ($game->renter != "")
            {

?>&nbsp; <span class="renter"><?= strtoupper($game->renter) ?></span><?php

            }
?>
<?php

            if ($game->released != null && $game->released != "0")
            {

                $released = parseDate($game->released);

?> <span class="release"><?= $released ?></span><?php

            }

?></name>
          <img src="<?= $game->getBoxArt() ?>" />
        </cover>

<?php

            if ($game->achPoints->outOf > 0)
            {
?>
        <achievements>
          <?= $game->achPoints->value ?>&nbsp;/&nbsp;<?= $game->achPoints->outOf ?><br />
          <progressbar><bar class="p<?= $classColorPercentage ?>" style="width: <?= $achPointPercentage ?>%;"><?= $achPointPercentage ?></bar></progressbar>
        </achievements>
<?php

            }
            else
            {
?>
         <bar style="display: none;">0</bar>
<?php

            }

?>
     </tile>
<?php

      ob_end_flush();

  }

?>
    </ul>

<?php } ?>
  <div style="clear: both;"></div></div>

  <div class="footer">
    <div style="float: right"><a href="https://github.com/milek/GamesRepository">Fork it on <strong>GitHub!</strong></a> or <a href="https://bitbucket.org/verdigo/gamesrepository">on <strong>Bitbucket!</strong></a></div>
    <strong>Last modified:</strong> <b><?= date("F d Y H:i:s", filemtime("data/achievements.json")) ?></b><br />
    <strong>Sort tiles:</strong>
    <sort class="selected" id="sOaz">original a-z</sort> |
    <sort id="sOza">original z-a</sort> | 
    <sort id="sNaz">name a-z</sort> |
    <sort id="sNza">name z-a</sort> |
    <sort id="sPaz">completion 0%-100%</sort> |
    <sort id="sPza">completion 100%-0%</sort>
  </div>

  <script>

    var currentTab = '<?= $categories[0]->id ?>';

    function changeClass(tab)
    {
        $('#content' + currentTab).removeClass('active');
        $('#' + currentTab).removeClass('active');

        $('#content' + tab).addClass('active');
        $('#' + tab).addClass('active');

        currentTab = tab;
    }

    function showHide(type)
    {
        $('tile').each(function(index, tile)
        {
            if ($(tile).hasClass(type))
            {
                $(tile).toggleClass('invisible');
            }
        });

        $('#' + type).toggleClass('selected');
    }

    function sort(id,where,desc)
    {
        if (desc != null)
        {
            $('ul#[id^"content"]>tile').tsort(where, {order:"desc"});
        }
        else
        {
            $('ul#[id^="content"]>tile').tsort(where);
        }
        $('sort').removeClass('selected');
        $(id).addClass('selected');
    }

<?php

    foreach ($categories as $category)
    {

?>
    $('#<?= $category->id ?>').click(function(){changeClass('<?= $category->id ?>')});
<?php

    }

?>

    $('#played').click(function(){showHidePlayed()});
    $('#completed').click(function(){showHide('completed')});
    $('#rented').click(function(){showHide('rented')});
    $('#notowned').click(function(){showHide('notowned')});
    $('#notreleased').click(function(){showHide('notreleased')});
    $('#notplayed').click(function(){showHide('notplayed')});

    $('#sOaz').click(function(){sort('#sOaz','id')});
    $('#sOza').click(function(){sort('#sOza','id','desc')});
    $('#sNaz').click(function(){sort('#sNaz','name')});
    $('#sNza').click(function(){sort('#sNza','name','desc')});
    $('#sPaz').click(function(){sort('#sPaz','name');sort('#sPaz','bar')});
    $('#sPza').click(function(){sort('#sPza','name');sort('#sPza','bar','desc')});

  </script>

</body>

