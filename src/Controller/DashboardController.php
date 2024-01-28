<?php

namespace App\Controller;

use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/dashboard')]
class DashboardController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/', name: 'dashboard_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        $user = $this->security->getUser();

        // Ensure the user is authenticated
        if (!$user) {
            throw new AccessDeniedException('User not authenticated');
        }

        // Fetch tasks belonging to the currently authenticated user
        $tasks = $taskRepository->findBy(['user' => $user, 'isDeleted' => false]);

        // Prepare the tasks data for JSON response
        $tasksData = array_map(function ($task) {
            $tagData = array_map(function ($tag) {
                return [
                    'id' => $tag->getId(),
                    'name' => $tag->getName(),
                    // Include other tag fields as needed
                ];
            }, $task->getTags()->toArray());

            return [
                'id' => $task->getId(),
                'name' => $task->getName(),
                'description' => $task->getDescription(),
                'dueDate' => $task->getDueDate() ? $task->getDueDate()->format('Y-m-d H:i:s') : null,
                'color' => $task->getColor(),
                'dateCreated' => $task->getDateCreated()->format('Y-m-d H:i:s'),
                'isCompleted' => $task->getIsCompleted(),
                'tags' => $tagData,
                // Add other necessary fields from your Task entity
            ];
        }, $tasks);

        // Return the data as a JSON response
        return $this->json([
            'status' => 'success',
            'tasks' => $tasksData
        ]);
    }
}