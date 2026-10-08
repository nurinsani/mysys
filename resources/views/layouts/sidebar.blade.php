<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="#" class="brand-link">
        <img src="{{ asset('assets/img/logo-ni-white.png') }}" alt="" class="brand-image" style="opacity: .8">
        <span class="brand-text font-weight-light">KSPPS Nur Insani</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu"
                    data-accordion="false">
                    @foreach ($menus as $menu)
                        <li class="nav-item">
                            <a href="{{ $menu->url ? url($menu->url) : '#' }}" class="nav-link">
                                <i class="{{ $menu->icon }}"></i>
                                <p>
                                    {{ $menu->name }}
                                    @if ($menu->children->count())
                                        <i class="right fas fa-angle-left"></i>
                                    @endif
                                </p>
                            </a>

                            @if ($menu->children->count())
                                <ul class="nav nav-treeview">
                                    @foreach ($menu->children as $child)
                                        <li class="nav-item">
                                            <a href="{{ $child->url ? url($child->url) : '#' }}" class="nav-link">
                                                <i class="{{ $child->icon }}"></i>
                                                <p>{{ $child->name }}</p>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                    

                    <li class="nav-item mt-3">
                        <form action="{{ url('/logout') }}" method="post">
                            @csrf
                            <button type="submit" class="nav-link btn btn-danger text-white w-100">
                                <i class="fas fa-sign-out-alt"></i> Keluar
                            </button>
                        </form>
                    </li>

                </ul>
            </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
