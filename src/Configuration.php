<?php

declare(strict_types=1);

namespace EasySlip;

/** Environment variable names and the production API base URLs. */
final class Configuration
{
    public const API_KEY = 'EASYSLIP_API_KEY';
    public const API_KEY_FILE = 'EASYSLIP_API_KEY_FILE';
    public const V1_ENV = 'EASYSLIP_V1_BASE_URL';
    public const V2_ENV = 'EASYSLIP_V2_BASE_URL';
    public const V1_BASE = 'https://api.easyslip.com/v1';
    public const V2_BASE = 'https://api.easyslip.com/v2';
}
