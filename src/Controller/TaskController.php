<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/task')]
class TaskController extends AbstractController
{
    // #[Route('/', name: 'task_index', methods: ['GET'])]
    // public function index(TaskRepository $taskRepository): Response
    // {
    //     // Fetch and display a list of tasks
    //     return $this->render('task/index.html.twig', [
    //         'tasks' => $taskRepository->findAll(),
    //     ]);
    // }

    #[Route('/store', name: 'task_store', methods: ['POST'])]
    public function store(Request $request): Response
    {
        // Create a new Task entity and set its properties from $request data
        $task = new Task();
        $task->setName($request->request->get('name', ''));
        $task->setDescription($request->request->get('description', ''));

        // Handling a dueDate field (assuming it's sent as a string)
        $dueDate = $request->request->get('due_date', null);
        if ($dueDate) {
            try {
                $task->setDueDate(new \DateTime($dueDate));
            } catch (\Exception $e) {
                // Handle invalid date format
                return $this->json(['status' => 'error', 'message' => 'Invalid due date format']);
            }
        }

        // Handling the color field
        $color = $request->request->get('color', null);
        if ($color) {
            $task->setColor($color); // Assuming you have a setColor method in your Task entity
        }

        // Handling tags (assuming they are submitted as an array of tag IDs)
        $tagIds = $request->request->get('tags', []);
        if (!empty($tagIds)) {
            $entityManager = $this->getDoctrine()->getManager();
            foreach ($tagIds as $tagId) {
                $tag = $entityManager->getRepository(Tag::class)->find($tagId);
                if ($tag) {
                    $task->addTag($tag); // Assuming you have an addTag method in your Task entity
                }
            }
        }

        // Validate and persist the task
        $errors = $this->validateTask($task);
        if (!empty($errors)) {
            return $this->json(['status' => 'error', 'message' => $errors]);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($task);
        $entityManager->flush();

        // Return a JSON response
        return $this->json(['status' => 'success', 'message' => 'Task created successfully']);
    }

    // #[Route('/{id}', name: 'task_show', methods: ['GET'])]
    // public function show(Task $task): Response
    // {
    //     // Display a single task
    //     return $this->render('task/show.html.twig', [
    //         'task' => $task,
    //     ]);
    // }

    #[Route('/{id}/edit', name: 'task_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Task $task): Response
    {
        // Check if the request is a POST request
        if ($request->isMethod('POST')) {
            // Set properties from request
            $task->setName($request->request->get('name', $task->getName()));
            $task->setDescription($request->request->get('description', $task->getDescription()));
            
            // Handling a dueDate field (assuming it's sent as a string)
            $dueDate = $request->request->get('due_date', null);
            if ($dueDate) {
                try {
                    $task->setDueDate(new \DateTime($dueDate));
                } catch (\Exception $e) {
                    // Handle invalid date format
                    return $this->json(['status' => 'error', 'message' => 'Invalid due date format']);
                }
            }

            // Handling the color field
            $color = $request->request->get('color', null);
            if ($color) {
                $task->setColor($color); // Assuming you have a setColor method in your Task entity
            }

            // Handling tags (assuming they are submitted as an array of tag IDs)
            $tagIds = $request->request->get('tags', []);
            if (!empty($tagIds)) {
                $entityManager = $this->getDoctrine()->getManager();
                foreach ($tagIds as $tagId) {
                    $tag = $entityManager->getRepository(Tag::class)->find($tagId);
                    if ($tag) {
                        $task->addTag($tag); // Assuming you have an addTag method in your Task entity
                    }
                }
            }

            // Validate task data
            $errors = $this->validateTask($task);
            if (!empty($errors)) {
                return $this->json(['status' => 'error', 'message' => $errors]);
            }

            // Save changes
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            return $this->json(['status' => 'success', 'message' => 'Task updated successfully']);
        }

        // If GET request, return current task data
        // You may choose to return a form structure or just task details for the frontend
        return $this->json(['task' => $task]);
    }

    #[Route('/{id}', name: 'task_delete', methods: ['POST'])]
    public function delete(Request $request, Task $task): Response
    {
        // Delete an existing task
        if ($this->isCsrfTokenValid('delete'.$task->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($task);
            $entityManager->flush();
        }

        return $this->redirectToRoute('task_index');
    }

    private function validateTask(Task $task): array
    {
        $errors = [];

        // Validate name - required
        if (empty($task->getName())) {
            $errors[] = 'Task name is required.';
        }

        // Validate description - optional, so no validation needed

        // Validate due date - check if it's a valid date
        // Assuming dueDate is a DateTime object or null
        if ($task->getDueDate() !== null && !$task->getDueDate() instanceof \DateTimeInterface) {
            $errors[] = 'Invalid due date format.';
        }

        // Validate tags - optional, but if present, ensure they are valid
        foreach ($task->getTags() as $tag) {
            if (!$tag instanceof Tag) {
                $errors[] = 'Invalid tag provided.';
            }
            // Add more specific tag validation if necessary
        }

        // Validate color - optional, but if present, check for valid format (e.g., hex color code)
        if ($task->getColor() !== null) {
            if (!preg_match('/^#(?:[0-9a-fA-F]{3}){1,2}$/', $task->getColor())) {
                $errors[] = 'Invalid color format. Please use a valid hex color code.';
            }
        }

        return $errors;
    }
}
