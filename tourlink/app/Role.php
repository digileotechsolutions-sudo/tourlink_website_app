<?php

namespace App;

enum Role: string
{
    case Traveler = 'TRAVELER';
    case Operator = 'OPERATOR';
    case VehicleOwner = 'VEHICLE_OWNER';
    case Admin = 'ADMIN';
}
