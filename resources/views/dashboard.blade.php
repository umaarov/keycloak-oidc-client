<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
<div class="container mx-auto mt-10 p-8 bg-white rounded-lg shadow-lg max-w-2xl">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-800">Dashboard</h1>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition duration-300">
                Logout
            </button>
        </form>
    </div>
    <hr class="my-6">
    <div class="text-lg">
        <p class="text-gray-700">Welcome back, <span class="font-bold">{{ Auth::user()->name }}</span>!</p>
        <p class="text-gray-600 mt-2">Your registered email is: <span class="font-mono">{{ Auth::user()->email }}</span></p>
    </div>
</div>
</body>
</html>
