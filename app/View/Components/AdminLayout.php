<?php

declare(strict_types=1);

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

final class AdminLayout extends Component
{
    public array $themeConfig = [];

    public function __construct(public string $title = '')
    {
        $this->themeConfig = [
            'theme' => request()->cookie('templateCustomizer-admin--Theme') ?? 'system',
            'menuCollapsed' => request()->cookie('templateCustomizer-admin--LayoutCollapsed') ?? false,
        ];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.admin-layout');
    }
}
