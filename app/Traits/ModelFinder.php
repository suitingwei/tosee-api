<?php

namespace App\Traits;

trait ModelFinder
{

    /**
     * @param $id
     *
     * @return static
     */
    public static function find($id)
    {
        return static::where('id', $id)->first();
    }
}
