<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/todos', name: 'todo_list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('/test.html');
    }
}