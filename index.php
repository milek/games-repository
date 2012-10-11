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
    <span><?php echo $config['title']; ?></span>
  </div>

  <div class="tabs">
    <div class="filters" id="notreleased">not released</div>
    <div class="filters" id="notowned">not owned</div>
    <div class="filters selected" id="notplayed">not played</div>
    <div class="filters selected" id="rented">rented</div>
    <div class="filters selected" style="display: none;" id="played">played</div>
<?php

    $selectedCategory = false;

    foreach ($categories as $category)
    {
?>
    <a id="<?= $category->id ?>"<?php if ($category->active) {?> class="active"<?php } ?>><?= $category->name ?></a>
<?php

    }

?>
  </div>

  <div class="main">

<?php

    foreach ($games as $group)
    {
        $i = 1;

?>    <ul id="content<?= $group->name ?>" class="content tiles<?= $group->name == $categories[0]->id ? " active" : "" ?>">
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
      <li class="game<?= $addtnl ?>">
        <cover>
          <span class="name"><?= $game->getGameName() ?><?php

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

?></span>
          <span class="id"><?= $i++ ?></span>
          <img src="<?= $game->getBoxArt() ?>" />
        </cover>

<?php

            if ($game->achPoints->outOf > 0)
            {
?>
         <span class="achievements">
          <?= $game->achPoints->value ?>&nbsp;/&nbsp;<?= $game->achPoints->outOf ?><br />
          <span class="progress"><span class="count p<?= $classColorPercentage ?>" style="width: <?= $achPointPercentage ?>%;"><?= $achPointPercentage ?></span></span>
        </span>
<?php

            }
            else
            {
?>
         <span class="count" style="display: none;">0</span>
<?php

            }

?>
     </li>
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
    <div class="sort selected" id="sOaz">original a-z</div> |
    <div class="sort" id="sOza">original z-a</div> | 
    <div class="sort" id="sNaz">name a-z</div> |
    <div class="sort" id="sNza">name z-a</div> |
    <div class="sort" id="sPaz">completion 0%-100%</div> |
    <div class="sort" id="sPza">completion 100%-0%</div>
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

    function showHidePlayed()
    {
        $('li').each(function(index, li)
        {
            if (!$(li).hasClass('notplayed') 
             && !$(li).hasClass('notowned'))
            {
                $(li).toggleClass('invisible');
            }
        });

        $('#played').toggleClass('selected');
    }

    function showHide(type)
    {
        $('li').each(function(index, li)
        {
            if ($(li).hasClass(type))
            {
                $(li).toggleClass('invisible');
            }
        });

        $('#' + type).toggleClass('selected');
    }

    function sort(id,where,desc)
    {
        if (desc != null)
        {
            $('ul#[id^"content"]>li').tsort(where, {order:"desc"});
        }
        else
        {
            $('ul#[id^="content"]>li').tsort(where);
        }
        $(".sort").removeClass('selected');
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

    $('#sOaz').click(function(){sort('#sOaz','span.id')});
    $('#sOza').click(function(){sort('#sOza','span.id','desc')});
    $('#sNaz').click(function(){sort('#sNaz','span.name')});
    $('#sNza').click(function(){sort('#sNza','span.name','desc')});
    $('#sPaz').click(function(){sort('#sPaz','span.name');sort('#sPaz','span.count')});
    $('#sPza').click(function(){sort('#sPza','span.name');sort('#sPza','span.count','desc')});

  </script>

</body>

