<?php

namespace App\Services\Websocket\Validators;

class MessageValidator
{
    public function validate($msg)
    {
        $msg = preg_replace("/[\r\n]+/", "<br>", $msg);

        return json_decode($msg);
    }
}
