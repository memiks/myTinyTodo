<?php
/*
	This file is part of myTinyTodo.
	(C) Copyright 2009-2010 Max Pozdeev <maxpozdeev@gmail.com>
	Licensed under the GNU GPL v3 license. See file COPYRIGHT for details.
*/

if(!defined('MTTPATH')) define('MTTPATH', dirname(__FILE__) .'/');

require_once(MTTPATH. 'functions.php');
require_once(MTTPATH. 'common.php');
require_once(MTTPATH. 'db/config.php');

ini_set('display_errors', 0);

if(!isset($config)) global $config;
Config::loadConfig($config);
unset($config);

date_default_timezone_set(Config::get('timezone'));

# MySQL Database Connection
if(Config::get('db') == 'mysql')
{
	require_once(MTTPATH. 'class.db.mysql.php');
	$db = DBConnection::init(new Database_Mysql);
	$db->connect(Config::get('mysql.host'), Config::get('mysql.user'), Config::get('mysql.password'), Config::get('mysql.db'));
	$db->dq("SET NAMES utf8");
}

# SQLite3 (pdo_sqlite)
elseif(Config::get('db') == 'sqlite')
{
	require_once(MTTPATH. 'class.db.sqlite3.php');
	$db = DBConnection::init(new Database_Sqlite3);
	$db->connect(MTTPATH. 'db/todolist.db');
}
else {
	# It seems not installed
	die("Not installed. Run <a href=setup.php>setup.php</a> first.");
}
$db->prefix = Config::get('prefix');

require_once(MTTPATH. 'lang/class.default.php');
require_once(MTTPATH. 'lang/'.Config::get('lang').'.php');

$_mttinfo = array();


$needAuth = (Config::get('password') != '') ? 1 : 0;
if($needAuth && !isset($dontStartSession) && !defined ("__API__"))
{
	ini_set('session.use_cookies', true);
	ini_set('session.use_only_cookies', true);
	session_set_cookie_params(1209600, url_dir(Config::get('url')=='' ? @$_SERVER['REQUEST_URI'] : Config::get('url'))); # 14 days session cookie lifetime
	session_name('mtt-session');
	session_start();
}

function use_mobile()
{
	if(!defined("MTT_TEMPLATE_VAR")) {
		require_once(MTTPATH . "Mobile_Detect.php");
		$detect = new Mobile_Detect();
		if($detect->isMobile()) {
			//TODO: Add support for tablets?
			define("MTT_TEMPLATE_VAR", 'mobiletemplate');
		} else {
			define("MTT_TEMPLATE_VAR", 'template');
		}
	}
}

function _e($s)
{
	echo Lang::instance()->get($s);
}

function __($s)
{
	return Lang::instance()->get($s);
}

function mttinfo($v)
{
	global $_mttinfo;
	if(!isset($_mttinfo[$v])) {
		echo get_mttinfo($v);
	} else {
		echo $_mttinfo[$v];
	}
}

function get_mttinfo($v)
{
	global $_mttinfo;
	if(isset($_mttinfo[$v])) return $_mttinfo[$v];
	use_mobile();
	switch($v)
	{
		case 'template_url':
			$_mttinfo['template_url'] = get_mttinfo('mtt_url'). 'themes/'. Config::get(MTT_TEMPLATE_VAR) . '/';
			return $_mttinfo['template_url'];
		case 'url':
			$_mttinfo['url'] = Config::get('url');
			if($_mttinfo['url'] == '')
				$_mttinfo['url'] = 'http://'.$_SERVER['HTTP_HOST'] .($_SERVER['SERVER_PORT'] != 80 ? ':'.$_SERVER['SERVER_PORT'] : '').
									url_dir(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME']);
			return $_mttinfo['url'];
		case 'mtt_url':
			$_mttinfo['mtt_url'] = Config::get('mtt_url');
			if($_mttinfo['mtt_url'] == '') $_mttinfo['mtt_url'] = url_dir(isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME']);
			return $_mttinfo['mtt_url'];
		case 'title':
			$_mttinfo['title'] = (Config::get('title') != '') ? htmlarray(Config::get('title')) : __('My Tiny Todolist');
			return $_mttinfo['title'];
	}
}

function jsonExit($data)
{
	if (!defined ("__API__"))
	{
		header('Content-type: application/json; charset=utf-8');
		echo json_encode($data);
		exit;
	}
	else
	{
		echo json_encode($data);
	}
}

?>
