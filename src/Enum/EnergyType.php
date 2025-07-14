<?php

namespace App\Enum;

enum EnergyType: string
{
    case ESSENCE = 'ESSENCE';           
    case GAZOIL = 'GAZOIL';         
    case ELECTRICITE = 'ELECTRICITE'; 
    case HYBRID = 'HYBRID'; 
    case BIOCARBURANT = 'BIOCARBURANT'; 
}