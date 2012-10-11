<?php

    // includes
    include_once("config.php");
    include_once("includes/types.php");
    include_once("includes/functions.php");

    // lists
    $categories = array();
    $games = array();

    // default values
    $filters['rented'] = $_COOKIE['selected_rented'] != null ? $_COOKIE['selected_rented'] == 'true' : true;
    $filters['notplayed'] = $_COOKIE['selected_notplayed'] != null ? $_COOKIE['selected_notplayed'] == 'true' : true;
    $filters['notowned'] = $_COOKIE['selected_notowned'] != null ? $_COOKIE['selected_notowned'] == 'true' : false;
    $filters['notreleased'] = $_COOKIE['selected_notreleased'] != null ? $_COOKIE['selected_notreleased'] == 'true' : false;

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
  <script src="jquery.cookie.js"></script>
  <script src="jquery.tinysort.js"></script>
</head>
<body>

  <div class="top">
    <h1><?php echo $config['title']; ?></h1>
  </div>

  <tabs>
    <filter id="notreleased"<?= $filters['notreleased'] ? ' class="selected"' : ""; ?>>not released</filter>
    <filter id="notowned"<?= $filters['notowned'] ? ' class="selected"' : ""; ?>>not owned</filter>
    <filter id="notplayed"<?= $filters['notplayed'] ? ' class="selected"' : ""; ?>>not played</filter>
    <filter id="rented"<?= $filters['rented'] ? ' class="selected"' : ""; ?>>rented</filter>
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

    $group_index = 0;
    $currentTab = $categories[0]->id;

    foreach ($games as $group)
    {
        $group_index++;
        $i = 1;

        $active = $categories[$group_index - 1]->active;
        if ($active)
        {
            $currentTab = $group->name;
        }

?>    <ul id="content<?= $group->name ?>" class="content<?= $categories[$group_index - 1]->active ? " active" : "" ?>">
<?php

        foreach ($group->games as $game)
        {
            $addtnl = "";

            if ($game->renter != null)
            {
                $addtnl = " rented";
                if (!$filters['rented'])
                {
                    $addtnl .= " invisible";
                }
            }
            else if ($game->released != null && $game->released != "0")
            {
                $addtnl = " notreleased";
                if (!$filters['notreleased'])
                {
                    $addtnl .= " invisible";
                }
            }
            else if ($game->released != null && $game->released == "0")
            {
                $addtnl = " notowned";
                if (!$filters['notowned'])
                {
                    $addtnl .= " invisible";
                }
            }
            else if ($game->achPoints->value == null || $game->achPoints->value == 0)
            {
                $addtnl = " notplayed";
                if (!$filters['notplayed'])
                {
                    $addtnl .= " invisible";
                }
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

  <footer>
    <right><a href="https://github.com/milek/GamesRepository">Fork it on <strong>GitHub!</strong></a> or <a href="https://bitbucket.org/verdigo/gamesrepository">on <strong>Bitbucket!</strong></a></right>
    <strong>Last modified:</strong> <b><?= date("F d Y H:i:s", filemtime("data/achievements.json")) ?></b><br />
    <right><clear>Clear my <strong>Settings</strong></clear></right>
    <strong>Sort tiles:</strong>
    <sort class="selected" id="sOaz">original a-z</sort> |
    <sort id="sOza">original z-a</sort> | 
    <sort id="sNaz">name a-z</sort> |
    <sort id="sNza">name z-a</sort> |
    <sort id="sPaz">completion 0%-100%</sort> |
    <sort id="sPza">completion 100%-0%</sort>
  </footer>

  <script>

    var currentTab = '<?= $currentTab ?>';

    function changeTab(tab)
    {
        $('#content' + currentTab).removeClass('active');
        $('#' + currentTab).removeClass('active');

        $('#content' + tab).addClass('active');
        $('#' + tab).addClass('active');

        currentTab = tab;

        $.cookie('tab', tab, {expire: 365});
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
        $.cookie('selected.' + type, $('#' + type).hasClass('selected'), {expire: 365});
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

    function removeSettings()
    {
        $.removeCookie('tab');
        $.removeCookie('selected.rented');
        $.removeCookie('selected.notplayed');
        $.removeCookie('selected.notowned');
        $.removeCookie('selected.notreleased');
    }

<?php

    foreach ($categories as $category)
    {

?>
    $('#<?= $category->id ?>').click(function(){changeTab('<?= $category->id ?>')});
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

    $('clear').click(function(){removeSettings()});

  </script>

</body>

