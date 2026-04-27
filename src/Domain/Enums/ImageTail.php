<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Domain\Enums;

enum ImageTail: string {
    case THUMBNAIL = 'default_xs';
    case SMALL = 'default_s';
    case MEDIUM = 'default_m';
    case LARGE = 'default_l';
    case CART = 'cart_default';
    case CATEGORY = 'category_default';
    case ORIGINAL = 'product_main';
    case COVER = 'category_cover';
    
}