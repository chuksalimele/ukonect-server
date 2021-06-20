<!DOCTYPE html>

<html>
    <head>
        @include('includes.head')
    </head>

    <body class="host_version"> 

        <!-- Modal -->
        <div class="modal fade" id="login" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            @include('includes.modal')
        </div>

        <!-- LOADER -->
        <div id="preloader">
            @include('includes.loader')
        </div>
        <!-- END LOADER -->

        <header class="header header_style_01" >
            @include('includes.header')        
        </header>

        @yield('top')

        @yield('content')

        @include('includes.footer')

        <!-- end footer -->

        <!--  Scripts-->
        <script src="https://code.jquery.com/jquery-2.1.1.min.js"></script>
        <script src="{{ url('/') }}/js/materialize.js"></script>
        <script src="{{ url('/') }}/js/init.js"></script>

    </body>
</html>
