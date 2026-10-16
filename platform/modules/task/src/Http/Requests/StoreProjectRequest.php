<?php

namespace Polirium\Modules\Task\Http\Requests;

class StoreProjectRequest extends ProjectRequest
{
    protected function allowedStatuses(): array
    {
        return ['planning', 'active', 'on_hold'];
    }
}
