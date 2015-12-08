<?php

/**
 * This class is a wrapper around \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typoLink
 *
 * PHP versions 4 and 5
 *
 *  (c) 2008 Rene Nitzsche
 *  Contact: rene@system25.de
 *
 *  Original version:
 * Copyright (c) 2006-2007 Elmar Hinz
 *
 * LICENSE:
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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 */

/**
 * This class is a wrapper around \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typoLink
 *
 * It is not a full implementation of typolink functionality
 * but targeted to the day-to-day requirements. The idea is to provide
 * an simple to use object orientated interface as an alternative to the
 * typolink functions of pi_base.
 *
 * Depends on: the TS link function
 *
 * @author     Elmar Hinz <elmar.hinz@team-red.net>
 * @package    TYPO3
 * @subpackage lib
 */
class tx_rnbase_util_Link {

	var $tagAttributes = array();       // setting attributes for the tag in general
	var $classString = '';              // tags class attribute
	var $idString = '';                 // tags id attribute
	var $cObject;                       // instance of tx_rnbase_util_Typo3Classes::getContentObjectRendererClass()
	var $destination = '';              // page id, alias, external link, etc.
	var $labelString = '';              // tags label
	var $labelHasAlreadyHtmlSpecialChars = FALSE; // is the label already HSC?
	var $noCacheBoolean = FALSE;        // don't make a cHash
	var $noHashBoolean = FALSE;         // add a no_cache=1 parameter
	var $overruledParameters = array(); // parameters overruled by $parameters
	var $parameters = array();		      // parameters of the link
	var $designatorString = '';         // parameter array name (prefixId) as controller namespace
	var $anchorString = '';             // section anchor as url target
	var $targetString = '';             // tags target attribute
	var $externalTargetString = '-1'; // external target defaults to new window
	var $titleString = '';              // tags title attribute
	var $titleHasAlreadyHtmlSpecialChars = FALSE; //is title attribute already HSC?
	private $typolinkParams = array();	// container for generic typolink parameters
	private $uniqueParameterId = NULL;     // used to build unique parameters for plugin

	// -------------------------------------------------------------------------------------
	// Constructor
	// -------------------------------------------------------------------------------------

	/**
	 * Construct a link object
	 *
	 * By default this object wraps \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typolink();
	 * The $cObjectClass parameter can be used to provide a mock object
	 * for unit tests.
	 *
	 * @param	object		mock object for testing purpuses
	 * @return	void
	 */
	function __construct($cObject = NULL) {
		if (is_object($cObject)) {
			$this->cObject = $cObject;
		} else {
			$this->cObject = tx_rnbase::makeInstance(
				$cObject === NULL ? tx_rnbase_util_Typo3Classes::getContentObjectRendererClass() : $cObject
			);
		}

	}

	/**
	 * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer or tslib_cObj
	 */
	protected function getCObj() {
		return $this->cObject;
	}

	// -------------------------------------------------------------------------------------
	// Setters
	// -------------------------------------------------------------------------------------

	/**
	 * Set the section anchor of the url
	 *
	 * Anchor of page as url target.
	 *
	 * @param	string		the anchor
	 * @return	object		self
	 */
	public function anchor($anchorString) {
		$this->anchorString = $anchorString;
		return $this;
	}

	/**
	 * Set the designator (parameter array name) as controler namespace
	 *
	 * Put the parameters into this array.
	 * <samp>Example: &tx_example[parameterName]=parameterValue</samp>
	 * tx_example is the designator, parameterName is the key,
	 * pararmeterValue is the value of one array element.
	 *
	 * @param	string		parameter array name
	 * @return	object		self
	 */
	public function designator($designatorString) {
		$this->designatorString = $designatorString;
		return $this;
	}

	/**
	 * Set the id attribute of the tag
	 *
	 * @param	string		id attribute
	 * @return	object		self
	 */
	public function idAttribute($idString) {
		$this->idString = $idString;
		return $this;
	}
	/**
	 * Add a param for typolink config
	 * @param string $name
	 * @param mixed $value
	 */
	public function addTypolinkParam($name, $value) {
		$this->typolinkParams[$name] = $value;
	}

