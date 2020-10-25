<?php

/*******************************************************************************\
*    IDE.PHP, a web based editor for quick PHP development                     *
*    Copyright (C) 2000  Johan Ekenberg                                        *
*                                                                              *
*    This program is free software; you can redistribute it and/or modify      *
*    it under the terms of the GNU General Public License as published by      *
*    the Free Software Foundation; either version 2 of the License, or         *
*    (at your option) any later version.                                       *
*                                                                              *
*    This program is distributed in the hope that it will be useful,           *
*    but WITHOUT ANY WARRANTY; without even the implied warranty of            *
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             *
*    GNU General Public License for more details.                              *
*                                                                              *
*    You should have received a copy of the GNU General Public License         *
*    along with this program; if not, write to the Free Software               *
*    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA  *
*                                                                              *
*    To contact the author regarding this program,                             *
*    please use this email address: <ide.php@ekenberg.se>                      *
\*******************************************************************************/
// ------------------------------------------------------------------------- //
//						 C-JAY Content							             //
//				         Version:  V2				  	  					 //
//						  Module for										 //
//				XOOPS - PHP Content Management System				 		 //
//					<https://www.xoops.org>						  			 //
// ------------------------------------------------------------------------- //
// Author: Christoph forlon Brecht          								 //
// Purpose: Module to wrap html or php-content into nice Xoops design.	     //
// email: master@c-jay.net										  			 //
// Site: http://c-jay.net													 //
// ------------------------------------------------------------------------- //
//  This program is free software; you can redistribute it and/or modify	 //
//  it under the terms of the GNU General Public License as published by	 //
//  the Free Software Foundation; either version 2 of the License, or 	     //
//  (at your option) any later version. 							         //
//															                 //
//  This program is distributed in the hope that it will be useful,		     //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of		     //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the		     //
//  GNU General Public License for more details.						     //
// ------------------------------------------------------------------------- //
require dirname(__DIR__, 3) . '/include/cp_header.php';
if (file_exists('../language/' . $xoopsConfig['language'] . '/main.php')) {
    include '../language/' . $xoopsConfig['language'] . '/main.php';
} else {
    include '../language/english/main.php';
}
/*require dirname(__DIR__) . '/include/functions.php';
require_once XOOPS_ROOT_PATH.'/class/xoopstree.php';
require_once XOOPS_ROOT_PATH."/class/xoopslists.php";
require_once XOOPS_ROOT_PATH."/include/xoopscodes.php";
require_once XOOPS_ROOT_PATH.'/class/module.errorhandler.php';*/
error_reporting(E_ERROR | E_WARNING | E_PARSE);
set_magic_quotes_runtime(0);

include('./Page.phpclass');
include('./Conf.phpclass');

   $Ide = new Ide();

class Ide
{
    public $code;

    public $alert_message;

    public $success_message;

    public $IDE_homepage_url = 'http://www.ekenberg.se/php/ide/';

    public $GPL_link = "<A HREF='http://www.gnu.org/copyleft/gpl.html'>GNU General Public License</A>";

    public $PHP_link = "<A HREF='http://www.php.net'>PHP</A>";

    public $IDE_version = '1 . 5';

