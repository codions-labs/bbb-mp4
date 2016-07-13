<?php

namespace ProfIT\Bbb;

use ProfIT\Bbb\Layout\Box;
use ProfIT\Bbb\Layout\StyleSheet;
use ProfIT\Bbb\Layout\Window;

class Layout
{
    protected $data;
    protected $styles;

    protected $width;
    protected $height;
    protected $pad;

    protected $windows = [];
    protected $markedWindows = [];
    protected $unfilledWindows = [];

    public function __construct(string $filename, string $name, StyleSheet $styles)
    {
        /** @var \SimpleXMLElement $xml */
        $xml = @simplexml_load_file($filename);
        if (false === $xml) {
            throw new \Exception('Layout file can not be loaded: ' . $filename);
        }

        $data = @$xml->xpath('//layouts/layout[@name="bbb.layout.name.' . $name . '"]');

        if (false === $data) {
            throw new \Exception('Invalid layout');
        }

        $this->styles = $styles;
        $this->data = $data[0];
    }

    public function setDimensions(int $width, int $height, int $pad)
    {
        $this->width = $width;
        $this->height = $height;
        $this->pad = $pad;

        $this->makeWindows();
    }

    protected function makeWindows()
    {
        $windows = [];

        foreach ($this->data->window as $window) {
            /** @var \SimpleXMLElement $window */
            $attributes = $window->attributes();
            if (
                empty($attributes->width)
                ||
                false === in_array((string)$attributes->name, array_keys(Window::TITLES))
            ) {
                continue;
            }

            $windows[] = new Window($this->styles, [
                'name'   => (string)$attributes->name,
                'x'      => (int) round(((float)$attributes->x) * $this->width),
                'y'      => (int) round(((float)$attributes->y) * $this->height),
                'w'      => (int) round(((float)$attributes->width) * $this->width),
                'h'      => (int) round(((float)$attributes->height) * $this->height),
                'hidden' => $attributes->hidden == true,
                'pad'    => $this->pad,
            ]);
        }

        $this->windows = $windows;
    }

    protected function createWindowWithParams(array $params) {
        return new Window($this->styles, [
            'x'      => (int) round(((float)$params['x']) * $this->width),
            'y'      => (int) round(((float)$params['y']) * $this->height),
            'w'      => (int) round(((float)$params['w']) * $this->width),
            'h'      => (int) round(((float)$params['h']) * $this->height),
            'pad'    => $this->pad,
        ]);
    }
    
    public function addCustomWindow(array $params)
    {
        $customWindow = $this->createWindowWithParams($params);
        $customWindow->name = $params['name'];
        $this->windows[] = $customWindow;
    }

    public function addListWindow(array $params, array $list)
    {
        $listWindow = $this->createWindowWithParams($params);
        foreach ($list as $text) {
            $listWindow->createUserListRow($text);
        }
        $this->windows[] = $listWindow;
    }

    public function addChatListWindow(array $params, array $list, EventsFile $events)
    {
        $meetingName = $events->findMeetingName();
        $listWindow = $this->createWindowWithParams($params);
        $listWindow->createChatListCaption($meetingName);
        foreach ($list as $item) {
            $listWindow->createChatMessageCaption($item['user'], $events->getAbsoluteTime($item['time'])->format('H:i'));
            $listWindow->createChatMessage($item['message']);
        }
        $this->windows[] = $listWindow;
    }

    public function getWindows()
    {
        if (count($this->markedWindows) > 0) {
            return array_filter($this->windows, function (Window $w) {
                return in_array($w->name, $this->markedWindows);
            });
        } else {
            return $this->windows;
        }
    }

    public function setMarkedWindows(array $marked) {
        $this->markedWindows = $marked;
    }

    public function setUnfilledWindows(array $unfilled) {
        $this->unfilledWindows = $unfilled;
    }

    public function generatePng($dstFileName, bool $fillContent, bool $drawTitle = true, bool $bgTransparent = false)
    {
        $canvas = imagecreatetruecolor($this->width, $this->height);

        if (true === $bgTransparent) {
            $transparency = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
            imagefill($canvas, 0, 0, $transparency);
            imagesavealpha($canvas, true);
        } else {
            imagefill($canvas, 0, 0, Box::color($canvas, $this->styles->rules['Application']['backgroundColor']));
        }

        foreach ($this->windows as $window) {
            /** @var Window $window */
            if (count($this->markedWindows) > 0 && false === in_array($window->name, $this->markedWindows)) {
                continue;
            }

            if (true === $drawTitle) {
                $window->createTitleBar();
            }

            if (true === $fillContent && false === in_array($window->name, $this->unfilledWindows)) {
                $window->fillContentBackground();
            }

            $window->render($canvas);
        }

        imagepng($canvas, $dstFileName);
    }

}