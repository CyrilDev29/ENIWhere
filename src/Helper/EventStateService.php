<?php
namespace App\Helper;

use App\Entity\Event;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Workflow\Registry;

class EventStateService
{
    public function __construct(
        private readonly Registry $workflows,
        private readonly EntityManagerInterface $em,
        private readonly LoggerInterface $logger,
    ) {}

    public function apply(Event $event, string $transition): bool
    {
        $wf = $this->workflows->get($event, 'event');

        if (!$wf->can($event, $transition)) {
            $this->logger->warning('Transition refusée', [
                'event' => $event->getId(),
                'state' => $event->getState(),
                'try' => $transition,
            ]);
            return false;
        }

        $wf->apply($event, $transition);

        $this->em->flush();

        return true;
    }
}