	/**
	 * Set the class attribute of the tag
	 *
	 * @param	string		class name
	 * @return	object		self
	 */
	public function classAttribute($classString) {
		$this->classString = $classString;
		return $this;
	}

	/**
	 * Set the links destination
	 *
	 * @param	mixed		pageId, page alias, external url, etc.
	 * @param	boolean		if TRUE don't parse through htmlspecialchars()
	 * @return	object		self
	 * @see		TSref => typolink => parameter
	 * @see		\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::typoLink()
	 */
	public function destination($destination) {
		$this->destination = $destination;
		return $this;
	}

	/**
	 * Add no_cache=1 and disable the cHash parameter
	 *
	 * @param	boolean		if TRUE don't make a cHash, set no_cache=1
	 * @return	object		self
	 */
	public function noCache() {
		$this->noCacheBoolean = TRUE;
		return $this;
	}

	/**
	 * Disable the cHash parameter
	 *
	 * @param	boolean		if TRUE don't make a cHash
	 * @return	object		self
	 */
	public function noHash() {
		$this->noHashBoolean = TRUE;
		return $this;
	}

	/**
	 * Set the links label
	 *
	 * By default the label will be parsed through htmlspecialchars().
	 *
	 * @param	string		the label
	 * @param	boolean		if TRUE don't parse through htmlspecialchars()
	 * @return	object		self
	 */
	public function label($labelString, $hasAlreadyHtmlSpecialChars = FALSE) {
		$this->labelString = $labelString;
		$this->labelHasAlreadyHtmlSpecialChars = $hasAlreadyHtmlSpecialChars;
		return $this;
	}

	/**
	 * Returns the label
	 *
	 * @return string
	 */
	public function getLabel() {
		return $this->labelString;
	}
	public function getUniqueParameterId() {
		return $this->uniqueParameterId;
	}
	public function setUniqueParameterId($id) {
		$this->uniqueParameterId = $id;
	}
	/**
	 * Set array of parameters to be overruled by parameters
	 *
	 * The parameters will create a common array with the name $this->designatorString.
	 * <samp>Example: &tx_example[parameterName]=parameterValue</samp>
	 * tx_example is the designator, parameterName is the key,
	 * pararmeterValue is the value of one array element.
	 *
	 * Usually you set the incomming piVars here you wan't to forward.
	 * Like in tslib_pibase::pi_linkTP_keepPIvars the element DATA is unset during processing.
	 *
	 * @param	mixed		parameters
	 * @return	object		self
	 */
	function overruled($overruledParameters = array()) {
		if(is_object($overruledParameters)) {
			$overruledParameters = $overruledParameters->getArrayCopy();
		}
		$this->overruledParameters = $overruledParameters;
		return $this;
	}

	/**
	 * Set array of new parameters to add to the link url
	 *
	 * The parameters will create a common array with the name $this->designatorString.
	 * <samp>Example: &tx_example[parameterName]=parameterValue</samp>
	 * tx_example is the designator, parameterName is the key,
	 * pararmeterValue is the value of one array element.
	 *
	 * This parameters overrule parameters in $this->baseParameters.
	 *
	 * @param	mixed		parameters
	 * @return	object		self
	 */
	function parameters($parameters = array()) {
		if(is_object($parameters)) {
			$parameters = $parameters->getArrayCopy();
		}
		$this->parameters = $parameters;
		return $this;
	}

	/**
	 * Set the attributes of the tag
	 *
	 * This is a general approach to set tag attributes by an array hash.
	 *
	 * @see	classAttribute()
	 * @see	titleAttribute()
	 * @see	targetAttribute()
	 *
	 * @param	array		key value pairs
	 * @return	object		self
	 */
	function attributes($tagAttributes = array()) {
		$this->tagAttributes = $tagAttributes;
		return $this;
	}

