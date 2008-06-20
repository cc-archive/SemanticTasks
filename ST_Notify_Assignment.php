<?php
# (C) 2008 Steren Giannini
# Licensed under the GNU GPLv2 (or later).

function fnMailAssignees_new_task(&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor, &$flags, &$revision)
{
    fnMailAssignees(&$article, $user,'[Teamspace] New task:','has just been assigned to you');
    return TRUE;
}

function fnMailAssignees_updated_task(&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor, &$flags, &$revision)
{
    fnMailAssignees(&$article, $user,'[Teamspace] Task updated:','has just been updated');
    return TRUE;
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

    $query_string = "[[$title]][[assigned to::*]]";
    $results = st_get_query_results($query_string);
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

function st_get_query_results(&$query_string)
{
    //We use the Semantic Media Wiki Processor
    global $smwgIP;
    include_once($smwgIP . "/includes/SMW_QueryProcessor.php");

    $task_assignees = array();

    $params = array();
    $inline = true;
    $format = 'auto';
    $printlabel = "";        
    $printouts[] = new SMWPrintRequest(SMW_PRINT_THIS, $printlabel);

    $query  = SMWQueryProcessor::createQuery($query_string, $params, $inline, $format, $printouts);        
    $results = smwfGetStore()->getQueryResult($query);

    return $results;
}


function fnRemindAssignees()
{
    $user_mailer = new UserMailer();

    $t = getdate();
    $today = date('F d Y',$t[0]);

    $subject = "[Teamspace] Reminder: ";

    $query_string = "[[reminder at::+]][[Target date::> $today]][[Reminder at::*]][[Assigned to::*]][[Target date::*]]";
    $results = st_get_query_results($query_string);

    while ($row = $results->getNext())
    {
        $task_name = $row[0]->getNextObject()->getTitle();
        $subject .= $task_name;
        $link = $task_name->getFullURL();

        $target_date = $row[3]->getNextObject();
        $date = new DateTime($target_date->getShortHTMLText());
        $date_today = new DateTime($today);

        while ($reminder = $row[1]->getNextObject())
        {
            $remind_me_in = $reminder->getShortHTMLText();
            $date_today->modify("+$remind_me_in day");

            if($date == $date_today)
            {
                while ($task_assignee = $row[2]->getNextObject())
                {
                    $assignee_username = $task_assignee->getTitle();
                    $assignee_user_name = explode(":",$assignee_username);
                    $assignee_name = $assignee_user_name[1];

                    $assignee = User::newFromName($assignee_name);
                    $assignee_mail = new MailAddress($assignee->getEmail(),$assignee_name);
                    $body = "Hello $assignee_name, \nJust to remind you that the task \"$task_name\" ends in $remind_me_in days.\n\n$link";
                    $user_mailer->send( $assignee_mail, $assignee_mail, $subject, $body );
                }        
            }            
        }
    }
    return TRUE;
}

$wgHooks['ArticleInsertComplete'][] = 'fnMailAssignees_new_task';
$wgHooks['ArticleSaveComplete'][] = 'fnMailAssignees_updated_task';


?>
