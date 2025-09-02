<?php

namespace App\Helper;

use App\Entity\Event;
use App\Entity\Registration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Workflow\Event\Event as WorkflowEvent;

class EventWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(private MailerInterface $mailer) {}

    public static function getSubscribedEvents(): array
    {
        return [
            //---------------- Lorsqu'une sortie passe en CANCELED---------
            'workflow.event.transition.cancel' => 'onEventCanceled',
        ];
    }

    public function onEventCanceled(WorkflowEvent $event): void
    {
        $eventEntity = $event->getSubject();

        if (!$eventEntity instanceof Event) {
            return;
        }

        foreach ($eventEntity->getRegistrations() as $registration) {
            if ($registration instanceof Registration && $registration->getParticipant()) {
                $user = $registration->getParticipant();

                $email = (new Email())
                    ->from('noreply@tonsite.com')
                    ->to($user->getEmail())
                    ->subject('Annulation de l’événement')
                    ->text(sprintf(
                        "Bonjour %s,\n\nL’événement '%s' prévu le %s a été annulé.",
                        $user->getFirstName(),
                        $eventEntity->getName(),
                        $eventEntity->getStartDateTime()->format('d/m/Y H:i')
                    ));

                $this->mailer->send($email);
            }
        }
    }
}
