<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\Form;

use JeanSebastienChristophe\CalendarBundle\Form\EventType;
use JeanSebastienChristophe\CalendarBundle\Tests\Fixtures\CustomEvent;
use Symfony\Component\Form\Test\TypeTestCase;

class CustomEventTypeTest extends TypeTestCase
{
    public function testDataClassUsesConfiguredEventClass(): void
    {
        $form = $this->factory->create(EventType::class);

        $this->assertSame(CustomEvent::class, $form->getConfig()->getOption('data_class'));
    }

    public function testFormAcceptsConfiguredCustomEventInstance(): void
    {
        $event = new CustomEvent();
        $form = $this->factory->create(EventType::class, $event);

        $form->submit([
            'title' => 'Custom Event',
            'startDate' => '2025-01-15T10:00:00',
            'endDate' => '2025-01-15T12:00:00',
            'allDay' => false,
            'description' => 'Configured entity',
            'color' => '#123456',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('Custom Event', $event->getTitle());
        $this->assertEquals(new \DateTime('2025-01-15T10:00:00'), $event->getStartDate());
        $this->assertEquals(new \DateTime('2025-01-15T12:00:00'), $event->getEndDate());
        $this->assertSame('Configured entity', $event->getDescription());
        $this->assertSame('#123456', $event->getColor());
    }

    protected function getTypes(): array
    {
        return [
            new EventType(CustomEvent::class),
        ];
    }
}
