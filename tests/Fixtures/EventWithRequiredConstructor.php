<?php

namespace JeanSebastienChristophe\CalendarBundle\Tests\Fixtures;

class EventWithRequiredConstructor extends CustomEvent
{
    public function __construct(string $name)
    {
        parent::__construct();
    }
}
