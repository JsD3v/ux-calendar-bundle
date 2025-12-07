<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\Form;

use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use JeanSebastienChristophe\CalendarBundle\Form\EventType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;

class EventTypeTest extends TypeTestCase
{
    public function testBuildForm(): void
    {
        // DateTimeType with single_text widget expects string input in ISO format
        $formData = [
            'title' => 'Test Event',
            'startDate' => '2025-01-15T10:00:00',
            'endDate' => '2025-01-15T12:00:00',
            'allDay' => false,
            'description' => 'Test description',
            'color' => '#ff0000',
        ];

        $event = new Event();
        $form = $this->factory->create(EventType::class, $event);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());

        $this->assertEquals($formData['title'], $event->getTitle());
        $this->assertEquals(new \DateTime($formData['startDate']), $event->getStartDate());
        $this->assertEquals(new \DateTime($formData['endDate']), $event->getEndDate());
        $this->assertEquals($formData['allDay'], $event->isAllDay());
        $this->assertEquals($formData['description'], $event->getDescription());
        $this->assertEquals($formData['color'], $event->getColor());
    }

    public function testFormHasCorrectFields(): void
    {
        $form = $this->factory->create(EventType::class);

        $this->assertTrue($form->has('title'));
        $this->assertTrue($form->has('startDate'));
        $this->assertTrue($form->has('endDate'));
        $this->assertTrue($form->has('allDay'));
        $this->assertTrue($form->has('description'));
        $this->assertTrue($form->has('color'));
    }

    public function testTitleFieldConfiguration(): void
    {
        $form = $this->factory->create(EventType::class);
        $titleConfig = $form->get('title')->getConfig();

        $this->assertEquals(TextType::class, get_class($titleConfig->getType()->getInnerType()));
        $this->assertEquals('Titre', $titleConfig->getOption('label'));

        $attr = $titleConfig->getOption('attr');
        $this->assertArrayHasKey('placeholder', $attr);
        $this->assertEquals('Ex: Réunion d\'équipe', $attr['placeholder']);
        $this->assertArrayHasKey('class', $attr);
        $this->assertEquals('form-control', $attr['class']);
    }

    public function testStartDateFieldConfiguration(): void
    {
        $form = $this->factory->create(EventType::class);
        $startDateConfig = $form->get('startDate')->getConfig();

        $this->assertEquals(DateTimeType::class, get_class($startDateConfig->getType()->getInnerType()));
        $this->assertEquals('Date de début', $startDateConfig->getOption('label'));
        $this->assertEquals('single_text', $startDateConfig->getOption('widget'));

        $attr = $startDateConfig->getOption('attr');
        $this->assertArrayHasKey('class', $attr);
        $this->assertEquals('form-control', $attr['class']);
    }

    public function testEndDateFieldConfiguration(): void
    {
        $form = $this->factory->create(EventType::class);
        $endDateConfig = $form->get('endDate')->getConfig();

        $this->assertEquals(DateTimeType::class, get_class($endDateConfig->getType()->getInnerType()));
        $this->assertEquals('Date de fin', $endDateConfig->getOption('label'));
        $this->assertEquals('single_text', $endDateConfig->getOption('widget'));

        $attr = $endDateConfig->getOption('attr');
        $this->assertArrayHasKey('class', $attr);
        $this->assertEquals('form-control', $attr['class']);
    }

    public function testAllDayFieldConfiguration(): void
    {
        $form = $this->factory->create(EventType::class);
        $allDayConfig = $form->get('allDay')->getConfig();

        $this->assertEquals(CheckboxType::class, get_class($allDayConfig->getType()->getInnerType()));
        $this->assertEquals('Journée entière', $allDayConfig->getOption('label'));
        $this->assertFalse($allDayConfig->getRequired());

        $attr = $allDayConfig->getOption('attr');
        $this->assertArrayHasKey('class', $attr);
        $this->assertEquals('form-check-input', $attr['class']);
    }

    public function testDescriptionFieldConfiguration(): void
    {
        $form = $this->factory->create(EventType::class);
        $descriptionConfig = $form->get('description')->getConfig();

        $this->assertEquals(TextareaType::class, get_class($descriptionConfig->getType()->getInnerType()));
        $this->assertEquals('Description', $descriptionConfig->getOption('label'));
        $this->assertFalse($descriptionConfig->getRequired());

        $attr = $descriptionConfig->getOption('attr');
        $this->assertArrayHasKey('placeholder', $attr);
        $this->assertEquals('Description de l\'événement...', $attr['placeholder']);
        $this->assertArrayHasKey('class', $attr);
        $this->assertEquals('form-control', $attr['class']);
        $this->assertArrayHasKey('rows', $attr);
        $this->assertEquals(4, $attr['rows']);
    }

    public function testColorFieldConfiguration(): void
    {
        $form = $this->factory->create(EventType::class);
        $colorConfig = $form->get('color')->getConfig();

        $this->assertEquals(ColorType::class, get_class($colorConfig->getType()->getInnerType()));
        $this->assertEquals('Couleur', $colorConfig->getOption('label'));
        $this->assertFalse($colorConfig->getRequired());

        $attr = $colorConfig->getOption('attr');
        $this->assertArrayHasKey('class', $attr);
        $this->assertEquals('form-control form-control-color', $attr['class']);
    }

    public function testFormWithAllDayEvent(): void
    {
        $formData = [
            'title' => 'All Day Event',
            'startDate' => new \DateTime('2025-01-15 00:00:00'),
            'endDate' => new \DateTime('2025-01-15 23:59:59'),
            'allDay' => true,
            'description' => null,
            'color' => '#00ff00',
        ];

        $event = new Event();
        $form = $this->factory->create(EventType::class, $event);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($event->isAllDay());
    }

    public function testFormWithOptionalFieldsEmpty(): void
    {
        $formData = [
            'title' => 'Minimal Event',
            'startDate' => new \DateTime('2025-01-15 10:00:00'),
            'endDate' => new \DateTime('2025-01-15 12:00:00'),
            'allDay' => false,
        ];

        $event = new Event();
        $form = $this->factory->create(EventType::class, $event);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertNull($event->getDescription());
    }

    public function testDataClassIsEvent(): void
    {
        $form = $this->factory->create(EventType::class);
        $config = $form->getConfig();

        $this->assertEquals(Event::class, $config->getOption('data_class'));
    }

    public function testFormWithMultiDayEvent(): void
    {
        $formData = [
            'title' => 'Multi Day Event',
            'startDate' => '2025-01-15T10:00:00',
            'endDate' => '2025-01-20T18:00:00',
            'allDay' => false,
            'description' => 'A week-long event',
            'color' => '#0000ff',
        ];

        $event = new Event();
        $form = $this->factory->create(EventType::class, $event);
        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(new \DateTime($formData['startDate']), $event->getStartDate());
        $this->assertEquals(new \DateTime($formData['endDate']), $event->getEndDate());
    }

    public function testFormPrePopulatesExistingEvent(): void
    {
        $existingEvent = new Event();
        $existingEvent
            ->setTitle('Existing Event')
            ->setStartDate(new \DateTime('2025-01-15 10:00:00'))
            ->setEndDate(new \DateTime('2025-01-15 12:00:00'))
            ->setAllDay(true)
            ->setDescription('Existing description')
            ->setColor('#ff00ff');

        $form = $this->factory->create(EventType::class, $existingEvent);

        $this->assertEquals('Existing Event', $form->get('title')->getData());
        $this->assertEquals(new \DateTime('2025-01-15 10:00:00'), $form->get('startDate')->getData());
        $this->assertEquals(new \DateTime('2025-01-15 12:00:00'), $form->get('endDate')->getData());
        $this->assertTrue($form->get('allDay')->getData());
        $this->assertEquals('Existing description', $form->get('description')->getData());
        $this->assertEquals('#ff00ff', $form->get('color')->getData());
    }
}
