<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <script src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        inspiniaBg: '#f3f3f4',
                        inspiniaPrimary: '#1ab394',
                        inspiniaPrimaryHover: '#18a689',
                        inspiniaMuted: '#676a6c',
                    }
                }
            }
        }
    </script>
    <style>
        .logo-name {
            color: #e6e6e6;
            font-size: 140px;
            font-weight: 800;
            letter-spacing: -10px;
            line-height: 120px;
        }
        /* Custom Warna Toastr agar Selaras dengan INSPINIA Theme */
        #toast-container > .toast-warning {
            background-color: #f8ac59 !important; /* Warna Amber Warning khas Inspinia */
        }
        #toast-container > .toast-success {
            background-color: #1ab394 !important; /* Warna Emerald Primary Inspinia */
        }
    </style>
</head>
<body class="bg-inspiniaBg font-sans text-sm text-[#676a6c]">

    <div class="w-full max-w-[300px] mx-auto text-center pt-20 pb-6">
        <div>
            <div>
                <h1 class="logo-name">TS+</h1>
            </div>
            
            <h3 class="text-[18px] font-semibold text-[#676a6c] tracking-tight mb-2">Welcome to TS Asset</h3>
            <p class="text-xs mb-4">Perfectly designed and precisely prepared asset management application.</p>
            <p class="text-xs font-semibold mb-6">Login in. To see it in action.</p>

            <form class="space-y-4" action="{{ url('/login') }}" method="POST">
                @csrf
                
                <div>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Username / Email" raw-value required
                        class="w-full px-3 py-2 bg-white border border-[#e5e6e7] rounded text-sm text-[#676a6c] placeholder-gray-400 focus:border-inspiniaPrimary focus:outline-none transition duration-150">
                </div>
                
                <div>
                    <input type="password" name="password" placeholder="Password" required
                        class="w-full px-3 py-2 bg-white border border-[#e5e6e7] rounded text-sm text-[#676a6c] placeholder-gray-400 focus:border-inspiniaPrimary focus:outline-none transition duration-150">
                </div>

                <div class="flex items-center text-left text-xs text-inspiniaMuted px-0.5">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-[#e5e6e7] text-inspiniaPrimary focus:ring-inspiniaPrimary">
                        <span class="ml-2 select-none">Remember me on this machine</span>
                    </label>
                </div>

                <button type="submit" 
                    class="w-full bg-inspiniaPrimary text-white py-2 px-3 rounded text-sm font-medium hover:bg-inspiniaPrimaryHover transition duration-150 block shadow-sm">
                    Login
                </button>

                <div class="py-2">
                    <a href="#" class="text-xs text-inspiniaPrimary hover:underline"><small>Forgot password?</small></a>
                </div>
                
                <p class="text-xs text-center text-gray-400"><small>Do not have an account?</small></p>
                <a class="w-full bg-white text-inspiniaMuted border border-[#e5e6e7] py-1.5 px-3 rounded text-xs font-medium hover:bg-gray-50 transition duration-150 block text-center shadow-xs" 
                   href="#">
                   Create an account
                </a>
            </form>
            
            <p class="mt-8 text-xs text-gray-400"> 
                <small>TS Asset Management Framework based on Inspinia &copy; 2026</small> 
            </p>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "timeOut": "4000"
            };

            @if(session('toast_warning'))
                toastr.warning("{{ session('toast_warning') }}", "Peringatan Sistem");
            @endif

            @if(session('toast_success'))
                toastr.success("{{ session('toast_success') }}", "Sukses");
            @endif
        });
    </script>

</body>
</html>
