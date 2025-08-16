<?php

declare(strict_types=1);

namespace App\View\Components\Alert;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class Validation extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(public $errors)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.alert.validation');
    }
}
