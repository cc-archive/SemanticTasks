<?php
# (C) 2008 Steren Giannini
# Licensed under the GNU GPLv2 (or later).

function fnMailAssignees_new_task(&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor, &$flags, &$revision)
{
    fnMailAssignees(&$article, $user,'New task:','has just been assigned to you');
    return TRUE;
}

function fnMailAssignees_updated_task(&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor, &$flags, &$revision)
{
    fnMailAssignees(&$article, $user,'Task updated:','has just been updated');
    return TRUE;

###########
/*
//Here starts the test for mail reminders 
    fnRemindAssignees('Reminder:', 'Do not forget');
*/
###########
}

function fnMailAssignees(&$article, &$user, $pre_title, $message)
{
    $title = $article->getTitle();
    $subject = "$pre_title $title";
    $from = new MailAddress($user->getEmail(),$user->getName());

    /*
    the send() function of MediaWiki only send plain text
    so we can't use the linker->makelinkObj() method to generate a html link.
        $l = new Linker();
        $link = $l->makeLinkObj($title);
    */
    $link = $title->getFullURL();

    $results = st_get_assignees_to_notify($title);
    while ($row = $results->getNext())
    {
        $task_assignees = $row[0];
    }

    $user_mailer = new UserMailer();

    while ($task_assignee = $task_assignees->getNextObject())
    {
        $assignee_username = $task_assignee->getTitle();
        $assignee_user_name = explode(":",$assignee_username);
        $assignee_name = $assignee_user_name[1];
        $body = "Hello $assignee_name, \nThe task \"$title\" $message.\n\n$link";

        $assignee = User::newFromName($assignee_name);

        if ($assignee->getID() != $user->getID())
        {
            $assignee_mail = new MailAddress($assignee->getEmail(),$assignee_name);
            $user_mailer->send( $assignee_mail, $from, $subject, $body );
        }
    }

    return TRUE;
}

function st_get_assignees_to_notify(&$tasktitle)
{
    //We use the Semantic Media Wiki Processor
    global $smwgIP;
    include_once($smwgIP . "/includes/SMW_QueryProcessor.php");

    $task_assignees = array();

    $query_string = "[[$tasktitle]][[assigned to::*]]";
    $params = array();
    $inline = true;
    $format = 'auto';
    $printlabel = "";        
    $printouts[] = new SMWPrintRequest(SMW_PRINT_THIS, $printlabel);

    $query  = SMWQueryProcessor::createQuery($query_string, $params, $inline, $format, $printouts);        
    $results = smwfGetStore()->getQueryResult($query);

    return $results;
}

/*
##########################################
//Here is for email reminders

function fnRemindAssignees($pre_title, $message)
{
    $t = getdate();
    $today = date('F d Y',$t[0]);

    $results = st_get_tasks_to_remind($today);
    while ($row = $results->getNext())
    {
        $task_assignees = $row[0];
    }

    $user_mailer = new UserMailer();

    while ($task_assignee = $task_assignees->getNextObject())
    {
        $assignee_username = $task_assignee->getTitle();
        $assignee_user_name = explode(":",$assignee_username);
        $assignee_name = $assignee_user_name[1];

        $assignee = User::newFromName($assignee_name);
        $assignee_mail = new MailAddress($assignee->getEmail(),$assignee_name);
        $body = "Hello $assignee_name, \nThe task \"$title\" $message.\n\n$link";
        $user_mailer->send( $assignee_mail, $from, $subject, $body );
    }

    return TRUE;
}


function st_get_tasks_to_remind(&$today)
{
    //We use the Semantic Media Wiki Processor
    global $smwgIP;
    include_once($smwgIP . "/includes/SMW_QueryProcessor.php");

    $task_assignees = array();

    $query_string = "[[reminder at::+]][[Target date::> $today]][[Assigned to::*]][[Reminder at::*]][[Target date::*]]";
    $params = array();
    $inline = true;
    $format = 'auto';
    $printlabel = "";        
    $printouts[] = new SMWPrintRequest(SMW_PRINT_THIS, $printlabel);

    $query  = SMWQueryProcessor::createQuery($query_string, $params, $inline, $format, $printouts);        
    $results = smwfGetStore()->getQueryResult($query);

    return $results;
}
//end of email reminders
*/
##########################################

$wgHooks['ArticleInsertComplete'][] = 'fnMailAssignees_new_task';
$wgHooks['ArticleSaveComplete'][] = 'fnMailAssignees_updated_task';


?>
