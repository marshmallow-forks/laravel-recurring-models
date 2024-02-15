<?php

namespace MohammedManssour\LaravelRecurringModels\Support;

use Illuminate\Database\Eloquent\Collection;

class RepeatCollection extends Collection
{
    public function save()
    {
        $this->transform(fn ($item) => $item->getAttributes());
        config('recurring-models.models.repetition')::insert($this->items);
    }
}