	/**
	 * Set target attribute of the tag
	 * A shortcut for the targetAttribute() function.
	 *
	 * @see	targetAttribute()
	 *
	 * @param	string		target attribute
	 * @return	object		self
	 */
	function target($targetString) {
		$this->targetAttribute($targetString);
		return $this;
	}

	/**
	 * Set target attribute of the tag
	 *
	 * @param	string		target attribute
	 * @return	object		self
	 */
	function targetAttribute($targetString) {
		$this->targetString = $targetString;
		return $this;
	}

	/**
	 * Set external target attribute of the tag
	 * Defaults to _blank
	 *
	 * @param	string		external target attribute
	 * @return	object		self
	 */
	function externalTargetAttribute($targetString) {
		$this->externalTargetString = $targetString;
		return $this;
	}

	/**
	 * Set title attribute of the tag
	 * A shortcut for the titleAttribute() function.
	 *
	 * @see	titleAttribute()
	 *
	 * @param	string		title attribute
	 * @param	boolean		if TRUE don't apply htmlspecialchars() again
	 * @return	object		self
	 */
	function title($titleString, $hasAlreadyHtmlSpecialChars = FALSE) {
		$this->titleAttribute($titleString, $hasAlreadyHtmlSpecialChars);
		return $this;
	}

	/**
	 * Set title attribute of the tag
	 *
	 * @param	string		title attribute
	 * @param	boolean		if TRUE don't apply htmlspecialchars() again
	 * @return	object		self
	 */
	function titleAttribute($titleString, $hasAlreadyHtmlSpecialChars = FALSE) {
		$this->titleString = $titleString;
		$this->titleHasAlreadyHtmlSpecialChars = $hasAlreadyHtmlSpecialChars;
		return $this;
	}

	// -------------------------------------------------------------------------------------
	// Getters
	// -------------------------------------------------------------------------------------

	/**
	 * Return the link as tag
	 *
	 * @return	string		the link tag
	 */
	function makeTag() {
		$link = $this->getCObj()->typolink($this->_makeLabel(), $this->_makeConfig('tag'));
		if ($this->isAbsUrl() && (@simplexml_load_string($link))) {
			$link = self::parseAbsUrl($link, $this->getAbsUrlSchema());
		}
		return $link;
	}

	/**
	 * Return the link as url
	 *
	 * @param	boolean		set to TRUE to run htmlspecialchars() on generated url
	 * @return	string		the link url
	 */
	function makeUrl($applyHtmlspecialchars = TRUE) {
		$url = $this->getCObj()->typolink(NULL, $this->_makeConfig('url'));
		if($this->isAbsUrl()) {
			$url = self::parseAbsUrl($url, $this->getAbsUrlSchema());
		}
		return $applyHtmlspecialchars ? htmlspecialchars($url) : $url;
	}

	/**
	 * forces a schema for a url.
	 * if there is already a schema in the url, the schema will be replaced.
	 *
	 * @param string $url
	 * @param string $schema
	 * @return string
	 */
	public static function parseAbsUrl($url, $schema = FALSE)
	{
		if (!$schema) {
			$schema = tx_rnbase_util_Misc::getIndpEnv('TYPO3_REQUEST_DIR');
		}

		// check if we have a A-Tag with href attribute or a IMG-Tag with src attribute
		if ((@simplexml_load_string($url))) {
			return preg_replace_callback(
				'/(href="|src=")(.+)"/',
				function($match) use ($schema) {
					// $match[1] contains 'href="' or 'src="'
					// $match[2] contains the url '/service/contact.html'
					return $match[1] .
						tx_rnbase_util_Link::parseAbsUrl($match[2], $schema) .
						'"'
					;
				},
				ltrim($url, '/')
			);
		}
		// else, we have only a url to rebuild

		// rebuild the url without schema
		$urlParts = parse_url(self::addDefaultSchemaIfRequired($url));
		$urlPath  = isset($urlParts['path']) ? $urlParts['path'] : '';
		$urlPath .= isset($urlParts['query']) ? '?' . $urlParts['query'] : '';
		$urlPath .= isset($urlParts['fragment']) ? '#' . $urlParts['fragment'] : '';

		return rtrim($schema, '/') . '/'. ltrim($urlPath, '/');
	}

