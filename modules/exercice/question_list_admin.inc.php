<?php
/*========================================================================
*   Open eClass 2.3
*   E-learning and Course Management System
* ========================================================================
*  Copyright(c) 2003-2010  Greek Universities Network - GUnet
*  A full copyright notice can be read in "/info/copyright.txt".
*
*  Developers Group:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*			Yannis Exidaridis <jexi@noc.uoa.gr>
*			Alexandros Diamantidis <adia@noc.uoa.gr>
*			Tilemachos Raptis <traptis@noc.uoa.gr>
*
*  For a full list of contributors, see "credits.txt".
*
*  Open eClass is an open platform distributed in the hope that it will
*  be useful (without any warranty), under the terms of the GNU (General
*  Public License) as published by the Free Software Foundation.
*  The full license can be read in "/info/license/license_gpl.txt".
*
*  Contact address: 	GUnet Asynchronous eLearning Group,
*  			Network Operations Center, University of Athens,
*  			Panepistimiopolis Ilissia, 15784, Athens, Greece
*  			eMail: info@openeclass.org
* =========================================================================*/

 // $Id$
/**
 * This script allows to manage the question list
 * It is included from the script admin.php
 */

// moves a question up in the list
if(isset($_GET['moveUp'])) {
	$objExercise->moveUp($_GET['moveUp']);
	$objExercise->save();
}

// moves a question down in the list
if(isset($_GET['moveDown'])) {
	$objExercise->moveDown($_GET['moveDown']);
	$objExercise->save();
}

// deletes a question from the exercise (not from the data base)
if(isset($_GET['deleteQuestion'])) {
	$deleteQuestion = $_GET['deleteQuestion'];
	// construction of the Question object
	$objQuestionTmp=new Question();
	// if the question exists
	if($objQuestionTmp->read($deleteQuestion)) {
		$objQuestionTmp->delete($exerciseId);
		// if the question has been removed from the exercise
		if($objExercise->removeFromList($deleteQuestion))
		{
			$nbrQuestions--;
		}
	}
	// destruction of the Question object
	unset($objQuestionTmp);
}


$tool_content .= "
    <div align=\"left\" id=\"operations_container\">
      <ul id=\"opslist\">
        <li><a href='$_SERVER[PHP_SELF]?newQuestion=yes'>$langNewQu</a>&nbsp;|&nbsp;<a href='question_pool.php?fromExercise=$exerciseId'>$langGetExistingQuestion</a></li>
      </ul>
    </div>";


if($nbrQuestions) {
	$questionList=$objExercise->selectQuestionList();
	$i=1;

$tool_content .= "
    <table width='99%' class='tbl_alt'>
    <tr>
      <th colspan='2'><div align='left'>$langQuestionList</div></th>
      <th colspan='4' class='right'>$langActions</th>
    </tr>";


	foreach($questionList as $id) {
		$objQuestionTmp=new Question();
		$objQuestionTmp->read($id);
                    if ($i%2 == 0) {
                       $tool_content .= "\n    <tr class='even'>";
                    } else {
                       $tool_content .= "\n    <tr class='odd'>";
                    }

		$tool_content .= "
      <td align=\"right\" width=\"1\">".$i.".</td>
      <td> ".$objQuestionTmp->selectTitle()."<br />
	   ".$aType[$objQuestionTmp->selectType()-1]."</td>
      <td class=\"right\" width=\"50\"><a href=\"".$_SERVER['PHP_SELF']."?editQuestion=".$id."\">".
		"<img src='../../template/classic/img/edit.png' align='absmiddle' title='$langModify'></a>".
		" <a href=\"".$_SERVER['PHP_SELF']."?deleteQuestion=".$id."\" "."onclick=\"javascript:if(!confirm('".addslashes(htmlspecialchars($langConfirmYourChoice))."')) return false;\">".
		"<img src='../../template/classic/img/delete.png' align='absmiddle' title='$langDelete'></a></td>
      <td width='20'>";
		if($i != 1) {
			$tool_content .= "<a href=\"".$_SERVER['PHP_SELF']."?moveUp=".$id."\">
   			<img src=\"../../template/classic/img/up.gif\" border=\"0\" align=\"absmiddle\" title=\"".$langUp."\"></a> ";
		}
		$tool_content .= "</td>
      <td width='20'>";
		if($i != $nbrQuestions)	{
			$tool_content .= "<a href=\"".$_SERVER['PHP_SELF']."?moveDown=".$id."\">
			<img src=\"../../template/classic/img/down.gif\" border=\"0\" align=\"absmiddle\" title=\"".$langDown."\"></a> ";
		}
		$tool_content .= "</td>
    </tr>";
		$i++;
		unset($objQuestionTmp);
	}
}

if(!isset($i)) {
	$tool_content .= "
      <p class='alert1'>$langNoQuestion</p>";
}

$tool_content .= "
    </table>";
?>
