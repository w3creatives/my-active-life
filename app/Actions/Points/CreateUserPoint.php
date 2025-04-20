<?php

namespace App\Actions\Points;

use App\Models\User;
use App\Services\UserPointService;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateUserPoint
{
    use AsAction;
    use UserPointService;

    public function handle(User $user, $data)
    {
        $this->createOrUpdate($data);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'min:8'],
            'body' => ['required'],
            'published' => ['required', 'boolean'],
        ];
    }
}
