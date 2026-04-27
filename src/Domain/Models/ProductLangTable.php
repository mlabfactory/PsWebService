<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Domain\Models;

use DolzeZampa\WS\Domain\Models\PsTable;


final class ProductLangTable extends PsTable
{
    protected $table = 'product_lang';
    protected $primaryKey = 'id_product';

}