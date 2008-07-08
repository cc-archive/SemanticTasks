<?php
# (C) 2008 Steren Giannini
# Licensed under the GNU GPLv2 (or later).

function fnMailAssignees_updated_task(&$article, &$user, &$text, &$summary, &$minoredit, &$watchthis, &$sectionanchor, &$flags, &$revision)
{
    //i18n
    wfLoadExtensionMessages( 'SemanticTasks' );

    //Grab the wiki name
    global $wgSitename;

    //Get the revision count to determine if new article
    $rev = $article->estimateRevisionCount();	

    if($rev == 1)
    {
        fnMailAssignees(&$article, $user,'['.$wgSitename.'] '. wfMsg('newtask'), 'assignedtoyou_msg', /*diff?*/ false );
    }else
    {
        fnMailAssignees(&$article, $user,'['.$wgSitename.'] '. wfMsg('taskupdated'), 'updatedtoyou_msg', /*diff?*/ true );
    }
    return TRUE;
}

function fnMailAssignees(&$article, &$user, $pre_title, $message, $display_diff)
{
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
    if( $task_assignees == '' ) { return FALSE; }
    
    $user_mailer = new UserMailer();

    if($display_diff)
    {
        //here we generate a diff
        $revision = Revision::newFromTitle ($title,0);
        $diff = new DifferenceEngine($title,$revision->getId(),'prev');
        //The getDiffBody() method generates html, so let's generate the txt diff manualy:
            global $wgContLang;
            $diff->loadText();
        	$otext = str_replace( "\r\n", "\n", $diff->mOldtext );
        	$ntext = str_replace( "\r\n", "\n", $diff->mNewtext );
            $ota = explode( "\n", $wgContLang->segmentForDiff( $otext ) );
        	$nta = explode( "\n", $wgContLang->segmentForDiff( $ntext ) );
            //We use here the php diff engine included in MediaWiki 
        	$diffs = new Diff( $ota, $nta );
            //And we ask for a txt formatted diff
            $formatter = new UnifiedDiffFormatter();		
            $diff_text = $wgContLang->unsegmentForDiff( $formatter->format( $diffs ) );
    }

    while ($task_assignee = $task_assignees->getNextObject())
    {
        $assignee_username = $task_assignee->getTitle();
        $assignee_user_name = explode(":",$assignee_username);
        $assignee_name = $assignee_user_name[1];
        $body = wfMsg($message , $assignee_name , $title) . $link;
        if($display_diff){ $body .= "\n \n". wfMsg('diff-message') . "\n" . $diff_text; }

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
    //i18n
    wfLoadExtensionMessages( 'SemanticTasks' );

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


function fnRemindAssignees($wiki_url)
{
    global $wgSitename, $wgServer;

    $user_mailer = new UserMailer();

    $t = getdate();
    $today = date('F d Y',$t[0]);

    $query_string = "[[Reminder at::+]][[Status::New||In Progress]][[Target date::> $today]][[Reminder at::*]][[Assigned to::*]][[Target date::*]]";
    $results = st_get_query_results($query_string);
    if( $task_assignees == '' ) { return FALSE; }

    $sender = new MailAddress("no-reply@$wgServerName","$wgSitename");

    while ($row = $results->getNext())
    {
        $task_name = $row[0]->getNextObject()->getTitle();
        $subject = '['.$wgSitename.'] '.wfMsg('reminder').$task_name;
        //The following doesn't work, maybe because we use a cron job.        
        //$link = $task_name->escapeFullURL();
        //So let's do it manually
        $link = $wiki_url . $task_name->getPartialURL();

        $target_date = $row[3]->getNextObject();
        $tg_date = new DateTime($target_date->getShortHTMLText());

        while ($reminder = $row[1]->getNextObject())
        {
            $remind_me_in = $reminder->getShortHTMLText();
            $date = new DateTime($today);
            $date->modify("+$remind_me_in day");

            if($tg_date-> format('F d Y') == $date-> format('F d Y') )
            {
                while ($task_assignee = $row[2]->getNextObject())
                {
                    $assignee_username = $task_assignee->getTitle();
                    $assignee_user_name = explode(":",$assignee_username);
                    $assignee_name = $assignee_user_name[1];

                    $assignee = User::newFromName($assignee_name);
                    $assignee_mail = new MailAddress($assignee->getEmail(),$assignee_name);
                    $body = wfMsg('reminder-message' , $assignee_name , $task_name , $remind_me_in , $link);
                    $user_mailer->send($assignee_mail, $sender, $subject, $body );
                }        
            }            
        }
    }
    return TRUE;
}

function st_SetupExtension()
{
    global $wgHooks;
    $wgHooks['ArticleSaveComplete'][] = 'fnMailAssignees_updated_task';
    return true;
}

?>
