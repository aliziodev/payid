<?php

namespace Aliziodev\PayId\Enums;

enum Capability: string
{
    case Charge = 'charge';
    case DirectCharge = 'direct_charge';
    case Status = 'status';
    case Refund = 'refund';
    case Cancel = 'cancel';
    case Expire = 'expire';
    case Approve = 'approve';
    case Deny = 'deny';
    case CreateSubscription = 'create_subscription';
    case GetSubscription = 'get_subscription';
    case UpdateSubscription = 'update_subscription';
    case PauseSubscription = 'pause_subscription';
    case ResumeSubscription = 'resume_subscription';
    case CancelSubscription = 'cancel_subscription';
    case WebhookVerification = 'webhook_verification';
    case WebhookParsing = 'webhook_parsing';
}
