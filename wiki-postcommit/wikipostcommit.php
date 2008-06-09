<?php

require_once('Mail.php'); // PEAR Mail

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
$wpcBotEmail = 'freecult@localhost';
$wpcEmailSubject = "";
$wpcEmailMessage = "";
$wpcAPI_Base = 'http://wiki.freeculture.org/api.php'; # FIXME could be detected

# ------------------- CUT HERE --------------------
# ONLY EDIT BELOW THIS LINE IF YOU THINK YOU ARE
# SMART ENOUGH TO HACK BAD PHP
#
# DO NOT EDIT BELOW THIS LINE IF YOU REALIZE YOU ARE
# TOO SMART TO HACK BAD PHP


// FIXME: This is hilariously slow and calls the API despite the fact that we're internal.
// FIXME: Also, this function is a massive race condition in its correctness.
function wpc_is_article_new(&$article) {
    return TRUE;
  $api_call_url = $wpcAPI_BASE . '?action=query&titles=' . urlencode($article) . 
    '&prop=revisions&rvprop=timestamp&rvlimit=2&rvdir=newer&format=json';
  $fopened = fopen($api_call_url);
  if ($fopened === FALSE) {
    // Uh, oh well.
    return FALSE;
  } else {
    // Grab the response
    $response_json = stream_get_contents($fopened);
    fclose($fopened);

    $response = json_decode($response_json, $assoc = TRUE);

    $pages = $response['query']['pages'];
    $page_ids = array_keys($pages);
    $first_page_id = $page_ids[0];
    $revisions = $pages[$first_page_id]['revisions'];
    if (count($revisions) == 1) {
        // Then the page is new, I suppose
        return TRUE;
    } // endif
  } // end else
  return FALSE;
}
  
function fnWikiPostCommit(&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor, &$flags, $revision) {
    //if (wpc_is_article_a_chapter_page($article, $text)) {
      //if (wpc_is_article_new($article)) {
    	  wpc_send_article_by_email($article, $user, $text);
      //}
    //}
   return TRUE;
}

function wpc_send_article_by_email(&$article, &$user, &$text) {
  global $wpcBotEmail;

  $headers = array();
  $headers['From'] = '"FreeCulture.org Chapter Registration bot" <webleader@freeculture.org>';
  $headers['Return-Path'] = 'webleader@freeculture.org';
  $headers['To'] = $wpcBotEmail;
  $headers['Date'] = date('r'); // r format is hopefully RFC2822
  $headers['Subject'] = 'New chapter registered: ' . $article->mTitle;
  $headers['X-New-Chapter-Registration'] = $article->mTitle;
  
  $body =  "IGNORE ME!\r\n";

  $params['host'] = 'localhost';
  $mail_object = & Mail::factory('smtp', $params);
  $send = $mail_object->send($wpcBotEmail, $headers, $body);
  if (PEAR::isError($send)) { print($send->getMessage()); }
  // Send the mail sometime.  FIXME
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
$wgHooks['ArticleSaveComplete'][] = 'fnWikiPostCommit';

?>
