<?php
/**
 * The file is part of the swoft_marketing_engine.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 11:31 下午
 */

namespace Anhoder\Mongodb\Connection;

use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Co;
use Swoft\Concern\ArrayPropertyTrait;
use Swoft\Connection\Pool\Contract\ConnectionInterface as BaseConnection;

/**
 * Class ConnectionManager
 *
 * @since 2.0
 *
 * @Bean()
 */
class ConnectionManager
{
    /**
     * @example
     * [
     *  'tid' => [
     *      'cid' => [
     *          'poolName' => [
     *              'connectionId' => Connection
     *          ]
     *      ],
     *      'cid2' => [
     *          'poolName' => [
     *              'connectionId' => Connection
     *          ]
     *      ]
     *   ]
     * ]
     */
    use ArrayPropertyTrait;

    /**
     * @param BaseConnection $connection
     * @param string         $poolName
     */
    public function setConnection(BaseConnection $connection, string $poolName): void
    {
        $poolName = $this->formatName($poolName);

        $key = sprintf('%d.%d.%s.%d', Co::tid(), Co::id(), $poolName, $connection->getId());
        $this->set($key, $connection);
    }

    /**
     * @param int    $id
     * @param string $poolName
     */
    public function releaseConnection(int $id, string $poolName): void
    {
        $poolName = $this->formatName($poolName);

        $key = sprintf('%d.%d.%s.%d', Co::tid(), Co::id(), $poolName, $id);
        $this->unset($key);
    }

    /**
     * release
     *
     * @param bool $final
     */
    public function release(bool $final = false): void
    {
        // Final release
        if ($final) {
            $finalKey = sprintf('%d', Co::tid());
            $this->unset($finalKey);
            return;
        }

        // Release current coroutine
        $key = sprintf('%d.%d', Co::tid(), Co::id());

        $connections = $this->get($key, []);

        foreach ($connections as $poolName => $poolConnection) {
            foreach ($poolConnection as $conId => $connection) {
                if (!$connection instanceof Connection) {
                    continue;
                }

                $connection->release(true);
            }
        }

        $this->unset($key);
    }

    /**
     * Format name
     *
     * @param string $name
     *
     * @return string
     */
    private function formatName(string $name): string
    {
        return str_replace('.', '-', $name);
    }
}

