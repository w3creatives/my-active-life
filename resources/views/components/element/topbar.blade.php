<nav
    class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
    id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="icon-base ti tabler-menu-2 icon-md"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">
        <div class="navbar-nav align-items-center">
            <div class="nav-item dropdown me-2 me-xl-0">
                <a
                    class="nav-link dropdown-toggle hide-arrow"
                    id="nav-theme"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                    <i class="icon-base ti tabler-sun icon-md theme-icon-active"></i>
                    <span class="d-none ms-2" id="nav-theme-text">Toggle theme</span>
                </a>
                <ul class="dropdown-menu dropdown-menu-start" aria-labelledby="nav-theme-text">
                    <li>
                        <button
                            type="button"
                            class="dropdown-item align-items-center active"
                            data-bs-theme-value="light"
                            aria-pressed="false">
                            <span><i class="icon-base ti tabler-sun icon-md me-3" data-icon="sun"></i>Light</span>
                        </button>
                    </li>
                    <li>
                        <button
                            type="button"
                            class="dropdown-item align-items-center"
                            data-bs-theme-value="dark"
                            aria-pressed="true">
                        <span
                        ><i class="icon-base ti tabler-moon-stars icon-md me-3" data-icon="moon-stars"></i>Dark</span
                        >
                        </button>
                    </li>
                    <li>
                        <button
                            type="button"
                            class="dropdown-item align-items-center"
                            data-bs-theme-value="system"
                            aria-pressed="false">
                        <span
                        ><i
                                class="icon-base ti tabler-device-desktop-analytics icon-md me-3"
                                data-icon="device-desktop-analytics"></i
                            >System</span
                        >
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <ul class="navbar-nav flex-row align-items-center ms-md-auto">
            <!-- User -->
            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                <a
                    class="nav-link dropdown-toggle hide-arrow p-0"
                    href="javascript:void(0);"
                    data-bs-toggle="dropdown">
                        <x-auth.avatar :user="auth()->user()"></x-auth.avatar>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <a class="dropdown-item" href="#">
                            <div class="d-flex">
                                <div class="flex-shrink-0 me-3">

                                    <x-auth.avatar :user="auth()->user()"
                                                   classNames="w-px-40 h-auto"></x-auth.avatar>

                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ auth()->user()->display_name }}</h6>
                                    <small class="text-body-secondary">Admin</small>
                                </div>
                            </div>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1 mx-n2"></div>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="icon-base ti tabler-user icon-md me-3"></i><span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="icon-base ti tabler-settings icon-md me-3"></i><span>Settings</span>
                        </a>
                    </li>
                    <li>
                        <div class="dropdown-divider my-1 mx-n2"></div>
                    </li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="icon-base ti tabler-power icon-md me-3"></i><span>Log Out</span>
                            </button>
                        </form>
                    </li>

                </ul>
            </li>
            <!--/ User -->
        </ul>
    </div>
</nav>
