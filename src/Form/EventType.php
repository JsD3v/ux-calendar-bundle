<?php

namespace JeanSebastienChristophe\CalendarBundle\Form;

use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventInterface;
use JeanSebastienChristophe\CalendarBundle\Entity\Event;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function __construct(
        private readonly string $eventClass = Event::class
    ) {
        if (!is_a($this->eventClass, CalendarEventInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf(
                'The event class "%s" must implement "%s".',
                $this->eventClass,
                CalendarEventInterface::class
            ));
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'calendar.form.title',
                'attr' => [
                    'placeholder' => 'calendar.form.title_placeholder',
                    'class' => 'form-control',
                ],
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'calendar.form.start_date',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'calendar.form.end_date',
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('allDay', CheckboxType::class, [
                'label' => 'calendar.form.all_day',
                'required' => false,
                'attr' => [
                    'class' => 'form-check-input',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'calendar.form.description',
                'required' => false,
                'attr' => [
                    'placeholder' => 'calendar.form.description_placeholder',
                    'class' => 'form-control',
                    'rows' => 4,
                ],
            ])
            ->add('color', ColorType::class, [
                'label' => 'calendar.form.color',
                'required' => false,
                'attr' => [
                    'class' => 'form-control form-control-color',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->eventClass,
            'translation_domain' => 'calendar',
        ]);
    }
}
