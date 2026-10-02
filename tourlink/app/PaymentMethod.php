<?php

namespace App;

enum PaymentMethod: string
{
    case Mpesa = 'MPESA';
    case Card = 'CARD';
    case Cash = 'CASH';
    case BankTransfer = 'BANK_TRANSFER';
}
