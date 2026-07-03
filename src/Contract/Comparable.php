<?php

declare(strict_types=1);

namespace MSSTC4PHP\ValueObject\Contract;

/**
 * Total ordering for value objects that have a natural sort (numbers, versions).
 */
interface Comparable
{
    /**
     * @return int negative when $this precedes $other, 0 when equal, positive when it follows
     */
    public function compareTo(self $other): int;
}
