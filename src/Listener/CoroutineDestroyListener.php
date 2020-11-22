<?php declare(strict_types=1);
/**
 * The file is part of the swoft2-mongodb.
 *
 * (c) alan <alan1766447919@gmail.com>.
 *
 * 2020/11/20 11:50 下午
 */

namespace Anhoder\Mongodb\Listener;

use function bean;
use Anhoder\Mongodb\Connection\ConnectionManager;
use Swoft\Event\Annotation\Mapping\Listener;
use Swoft\Event\EventHandlerInterface;
use Swoft\Event\EventInterface;
use Swoft\SwoftEvent;

/**
 * Class CoroutineDestroyListener
 *
 * @since 2.0
 *
 * @Listener(SwoftEvent::COROUTINE_DESTROY)
 */
class CoroutineDestroyListener implements EventHandlerInterface
{
    /**
     * @param EventInterface $event
     *
     */
    public function handle(EventInterface $event): void
    {
        /* @var ConnectionManager $cm */
        $cm = bean(ConnectionManager::class);
        $cm->release(true);
    }
}
