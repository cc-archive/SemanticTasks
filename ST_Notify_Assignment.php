<?php
# (C) 2008 Steren Giannini
# Licensed under the GNU GPLv2 (or later).

function fnMailAssignees_updated_task(&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor, &$flags, &$revision)
{
    //Get the revision count to determine if new article
    $rev = $article->estimateRevisionCount();	

    if($rev == 1)
    {
        fnMailAssignees(&$article, $user,'[Teamspace] New task:','has just been assigned to you');
    }else
    {
        fnMailAssignees(&$article, $user,'[Teamspace] Task updated:','has just been updated');
    }
    return TRUE;
}

function fnMailAssignees(&$article, &$user, $pre_title, $message)
{
    //This is for test
    // TODO : remove
    $me = new MailAddress("steren.giannini@gmail.com","Moi");


    //We force here SMW to store the semantic data.
    //Hooks are supposed to be executed in the order they are declared, but This is not the case here.
    smwfSaveHook($article);

    $title = $article->getTitle();
    $subject = "$pre_title $title";
    $from = new MailAddress($user->getEmail(),$user->getName());
    
    $link = $title->escapeFullURL();   

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
//TODO : remove                  $user_mailer->send( $assignee_mail, $from, $subject, $body );
            $user_mailer->send( $me, $from, $subject, $body );
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

    $query_string = "[[Reminder at::+]][[Target date::> $today]][[Reminder at::*]][[Assigned to::*]][[Target date::*]]";
    $results = st_get_query_results($query_string);

    $sender = new MailAddress("no-reply@creativecommons.org","Teamspace");
    //This is for test
    // TODO : remove
    $me = new MailAddress("steren.giannini@gmail.com","Moi");

    while ($row = $results->getNext())
    {
        $task_name = $row[0]->getNextObject()->getTitle();
        $subject = "[Teamspace] Reminder: $task_name";
        $link = $task_name->escapeFullURL();

        $target_date = $row[3]->getNextObject();
        $tg_date = new DateTime($target_date->getShortHTMLText());

        while ($reminder = $row[1]->getNextObject())
        {
            $remind_me_in = $reminder->getShortHTMLText();
            $date = new DateTime($today);
            $date->modify("+$remind_me_in day");

            if($tg_date == $date)
            {
                while ($task_assignee = $row[2]->getNextObject())
                {
                    $assignee_username = $task_assignee->getTitle();
                    $assignee_user_name = explode(":",$assignee_username);
                    $assignee_name = $assignee_user_name[1];

                    $assignee = User::newFromName($assignee_name);
                    $assignee_mail = new MailAddress($assignee->getEmail(),$assignee_name);
                    $body = "Hello $assignee_name, \nJust to remind you that the task \"$task_name\" ends in $remind_me_in days.\n\n$link" . " Date " . $date->format('F d Y') . " Target date : " . $tg_date->format('F d Y');
//TODO : remove                    $user_mailer->send( $assignee_mail, $sender, $subject, $body );
                    $user_mailer->send( $me, $sender, $subject, $body );

                }        
            }            
        }
    }
    return TRUE;
}

$wgHooks['ArticleSaveComplete'][] = 'fnMailAssignees_updated_task';

?>
