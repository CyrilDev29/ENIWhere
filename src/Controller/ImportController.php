<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Site;
use App\Form\UserImportType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// si on veut sécuriser l'acces si on veut reserver aux admins
// use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;



#[Route('/admin/import', name: 'admin_import_')]
class ImportController extends AbstractController
{
    #[Route('/users', name: 'users', methods: ['GET', 'POST'])]
    // #[IsGranted('ROLE_ADMIN')]
    public function importUsers(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {

        $form = $this->createForm(UserImportType::class);
        $form->handleRequest($request);

        $importResults = [];
        $hasErrors = false;

        if ($form->isSubmitted() && $form->isValid()) {

            $csvFile = $form->get('csvFile')->getData();

            if ($csvFile) {
                $importResults = $this->processCsvFile(
                    $csvFile,
                    $userPasswordHasher,
                    $entityManager,
                    $validator
                );

                $hasErrors = !empty($importResults['errors']);

                if (!$hasErrors && !empty($importResults['success'])) {
                    $this->addFlash('success', count($importResults['success']) . ' utilisateur(s) ont été importé(s) avec succès.');
                } elseif ($hasErrors) {
                    $this->addFlash('warning', 'Import terminé avec des erreurs. Consultez le détail ci-dessous.');
                } else {
                    $this->addFlash('info', 'Aucun utilisateur n’a été importé.');
                }
            }
        }

        return $this->render('admin/import/import.html.twig', [
            'form' => $form->createView(),
            'importResults' => $importResults,
            'hasErrors' => $hasErrors,
        ]);
    }


    //Traite le fichier CSV et retourne les résultats.

    private function processCsvFile(
        UploadedFile $csvFile,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): array {
        $results = [
            'success' => [],
            'errors'  => [],
        ];

        $handle = fopen($csvFile->getPathname(), 'r');
        if ($handle === false) {
            $results['errors'][] = [
                'success' => false,
                'line'    => 0,
                'email'   => 'N/A',
                'error'   => 'Impossible d’ouvrir le fichier CSV.',
            ];
            return $results;
        }

        // Détection simple du délimiteur (',' ou ';') et gestion BOM
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);
            $results['errors'][] = [
                'success' => false,
                'line'    => 0,
                'email'   => 'N/A',
                'error'   => 'Fichier CSV vide.',
            ];
            return $results;
        }
        $delimiter = (substr_count($firstLine, ';') > substr_count($firstLine, ',')) ? ';' : ',';
        // Retour au début du fichier
        rewind($handle);

        $lineNumber = 0;
        $headers = [];

        // Mémoires pour détecter les doublons dans le même fichier (avant la BDD)
        $seenEmails = [];
        $seenUsernames = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNumber++;

            // Première ligne = entêtes
            if ($lineNumber === 1) {
                // Retire un éventuel BOM UTF-8 et espace de chaque header
                $headers = array_map(static function ($h) {
                    return ltrim(trim((string)$h), "\xEF\xBB\xBF");
                }, $row);

                $requiredColumns = ['lastName', 'firstName', 'username', 'email', 'password'];
                $missingColumns = array_diff($requiredColumns, $headers);
                if (!empty($missingColumns)) {
                    fclose($handle);
                    $results['errors'][] = [
                        'success' => false,
                        'line'    => 1,
                        'email'   => 'N/A',
                        'error'   => 'Colonnes manquantes : ' . implode(', ', $missingColumns),
                    ];
                    return $results;
                }
                continue;
            }

            // Associe chaque valeur à son entête
            $row = array_map('trim', $row);
            $userData = @array_combine($headers, $row);
            if ($userData === false) {
                $results['errors'][] = [
                    'success' => false,
                    'line'    => $lineNumber,
                    'email'   => 'N/A',
                    'error'   => 'Format de ligne invalide (nombre de colonnes incorrect).',
                ];
                continue;
            }

            // Doublons intra-fichier évite d’attendre le flush
            $emailKey = strtolower($userData['email'] ?? '');
            $usernameKey = strtolower($userData['username'] ?? '');

            if ($emailKey && isset($seenEmails[$emailKey])) {
                $results['errors'][] = [
                    'success' => false,
                    'line'    => $lineNumber,
                    'email'   => $userData['email'],
                    'error'   => 'Doublon d’email détecté dans le fichier.',
                ];
                continue;
            }
            if ($usernameKey && isset($seenUsernames[$usernameKey])) {
                $results['errors'][] = [
                    'success' => false,
                    'line'    => $lineNumber,
                    'email'   => $userData['email'] ?? 'N/A',
                    'error'   => 'Doublon de nom d’utilisateur détecté dans le fichier.',
                ];
                continue;
            }

            // Traite la ligne
            $result = $this->createUserFromCsvData(
                $userData,
                $lineNumber,
                $userPasswordHasher,
                $entityManager,
                $validator
            );

