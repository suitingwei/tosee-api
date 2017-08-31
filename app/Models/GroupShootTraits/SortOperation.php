<?php

namespace App\Models\GroupShootTraits;

/**
 * Trait SortOperation
 * @package App\Models\Selections
 */
trait SortOperation
{
    /**
     * @return bool
     */
    public function isTop()
    {
        return static::max('sort') == $this->sort;
    }

    /**
     * @return bool
     */
    public function isBottom()
    {
        return static::min('sort') == $this->sort;
    }

    /**
     *
     */
    public function moveUp()
    {
        if ($this->isTop()) {
            return;
        }
        $above = $this->above();
        $this->exchangeSort($above);
    }

    /**
     * @param int $step
     * @return mixed
     */
    public function above($step = 1)
    {
        return static::where('sort', '>', $this->sort)->orderBy('sort')->first();
    }

    /**
     * @param int $step
     * @return mixed
     */
    public function below($step = 1)
    {
        return static::where('sort', '<', $this->sort)->orderByDesc('sort')->first();
    }

    /**
     *
     */
    public function moveDown()
    {
        if ($this->isBottom()) {
            return;
        }
        $below = $this->below();
        $this->exchangeSort($below);
    }

    /**
     * @param $needToChange
     */
    protected function exchangeSort($needToChange)
    {
        $newSort = $needToChange->sort;
        $needToChange->update(['sort' => $this->sort]);
        $this->update(['sort' => $newSort]);
    }
}
