<?php

namespace Aliziodev\PayId;

use Aliziodev\PayId\Managers\PayIdManager;

/**
 * PayID — Unified Laravel Payment Orchestrator for Indonesian Payment Gateways
 *
 * Entry point class. Di aplikasi Laravel, gunakan Facade PayId atau
 * inject PayIdManager langsung ke constructor.
 *
 * @see PayIdManager
 * @see Laravel\Facades\PayId
 */
final class PayId
{
    public const VERSION = '0.1.0-alpha';
}