    public function __construct()
    {
        global $_POST, $_GET;

        $this->Conf = new Conf();

        $this->Out = new Page();

        /*
        ** Remove slashes if necessary, put code in $this->code
        */

        if (isset($_POST['code'])) {
            if (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()) {
                $this->code = stripslashes($_POST['code']);
            } else {
                $this->code = $_POST['code'];
            }
        }

        /*
        ** Get code from code file if not submitted through form.
        */

        if ((!isset($this->code)) && (file_exists($this->Conf->Code_file))) {
            $this->code = implode('', (file($this->Conf->Code_file)));
        }

        /*
        ** Since the code is displayed in a <TEXTAREA>, it can't contain the tag </TEXTAREA>,
        ** since that would break our editor :/ Thus we replace it with </ideTEXTAREA>
        ** and put it in $this->textarea_safe_code. The reverse substitution is first
        ** performed on $this->code, to restore any previous replacements.
        */

        $this->code = eregi_replace('</ide(TEXTAREA)>', '</\\1>', $this->code);

        $this->textarea_safe_code = $this->make_textarea_safe($this->code);

        /*
        ** Htmlentities are not literally shown inside TEXTAREA in some (all?) browsers.
        */

        if ($this->Conf->Protect_entities) {
            $this->code = eregi_replace('(&amp;)+&', '&', $this->code);
        }

        /*
        ** Remove \r\f if desired, needed for cgi on UNIX
        */

        if ($this->Conf->Unix_newlines) {
            $this->code = preg_replace("/[\r\f]/", '', $this->code);
        }

        /*
        ** What file are we working with?
        */

        $this->Conf->Current_file = $_POST['Current_file'] ?: $this->Conf->Tmp_file;

        /*
        ** Check our environment.
        */

        if ($error = $this->Conf->Is_bad_environment()) {
            print $this->Out->html_top();

            print "<H3><BLOCKQUOTE>$error</BLOCKQUOTE></H2>\n";

            print $this->Out->html_bottom();

            exit;
        }

        /*
        ** Always save the code in our code and tmp files
        */

        if (isset($this->code)) {
            $FH_CODE = fopen($this->Conf->Code_file, 'wb');

            $FH_TMP = fopen($this->Conf->Tmp_file, 'wb');

            fwrite($FH_CODE, $this->code);

            fwrite($FH_TMP, $this->code);

            fclose($FH_CODE);

            fclose($FH_TMP);
        }

        /*
        ** These options are saved every time
        */

        $this->Conf->save_to_file(['Eval_suffix']);

        /*
        ** Set file permissions as desired
        */

        if ($this->Conf->Eval_executable) {
            chmod($this->Conf->Tmp_file, 0755);
        } else {
            chmod($this->Conf->Tmp_file, 0644);
        }

        /*
        ** Print-and-exit-immediately stuff
        */

        if ('fancy_view_source' == $_GET['action']) {
            print $this->fancy_view_source();

            exit;
        }

        if ('about' == $_POST['action']) {
            print $this->Out->html_top();

            print $this->about_page();

            print $this->Out->html_bottom();

            exit;
        }

        if ('options' == $_POST['action']) {
            if ('add_suffix' == $_POST['options_action']) {
                $add_suffix = preg_replace("^\.*(.+)", '.\\1', trim($_POST['add_remove_suffix']));

                if ($add_suffix && (!in_array($add_suffix, $this->Conf->Eval_suffix_list, true))) {
                    $this->Conf->Eval_suffix_list[] = $add_suffix;
                }

                $this->options_page_save(['Fancy_view_line_numbers', 'Protect_entities',
                'Eval_executable', 'Unix_newlines', 'Eval_suffix_list',
]);
            } elseif ('remove_suffix' == $_POST['options_action']) {
                $remove_suffix = preg_replace("^\.*(.+)", '.\\1', trim($_POST['add_remove_suffix']));

                if ($remove_suffix && (in_array($remove_suffix, $this->Conf->Eval_suffix_list, true))) {
                    reset($this->Conf->Eval_suffix_list);

                    for ($i = 0, $iMax = count($this->Conf->Eval_suffix_list); $i < $iMax; $i++) {
                        if (preg_match("^$remove_suffix$", $this->Conf->Eval_suffix_list[$i])) {
                            unset($this->Conf->Eval_suffix_list[$i]);
                        }
                    }
                }

                $this->options_page_save(['Fancy_view_line_numbers', 'Protect_entities',
                'Eval_executable', 'Unix_newlines', 'Eval_suffix_list',
]);
            }

            print $this->Out->html_top();

            print $this->options_page();

            print $this->Out->html_bottom();

            exit;
        }

        /*
        ** Print top of page
        */

        print $this->Out->html_top();

        /*
        ** Act according to 'action'
        */

        if ('eval' == $_POST['action']) {
            print $this->js_open_code_window($this->Conf->Tmp_file);
        } elseif ('source_viewer' == $_POST['action']) {
            print $this->js_open_code_window("{$GLOBALS['PHP_SELF']}?action=fancy_view_source&file={$this->Conf->Tmp_file}");
        } elseif ('save_as' == $_POST['action']) {
            if (!$_POST['save_as_filename']) {
                $this->alert_message = "Can't save file without a filename!!";
            } elseif ((!$_POST['overwrite_ok']) && (file_exists("{$this->Conf->Data_dir}/{$_POST['save_as_filename']}"))) {
                $this->alert_message = "The file <B>{$this->Conf->Data_dir}/{$_POST['save_as_filename']}</B> already exists! 
                   Please choose another name, or check \"Replace\".";
            } else {
                if ($FH_SAVEAS = @fopen("{$this->Conf->Data_dir}/{$_POST['save_as_filename']}", 'wb')) {
                    fwrite($FH_SAVEAS, $this->code);

                    fclose($FH_SAVEAS);

                    $this->success_message = "Current code was saved to file: <B>{$this->Conf->Data_dir}/{$_POST['save_as_filename']}</B>!";
                } else {
                    $this->alert_message = "Could not save to file <B>{$this->Conf->Data_dir}/{$_POST['save_as_filename']}</B>: $php_errormsg";
                }

                $this->Conf->Current_file = "{$this->Conf->Data_dir}/{$_POST['save_as_filename']}";
            }
        } elseif ('open_file' == $_POST['action']) {
            $this->textarea_safe_code = implode('', (file("{$this->Conf->Data_dir}/{$_POST['code_file_name']}")));

            if (get_magic_quotes_runtime()) {
                $this->textarea_safe_code = stripslashes($this->textarea_safe_code);
            }

            $this->textarea_safe_code = $this->make_textarea_safe($this->textarea_safe_code);

            $this->Conf->Current_file = "{$this->Conf->Data_dir}/{$_POST['code_file_name']}";

            $_POST['save_as_filename'] = $_POST['code_file_name'];
        } elseif ('erase_file' == $_POST['action']) {
            if (unlink("{$this->Conf->Data_dir}/{$_POST['code_file_name']}")) {
                $this->Conf->Current_file = $this->Conf->Tmp_file;

                $_POST['save_as_filename'] = $_POST['overwrite_ok'] = '';

                $this->success_message = "The file <B>{$this->Conf->Data_dir}/{$_POST['code_file_name']}</B> was erased!";
            }
        } elseif ('save_size' == $_POST['action']) {
            $this->Conf->save_to_file(['Code_cols', 'Code_rows']);
        } elseif ('show_template' == $_POST['action']) {
            $this->textarea_safe_code = $this->make_textarea_safe($this->Conf->Code_template);
        } elseif ('save_as_template' == $_POST['action']) {
            $this->Conf->Code_template = $this->code;

            $this->Conf->save_to_file(['Code_template']);
        } elseif ('save_options' == $_POST['action']) {
            $this->options_page_save(['Fancy_view_line_numbers', 'Protect_entities',
                'Eval_executable', 'Unix_newlines', 'Http_auth_username', 'Http_auth_password',
]);

            $this->success_message = 'Ide.php options were saved!';
        }

        /*
        ** Print the main page and exit
        */

        OpenTable();

        print $this->main_page();

        CloseTable();

        print $this->Out->html_bottom();

        exit;
    }

