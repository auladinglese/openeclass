<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
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

/**
 * @file forum_admin.php  
 */

$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'For';
$require_editor = true;

require_once '../../include/baseTheme.php';
require_once 'include/sendMail.inc.php';
require_once 'include/course_settings.php';
require_once 'functions.php';
require_once 'modules/search/indexer.class.php';
require_once 'modules/search/forumindexer.class.php';
require_once 'modules/search/forumtopicindexer.class.php';
require_once 'modules/search/forumpostindexer.class.php';

$idx = new Indexer();
$fidx = new ForumIndexer($idx);
$ftdx = new ForumTopicIndexer($idx);
$fpdx = new ForumPostIndexer($idx);

$nameTools = $langForumAdmin;
$navigation[] = array('url' => "index.php?course=$course_code", 'name' => $langForums);

$forum_id = isset($_REQUEST['forum_id']) ? intval($_REQUEST['forum_id']) : '';
$cat_id = isset($_REQUEST['cat_id']) ? intval($_REQUEST['cat_id']) : '';

$head_content .= <<<hContent
<script type="text/javascript">
function checkrequired(which, entry) {
	var pass=true;
	if (document.images) {
		for (i=0;i<which.length;i++) {
			var tempobj=which.elements[i];
			if (tempobj.name == entry) {
				if (tempobj.type=="text"&&tempobj.value=='') {
					pass=false;
					break;
		  		}
	  		}
		}
	}
	if (!pass) {
		alert("$langEmptyCat");
		return false;
	} else {
		return true;
	}
}
</script>
hContent;
// forum go
if (isset($_GET['forumgo'])) {
    $ctg = category_name($cat_id);
    $tool_content .= "
        <form action='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;forumgoadd=yes&amp;cat_id=$cat_id' method='post' onsubmit=\"return checkrequired(this,'forum_name');\">
        <fieldset>
                <legend>$langAddForCat</legend>
                <table class='tbl' width='100%'>
                <tr>
                <th>$langCat:</th>
                <td>$ctg</td>
                </tr>
                <tr>
                <th>$langForName:</th>
                <td><input type='text' name='forum_name' size='40'></td>
                </tr>
                <tr>
                <th valign='top'>$langDescription:</th>
                <td><textarea name='forum_desc' cols='37' rows='3'></textarea></td>
                </tr>
                <tr>
                <th>&nbsp;</th>
                <td class='right'><input type='submit' value='$langAdd'></td>
                </tr>
                </table>
        </fieldset>
        </form>
        <p>&laquo; <a href='index.php?course=$course_code'>$langBack</a></p>";
}
// forum go edit
elseif (isset($_GET['forumgoedit'])) {
    $result = Database::get()->querySingle("SELECT id, name, `desc`, cat_id
                                        FROM forum
                                        WHERE id = ?d
                                        AND course_id = ?d", $forum_id, $course_id);
    $forum_id = $result->id;
    $forum_name = $result->name;
    $forum_desc = $result->desc;
    $cat_id_1 = $result->cat_id;

    $tool_content .= "
                <form action='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;forumgosave=yes&amp;cat_id=$cat_id' method='post' onsubmit=\"return checkrequired(this,'forum_name');\">
                <input type='hidden' name='forum_id' value='$forum_id'>
                <fieldset>
                <legend>$langChangeForum</legend>
                <table class='tbl' width='100%'>
                <tr>
                <th>$langForName</th>
                <td><input type='text' name='forum_name' size='50' value='" . q($forum_name) . "'></td>
                </tr>
                <tr>
                <th valign='top'>$langDescription</th>
                <td><textarea name='forum_desc' cols='47' rows='3'>" . q($forum_desc) . "</textarea></td>
                </tr>";
    
    $result = Database::get()->querySingle("SELECT COUNT(*) as c FROM `group` WHERE `forum_id` = ?d", $forum_id);
    if ($result->c == 0) {//group forums cannot change category
        $tool_content .= "
                    <tr>
                    <th>$langChangeCat</th>
                    <td>
                    <select name='cat_id'>";
        $result = Database::get()->queryArray("SELECT `id`, `cat_title` FROM `forum_category` WHERE `course_id` = ?d AND `cat_order` <> ?d", $course_id, -1);
        //cat_order <> -1: temp solution to exclude group categories and avoid triple join
        foreach ($result as $result_row) {
            $cat_id = $result_row->id;
            $cat_title = $result_row->cat_title;
            if ($cat_id == $cat_id_1) {
                $tool_content .= "<option value='$cat_id' selected>$cat_title</option>";
            } else {
                $tool_content .= "<option value='$cat_id'>$cat_title</option>";
            }
        }
        $tool_content .= "</select></td></tr>";
    }
    $tool_content .= "
        <tr><th>&nbsp;</th>
        <td class='right'><input type='submit' value='$langModify'></td>
        </tr></table>
        </fieldset>
        </form>";
}

// edit forum category
elseif (isset($_GET['forumcatedit'])) {
    $nameTools = $langCatForumAdmin;
    
    $result = Database::get()->querySingle("SELECT id, cat_title FROM forum_category
                                WHERE id = ?d
                                AND course_id = ?d", $cat_id, $course_id);
    $cat_id = $result->id;
    $cat_title = $result->cat_title;
    $tool_content .= "
        <form action='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;forumcatsave=yes' method='post' onsubmit=\"return checkrequired(this,'cat_title');\">
        <input type='hidden' name='cat_id' value='$cat_id'>
        <fieldset>
        <legend>$langModCatName</legend>
        <table class='tbl' width='100%'>
        <tr>
        <th>$langCat</th>
        <td><input type='text' name='cat_title' size='55' value='$cat_title' /></td>
        </tr>
        <tr>
        <th>&nbsp;</th>
        <td class='right'><input type='submit' value='$langModify'></td>
        </tr>
        </table>
        </fieldset>
        </form>";
}

// Save forum category
elseif (isset($_GET['forumcatsave'])) {
    $nameTools = $langCatForumAdmin;
    
    Database::get()->query("UPDATE forum_category SET cat_title = ?s
                                        WHERE id = ?d AND course_id = ?d", $_POST['cat_title'], $cat_id, $course_id);
    $tool_content .= "<p class='success'>$langNameCatMod</p>
                                <p>&laquo; <a href='index.php?course=$course_code'>$langBack</a></p>";
}
// Save forum
elseif (isset($_GET['forumgosave'])) {
    Database::get()->query("UPDATE forum SET name = ?s,
                                   `desc` = ?s,
                                   cat_id = ?d
                                WHERE id = ?d
                                AND course_id = ?d"
            , $_POST['forum_name'], purify($_POST['forum_desc']), $cat_id, $forum_id, $course_id);
    $fidx->store($forum_id);
    $tool_content .= "<p class='success'>$langForumDataChanged</p>
                                <p>&laquo; <a href='index.php?course=$course_code'>$langBack</a></p>";
}

// Add category to forums
elseif (isset($_GET['forumcatadd'])) {
    $nameTools = $langCatForumAdmin;
    
    Database::get()->query("INSERT INTO forum_category
                        SET cat_title = ?s,
                        course_id = ?d", $_POST['categories'], $course_id);
    $tool_content .= "<p class='success'>$langCatAdded</p>
                                <p>&laquo; <a href='index.php?course=$course_code'>$langBack</a></p>";
}

// forum go add
elseif (isset($_GET['forumgoadd'])) {
    $nameTools = $langAdd;
    
    $ctg = category_name($cat_id);
    $forid = Database::get()->query("INSERT INTO forum (name, `desc`, cat_id, course_id)
                                VALUES (?s, ?s, ?d, ?d)"
            , $_POST['forum_name'], $_POST['forum_desc'], $cat_id, $course_id)->lastInsertID;
    $fidx->store($forid);
    // --------------------------------
    // notify users
    // --------------------------------
    $subject_notify = "$logo - $langCatNotify";
    $body_topic_notify = "$langBodyCatNotify $langInCat '$ctg' \n\n$gunet";
    $sql = Database::get()->queryArray("SELECT DISTINCT user_id FROM forum_notify
                                WHERE cat_id = ?d AND
                                        notify_sent = 1 AND
                                        course_id = ?d AND
                                        user_id <> ?d"
            , $cat_id, $course_id, $uid);
    foreach ($sql as $r) {
        if (get_user_email_notification($r->user_id, $course_id)) {
            $linkhere = "&nbsp;<a href='${urlServer}main/profile/emailunsubscribe.php?cid=$course_id'>$langHere</a>.";
            $unsubscribe = "<br /><br />$langNote: " . sprintf($langLinkUnsubscribe, $title);
            $body_topic_notify .= $unsubscribe . $linkhere;
            $emailaddr = uid_to_email($r->user_id);
            send_mail('', '', '', $emailaddr, $subject_notify, $body_topic_notify, $charset);
        }
    }
    // end of notification
    $tool_content .= "<p class='success'>$langForumCategoryAdded</p>
                                <p>&laquo; <a href='index.php?course=$course_code'>$langBack</a></p>";
}

// delete forum category
elseif (isset($_GET['forumcatdel'])) {
    $nameTools = $langCatForumAdmin;
    
    $result = Database::get()->queryArray("SELECT id FROM forum WHERE cat_id = ?d AND course_id = ?d", $cat_id, $course_id);
    foreach ($result as $result_row) {
        $forum_id = $result_row->id;
        $result2 = Database::get()->queryArray("SELECT id FROM forum_topic WHERE forum_id = ?d", $forum_id);
        foreach ($result2 as $result_row2) {
            $topic_id = $result_row2->id;
            Database::get()->query("DELETE FROM forum_post WHERE topic_id = ?d", $topic_id);
            $fpdx->removeByTopic($topic_id);
        }
        Database::get()->query("DELETE FROM forum_topic WHERE forum_id = ?d", $forum_id);
        $ftdx->removeByForum($forum_id);
        Database::get()->query("DELETE FROM forum_notify WHERE forum_id = ?d AND course_id = ?d", $forum_id, $course_id);
        $fidx->remove($forum_id);
    }
    Database::get()->query("DELETE FROM forum WHERE cat_id = ?d AND course_id = ?d", $cat_id, $course_id);
    Database::get()->query("DELETE FROM forum_notify WHERE cat_id = ?d AND course_id = ?d", $cat_id, $course_id);
    Database::get()->query("DELETE FROM forum_category WHERE id = ?d AND course_id = ?d", $cat_id, $course_id);
    $tool_content .= "<p class='success'>$langCatForumDelete</p>
                                <p>&laquo; <a href='index.php?course=$course_code'>$langBack</a></p>";
}

// delete forum
elseif (isset($_GET['forumgodel'])) {
    $nameTools = $langDelete;
    
    $result = Database::get()->queryArray("SELECT id FROM forum WHERE id = ?d AND course_id = ?d", $forum_id, $course_id);    
    foreach ($result as $result_row) {
        $forum_id = $result_row->id;
        $result2 = Database::get()->queryArray("SELECT id FROM forum_topic WHERE forum_id = ?d", $forum_id);
        foreach ($result2 as $result_row2) {
            $topic_id = $result_row2->id;
            Database::get()->query("DELETE FROM forum_post WHERE topic_id = ?d", $topic_id);
            $fpdx->removeByTopic($topic_id);
        }
    }
    Database::get()->query("DELETE FROM forum_topic WHERE forum_id = ?d", $forum_id);
    $ftdx->removeByForum($forum_id);
    Database::get()->query("DELETE FROM forum_notify WHERE forum_id = ?d AND course_id = ?d", $forum_id, $course_id);
    Database::get()->query("DELETE FROM forum WHERE id = ?d AND course_id = ?d", $forum_id, $course_id);
    $fidx->remove($forum_id);
    Database::get()->query("UPDATE `group` SET forum_id = 0
                    WHERE forum_id = ?d
                    AND course_id = ?d", $forum_id, $course_id);    
    $tool_content .= "<p class='success'>$langForumDelete</p>
                                <p>&laquo; <a href='index.php?course=$course_code'>$langBack</a></p>";
} elseif (isset($_GET['forumtopicedit'])) {
   $topic_id = intval($_GET['topic_id']);
   
   $result = Database::get()->querySingle("SELECT `forum_id` FROM `forum_topic` as ft, `forum` as f  
           WHERE ft.`id` = ?d AND ft.`forum_id` = f.`id` AND `f`.course_id = ?d ", $topic_id, $course_id);
   if ($result) {
       $current_forum_id = $result->forum_id;
       
       $tool_content .= "
       <form action='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;forumtopicsave=yes&amp;topic_id=$topic_id' method='post'>
       <fieldset>
       <legend>$langEditTopic</legend>
       <table class='tbl' width='100%'>
           <tr>
           <th>$langChangeTopicForum</th>
           <td>
           <select name='forum_id'>";
       $result = Database::get()->queryArray("SELECT f.`id` as `forum_id`, f.`name` as `forum_name`, fc.`cat_title` as `cat_title` FROM `forum` AS `f`, `forum_category` AS `fc` WHERE f.`course_id` = ?d AND f.`cat_id` = fc.`id`", $course_id);
       foreach ($result as $result_row) {
           $forum_id = $result_row->forum_id;
           $forum_name = $result_row->forum_name;
           $cat_title = $result_row->cat_title;
           
           if ($forum_id == $current_forum_id) {
               $tool_content .= "<option value='$forum_id' selected>$forum_name ($cat_title)</option>";
           } else {
               $tool_content .= "<option value='$forum_id'>$forum_name ($cat_title)</option>";
           }
       }
       $tool_content .= "</select></td></tr>
       <tr><th>&nbsp;</th>
       <td class='right'><input type='submit' value='$langModify'></td>
       </tr></table>
       </fieldset>
       </form>";
   }
} elseif (isset($_GET['forumtopicsave'])) {
    $topic_id = intval($_GET['topic_id']);
    $new_forum = intval($_POST['forum_id']);
    
    if (does_exists($topic_id, 'topic')) {//topic belongs to the course and new forum is not a group forum
    
        $result = Database::get()->querySingle("SELECT `forum_id`, `num_replies`, `last_post_id`  FROM `forum_topic` WHERE `id` = ?d", $topic_id);
        $current_forum_id = $result->forum_id;
        $num_replies = $result->num_replies;
        $last_post_id = $result->last_post_id;
        
        if ($current_forum_id != $new_forum) {
            Database::get()->query("UPDATE `forum_topic` SET `forum_id` = ?d WHERE `id` = ?d", $new_forum, $topic_id);
            $ftdx->store($topic_id);
            
            $result = Database::get()->querySingle("SELECT `last_post_id`, MAX(`topic_time`) FROM `forum_topic` WHERE `forum_id`=?d",$new_forum);
            $last_post_id = $result->last_post_id;
            
            Database::get()->query("UPDATE `forum` SET `num_topics` = `num_topics`+1, `num_posts` = `num_posts`+?d, `last_post_id` = ?d
                    WHERE id = ?d",$num_replies+1, $last_post_id, $new_forum);
            
            $result = Database::get()->querySingle("SELECT `last_post_id`, MAX(`topic_time`) FROM `forum_topic` WHERE `forum_id`=?d",$current_forum_id);
            if ($result) {
                $last_post_id = $result->last_post_id;
            } else {
                $last_post_id = 0;
            }
            
            Database::get()->query("UPDATE `forum` SET `num_topics` = `num_topics`-1, `num_posts` = `num_posts`-?d, `last_post_id` = ?d
                    WHERE id = ?d",$num_replies+1,$last_post_id, $current_forum_id);
        }//if user selected the current forum do nothing
        
       $tool_content .= "<p class='success'>$langTopicDataChanged</p>
                         <p>&laquo; <a href='viewforum.php?course=$course_code&amp;forum=$new_forum'>$langBack</a></p>";
    }
    
} elseif (isset($_GET['settings'])) {
    if (isset($_POST['submitSettings'])) {
        setting_set(SETTING_FORUM_RATING_ENABLE, $_POST['r_radio'], $course_id);
        $message = "<p class='success'>$langRegDone</p>";
    }
    
    if (isset($message) && $message) {
        $tool_content .= $message . "<br/>";
        unset($message);
    }
    
    if (setting_get(SETTING_FORUM_RATING_ENABLE, $course_id) == 1) {
        $checkDis = "";
        $checkEn = "checked ";
    } else {
        $checkDis = "checked ";
        $checkEn = "";
    }
    
    $tool_content .= "
        <form action='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;settings=yes' method='post'>
        <fieldset>
        <legend>$langRating</legend>
        <table class='tbl' width='100%'>
        <tbody>
        <tr><td><input type=\"radio\" value=\"1\" name=\"r_radio\" $checkEn/>$langRatingEn</td></tr>
        <tr><td><input type=\"radio\" value=\"0\" name=\"r_radio\" $checkDis/>$langRatingDis</td></tr>
        <tr><td><input type=\"submit\" name=\"submitSettings\" value=\"$langSubmit\" /></td></tr>
        </tbody>
        </table>
        </fieldset>
        </form>";
} else {
    $nameTools = $langCatForumAdmin;
    
    $tool_content .= "
        <form action='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;forumcatadd=yes' method='post' onsubmit=\"return checkrequired(this,'categories');\">
        <fieldset>
        <legend>$langAddCategory</legend>
        <table class='tbl' width='100%'>
        <tr><th>$langCat</th><td><input type=text name=categories size=50></td></tr>
        <tr>
        <th>&nbsp;</th>
        <td class='right'><input type=submit value='$langAdd'></td>
        </tr>
        </table>
        </fieldset>
        </form>";
}
draw($tool_content, 2, null, $head_content);
