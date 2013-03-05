<?php

/**
 * This file contains Utils_Text_BBCode
 */

/**
 * QUIQQER BBcode class
 *
 * @author www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qui.utils.text
 *
 * @todo check the class, the class is realy old, maybe this can be done better
 * @todo docu translation
 */

class Utils_Text_BBCode extends QDOM
{
    /**
     * the project string
     * @var String
     */
	protected $_projects = array();

	/**
	 * internal smiley list
	 * @var array
	 */
	protected $_smileys = array();

	/**
	 * internal output smileys
	 * @var array
	 */
	protected $_output_smiley = array();

	/**
	 * bbcode to html plugin list
	 * @var array
	 */
	protected $_plugins_bbcode_to_html = array();

	/**
	 * html to bbcode plugin list
	 * @var array
	 */
	protected $_plugins_html_to_bbcode = array();

	/**
	 * Wandelt HTML Tags in BBCode um
	 *
	 * @param unknown_type $html
	 * @return unknown
	 */
	public function parseToBBCode($html)
	{
		// Normal HTML Elemente
		$bbcode = str_replace(array(
			'<b>','</b>',
			'<strong>','</strong>',

			'<i>', '</i>',
			'<u>', '</u>',
			'<del>', '</del>',
			'<strike>','</strike>',

			'<ul>', '</ul>',
			'<li>', '</li>',

			'<br>', '<br />',

			'<h1>', '</h1>',
			'<h2>', '</h2>',
			'<h3>', '</h3>',
			'<h4>', '</h4>',
			'<h5>', '</h5>',
			'<h6>', '</h6>'
		), array(
			'[b]','[/b]',
			'[b]','[/b]',

			'[i]','[/i]',
			'[u]','[/u]',

			'[s]','[/s]',
			'[s]','[/s]',

			'[ul]','[/ul]',
			'[li]','[/li]',

			'[br]','[br]',

			'[h1]', '[/h1]',
			'[h2]', '[/h2]',
			'[h3]', '[/h3]',
			'[h4]', '[/h4]',
			'[h5]', '[/h5]',
			'[h6]', '[/h6]'
		), $html);

		// Block Elemente
		$bbcode = preg_replace(
			array(
				'/<p[^>]*>(.*?)<\/p>/i',
		        '/<pre[^>]*>(.*?)<\/pre>/i',
				'/<b [^>]*>/i', '/<strong [^>]*>/i',
				'/<i [^>]*>/i',
				'/<u [^>]*>/i',
				'/<ul [^>]*>/i',
				'/<li [^>]*>/i'
			),
			array(
				'[p]\\1[/p]',
				'[code]\\1[/code]',
				'[b]','[b]',
				'[i]',
				'[u]',
				'[ul]',
				'[li]'
			),
			$bbcode
		);

		$_smileys = $this->_getSmileyArrays();
		$this->_output_smiley = $_smileys['classes'];

		$bbcode = preg_replace_callback(
			'#<span([^>]*)><span>(.*?)<\/span><\/span>#is',
			array(&$this, "_outputsmileys"),
			$bbcode
		);

		$bbcode = preg_replace_callback(
			'#<div([^>]*)>(.*?)<\/div>#is',
			array(&$this, "_output"),
			$bbcode
		);

		$bbcode = preg_replace_callback(
			'#<span([^>]*)>(.*?)<\/span>#is',
			array(&$this, "_output"),
			$bbcode
		);

		$bbcode = preg_replace_callback(
			'#<a([^>]*)>(.*?)<\/a>#is',
			array(&$this, "_outputlink"),
			$bbcode
		);

		$bbcode = preg_replace_callback(
			'#<img([^>]*)>#i',
			array(&$this, "_output_images"),
			$bbcode
        );

		// delete Line breaks
		$bbcode = str_replace(array("\r\n","\n","\r"), '', $bbcode);
		$bbcode = str_replace(array("<br>","<br />"), "\n", $bbcode);

		$bbcode = Utils_Security_Orthos::removeHTML($bbcode);

		return $bbcode;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $params
	 */
	private function _output($params)
	{
		$params[1] = str_replace('\"', '"', $params[1]);

		if (strpos($params[1], 'style="') === false)
		{
			if (substr($params[0], 0, 4) == '<div') {
				return '<br />'.$params[2].'<br />';
			}

			return $params[2];
		}

		// Style auseinander frimmeln
		$str = $params[2];
		$_s  = $params[1];

		$_s = preg_replace(
			array('/style="([^"]*)"/i'),
			array('\\1'),
			$_s
		);

		if (strpos($_s, 'font-weight') && strpos($_s, 'bold')) {
			$str = '[b]'.$str.'[/b]';
		}

		if (strpos($_s, 'font-style') && strpos($_s, 'italic')) {
			$str = '[i]'.$str.'[/i]';
		}

		if (strpos($_s, 'text-decoration') && strpos($_s, 'underline')) {
			$str = '[u]'.$str.'[/u]';
		}

		if (strpos($_s, 'text-decoration') && strpos($_s, 'line-through')) {
			$str = '[s]'.$str.'[/s]';
		}


		if (strpos($_s, 'text-align') && strpos($_s, 'center')) {
			$str = '[center]'.$str.'[/center]';
		}

		if (strpos($_s, 'text-align') && strpos($_s, 'left')) {
			$str = '[left]'.$str.'[/left]';
		}

		if (strpos($_s, 'text-align') && strpos($_s, 'right')) {
			$str = '[right]'.$str.'[/right]';
		}

		if (substr($params[0],0, 4) == '<div') {
			return '<br />'.$str.'<br />';
		}

		return $str;
	}

	/**
	 * HTML Smileys in BBCode umwandeln
	 *
	 * @param String $_s - html string to replace
	 */
	protected function _outputsmileys($_s)
	{
		// Smileys
		$_s = preg_replace(
			array('/.class="([^"]*)"/i'),
			array('\\1'),
			$_s
		);

		if (!isset($_s[1])) {
			return $_s;
		}

		if (isset($this->_output_smiley[$_s[1]])) {
			return $this->_output_smiley[$_s[1]];
		}

		return $_s[2];
	}

	/**
	 * Parst Links in BBCode Links um
	 *
	 * @param Array $params
	 * @return String
	 */
	protected function _outputlink($params)
	{
		$attributes = str_replace('\"', '"', $params[1]);
		$cssclass   = 'extern';

		if (strpos($attributes, 'class="intern"')) {
			$cssclass = 'intern';
		}

		$url  = preg_replace('/(.*?)href="([^"]+).*"/is', '\\2', $attributes);
		$link = '[url="'. $url .'" class="'. $cssclass .'"]'. $params[2] .'[/url]';

		return $link;
	}

	/**
	 * Parst Bilder in BBCode Links um
	 *
	 * @param unknown_type $params
	 * @return unknown
	 */
	protected function _output_images($params)
	{
		$img = str_replace('\"', '"', $params[0]);

		// Falls in der eigenen Sammlung schon vorhanden
		if (strpos($img, 'image.php') !== false &&
			strpos($img, 'pms=1') !== false)
		{
			$att = Utils_String::getHTMLAttributes($img);

			if (isset($att['src']))
			{
				$src = str_replace('&amp;', '&', $att['src']);
				$url = Utils_String::getUrlAttributes($src);

				if (isset($url['project']) && $url['id'])
				{
					$project = $url['project'];
					$id      = $url['id'];

					if (!isset($this->_projects[ $project ]))
					{
						try
						{
							$Project = new Project($project);
							$this->_projects[ $project ] = $Project;

						} catch (QException $e)
						{
							return '';
						}
					}

					$Project = $this->_projects[ $project ];
					$Media   = $Project->getMedia();

					try
					{
						$Image = $Media->get( (int)$id ); /* @var $Image MF_Image */
					} catch (QException $e)
					{
						return '';
					}

					$str         = '[img="'.$Image->getUrl(true).'" ';
					$_attributes = $this->_size($att);

					if (isset($_attributes['width'])) {
						$str .= ' width="'.$_attributes['width'].'"';
					}

					if (isset($_attributes['height'])) {
						$str .= ' height="'.$_attributes['height'].'"';
					}

					if (isset($att['align'])) {
						$str .= ' align="'.$att['align'].'"';
					}

					$str .= ']';

					return $str;
				}
			}
		}

		if (strpos($img, '/media/cache/') || $this->getAttribute('extern_image'))
		{
			$att = Utils_String::getHTMLAttributes($img);

			if (!isset($att['src'])) {
				return '';
			}

			$str = '[img="'. $att['src'] .'"';

			$_attributes = $this->_size($att);

			if (isset($_attributes['width'])) {
				$str .= ' width="'.$_attributes['width'].'"';
			}

			if (isset($_attributes['height'])) {
				$str .= ' height="'.$_attributes['height'].'"';
			}

			if (isset($att['align'])) {
				$str .= ' align="'.$att['align'].'"';
			}

			$str .= ']';

			return $str;
		}

		// externe Bilder werden nicht erlaubt
		return '';
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $attributes
	 * @return unknown
	 */
	protected function _size($attributes)
	{
		$size = array();

		if (isset($attributes['style']))
		{
			$style = Utils_String::splitStyleAttributes($attributes['style']);

			if (isset($style['width'])) {
				$size['width'] = (int)$style['width'];
			}

			if (isset($style['height'])) {
				$size['height'] = (int)$style['height'];
			}

		} else
		{
			if (isset($attributes['width'])) {
				$size['width'] = (int)$attributes['width'];
			}

			if (isset($attributes['height'])) {
				$size['height'] = (int)$attributes['height'];
			}
		}

		return $size;
	}

	/**
	 * Entfernt HTML und wandelt BBCode in HTML um
	 *
	 * @param String $bbcode
	 * @param Bool $delete_html - delete rest html which was not interpreted?
	 * @return String
	 */
	public function parseToHTML($bbcode, $delete_html=true)
	{
		if ($delete_html) {
			$bbcode = Utils_Security_Orthos::removeHTML($bbcode);
		}

		// Normal HTML Elemente
		$html = str_replace(array(
			'[b]','[/b]',

			'[i]','[/i]',
			'[u]','[/u]',
			'[s]','[/s]',

			'[li]','[/li]',
			'[ul]','[/ul]',

			'[center]', '[/center]',
			'[left]', '[/left]',
			'[right]', '[/right]',

			'[br]',

			'[h1]', '[/h1]',
			'[h2]', '[/h2]',
			'[h3]', '[/h3]',
			'[h4]', '[/h4]',
			'[h5]', '[/h5]',
			'[h6]', '[/h6]',
			/*
			':-)', ':)',
			':D', ':-D',
			':-(', ':(',
			':P', ':-P',
			':confused:',
			':shocked:'
			*/
		), array(
			'<b>','</b>',

			'<i>', '</i>',
			'<u>','</u>',
			'<strike>','</strike>',

			'<li>', '</li>',
			'<ul>','</ul>',

			'<div style="text-align: center">','</div>',
			'<div style="text-align: left">','</div>',
			'<div style="text-align: right">','</div>',

			'<br />',

			'<h1>', '</h1>',
			'<h2>', '</h2>',
			'<h3>', '</h3>',
			'<h4>', '</h4>',
			'<h5>', '</h5>',
			'<h6>', '</h6>',
			/*
			'<span class="smile_smile"><span>:-)</span></span>', '<span class="smile_smile"><span>:-)</span></span>',
			'<span class="smile_biggrin"><span>:D</span></span>', '<span class="smile_biggrin"><span>:D</span></span>',
			'<span class="smile_frown"><span>:-(</span></span>', '<span class="smile_frown"><span>:-(</span></span>',
			'<span class="smile_tongue"><span>:P</span></span>', '<span class="smile_tongue"><span>:P</span></span>',
			'<span class="smile_confused"><span>:S</span></span>',
			'<span class="smile_shocked"><span>:O</span></span>'
			*/
		), $bbcode);

		// Smileys
		$smileys = $this->_getSmileyArrays();
		$html    = str_replace($smileys['code'], $smileys['replace'], $html);

		// Block Elemente
		$html = preg_replace(
			array(
				'/\[p\](.*?)\[\/p\]/',
				'/\[code\](.*?)\[\/code\]/',
				'/\[php\](.*?)\[\/php\]/',
			),
			array(
				'<p>\\1</p>',
				'<pre class="code">\\1</pre>',
				'<pre class="php">\\1</pre>'
			),
			$html
		);

		$html = preg_replace_callback(
			'/\[url=([^\]]*)\](.*?)\[\/url\]/is',
			array(&$this, "_outputlinkhtml"),
			$html
		);

		$html = preg_replace_callback(
			'/\[img=([^\]]*)]/is',
			array(&$this, "_output_image_html"),
			$html
		);

		$html = preg_replace_callback(
			'/\[email([^\]]*)](.*?)\[\/email\]/is',
			array(&$this, "_output_mail_html"),
			$html
		);

		// Line breaks
		$html = str_replace(array("\r\n","\n","\r"), "<br />", $html);

		return $html;
	}

	/**
	 * Smileys Array
	 *
	 * @return Array
	 */
	protected function _getSmileyArrays()
	{
		$_s_code    = array();
		$_s_replace = array();
		$_s_classes = array();
		$_smileys   = $this->_smileys;

		foreach ($_smileys as $smiley => $class)
		{
			$_s_code[]    = $smiley;
			$_s_replace[] = '<span class="'. $class .'"><span>'. $smiley .'</span></span>';

			$_s_classes[$class] = $smiley;
		}

		return array(
			'code'    => $_s_code,
			'replace' => $_s_replace,
			'classes' => $_s_classes
		);
	}

	/**
	 * Wandelt BBCode Links in HTML um
	 *
	 * @param unknown_type $params
	 * @return unknown
	 */
	protected function _outputlinkhtml($params)
	{
		$link = $params[2];
		$url  = preg_replace('/"([^"]+).*"(.*?)/is', '\\1', $params[1]);

		$cssclass = 'extern';

		if (strpos($url, 'http://') === false) {
			$cssclass = 'intern';
		}

		$url = str_replace(array('"', "'"), '', $url);

		return '<a href="'. $url .'" class="'. $cssclass .'">'.$link.'</a>';
	}

	/**
	 * Wandelt BBCode Images in HTML um
	 *
	 * @param unknown_type $params
	 * @return unknown
	 */
	protected function _output_image_html($params)
	{
		$str = '<img ';
		$p   = explode(' ', $params[1]);

		$str .= 'src="'. str_replace('"', '', $p[0]) .'" ';
		unset($p[0]);

		foreach ($p as $value)
		{
			if (!empty($value)) {
				$str .= $value.' ';
			}
		}

		$str .= '/>';

		return $str;
	}

	/**
	 * Wandelt BBCode Email in HTML um
	 *
	 * @param unknown_type $params
	 * @return unknown
	 */
	protected function _output_mail_html($params)
	{
		$str  = '<a ';
		$mail = str_replace('=', '', $params[1]);

		if (empty($mail)) {
			$mail = $params[2];
		}

		$str .= 'href="mailto:'. $mail .'"';
		$str .= '>'. $params[2] .'</a>';

		return $str;
	}

	/**
	 * Fügt ein Smileys ein
	 *
	 * @param unknown_type $bbcode
	 * @param unknown_type $cssclass
	 */
	public function addSmiley($bbcode, $cssclass)
	{
		$this->_smileys[$bbcode] = $cssclass;
	}

	/**
	 * Entfernt ein Smiley Code
	 *
	 * @param unknown_type $bbcode
	 * @return unknown
	 */
	public function removeSmiley($bbcode)
	{
		if (isset($this->_smileys[$bbcode])) {
			unset($this->_smileys[$bbcode]);
		}

		return true;
	}
}

?>