    /*
    ** Functions
    */

    public function options_page()
    {
        $fancy_view_line_numbers_checked = $this->Conf->Fancy_view_line_numbers ? 'CHECKED' : '';

        $protect_entities_checked = $this->Conf->Protect_entities ? 'CHECKED' : '';

        $eval_executable_checked = $this->Conf->Eval_executable ? 'CHECKED' : '';

        $unix_newlines_checked = $this->Conf->Unix_newlines ? 'CHECKED' : '';

        reset($this->Conf->Eval_suffix_list);

        $sections = [
    "<P><INPUT TYPE='CHECKBOX' NAME='Fancy_view_line_numbers' VALUE='1' $fancy_view_line_numbers_checked>
	   Print line numbers in 'Fancy view'</P>",
    "<P><INPUT TYPE='CHECKBOX' NAME='Protect_entities' VALUE='1' $protect_entities_checked>
	   Protect HTML entities (IE4/5)</P>",
    "<P CLASS='indentall'>Suffix list:<BR><I>&nbsp;" . implode(' &nbsp;', $this->Conf->Eval_suffix_list) . "</I></P>\n
	 <P CLASS='indentall'>Add/remove suffix:
	   <BR><INPUT TYPE='text' NAME='add_remove_suffix' SIZE='8'>
	   &nbsp; <INPUT TYPE='submit' VALUE='Add' onClick='document.options_form.options_action.value=\"add_suffix\"; document.options_form.action.value=\"options\"'>
	   <INPUT TYPE='submit' VALUE='Remove' onClick='document.options_form.options_action.value=\"remove_suffix\"; document.options_form.action.value=\"options\"'></P>\n
	 <P><INPUT TYPE='CHECKBOX' NAME='Eval_executable' VALUE='1' $eval_executable_checked>Make executable (CGI on UNIX)</P>\n
	 <P><INPUT TYPE='CHECKBOX' NAME='Unix_newlines' VALUE='1' $unix_newlines_checked>
	   Use UNIX newlines (CGI on UNIX)</P>",
        "<P CLASS='indentall'>To make 'Fancy view' work under password protection:<P>
         <P CLASS='indentall'><TABLE BORDER='0' WIDTH='70%' CELLPADDING='0' CELLSPACING='0'>
         <TR><TD><P CLASS='noindent'>Username:</P></TD>
         <TD><P CLASS='noindent'>Password:</P></TD></TR>
         <TR><TD><INPUT TYPE='text' NAME='Http_auth_username' SIZE='12' VALUE='{$this->Conf->Http_auth_username}'></TD>
         <TD><INPUT TYPE='text' NAME='Http_auth_password' SIZE='12' VALUE='{$this->Conf->Http_auth_password}'></TD></TR></TABLE>
         </P>",
];

        $ret .= "<DIV ALIGN='CENTER'>\n";

        $ret .= "<H2>I D E . P H P &nbsp; O P T I O N S</H2></DIV>\n";

        $ret .= "<FORM NAME='options_form' METHOD='POST' ACTION='{$GLOBALS['PHP_SELF']}'>\n";

        $ret .= "<INPUT TYPE='hidden' NAME='action' VALUE='save_options'>\n";

        $ret .= "<INPUT TYPE='hidden' NAME='options_action' VALUE=''>\n";

        while (list(, $content) = each($sections)) {
            $ret .= "<BR>\n";

            $ret .= $this->Out->info_box(400, $content);
        }

        $ret .= "<BR><DIV ALIGN='CENTER'>\n";

        $ret .= "<A HREF='javascript: document.options_form.submit()' CLASS='netscapesucks'>[ r e t u r n ]</A></DIV>\n";

        $ret .= "</FORM>\n";

        return($ret);
    }

