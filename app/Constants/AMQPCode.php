<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace App\Constants;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * @Constants
 */
class AMQPCode extends AbstractConstants
{
    public const DURABLE_TRUE = true;

    public const DURABLE_FALSE = true;

    public const EXCHANGE_DIRECT = 'direct';

    public const EXCHANGE_FANOUT = 'fanout';

    public const EXCHANGE_HEADERS = 'headers';

    public const EXCHANGE_TOPIC = 'topic';
}
