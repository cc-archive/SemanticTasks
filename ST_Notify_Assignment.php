<?php

require_once($stIP . '/XPM4/SMTP.php'); // XPM4

# This is a (fairly) simple hack to notify people when a new chapter page has been created.
#   When a wiki article is saved,
#   If that article is now a member of "Category:Chapter" AND it was not previously,
#   Send an email to freedom@freeculture.org and the email address of the user that created/modified the article.

# This is by no means an elegant hack. At the moment we use our own parser to determine category membership, as I
# couldn't find another simple method to do this in a reasonable amount of time or searching. Hopefully
# this will change in the near future.

# (C) 2007 AMP and Asheesh Laroia
# Licensed under the GNU GPLv2 (or later).

# CONFIGURATION
$wpcSearchStrings = array("[[Category:Chapter]]", "{{Chapter");
$wpcBotEmail = 'webmaster+tasks@creativecommons.org';
$wpcEmailSubject = "";
$wpcEmailMessage = "";
$wpcAPI_Base = 'http://wiki.freeculture.org/api.php'; # FIXME could be detected

# ------------------- CUT HERE --------------------
# ONLY EDIT BELOW THIS LINE IF YOU THINK YOU ARE
# SMART ENOUGH TO HACK BAD PHP
#
# DO NOT EDIT BELOW THIS LINE IF YOU REALIZE YOU ARE
# TOO SMART TO HACK BAD PHP

function st_get_assignees_to_notify(&$article) {

        global $smwgIP;        
	include_once($smwgIP . "/includes/SMW_QueryProcessor.php");
        $events = array();
        $query_string = "[[$date_property::*]][[$date_property::+]]$filter_query";
        $params = array();
        $inline = true;
        $format = 'auto';
        $printlabel = "";        
	$printouts[] = new SMWPrintRequest(SMW_PRINT_THIS, $printlabel);

	$query  = SMWQueryProcessor::createQuery($query_string, $params, $inline, $format, $printouts);        
	$results = smwfGetStore()->getQueryResult($query);

	while ($row = $results->getNext()) {
		$event_names = $row[0];
		$event_dates = $row[1];
		$event_title = $event_names->getNextObject()->getTitle();
	
		while ($event_date = $event_dates->getNextObject()) {
		$actual_date = date("Y-m-d", $event_date->getNumericValue());
																	                        		$events[] = array($event_title, $actual_date);
		}
	}
	return $events;
}


function st_updateAssignees(&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor, &$flags, $revision) {


$page_title = $article->getTitle();
// $user_info = smwfGetStore()->getPropertyValues($page_title, ");

$f = 'webmaster+tasks@creativecommons.org'; // from mail address
$t = 'nathan@creativecommons.org'; // to mail address

// standard mail message RFC2822
$m = 'From: '.$f."\r\n".
     'To: '.$t."\r\n".
          'Subject: test'."\r\n".
	       'Content-Type: text/plain'."\r\n\r\n".
	            $page_title;

		    $h = explode('@', $t); // get client hostname
		    $c = SMTP::MXconnect($h[1]); // connect to SMTP server (direct) from MX hosts list
		    $s = SMTP::Send($c, array($t), $m, $f); // send mail
		    // print result
		    if ($s) echo 'Sent !';
		    else print_r($_RESULT);
		    SMTP::Disconnect($c); // disconnect

   return TRUE;
}

// FIXME: Notice pageCountInfo in the private article whatever

// This whole function is terrible
function wpc_is_article_a_chapter_page(&$article, &$text) {
    $wpcSearchStrings = array('[[Category:Chapter', '{{Chapter');
    foreach($wpcSearchStrings as $needle) {
        if (strstr($text, $needle)) {
            return true;
        }
    }
    return false;
}

# When an article is saved, call the function 'fnWikiPostCommit'
# $wgHooks['ArticleSaveComplete'][] = 'st_updateAssignees';

?>
