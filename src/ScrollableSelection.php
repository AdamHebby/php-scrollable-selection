<?php

/**
 * Displays a scrollable list to the user in the command line. Allows the user to
 * select an option from a specified list and scroll using arrow keys.
 *
 * @category Library
 * @package  ScrollableSelection
 * @author   AdamHebby <adamhebden56@gmail.com>
 * @license  Apache-2.0 https://www.apache.org/licenses/LICENSE-2.0
 * @link     https://github.com/AdamHebby/php-scrollable-selection
 */
class ScrollableSelection
{
    protected $colors;
    protected $currentKey;
    protected $cursor;
    protected $extraLength;
    protected $lastLines = 0;
    protected $list;
    protected $listCount;
    protected $listToDisplay;
    protected $longestString;
    protected $longestKey;
    protected $loops;
    protected $maxItems;
    protected $termWidth;
    protected $visibleKeys;

    protected $colorMapCodes = [
        'black'         => 30,
        'red'           => 31,
        'green'         => 32,
        'yellow'        => 33,
        'blue'          => 34,
        'magenta'       => 35,
        'cyan'          => 36,
        'light_gray'    => 37,
        'dark_gray'     => 90,
        'light_red'     => 91,
        'light_green'   => 92,
        'light_yellow'  => 93,
        'light_blue'    => 94,
        'light_magenta' => 95,
        'light_cyan'    => 96,
        'white'         => 97
    ];

    /**
     * ScrollableSelection Constructor
     *
     * @param array $config Array of elements for configuration purposes. See GitHub
     *                      for documentation
     *
     * @return void
     */
    public function __construct(array $config)
    {
        $this->setOptions($config);
    }

    /**
     * Set the options for ScrollableSelection
     *
     * @param array $config Array of elements for configuration purposes. See GitHub
     *                      for documentation
     *
     * @return void
     */
    public function setOptions(array $config)
    {
        // Load default config JSON file
        $defaultConf = json_decode(
            file_get_contents('config.json'),
            true
        );

        // Merge default config with passed config and assign vars
        $newConf          = array_merge($defaultConf, $config);
        $this->list       = $newConf['list'];
        $this->maxItems   = $newConf['maxItems'];
        $this->loops      = $newConf['loops'];
        $this->currentKey = $newConf['startKey'];
        $this->cursor     = $newConf['cursor'];
        $this->colors     = $newConf['colors'];
        $this->listCount  = count($this->list);

        // Store keys for later use in array
        $listItemKey = 0;
        foreach ($this->list as $key => $value) {
            $this->list[$key] = [
                'originalKey' => $key,
                'value'       => $value,
                'newListKey'  => $listItemKey
            ];
            $listItemKey++;
        }

        // Set max item count - cannot be higher than list count
        if ($this->maxItems > $this->listCount) {
            $this->maxItems = $this->listCount;
        }

        // Get longest string length
        $this->longestString = max(
            array_map(
                function ($arr) {
                    return strlen($arr['value']);
                },
                $this->list
            )
        );

        // Get longest key length
        $this->longestKey = max(
            array_map(
                function ($arr) {
                    return strlen($arr['newListKey']);
                },
                $this->list
            )
        );

        $this->updateTerminalDimensions();
    }

    /**
     * Displays the scrollable list specified to the user and asks for a selection
     *
     * @return int|bool
     */
    public function displayList()
    {
        $this->setVisibleList();
        $this->echoList();
        while (@ob_end_flush()) {
        }

        // Get input from user, JS returns key names
        $proc = popen("node getInput.js", 'r');
        while (!feof($proc)) {
            $inputKey = trim(fread($proc, 4096));
            @flush();

            if ($inputKey === 'return') {
                // User has selected option
                pclose($proc);
                return $this->list[$this->currentKey]['originalKey'];
            } elseif ($inputKey === 'down') {
                if ($this->increaseKey()) {
                    $this->removePreviousLines();
                    $this->echoList();
                }
            } elseif ($inputKey === 'up') {
                if ($this->decreaseKey()) {
                    $this->removePreviousLines();
                    $this->echoList();
                }
            }
        }
        // User has Quit with CTRL+C || ESC
        return -1;
    }