	/**
	 * Vor PHP 5.4.7 wird die URL nicht korrekt geparsed wenn das Schema fehlt.
	 * Also fügen wir dann ein default Schema hinzu damit parse_url korrekt funktioniert.
	 *
	 * @param string $url
	 * @return string
	 */
	static public function addDefaultSchemaIfRequired($url) {
		if (version_compare(phpversion(), '5.4.7', '<') && substr($url, 0, 2) == '//') {
			$url = 'http:' . $url;
		}

		return $url;
	}

	/**
	 * Redirect the page to the url
	 *
	 * @return	void
	 */
	function redirect() {
		session_write_close();

		$target = $this->makeUrl(FALSE);
		$target = tx_rnbase_util_TYPO3::isTYPO60OrHigher() ?
			\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl($target) :
			t3lib_div::locationHeaderUrl($target);
		header('Location: ' . $target);
		exit();
	}

	// -------------------------------------------------------------------------------------
	// Private functions
	// -------------------------------------------------------------------------------------

	/**
	 * Make the full configuration for the typolink function
	 *
	 * @param	string		$type: tag oder url
	 * @return	array		the configuration
	 * @access	private
	 */
	function _makeConfig($type) {
		$conf = Array();
		$this->parameters = is_array($this->parameters)
			? $this->parameters
			: array()
		;
		$this->overruledParameters = is_array($this->overruledParameters)
			? $this->overruledParameters
			: array()
		;
		unset($this->overruledParameters['DATA']);
		tx_rnbase::load('tx_rnbase_util_Arrays');
		$parameters = tx_rnbase_util_Arrays::mergeRecursiveWithOverrule(
			$this->overruledParameters,
			$this->parameters
		);
		foreach((array) $parameters as $key => $value) {
			// Ggf. hier auf die Parameter der eigenen Extension prüfen
			if($this->getUniqueParameterId() !== NULL) {
				$value = array($key => $value);
				$key = $this->getUniqueParameterId();
			}
			$conf['additionalParams'] .= $this->makeUrlParam($key, $value);
		}
		if($this->noHashBoolean ) {
			$conf['useCacheHash'] = 0;
		} else {
			$conf['useCacheHash'] = 1;
		}
		if($this->noCacheBoolean) {
			$conf['no_cache'] = 1;
			$conf['useCacheHash'] = 0;
		} else {
			$conf['no_cache'] = 0;
		}
		if($this->destination !== '')
			$conf['parameter'] = $this->destination;
		if($type == 'url') {
			$conf['returnLast'] = 'url';
		}
		if($this->anchorString) {
			$conf['section'] = $this->anchorString;
		}
		if($this->targetString) {
			$conf['target'] = $this->targetString;
		}
		$conf['extTarget'] = ($this->externalTargetString != '-1') ? $this->externalTargetString : '_blank';
		if($this->classString) {
			$conf['ATagParams'] .= 'class="' . $this->classString . '" ';
		}
		if($this->idString) {
			$conf['ATagParams'] .= 'id="' . $this->idString . '" ';
		}
		if($this->titleString) {
			$title = ($this->titleHasAlreadyHtmlSpecialChars) ? $this->titleString
				: htmlspecialchars($this->titleString);
			$conf['ATagParams'] .= 'title="' . $title . '" ';
		}
		if(is_array($this->tagAttributes)
				&& (count($this->tagAttributes) > 0)) {
			foreach($this->tagAttributes as $key => $value) {
				$conf['ATagParams'] .= ' ' .  $key . '="' . htmlspecialchars($value) . '" ';
			}
		}
		// Weiter generische Attribute setzen
		if(count($this->typolinkParams)) {
			$conf = array_merge($conf, $this->typolinkParams);
		}


		return $conf;
	}

