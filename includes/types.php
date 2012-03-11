<?php

    class Group
    {

        // name of group
        public $name;

        // array of games
        public $games = array();

        /**
         * Adds game
         */
        function addGame($name, $renter)
        {
            $id = '';

            if (!(strpos($name, "{") === false))
            {
                $id = substr($name, strpos($name, "{") + 1, -1);
                $name = substr($name, 0, strpos($name, "{") - 1);
            }

            $game = new Game;
            $game->name = $name;
            $game->id = $id;
            $game->renter = $renter;
            $game->released = null;

            $game->checkForAchievements();

            $this->games[] = $game;
        }

        /**
         * Adds game with release date
         */
        function addGameWithReleaseDate($name, $renter, $releaseDate)
        {
            $game = new Game;
            $game->name = $name;
            $game->renter = $renter;
            $game->released = $releaseDate;

            $game->checkForAchievements();

            $this->games[] = $game;
        }

    }

    class Game
    {

        // id
        public $id;

        // name
        public $name;

        // box art id
        public $boxArtId;

        // renter
        public $renter;

        // achievements points
        public $achPoints;

        // achivements count
        public $achCount;

        // released
        public $released;

        /**
         * Constructor
         */
        function __construct()
        {
            $this->achPoints = new Points;
            $this->achCount = new Points;
            $this->released = true;
        }

        /**
         * Check game for XBox Live Achievements
         */
        function checkForAchievements()
        {
        }

        /**
         * Checks if box art for game exists, either on server
         * or through id
         */
        function boxArtExists()
        {
            $local = "covers/".strtolower($this->normalize($this->name)).".jpg";
            if (file_exists($local))
            {
                return true;
            }

            return $this->boxArtId != null;
        }

        /**
         * Returns box art for game. Either local file or remote
         * from XBox Live servers. It's always better to have
         * local file.
         */
        function getBoxArt()
        {
            $local = "covers/".strtolower($this->normalize($this->name)).".jpg";
            if (file_exists($local))
            {
                return $local;
            }

            $remote = "http://tiles.xbox.com:80/consoleAssets/".$this->boxArtId."/en-GB/largeboxart.jpg";

            return $remote;
        }

        /**
         * Returns update game name, use only for display purposes
         */
        function getGameName()
        {
            $gameName = $this->name;

            // LEGO games looks better with ®
            $gameName = str_replace("LEGO", "LEGO&reg;", $gameName);

            return $gameName;
        }

        /**
         * Normalize game name for use with box art
         */
        function normalize($gamename)
        {
            $gamename = str_replace(" ", "_", $gamename);
            $gamename = str_replace(".", "_", $gamename);
            $gamename = str_replace(":", "", $gamename);
            $gamename = str_replace("'", "", $gamename);
            $gamename = str_replace("&#252;", "u", $gamename);
            $gamename = str_replace("&", "_", $gamename);

            $gamename = str_replace("__", "_", $gamename);
            $gamename = str_replace("__", "_", $gamename);

            return $gamename;
        }

    }

    class Points
    {

        // current value
        public $value = 0;

        // out of
        public $outOf = 0;

    }

    class Category
    {

        // id
        public $id = "";

        // name
        public $name = "";

        // active
        public $active = false;

    }

?>