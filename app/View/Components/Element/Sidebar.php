<?php

declare(strict_types=1);

namespace App\View\Components\Element;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use JetBrains\PhpStorm\NoReturn;

final class Sidebar extends Component
{
    public array $menuItems;

    public string $currentNav;

    public string $currentRoute;

    #[NoReturn]
    public function __construct()
    {
        [$prefix, $currentNav] = explode('/', request()->path());

        $routeAction = request()->route()->action;

        $this->currentRoute = $routeAction['as'] ?? '';

        $this->currentNav = $currentNav;

        $this->menuItems = $this->menuItems();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.element.sidebar');
    }

    private function menuItems(): array
    {
        return [
            [
                'icon' => 'tabler-users',
                'label' => 'Clients',
                'route_group' => 'clients',
                'has_children' => true,
                'children' => [
                    [
                        'route' => 'admin.clients',
                        'label' => 'Clients List',
                        'route_group' => 'clients',
                    ],
                    [
                        'route' => 'admin.clients.create',
                        'label' => 'Create New Client',
                        'route_group' => 'clients',
                    ],
                ],
            ],
            [
                'icon' => 'tabler-users',
                'label' => 'Users',
                'route_group' => 'users',
                'has_children' => true,
                'children' => [
                    [
                        'route' => 'admin.users',
                        'label' => 'Users List',
                        'route_group' => 'users',
                    ],
                    [
                        'route' => 'admin.users.create',
                        'label' => 'Create New Account',
                        'route_group' => 'users',
                    ],
                    [
                        'route' => 'admin.users.merge-accounts',
                        'label' => 'Merge Accounts',
                        'route_group' => 'users',
                    ],
                ],
            ],
            [
                'route' => 'admin.events',
                'icon' => 'tabler-shield',
                'label' => 'Events',
                'route_group' => 'events',
                'has_children' => false,
            ],
            [
                'icon' => 'tabler-sitemap',
                'label' => 'Quests Groups',
                'route_group' => 'quest-groups',
                'has_children' => true,
                'children' => [
                    [
                        'route' => 'admin.quests.groups',
                        'label' => 'Quest Groups List',
                        'route_group' => 'quest-groups',
                    ],
                    [
                        'route' => 'admin.quests.groups.create',
                        'label' => 'Create Quest Group',
                        'route_group' => 'quest-groups',
                    ],
                ],
            ],
            [
                'icon' => 'tabler-category-2',
                'label' => 'Quests Categories',
                'route_group' => 'quest-categories',
                'has_children' => true,
                'children' => [
                    [
                        'route' => 'admin.quests.categories',
                        'label' => 'Quest Categories List',
                        'route_group' => 'quest-categories',
                    ],
                    [
                        'route' => 'admin.quests.categories.create',
                        'label' => 'Create Quest Category',
                        'route_group' => 'quest-categories',
                    ],
                ],
            ],
            [
                'route' => 'admin.email.builders',
                'icon' => 'tabler-app-window',
                'label' => 'Email Templates',
                'route_group' => 'email-builders',
                'has_children' => false,
            ],
            [
                'icon' => 'tabler-chart-infographic',
                'label' => 'Reports',
                'route_group' => 'reports',
                'has_children' => true,
                'children' => [
                    [
                        'route' => 'admin.reports.users',
                        'label' => 'Users',
                        'route_group' => 'reports',
                    ],
                    [
                        'route' => 'admin.reports.point-tracker',
                        'label' => 'Data Sources Tracker',
                        'route_group' => 'reports',
                    ],
                ],
            ],
        ];
    }
}
