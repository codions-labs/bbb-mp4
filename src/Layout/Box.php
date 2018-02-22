<?php

namespace ProfIT\Bbb\Layout;

use Runn\Core\Std;

/**
 * Class Box
 * @package ProfIT\Bbb\Layout
 */

class Box
{
    const COLOR_BLACK        = '#000000';
    const COLOR_GRAY         = '#CCCCCC';
    const COLOR_WHITE        = '#FFFFFF';
    const FONT_PATH          = __DIR__ . '/../../resources/fonts/arial.ttf';
    const DEFAULT_FONT_COLOR = '#3f3f41';
    const DEFAULT_FONT_SIZE  = 9;

    /** @var int coordinates and sizes */
    public $x;
    public $y;
    public $w;
    public $h;
    
    public $yCorrection;

    /** @var int padding */
    public $pad = 0;

    /** @var array content offset */
    public $offset = [
        'top'    => 0,
        'right'  => 0,
        'bottom' => 0,
        'left'   => 0
    ];

    /** @var Box|Window */
    public $parent;
    /** @var array */
    public $children = [];

    /** @var bool */
    public $hidden;

    /** default styles */
    protected $fontColor = self::DEFAULT_FONT_COLOR;
    public $fontSize  = self::DEFAULT_FONT_SIZE;

    public function __construct(Std $props = null)
    {
        foreach ($props as $key => $val) {
            if (null !== $val) {
                $this->$key = $val;
            }
        }
    }

    public function render($canvas)
    {
        if ($this->hidden) {
            return;
        }

        $transparency = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparency);
        imagesavealpha($canvas, true);

        foreach ($this->children as $child) {
            /** @var Box $child */
            $child->render($canvas);
        }
    }
    
    public function renderText($canvas, string $text, bool $bold = false)
    {
        $textHeight = $this->fontSize;
        $offsetY = floor(($this->h - $textHeight) / 2);

        $x = $this->x + $this->pad;
        $yCorrection = $this->parent->yCorrection ?? 0;
        $y = $this->y + $yCorrection + $this->fontSize + $offsetY;

        imagettftext(
            $canvas,
            $textHeight,
            0,
            $x,
            $y,
            self::color($canvas, $this->fontColor),
            static::FONT_PATH,
            $text
        );
        if (true === $bold) {
            imagettftext(
                $canvas,
                $textHeight,
                0,
                $x+1,
                $y+1,
                self::color($canvas, $this->fontColor),
                static::FONT_PATH,
                $text
            );
        }
    }

    public function addChild(Box $child)
    {
        $child->parent = $this;
        $this->children[] = $child;
    }

    public function addOffset($side, $value)
    {
        $this->offset[$side] += $value;
    }

    public static function color($canvas, string $value)
    {
        list ($r, $g, $b) = array_map('hexdec', str_split(trim($value, "#"), 2));

        return imagecolorallocate($canvas, $r, $g, $b);
    }
}