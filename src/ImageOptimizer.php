<?php

namespace App;

use Imagine\Gd\Imagine;
use Imagine\Image\Box;

class ImageOptimizer
{
    private const MAX_WIDTH = 200;
    private const MAX_HEIGHT = 150;

    private Imagine $imagine;

    public function __construct()
    {
        $this->imagine = new Imagine();
    }

    public function resize(string $imagePath): void
    {
        [$currentWidth, $currentHeight] = getimagesize($imagePath);
        $ratio = $currentWidth / $currentHeight;
        $newWidth = static::MAX_WIDTH;
        $newHeight = static::MAX_HEIGHT;
        if ($newWidth / $newHeight > $ratio) {
            $newWidth = $newHeight * $ratio;
        } else {
            $newHeight = $newWidth / $ratio;
        }

        $photo = $this->imagine->open($imagePath);
        $photo->resize(new Box($newWidth, $newHeight))->save($imagePath);
    }
}
