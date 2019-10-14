{{--<!DOCTYPE html>--}}
{{--<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">--}}
{{--    <head>--}}
{{--        <meta charset="utf-8">--}}
{{--        <meta name="viewport" content="width=device-width, initial-scale=1">--}}

{{--        <title>Laravel</title>--}}

{{--        <!-- Fonts -->--}}
{{--        <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">--}}

{{--        <!-- Styles -->--}}
{{--        <style>--}}
{{--            html, body {--}}
{{--                background-color: #fff;--}}
{{--                color: #636b6f;--}}
{{--                font-family: 'Nunito', sans-serif;--}}
{{--                font-weight: 200;--}}
{{--                height: 100vh;--}}
{{--                margin: 0;--}}
{{--            }--}}

{{--            .full-height {--}}
{{--                height: 100vh;--}}
{{--            }--}}

{{--            .flex-center {--}}
{{--                align-items: center;--}}
{{--                display: flex;--}}
{{--                justify-content: center;--}}
{{--            }--}}

{{--            .position-ref {--}}
{{--                position: relative;--}}
{{--            }--}}

{{--            .top-right {--}}
{{--                position: absolute;--}}
{{--                right: 10px;--}}
{{--                top: 18px;--}}
{{--            }--}}

{{--            .content {--}}
{{--                text-align: center;--}}
{{--            }--}}

{{--            .title {--}}
{{--                font-size: 84px;--}}
{{--            }--}}

{{--            .links > a {--}}
{{--                color: #636b6f;--}}
{{--                padding: 0 25px;--}}
{{--                font-size: 13px;--}}
{{--                font-weight: 600;--}}
{{--                letter-spacing: .1rem;--}}
{{--                text-decoration: none;--}}
{{--                text-transform: uppercase;--}}
{{--            }--}}

{{--            .m-b-md {--}}
{{--                margin-bottom: 30px;--}}
{{--            }--}}
{{--        </style>--}}
{{--    </head>--}}
{{--    <body>--}}
{{--        <div class="flex-center position-ref full-height">--}}
{{--            @if (Route::has('login'))--}}
{{--                <div class="top-right links">--}}
{{--                    @auth--}}
{{--                        <a href="{{ url('/home') }}">Home</a>--}}
{{--                    @else--}}
{{--                        <a href="{{ route('login') }}">Login</a>--}}

{{--                        @if (Route::has('register'))--}}
{{--                            <a href="{{ route('register') }}">Register</a>--}}
{{--                        @endif--}}
{{--                    @endauth--}}
{{--                </div>--}}
{{--            @endif--}}

{{--            <div class="content">--}}
{{--                <div class="title m-b-md">--}}
{{--                    Welcome--}}
{{--                </div>--}}
{{--            </div>--}}
{{--        </div>--}}
{{--    </body>--}}
{{--</html>--}}



<html>
<head>
    <!-- INCLUDE SESSION.JS JAVASCRIPT LIBRARY -->
    <script src="https://easternbank.test.gateway.mastercard.com/form/version/53/merchant/<MERCHANTID>/session.js"></script>
    <!-- APPLY CLICK-JACKING STYLING AND HIDE CONTENTS OF THE PAGE -->
    <style id="antiClickjack">body{display:none !important;}</style>
</head>
<body>

<!-- CREATE THE HTML FOR THE PAYMENT PAGE -->

<div>Please enter your payment details:</div>
<h3>Credit Card</h3>
<div>Card Number: <input type="text" id="card-number" class="input-field" title="card number" aria-label="enter your card number" value="" tabindex="1" ></div>
<div>Expiry Month:<input type="text" id="expiry-month" class="input-field" title="expiry month" aria-label="two digit expiry month" value="" tabindex="2" ></div>
<div>Expiry Year:<input type="text" id="expiry-year" class="input-field" title="expiry year" aria-label="two digit expiry year" value="" tabindex="3" ></div>
<div>Security Code:<input type="text" id="security-code" class="input-field" title="security code" aria-label="three digit CCV security code" value="" tabindex="4" ></div>
<div>Cardholder Name:<input type="text" id="cardholder-name" class="input-field" title="cardholder name" aria-label="enter name on card" value="" tabindex="5"></div>
<div><button id="payButton" onclick="pay('card');">Pay Now</button></div>

<!-- JAVASCRIPT FRAME-BREAKER CODE TO PROVIDE PROTECTION AGAINST IFRAME CLICK-JACKING -->
<script type="text/javascript">
    if (self === top) {
        var antiClickjack = document.getElementById("antiClickjack");
        antiClickjack.parentNode.removeChild(antiClickjack);
    } else {
        top.location = self.location;
    }

    PaymentSession.configure({
        fields: {
            // ATTACH HOSTED FIELDS TO YOUR PAYMENT PAGE FOR A CREDIT CARD
            card: {
                number: "#card-number",
                securityCode: "#security-code",
                expiryMonth: "#expiry-month",
                expiryYear: "#expiry-year",
                nameOnCard: "#cardholder-name"
            }
        },
        //SPECIFY YOUR MITIGATION OPTION HERE
        frameEmbeddingMitigation: ["javascript"],
        callbacks: {
            initialized: function(response) {
                // HANDLE INITIALIZATION RESPONSE
            },
            formSessionUpdate: function(response) {
                // HANDLE RESPONSE FOR UPDATE SESSION
                if (response.status) {
                    if ("ok" == response.status) {
                        console.log("Session updated with data: " + response.session.id);

                        //check if the security code was provided by the user
                        if (response.sourceOfFunds.provided.card.securityCode) {
                            console.log("Security code was provided.");
                        }

                        //check if the user entered a Mastercard credit card
                        if (response.sourceOfFunds.provided.card.scheme == 'MASTERCARD') {
                            console.log("The user entered a Mastercard credit card.")
                        }
                    } else if ("fields_in_error" == response.status)  {

                        console.log("Session update failed with field errors.");
                        if (response.errors.cardNumber) {
                            console.log("Card number invalid or missing.");
                        }
                        if (response.errors.expiryYear) {
                            console.log("Expiry year invalid or missing.");
                        }
                        if (response.errors.expiryMonth) {
                            console.log("Expiry month invalid or missing.");
                        }
                        if (response.errors.securityCode) {
                            console.log("Security code invalid.");
                        }
                    } else if ("request_timeout" == response.status)  {
                        console.log("Session update failed with request timeout: " + response.errors.message);
                    } else if ("system_error" == response.status)  {
                        console.log("Session update failed with system error: " + response.errors.message);
                    }
                } else {
                    console.log("Session update failed: " + response);
                }
            }
        },
        interaction: {
            displayControl: {
                formatCard: "EMBOSSED",
                invalidFieldCharacters: "REJECT"
            }
        }
    });

    function pay() {
        // UPDATE THE SESSION WITH THE INPUT FROM HOSTED FIELDS
        PaymentSession.updateSessionFromForm('card');
    }
</script>
</body>
</html>
