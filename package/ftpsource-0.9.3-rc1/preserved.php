<?php return array (
  '5af02154f59e3808185eb2e9c9d42c98' => 
  array (
    'criteria' => 
    array (
      'name' => 'ftpsource',
    ),
    'object' => 
    array (
      'name' => 'ftpsource',
      'path' => '{core_path}components/ftpsource/',
      'assets_path' => '',
    ),
  ),
  'c7c3d5c607070bd7b80fd3e37d047209' => 
  array (
    'criteria' => 
    array (
      'category' => 'ftpsource',
    ),
    'object' => 
    array (
      'id' => 2,
      'parent' => 0,
      'category' => 'ftpsource',
      'rank' => 0,
    ),
  ),
  'db901e95c16c2dada5d903b3e1b8b14d' => 
  array (
    'criteria' => 
    array (
      'name' => 'Archivist',
    ),
    'object' => 
    array (
      'id' => 1,
      'source' => 0,
      'property_preprocess' => 0,
      'name' => 'Archivist',
      'description' => '',
      'editor_type' => 0,
      'category' => 2,
      'cache_type' => 0,
      'snippet' => '/**
 * Archivist
 *
 * Copyright 2010-2011 by Shaun McCormick <shaun@modx.com>
 *
 * This file is part of Archivist, a simple archive navigation system for MODx
 * Revolution.
 *
 * Archivist is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Archivist is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Archivist; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package archivist
 */
/**
 * Display an archived result filter list
 *
 * @var modX $modx
 * @var array $scriptProperties
 * @var Archivist $archivist
 *
 * @package archivist
 */
$archivist = $modx->getService(\'archivist\',\'Archivist\',$modx->getOption(\'archivist.core_path\',null,$modx->getOption(\'core_path\').\'components/archivist/\').\'model/archivist/\',$scriptProperties);
if (!($archivist instanceof Archivist)) return \'\';

/* setup default properties */
$tpl = $modx->getOption(\'tpl\',$scriptProperties,\'row\');
$parents = !empty($scriptProperties[\'parents\']) ? $scriptProperties[\'parents\'] : $modx->resource->get(\'id\');
$parents = explode(\',\',$parents);
$target = !empty($scriptProperties[\'target\']) ? $scriptProperties[\'target\'] : $modx->resource->get(\'id\');
$sortBy = $modx->getOption(\'sortBy\',$scriptProperties,\'publishedon\');
$sortDir = $modx->getOption(\'sortDir\',$scriptProperties,\'DESC\');
$groupByYear = $modx->getOption(\'groupByYear\',$scriptProperties,false);
$sortYear = $modx->getOption(\'sortYear\',$scriptProperties,\'DESC\');
$depth = $modx->getOption(\'depth\',$scriptProperties,10);
$where = $modx->getOption(\'where\',$scriptProperties,\'\');

$cls = $modx->getOption(\'cls\',$scriptProperties,\'arc-row\');
$altCls = $modx->getOption(\'altCls\',$scriptProperties,\'arc-row-alt\');
$lastCls = $modx->getOption(\'lastCls\',$scriptProperties,\'\');
$firstCls = $modx->getOption(\'firstCls\',$scriptProperties,\'\');

$filterPrefix = $modx->getOption(\'filterPrefix\',$scriptProperties,\'arc_\');
$useMonth = $modx->getOption(\'useMonth\',$scriptProperties,true);
$useDay = $modx->getOption(\'useDay\',$scriptProperties,false);
$dateFormat = !empty($scriptProperties[\'dateFormat\']) ? $scriptProperties[\'dateFormat\'] : \'\';
$limit = $modx->getOption(\'limit\',$scriptProperties,12);
$start = $modx->getOption(\'start\',$scriptProperties,0);
$hideContainers = $modx->getOption(\'hideContainers\',$scriptProperties,true);
$useFurls = $modx->getOption(\'useFurls\',$scriptProperties,true);
$persistGetParams = $modx->getOption(\'persistGetParams\',$scriptProperties,false);

/* handle existing GET params */
$extraParams = $modx->getOption(\'extraParams\',$scriptProperties,array());
$extraParams = $archivist->mergeGetParams($extraParams,$persistGetParams,$filterPrefix);

/* set locale for date processing */
if ($modx->getOption(\'setLocale\',$scriptProperties,true)) {
    $cultureKey = $modx->getOption(\'cultureKey\',null,\'en\');
    $locale = !empty($scriptProperties[\'locale\']) ? $scriptProperties[\'locale\'] : $cultureKey;
    if (!empty($locale)) {
        setlocale(LC_ALL,$locale);
    }
}

/* find children of parents */
$children = array();
foreach ($parents as $parent) {
    $pchildren = $modx->getChildIds($parent, $depth);
    if (!empty($pchildren)) $children = array_merge($children, $pchildren);
}
if (!empty($children)) $parents = array_merge($parents, $children);

/* get filter format */
$dateEmpty = empty($dateFormat);
$sqlDateFormat = \'%Y\';
if ($dateEmpty) $dateFormat = \'%Y\';
if ($useMonth) {
    if ($dateEmpty) $dateFormat = \'%B \'.$dateFormat;
    $sqlDateFormat = \'%b \'.$sqlDateFormat;
}
if ($useDay) {
    if ($dateEmpty) $dateFormat = \'%d \'.$dateFormat;
    $sqlDateFormat = \'%d \'.$sqlDateFormat;
}
/* build query */
$c = $modx->newQuery(\'modResource\');
$fields = $modx->getSelectColumns(\'modResource\',\'\',\'\',array(\'id\',$sortBy));
$c->select($fields);
$c->select(array(
    \'FROM_UNIXTIME(\'.$sortBy.\',"\'.$sqlDateFormat.\'") AS \'.$modx->escape(\'date\'),
    \'FROM_UNIXTIME(\'.$sortBy.\',"\'.$sqlDateFormat.\'") AS \'.$modx->escape(\'date\'),
    \'FROM_UNIXTIME(\'.$sortBy.\',"%D") AS \'.$modx->escape(\'day_formatted\'),
    \'COUNT(\'.$modx->escape(\'id\').\') AS \'.$modx->escape(\'count\'),
));
$c->where(array(
    \'parent:IN\' => $parents,
    \'published\' => true,
    \'deleted\' => false,
));
/* don\'t grab unpublished resources */
$c->where(array(
    \'published\' => true,
));
if ($hideContainers) {
    $c->where(array(
        \'isfolder\' => false,
    ));
}
if (!empty($where)) {
    $where = $modx->fromJSON($where);
    $c->where($where);
}
$exclude = $modx->getOption(\'exclude\',$scriptProperties,\'\');
if (!empty($exclude)) {
    $c->where(array(
        \'id:NOT IN\' => is_array($exclude) ? $exclude : explode(\',\',$exclude),
    ));
}
$c->sortby(\'FROM_UNIXTIME(`\'.$sortBy.\'`,"%Y") \'.$sortDir.\', FROM_UNIXTIME(`\'.$sortBy.\'`,"%m") \'.$sortDir.\', FROM_UNIXTIME(`\'.$sortBy.\'`,"%d") \'.$sortDir,\'\');
$c->groupby(\'FROM_UNIXTIME(`\'.$sortBy.\'`,"\'.$sqlDateFormat.\'")\');
/* if limiting to X records */
if (!empty($limit)) { $c->limit($limit,$start); }
$resources = $modx->getIterator(\'modResource\',$c);

/* iterate over resources */
$output = array();
$groupByYearOutput = array();
$idx = 0;
$count = count($resources);
/** @var modResource $resource */
foreach ($resources as $resource) {
    $resourceArray = $resource->toArray();

    $date = $resource->get($sortBy);
    $dateObj = strtotime($date);

    $resourceArray[\'date\'] = strftime($dateFormat,$dateObj);
    $resourceArray[\'month_name_abbr\'] = strftime(\'%b\',$dateObj);
    $resourceArray[\'month_name\'] = strftime(\'%B\',$dateObj);
    $resourceArray[\'month\'] = strftime(\'%m\',$dateObj);
    $resourceArray[\'year\'] = strftime(\'%Y\',$dateObj);
    $resourceArray[\'year_two_digit\'] = strftime(\'%y\',$dateObj);
    $resourceArray[\'day\'] = strftime(\'%d\',$dateObj);
    $resourceArray[\'weekday\'] = strftime(\'%A\',$dateObj);
    $resourceArray[\'weekday_abbr\'] = strftime(\'%a\',$dateObj);
    $resourceArray[\'weekday_idx\'] = strftime(\'%w\',$dateObj);

    /* css classes */
    $resourceArray[\'cls\'] = $cls;
    if ($idx % 2) { $resourceArray[\'cls\'] .= \' \'.$altCls; }
    if ($idx == 0 && !empty($firstCls)) { $resourceArray[\'cls\'] .= \' \'.$firstCls; }
    if ($idx+1 == $count && !empty($lastCls)) { $resourceArray[\'cls\'] .= \' \'.$lastCls; }

    /* setup GET params */
    $params = array();
    $params[$filterPrefix.\'year\'] = $resourceArray[\'year\'];

    /* if using month filter */
    if ($useMonth) {
        $params[$filterPrefix.\'month\'] = $resourceArray[\'month\'];
    }
    /* if using day filter (why you would ever is beyond me...) */
    if ($useDay) {
        $params[$filterPrefix.\'day\'] = $resourceArray[\'day\'];
        if (empty($scriptProperties[\'dateFormat\'])) {
            $resourceArray[\'date\'] = $resourceArray[\'month_name\'].\' \'.$resourceArray[\'day\'].\', \'.$resourceArray[\'year\'];
        }
    }

    if ($useFurls) {
        $params = implode(\'/\',$params);
        if (!empty($extraParams)) $params .= \'?\'.$extraParams;
        $resourceArray[\'url\'] = $modx->makeUrl($target).$params;
    } else {
        $params = http_build_query($params);
        if (!empty($extraParams)) $params .= \'&\'.$extraParams;
        $resourceArray[\'url\'] = $modx->makeUrl($target,\'\',$params);
    }

    if ($groupByYear) {
        $groupByYearOutput[$resourceArray[\'year\']][] = $resourceArray;
    } else {
        $output[] = $archivist->getChunk($tpl,$resourceArray);
    }

    $idx++;
}

if ($groupByYear) {
    $wrapperTpl = $modx->getOption(\'yearGroupTpl\',$scriptProperties,\'yeargroup\');
    $wrapperRowSeparator = $modx->getOption(\'yearGroupRowSeparator\',$scriptProperties,"\\n");
    if (strtolower($sortYear) === \'asc\') {
        ksort($groupByYearOutput);
    } else {
        krsort($groupByYearOutput);
    }
    foreach ($groupByYearOutput as $year => $row) {
        $wrapper[\'year\'] = $year;

        $params = array();
        $params[$filterPrefix.\'year\'] = $year;

        if ($useFurls) {
            $params = implode(\'/\',$params);
            if (!empty($extraParams)) $params .= \'?\'.$extraParams;
            $wrapper[\'url\'] = $modx->makeUrl($target).$params;
        } else {
            $params = http_build_query($params);
            if (!empty($extraParams)) $params .= \'&\'.$extraParams;
            $wrapper[\'url\'] = $modx->makeUrl($target,\'\',$params);
        }

        $wrapper[\'row\'] = array();
        foreach ($row as $month) {
            $wrapper[\'row\'][] = $archivist->getChunk($tpl,$month);
        }
        $wrapper[\'row\'] = implode($wrapperRowSeparator,$wrapper[\'row\']);
        $output[] = $archivist->getChunk($wrapperTpl,$wrapper);
    }
}

/* output or set to placeholder */
$outputSeparator = $modx->getOption(\'outputSeparator\',$scriptProperties,"\\n");
$output = implode($outputSeparator,$output);
$toPlaceholder = $modx->getOption(\'toPlaceholder\',$scriptProperties,false);
if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder,$output);
    return \'\';
}
return $output;',
      'locked' => 0,
      'properties' => 'a:26:{s:3:"tpl";a:7:{s:4:"name";s:3:"tpl";s:4:"desc";s:23:"prop_archivist.tpl_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:3:"row";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"target";a:7:{s:4:"name";s:6:"target";s:4:"desc";s:26:"prop_archivist.target_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:7:"parents";a:7:{s:4:"name";s:7:"parents";s:4:"desc";s:27:"prop_archivist.parents_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:5:"depth";a:7:{s:4:"name";s:5:"depth";s:4:"desc";s:25:"prop_archivist.depth_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:2:"10";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:7:"exclude";a:7:{s:4:"name";s:7:"exclude";s:4:"desc";s:27:"prop_archivist.exclude_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"sortBy";a:7:{s:4:"name";s:6:"sortBy";s:4:"desc";s:26:"prop_archivist.sortby_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:11:"publishedon";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:7:"sortDir";a:7:{s:4:"name";s:7:"sortDir";s:4:"desc";s:27:"prop_archivist.sortdir_desc";s:4:"type";s:4:"list";s:7:"options";a:2:{i:0;a:2:{s:4:"text";s:18:"prop_arc.ascending";s:5:"value";s:3:"ASC";}i:1;a:2:{s:4:"text";s:19:"prop_arc.descending";s:5:"value";s:4:"DESC";}}s:5:"value";s:4:"DESC";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:5:"limit";a:7:{s:4:"name";s:5:"limit";s:4:"desc";s:25:"prop_archivist.limit_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:2:"12";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:5:"start";a:7:{s:4:"name";s:5:"start";s:4:"desc";s:25:"prop_archivist.start_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:1:"0";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:8:"useMonth";a:7:{s:4:"name";s:8:"useMonth";s:4:"desc";s:28:"prop_archivist.usemonth_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:1;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"useDay";a:7:{s:4:"name";s:6:"useDay";s:4:"desc";s:26:"prop_archivist.useday_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:0;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:11:"groupByYear";a:7:{s:4:"name";s:11:"groupByYear";s:4:"desc";s:31:"prop_archivist.groupbyyear_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:0;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:14:"groupByYearTpl";a:7:{s:4:"name";s:14:"groupByYearTpl";s:4:"desc";s:34:"prop_archivist.groupbyyeartpl_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:9:"yeargroup";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:10:"dateFormat";a:7:{s:4:"name";s:10:"dateFormat";s:4:"desc";s:30:"prop_archivist.dateformat_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:8:"useFurls";a:7:{s:4:"name";s:8:"useFurls";s:4:"desc";s:23:"prop_archivist.usefurls";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:1;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:16:"persistGetParams";a:7:{s:4:"name";s:16:"persistGetParams";s:4:"desc";s:36:"prop_archivist.persistgetparams_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:0;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:11:"extraParams";a:7:{s:4:"name";s:11:"extraParams";s:4:"desc";s:31:"prop_archivist.extraparams_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:3:"cls";a:7:{s:4:"name";s:3:"cls";s:4:"desc";s:23:"prop_archivist.cls_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:7:"arc-row";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"altCls";a:7:{s:4:"name";s:6:"altCls";s:4:"desc";s:26:"prop_archivist.altcls_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:11:"arc-row-alt";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:8:"firstCls";a:7:{s:4:"name";s:8:"firstCls";s:4:"desc";s:28:"prop_archivist.firstcls_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:7:"lastCls";a:7:{s:4:"name";s:7:"lastCls";s:4:"desc";s:27:"prop_archivist.lastcls_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:12:"filterPrefix";a:7:{s:4:"name";s:12:"filterPrefix";s:4:"desc";s:32:"prop_archivist.filterprefix_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:4:"arc_";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:13:"toPlaceholder";a:7:{s:4:"name";s:13:"toPlaceholder";s:4:"desc";s:33:"prop_archivist.toplaceholder_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:9:"setLocale";a:7:{s:4:"name";s:9:"setLocale";s:4:"desc";s:29:"prop_archivist.setlocale_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:1;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"locale";a:7:{s:4:"name";s:6:"locale";s:4:"desc";s:26:"prop_archivist.locale_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";b:1;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:14:"hideContainers";a:7:{s:4:"name";s:14:"hideContainers";s:4:"desc";s:41:"prop_archivistbymonth.hidecontainers_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:1;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}}',
      'moduleguid' => '',
      'static' => 0,
      'static_file' => '',
      'content' => '/**
 * Archivist
 *
 * Copyright 2010-2011 by Shaun McCormick <shaun@modx.com>
 *
 * This file is part of Archivist, a simple archive navigation system for MODx
 * Revolution.
 *
 * Archivist is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Archivist is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Archivist; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package archivist
 */
/**
 * Display an archived result filter list
 *
 * @var modX $modx
 * @var array $scriptProperties
 * @var Archivist $archivist
 *
 * @package archivist
 */
$archivist = $modx->getService(\'archivist\',\'Archivist\',$modx->getOption(\'archivist.core_path\',null,$modx->getOption(\'core_path\').\'components/archivist/\').\'model/archivist/\',$scriptProperties);
if (!($archivist instanceof Archivist)) return \'\';

/* setup default properties */
$tpl = $modx->getOption(\'tpl\',$scriptProperties,\'row\');
$parents = !empty($scriptProperties[\'parents\']) ? $scriptProperties[\'parents\'] : $modx->resource->get(\'id\');
$parents = explode(\',\',$parents);
$target = !empty($scriptProperties[\'target\']) ? $scriptProperties[\'target\'] : $modx->resource->get(\'id\');
$sortBy = $modx->getOption(\'sortBy\',$scriptProperties,\'publishedon\');
$sortDir = $modx->getOption(\'sortDir\',$scriptProperties,\'DESC\');
$groupByYear = $modx->getOption(\'groupByYear\',$scriptProperties,false);
$sortYear = $modx->getOption(\'sortYear\',$scriptProperties,\'DESC\');
$depth = $modx->getOption(\'depth\',$scriptProperties,10);
$where = $modx->getOption(\'where\',$scriptProperties,\'\');

$cls = $modx->getOption(\'cls\',$scriptProperties,\'arc-row\');
$altCls = $modx->getOption(\'altCls\',$scriptProperties,\'arc-row-alt\');
$lastCls = $modx->getOption(\'lastCls\',$scriptProperties,\'\');
$firstCls = $modx->getOption(\'firstCls\',$scriptProperties,\'\');

$filterPrefix = $modx->getOption(\'filterPrefix\',$scriptProperties,\'arc_\');
$useMonth = $modx->getOption(\'useMonth\',$scriptProperties,true);
$useDay = $modx->getOption(\'useDay\',$scriptProperties,false);
$dateFormat = !empty($scriptProperties[\'dateFormat\']) ? $scriptProperties[\'dateFormat\'] : \'\';
$limit = $modx->getOption(\'limit\',$scriptProperties,12);
$start = $modx->getOption(\'start\',$scriptProperties,0);
$hideContainers = $modx->getOption(\'hideContainers\',$scriptProperties,true);
$useFurls = $modx->getOption(\'useFurls\',$scriptProperties,true);
$persistGetParams = $modx->getOption(\'persistGetParams\',$scriptProperties,false);

/* handle existing GET params */
$extraParams = $modx->getOption(\'extraParams\',$scriptProperties,array());
$extraParams = $archivist->mergeGetParams($extraParams,$persistGetParams,$filterPrefix);

/* set locale for date processing */
if ($modx->getOption(\'setLocale\',$scriptProperties,true)) {
    $cultureKey = $modx->getOption(\'cultureKey\',null,\'en\');
    $locale = !empty($scriptProperties[\'locale\']) ? $scriptProperties[\'locale\'] : $cultureKey;
    if (!empty($locale)) {
        setlocale(LC_ALL,$locale);
    }
}

/* find children of parents */
$children = array();
foreach ($parents as $parent) {
    $pchildren = $modx->getChildIds($parent, $depth);
    if (!empty($pchildren)) $children = array_merge($children, $pchildren);
}
if (!empty($children)) $parents = array_merge($parents, $children);

/* get filter format */
$dateEmpty = empty($dateFormat);
$sqlDateFormat = \'%Y\';
if ($dateEmpty) $dateFormat = \'%Y\';
if ($useMonth) {
    if ($dateEmpty) $dateFormat = \'%B \'.$dateFormat;
    $sqlDateFormat = \'%b \'.$sqlDateFormat;
}
if ($useDay) {
    if ($dateEmpty) $dateFormat = \'%d \'.$dateFormat;
    $sqlDateFormat = \'%d \'.$sqlDateFormat;
}
/* build query */
$c = $modx->newQuery(\'modResource\');
$fields = $modx->getSelectColumns(\'modResource\',\'\',\'\',array(\'id\',$sortBy));
$c->select($fields);
$c->select(array(
    \'FROM_UNIXTIME(\'.$sortBy.\',"\'.$sqlDateFormat.\'") AS \'.$modx->escape(\'date\'),
    \'FROM_UNIXTIME(\'.$sortBy.\',"\'.$sqlDateFormat.\'") AS \'.$modx->escape(\'date\'),
    \'FROM_UNIXTIME(\'.$sortBy.\',"%D") AS \'.$modx->escape(\'day_formatted\'),
    \'COUNT(\'.$modx->escape(\'id\').\') AS \'.$modx->escape(\'count\'),
));
$c->where(array(
    \'parent:IN\' => $parents,
    \'published\' => true,
    \'deleted\' => false,
));
/* don\'t grab unpublished resources */
$c->where(array(
    \'published\' => true,
));
if ($hideContainers) {
    $c->where(array(
        \'isfolder\' => false,
    ));
}
if (!empty($where)) {
    $where = $modx->fromJSON($where);
    $c->where($where);
}
$exclude = $modx->getOption(\'exclude\',$scriptProperties,\'\');
if (!empty($exclude)) {
    $c->where(array(
        \'id:NOT IN\' => is_array($exclude) ? $exclude : explode(\',\',$exclude),
    ));
}
$c->sortby(\'FROM_UNIXTIME(`\'.$sortBy.\'`,"%Y") \'.$sortDir.\', FROM_UNIXTIME(`\'.$sortBy.\'`,"%m") \'.$sortDir.\', FROM_UNIXTIME(`\'.$sortBy.\'`,"%d") \'.$sortDir,\'\');
$c->groupby(\'FROM_UNIXTIME(`\'.$sortBy.\'`,"\'.$sqlDateFormat.\'")\');
/* if limiting to X records */
if (!empty($limit)) { $c->limit($limit,$start); }
$resources = $modx->getIterator(\'modResource\',$c);

/* iterate over resources */
$output = array();
$groupByYearOutput = array();
$idx = 0;
$count = count($resources);
/** @var modResource $resource */
foreach ($resources as $resource) {
    $resourceArray = $resource->toArray();

    $date = $resource->get($sortBy);
    $dateObj = strtotime($date);

    $resourceArray[\'date\'] = strftime($dateFormat,$dateObj);
    $resourceArray[\'month_name_abbr\'] = strftime(\'%b\',$dateObj);
    $resourceArray[\'month_name\'] = strftime(\'%B\',$dateObj);
    $resourceArray[\'month\'] = strftime(\'%m\',$dateObj);
    $resourceArray[\'year\'] = strftime(\'%Y\',$dateObj);
    $resourceArray[\'year_two_digit\'] = strftime(\'%y\',$dateObj);
    $resourceArray[\'day\'] = strftime(\'%d\',$dateObj);
    $resourceArray[\'weekday\'] = strftime(\'%A\',$dateObj);
    $resourceArray[\'weekday_abbr\'] = strftime(\'%a\',$dateObj);
    $resourceArray[\'weekday_idx\'] = strftime(\'%w\',$dateObj);

    /* css classes */
    $resourceArray[\'cls\'] = $cls;
    if ($idx % 2) { $resourceArray[\'cls\'] .= \' \'.$altCls; }
    if ($idx == 0 && !empty($firstCls)) { $resourceArray[\'cls\'] .= \' \'.$firstCls; }
    if ($idx+1 == $count && !empty($lastCls)) { $resourceArray[\'cls\'] .= \' \'.$lastCls; }

    /* setup GET params */
    $params = array();
    $params[$filterPrefix.\'year\'] = $resourceArray[\'year\'];

    /* if using month filter */
    if ($useMonth) {
        $params[$filterPrefix.\'month\'] = $resourceArray[\'month\'];
    }
    /* if using day filter (why you would ever is beyond me...) */
    if ($useDay) {
        $params[$filterPrefix.\'day\'] = $resourceArray[\'day\'];
        if (empty($scriptProperties[\'dateFormat\'])) {
            $resourceArray[\'date\'] = $resourceArray[\'month_name\'].\' \'.$resourceArray[\'day\'].\', \'.$resourceArray[\'year\'];
        }
    }

    if ($useFurls) {
        $params = implode(\'/\',$params);
        if (!empty($extraParams)) $params .= \'?\'.$extraParams;
        $resourceArray[\'url\'] = $modx->makeUrl($target).$params;
    } else {
        $params = http_build_query($params);
        if (!empty($extraParams)) $params .= \'&\'.$extraParams;
        $resourceArray[\'url\'] = $modx->makeUrl($target,\'\',$params);
    }

    if ($groupByYear) {
        $groupByYearOutput[$resourceArray[\'year\']][] = $resourceArray;
    } else {
        $output[] = $archivist->getChunk($tpl,$resourceArray);
    }

    $idx++;
}

if ($groupByYear) {
    $wrapperTpl = $modx->getOption(\'yearGroupTpl\',$scriptProperties,\'yeargroup\');
    $wrapperRowSeparator = $modx->getOption(\'yearGroupRowSeparator\',$scriptProperties,"\\n");
    if (strtolower($sortYear) === \'asc\') {
        ksort($groupByYearOutput);
    } else {
        krsort($groupByYearOutput);
    }
    foreach ($groupByYearOutput as $year => $row) {
        $wrapper[\'year\'] = $year;

        $params = array();
        $params[$filterPrefix.\'year\'] = $year;

        if ($useFurls) {
            $params = implode(\'/\',$params);
            if (!empty($extraParams)) $params .= \'?\'.$extraParams;
            $wrapper[\'url\'] = $modx->makeUrl($target).$params;
        } else {
            $params = http_build_query($params);
            if (!empty($extraParams)) $params .= \'&\'.$extraParams;
            $wrapper[\'url\'] = $modx->makeUrl($target,\'\',$params);
        }

        $wrapper[\'row\'] = array();
        foreach ($row as $month) {
            $wrapper[\'row\'][] = $archivist->getChunk($tpl,$month);
        }
        $wrapper[\'row\'] = implode($wrapperRowSeparator,$wrapper[\'row\']);
        $output[] = $archivist->getChunk($wrapperTpl,$wrapper);
    }
}

/* output or set to placeholder */
$outputSeparator = $modx->getOption(\'outputSeparator\',$scriptProperties,"\\n");
$output = implode($outputSeparator,$output);
$toPlaceholder = $modx->getOption(\'toPlaceholder\',$scriptProperties,false);
if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder,$output);
    return \'\';
}
return $output;',
    ),
  ),
  '0fe7be4a5c82969acb6d9edf83d67616' => 
  array (
    'criteria' => 
    array (
      'name' => 'getArchives',
    ),
    'object' => 
    array (
      'id' => 2,
      'source' => 0,
      'property_preprocess' => 0,
      'name' => 'getArchives',
      'description' => '',
      'editor_type' => 0,
      'category' => 2,
      'cache_type' => 0,
      'snippet' => '/**
 * Archivist
 *
 * Copyright 2010-2011 by Shaun McCormick <shaun@modx.com>
 *
 * This file is part of Archivist, a simple archive navigation system for MODx
 * Revolution.
 *
 * Archivist is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Archivist is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Archivist; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package archivist
 */
/**
 * getArchives
 *
 * Used to display a list of Resources for a given archive state.
 *
 * Written by Shaun McCormick <shaun+archivist@modx.com>. Based on getResources by Jason Coward <jason@modxcms.com>
 *
 * @var Archivist $archivist
 * @var modX $modx
 * @var array $scriptProperties
 * 
 * @package archivist
 */
$archivist = $modx->getService(\'archivist\',\'Archivist\',$modx->getOption(\'archivist.core_path\',null,$modx->getOption(\'core_path\').\'components/archivist/\').\'model/archivist/\',$scriptProperties);
if (!($archivist instanceof Archivist)) return \'\';

/* setup some getArchives-specific properties */
$filterPrefix = $modx->getOption(\'filterPrefix\',$scriptProperties,\'arc_\');
$filterField = $modx->getOption(\'filterField\',$scriptProperties,\'publishedon\');

/* first off, let\'s sync the archivist.archive_ids setting */
if ($modx->getOption(\'makeArchive\',$scriptProperties,true)) {
    $archivist->makeArchive($modx->resource->get(\'id\'),$filterPrefix);
}

/* get filter by year, month, and/or day. Sanitize to prevent injection. */
$where = $modx->getOption(\'where\',$scriptProperties,false);
$where = is_array($where) ? $where : $modx->fromJSON($where);
$parameters = $modx->request->getParameters();

$year = $modx->getOption($filterPrefix.\'year\',$parameters,$modx->getOption(\'year\',$scriptProperties,\'\'));
$year = (int)$archivist->sanitize($year);
if (!empty($year)) {
    $modx->setPlaceholder($filterPrefix.\'year\',$year);
    $where[] = \'FROM_UNIXTIME(`\'.$filterField.\'`,"%Y") = "\'.$year.\'"\';
}
$month = $modx->getOption($filterPrefix.\'month\',$parameters,$modx->getOption(\'month\',$scriptProperties,\'\'));
$month = (int)$archivist->sanitize($month);
if (!empty($month)) {
    if (strlen($month) == 1) $month = \'0\'.$month;
    $modx->setPlaceholder($filterPrefix.\'month\',$month);
    $modx->setPlaceholder($filterPrefix.\'month_name\',$archivist->translateMonth($month));
    $where[] = \'FROM_UNIXTIME(`\'.$filterField.\'`,"%m") = "\'.$month.\'"\';
}
$day = $modx->getOption($filterPrefix.\'day\',$parameters,$modx->getOption(\'day\',$scriptProperties,\'\'));
$day = (int)$archivist->sanitize($day);
if (!empty($day)) {
    if (strlen($day) == 1) $day = \'0\'.$day;
    $modx->setPlaceholder($filterPrefix.\'day\',$day);
    $where[] = \'FROM_UNIXTIME(`\'.$filterField.\'`,"%d") = "\'.$day.\'"\';
}

/* author handling */
if (!empty($parameters[$filterPrefix.\'author\'])) {
    /** @var modUser $user */
    $user = $modx->getObject(\'modUser\',array(\'username\' => $parameters[$filterPrefix.\'author\']));
    if ($user) {
        $where[\'createdby\'] = $user->get(\'id\');
    }
}
$scriptProperties[\'where\'] = $modx->toJSON($where);

/* better tags handling */
$tagKeyVar = $modx->getOption(\'tagKeyVar\',$scriptProperties,\'key\');
$tagKey = (!empty($tagKeyVar) && array_key_exists($tagKeyVar,$parameters) && !empty($parameters[$tagKeyVar])) ? $parameters[$tagKeyVar] : $modx->getOption(\'tagKey\',$scriptProperties,\'tags\');
$tagRequestParam = $modx->getOption(\'tagRequestParam\',$scriptProperties,\'tag\');
$tag = $modx->getOption(\'tag\',$scriptProperties,array_key_exists($tagRequestParam,$parameters) ? urldecode($parameters[$tagRequestParam]) : \'\');
if (!empty($tag)) {
    $tag = $modx->stripTags($tag);
    $tagSearchType = $modx->getOption(\'tagSearchType\',$scriptProperties,\'exact\');
    if ($tagSearchType == \'contains\') {
        $scriptProperties[\'tvFilters\'] = $tagKey.\'==%\'.$tag.\'%\';
    } else if ($tagSearchType == \'beginswith\') {
        $scriptProperties[\'tvFilters\'] = $tagKey.\'==%\'.$tag.\'\';
    } else if ($tagSearchType == \'endswith\') {
        $scriptProperties[\'tvFilters\'] = $tagKey.\'==\'.$tag.\'%\';
    } else {
        $scriptProperties[\'tvFilters\'] = $tagKey.\'==\'.$tag.\'\';
    }
}

$grSnippet = $modx->getOption(\'grSnippet\',$scriptProperties,\'getResources\');
/** @var modSnippet $snippet */
$snippet = $modx->getObject(\'modSnippet\', array(\'name\' => $grSnippet));
if ($snippet) {
    $snippet->setCacheable(false);
    $output = $snippet->process($scriptProperties);
} else {
    return \'You must have getResources downloaded and installed to use this snippet.\';
}
return $output;',
      'locked' => 0,
      'properties' => 'a:28:{s:3:"tpl";a:7:{s:4:"name";s:3:"tpl";s:4:"desc";s:25:"prop_getarchives.tpl_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:11:"filterField";a:7:{s:4:"name";s:11:"filterField";s:4:"desc";s:33:"prop_getarchives.filterfield_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:11:"publishedon";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:12:"filterPrefix";a:7:{s:4:"name";s:12:"filterPrefix";s:4:"desc";s:34:"prop_getarchives.filterprefix_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:4:"arc_";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"tagKey";a:7:{s:4:"name";s:6:"tagKey";s:4:"desc";s:28:"prop_getarchives.tagkey_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:4:"tags";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:9:"tagKeyVar";a:7:{s:4:"name";s:9:"tagKeyVar";s:4:"desc";s:31:"prop_getarchives.tagkeyvar_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:3:"key";s:7:"lexicon";s:20:"taglister:properties";s:4:"area";s:0:"";}s:15:"tagRequestParam";a:7:{s:4:"name";s:15:"tagRequestParam";s:4:"desc";s:37:"prop_getarchives.tagrequestparam_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:3:"tag";s:7:"lexicon";s:20:"taglister:properties";s:4:"area";s:0:"";}s:13:"tagSearchType";a:7:{s:4:"name";s:13:"tagSearchType";s:4:"desc";s:35:"prop_getarchives.tagsearchtype_desc";s:4:"type";s:4:"list";s:7:"options";a:4:{i:0;a:2:{s:4:"text";s:12:"tst_contains";s:5:"value";s:8:"contains";}i:1;a:2:{s:4:"text";s:9:"tst_exact";s:5:"value";s:5:"exact";}i:2;a:2:{s:4:"text";s:14:"tst_beginswith";s:5:"value";s:10:"beginswith";}i:3;a:2:{s:4:"text";s:12:"tst_endswith";s:5:"value";s:8:"endswith";}}s:5:"value";s:8:"contains";s:7:"lexicon";s:20:"taglister:properties";s:4:"area";s:0:"";}s:13:"toPlaceholder";a:7:{s:4:"name";s:13:"toPlaceholder";s:4:"desc";s:35:"prop_getarchives.toplaceholder_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"tplOdd";a:7:{s:4:"name";s:6:"tplOdd";s:4:"desc";s:28:"prop_getarchives.tplodd_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:8:"tplFirst";a:7:{s:4:"name";s:8:"tplFirst";s:4:"desc";s:30:"prop_getarchives.tplfirst_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:7:"tplLast";a:7:{s:4:"name";s:7:"tplLast";s:4:"desc";s:29:"prop_getarchives.tpllast_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"sortby";a:7:{s:4:"name";s:6:"sortby";s:4:"desc";s:28:"prop_getarchives.sortby_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:11:"publishedon";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:11:"sortbyAlias";a:7:{s:4:"name";s:11:"sortbyAlias";s:4:"desc";s:33:"prop_getarchives.sortbyalias_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:13:"sortbyEscaped";a:7:{s:4:"name";s:13:"sortbyEscaped";s:4:"desc";s:35:"prop_getarchives.sortbyescaped_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:1:"0";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:7:"sortdir";a:7:{s:4:"name";s:7:"sortdir";s:4:"desc";s:29:"prop_getarchives.sortdir_desc";s:4:"type";s:4:"list";s:7:"options";a:2:{i:0;a:2:{s:4:"text";s:18:"prop_arc.ascending";s:5:"value";s:3:"ASC";}i:1;a:2:{s:4:"text";s:19:"prop_arc.descending";s:5:"value";s:4:"DESC";}}s:5:"value";s:4:"DESC";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:5:"limit";a:7:{s:4:"name";s:5:"limit";s:4:"desc";s:27:"prop_getarchives.limit_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:1:"5";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"offset";a:7:{s:4:"name";s:6:"offset";s:4:"desc";s:28:"prop_getarchives.offset_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:1:"0";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:9:"tvFilters";a:7:{s:4:"name";s:9:"tvFilters";s:4:"desc";s:31:"prop_getarchives.tvfilters_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:5:"depth";a:7:{s:4:"name";s:5:"depth";s:4:"desc";s:27:"prop_getarchives.depth_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:2:"10";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:7:"parents";a:7:{s:4:"name";s:7:"parents";s:4:"desc";s:29:"prop_getarchives.parents_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:14:"includeContent";a:7:{s:4:"name";s:14:"includeContent";s:4:"desc";s:36:"prop_getarchives.includecontent_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:0;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:10:"includeTVs";a:7:{s:4:"name";s:10:"includeTVs";s:4:"desc";s:32:"prop_getarchives.includetvs_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:0;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:10:"processTVs";a:7:{s:4:"name";s:10:"processTVs";s:4:"desc";s:32:"prop_getarchives.processtvs_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:0;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:8:"tvPrefix";a:7:{s:4:"name";s:8:"tvPrefix";s:4:"desc";s:30:"prop_getarchives.tvprefix_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:3:"tv.";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:3:"idx";a:7:{s:4:"name";s:3:"idx";s:4:"desc";s:25:"prop_getarchives.idx_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:5:"first";a:7:{s:4:"name";s:5:"first";s:4:"desc";s:27:"prop_getarchives.first_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:4:"last";a:7:{s:4:"name";s:4:"last";s:4:"desc";s:26:"prop_getarchives.last_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:5:"debug";a:7:{s:4:"name";s:5:"debug";s:4:"desc";s:27:"prop_getarchives.debug_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:0;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}}',
      'moduleguid' => '',
      'static' => 0,
      'static_file' => '',
      'content' => '/**
 * Archivist
 *
 * Copyright 2010-2011 by Shaun McCormick <shaun@modx.com>
 *
 * This file is part of Archivist, a simple archive navigation system for MODx
 * Revolution.
 *
 * Archivist is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Archivist is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Archivist; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package archivist
 */
/**
 * getArchives
 *
 * Used to display a list of Resources for a given archive state.
 *
 * Written by Shaun McCormick <shaun+archivist@modx.com>. Based on getResources by Jason Coward <jason@modxcms.com>
 *
 * @var Archivist $archivist
 * @var modX $modx
 * @var array $scriptProperties
 * 
 * @package archivist
 */
$archivist = $modx->getService(\'archivist\',\'Archivist\',$modx->getOption(\'archivist.core_path\',null,$modx->getOption(\'core_path\').\'components/archivist/\').\'model/archivist/\',$scriptProperties);
if (!($archivist instanceof Archivist)) return \'\';

/* setup some getArchives-specific properties */
$filterPrefix = $modx->getOption(\'filterPrefix\',$scriptProperties,\'arc_\');
$filterField = $modx->getOption(\'filterField\',$scriptProperties,\'publishedon\');

/* first off, let\'s sync the archivist.archive_ids setting */
if ($modx->getOption(\'makeArchive\',$scriptProperties,true)) {
    $archivist->makeArchive($modx->resource->get(\'id\'),$filterPrefix);
}

/* get filter by year, month, and/or day. Sanitize to prevent injection. */
$where = $modx->getOption(\'where\',$scriptProperties,false);
$where = is_array($where) ? $where : $modx->fromJSON($where);
$parameters = $modx->request->getParameters();

$year = $modx->getOption($filterPrefix.\'year\',$parameters,$modx->getOption(\'year\',$scriptProperties,\'\'));
$year = (int)$archivist->sanitize($year);
if (!empty($year)) {
    $modx->setPlaceholder($filterPrefix.\'year\',$year);
    $where[] = \'FROM_UNIXTIME(`\'.$filterField.\'`,"%Y") = "\'.$year.\'"\';
}
$month = $modx->getOption($filterPrefix.\'month\',$parameters,$modx->getOption(\'month\',$scriptProperties,\'\'));
$month = (int)$archivist->sanitize($month);
if (!empty($month)) {
    if (strlen($month) == 1) $month = \'0\'.$month;
    $modx->setPlaceholder($filterPrefix.\'month\',$month);
    $modx->setPlaceholder($filterPrefix.\'month_name\',$archivist->translateMonth($month));
    $where[] = \'FROM_UNIXTIME(`\'.$filterField.\'`,"%m") = "\'.$month.\'"\';
}
$day = $modx->getOption($filterPrefix.\'day\',$parameters,$modx->getOption(\'day\',$scriptProperties,\'\'));
$day = (int)$archivist->sanitize($day);
if (!empty($day)) {
    if (strlen($day) == 1) $day = \'0\'.$day;
    $modx->setPlaceholder($filterPrefix.\'day\',$day);
    $where[] = \'FROM_UNIXTIME(`\'.$filterField.\'`,"%d") = "\'.$day.\'"\';
}

/* author handling */
if (!empty($parameters[$filterPrefix.\'author\'])) {
    /** @var modUser $user */
    $user = $modx->getObject(\'modUser\',array(\'username\' => $parameters[$filterPrefix.\'author\']));
    if ($user) {
        $where[\'createdby\'] = $user->get(\'id\');
    }
}
$scriptProperties[\'where\'] = $modx->toJSON($where);

/* better tags handling */
$tagKeyVar = $modx->getOption(\'tagKeyVar\',$scriptProperties,\'key\');
$tagKey = (!empty($tagKeyVar) && array_key_exists($tagKeyVar,$parameters) && !empty($parameters[$tagKeyVar])) ? $parameters[$tagKeyVar] : $modx->getOption(\'tagKey\',$scriptProperties,\'tags\');
$tagRequestParam = $modx->getOption(\'tagRequestParam\',$scriptProperties,\'tag\');
$tag = $modx->getOption(\'tag\',$scriptProperties,array_key_exists($tagRequestParam,$parameters) ? urldecode($parameters[$tagRequestParam]) : \'\');
if (!empty($tag)) {
    $tag = $modx->stripTags($tag);
    $tagSearchType = $modx->getOption(\'tagSearchType\',$scriptProperties,\'exact\');
    if ($tagSearchType == \'contains\') {
        $scriptProperties[\'tvFilters\'] = $tagKey.\'==%\'.$tag.\'%\';
    } else if ($tagSearchType == \'beginswith\') {
        $scriptProperties[\'tvFilters\'] = $tagKey.\'==%\'.$tag.\'\';
    } else if ($tagSearchType == \'endswith\') {
        $scriptProperties[\'tvFilters\'] = $tagKey.\'==\'.$tag.\'%\';
    } else {
        $scriptProperties[\'tvFilters\'] = $tagKey.\'==\'.$tag.\'\';
    }
}

$grSnippet = $modx->getOption(\'grSnippet\',$scriptProperties,\'getResources\');
/** @var modSnippet $snippet */
$snippet = $modx->getObject(\'modSnippet\', array(\'name\' => $grSnippet));
if ($snippet) {
    $snippet->setCacheable(false);
    $output = $snippet->process($scriptProperties);
} else {
    return \'You must have getResources downloaded and installed to use this snippet.\';
}
return $output;',
    ),
  ),
  '6cd1c0e02254de06ae5295d95b03dc63' => 
  array (
    'criteria' => 
    array (
      'name' => 'ArchivistGrouper',
    ),
    'object' => 
    array (
      'id' => 3,
      'source' => 0,
      'property_preprocess' => 0,
      'name' => 'ArchivistGrouper',
      'description' => '',
      'editor_type' => 0,
      'category' => 2,
      'cache_type' => 0,
      'snippet' => '/**
 * Archivist
 *
 * Copyright 2010-2011 by Shaun McCormick <shaun@modx.com>
 *
 * This file is part of Archivist, a simple archive navigation system for MODx
 * Revolution.
 *
 * Archivist is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Archivist is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Archivist; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package archivist
 */
/*
 * Display an archived result filter list, nested by month
 *
 * @package archivist
 */
$archivist = $modx->getService(\'archivist\',\'Archivist\',$modx->getOption(\'archivist.core_path\',null,$modx->getOption(\'core_path\').\'components/archivist/\').\'model/archivist/\',$scriptProperties);
if (!($archivist instanceof Archivist)) return \'\';

/* setup default properties */
$mode = $modx->getOption(\'mode\',$scriptProperties,\'month\');
$itemTpl = $modx->getOption(\'itemTpl\',$scriptProperties,\'itemBrief\');
$parents = !empty($scriptProperties[\'parents\']) ? $scriptProperties[\'parents\'] : $modx->resource->get(\'id\');
$parents = explode(\',\',$parents);
$target = !empty($scriptProperties[\'target\']) ? $scriptProperties[\'target\'] : $modx->resource->get(\'id\');
$depth = $modx->getOption(\'depth\',$scriptProperties,10);
$where = $modx->getOption(\'where\',$scriptProperties,\'\');
$hideContainers = $modx->getOption(\'hideContainers\',$scriptProperties,true);
$sortBy = $modx->getOption(\'sortBy\',$scriptProperties,\'publishedon\');
$sortDir = $modx->getOption(\'sortDir\',$scriptProperties,\'DESC\');
$dateFormat = !empty($scriptProperties[\'dateFormat\']) ? $scriptProperties[\'dateFormat\'] : \'\';
$limitGroups = $modx->getOption(\'limitGroups\',$scriptProperties,12);
$limitItems = $modx->getOption(\'limitItems\',$scriptProperties,0);
$resourceSeparator = $modx->getOption(\'resourceSeparator\',$scriptProperties,"\\n");
$groupSeparator = $modx->getOption(\'monthSeparator\',$scriptProperties,"\\n");

$filterPrefix = $modx->getOption(\'filterPrefix\',$scriptProperties,\'arc_\');
$useFurls = $modx->getOption(\'useFurls\',$scriptProperties,true);
$persistGetParams = $modx->getOption(\'persistGetParams\',$scriptProperties,false);
/* handle existing GET params */
$extraParams = $modx->getOption(\'extraParams\',$scriptProperties,array());
$extraParams = $archivist->mergeGetParams($extraParams,$persistGetParams,$filterPrefix);

$cls = $modx->getOption(\'cls\',$scriptProperties,\'arc-resource-row\');
$altCls = $modx->getOption(\'altCls\',$scriptProperties,\'arc-resource-row-alt\');

/* set locale for date processing */
if ($modx->getOption(\'setLocale\',$scriptProperties,true)) {
    $cultureKey = $modx->getOption(\'cultureKey\',null,\'en\');
    $locale = !empty($scriptProperties[\'locale\']) ? $scriptProperties[\'locale\'] : $cultureKey;
    if (!empty($locale)) {
        setlocale(LC_ALL,$locale);
    }
}

/* find children of parents */
$children = array();
foreach ($parents as $parent) {
    $pchildren = $modx->getChildIds($parent, $depth);
    if (!empty($pchildren)) $children = array_merge($children, $pchildren);
}
if (!empty($children)) $parents = array_merge($parents, $children);

/* build query */
$c = $modx->newQuery(\'modResource\');
$c->where(array(
    \'parent:IN\' => $parents,
    \'published\' => true,
    \'deleted\' => false,
));
if ($hideContainers) {
    $c->where(array(
        \'isfolder\' => false,
    ));
}
if (!empty($where)) {
    $where = $modx->fromJSON($where);
    $c->where($where);
}
$c->sortby(\'FROM_UNIXTIME(\'.$sortBy.\',"%Y") \'.$sortDir.\', FROM_UNIXTIME(\'.$sortBy.\',"%m") \'.$sortDir.\', FROM_UNIXTIME(\'.$sortBy.\',"%d") \'.$sortDir,\'\');
$resources = $modx->getIterator(\'modResource\',$c);

/* get grouping constraint */
switch ($mode) {
    case \'year\':
        $groupConstraint = \'%Y-01-01\';
        $groupDefaultTpl = \'yearContainer\';
        break;
    case \'month\':
    default:
        $groupConstraint = \'%Y-%m-01\';
        $groupDefaultTpl = \'monthContainer\';
        break;
}
$groupTpl = !empty($scriptProperties[\'groupTpl\']) ? $scriptProperties[\'groupTpl\'] : $groupDefaultTpl;

/* iterate over resources */
$output = array();
$children = array();
$resourceArray = array();
$groupIdx = 0;
$childIdx = 0;
$countGroups = 0;
foreach ($resources as $resource) {
    $resourceArray = $resource->toArray();
    $date = $resource->get($sortBy);
    $dateObj = strtotime($date);
    $activeTime = strftime($groupConstraint,$dateObj);
    if (!isset($currentTime)) {
        $currentTime = $activeTime;
    }

    $resourceArray[\'date\'] = strftime($dateFormat,$dateObj);
    $resourceArray[\'month_name_abbr\'] = strftime(\'%h\',$dateObj);
    $resourceArray[\'month_name\'] = strftime(\'%B\',$dateObj);
    $resourceArray[\'month\'] = strftime(\'%m\',$dateObj);
    $resourceArray[\'year\'] = strftime(\'%Y\',$dateObj);
    $resourceArray[\'year_two_digit\'] = strftime(\'%y\',$dateObj);
    $resourceArray[\'day\'] = strftime(\'%d\',$dateObj);
    $resourceArray[\'weekday\'] = strftime(\'%A\',$dateObj);
    $resourceArray[\'weekday_abbr\'] = strftime(\'%a\',$dateObj);
    $resourceArray[\'weekday_idx\'] = strftime(\'%w\',$dateObj);

    /* css classes */
    $resourceArray[\'cls\'] = $cls;
    if ($childIdx % 2) { $resourceArray[\'cls\'] .= \' \'.$altCls; }
    $resourceArray[\'idx\'] = $childIdx;

    if ($currentTime != $activeTime) {
        $groupArray = array();
        $timestamp = strtotime($currentTime);
        $groupArray[\'month_name\'] = strftime(\'%B\',$timestamp);
        $groupArray[\'month\'] = strftime(\'%m\',$timestamp);
        $groupArray[\'year\'] = strftime(\'%Y\',$timestamp);
        $groupArray[\'year_two_digit\'] = strftime(\'%y\',$timestamp);
        $groupArray[\'day\'] = strftime(\'%d\',$timestamp);
        $groupArray[\'weekday\'] = strftime(\'%A\',$timestamp);
        $groupArray[\'weekday_abbr\'] = strftime(\'%a\',$timestamp);
        $groupArray[\'weekday_idx\'] = strftime(\'%w\',$timestamp);
        $groupArray[\'resources\'] = implode($resourceSeparator,$children);
        $groupArray[\'idx\'] = $groupIdx;

        /* setup GET params */
        $params = array();
        $params[$filterPrefix.\'year\'] = $groupArray[\'year\'];
        if ($mode == \'month\') {
            $params[$filterPrefix.\'month\'] = $groupArray[\'month\'];
        }

        if ($useFurls) {
            $params = implode(\'/\',$params);
            if (!empty($extraParams)) $params .= \'?\'.$extraParams;
            $groupArray[\'url\'] = $modx->makeUrl($target).$params;
        } else {
            $params = http_build_query($params);
            if (!empty($extraParams)) $params .= \'&\'.$extraParams;
            $groupArray[\'url\'] = $modx->makeUrl($target,\'\',$params);
        }
        $output[] = $archivist->getChunk($groupTpl,$groupArray);
        $children = array();
        $childIdx = 0;
        $countGroups++;
        $groupIdx++;
        $currentTime = $activeTime;
    }

    if ($limitItems == 0 || $childIdx < $limitItems) {
        $children[] = $archivist->getChunk($itemTpl,$resourceArray);
    }
    $childIdx++;
    if ($countGroups >= $limitGroups) {
        break;
    }
}

$groupArray = array();
$timestamp = strtotime($currentTime);
$groupArray[\'month_name\'] = strftime(\'%B\',$timestamp);
$groupArray[\'month\'] = strftime(\'%m\',$timestamp);
$groupArray[\'year\'] = strftime(\'%Y\',$timestamp);
$groupArray[\'year_two_digit\'] = strftime(\'%y\',$timestamp);
$groupArray[\'day\'] = strftime(\'%d\',$timestamp);
$groupArray[\'weekday\'] = strftime(\'%A\',$timestamp);
$groupArray[\'weekday_abbr\'] = strftime(\'%a\',$timestamp);
$groupArray[\'weekday_idx\'] = strftime(\'%w\',$timestamp);
$groupArray[\'resources\'] = implode($resourceSeparator,$children);
$groupArray[\'idx\'] = $groupIdx;
/* setup GET params */
$params = array();
$params[$filterPrefix.\'year\'] = $groupArray[\'year\'];
if ($mode == \'month\') {
    $params[$filterPrefix.\'month\'] = $groupArray[\'month\'];
}

if ($useFurls) {
    $params = implode(\'/\',$params);
    if (!empty($extraParams)) $params .= \'?\'.$extraParams;
    $groupArray[\'url\'] = $modx->makeUrl($target).$params;
} else {
    $params = http_build_query($params);
    if (!empty($extraParams)) $params .= \'&\'.$extraParams;
    $groupArray[\'url\'] = $modx->makeUrl($target,\'\',$params);
}
$output[] = $archivist->getChunk($groupTpl,$groupArray);
$children = array();
$childIdx = 0;
$countGroups++;
$groupIdx++;

/* output or set to placeholder */
$output = implode("\\n",$output);
$toPlaceholder = $modx->getOption(\'toPlaceholder\',$scriptProperties,false);
if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder,$output);
    return \'\';
}
return $output;',
      'locked' => 0,
      'properties' => 'a:20:{s:4:"mode";a:7:{s:4:"name";s:4:"mode";s:4:"desc";s:31:"prop_archivistgrouper.mode_desc";s:4:"type";s:4:"list";s:7:"options";a:2:{i:0;a:2:{s:4:"text";s:14:"prop_arc.month";s:5:"value";s:5:"month";}i:1;a:2:{s:4:"text";s:13:"prop_arc.year";s:5:"value";s:4:"year";}}s:5:"value";s:5:"month";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:7:"itemTpl";a:7:{s:4:"name";s:7:"itemTpl";s:4:"desc";s:34:"prop_archivistgrouper.itemtpl_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:9:"itemBrief";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:8:"groupTpl";a:7:{s:4:"name";s:8:"groupTpl";s:4:"desc";s:35:"prop_archivistgrouper.grouptpl_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"target";a:7:{s:4:"name";s:6:"target";s:4:"desc";s:33:"prop_archivistgrouper.target_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:7:"parents";a:7:{s:4:"name";s:7:"parents";s:4:"desc";s:34:"prop_archivistgrouper.parents_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:5:"depth";a:7:{s:4:"name";s:5:"depth";s:4:"desc";s:32:"prop_archivistgrouper.depth_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:2:"10";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"sortBy";a:7:{s:4:"name";s:6:"sortBy";s:4:"desc";s:33:"prop_archivistgrouper.sortby_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:11:"publishedon";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:7:"sortDir";a:7:{s:4:"name";s:7:"sortDir";s:4:"desc";s:34:"prop_archivistgrouper.sortdir_desc";s:4:"type";s:4:"list";s:7:"options";a:2:{i:0;a:2:{s:4:"text";s:18:"prop_arc.ascending";s:5:"value";s:3:"ASC";}i:1;a:2:{s:4:"text";s:19:"prop_arc.descending";s:5:"value";s:4:"DESC";}}s:5:"value";s:4:"DESC";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:11:"limitGroups";a:7:{s:4:"name";s:11:"limitGroups";s:4:"desc";s:38:"prop_archivistgrouper.limitgroups_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";i:12;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:10:"dateFormat";a:7:{s:4:"name";s:10:"dateFormat";s:4:"desc";s:37:"prop_archivistgrouper.dateformat_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:3:"cls";a:7:{s:4:"name";s:3:"cls";s:4:"desc";s:30:"prop_archivistgrouper.cls_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:7:"arc-row";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"altCls";a:7:{s:4:"name";s:6:"altCls";s:4:"desc";s:33:"prop_archivistgrouper.altcls_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:11:"arc-row-alt";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:13:"toPlaceholder";a:7:{s:4:"name";s:13:"toPlaceholder";s:4:"desc";s:40:"prop_archivistgrouper.toplaceholder_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:9:"setLocale";a:7:{s:4:"name";s:9:"setLocale";s:4:"desc";s:36:"prop_archivistgrouper.setlocale_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:1;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:6:"locale";a:7:{s:4:"name";s:6:"locale";s:4:"desc";s:33:"prop_archivistgrouper.locale_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";b:1;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:12:"filterPrefix";a:7:{s:4:"name";s:12:"filterPrefix";s:4:"desc";s:39:"prop_archivistgrouper.filterprefix_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:4:"arc_";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:8:"useFurls";a:7:{s:4:"name";s:8:"useFurls";s:4:"desc";s:30:"prop_archivistgrouper.usefurls";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:1;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:16:"persistGetParams";a:7:{s:4:"name";s:16:"persistGetParams";s:4:"desc";s:43:"prop_archivistgrouper.persistgetparams_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:0;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:11:"extraParams";a:7:{s:4:"name";s:11:"extraParams";s:4:"desc";s:38:"prop_archivistgrouper.extraparams_desc";s:4:"type";s:9:"textfield";s:7:"options";s:0:"";s:5:"value";s:0:"";s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}s:14:"hideContainers";a:7:{s:4:"name";s:14:"hideContainers";s:4:"desc";s:41:"prop_archivistgrouper.hidecontainers_desc";s:4:"type";s:13:"combo-boolean";s:7:"options";s:0:"";s:5:"value";b:1;s:7:"lexicon";s:20:"archivist:properties";s:4:"area";s:0:"";}}',
      'moduleguid' => '',
      'static' => 0,
      'static_file' => '',
      'content' => '/**
 * Archivist
 *
 * Copyright 2010-2011 by Shaun McCormick <shaun@modx.com>
 *
 * This file is part of Archivist, a simple archive navigation system for MODx
 * Revolution.
 *
 * Archivist is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Archivist is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * Archivist; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @package archivist
 */
/*
 * Display an archived result filter list, nested by month
 *
 * @package archivist
 */
$archivist = $modx->getService(\'archivist\',\'Archivist\',$modx->getOption(\'archivist.core_path\',null,$modx->getOption(\'core_path\').\'components/archivist/\').\'model/archivist/\',$scriptProperties);
if (!($archivist instanceof Archivist)) return \'\';

/* setup default properties */
$mode = $modx->getOption(\'mode\',$scriptProperties,\'month\');
$itemTpl = $modx->getOption(\'itemTpl\',$scriptProperties,\'itemBrief\');
$parents = !empty($scriptProperties[\'parents\']) ? $scriptProperties[\'parents\'] : $modx->resource->get(\'id\');
$parents = explode(\',\',$parents);
$target = !empty($scriptProperties[\'target\']) ? $scriptProperties[\'target\'] : $modx->resource->get(\'id\');
$depth = $modx->getOption(\'depth\',$scriptProperties,10);
$where = $modx->getOption(\'where\',$scriptProperties,\'\');
$hideContainers = $modx->getOption(\'hideContainers\',$scriptProperties,true);
$sortBy = $modx->getOption(\'sortBy\',$scriptProperties,\'publishedon\');
$sortDir = $modx->getOption(\'sortDir\',$scriptProperties,\'DESC\');
$dateFormat = !empty($scriptProperties[\'dateFormat\']) ? $scriptProperties[\'dateFormat\'] : \'\';
$limitGroups = $modx->getOption(\'limitGroups\',$scriptProperties,12);
$limitItems = $modx->getOption(\'limitItems\',$scriptProperties,0);
$resourceSeparator = $modx->getOption(\'resourceSeparator\',$scriptProperties,"\\n");
$groupSeparator = $modx->getOption(\'monthSeparator\',$scriptProperties,"\\n");

$filterPrefix = $modx->getOption(\'filterPrefix\',$scriptProperties,\'arc_\');
$useFurls = $modx->getOption(\'useFurls\',$scriptProperties,true);
$persistGetParams = $modx->getOption(\'persistGetParams\',$scriptProperties,false);
/* handle existing GET params */
$extraParams = $modx->getOption(\'extraParams\',$scriptProperties,array());
$extraParams = $archivist->mergeGetParams($extraParams,$persistGetParams,$filterPrefix);

$cls = $modx->getOption(\'cls\',$scriptProperties,\'arc-resource-row\');
$altCls = $modx->getOption(\'altCls\',$scriptProperties,\'arc-resource-row-alt\');

/* set locale for date processing */
if ($modx->getOption(\'setLocale\',$scriptProperties,true)) {
    $cultureKey = $modx->getOption(\'cultureKey\',null,\'en\');
    $locale = !empty($scriptProperties[\'locale\']) ? $scriptProperties[\'locale\'] : $cultureKey;
    if (!empty($locale)) {
        setlocale(LC_ALL,$locale);
    }
}

/* find children of parents */
$children = array();
foreach ($parents as $parent) {
    $pchildren = $modx->getChildIds($parent, $depth);
    if (!empty($pchildren)) $children = array_merge($children, $pchildren);
}
if (!empty($children)) $parents = array_merge($parents, $children);

/* build query */
$c = $modx->newQuery(\'modResource\');
$c->where(array(
    \'parent:IN\' => $parents,
    \'published\' => true,
    \'deleted\' => false,
));
if ($hideContainers) {
    $c->where(array(
        \'isfolder\' => false,
    ));
}
if (!empty($where)) {
    $where = $modx->fromJSON($where);
    $c->where($where);
}
$c->sortby(\'FROM_UNIXTIME(\'.$sortBy.\',"%Y") \'.$sortDir.\', FROM_UNIXTIME(\'.$sortBy.\',"%m") \'.$sortDir.\', FROM_UNIXTIME(\'.$sortBy.\',"%d") \'.$sortDir,\'\');
$resources = $modx->getIterator(\'modResource\',$c);

/* get grouping constraint */
switch ($mode) {
    case \'year\':
        $groupConstraint = \'%Y-01-01\';
        $groupDefaultTpl = \'yearContainer\';
        break;
    case \'month\':
    default:
        $groupConstraint = \'%Y-%m-01\';
        $groupDefaultTpl = \'monthContainer\';
        break;
}
$groupTpl = !empty($scriptProperties[\'groupTpl\']) ? $scriptProperties[\'groupTpl\'] : $groupDefaultTpl;

/* iterate over resources */
$output = array();
$children = array();
$resourceArray = array();
$groupIdx = 0;
$childIdx = 0;
$countGroups = 0;
foreach ($resources as $resource) {
    $resourceArray = $resource->toArray();
    $date = $resource->get($sortBy);
    $dateObj = strtotime($date);
    $activeTime = strftime($groupConstraint,$dateObj);
    if (!isset($currentTime)) {
        $currentTime = $activeTime;
    }

    $resourceArray[\'date\'] = strftime($dateFormat,$dateObj);
    $resourceArray[\'month_name_abbr\'] = strftime(\'%h\',$dateObj);
    $resourceArray[\'month_name\'] = strftime(\'%B\',$dateObj);
    $resourceArray[\'month\'] = strftime(\'%m\',$dateObj);
    $resourceArray[\'year\'] = strftime(\'%Y\',$dateObj);
    $resourceArray[\'year_two_digit\'] = strftime(\'%y\',$dateObj);
    $resourceArray[\'day\'] = strftime(\'%d\',$dateObj);
    $resourceArray[\'weekday\'] = strftime(\'%A\',$dateObj);
    $resourceArray[\'weekday_abbr\'] = strftime(\'%a\',$dateObj);
    $resourceArray[\'weekday_idx\'] = strftime(\'%w\',$dateObj);

    /* css classes */
    $resourceArray[\'cls\'] = $cls;
    if ($childIdx % 2) { $resourceArray[\'cls\'] .= \' \'.$altCls; }
    $resourceArray[\'idx\'] = $childIdx;

    if ($currentTime != $activeTime) {
        $groupArray = array();
        $timestamp = strtotime($currentTime);
        $groupArray[\'month_name\'] = strftime(\'%B\',$timestamp);
        $groupArray[\'month\'] = strftime(\'%m\',$timestamp);
        $groupArray[\'year\'] = strftime(\'%Y\',$timestamp);
        $groupArray[\'year_two_digit\'] = strftime(\'%y\',$timestamp);
        $groupArray[\'day\'] = strftime(\'%d\',$timestamp);
        $groupArray[\'weekday\'] = strftime(\'%A\',$timestamp);
        $groupArray[\'weekday_abbr\'] = strftime(\'%a\',$timestamp);
        $groupArray[\'weekday_idx\'] = strftime(\'%w\',$timestamp);
        $groupArray[\'resources\'] = implode($resourceSeparator,$children);
        $groupArray[\'idx\'] = $groupIdx;

        /* setup GET params */
        $params = array();
        $params[$filterPrefix.\'year\'] = $groupArray[\'year\'];
        if ($mode == \'month\') {
            $params[$filterPrefix.\'month\'] = $groupArray[\'month\'];
        }

        if ($useFurls) {
            $params = implode(\'/\',$params);
            if (!empty($extraParams)) $params .= \'?\'.$extraParams;
            $groupArray[\'url\'] = $modx->makeUrl($target).$params;
        } else {
            $params = http_build_query($params);
            if (!empty($extraParams)) $params .= \'&\'.$extraParams;
            $groupArray[\'url\'] = $modx->makeUrl($target,\'\',$params);
        }
        $output[] = $archivist->getChunk($groupTpl,$groupArray);
        $children = array();
        $childIdx = 0;
        $countGroups++;
        $groupIdx++;
        $currentTime = $activeTime;
    }

    if ($limitItems == 0 || $childIdx < $limitItems) {
        $children[] = $archivist->getChunk($itemTpl,$resourceArray);
    }
    $childIdx++;
    if ($countGroups >= $limitGroups) {
        break;
    }
}

$groupArray = array();
$timestamp = strtotime($currentTime);
$groupArray[\'month_name\'] = strftime(\'%B\',$timestamp);
$groupArray[\'month\'] = strftime(\'%m\',$timestamp);
$groupArray[\'year\'] = strftime(\'%Y\',$timestamp);
$groupArray[\'year_two_digit\'] = strftime(\'%y\',$timestamp);
$groupArray[\'day\'] = strftime(\'%d\',$timestamp);
$groupArray[\'weekday\'] = strftime(\'%A\',$timestamp);
$groupArray[\'weekday_abbr\'] = strftime(\'%a\',$timestamp);
$groupArray[\'weekday_idx\'] = strftime(\'%w\',$timestamp);
$groupArray[\'resources\'] = implode($resourceSeparator,$children);
$groupArray[\'idx\'] = $groupIdx;
/* setup GET params */
$params = array();
$params[$filterPrefix.\'year\'] = $groupArray[\'year\'];
if ($mode == \'month\') {
    $params[$filterPrefix.\'month\'] = $groupArray[\'month\'];
}

if ($useFurls) {
    $params = implode(\'/\',$params);
    if (!empty($extraParams)) $params .= \'?\'.$extraParams;
    $groupArray[\'url\'] = $modx->makeUrl($target).$params;
} else {
    $params = http_build_query($params);
    if (!empty($extraParams)) $params .= \'&\'.$extraParams;
    $groupArray[\'url\'] = $modx->makeUrl($target,\'\',$params);
}
$output[] = $archivist->getChunk($groupTpl,$groupArray);
$children = array();
$childIdx = 0;
$countGroups++;
$groupIdx++;

/* output or set to placeholder */
$output = implode("\\n",$output);
$toPlaceholder = $modx->getOption(\'toPlaceholder\',$scriptProperties,false);
if (!empty($toPlaceholder)) {
    $modx->setPlaceholder($toPlaceholder,$output);
    return \'\';
}
return $output;',
    ),
  ),
);