    public function about_page()
    {
        $sections = [
    "<P><B>I d e . p h p &nbsp; v e r s i o n &nbsp; {$this->IDE_version}</B></P>\n",
    "<P>Ide.php is distributed under the {$this->GPL_link}</P>",
        "<P>Ide.php is developed by <A HREF='mailto:johan@ekenberg.se'>Johan Ekenberg</A>,
           a Swedish Internet consultant who, besides web development with {$this->PHP_link}, does a lot of Perl, C, Linux and bass playing.</P>\n",
        "<P>Visit the <A HREF='{$this->IDE_homepage_url}'>Ide.php homepage</A>.\n",
        "<P>Feedback and suggestions are always welcome, please use the address
           <A HREF='mailto:ide.php@ekenberg.se'>ide.php@ekenberg.se</A> for email related to Ide.php</P>",
];

        $ret .= "<DIV ALIGN='CENTER'>\n";

        $ret .= "<H2>A B O U T &nbsp; I D E . P H P</H2></DIV>\n";

        while (list(, $content) = each($sections)) {
            $ret .= "<BR>\n";

            $ret .= $this->Out->info_box(400, $content);
        }

        $ret .= "<BR><DIV ALIGN='CENTER'>\n";

        $ret .= "<A HREF='{$GLOBALS['PHP_SELF']}' CLASS='netscapesucks'>[ r e t u r n ]</A></DIV>\n";

        return($ret);
    }

