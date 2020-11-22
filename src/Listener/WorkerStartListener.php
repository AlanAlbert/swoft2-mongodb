<?php declare(strict_types=1);
/**
 * The file is part of the swoft2-mongodb.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 11:50 下午
 */

namespace Anhoder\Mongodb\Listener;

use Swoft\Bean\BeanFactory;
use Anhoder\Mongodb\Pool\Pool;
use Swoft\Event\Annotation\Mapping\Listener;
use Swoft\Event\EventHandlerInterface;
use Swoft\Event\EventInterface;
use Swoft\Server\ServerEvent;

/**
 * Class WorkerStartListener
 *
 * @since 2.0
 *
 * @Listener(event=ServerEvent::WORK_PROCESS_START)
 */
class WorkerStartListener implements EventHandlerInterface
{
    /**
     * @param EventInterface $event
     *
     * @throws \Swoft\Connection\Pool\Exception\ConnectionPoolException
     */
    public function handle(EventInterface $event): void
    {
        $pools = BeanFactory::getBeans(Pool::class);

        /* @var Pool $pool */
        foreach ($pools as $pool) {
            $pool->initPool();
        }
    }
}