	/**
	 * Generates an additional parameter.
	 * Examples:
	 * $key='param'; $value='123' => &qualifier[param]=123
	 * $key='ttnews::param'; $value='123' => &ttnews[param]=123
	 * $key='::param'; $value='123' => &param=123
	 *
	 * @param string $key
	 * @param string $value
	 * @return string
	 */
	protected function makeUrlParam($key, $value) {
		$qualifier = $this->designatorString;

		// check for qualifier in the keyname
		if(strstr($key, '::')) {
			list($qualifier, $key) = tx_rnbase_util_Strings::trimExplode('::', $key);
		}

		if (!is_array($value)) {
			return '&'
				. rawurlencode($qualifier ? $qualifier . '[' . $key . ']' : $key )
				. '=' . rawurlencode($value)
			;
		}

		return t3lib_div::implodeArrayForUrl(
			$qualifier ? $qualifier : $key,
			$qualifier ? array($key => $value) : $value,
			'',
			TRUE,
			TRUE
		);
	}

	/**
	 * Make the label for the link
	 *
	 * @return	string		the label
	 * @access	private
	 */
	function _makeLabel() {
		return ($this->labelHasAlreadyHtmlSpecialChars) ? $this->labelString
			: htmlspecialchars($this->labelString);
	}

	/**
	 * Generate absolute urls
	 *
	 * @param boolean $flag
	 * @param server schema
	 */
	public function setAbsUrl($flag, $schema='') {
		$this->absUrl = $flag ? TRUE : FALSE;
		$this->absUrlSchema = $schema;
	}
	public function isAbsUrl() {
		return $this->absUrl;
	}
	public function getAbsUrlSchema() {
		return $this->absUrlSchema;
	}

