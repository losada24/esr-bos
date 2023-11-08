<?php

namespace App\Products;

use App\Interfaces\IProduct;

class FixedWindows implements IProduct {

    public $width;
    public $height;
    public $frame_color;
    public $line_item_name;
    public $glass;
    public $qty;
    public $markup;
}