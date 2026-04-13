<?php

namespace Aliziodev\PayId\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Failed], true);
    }
}
