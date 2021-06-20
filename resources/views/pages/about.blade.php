
@extends('layouts.default')

@section('box_title')
<h1>What More About Us?<span class="m_1">Our services are open to any company.</span></h1>
@endsection

@section('top')
@include('includes.all_title_box')    
@endsection

@section('content')

<div style="min-height: 80%; padding: 20px;">
    <div class="ui stackable grid" style="font-size: 24px;">
        <div class="ten wide centered column">
            <div>Bla Bla Blah of About Us goes here.... Bla Bla Blah of About Us goes here.... Bla Bla Blah of About Us goes here.... Bla Bla Blah of About Us goes here.... Bla Bla Blah of About Us goes here.... Bla Bla Blah of About Us goes here.... Bla Bla Blah of About Us goes here.... Bla Bla Blah of About Us goes here.... </div>
        </div>

    </div>
    <div class="ui stackable three column grid">
        <div class="column">
            <div class="ui link fluid card" style="max-width: 300px; margin: 0 auto;">
                <div class="image">
                    <img src="{{ url('/') }}/uploads/testi_01.png">
                </div>
                <div class="content">
                    <a class="header">Daniel Louise</a>

                    <div class="meta">
                        <a>Managing Director</a>
                    </div>
                    <div class="description">
                        Matthew is an interior designer living in New York.
                    </div>
                </div>
            </div>
        </div>
        <div class="column">
            <div class="ui link fluid card" style="max-width: 300px; margin: 0 auto;">
                <div class="image">
                    <img src="{{ url('/') }}/uploads/testi_02.png">
                </div>
                <div class="content">
                    <a class="header">Helen Troy</a>

                    <div class="meta">
                        <a>Project Manager</a>
                    </div>
                    <div class="description">
                        Matthew is an interior designer living in New York.
                    </div>
                </div>
            </div>
        </div>
        <div class="column">
            <div class="ui link fluid card" style="max-width: 300px; margin: 0 auto;">
                <div class="image">
                    <img src="{{ url('/') }}/uploads/testi_03.png">
                </div>
                <div class="content">
                    <a class="header">Elliot Fu</a>

                    <div class="meta">
                        <a>Sales Manager</a>
                    </div>
                    <div class="description">
                        Matthew is an interior designer living in New York.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection