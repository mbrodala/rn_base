<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2011 Rene Nitzsche
 *  Contact: rene@system25.de
 *  All rights reserved
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 ***************************************************************/

require_once(t3lib_extMgm::extPath('rn_base') . 'class.tx_rnbase_configurations.php');

/**
 * Contains some helpful methods
 */
class tx_rnbase_util_Misc {
	private static $enableTT = false;

	/**
	 * Returns a service
	 * Mayday is raised if service not found.
	 *
	 * @param string $type
	 * @param string $subType
	 * @return t3lib_svbase
	 */
	static function getService($type, $subType='') {
    $srv = t3lib_div::makeInstanceService($type, $subType);
    if(!is_object($srv)) {
    	tx_rnbase::load('tx_rnbase_util_Misc');
      return self::mayday('Service ' . $type . ' - ' . $subType . ' not found!');;
    }
    return $srv;
	}
	/**
	 * Returns an array with all subtypes for given service key.
	 *
	 * @param string $type
	 */
	static function lookupServices($serviceType) {
		global $T3_SERVICES;
		$priority = array(); // Remember highest priority
		$services = array();
		if(is_array($T3_SERVICES[$serviceType])) {
			foreach($T3_SERVICES[$serviceType] As $key => $info) {
				if($info['available'] AND (!isset($priority[$info['subtype']]) || $info['priority'] >= $priority[$info['subtype']]) ) {
					$priority[$info['subtype']] = $info['priority'];
					$services[$info['subtype']] = $info;
				}
			}
		}
		return $services;
	}

	/**
	 * Zufällige Sortierung der Items in der Liste. Die Liste wird per PHP gemischt. Zusätzlich kann ein weiteres
	 * Limit gesetzt werden, um die Anzahl der auszugebenden Items weiter einzuschränken.
	 * Damit kann man aus einem Pool von Items (bspw. die neuesten 10 Items) per Zufall eine gewünschte
	 * Anzahl von Items anzeigen.
	 * @param array $items
	 * @param int $limit
	 * @return array
	 */
	public static function randomizeItems(array $items, $limit=0) {
		$anzahl = count($items);
		$idxArr = range(1, $anzahl);
		shuffle($idxArr);
		$limit = ($limit > 0 && $limit < $anzahl) ? $limit : $anzahl;
		$ret = array();
		for($i=0; $i<$limit; $i++) {
			$ret[] = $items[($idxArr[$i]-1)];
		}
		reset($ret);
		return $ret;
	}

	/**
	 * Calls a hook
	 *
	 * @param string $extKey
	 * @param string $hookKey
	 * @param array $params
	 * @param mixed $parent instance of calling class or 0
	 */
	public static function callHook($extKey, $hookKey, $params, $parent=0) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey][$hookKey])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extKey][$hookKey] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $parent);
			}
		}
	}
		
	/**
	 * Stops PHP execution : die() if some critical error appeared
   * This method is taken from the great ameos_formidable extension.
	 * 
	 * @param	string		$msg: the error message
	 * @return	void
	 */
	public static function mayday($msg, $extKey = '') {
		tx_rnbase::load('tx_rnbase_util_Logger');

		tx_rnbase_util_Logger::fatal($msg, $extKey);
		$aTrace		= debug_backtrace();
		$aLocation	= array_shift($aTrace);
		$aTrace1	= array_shift($aTrace);
		$aTrace2	= array_shift($aTrace);
		$aTrace3	= array_shift($aTrace);
		$aTrace4	= array_shift($aTrace);

		$aDebug = array();

		$aDebug[] = '<h2 id="backtracetitle">Call stack</h2>';
		$aDebug[] = '<div class="backtrace">';
		$aDebug[] = '<span class="notice"><b>Call 0: </b>' . str_replace(PATH_site, '/', $aLocation['file']) . ':' . $aLocation['line']  . ' | <b>' . $aTrace1['class'] . $aTrace1['type'] . $aTrace1['function'] . '</b></span><br/>With parameters: ' . (!empty($aTrace1['args']) ? self::viewMixed($aTrace1['args']) : ' no parameters');
		$aDebug[] = '<hr/>';
		$aDebug[] = '<span class="notice"><b>Call -1: </b>' . str_replace(PATH_site, '/', $aTrace1['file']) . ':' . $aTrace1['line']  . ' | <b>' . $aTrace2['class'] . $aTrace2['type'] . $aTrace2['function'] . '</b></span><br />With parameters: ' . (!empty($aTrace2['args']) ? self::viewMixed($aTrace2['args']) : ' no parameters');
		$aDebug[] = '<hr/>';
		$aDebug[] = '<span class="notice"><b>Call -2: </b>' . str_replace(PATH_site, '/', $aTrace2['file']) . ':' . $aTrace2['line']  . ' | <b>' . $aTrace3['class'] . $aTrace3['type'] . $aTrace3['function'] . '</b></span><br />With parameters: ' . (!empty($aTrace3['args']) ? self::viewMixed($aTrace3['args']) : ' no parameters');
		$aDebug[] = '<hr/>';
		$aDebug[] = '<span class="notice"><b>Call -3: </b>' . str_replace(PATH_site, '/', $aTrace3['file']) . ':' . $aTrace3['line']  . ' | <b>' . $aTrace4['class'] . $aTrace4['type'] . $aTrace4['function'] . '</b></span><br />With parameters: ' . (!empty($aTrace4['args']) ? self::viewMixed($aTrace4['args']) : ' no parameters');
		$aDebug[] = '<hr/>';

		if(is_callable(array('t3lib_div', 'debug_trail'))) {
			$aDebug[] = '<span class="notice">' . t3lib_div::debug_trail() . '</span>';
			$aDebug[] = '<hr/>';
		}

		$aDebug[] = '</div>';

		if(intval(tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'forceException4Mayday'))) {
			throw tx_rnbase::makeInstance('tx_rnbase_util_Exception', $msg, 0, array('Info' => $aDebug));
		}

		$aDebug[] = '<br/>';

		$sContent =	'<h1 id="title">Mayday</h1>';
		$sContent .= '<div id="errormessage">' . $msg . '</div>';
		$sContent .= '<hr />';
		$verbose = intval(tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'verboseMayday'));
		if($verbose)
			$sContent .= implode('', $aDebug);

		$sPage =<<<MAYDAYPAGE
