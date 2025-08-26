<?php

namespace App\DataFixtures;

use App\Entity\City;
use App\Entity\Event;
use App\Entity\Place;
use App\Entity\Registration;
use App\Entity\Site;
use App\Entity\State;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');


        $statesByLabel = [];
        foreach (['Créée', 'Ouverte', 'Clôturée', 'En cours', 'Terminée', 'Annulée', 'Historisée'] as $label) {
            $s = new State();
            $s->setLabel($label);
            $manager->persist($s);
            $statesByLabel[$label] = $s;
        }

        //Campus
        $sites = [];
        foreach (['Nantes', 'Rennes', 'Quimper', 'Niort'] as $name) {
            $site = new Site();
            $site->setName($name);
            $manager->persist($site);
            $sites[] = $site;
        }

        //Villes lieux
        $cities = [];
        $places = [];
        for ($i = 0; $i < 5; $i++) {
            $city = new City();
            $city->setName($faker->city);
            $city->setPostalCode($faker->postcode);
            $manager->persist($city);
            $cities[] = $city;

            // 2 lieux par ville
            for ($j = 0; $j < 2; $j++) {
                $place = new Place();
                $place->setName($faker->company);
                $place->setStreet($faker->streetAddress);
                $place->setGpsLatitude($faker->latitude());
                $place->setGpsLongitude($faker->longitude());
                $place->setCity($city);
                $manager->persist($place);
                $places[] = $place;
            }
        }

        //Users
        $users = [];
        for ($i = 0; $i < 12; $i++) {
            $u = new User();
            $u->setEmail($faker->unique()->safeEmail());
            $u->setPassword('password');
            $u->setFirstName($faker->firstName());
            $u->setLastName($faker->lastName());
            $u->setPhoneNumber($faker->phoneNumber());
            $u->setUsername($faker->userName());
            $u->setIsActive(true);
            $u->setCreatedAt(new \DateTime());
            $u->setSite($faker->randomElement($sites));
            $manager->persist($u);
            $users[] = $u;
        }

        //Events
        $events = [];
        for ($i = 0; $i < 14; $i++) {
            $start = $faker->dateTimeBetween('+1 day', '+1 month');
            $limit = (clone $start);
            $limit->modify('-' . $faker->numberBetween(1, 10) . ' days');

            $e = new Event();
            $e->setName($faker->sentence(3));
            $e->setDescription($faker->paragraph());
            $e->setStartDateTime($start);
            $e->setDuration($faker->numberBetween(60, 240));
            $e->setRegistrationDeadline($limit);
            $e->setMaxParticipant($faker->numberBetween(6, 30));
            $e->setCreatedDate(new \DateTime());

            $e->setState($faker->randomElement([
                $statesByLabel['Ouverte'],
                $statesByLabel['Clôturée'],
                $statesByLabel['Créée'],
                $statesByLabel['Annulée'],
            ]));
            $e->setSite($faker->randomElement($sites));
            $e->setPlace($faker->randomElement($places));
            $e->setOrganizer($faker->randomElement($users));


            if ($e->getState()->getLabel() === 'Annulée') {
                $e->setCancellationReason($faker->sentence(6));
            }

            $manager->persist($e);
            $events[] = $e;
        }

      //registrations
        foreach ($events as $event) {
            $label = $event->getState() ? $event->getState()->getLabel() : null;


            if (in_array($label, ['Ouverte', 'Clôturée'], true)) {
                $max = (int) $event->getMaxParticipant();
                $nbInscrits = $faker->numberBetween(0, max(0, min($max - 1, 8)));


                $candidats = array_values(array_filter($users, fn(User $u) => $u !== $event->getOrganizer()));
                shuffle($candidats);

                for ($k = 0; $k < $nbInscrits; $k++) {
                    $reg = new Registration();

                    $reg->setParticipant($candidats[$k]);
                    $reg->setEvent($event);


                    if ($label === 'Clôturée' && $faker->boolean(10)) {
                        $reg->setCancellationDate($faker->dateTimeBetween('-5 days', 'now'));
                    }

                    $manager->persist($reg);
                }
            }
        }

        $manager->flush();
    }
}
