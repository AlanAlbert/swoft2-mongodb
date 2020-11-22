<?php
/**
 * The file is part of the swoft_marketing_engine.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 5:50 下午
 */

namespace Anhoder\Mongodb\Contract;

use MongoDB\Client;

/**
 * Interface ConnectorInterface
 * @package Database\Mongo
 */
interface ConnectorInterface
{
    /**
     * @param array $config
     * @return Client
     */
    public function connect(array $config): Client;
}
