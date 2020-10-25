<?php

include '../../mainfile.php';
if ('cjaycontent' == $xoopsConfig['startpage']) {
    $cj_start_id = 1;

    $id = $cj_start_id;
} else {
    $id = $_GET['id'];

    $cj_item = cj_query_db_on_id($id);
}
$GLOBALS['xoopsOption']['template_main'] = 'cc_index.html';
require XOOPS_ROOT_PATH . '/header.php';
//$id exists?
if (isset($cj_item['adress']) && '' != $cj_item['adress']) {
    $source = '';

    // include content

    $cj_include = XOOPS_ROOT_PATH . '/modules/cjaycontent/content/' . $cj_item['adress'];

    if (file_exists($cj_include)) {
        ob_start();

        include $cj_include;

        $cj_data = ob_get_contents();

        ob_end_clean();

        $source = &$cj_data;

        $res = $xoopsDB->queryF('UPDATE ' . $xoopsDB->prefix() . '_cjaycontent SET hits=hits+1 WHERE id="' . $id . '"');
    } else {
        $source .= _CC_FILENOTFOUND . $fc_include;
    }
} else {
    $cj_include = XOOPS_ROOT_PATH . '/modules/cjaycontent/content/DO_NOT_DELETE.php';

    if (file_exists($cj_include)) {
        ob_start();

        include $cj_include;

        $cj_data = ob_get_contents();

        ob_end_clean();

        $source = &$cj_data;

        // increment hitcounter (hits)

        $res = $xoopsDB->queryF('UPDATE ' . $xoopsDB->prefix() . '_cjaycontent SET hits=hits+1 WHERE id="' . $id . '"');
    }
}

$xoopsTpl->assign('cjcontent', $source);
require XOOPS_ROOT_PATH . '/include/comment_view.php';
require XOOPS_ROOT_PATH . '/footer.php';
//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
function cj_query_db_on_id($id)
{
    global $xoopsDB;

    //query Database (returns an array)

    $result = $xoopsDB->queryF('SELECT adress FROM ' . $xoopsDB->prefix() . '_cjaycontent WHERE id="' . $id . '"', 1);

    return $xoopsDB->fetchArray($result);
}
