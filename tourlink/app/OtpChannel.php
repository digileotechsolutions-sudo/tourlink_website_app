<?php

namespace App;

enum OtpChannel: string
{
    case Email = 'EMAIL';
    case Phone = 'PHONE';
}