    public function main_page()
    {
        global $_POST;

        $suffix_list_selected[$this->Conf->Eval_suffix] = 'SELECTED';

        while (list(, $suffix) = each($this->Conf->Eval_suffix_list)) {
            $suffix_select_options .= "<OPTION VALUE='$suffix' {$suffix_list_selected[$suffix]}>$suffix\n";
        }

        $ret .= "<DIV ALIGN='center'>\n";

        $ret .= '<H2>' . _CC_ED_TITLE . "</H2></DIV>\n";

        $ret .= "<FORM NAME='main_form' METHOD='POST' ACTION='{$GLOBALS['PHP_SELF']}'>\n";

        $ret .= "<INPUT TYPE='hidden' NAME='action' VALUE=''>\n";

        $ret .= "<INPUT TYPE='hidden' NAME='Current_file' VALUE='{$this->Conf->Current_file}'>\n";

        $ret .= $this->Out->begin_invisible_table('', ["CELLPADDING='1'", "CELLSPACING='0'", "ALIGN='center'"]);

        $ret .= "<TR BGCOLOR='#CCCCCC'><TD>\n";

        $ret .= "<FONT COLOR='{$this->Conf->Alert_message_color}' FACE='MS Sans Serif, Arial'>{$this->alert_message}</FONT>\n";

        $ret .= "<FONT COLOR='{$this->Conf->Success_message_color}' FACE='MS Sans Serif, Arial'>{$this->success_message}</FONT>\n";

        $ret .= "</TD</TR>\n";

        $ret .= "<TR BGCOLOR='#CCCCCC'><TD>\n";

        $ret .= $this->Out->start_box_table();

        $ret .= "<TR BGCOLOR='#CCCCCC'>\n";

        /* SELECT FILE */

        $ret .= "<TD ALIGN='left' COLSPAN='7'><SELECT NAME='code_file_name'>\n";

        $data_dir_obj = dir($this->Conf->Data_dir);

        $selected[$this->Conf->Current_file] = 'SELECTED';

        while ($my_files[] = $data_dir_obj->read());

        sort($my_files);

        while ($file = next($my_files)) {
            if (preg_match("^\.{1,2}$", $file)) {
                continue;
            }

            $my_fullname = "{$data_dir_obj->path}/$file";

            $ret .= "<OPTION VALUE='$file' {$selected[$my_fullname]}>$file</OPTION>\n";
        }

        $data_dir_obj->close();

        $ret .= '</SELECT>';

        /* CLEAR */

        $ret .= "<INPUT TYPE='submit' VALUE='" . _CC_ED_CLEAR . "' onClick='if (confirm(\"Do you really want to clear the codearea??\")) {main_form.code.value=\"\"}; return false'></TD>\n";

        $ret .= "</TR>\n";

        $ret .= "<TR BGCOLOR='#CCCCCC'>\n";

        /* OPEN SAVE AS */

        $ret .= "<TD ALIGN='left' COLSPAN='7'><INPUT TYPE='submit' VALUE='" . _CC_ED_OPEN . "' onClick='main_form.action.value=\"open_file\"'>\n";

        $ret .= "<INPUT TYPE='submit' VALUE='" . _CC_ED_SAVE . "' onClick='main_form.action.value=\"save_as\"; main_form.submit()'>\n";

        $ret .= "<INPUT TYPE='text' NAME='save_as_filename' VALUE='{$_POST['save_as_filename']}'>\n";

        $ret .= '' . _CC_ED_REPL . ": <INPUT TYPE='CHECKBOX' NAME='overwrite_ok' VALUE='CHECKED' {$_POST['overwrite_ok']}>\n";

        /* ROWS */

        $ret .= "<div align='right'>" . _CC_ED_ROWS . ": <INPUT TYPE='text' NAME='Code_rows' VALUE='{$this->Conf->Code_rows}' SIZE='3' MAXLENGTH='3' CLASS='netscapesucks2'>\n";

        $ret .= "<INPUT TYPE='submit' VALUE='" . _CC_ED_SIZE . "' onClick='main_form.action.value=\"save_size\"; main_form.submit()'></div></TD>\n";

        $ret .= "</TR>\n";

        $ret .= "<TR BGCOLOR='#CCCCCC'>\n";

        /* TEXTAREA */

        $ret .= "<TD COLSPAN='7'><TEXTAREA ROWS='{$this->Conf->Code_rows}' NAME='code' class='textareacj'>{$this->textarea_safe_code}</TEXTAREA></TD>";

        $ret .= "</TR>\n";

        $ret .= "<TR BGCOLOR='#CCCCCC'>\n";

        /* RUN AS */

        $ret .= "<TD COLSPAN='7'><INPUT TYPE='submit' VALUE='- " . _CC_ED_RUN . " -' onClick='main_form.action.value=\"eval\"; main_form.submit()'>\n";

        $ret .= "<SPAN CLASS='netscapesucks'>" . _CC_ED_RUNAS . ":</SPAN> <SELECT NAME='Eval_suffix'>$suffix_select_options</SELECT>";

        /* OPEN TEMPLATE */

        //$ret .= "<TD ALIGN='center'><INPUT TYPE='submit' VALUE='Open tpl' onClick='main_form.action.value=\"show_template\"; return confirm(\"Replace current code with new template?\")'></TD>\n";

        /* SAVE TEMPLATE */

        //$ret .= "<TD ALIGN='center'><INPUT TYPE='submit' VALUE='Save as tpl' onClick='main_form.action.value=\"save_as_template\"; return confirm(\"Replace current template?\")'></TD>\n";

        /* ABOUT */

        $ret .= "<div align='right'><INPUT TYPE='submit' VALUE='" . _CC_ED_ABOUT . "' onClick='main_form.action.value=\"about\"; main_form.submit()'></div></TD>\n";

        $ret .= "</TR>\n";

        $ret .= $this->Out->end_box_table();

        $ret .= "</TD></TR>\n";

        $ret .= $this->Out->end_invisible_table();

        $ret .= "</FORM>\n";

        return ($ret);
    }

