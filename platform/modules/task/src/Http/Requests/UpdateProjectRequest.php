<?php

namespace Polirium\Modules\Task\Http\Requests;

class UpdateProjectRequest extends ProjectRequest
{
    protected function projectId(): ?int
    {
        return (int) $this->route('id');
    }
}
