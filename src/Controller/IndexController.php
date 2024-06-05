<?php

namespace App\Controller;

use App\DTO\SubmittedUrl;
use App\Form\IndexFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    public const SESSION_KEY = 'url';
    
    #[Route('/', name: 'app_index')]
    public function __invoke(Request $request, SessionInterface $session): Response
    {
        $form = $this->createForm(IndexFormType::class);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SubmittedUrl $url */
            $url = $form->getData();
            $session->set(self::SESSION_KEY, $url->getUrl());
            return $this->redirectToRoute('app_show_images');
        }
        
        return $this->render('index.html.twig', [
            'controller_name' => 'IndexController',
            'form' => $form
        ]);
    }
}
