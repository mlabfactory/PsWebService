<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class PsTable extends Model
{
    public $timestamps = false;

    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable()
    {
        if (empty(env('PS_TABLE_PREFIX'))) {
            throw new \RuntimeException("PS_TABLE_PREFIX environment variable is not set. Please check your configuration.");
        }

        return $this->table = env('PS_TABLE_PREFIX', 'ps_') . $this->table;
    }
    

}