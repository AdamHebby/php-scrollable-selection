<?php
require_once __DIR__ . '/src/ScrollableSelection.php';

$list = array();

for ($i=1; $i < 15; $i++) {
    $list[] = "$i " . str_repeat('-', 20);
}

$ScrollableSelection = new ScrollableSelection(
    [
        'list'     => $list,
        'maxItems' => 10,
        'loops'    => true,
        'startKey' => 0,
        'cursor'   => '>',
        'colors' => [
            'active'   => 'white',
            'inactive' => 'dark_gray'
        ]
    ]
);

$key = $ScrollableSelection->displayList();

if (!is_numeric($key)) {
    echo "User quit selection \n\n";
} else {
    echo "\nUser selected {$list[$key]} \n\n";
}
