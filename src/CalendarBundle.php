<?php

namespace JeanSebastienChristophe\CalendarBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class CalendarBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