	/**
	 * Init this link by typoscript setup
	 *
	 * @param tx_rnbase_configurations $configurations
	 * @param string $confId
	 */
	public function initByTS($configurations, $confId, $parameterArr) {
		$parameterArr = is_array($parameterArr) ? $parameterArr : array();
		$pid = $configurations->getCObj()->stdWrap(
			$configurations->get($confId.'pid'),
			$configurations->get($confId.'pid.')
		);
		$qualifier = $configurations->get($confId.'qualifier');
		if ($qualifier) {
			$this->designator($qualifier);
		}
		$target = $configurations->get($confId.'target');
		if ($target) {
			$this->target($target);
		}

		// feste URL für externen Link
		if ($fixed = $configurations->get($confId.'fixedUrl', TRUE)) {
			$this->destination($fixed);
		}
		// Das Ziel der Seite vorbereiten
		else {
			$this->destination($pid ? $pid : $GLOBALS['TSFE']->id);
			// absolute und ggf. schema url erzeugen
			if ($absUrl = $configurations->get($confId . 'absurl')) {
				$this->setAbsUrl(TRUE, ($absUrl == 1 || strtolower($absUrl) == 'true' ) ? '' : $absUrl);
			}
		}

		if (array_key_exists('SECTION', $parameterArr)) {
			$this->anchor(htmlspecialchars($parameterArr['SECTION']));
			unset($parameterArr['SECTION']);
		} else {
			$this->anchor((string) $configurations->get($confId.'section', TRUE));
		}
		$this->parameters($parameterArr);

		// eigene Parameter für typolink, die einfach weitergegeben werden
		$typolinkCfg = $configurations->get($confId.'typolink.');
		if (is_array($typolinkCfg)) {
			foreach ($typolinkCfg As $cfgName => $cfgValue) {
				$this->addTypolinkParam($cfgName, $cfgValue);
			}
		}

		// Zusätzliche Parameter für den Link
		$atagParams = $configurations->get($confId.'atagparams.', TRUE);
		if (is_array($atagParams)) {
			// Die Parameter werden jetzt nochmal per TS validiert und können somit dynamisch gesetzt werden
			$attributes = array();
			foreach ($atagParams As $aParam => $lvalue) {
				if (substr($aParam, strlen($aParam)-1, 1) == '.') {
					$aParam = substr($aParam, 0, strlen($aParam)-1);
					if (array_key_exists($aParam, $atagParams)) {
						continue;
					}
				}
				$attributes[$aParam] = $configurations->getCObj()->stdWrap($atagParams[$aParam], $atagParams[$aParam.'.']);
			}
			$this->attributes($attributes);
		}

		// KeepVars prüfen
		// Per Default sind die KeepVars nicht aktiviert. Mit useKeepVars == 1 können sie hinzugefügt werden
		if (!$configurations->get($confId.'useKeepVars')) {
			$this->overruled();
		}
		elseif ($keepVarConf = $configurations->get($confId.'useKeepVars.')) {
			// Sonderoptionen für KeepVars gesetzt
			$newKeepVars = array();
			// skip empty values? default false!
			$skipEmpty = !empty($keepVarConf['skipEmpty']);
			$keepVars = $configurations->getKeepVars();
			$allow = $keepVarConf['allow'];
			$deny = $keepVarConf['deny'];
			if ($allow) {
				$allow = tx_rnbase_util_Strings::trimExplode(',', $allow);
				foreach ($allow As $allowed) {
					$value = $keepVars->offsetGet($allowed);
					if ($skipEmpty && empty($value)) {
						continue;
					}
					$newKeepVars[$allowed] = $keepVars->offsetGet($allowed);
				}
			}
			elseif ($deny) {
				$deny = array_flip(tx_rnbase_util_Strings::trimExplode(',', $deny));
				$keepVarsArr = $keepVars->getArrayCopy();
				foreach($keepVarsArr As $key => $value) {
					if ($skipEmpty && empty($value)) {
						continue;
					}
					if(!array_key_exists($key, $deny)) {
						$newKeepVars[$key] = $value;
					}
				}
			}
			$add = $keepVarConf['add'];
			if ($add) {
				$add = tx_rnbase_util_Strings::trimExplode(',', $add);
				foreach ($add As $linkvar) {
					$linkvar = tx_rnbase_util_Strings::trimExplode('=', $linkvar);
					if (count($linkvar)< 2)  {
						// tt_news::* or ttnews::id
						list($qualifier, $name) = tx_rnbase_util_Strings::trimExplode('::', $linkvar[0]);
						if ($value = tx_rnbase_parameters::getPostOrGetParameter($qualifier)) {
							if($name == '*' && is_array($value)) {
								foreach ($value As $paramName => $paramValue) {
									if ($skipEmpty && empty($paramValue)) {
										continue;
									}
									if(strpos($paramName, 'NK_') === FALSE) {
										$newKeepVars[$qualifier.'::'.$paramName] =  $paramValue;
									}
								}
							}
							else {
								$newKeepVars[$linkvar[0]] =  $value[$name];
							}
						}
					} else {
						$newKeepVars[$linkvar[0]] = $linkvar[1];
					}
				}
			}
			$this->overruled($newKeepVars);
		}
		if ($configurations->get($confId.'noCache')) {
			$this->noCache();
		}
		// Bei der Linkerzeugung wir normalerweise immer ein cHash angelegt. Bei Plugins, die als USER_INT
		// ausgeführt werden, ist dies nicht notwendig und geht auf die Performance. Daher wird hier
		// automatisch der cHash für USER_INT deaktiviert. Per Typocript kann man es aber bei Bedarf manuell
		// wieder aktivieren
		if (
			$configurations->get($confId.'noHash')
			|| (
				$configurations->get($confId.'noHash') !== '0'
				&& $configurations->isPluginUserInt()
			)
		) {
			$this->noHash();
		}

	}

	/**
	 * @see t3lib_div::linkThisScript
	 * @see \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript
	 *
	 * @param array $getParams Array of GET parameters to include
	 * @return string
	 */
	static public function linkThisScript(array $getParams = array()) {
		if (tx_rnbase_util_TYPO3::isTYPO60OrHigher()) {
			$return = \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript($getParams);
		} else {
			$return = t3lib_div::linkThisScript($getParams);
		}

		return $return;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rn_base/util/class.tx_rnbase_util_Link.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rn_base/util/class.tx_rnbase_util_Link.php']);
}
