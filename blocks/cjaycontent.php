<?php

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
// ------------------------------------------------------------------------- //
// **************************************************************************//
// * Function: b_freecontent_show                                                                         *//
// * Output  : Returns the links to FC content with hide=0                                 *//
// **************************************************************************//

function b_cjaycontent_show($options)
{
    global $xoopsDB, $xoopsConfig;

    $myts = MyTextSanitizer::getInstance();

    $block = [];

    $block['title'] = _FC_BLOCK_TITLE;

    $block['content'] = '';

    // Query Database for link generation

    $result = $xoopsDB->query('SELECT id, title, hits, weight FROM ' . $xoopsDB->prefix() . '_cjaycontent WHERE hide=0');

    // generate links

    while ($fc_item = $xoopsDB->fetchArray($result)) {
        $fc_title = htmlspecialchars($fc_item['title'], ENT_QUOTES | ENT_HTML5);

        // shorten linktitles to fit into the block

        if (!XOOPS_USE_MULTIBYTES) {
            if (mb_strlen($fc_title) >= 40) {
                $fc_title = mb_substr($fc_title, 0, 39) . '...';
            }
        }

        if ('..' != mb_substr($fc_title, 0, 2)) {
            $block['content'] .= '<li><a href="' . XOOPS_URL . '/modules/cjaycontent/index.php?id=' . $fc_item['id'] . '">' . $fc_title . '</a></li>';
        } else {
            $block['content'] .= '<strong>' . mb_substr($fc_title, 2, mb_strlen($fc_title) - 2) . '</strong>&nbsp;';
        }

        // there will be an hits-display coded in later versions

        $block['content'] .= '<br>';
    }

    // $block['content'] .= "";

    return $block;
}
