<?php

use JVelletti\Jvchat\Eid\Chat;
use TYPO3\CMS\Frontend\Utility\EidUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

$timer = new Timer();
$timer->enabled = ($_GET['d'] == 'timer' || $_POST['d'] == 'timer');
$timer->start('all');

// Exit, if script is called directly (must be included via eID in index_ts.php)
if (!defined ('TYPO3')) die ('JV Chat (old eId): Could not access this script directly!');

// Initialize FE user object:
/** @var FrontendUserAuthentication $feUserObj */
$feUserObj = EidUtility::initFeUser();
$charset = 'utf-8';

// ##################
// ## HEADER

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");   // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
//header("Cache-Control: post-check=0, pre-check=0", false);
// header('Content-type: text/plain; charset='.$charset);
// header("strict-transport-security:max-age=31536000");
// header("X-Frame-Options:SAMEORIGIN");
// header("X-Xss-Protection: 1; mode=block");
// header("X-Content-Type-Options: nosniff");
// header("Content-Security-Policy: default-src https: 'unsafe-eval' 'unsafe-inline'; font-src https: data: filesystem: 'unsafe-inline'; img-src https: data: ;");

// ################

$timer->start('chat');
/** @var Chat $chat */
$chat = GeneralUtility::makeInstance(Chat::class);
$chat->init($feUserObj, $charset, false);
print $chat->perform();


$timer->stop('chat');
$timer->stop('all');

if($_GET['d'] == 'timer' || $_POST['d'] == 'timer') {
    print '<debug>'.$timer->output().'</debug>';
}


class Timer {
    var	$timers = array();

    var $dec = 1;

    var $precision = 4;

    var $enabled = true;

    function start($label) {

        if(!$this->enabled)
            return;

        $this->timers[$label]['start'] = microtime();

        $backtrace = debug_backtrace();

        $this->timers[$label]['line'] = $backtrace[0]['line'] ;
        while(strlen($this->timers[$label]['line']) < 4)
            $this->timers[$label]['line'] = '0'.$this->timers[$label]['line'];
    }

    function stop($label) {
        $this->timers[$label]['end'] = microtime();
    }

    function output() {
        $out = '';
        foreach($this->timers as $key => $timer) {
            $time = ($this->getMicrotime($timer['end']) -  $this->getMicrotime($timer['start']));
            $out .= '&lt;stat label="'.$key.'" time="'.$this->format($time).'" /&gt;';
        }

        return '&lt;stats&gt;'.$out.'&lt;/stats&gt;<br>';
    }

    function getMicrotime($microtime = NULL) {
        if(!$microtime)
            $microtime = microtime();
        list($usec, $sec) = explode(" ", $microtime);
        return ((float)$usec + (float)$sec);
    }

    function format($time) {
        $timeArray = explode('.',$time);

        while(strlen($timeArray[0]) < $this->dec)
            $timeArray[0] = '0'.$timeArray[0];

        while(strlen($timeArray[1]) < $this->precision)
            $timeArray[1] = $timeArray[1].'0';

        $timeArray[1] = substr($timeArray[1], 0, $this->precision);

        return implode('.', $timeArray);

    }
}
