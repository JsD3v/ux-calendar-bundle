<?php

namespace JeanSebastienChristophe\CalendarBundle\ValueResolver;

use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EventValueResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly string $eventClass
    ) {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // Only resolve CalendarEventInterface arguments
        $argumentType = $argument->getType();
        if ($argumentType !== CalendarEventInterface::class) {
            return [];
        }

        // Get the {id} parameter from the route
        $id = $request->attributes->get('id');
        if ($id === null) {
            return [];
        }

        // Fetch the event entity
        $event = $this->entityManager->getRepository($this->eventClass)->find($id);

        if (!$event instanceof CalendarEventInterface) {
            throw new NotFoundHttpException(sprintf('Event with id "%s" not found.', $id));
        }

        yield $event;
    }
}
