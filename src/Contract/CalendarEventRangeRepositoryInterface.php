<?php

namespace JeanSebastienChristophe\CalendarBundle\Contract;

/**
 * Optional capability interface for the week/day views.
 *
 * Implement it on your event repository to get an optimised single-query
 * fetch for an arbitrary date range. Repositories that only implement
 * {@see CalendarEventRepositoryInterface} keep working: the controller then
 * falls back to {@see CalendarEventRepositoryInterface::findByMonth()}.
 */
interface CalendarEventRangeRepositoryInterface
{
    /**
     * Returns every event overlapping the given (inclusive) datetime range.
     *
     * @return CalendarEventInterface[]
     */
    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end): array;
}
