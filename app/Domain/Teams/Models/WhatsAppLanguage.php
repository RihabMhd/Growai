<?php

namespace App\Domain\Teams\Models;

enum WhatsAppLanguage: string
{
    case FR       = 'FR';
    case AR       = 'AR';
    case FR_AR    = 'FR/AR';
    case DarijaAR = 'Darija AR';
    case DarijaFR = 'Darija FR';
}