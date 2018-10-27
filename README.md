# PHP Scrollable Selection

Allows a user to select an option from a scrollable list, returns key selected from original array input

```PHP
$list = array();

for ($i=1; $i < 50; $i++) {
    $list[] = "$i " . str_repeat('-', 20);
}

$ScrollableSelection = new ScrollableSelection(
    [
        'list'     => $list,
        'maxItems' => 10,
        'loops'    => false,
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
```