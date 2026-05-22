<?php

namespace App\Service;

use App\Entity\Bingo;

class BingoChecker
{

private const LINES = [
    [0,1,2,3], [4,5,6,7], [8,9,10,11], [12,13,14,15]
];
private const COLUMNS = [
    [0,4,8,12], [1,5,9,13], [2,6,10,14], [3,7,11,15]
];
    public function getCompletedPositions(Bingo $bingo): array
    {
        $positions = [];
        foreach($bingo->getBingoItems() as $item) {
            if($item->getCompletedAt() !== null) {
                $positions[] = $item->getPosition();
            }
        }
        return $positions;
    }

    public function getCompletedLines(Bingo $bingo): array
    {
        $completed = $this->getCompletedPositions($bingo);
        return array_filter(self::LINES, fn($line) =>
        count(array_intersect($line, $completed)) === 4 );
    }

    public function getCompletedColumns(Bingo $bingo): array
    {
        $completed = $this->getCompletedPositions($bingo);
        return array_filter(self::COLUMNS, fn($column) =>
        count(array_intersect($column, $completed)) === 4 );
    }
    public function hasBingo(Bingo $bingo): bool
    {
        return count($this->getCompletedLines($bingo)) > 0 || count($this->getCompletedColumns($bingo)) > 0;
    }

    /**
     * @return int[] positions belonging to at least one completed line or column
     */
    public function getLinePositions(Bingo $bingo): array
    {
        $positions = [];
        foreach (array_merge($this->getCompletedLines($bingo), $this->getCompletedColumns($bingo)) as $group) {
            $positions = array_merge($positions, $group);
        }
        return array_values(array_unique($positions));
    }
}
