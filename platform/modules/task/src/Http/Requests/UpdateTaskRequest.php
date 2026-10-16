<?php

namespace Polirium\Modules\Task\Http\Requests;

class UpdateTaskRequest extends TaskRequest
{
    protected function taskId(): ?int
    {
        return (int) $this->route('id');
    }
}
