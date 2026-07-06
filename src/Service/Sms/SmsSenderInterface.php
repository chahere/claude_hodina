<?php

namespace App\Service\Sms;

interface SmsSenderInterface
{
    public function send(string $to, string $message): SmsSendResult;
}
