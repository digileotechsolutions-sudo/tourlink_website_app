<?php

namespace App;

enum VerificationLevel: string
{
    case Basic = 'BASIC';
    case Verified = 'VERIFIED';
    case Trusted = 'TRUSTED';
}
