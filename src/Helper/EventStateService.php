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
        $wf = $this->workflows->get($event, 'event'); // nom du workflow
        if (!$wf->can($event, $transition)) {
            $this->logger->warning('Transition refusée', [
                'event' => $event->getId(),
                'from' => $event->getStatus(),
                'try' => $transition,
            ]);
            return false;
        }

        $wf->apply($event, $transition);
        $map = [
            'draft' => 'CREATED',
            'published' => 'PUBLISHED',
            'registration_open' => 'OPEN',
            'registration_closed' => 'CLOSED',
            'ongoing' => 'ONGOING',
            'finished' => 'FINISHED',
            'canceled' => 'CANCELED',
        ];
        $label = $map[$event->getStatus()] ?? null;
        if ($label) {
            $state = $this->em->getRepository(\App\Entity\State::class)->findOneBy(['label' => $label]);
            if ($state) {
                $event->setState($state);
            }
        }

        $this->em->flush();
        return true;
    }
}

