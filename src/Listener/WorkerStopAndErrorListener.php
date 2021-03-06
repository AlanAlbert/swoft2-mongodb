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
use Swoft\Event\Annotation\Mapping\Subscriber;
use Swoft\Event\EventInterface;
use Swoft\Event\EventSubscriberInterface;
use Swoft\Log\Helper\CLog;
use Swoft\Server\SwooleEvent;
use Swoft\SwoftEvent;

/**
 * Class WorkerStopListener
 *
 * @since 2.0
 *
 * @Subscriber()
 */
class WorkerStopAndErrorListener implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SwooleEvent::WORKER_STOP    => 'handle',
            SwoftEvent::WORKER_SHUTDOWN => 'handle',
        ];
    }

    /**
     * @param EventInterface $event
     *
     */
    public function handle(EventInterface $event): void
    {
        $pools = BeanFactory::getBeans(Pool::class);

        /* @var Pool $pool */
        foreach ($pools as $pool) {
            $count = $pool->close();

            CLog::info('Close %d database connection on %s!', $count, $event->getName());
        }
    }
}
