<?php

    /**
     * Load retail games file.
     */
    function loadRetailGames(&$games, $buy = false)
    {
        $lastGroup = "";
        $retailHandle = @fopen("data/retail-games.txt", "r");
        if ($retailHandle)
        {
            while (!feof($retailHandle))
            {
                $line = fgets($retailHandle);
                parseGamesLine($line, $games, $lastGroup, $buy);
            }
        }

        fclose($retailHandle);
    }

    /**
     * Load categories
     */
    function loadCategories(&$categories)
    {
        $temp = loadPropertyFile("data/categories.txt");
        $active = false;

        foreach ($temp[2] as $key => $value)
        {
            $category = new Category();
            $category->id = $value;
            $category->active = !$active;
            $category->name = $temp[1][$key];

            $categories[] = $category;

            $active = true;
        }
    }

    /**
     * Parse line.
     */
    function parseGamesLine($line, &$games, &$lastGroup, $buy)
    {
        $line = trim($line);

        if (substr($line, 0, 1) == "[" && substr($line, -1) == "]")
        {
            $name = substr($line, 1, -1);
            $games[$name] = new Group;
            $games[$name]->name = $name;

            $lastGroup = $name;
        }
        else if (substr($line, 0, 2) == "//")
        {
            // do nothing, just a comment
            $game = trim(substr($line, 2));
            $releaseDate = "0";

            if (substr($game, -9, 2) == "//")
            {
                $releaseDate = substr($game, -6);
                $game = trim(substr($game, 0, -9));
            }

            $games[$lastGroup]->addGameWithReleaseDate($game, $renter, $releaseDate);
        }
        else if (strlen($line) > 0 && substr($line, 0, 2) != "//")
        {
            $game = trim($line);
            $renter = null;

            if (preg_match("/^(.*?)\s+\[(.*)\]/i", $line, $matches) > 0)
            {
                $game = $matches[1];
                $renter = $matches[2];
            }

            $games[$lastGroup]->addGame($game, $renter);
        }
    }

    /**
     * Parse achievements.
     */
    function parseAchievements($file, &$games)
    {
        $file = str_replace("\n", "", $file);
        $file = str_replace("\r", "", $file);
        $file = str_replace("&#39;", "'", $file);
        $file = str_replace("&#174;", "", $file);
        $file = str_replace("&#178;", "", $file);
        $file = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $file);

        $regexp = "/<div class=\"LineItem\">";
        $regexp .= "(.*?)consoleAssets\/(.*?)\/en";
        $regexp .= "(.*?)class=\"nohover\">(.*?)<\/a>";
        $regexp .= "(.*?)GamerScore Stat\">(.*?) \/ (.*?)<\/div>";
        $regexp .= "(.*?)Achievement Stat\">(.*?) \/ (.*?)<\/div>";
        $regexp .= "/i";

        preg_match_all($regexp, $file, $matches);

        $live = loadPropertyfile("data/live-matches.txt");

        foreach ($matches[4] as $key => $value)
        {
            if (substr($value, -1) == ".")
            {
                $value = substr($value, 0, -1);
            }

            $gamerscore = new Points;
            $gamerscore->value = $matches[6][$key];
            $gamerscore->outOf = $matches[7][$key];

            assignToGame($games, $live, $value, $matches[2][$key], $gamerscore);
        }
    }

    /**
     * Parse achievements, new format JSON.
     */
    function parseAchievementsJson($file, &$games)
    {
        $json = json_decode($file, true);
        $live = loadPropertyfile("data/live-matches.txt");

        $gamertag = $json['Data']['Players'][0]['Gamertag'];
        $forbidden = array(174, 194, 132, 162, 226);
        $replacement = array_fill(0, count($forbidden), '');

        foreach ($json['Data']['Games'] as $game)
        {
            $name = $game['Name'];
            $name = iconv("UTF-8", "ASCII//IGNORE", $name);
            $name = str_replace($forbidden, $replacement, $name);

            $gamerscore = new Points;
            $gamerscore->value = $game['Progress'][$gamertag]['Score'];
            $gamerscore->outOf = $game['PossibleScore'];

            assignToGame($games, $live, $name, $game['Id'], 0, $gamerscore);
        }
    }

    /**
     * Assign to game.
     */
    function assignToGame(&$games, $live, $name, $id, $boxArtId, $gamerscore)
    {
        $key = array_search($name, $live[1]);
        if ($key !== false)
        {
            $name = $live[2][$key];
        }

        foreach ($games as $group)
        {
            foreach ($group->games as $game)
            {
                if (strcasecmp($game->name, $name) == 0)
                {
                    if ($game->id != "")
                    {
                        if ($id != $game->id)
                        {
                            continue;
                        }
                    }

                    $game->boxArtId = $boxArtId;
                    $game->achPoints = $gamerscore;

                    return;
                }
            }
        }
    }

    /**
     * Parse release date.
     */
    function parseDate($date)
    {
        $day = substr($date, 0, 2);
        $month = substr($date, 2, 2);
        $year = substr($date, -2);

        $dmDiv = ".";
        $myDiv = ".";

        if ($day == "00") $day = null;
        if ($month == "00") $month = null;
        if ($month != null && substr($month, 0, 1) == "Q") $myDiv = " ";

        return ($day != null ? $day.$dmDiv : "").
               ($month != null ? $month.$myDiv : "").
               "20".$year;
    }

    /**
     * Remove empty groups.
     */
    function removeEmptyGroups(&$games)
    {
        foreach ($games as $key=>$value)
        {
            if (empty($value->games))
            {
                unset($games[$key]);
            }
        }
    }

    /**
     * Load propert file.
     */
    function loadPropertyFile($file)
    {
        $content = file_get_contents($file);
        preg_match_all("/(.*?) = (.*)/i", $content, $matches);

        return $matches;
    }

    /**
     * Loads CSV file to array, specific for history data files.
     */
    function csvToArray($filename='', $delimiter=',')
    {
        if(!file_exists($filename) || !is_readable($filename))
        {
            return false;
        }

        $data = array();
        if (($handle = fopen($filename, 'r')) !== FALSE)
        {
            while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE)
            {
                if ($row[0] == "")
                {
                    continue;
                }

                $day = substr($row[0], 0, 2);
                $month = substr($row[0], 2, 2);

                $data[$month][$day] = array($row[1], $row[2]);
            }
            fclose($handle);
        }

        return $data;
    }

    /**
     * Calculate achievements count
     */
    function calculateAchCount($data, $monthToList = "")
    {
        $count = 0;

        if ($monthToList != "")
        {
            if (!isset($data[$monthToList]))
            {
                return "";
            }

            foreach ($data[$monthToList] as $day)
            {
                $count += $day[0];
            }

            return $count;
        }

        foreach ($data as $month)
        {
            foreach ($month as $day)
            {
                $count += $day[0];
            }
        }

        return $count;
    }

    /**
     * Calculate achievements score
     */
    function calculateAchScore($data, $monthToList = "")
    {
        $score = 0;

        if ($monthToList != "")
        {
            if (!isset($data[$monthToList]))
            {
                return "";
            }

            foreach ($data[$monthToList] as $day)
            {
                $score += $day[1];
            }

            return $score;
        }

        foreach ($data as $month)
        {
            foreach ($month as $day)
            {
                $score += $day[1];
            }
        }

        return $score;
    }

    /**
     * Calculate achievements score for graph
     */
    function calculateAchScoreWithArray($data, &$score, &$array)
    {
        foreach ($data as $month)
        {
            foreach ($month as $day)
            {
                $score += $day[1];
                $array .= $score.',';
            }
        }

        return $score;
    }

    /**
     * Calculate achievements count for graph
     */
    function calculateAchCountWithArray($data, &$count, &$array)
    {
        foreach ($data as $month)
        {
            foreach ($month as $day)
            {
                $count += $day[0];
                $array .= $count.',';
            }
        }

        return $count;
    }

    function newlines($buffer)
    {
        $return = "";
        $split = split("[\n\r\t]+", $buffer);

        foreach ($split as $line)
        {
            $return .= trim($line);
        }

        $return = "      ".$return."\r\n";
        return $return;
    }

?>
