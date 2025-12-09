<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Association Rule ECLAT')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-active {
            background-color: rgba(139, 92, 246, 0.1);
            border-left: 4px solid #8B5CF6;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-purple-700 text-white">
            <div class="p-4">
                <h1 class="text-xl font-bold flex items-center">
                    <i class="fas fa-project-diagram mr-2"></i>
                    Association Rule ECLAT
                </h1>
            </div>
                <nav class="mt-8">
                <a href="{{ route('beranda') }}" class="flex items-center px-6 py-3 text-white hover:bg-purple-800 transition {{ request()->routeIs('beranda') ? 'sidebar-active' : '' }}">
                    <i class="fas fa-home w-5 mr-3"></i>
                    <span>Beranda</span>
                </a>
                <a href="{{ route('data-transaksi') }}" class="flex items-center px-6 py-3 text-white hover:bg-purple-800 transition {{ request()->routeIs('data-transaksi') ? 'sidebar-active' : '' }}">
                    <i class="fas fa-exchange-alt w-5 mr-3"></i>
                    <span>Data Transaksi</span>
                </a>
                <a href="{{ route('data-proses') }}" class="flex items-center px-6 py-3 text-white hover:bg-purple-800 transition {{ request()->routeIs('data-proses') ? 'sidebar-active' : '' }}">
                    <i class="fas fa-cogs w-5 mr-3"></i>
                    <span>Data Proses</span>
                </a>
                <a href="{{ route('data-uji') }}" class="flex items-center px-6 py-3 text-white hover:bg-purple-800 transition {{ request()->routeIs('data-uji') ? 'sidebar-active' : '' }}">
                    <i class="fas fa-vial w-5 mr-3"></i>
                    <span>Data Uji</span>
                </a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 overflow-auto">
            @yield('content')
        </div>
    </div>
</body>
</html>