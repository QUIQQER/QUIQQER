<?php

/**
 * This file contains Utils_Api_Google_YouTubePlayer
 */

/**
 * Easy Access to YouTubePlayer Api
 *
 * @author www.pcsg.de (Moritz Scholz)
 * @copyright 2009 PCSG
 *
 * @package com.pcsg.qui.utils.api.google
 *
 * @example $YouTubePlayer  = new Utils_Api_Google_YouTubePlayer($videocode);
 *
 * @Settings
 *	$YouTubePlayer->setAttribute('playerDiv', 'meineDivID');
 *	$YouTubePlayer->setAttribute('parentId', 'meineParentID');
 *
 *	$YouTubePlayer->setAttribute('autoplay', '1');
 *	$YouTubePlayer->setAttribute('loop','1');
 *	$YouTubePlayer->setAttribute('rel','0');
 *	$YouTubePlayer->setAttribute('enablejsapi','1');
 *	$YouTubePlayer->setAttribute('autohide','2');
 *	$YouTubePlayer->setAttribute('controls','1');
 *	$YouTubePlayer->setAttribute('playerapiid','1');
 *	$YouTubePlayer->setAttribute('disablekb','1');
 *	$YouTubePlayer->setAttribute('egm','1');
 *	$YouTubePlayer->setAttribute('border','1');
 *	$YouTubePlayer->setAttribute('color1','000000');
 *	$YouTubePlayer->setAttribute('color2','ffffff');
 *	$YouTubePlayer->setAttribute('fs','1');
 *	$YouTubePlayer->setAttribute('showsearch','1');
 *	$YouTubePlayer->setAttribute('showinfo','1');
 *	$YouTubePlayer->setAttribute('iv_load_policy','1');
 *	$YouTubePlayer->setAttribute('load_policy','1');
 *	$YouTubePlayer->setAttribute('width','480');
 *	$YouTubePlayer->setAttribute('height','320');
 *	$YouTubePlayer->setAttribute('start','0');
 *	$YouTubePlayer->setAttribute('end','0');
 *	$YouTubePlayer->setAttribute('bgcolor','ffffff');
 *	$YouTubePlayer->setAttribute('quality','22'); # 6=Low,18=High,22=HD
 *
 */
class Utils_Api_Google_YouTubePlayer extends \QUI\QDOM
{
    /**
     * YouTube video code
     * @var String
     */
    protected $_ytcode = null;

    /**
     * Youtube url
     * @var String
     */
    protected $_ytUrl = 'http://www.youtube.com/v/';

    /**
     * Settings
     * @var array
     */
    protected $_settings = array(
        'autoplay' 		 => '0',
        'enablejsapi'    => '0',
        'autohide'       => '2',
        'controls'       => '1',
        'origin'         => null,
        'start'          => null,
        'theme'          => 'dark',
        'loop' 			 => '0',
        'rel' 			 => '1',
        'playerapiid'  	 => '',
        'disablekb' 	 => '0',
        'egm' 			 => '0',
        'border' 		 => '0',
        'color1' 		 => '',
        'color2' 		 => '',
        'fs' 			 => '1',
        'showsearch' 	 => '0',
        'showinfo' 		 => '0',
        'iv_load_policy' => '3',
        'load_policy' 	 => '0',
        'width' 		 => '640',
        'height' 		 => '390',
        'quality' 		 => '18',
        'title' 		 => 'Video',
        'bgcolor' 		 => '#000000'
    );

    /**
     * Konstruktor
     *
     * @param $ytcode #YouTubeID oder Youtube URL
     */
    public function __construct($ytcode)
    {
        $this->_ytcode = self::getYouTubeId($ytcode, false);

        // Einstellungen
        foreach ($this->_settings as $key => $val) {
            $this->setAttribute($key,$val);
        }
    }