    /**
     * Removes previous lines from the terminal that echoList outputted
     *
     * @return void
     */
    protected function removePreviousLines()
    {
        for ($i = 0; $i < $this->lastLines; $i++) {
            echo "\r\033[K\033[1A\r\033[K\r";
        }

        $this->updateTerminalDimensions();
    }

    /**
     * Echos out list to terminal, capping at maxItems
     *
     * @return void
     */
    protected function echoList()
    {
        $this->setVisibleList();

        foreach ($this->listToDisplay as $item) {
            $itemKey       = $item['newListKey'];
            $itemVal       = $item['value'];
            $active        = ($itemKey == $this->currentKey);
            $displayKey    = ($itemKey + 1) . ")";
            $keyPadding    = str_repeat(
                ' ',
                ($this->longestKey + 1 - strlen($displayKey))
            );

            $cursor = ($active)
                ? $this->cursor
                : str_repeat(
                    ' ',
                    strlen($this->cursor)
                );

            $displayString     = " $cursor {$keyPadding}$displayKey $itemVal \n";
            $this->extraLength = strlen($displayString) - strlen($itemVal);

            $displayString     = $this->returnColoredString(
                $displayString,
                ($active) ? $this->colors['active'] : $this->colors['inactive']
            );

            echo $displayString;
        }
    }

    /**
     * Sets a list that the user should be allowed to see
     *
     * @return void
     */
    protected function setVisibleList()
    {
        if (!$this->loops
            && $this->currentKey >= (($this->listCount) - $this->maxItems)
        ) {
            $this->listToDisplay = array_slice(
                $this->list,
                ($this->listCount - $this->maxItems),
                $this->maxItems,
                false
            );
        } else {
            $this->listToDisplay = array_slice(
                $this->list,
                $this->currentKey,
                $this->maxItems,
                false
            );
        }

        // If at end of list & looping is enabled, add the start of list to the end
        if (count($this->listToDisplay) < $this->maxItems
            && $this->maxItems <= $this->listCount
            && $this->loops
        ) {
            $tacOnEnd = array_slice(
                $this->list,
                0,
                $this->maxItems - count($this->listToDisplay),
                false
            );
            $this->listToDisplay = array_merge($this->listToDisplay, $tacOnEnd);
        }
        $this->setVisibleKeys();

        // Count lines visible to user to clear later
        $lineCount = 0;
        foreach ($this->visibleKeys as $value) {
            $lineCount += count(
                str_split($this->list[$value]['value'], $this->termWidth)
            );
        }
        $this->lastLines = $lineCount;
    }

    /**
     * Works in conjunction with setVisibibleList; gets the keys from that list
     *
     * @return void
     */
    protected function setVisibleKeys()
    {
        $this->visibleKeys = array_map(
            function ($arr) {
                return $arr['newListKey'];
            },
            $this->listToDisplay
        );
    }

    /**
     * Increase the Currently visible Key, handles loops
     *
     * @return boolean
     */
    protected function increaseKey() : bool
    {
        if ($this->currentKey == ($this->listCount - 1)) {
            if (!$this->loops) {
                return false;
            }
            $this->currentKey = 0;
        } else {
            $this->currentKey++;
        }
        return true;
    }

    /**
     * Decrease the Currently visible Key, handles loops
     *
     * @return boolean
     */
    protected function decreaseKey() : bool
    {
        if ($this->currentKey == 0) {
            if (!$this->loops) {
                return false;
            }
            $this->currentKey = ($this->listCount - 1);
        } else {
            $this->currentKey--;
        }
        return true;
    }

    /**
     * Return the passed string back wrapped in the specified color codes
     *
     * @param string $string String to modify with color codes
     * @param string $color  Color to set the string to using $this->colorMapCodes
     *
     * @return string
     */
    protected function returnColoredString(
        string $string,
        string $color = 'white'
    ) : string {
        $newString = $string;
        if (isset($this->colorMapCodes[$color])) {
            $colorMap  = $this->colorMapCodes[$color];
            $newString = "\e[{$colorMap}m" . $newString . "\e[39m";
        }
        return $newString;
    }

    /**
     * Updates the class var $this->termWidth with the current terminal width
     *
     * @return void
     */
    protected function updateTerminalDimensions()
    {
        $this->termWidth  = exec('tput cols');
    }
}
