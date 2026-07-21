<?php

namespace JeanSebastienChristophe\CalendarBundle\Form;

use JeanSebastienChristophe\CalendarBundle\Contract\CalendarEventInterface;
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
    /**
     * @param class-string<CalendarEventInterface>|null $eventClass The configured
     *        `calendar.event_class`. The argument stays optional to keep the
     *        signature backward compatible, but it deliberately has no class
     *        default: falling back to the bundle entity would silently produce a
     *        `data_class` pointing at the wrong class for any subclass declared
     *        in an application, since the bundle's `bind` only applies to
     *        services in its own namespace.
     */
    public function __construct(
        private readonly ?string $eventClass = null
    ) {
        if (null === $this->eventClass) {
            throw new \LogicException(sprintf(
                'The event class is missing. "%s" must receive the configured "calendar.event_class". '
                . 'When extending it in your application, forward the parameter explicitly, for example: '
                . 'services: App\Form\MyEventType: { arguments: ["%%calendar.event_class%%"] }.',
                static::class
            ));
        }

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
