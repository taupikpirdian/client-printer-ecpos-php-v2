<?php

/**
 * This file is part of escpos-php: PHP receipt printer library for use with
 * ESC/POS-compatible thermal and impact printers.
 *
 * Copyright (c) 2014-20 Michael Billington < michael.billington@gmail.com >,
 * incorporating modifications by others. See CONTRIBUTORS.md for a full list.
 *
 * This software is distributed under the terms of the MIT license. See LICENSE.md
 * for details.
 */

declare(strict_types=1);

namespace Mike42\Escpos;

use Mike42\GfxPhp\Image;

/**
 * Implementation of EscposImage using only native PHP.
 */
class NativeEscposImage extends EscposImage
{
    protected function loadImageData(string $filename = null)
    {
        echo "=============================================\n";
        echo $filename . "\n";
        if ($filename === null) {
            echo "No image data provided\n";
            return;
        }
        $image = Image::fromFile($filename)->toRgb()->toBlackAndWhite();
        $imgHeight = $image -> getHeight();
        $imgWidth = $image -> getWidth();
        $imgData = str_repeat("\0", $imgHeight * $imgWidth);
        for ($y = 0; $y < $imgHeight; $y++) {
            for ($x = 0; $x < $imgWidth; $x++) {
                $imgData[$y * $imgWidth + $x] = $image -> getPixel($x, $y) == 0 ? 0: 1;
            }
        }
        $this -> setImgWidth($imgWidth);
        $this -> setImgHeight($imgHeight);
        $this -> setImgData($imgData);
    }
}
