<?php

/* ========================================================================
 * Open eClass 3.0
* E-learning and Course Management System
* ========================================================================
* Copyright 2003-2013  Greek Universities Network - GUnet
* A full copyright notice can be read in "/info/copyright.txt".
* For a full list of contributors, see "credits.txt".
*
* Open eClass is an open platform distributed in the hope that it will
* be useful (without any warranty), under the terms of the GNU (General
        * Public License) as published by the Free Software Foundation.
* The full license can be read in "/info/license/license_gpl.txt".
*
* Contact address: GUnet Asynchronous eLearning Group,
*                  Network Operations Center, University of Athens,
*                  Panepistimiopolis Ilissia, 15784, Athens, Greece
*                  e-mail: info@openeclass.org
* ======================================================================== */

$require_login = TRUE;
$guest_allowed = FALSE;

include '../../include/baseTheme.php';

require_once("class.msg.php");
require_once("class.mailbox.php");

if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

    if (isset($_GET['course_id'])) {
        $course_id = intval($_GET['course_id']);
    }
    
    if (isset($_GET['mbox_type'])) {
        $mbox_type = $_GET['mbox_type'];
    }
    
    if (isset($_POST['mid'])) {
        $mid = intval($_POST['mid']);
        $msg = new Msg($mid, $uid, 'any');
        if (!$msg->error) {
            $msg->delete();
        }
        exit();
    } elseif (isset($_POST['all_inbox'])) {
        $inbox = new Mailbox($uid, $course_id);
        $msgs = $inbox->getInboxMsgs();
        foreach ($msgs as $msg) {
            if (!$msg->error) {
                $msg->delete();
            }
        }
        exit();
    } elseif (isset($_POST['all_outbox'])) {
        $outbox = new Mailbox($uid, $course_id);
        $msgs = $outbox->getOutboxMsgs();
        foreach ($msgs as $msg) {
            if (!$msg->error) {
                $msg->delete();
            }
        }
        exit();
    }
    
    $mbox = new Mailbox($uid, $course_id);
    
    $limit = intval($_GET['iDisplayLength']);
    $offset = intval($_GET['iDisplayStart']);
    
    //Total records
    $data['iTotalRecords'] = $mbox->MsgsNumber($mbox_type);
    
    $keyword = quote('%' . $_GET['sSearch'] . '%');
    
    if ($mbox_type == 'inbox') {
        //Total records after applying search filter
        $data['iTotalDisplayRecords'] = count($mbox->getInboxMsgs($keyword));
        
        $msgs = $mbox->getInboxMsgs($keyword, $limit, $offset);
    } else {
        //Total records after applying search filter
        $data['iTotalDisplayRecords'] = count($mbox->getOutboxMsgs($keyword));
        
        $msgs = $mbox->getOutboxMsgs($keyword, $limit, $offset);
    }
    
    $data['aaData'] = array();
    
    foreach ($msgs as $msg) {
        if ($msg->is_read == 1) {
            $bold_start = "";
            $bold_end = "";
        } else {
            $bold_start = "<b>";
            $bold_end = "</b>";
        }
    
        $urlstr = '';
        if ($course_id != 0) {
            $urlstr = "&amp;course=".course_id_to_code($course_id);
        }
        
        if ($course_id == 0) {
            if ($msg->course_id != 0) {
                $td[0] = "$bold_start<a class=\"outtabs\" href=\"index.php?course=".course_id_to_code($msg->course_id)."\">".course_id_to_title($msg->course_id)."</a>$bold_end";
            } else {
                $td[0] = "";
            }
        }
    
        if ($mbox_type == 'index') {
            $td[1] = "$bold_start<a href='inbox.php?mid=$msg->id".$urlstr."'>".q($msg->subject)."</a>$bold_end";
        } else {
            $td[1] = "<a href='outbox.php?mid=$msg->id".$urlstr."'>".q($msg->subject)."</a>";
        }
        
        if ($mbox_type == 'index') {
            $td[2] = $bold_start.display_user($msg->author_id, false, false).$bold_end;
        } else {
            $recipients = '';
            foreach ($msg->recipients as $r) {
                if ($r != $msg->author_id) {
                    $recipients .= display_user($r, false, false).'<br/>';
                }
            }
            $td[2] = $recipients;
        }
        $td[3] = $bold_start.nice_format(date('Y-m-d H:i:s',$msg->timestamp), true).$bold_end;
        $td[4] = "<img src=\"".$themeimg.'/delete.png'."\" class=\"delete\"/>";
        
        if ($course_id == 0) {
            $data['aaData'][] = array(
                    'DT_RowId' => $msg->id,
                    '0' => $td[0],
                    '1' => $td[1],
                    '2' => $td[2],
                    '3' => $td[3],
                    '4' => $td[4]
            );
        } else {
            $data['aaData'][] = array(
                    'DT_RowId' => $msg->id,
                    '0' => $td[1],
                    '1' => $td[2],
                    '2' => $td[3],
                    '3' => $td[4]
            );
        }
    }
    
    echo json_encode($data);
    exit();
}