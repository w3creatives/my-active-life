<?php

namespace App\View\Components\Element;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Sidebar extends Component
{
    public  $menuItems;
    public $currentNav;

    public function __construct()
    {
        list($prefix, $currentNav) = explode('/', request()->path());

        $this->currentNav = $currentNav;

        $this->menuItems = [
            [
                'route' => 'admin.users',
                'icon' => 'tabler-smart-home',
                'label' => 'Users',
                'route_group' => 'users',
            ],
            [
                'route' => 'admin.events',
                'icon' => 'tabler-app-window',
                'label' => 'Events',
                'route_group' => 'events',
            ],
            [
                'route' => 'admin.email.builders',
                'icon' => 'tabler-app-window',
                'label' => 'Email Templates',
                'route_group' => 'email-builders',
            ],
        ];
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.element.sidebar');
    }
}
