<?php
include_once $_SERVER["DOCUMENT_ROOT"]."/midnight/shared/bases/Const.php";
/**
 * @description : A Class for Defining constants and Setting Conduction Mode
 * @author : PickleCode
 * @apiNote : DO NOT MODIFY THE CONSTANTS UNLESS YOU EXACTLY KNOW WHAT YOU ARE DOING
 */
if(!class_exists("Configs")) {

	class Configs{

        var $CONFIG;
        var $CONFIG_MODE;
        var $PF_FILE_PATH;
        var $PF_FILE_TEMP_PATH;
        var $PF_FILE_TEMP_SHORT;
        var $PF_FILE_DISPLAY_PATH;
        var $PF_FILE_PATH_720;
        var $PF_FILE_PATH_640;
        var $PF_FILE_PATH_480;
        var $PF_FILE_PATH_320;
        var $PF_FILE_PATH_100;
        var $PF_DB_HOST;
        var $PF_DB_NAME;
        var $PF_DB_USER;
        var $PF_DB_PASSWORD;
        var $PF_DB_CHARSET;

		function __construct(){
        	$this->init();
        }

		function init(){
            $DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
            /**
             * Variables which can be changed by developers and environments
             * @changeable true
             */
            $this->CONFIG_MODE = CONDUCT_MODE_DEV;
            $this->CONFIG = array(
                CONDUCT_MODE_DEV => array(
                    /**
                     * File Paths for DEV MODE
                     */
                    URL_PATH => PF_DEFAULT,
                    URL_PATH_TEMP => $DOCUMENT_ROOT."midnight/tempFiles",
                    PF_FILE_TEMP_SHORT => "/midnight/tempFiles",
                    URL_DISPLAY_PATH => PF_DEFAULT,
                    URL_PATH_100 => PF_DEFAULT,
                    URL_PATH_320 => PF_DEFAULT,
                    URL_PATH_480 => PF_DEFAULT,
                    URL_PATH_640 => PF_DEFAULT,
                    URL_PATH_720 => PF_DEFAULT,
                    /**
                     * Database Config for DEV MODE
                     */
                    DATABASE_HOST => "193.122.100.94",
                    DATABASE_NAME => "app_midnight",
                    DATABASE_USER => "root",
                    DATABASE_PASSWORD => "fishcreek!@#$",
                    DATABASE_CHARSET => "utf8"
                ),
                CONDUCT_MODE_TEST => array(
                    /**
                     * File Paths for TEST MODE
                     */
                    URL_PATH => PF_DEFAULT,
                    URL_DISPLAY_PATH => PF_DEFAULT,
                    URL_PATH_100 => PF_DEFAULT,
                    URL_PATH_320 => PF_DEFAULT,
                    URL_PATH_480 => PF_DEFAULT,
                    URL_PATH_640 => PF_DEFAULT,
                    URL_PATH_720 => PF_DEFAULT,
                    /**
                     * Database Config for TEST MODE
                     */
                    DATABASE_HOST => "picklecode.co.kr",
                    DATABASE_NAME => "app_midnight",
                    DATABASE_USER => "midnight",
                    DATABASE_PASSWORD => "midnight!@#$",
                    DATABASE_CHARSET => "utf8"
                ),
                CONDUCT_MODE_LIVE => array(
                    /**
                     * File Paths for LIVE MODE
                     */
                    URL_PATH => PF_DEFAULT,
                    URL_DISPLAY_PATH => PF_DEFAULT,
                    URL_PATH_100 => PF_DEFAULT,
                    URL_PATH_320 => PF_DEFAULT,
                    URL_PATH_480 => PF_DEFAULT,
                    URL_PATH_640 => PF_DEFAULT,
                    URL_PATH_720 => PF_DEFAULT,
                    /**
                     * Database Config for LIVE MODE
                     */
                    DATABASE_HOST => "picklecode.co.kr",
                    DATABASE_NAME => "app_midnight",
                    DATABASE_USER => "midnight",
                    DATABASE_PASSWORD => "midnight!@#$",
                    DATABASE_CHARSET => "utf8"
                )
            );

            /**
             * Variables which must not be changed
             * @apiNote DO NOT MODIFY UNLESS YOU EXACTLY KNOW WHAT YOU ARE DOING
             * @description Variables to be used by developers
             */
            $this->PF_FILE_PATH = $this->CONFIG[$this->CONFIG_MODE][URL_PATH];
            $this->PF_FILE_DISPLAY_PATH = $this->CONFIG[$this->CONFIG_MODE][URL_DISPLAY_PATH];
            $this->PF_FILE_TEMP_PATH = $this->CONFIG[$this->CONFIG_MODE][URL_PATH_TEMP];
            $this->PF_FILE_TEMP_SHORT = $this->CONFIG[$this->CONFIG_MODE][PF_FILE_TEMP_SHORT];
            $this->PF_FILE_PATH_720 = $this->CONFIG[$this->CONFIG_MODE][URL_PATH_720];
            $this->PF_FILE_PATH_640 = $this->CONFIG[$this->CONFIG_MODE][URL_PATH_640];
            $this->PF_FILE_PATH_480 = $this->CONFIG[$this->CONFIG_MODE][URL_PATH_480];
            $this->PF_FILE_PATH_320 = $this->CONFIG[$this->CONFIG_MODE][URL_PATH_320];
            $this->PF_FILE_PATH_100 = $this->CONFIG[$this->CONFIG_MODE][URL_PATH_100];
            $this->PF_DB_HOST = $this->CONFIG[$this->CONFIG_MODE][DATABASE_HOST];
            $this->PF_DB_NAME = $this->CONFIG[$this->CONFIG_MODE][DATABASE_NAME];
            $this->PF_DB_USER = $this->CONFIG[$this->CONFIG_MODE][DATABASE_USER];
            $this->PF_DB_PASSWORD = $this->CONFIG[$this->CONFIG_MODE][DATABASE_PASSWORD];
            $this->PF_DB_CHARSET = $this->CONFIG[$this->CONFIG_MODE][DATABASE_CHARSET];
		}
	}
}

?>
