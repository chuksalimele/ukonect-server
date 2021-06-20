
@extends('layouts.default')

@section('box_title')
<h1>Contacting Us Is A Breeze<span class="m_1">Our services are open to any company.</span></h1>
@endsection

@section('top')
@include('includes.all_title_box')    
@endsection


@section('content')
<div id="support" class="section wb">
    <div class="container">
        <div class="section-title text-center">
            <h3>Need Help? We are always at your service!</h3>
            <div class="ui stackable three column grid">
                <div class="column">

                    <div class="ui card" style="min-height: 250px; margin: 0 auto;">
                        <div class="extra content">
                            <div class="center aligned author">
                                <a class="icon item"  style="font-size: 30px; color: #445;">
                                    <i class="phone icon"></i> Phone Support
                                </a>
                            </div>
                        </div>
                        <div class="content">
                            <div class="center aligned header">Need any assistance? Call us now and talk to our Support Team. </div>
                            <div class="center aligned description">
                                <p>Mobile: 0700 000 000 000 </p>
                            </div>
                        </div>

                    </div>



                </div>
                <div class="column">


                    <div class="ui card" style="min-height: 250px; margin: 0 auto;">
                        <div class="extra content">
                            <div class="center aligned author">
                                <a class="icon item"  style="font-size: 30px; color: #445;">
                                    <i class="home icon"></i> Office
                                </a>
                            </div>
                        </div>
                        <div class="content">
                            <div class="center aligned header">The Office addresss... The Office addresss... The Office addresss...</div>
                            <div class="center aligned description">
                                <p></p>
                            </div>
                        </div>

                    </div>




                </div>
                <div class="column">



                    <div class="ui card" style="min-height: 250px; margin: 0 auto;">
                        <div class="extra content">
                            <div class="center aligned author">
                               <a class="icon item"  style="font-size: 30px; color: #445;">
                                    <i class="mail icon"></i> Email Support
                                </a>
                            </div>
                        </div>
                        <div class="content">
                            <div class="center aligned header">Need any assistance? Send us an email now and chat with our Support Team. </div>
                            <div class="center aligned description">
                                <p>Eamil: my-email-address@domain.com </p>
                            </div>
                        </div>

                    </div>





                </div>
            </div>

        </div><!-- end title -->
        <p class="lead">Please fill out the form below.</p>
        <div class="row">
            <div class="col-md-12">
                <div class="contact_form">
                    <div id="message"></div>
                    <form id="contactform" class="row" action="contact.php" name="contactform" method="post">
                        <fieldset class="row-fluid">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <input type="text" name="first_name" id="first_name" class="form-control" placeholder="First Name">
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <input type="text" name="last_name" id="last_name" class="form-control" placeholder="Last Name">
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <input type="email" name="email" id="email" class="form-control" placeholder="Your Email">
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                <input type="text" name="phone" id="phone" class="form-control" placeholder="Your Phone">
                            </div>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                <textarea class="form-control" name="comments" id="comments" rows="6" placeholder="Give us more details.."></textarea>
                            </div>
                            <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-center">
                                <button type="submit" value="SEND" id="submit" class="large ui blue button"  style="min-width: 200px;">Send us a message</button>
                            </div>
                        </fieldset>
                    </form>
                </div>
            </div><!-- end col -->
        </div><!-- end row -->
    </div><!-- end container -->
</div><!-- end section -->

@endsection