    /**
     * Gibt den Youtube Player HTML Code zurück
     *
     * @return String - player html code
     */
    public function getPlayerOld()
    {
        $html 	 = '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000"
                    width="'. $this->getAttribute('width') . '"
                    height="'. $this->getAttribute('height') . '">';
        $html 	.= ' <param name="movie" value="'. $this->getUrl() .'" />';
        $html 	.= ' <param name="quality" value="high" />';
        $html 	.= ' <param name="bgcolor" value="'. $this->getAttribute('bgcolor') .'" />';
        $html 	.= ' <param name="wmode" value="opaque" />';
        $html 	.= ' <param name="menu" value="false" />';
        $html 	.= ' <param name="allowfullscreen" value="true" />';
        $html 	.= ' <embed ';
        $html 	.= ' id="'. $this->getAttribute('title') .'"';
        $html 	.= ' quality="high"';
        $html 	.= ' width="'. $this->getAttribute('width') .'"';
        $html 	.= ' height="'. $this->getAttribute('height') .'"';
        $html 	.= ' type="application/x-shockwave-flash"';
        $html 	.= ' src="'. $this->getUrl() .'"';
        $html 	.= ' name="'. $this->getAttribute('title') .'"';
        $html 	.= ' bgcolor="'. $this->getAttribute('bgcolor') .'"';
        $html 	.= ' wmode="opaque"';
        $html 	.= ' menu="false"';
        $html 	.= ' allowfullscreen="true"';
        $html   .= $this->getAttribute('embed_extra');
        $html 	.= '></embed>';
        $html 	.= '</object>';

        return $html;
    }


    /**
     * Gibt den Youtube Player HTML Code als Div zurück
     *
     * @return String player javascript code
     */
    public function getPlayer()
    {
        $html  = '';
        $html .= '<script type="text/javascript">'."\r\n";
        $html .= $this->appendPlayer();
        $html .= '/* ]]> */</script>'."\r\n\r\n";

        $playerDiv = '';

        if ($this->getAttribute('playerDiv') !=  'ytVideo_' . $this->_getYTId()) {
            $playerDiv = '<div id="ytVideo_' . $this->_getYTId() . '" class="ytVideoDiv" '.$this->getAttribute('embed_extra') .'></div>';
        }

        $html .= $playerDiv."\r\n";

        return $html;
    }

    /**
     * Gibt den Youtube Player HTML Code als Div zurück
     *
     * @return String $PlayerCode
     */
    public function appendPlayer()
    {
        $playerDiv = '';

        if (!$this->getAttribute('playerDiv'))
        {
            $this->setAttribute('playerDiv', 'ytVideo_'. $this->_getYTId());

            if ($this->getAttribute('parentId'))
            {
                $playerDiv .= 'if(!document.getElementById("ytVideo_' . $this->_getYTId() . '")){'."\r\n";
                $playerDiv .= 'var _PlayerDiv_' . $this->_getYTId() . ' = document.createElement("div");'."\r\n";
                $playerDiv .= '_PlayerDiv_' . $this->_getYTId() . '.id = "ytVideo_' . $this->_getYTId() . '";'."\r\n";
                $playerDiv .= '_PlayerDiv_' . $this->_getYTId() . '.className = "ytVideoDic";'."\r\n";
                $playerDiv .= 'document.getElementById("' . $this->getAttribute('parentId') . '").appendChild(_PlayerDiv_' . $this->_getYTId() . ');'."\r\n\r\n";
                $playerDiv .= '};';
            }
        }

        $this->_playerHTML .= 'var _yt = document.createElement("script");'."\r\n";
        $this->_playerHTML .= '_yt.src = "http://www.youtube.com/player_api";'."\r\n";
        $this->_playerHTML .= 'var _scriptTag = document.getElementsByTagName("script")[0];'."\r\n";


        if ($this->getAttribute('parentId'))
        {
            $this->_playerHTML .= $playerDiv;
            $this->_playerHTML .= '_scriptTag.parentNode.insertBefore(_yt, _scriptTag);'."\r\n\r\n";

        } else {
            $this->_playerHTML .= '_scriptTag.parentNode.insertBefore(_yt, _scriptTag);'."\r\n\r\n";
        }

        $attr   = $this->getAllAttributes();
        $params = '';


        foreach($attr as $key => $value)
        {
            if(is_numeric($value) || !empty($value))
            {
                $params .= "'{$key}': ";

                if(is_numeric($value)) {
                    $params .= $value;
                } else {
                    $params .= "'{$value}'";
                }

                $params .= ',';
            }
        }

        $this->_playerHTML .= '_ytPlayer_' . $this->_getYTId() . ' = null;'."\r\n";
        $this->_playerHTML .= 'onYouTubePlayerAPIReady = function() {'."\r\n";
        $this->_playerHTML .= '_ytPlayer_' . $this->_getYTId() . ' = new YT.Player("'.$this->getAttribute('playerDiv').'", {'."\r\n";
        $this->_playerHTML .= 'height: "'.$this->getAttribute('height').'",'."\r\n";
        $this->_playerHTML .= 'width:  "'.$this->getAttribute('width').'",'."\r\n";

        if (!empty($params)) {
            $this->_playerHTML .= 'playerVars: {'.substr($params,0,-1).'},'."\r\n";
        }

        $this->_playerHTML .= 'videoId: "'.$this->_ytcode.'"'."\r\n";

        $this->_playerHTML .= '});'."\r\n";
        $this->_playerHTML .= '};'."\r\n\r\n";
        $this->_playerHTML .= $playerDiv."\r\n";

        $this->_playerHTML .= 'if (typeof YT !== \'undefined\' && YT.Player !== \'undefined\') {';
        $this->_playerHTML .= 'onYouTubePlayerAPIReady();';
        $this->_playerHTML .= '}';

        return $this->_playerHTML;
    }

