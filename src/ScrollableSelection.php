<?php

class ScrollableSelection
{
    private $colors;
    private $currentKey;
    private $cursor;
    private $extraLength;
    private $lastLines = 0;
    private $list;
    private $listCount;
    private $longestString;
    private $longestKey;
    private $loops;
    private $maxItems;
    private $visibleKeys;

    private $colorMapCodes = [
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

    public function __construct(array $config)
    {
        $this->setOptions($config);
    }
    public function setOptions(array $config)
    {
        $this->list       = (isset($config['list']))     ? $config['list']     : array();
        $this->maxItems   = (isset($config['maxItems'])) ? $config['maxItems'] : 5;
        $this->loops      = (isset($config['loops']))    ? $config['loops']    : true;
        $this->currentKey = (isset($config['startKey'])) ? $config['startKey'] : 0;
        $this->cursor     = (isset($config['cursor']))   ? $config['cursor']   : '>';
        $this->colors = array(
            'active'   => (isset($config['colors']['active']))   ? $config['colors']['active']   : 'white',
            'inactive' => (isset($config['colors']['inactive'])) ? $config['colors']['inactive'] : 'dark_gray'
        );

        $this->listCount = count($this->list);

        $listItemKey = 0;
        foreach ($this->list as $key => $value) {
            $this->list[$key] = [
                'originalKey' => $key,
                'value'       => $value,
                'newListKey'  => $listItemKey
            ];
            $listItemKey++;
        }

        $this->list = array_values($this->list);

        if ($this->maxItems > $this->listCount) {
            $this->maxItems = $this->listCount;
        }

        $this->longestString = max(array_map(function ($arr) {
            return strlen($arr['value']);
        }, $this->list));

        $this->longestKey = max(array_map(function ($arr) {
            return strlen($arr['newListKey']);
        }, $this->list));

        $this->updateTerminalDimensions();
    }
    public function displayList()
    {
        $this->setVisibleList();
        $this->echoList();
        while (@ob_end_flush());

        $proc = popen("node getInput.js", 'r');
        while (!feof($proc))
        {
            $inputKey = trim(fread($proc, 4096));
            @flush();

            if ($inputKey === 'return') {
                pclose($proc);
                return $this->list[$this->currentKey]['originalKey'];
            } else if ($inputKey === 'down') {
                if ($this->increaseKey()) {
                    $this->removePreviousLines();
                    $this->echoList();
                }
            } else if ($inputKey === 'up') {
                if ($this->decreaseKey()) {
                    $this->removePreviousLines();
                    $this->echoList();
                }
            }
        }
    }
    private function removePreviousLines()
    {
        for($i = 0; $i < $this->lastLines; $i++) {
            echo "\r\033[K\033[1A\r\033[K\r";
        }

        $this->updateTerminalDimensions();
    }
    private function echoList()
    {
        $this->setVisibleList();

        foreach ($this->listToDisplay as $item) {
            $itemKey           = $item['newListKey'];
            $itemVal           = $item['value'];
            $active            = ($itemKey == $this->currentKey);
            $displayKey        = ($itemKey + 1) . ")";
            $keyPadding        = str_repeat(' ', $this->longestKey + 1 - strlen($displayKey));
            $cursor            = ($active) ? $this->cursor : str_repeat(' ', strlen($this->cursor));
            $displayString     = " $cursor {$keyPadding}$displayKey $itemVal \n";
            $this->extraLength = strlen($displayString) - strlen($itemVal);

            $displayString = $this->returnColoredString(
                $displayString,
                ($active) ? $this->colors['active'] : $this->colors['inactive']
            );

            echo $displayString;
        }
    }
    private function setVisibleList()
    {
        if (!$this->loops && $this->currentKey >= (($this->listCount) - $this->maxItems)) {
            $this->listToDisplay = array_slice($this->list, (($this->listCount) - $this->maxItems), $this->maxItems, false);
        } else {
            $this->listToDisplay = array_slice($this->list, $this->currentKey, $this->maxItems, false);
        }

        if (
            count($this->listToDisplay) < $this->maxItems && 
            $this->maxItems <= $this->listCount &&
            $this->loops
        ) {
            $tacOnEnd = array_slice($this->list, 0, $this->maxItems - count($this->listToDisplay), false);
            $this->listToDisplay = array_merge($this->listToDisplay, $tacOnEnd);
        }
        $this->setVisibleKeys();

        $lineCount = 0;
        foreach ($this->visibleKeys as $value) {
            $lineCount += count(str_split($this->list[$value]['value'], $this->termWidth));
        }
        $this->lastLines = $lineCount;
    }
    private function setVisibleKeys()
    {
        $this->visibleKeys = array_map(function($arr) {
            return $arr['newListKey'];
        }, $this->listToDisplay);
    }
    private function increaseKey()
    {
        if ($this->currentKey == ($this->listCount - 1)) {
            if (!$this->loops) return false;
            $this->currentKey = 0;
        } else {
            $this->currentKey++;
        }
        return true;
    }
    private function decreaseKey()
    {
        if ($this->currentKey == 0) {
            if (!$this->loops) return false;
            $this->currentKey = ($this->listCount - 1);
        } else {
            $this->currentKey--;
        }
        return true;
    }
    private function returnColoredString(string $string, string $color = 'white')
    {
        $newString = $string;
        if (isset($this->colorMapCodes[$color])) {
            $colorMap  = $this->colorMapCodes[$color];
            $newString = "\e[{$colorMap}m" . $newString . "\e[39m";
        }
        return $newString;
    }
    private function updateTerminalDimensions()
    {
        $this->termWidth  = exec('tput cols');
    }
}