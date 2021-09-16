<?php

namespace App\Traits;

use Exception;

trait ErrorResponseTrait
{
    private function errorResponse(Exception $e)
    {
        $message = $e->getMessage() ?? "An error occured";
        $code = $e->getCode() == 0 ? 400 : $e->getCode();

        return response()->json(["message" => $message], $code);
    } //end method errorResponse
}//end trait ErrorResponseTrait