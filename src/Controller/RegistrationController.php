<?php

namespace App\Controller;

use App\Entity\Conseil;
use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\UserType;
use App\Repository\AnnonceRepository;
use App\Repository\ConseilRepository;
use App\Repository\UserRepository;
use App\Service\FileService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;


class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, FileService $fileService): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            //getData retourne l'entitée User
            /** @var User $user */
            $user = $form->getData();

            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();

            $fileService->upload($file, $user, 'photo');

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            // do anything else you need here, like send an email

            return $this->redirectToRoute('annonce_index');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);

        
    }

    #[Route('/register/{id}', name: 'app_compte')]
    public function compte(User $user): Response
    {
        return $this->render('registration/compte.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/register/{id}/edit', name: 'app_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, UserPasswordEncoderInterface $passwordEncoder, User $user, FileService $fileService): Response
    {
        $form = $this->createForm(RegistrationFormType::class, $user);
        $previousImage = $user->getPhoto();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );


             //getData retourne l'entitée User
            /** @var User $user */
            $user = $form->getData();

            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();
            
            if ($file != null) {
                $fileService->upload($file, $user, 'photo');
               
            }
            $this->getDoctrine()->getManager()->flush();
            $newImage = $user->getPhoto();
           

            return $this->redirectToRoute('app_compte', ['id'=> $user->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('registration/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/register/annonce/{id}', name:'app_annonce')]
    public function annonce(AnnonceRepository $annonceRepository) : Response
    {
        $user = $this->getUser();
        
        return $this->render('annonce/index.html.twig', [
            'annonces' => $annonceRepository->findByUser($user),
        ]);
    }
    #[Route('/register/conseil/{id}', name:'app_conseil')]
    public function conseil(ConseilRepository $conseilRepository, Conseil $conseil) : Response
    {
        $user = $this->getUser();
        
        return $this->render('conseil/index.html.twig', [
            'conseils' => $conseilRepository->findByUser($user),
        ]);
    }

    #[Route('/accept-cookie', name:'accept_cookie')]
    public function acceptCookie(Request $request) : Response
    {
        $session = $request->getSession();
        $session->set('acceptCookie', true);

        return $this->json(['error' => false]);
    }
}
