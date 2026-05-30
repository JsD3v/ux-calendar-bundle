<?php

namespace JeanSebastienChristophe\CalendarBundle\Contract;

interface CalendarEventRepositoryInterface
{
    /**
     * @return CalendarEventInterface[]
     */
    public function findByMonth(int $year, int $month): array;
}
