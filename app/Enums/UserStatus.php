<?php

namespace App\Enums;

enum UserStatus: string
{
    case SETTINGUP = 'Setting Up';
    case ACTIVE = 'Active';
    case PAYMENTWARNING = 'Payment Warning';
    case SUSPENDED = 'Suspended';
    case LEAVING = 'Leaving';
    case ONHOLD = 'On Hold';
    case LEFT = 'Left';
    case HONORARY = 'Honorary';

    public function getLabel(): ?string
    {
        return $this->value;
    }

}
