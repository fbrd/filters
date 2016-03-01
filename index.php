<?php

$dbHost = 'localhost';
$dbUser = 'fbrd';
$dbPass = '*********';
$dbName = 'filters';
$goodsTable = 'goods';
$propsTable = 'props';
$groupsTable = 'groups';

$mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
$mysqli->query('SET NAMES "utf8"');
$mysqli->query('SET collation_connection = "utf8_unicode_ci"');

$data = array();
$query = 'SELECT p.`id`, p.`name` as pname, g.`id` as gid, g.`name` as gname
FROM ' . $propsTable . ' as p
INNER JOIN ' . $groupsTable . ' as g ON p.group_id = g.id
ORDER by g.sort, p.sort';
$selectGC = array();
if ($result = $mysqli->query($query)) {
    while ($row = $result->fetch_assoc()) {
        $groupKey = 'group_' . $row['gid'];
        if (empty($data['groups'][$groupKey])) {
            $data['groups'][$groupKey] = array(
                'id' => $row['gid'],
                'name' => $row['gname'],
                'props' => array()
            );
            $selectGC[] = 'GROUP_CONCAT(DISTINCT group_' . $row['gid'] . ') as group_' . $row['gid'];
        }
        $data['groups'][$groupKey]['props'][$row['id']] = array(
            'id' => $row['id'],
            'name' => $row['pname'],
            'cheched' => '',
            'disabled' => 'disabled'
        );
    }
    $result->free();
}
$selectC = implode(', ', $selectGC);
$where = '';
if (!empty($_GET['p'])) {
    $whereC = array();
    foreach ($_GET['p'] as $groupId => $group) {
        $groupKey = 'group_' . (int)$groupId;
        $props = array();
        foreach ($group as $propId) {
            $propId = (int)$propId;
            $data['groups'][$groupKey]['props'][$propId]['checked'] = 'checked';
            $props[] = $propId;
        }
        $whereC[] = $groupKey . ' IN(' . implode(', ', $props) . ')';
    }
    $where .= ' WHERE ' . implode(' AND ', $whereC);
}

$query = 'SELECT ' . $selectC . ' FROM ' . $goodsTable . $where;
if ($result = $mysqli->query($query)) {
    while ($dProps = $result->fetch_assoc()) {
        foreach ($dProps as $groupKey => $jprops) {
            $props = explode(',', $jprops);
            if (!empty($props)) {
                foreach ($props as $prop) {
                    $data['groups'][$groupKey]['props'][$prop]['disabled'] = '';
                }
            }
        }
    }
    $result->free();
}

$query = 'SELECT * FROM ' . $goodsTable . $where;
if ($result = $mysqli->query($query)) {
    while ($good = $result->fetch_assoc()) {
        $data['goods'][] = $good;
    }
    $result->free();
}

?><!DOCTYPE html>
<html>
<head>
    <title>Параметрический поиск</title>
</head>
<body><h1>Параметрический поиск</h1>
<form><?
    foreach ($data['groups'] as $groupId => $group) {
        ?>
        <fieldset>
        <legend><?= $group['name'] ?></legend>
        <?
        foreach ($group['props'] as $prop) {
            ?><input type="checkbox" <?= $prop['checked'] ?> <?= $prop['disabled'] ?> name="p[<?= $group['id'] ?>][]"
                     value="<?= $prop['id'] ?>"><?= $prop['name'] ?>
        <?
        }
        ?></fieldset><?
    }
    ?>
    <input type="submit" value="Искать"/>
</form><?

if (!empty($data['goods'])) {
    ?>
    <ul><?
    foreach ($data['goods'] as $good) {
        ?>
        <li><strong><?= $good['name'] ?></strong>, <?= $good['price'] ?> руб.
        <div><?
            foreach ($data['groups'] as $groupId => $group) {
                if (!empty($good[$groupId])) {
                    ?><?=
                    $data['groups'][$groupId]['props'][$good[$groupId]]['name']?> <?
                }
            }
            ?></div></li><?
    }
    ?></ul><?
}
?></body></html>
