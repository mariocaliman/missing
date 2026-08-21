<?php
include('include/autoloader.php');
$smarty->assign('is_states_map',1);

$states = array();
$total_cases = 0;
$max_cases = 0;

if ($mysqli instanceof mysqli && !$mysqli->connect_errno) {
    $sql = "SELECT c.id, c.category, COUNT(n.id) AS cases
            FROM categories c
            LEFT JOIN news n ON n.category_id=c.id AND n.published='1'
            WHERE c.category REGEXP '^[A-Z]{2} - '
            GROUP BY c.id, c.category
            ORDER BY c.category ASC";
    $query = $mysqli->query($sql);
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $category = trim((string) $row['category']);
            if (!preg_match('/^([A-Z]{2})\s*-\s*(.+)$/', $category, $matches)) {
                continue;
            }
            $code = strtoupper(trim($matches[1]));
            $name = trim($matches[2]);
            $cases = intval($row['cases']);
            $total_cases += $cases;
            if ($cases > $max_cases) {
                $max_cases = $cases;
            }

            $url = $general_setting['siteurl'] . '/missing-persons/' . slugit($name) . '/';
            $states[] = array(
                'id' => intval($row['id']),
                'code' => $code,
                'name' => $name,
                'cases' => $cases,
                'url' => str_replace(':/','://',str_replace('//','/',$url))
            );
        }
    }
}

$states_by_cases = $states;
usort($states_by_cases, function ($a, $b) {
    if ($a['cases'] === $b['cases']) {
        return strcmp($a['name'], $b['name']);
    }
    return $b['cases'] - $a['cases'];
});

$active_states = 0;
foreach ($states as $state) {
    if ($state['cases'] > 0) {
        $active_states++;
    }
}

$smarty->assign('states_map_data', $states);
$smarty->assign('states_map_json', json_encode($states));
$smarty->assign('states_top', array_slice($states_by_cases, 0, 10));
$smarty->assign('states_total_cases', $total_cases);
$smarty->assign('states_active_count', $active_states);
$smarty->assign('states_max_cases', $max_cases);

$smarty->assign('seo_title', 'US Cases Map');
$smarty->assign('seo_keywords', 'missing cases map, united states, missing persons by state');
$smarty->assign('seo_description', 'Interactive map with case totals by US state, updated from published cases on the site.');

$smarty->display('states-map.html');
?>
