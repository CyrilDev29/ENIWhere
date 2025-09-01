<?php

namespace App\Helper;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Registration;

class ParticipationWorkflowSubscriber implements EventSubscriberInterface
{
    public function __construct(private MailerInterface $mailer) {}

    public static function getSubscribedEvents(): array
    {
        return [

            'workflow.participation.entered.REGISTERED' => 'onRegistered',
            'workflow.participation.transition.cancel'   => 'onCancel',
            'workflow.participation.transition.notify'   => 'onNotify',
        ];
    }

    public function onRegistered(Event $event): void
    {
        $registration = $event->getSubject();

        if (!$registration instanceof Registration) {
            return;
        }

        $this->sendEmail($registration, 'Confirmation d’inscription', 'Vous êtes inscrit à l’événement.');
    }

    public function onCancel(Event $event): void
    {
        $registration = $event->getSubject();

        if (!$registration instanceof Registration) {
            return;
        }

        $this->sendEmail($registration, 'Annulation', 'Votre inscription a été annulée.');
    }

    public function onNotify(Event $event): void
    {
        $registration = $event->getSubject();

        if (!$registration instanceof Registration) {
            return;
        }

        $this->sendEmail($registration, 'Rappel', 'Votre événement commence dans 48h !');
    }

    private function sendEmail(Registration $registration, string $subject, string $content): void
    {
        $user = $registration->getParticipant();
        $eventEntity = $registration->getEvent();

        if (!$user || !$eventEntity) {
            return;
        }

        $email = (new Email())
            ->from('noreply@tonsite.com')
            ->to($user->getEmail())
            ->subject($subject)
            ->text(sprintf(
                "Bonjour %s,\n\n%s\n\nÉvénement : %s\nDate : %s",
                $user->getFirstName(),
                $content,
                $eventEntity->getName(),
                $eventEntity->getStartDateTime()->format('d/m/Y H:i')
            ));

        $this->mailer->send($email);
    }
}