            if ($result['success']) {
                $seenEmails[$emailKey] = true;
                $seenUsernames[$usernameKey] = true;
                $results['success'][] = $result;
            } else {
                $results['errors'][] = $result;
            }
        }

        fclose($handle);

        // Si pas d'erreurs, flush  (tout ou rien)
        if (empty($results['errors']) && !empty($results['success'])) {
            $connection = $entityManager->getConnection();
            $connection->beginTransaction();
            try {
                $entityManager->flush();
                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();
                $results['errors'][] = [
                    'success' => false,
                    'line'    => 0,
                    'email'   => 'N/A',
                    'error'   => 'Erreur lors de la sauvegarde en base : ' . $e->getMessage(),
                ];
                $results['success'] = [];
            }
        }

        return $results;
    }


     // Crée un utilisateur à partir d’une ligne CSV.

    private function createUserFromCsvData(
        array $userData,
        int $lineNumber,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): array {
        try {
            $user = new User();

            // vérifie que les chhamps obligatoire ne sont pas vite
            foreach (['lastName', 'firstName', 'username', 'email', 'password'] as $field) {
                if (empty($userData[$field])) {
                    return [
                        'success' => false,
                        'line'    => $lineNumber,
                        'email'   => $userData['email'] ?? 'N/A',
                        'error'   => "Le champ « {$field} » est obligatoire.",
                    ];
                }
            }

            // rempli l'entité
            $user->setLastName($userData['lastName']);
            $user->setFirstName($userData['firstName']);
            $user->setUsername($userData['username']);
            $user->setEmail($userData['email']);

            // hash du mdp
            $plainPassword = (string)$userData['password'];
            if (mb_strlen($plainPassword) < 8) {
                return [
                    'success' => false,
                    'line'    => $lineNumber,
                    'email'   => $userData['email'],
                    'error'   => 'Le mot de passe doit contenir au moins 8 caractères.',
                ];
            }
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            // tous les champs optionnels
            // téléphone (optionnel)
            if (!empty($userData['phoneNumber'])) {
                $user->setPhoneNumber($userData['phoneNumber']);
            }

            // Le site est optionnel mais il  doit exister si fourni
            if (!empty($userData['site'])) {
                $site = $entityManager->getRepository(Site::class)->findOneBy(['name' => $userData['site']]);
                if (!$site) {
                    return [
                        'success' => false,
                        'line'    => $lineNumber,
                        'email'   => $userData['email'],
                        'error'   => "Le site « {$userData['site']} » n’existe pas en base de données.",
                    ];
                }
                $user->setSite($site);
            }

            // role
            if (!empty($userData['roles'])) {
                $roles = array_filter(array_map('trim', explode(';', $userData['roles'])));
                $user->setRoles($roles ?: ['ROLE_USER']);
            } else {
                $user->setRoles(['ROLE_USER']);
            }

            // actif
            if (array_key_exists('isActive', $userData) && $userData['isActive'] !== '') {
                $user->setIsActive(filter_var($userData['isActive'], FILTER_VALIDATE_BOOLEAN));
            } else {
                $user->setIsActive(true);
            }

            // validation de l’entité (messages issus des contraintes/UniqueEntity)
            $violations = $validator->validate($user);
            if (count($violations) > 0) {
                $messages = [];
                foreach ($violations as $violation) {
                    $messages[] = $violation->getMessage();
                }
                return [
                    'success' => false,
                    'line'    => $lineNumber,
                    'email'   => $userData['email'],
                    'error'   => 'Erreurs de validation : ' . implode(', ', $messages),
                ];
            }

            // gestion des doublons en BDD pour une sécurité supplementaire
            if ($entityManager->getRepository(User::class)->findOneBy(['email' => $userData['email']])) {
                return [
                    'success' => false,
                    'line'    => $lineNumber,
                    'email'   => $userData['email'],
                    'error'   => 'Un utilisateur avec cet email existe déjà.',
                ];
            }
            if ($entityManager->getRepository(User::class)->findOneBy(['username' => $userData['username']])) {
                return [
                    'success' => false,
                    'line'    => $lineNumber,
                    'email'   => $userData['email'],
                    'error'   => "Un utilisateur avec ce nom d’utilisateur existe déjà.",
                ];
            }

            // Persist sans flush
            $entityManager->persist($user);

            return [
                'success'  => true,
                'line'     => $lineNumber,
                'email'    => $userData['email'],
                'username' => $userData['username'],
                'site'     => $userData['site'] ?? 'Aucun',
                'roles'    => implode(', ', $user->getRoles()),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'line'    => $lineNumber,
                'email'   => $userData['email'] ?? 'N/A',
                'error'   => 'Erreur technique : ' . $e->getMessage(),
            ];
        }
    }
}
