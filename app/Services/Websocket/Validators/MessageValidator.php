<?php

namespace App\Services\Websocket\Validators;

class MessageValidator
{
    public function validateJsonString($msg)
    {
        $msg = preg_replace("/[\r\n]+/", "\\n", $msg);
        
        return json_decode($msg);
    }

    public function validateMessage($msg)
    {
        $msg = quotemeta($msg);
        $msg = preg_replace("/[\r\n]+/", "\\n", $msg);

        $msg = json_decode($msg);

        $msg->value = htmlspecialchars(trim($msg->value), ENT_QUOTES);

        return $msg;
    }
}
