<?php

declare(strict_types=1);

namespace App\View\Components\Auth;

use App\Models\User;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\View\Component;

final class Avatar extends Component
{
    public string $avatarCharactor;

    public function __construct(public User $user, public string $classNames = '')
    {
        $this->avatarCharactor = Str::upper(Str::charAt($user->first_name, 0)).Str::upper(Str::charAt($user->last_name, 0));
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.auth.avatar');
    }
}