    /**
     * Erstellt die YouTube URL mit allen Parametern
     *
     * @return string $movieUrl
     */
    private function getUrl()
    {
        $movieUrl  = $this->_ytUrl;
        $movieUrl .= $this->_ytcode;
        $movieUrl .= '&ap=%2526fmt%3D'	. $this->getAttribute('quality');
        $movieUrl .= '&rel='			. $this->getAttribute('rel');
        $movieUrl .= '&autoplay='		. $this->getAttribute('autoplay');
        $movieUrl .= '&loop='			. $this->getAttribute('loop');
        $movieUrl .= '&enablejsapi='	. $this->getAttribute('enablejsapi');
        $movieUrl .= '&playerapiid='	. $this->getAttribute('playerapiid');
        $movieUrl .= '&disablekb='		. $this->getAttribute('disablekb');
        $movieUrl .= '&egm='			. $this->getAttribute('egm');
        $movieUrl .= '&border='			. $this->getAttribute('border');
        $movieUrl .= '&color1='			. $this->getAttribute('color1');
        $movieUrl .= '&color2='			. $this->getAttribute('color2');
        $movieUrl .= '&fs='				. $this->getAttribute('fs');
        $movieUrl .= '&showsearch='		. $this->getAttribute('showsearch');
        $movieUrl .= '&showinfo='		. $this->getAttribute('showinfo');
        $movieUrl .= '&iv_load_policy=' . $this->getAttribute('iv_load_policy');
        $movieUrl .= '&load_policy=' 	. $this->getAttribute('load_policy');

        if ($this->getAttribute('start')) {
            $movieUrl .= '&start=' . $this->getAttribute('start');
        }

        if ($this->getAttribute('end')) {
            $movieUrl .= '&end=' . $this->getAttribute('end');
        }

        return htmlspecialchars($movieUrl);
    }

    /**
     * YouTube Player - ID für das DIV und JavaScript
     * @return String
     */
    protected function _getYTId()
    {
        return preg_replace('/[^a-zA-Z0-9]/i', '', $this->_ytcode);
    }

    /**
     * Return only the youtube id, if the id is correct
     *
     * @param String $youTubeString
     * @param Bool $check true/false
     *
     * @return string youTubeId
     * @throws \QUI\Exception
     */
    static function getYouTubeId($youTubeString=false, $check=true)
    {
        if (strpos($youTubeString, 'v='))
        {
            $params = \QUI\Utils\String::getUrlAttributes($youTubeString);

            if (isset($params['v'])) {
                return $params['v'];
            }

            throw new \QUI\Exception('Keine YouTube Id gefunden');
        }

        if (strpos($youTubeString, 'http://youtu.be/') !== false) {
            return str_replace('http://youtu.be/', '', $youTubeString);
        }

        if ($check) {
            self::checkYouTubeId($youTubeString);
        }

        return $youTubeString;
    }

    /**
     * Prüft eine YouTube Id
     *
     * @param String $yid
     *
     * @return String $yid
     */
    static function checkYouTubeId($yid)
    {
        $url = 'http://www.youtube.com/watch?v='. $yid;

        $httpcode = Utils_Request_Linkchecker::checkUrl($url);

        if ($httpcode >= 400) {
            throw new \QUI\Exception('Die YouTube Id existiert nicht', $httpcode);
        }

        return true;
    }
}

?>