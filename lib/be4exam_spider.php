<?php
/*
*	spider
*	Np.1: school spider
*	No.2: subject spider with department info
*	No.3: rank spider
*
*	by liulube@126.com
*/


include dirname(__FILE__) . "/school_info.php";

//************************************************//

// school spider running

//$cl_schoolslist = new schoolspider();
//$cl_schoolslist->spider_exec();

//ending of school spider

//***********************************************//

// subject spider running

$cl_subject = new subjectsipder();
$cl_subject->spider_exec();