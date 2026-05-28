<?php

namespace App\Service;

use App\Entity\Bingo;

class BingoChecker
{
    public function getCompletedPositions(Bingo $bingo): array
    {
        $positions = [];
        foreach ($bingo->getBingoItems() as $item) {
            if ($item->getCompletedAt() !== null) {
                $positions[] = $item->getPosition();
            }
        }
        return $positions;
    }

    public function hasBingo(Bingo $bingo): bool
    {
        return count($this->getCompletedLines($bingo)) > 0 || count($this->getCompletedColumns($bingo)) > 0;
    }

    public function getCompletedLines(Bingo $bingo): array
    {
        $size = $bingo->getSize();
        $completed = $this->getCompletedPositions($bingo);
        return array_filter($this->lines($size), fn($line) => count(array_intersect($line, $completed)) === $size);
    }

    public function getCompletedColumns(Bingo $bingo): array
    {
        $size = $bingo->getSize();
        $completed = $this->getCompletedPositions($bingo);
        return array_filter($this->columns($size), fn($column) => count(array_intersect($column, $completed)) === $size);
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

    /**
     * @return list<list<int>>
     */
    private function lines(int $size): array
    {
        $lines = [];
        for ($row = 0; $row < $size; $row++) {
            $line = [];
            for ($col = 0; $col < $size; $col++) {
                $line[] = $row * $size + $col + 1;
            }
            $lines[] = $line;
        }
        return $lines;
    }

    /**
     * @return list<list<int>>
     */
    private function columns(int $size): array
    {
        $columns = [];
        for ($col = 0; $col < $size; $col++) {
            $column = [];
            for ($row = 0; $row < $size; $row++) {
                $column[] = $row * $size + $col + 1;
            }
            $columns[] = $column;
        }
        return $columns;
    }
}
