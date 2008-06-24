<?php
    $t = getdate();
    $today = date('F d Y',$t[0]);
    echo "Today" . $today . "\n" ;

    $remind_me_in = 2;

    $date = new DateTime($today);
    echo "Today" . $date-> format('F d Y') . "\n";

    $date->modify("+$remind_me_in day");
    echo "Today + 2 days" . $date-> format('F d Y') . "\n";

    $target_date = new DateTime('19 June 2008');
    echo "Target date" . $target_date-> format('F d Y') . "\n";

    if($date == $target_date) echo "good !\n";
    else echo "not good";

?>
