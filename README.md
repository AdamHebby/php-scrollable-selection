# PHP Scrollable Selection

Allows a user to select an option from a scrollable list, returns key selected from original array input


### Loop Mode
![](https://media.giphy.com/media/5gWGJye0BUHC47j5jU/giphy.gif)

### Single List Mode
![](https://media.giphy.com/media/U7MywUAkiPBzswSD8N/giphy.gif)

### Custom Colors & Cursor Text
![](https://image.ibb.co/bTCgtA/php-scrollable-selection-colors.png)

#### Available Colors

![](https://image.ibb.co/mAaUfq/php-scrollable-selection-available-colors.png)

### Example
```PHP
$list = array();

for ($i=1; $i < 50; $i++) {
    $list[] = "$i " . str_repeat('-', 20);
}

$ScrollableSelection = new \ScrollableSelection(
    [
        'list'     => $list,
        'maxItems' => 10,
        'loops'    => true,
        'startKey' => 0,
        'cursor'   => '>',
        'colors'   => [
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