<!DOCTYPE html
	PUBLIC '-//W3C//DTD XHTML 1.1//EN'
	'http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en'>
	<head>
		<title>${extKey}::Mayday</title>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
		<meta name="robots" content="noindex, nofollow" />
		<style type="text/css">

			#title {
				color: red;
				font-family: Verdana;
			}

			#errormessage {
				border: 2px solid red;
				padding: 10px;
				color: white;
				background-color: red;
				font-family: Verdana;
				font-size: 12px;
			}

			.notice {
				font-family: Verdana;
				font-size: 9px;
				font-style: italic;
			}

			#backtracetitle {
			}

			.backtrace {
				background-color: #FFFFCC;
			}

			HR {
				border: 1px solid silver;
			}
		</style>
	</head>
	<body>
		{$sContent}
	</body>
</html>

MAYDAYPAGE;

		$dieOnMayday = intval(tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'dieOnMayday'));
		if($dieOnMayday)
			die($sPage);
		else
			echo($sPage);
	}

	/**
	 * Creates a html view for a php object
   * This method is taken from the great ameos_formidable extension.
	 * 
	 * @param mixed $mMixed
	 * @param boolean $bRecursive
	 * @param int $iLevel
	 * @return string
	 */
	function viewMixed($mMixed, $bRecursive = TRUE, $iLevel=0) {

		$sStyle = "font-family: Verdana; font-size: 9px;";
		$sStyleBlack = $sStyle . "color: black;";
		$sStyleRed = $sStyle . "color: red;";
		$sStyleGreen = $sStyle . "color: green;";

		$aBgColors = array(
			"FFFFFF", "F8F8F8", "EEEEEE", "E7E7E7", "DDDDDD", "D7D7D7", "CCCCCC", "C6C6C6", "BBBBBB", "B6B6B6", "AAAAAA", "A5A5A5", "999999", "949494", "888888", "848484", "777777", "737373"
		);

		if(is_array($mMixed)) {

			$result="<table border=1 style='border: 1px solid silver' cellpadding=1 cellspacing=0 bgcolor='#" . $aBgColors[$iLevel] . "'>";

			if(!count($mMixed)) {
				$result.= "<tr><td><span style='" . $sStyleBlack . "'><b>".htmlspecialchars("EMPTY!")."</b></span></td></tr>";
			} else {
				while(list($key, $val)=each($mMixed)) {

					$result.= "<tr><td valign='top'><span style='" . $sStyleBlack . "'>".htmlspecialchars((string)$key)."</span></td><td>";

					if(is_array($val))	{
						$result.=self::viewMixed($val, $bRecursive, $iLevel + 1);
					} else {
						$result.= "<span style='" . $sStyleRed . "'>".self::viewMixed($val, $bRecursive, $iLevel + 1)."<br /></span>";
					}

					$result.= "</td></tr>";
				}
			}

			$result.= "</table>";

		} elseif(is_resource($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>RESOURCE: </span>" . $mMixed;
		} elseif(is_object($mMixed)) {
			if($bRecursive) {
				$result = "<span style='" . $sStyleGreen . "'>OBJECT (" . get_class($mMixed) .") : </span>" . self::viewMixed(get_object_vars($mMixed), FALSE, $iLevel + 1);
			} else {
				$result = "<span style='" . $sStyleGreen . "'>OBJECT (" . get_class($mMixed) .") : !RECURSION STOPPED!</span>";// . t3lib_div::view_array(get_object_vars($mMixed), FALSE);
			}
		} elseif(is_bool($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>BOOLEAN: </span>" . ($mMixed ? "TRUE" : "FALSE");
		} elseif(is_string($mMixed)) {
			if(empty($mMixed)) {
				$result = "<span style='" . $sStyleGreen . "'>STRING(0)</span>";
			} else {
				$result = "<span style='" . $sStyleGreen . "'>STRING(" . strlen($mMixed) . "): </span>" . nl2br(htmlspecialchars((string)$mMixed));
			}
		} elseif(is_null($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>!NULL!</span>";
		} elseif(is_integer($mMixed)) {
			$result = "<span style='" . $sStyleGreen . "'>INTEGER: </span>" . $mMixed;
		} else {
			$result = "<span style='" . $sStyleGreen . "'>MIXED: </span>" . nl2br(htmlspecialchars(strVal($mMixed)));
		}

		return $result;
	}
  /**
   * Prepare classes for FE-rendering if it is needed in TYPO3 backend.
   */
	public static function prepareTSFE($options = array()) {
		$pid = array_key_exists('pid', $options) ? $options['pid'] : 1;
		$type = array_key_exists('type', $options) ? $options['type'] : 99;

		$force = array_key_exists('force', $options) ? true : false;

		if(!is_object($GLOBALS['TT'])) {
			require_once(PATH_t3lib.'class.t3lib_timetrack.php');
			$GLOBALS['TT'] = new t3lib_timeTrack;
			$GLOBALS['TT']->start();
		}
		if(!is_object($GLOBALS['TSFE']) || $force) {
			if(!defined('PATH_tslib')) {
				// PATH_tslib setzen
				if (@is_dir(PATH_site.'typo3/sysext/cms/tslib/')) {
					define('PATH_tslib', PATH_site.'typo3/sysext/cms/tslib/');
				} elseif (@is_dir(PATH_site.'tslib/')) {
					define('PATH_tslib', PATH_site.'tslib/');
				} else {
					$configured_tslib_path = '';
					// example:
					// $configured_tslib_path = '/var/www/mysite/typo3/sysext/cms/tslib/';
					define('PATH_tslib', $configured_tslib_path);
				}
			}

			require_once(PATH_tslib.'class.tslib_content.php');
			require_once(PATH_tslib.'class.tslib_fe.php');
			require_once(PATH_t3lib.'class.t3lib_page.php');
			require_once(PATH_t3lib.'class.t3lib_tstemplate.php');
			$GLOBALS['TSFE'] = tx_rnbase::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], $pid, $type);
			// Jetzt noch pageSelect
			$temp_sys_page = t3lib_div::makeInstance('t3lib_pageSelect');
			$temp_sys_page->init(0);
			$GLOBALS['TSFE']->sys_page = $temp_sys_page;
			$GLOBALS['TSFE']->initTemplate();
			$GLOBALS['TSFE']->tmpl->getFileName_backPath = $GLOBALS['TSFE']->tmpl->getFileName_backPath ? $GLOBALS['TSFE']->tmpl->getFileName_backPath : PATH_site;
			//Basis Nutzergruppen
			$GLOBALS['TSFE']->gr_list = '0,-1';
			$GLOBALS['TSFE']->config['config'] = array();
		}
		return $GLOBALS['TSFE'];
	}
	/**
	 * Umlaute durch normale Buchstaben erstetzen. Aus Ü wird Ue.
	 *
	 * @param string $str
	 * @return string
	 */
	static function removeUmlauts($str) {
		$array = array ( 'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue');
		return strtr ( $str, $array );
	}
	static function objImplode($sep, $arr) {
		$uids = array();
		foreach($arr As $obj) {
			$uids[] = $obj->uid;
		}
		return implode($sep, $uids);
	}
	/**
	 * Validate a search string for minimum length. All smaller parts are removed. 
	 *
	 * @param string $searchterm
	 * @param int $minLength
	 * @return string
	 */
	static function validateSearchString($searchterm, $minLength=3) {
		// Suchteile splitten
		$ret = array();
		$arr = t3lib_div::trimExplode(' ', $searchterm);
		foreach($arr As $term) {
			if(strlen($term) >= $minLength) $ret[] = $term;
		}
		return trim(implode(' ' , $ret));
	}
	/**
	 * Translates a string starting with LLL:
	 *
	 * @param string $title
	 * @return string
	 */
	public static function translateLLL($title) {
		if(substr($title, 0, 4) === 'LLL:') {
			if(array_key_exists('LANG', $GLOBALS))
				return $GLOBALS['LANG']->sL($title);
			if(array_key_exists('TSFE', $GLOBALS))
				return $GLOBALS['TSFE']->sL($title);
			return '';
		}
		return $title;
	}

	/**
	 * Create a short hash from all values in $params. This can be used as additional link parameter to 
	 * ensure submitted parameters are not modified. 
	 * The order of values doesn't matter.
	 *
	 * @param array $params
	 * @param string $salt a secret salt string
	 * @param boolean $daily Hash values changed with every new day
	 * @return string with 8 characters
	 */
	static function createHash($params, $salt='secret', $daily = true) {
		$str = '';
		if($daily) {
			tx_rnbase::load('tx_rnbase_util_Dates');
			$str .= tx_rnbase_util_Dates::getTodayDateString();
		}
		sort($params);
		foreach($params As $key => $value) {
			if(is_array($value)) $value = 1; // Arrays werden erstmal nicht unterstützt
			$str .= strval($value);
		}
		$str .= $salt;
		$hash = md5($str);
		return substr($hash,5,8);
	}

	/**
	 * Start TimeTrack section
	 *
	 * @param string $message
	 */
	public static function pushTT($label, $message='') {
		if(self::$enableTT && is_object($GLOBALS['TT']))
			$GLOBALS['TT']->push($label, $message);
	}
	/**
	 * End TimeTrack section
	 */
	public static function pullTT() {
		if(self::$enableTT && is_object($GLOBALS['TT']))
			$GLOBALS['TT']->pull();
	}
	/**
	 * The TimeTracking uses a lot of memory. So it should be used for testcases only.
	 * By default the timetracking is not enabled
	 *
	 * @param boolean $flag
	 */
	public static function enableTimeTrack($flag) {
		self::$enableTT = $flag;
	}
	/**
	 * Explode a list into an array
	 *
	 * Explodes a string by any number of the given charactrs.
	 * By default it uses comma, semicolon, colon and whitespace.
	 *
	 * The returned values are trimmed.
	 * Method taken from tx_div
	 * @param	string		string to split
	 * @param	string		regular expression that defines the splitter
	 * @return	array		with the results
	 */
	public static function explode($value, $splitCharacters = ',;:\s') {
		$pattern = '/[' . $splitCharacters . ']+/';
		$results = preg_split($pattern, $value, -1, PREG_SPLIT_NO_EMPTY);
		$return = array();
		foreach($results as $result)
			$return[] = trim($result);
		return (array) $return;
	}

	/**
 	 * Same method as tslib_pibase::pi_getPidList()
	 */
	public function getPidList($pid_list,$recursive=0)  {
		if (!strcmp($pid_list,''))      $pid_list = $GLOBALS['TSFE']->id;
		$recursive = t3lib_div::intInRange($recursive,0);

		$pid_list_arr = array_unique(t3lib_div::trimExplode(',',$pid_list,1));
		$pid_list = array();

		foreach($pid_list_arr as $val)  {
			$val = t3lib_div::intInRange($val,0);
			if ($val)       {
				$_list = tslib_cObj::getTreeList(-1*$val, $recursive);
				if ($_list)  $pid_list[] = $_list;
			}
		}
		return implode(',', $pid_list);
	}

	/**
	 * Returns a given CamelCasedString as an lowercase string with underscores.
	 * Example: Converts BlogExample to blog_example, and minimalValue to minimal_value
	 * Taken from t3lib_div for backward compatibility
	 *
	 * @param	string		$string: String to be converted to lowercase underscore
	 * @return	string		lowercase_and_underscored_string
	 */
	public static function camelCaseToLowerCaseUnderscored($string) {
		return strtolower(preg_replace('/(?<=\w)([A-Z])/', '_\\1', $string));
	}

	/**
	 * Sendout an error mail
	 * @param string $mailAddr commaseperated recipients
	 * @param string $actionName
	 * @param Exception $e
	 * @param array $options
	 */
	public static function sendErrorMail($mailAddr, $actionName, Exception $e, array $options=array()) {
		$ignoreMailLock = (array_key_exists('ignoremaillock', $options) && $options['ignoremaillock']);

		if(!$ignoreMailLock) {
			$lockFile = PATH_site.'typo3temp/rn_base/maillock.txt';
			$lockFileFound = is_file($lockFile);
			if($lockFileFound) {
				$lastCall = intval(trim(file_get_contents($lockFile)));
				if($lastCall > (time() - 60)) {
					return; // Only one mail within one minute sent
				}
			}
			else {
				if(!is_file(PATH_site.'typo3temp/rn_base/')) {
					tx_rnbase::load('tx_rnbase_util_Logger');
					tx_rnbase_util_Logger::info('TempDir for rn_base not found. Check EM settings!', 'rn_base', array('lockfile'=> $lockFile));
				}
				$lockFileFound = true;
			}
		}
		
		$textPart = 'This is an automatic email from TYPO3. Don\'t answer!'."\n\n"; 
		$htmlPart = '<strong>This is an automatic email from TYPO3. Don\'t answer!</strong>';
		$textPart .= 'UNCAUGHT EXCEPTION FOR VIEW: ' . $actionName ."\n\n";
		$htmlPart .= '<div><strong>UNCAUGHT EXCEPTION FOR VIEW: ' . $actionName .'</strong></div>';
		$textPart .= 'Message: ' . $e->getMessage()."\n\n";
		$htmlPart .= '<p><strong>Message:</strong><br />' . $e->getMessage() . '</p>';
		$textPart .= "Stacktrace:\n". $e->__toString()."\n";
		$htmlPart .= '<p><strong>Stacktrace:</strong><pre>'.$e->__toString().'</pre></p>';
		$textPart .= 'SITE_URL: ' . t3lib_div::getIndpEnv('TYPO3_SITE_URL')."\n";
		$htmlPart .= '<p><strong>SITE_URL</strong><br />'. t3lib_div::getIndpEnv('TYPO3_SITE_URL'). '</p>';
		if(count($_GET))
			$htmlPart .= '<p><strong>_GET</strong><br />'. var_export($_GET, true). '</p>';
		if(count($_POST))
			$htmlPart .= '<p><strong>_POST</strong><br />'. var_export($_POST, true). '</p>';
		$htmlPart .= '<p><strong>_SERVER</strong><br />'. var_export($_SERVER, true). '</p>';
		if($e instanceof tx_rnbase_util_Exception) {
			$additional = $e->getAdditional();
			if($additional)
				$htmlPart .= '<p><strong>Additional Data:</strong><br />' . strval($additional) . '</p>';
		}

		tx_rnbase::load('tx_rnbase_util_TYPO3');
		$textPart .= 'BE_USER: '.tx_rnbase_util_TYPO3::getBEUserUID()."\n";
		$htmlPart .= '<p><strong>BE_USER:</strong> '.tx_rnbase_util_TYPO3::getBEUserUID().'</p>';
		$textPart .= 'FE_USER: '.tx_rnbase_util_TYPO3::getFEUserUID()."\n";
		$htmlPart .= '<p><strong>FE_USER:</strong> '.tx_rnbase_util_TYPO3::getFEUserUID().'</p>';

		$mail = t3lib_div::makeInstance('t3lib_htmlmail');
		$mail->start();
		$mail->subject         = 'Exception on site '.$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
		$mail->from_email      = tx_rnbase_configurations::getExtensionCfgValue('rn_base', 'fromEmail');
		$mail->from_name       = '';
		$mail->organisation    = '';
		$mail->priority        = 1;
		$mail->addPlain($textPart);
		$mail->setHTML($htmlPart);
//		$mail->theParts['html']['content'] = $this->parse($mail->theParts['html']['content']);
		if($lockFileFound && !$ignoreMailLock)
			file_put_contents($lockFile, time()); // refresh lock
		return $mail->send($mailAddr);
	}
	/**
	 * Returns currently loaded LL files
	 * @return array
	 */
	public static function getLoadedLLFiles() {
		return array_keys($GLOBALS['LANG']->LL_files_cache);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rn_base/util/class.tx_rnbase_util_Misc.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rn_base/util/class.tx_rnbase_util_Misc.php']);
}

?>