    public function fancy_view_source()
    {
        global $_GET;

        $row_num_spacer = '&nbsp;&nbsp;';

        $ret = '';

        if ($_GET['internal_request'] || (!$this->Conf->Fancy_view_line_numbers)) {
            highlight_file($_GET['file']);

            return;
        }

        if ($this->Conf->Http_auth_username && $this->Conf->Http_auth_password) {
            $internal_url = "http://{$this->Conf->Http_auth_username}:{$this->Conf->Http_auth_password}@{$GLOBALS['HTTP_HOST']}{$GLOBALS['PHP_SELF']}?action=fancy_view_source&file={$this->Conf->Tmp_file}&internal_request=1";
        } else {
            $internal_url = "http://{$GLOBALS['HTTP_HOST']}{$GLOBALS['PHP_SELF']}?action=fancy_view_source&file={$this->Conf->Tmp_file}&internal_request=1";
        }

        if (!$code_array = @file($internal_url)) {
            $ret .= "<H2>An error occured</H2>
                  <P>If you are using password protection for Ide.php, please enter username and password in the 'Options' page.</P>";
        } else {
            $fancy_code_str = implode('', $code_array);

            $fancy_code_array = preg_split('<(br|BR)[[:space:]]*/*>', $fancy_code_str);

            if (count($fancy_code_array)) {
                $row_num_width = mb_strlen(count($fancy_code_array));

                $ret .= preg_replace('^<code>', "<code><FONT COLOR='{$this->Conf->Fancy_line_number_color}'>" . sprintf("%0{$row_num_width}d", 1) . "$row_num_spacer</FONT>", preg_replace('[[:space:]]', '', $fancy_code_array[0]));

                for ($i = 1, $iMax = count($fancy_code_array); $i < $iMax; $i++) {
                    $row_num = sprintf("%0{$row_num_width}d", $i + 1);

                    $ret .= "\n<BR><FONT COLOR='{$this->Conf->Fancy_line_number_color}'>$row_num$row_num_spacer</FONT>" . trim($fancy_code_array[$i]);
                }
            }
        }

        return ($ret);
    }

    public function make_textarea_safe($code)
    {
        $safe_code = eregi_replace('</(TEXTAREA)>', '</ide\\1>', $code);

        if ($this->Conf->Protect_entities) {
            $safe_code = eregi_replace('&', '&amp;', $safe_code);
        }

        return $safe_code;
    }

    public function js_open_code_window($url)
    {
        $ret = '';

        $ret .= "<SCRIPT LANGUAGE='JavaScript'>\n";

        $ret .= "var eval_window = window.open('$url','Foo');\n";

        $ret .= "eval_window.focus();\n";

        $ret .= "</SCRIPT>\n";

        return $ret;
    }

    public function options_page_save($var_names_array)
    {
        global $_POST;

        $this->Conf->Fancy_view_line_numbers = $_POST['Fancy_view_line_numbers'] ? 1 : 0;

        $this->Conf->Protect_entities = $_POST['Protect_entities'] ? 1 : 0;

        $this->Conf->Eval_executable = $_POST['Eval_executable'] ? 1 : 0;

        $this->Conf->Unix_newlines = $_POST['Unix_newlines'] ? 1 : 0;

        $this->Conf->save_to_file($var_names_array);
    }
}
