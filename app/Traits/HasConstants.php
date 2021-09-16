<?php

namespace App\Traits;

use ReflectionClass;

trait HasConstants
{
    public static function getConstants()
    {
        return (new ReflectionClass(__CLASS__))->getConstants();
    } //end method getConstants
}//end trait HasContants
